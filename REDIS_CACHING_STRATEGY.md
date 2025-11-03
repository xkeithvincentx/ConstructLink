# Redis Caching Strategy for ConstructLink Asset Management System

## Executive Summary

This document outlines a comprehensive Redis caching strategy for the ConstructLink Asset Management System. The analysis reveals that the `assets` table is the central data entity accessed by all major operations (borrowed tools, transfers, withdrawals), making it an ideal candidate for Redis caching.

**Key Findings:**
- Assets table is queried by ALL modules with complex multi-table JOINs
- Each operation joins 4-7 tables (assets, categories, projects, users, etc.)
- High-traffic pagination and filtering operations create significant database load
- Current architecture performs redundant queries for the same asset data

**Expected Benefits:**
- 60-80% reduction in database query load
- 40-70% improvement in response times for list and detail views
- Reduced database connection pool exhaustion
- Better scalability for concurrent users

---

## 1. Database Analysis

### 1.1 Assets Table Structure

The `assets` table is the cornerstone of the system with:

**Key Fields:**
- `id` (Primary Key)
- `ref` (Unique asset reference)
- `category_id` → categories
- `project_id` → projects
- `maker_id` → makers
- `vendor_id` → vendors
- `client_id` → clients
- `procurement_order_id` → procurement_orders
- `procurement_item_id` → procurement_items
- `status` (ENUM: available, in_use, borrowed, under_maintenance, retired, disposed)
- `quantity`, `available_quantity` (for consumable items)

**Indexes:**
- Primary: `id`
- Unique: `ref`
- Foreign Keys: `category_id`, `project_id`, `maker_id`, `vendor_id`, etc.
- Composite: `(project_id, status)`, `(status)`, `(quantity)`, `(available_quantity)`

### 1.2 Dependent Operations

**Borrowed Tools:**
```sql
SELECT bt.*, a.name, a.ref, c.name, p.name, u.full_name
FROM borrowed_tools bt
INNER JOIN assets a ON bt.asset_id = a.id
INNER JOIN categories c ON a.category_id = c.id
INNER JOIN projects p ON a.project_id = p.id
LEFT JOIN users u ON bt.issued_by = u.id
```

**Transfers:**
```sql
SELECT t.*, a.name, a.ref, c.name,
       pf.name as from_project, pt.name as to_project,
       ui.full_name
FROM transfers t
LEFT JOIN assets a ON t.asset_id = a.id
LEFT JOIN categories c ON a.category_id = c.id
LEFT JOIN projects pf ON t.from_project = pf.id
LEFT JOIN projects pt ON t.to_project = pt.id
```

**Withdrawals:**
```sql
SELECT w.*, a.name, a.ref, c.name, p.name, u.full_name
FROM withdrawals w
LEFT JOIN assets a ON w.asset_id = a.id
LEFT JOIN categories c ON a.category_id = c.id
LEFT JOIN projects p ON w.project_id = p.id
LEFT JOIN users u ON w.withdrawn_by = u.id
```

### 1.3 Query Hotspots Identified

1. **Pagination Queries** - Frequent COUNT(*) and paginated SELECT queries
2. **Detail View Queries** - Full asset details with all relationships
3. **Status Filtering** - WHERE status = 'X' queries
4. **Project Scoping** - WHERE project_id = X queries
5. **Aggregate Queries** - SUM(acquisition_cost), COUNT(status) queries

---

## 2. Recommended Caching Strategy

### 2.1 Primary Pattern: Cache-Aside (Lazy Loading)

**Why Cache-Aside?**
- Most popular and battle-tested pattern
- Application has full control over cache population
- Handles cache failures gracefully (falls back to database)
- Simplest to implement and debug

**How It Works:**
1. Application checks Redis for cached data
2. **Cache Hit:** Return data from Redis
3. **Cache Miss:** Query database → Store in Redis → Return to user

### 2.2 Data Caching Approach: Redis Hashes

**Why Redis Hashes?**
- Efficient for storing structured relational data
- Support partial updates (HSET for specific fields)
- Field-level operations (HGET, HMGET)
- Better memory efficiency than JSON strings for large objects
- Enables atomic updates of individual fields

**Alternative for Complex Objects:** JSON strings for denormalized queries with multiple JOINs

---

## 3. Implementation Plan

### 3.1 Cache Key Design

**Principle:** Hierarchical, predictable, easily invalidated

```
Pattern: {entity}:{id}
         {entity}:{id}:{relation}
         {entity}:list:{filters_hash}
         {entity}:count:{filters_hash}

Examples:
  asset:1234                           → Single asset (Hash)
  asset:1234:full                      → Full asset with all relations (JSON)
  asset:list:project_5_status_available → Filtered list (JSON array of IDs)
  asset:count:project_5                → Count cache
  category:42                          → Category data
  project:5                            → Project data
  user:12                              → User data
```

### 3.2 Cache Layers

#### Layer 1: Individual Entity Cache (Foundation)

**What to Cache:**
- Individual assets: `asset:{id}`
- Categories: `category:{id}`
- Projects: `project:{id}`
- Users: `user:{id}`
- Makers: `maker:{id}`
- Vendors: `vendor:{id}`

**Storage Format:** Redis Hash

**TTL:**
- Assets: 1 hour (with sliding TTL on access)
- Categories: 24 hours (rarely change)
- Projects: 6 hours
- Users: 30 minutes
- Reference data (makers, vendors): 12 hours

**Example PHP Implementation:**

```php
class AssetCache {
    private $redis;
    private $assetModel;

    const ASSET_TTL = 3600; // 1 hour
    const ASSET_KEY_PREFIX = 'asset:';

    public function __construct($redis, $assetModel) {
        $this->redis = $redis;
        $this->assetModel = $assetModel;
    }

    /**
     * Get asset with cache-aside pattern
     */
    public function getAsset($assetId) {
        $cacheKey = self::ASSET_KEY_PREFIX . $assetId;

        // Check cache first
        $cached = $this->redis->hGetAll($cacheKey);

        if (!empty($cached)) {
            // Cache hit - refresh TTL (sliding window)
            $this->redis->expire($cacheKey, self::ASSET_TTL);
            return $cached;
        }

        // Cache miss - fetch from database
        $asset = $this->assetModel->find($assetId);

        if (!$asset) {
            return null;
        }

        // Store in cache
        $this->setAssetCache($assetId, $asset);

        return $asset;
    }

    /**
     * Store asset in cache
     */
    private function setAssetCache($assetId, $asset) {
        $cacheKey = self::ASSET_KEY_PREFIX . $assetId;

        // Convert to flat array for HMSET
        $data = [];
        foreach ($asset as $field => $value) {
            $data[$field] = $value === null ? '' : (string)$value;
        }

        // Use pipeline for atomic operation
        $this->redis->multi();
        $this->redis->hMSet($cacheKey, $data);
        $this->redis->expire($cacheKey, self::ASSET_TTL);
        $this->redis->exec();
    }

    /**
     * Invalidate asset cache
     */
    public function invalidateAsset($assetId) {
        $cacheKey = self::ASSET_KEY_PREFIX . $assetId;
        $this->redis->del($cacheKey);

        // Also invalidate full asset cache
        $this->redis->del($cacheKey . ':full');
    }
}
```

#### Layer 2: Denormalized Query Cache (Performance)

**What to Cache:**
- Full asset details with JOINs: `asset:{id}:full`
- Common query results: `asset:list:{hash}`

**Storage Format:** JSON strings

**TTL:** 15-30 minutes (shorter due to denormalization)

**Example:**

```php
class AssetQueryCache {
    private $redis;

    const FULL_ASSET_TTL = 1800; // 30 minutes

    /**
     * Get full asset details with all relationships
     */
    public function getAssetWithDetails($assetId) {
        $cacheKey = "asset:{$assetId}:full";

        // Check cache
        $cached = $this->redis->get($cacheKey);

        if ($cached !== false) {
            return json_decode($cached, true);
        }

        // Build from individual caches or query database
        $asset = $this->buildFullAsset($assetId);

        if ($asset) {
            // Cache as JSON with TTL
            $this->redis->setex(
                $cacheKey,
                self::FULL_ASSET_TTL,
                json_encode($asset)
            );
        }

        return $asset;
    }

    /**
     * Build full asset from individual caches
     */
    private function buildFullAsset($assetId) {
        $assetCache = new AssetCache($this->redis, new AssetModel());
        $asset = $assetCache->getAsset($assetId);

        if (!$asset) {
            return null;
        }

        // Fetch related entities from cache
        if (!empty($asset['category_id'])) {
            $asset['category'] = $this->getCachedEntity('category', $asset['category_id']);
        }

        if (!empty($asset['project_id'])) {
            $asset['project'] = $this->getCachedEntity('project', $asset['project_id']);
        }

        // ... other relations

        return $asset;
    }

    private function getCachedEntity($type, $id) {
        $key = "{$type}:{$id}";
        $data = $this->redis->hGetAll($key);
        return !empty($data) ? $data : null;
    }
}
```

#### Layer 3: List and Pagination Cache (Scalability)

**What to Cache:**
- Paginated result IDs: `asset:list:project_5_page_1`
- Count queries: `asset:count:project_5_status_available`

**Storage Format:**
- Lists: JSON array of IDs
- Counts: Integer string

**TTL:** 5-10 minutes (frequently changing)

**Example:**

```php
class AssetListCache {
    private $redis;

    const LIST_TTL = 300; // 5 minutes

    /**
     * Get paginated asset list with caching
     */
    public function getAssetList($filters = [], $page = 1, $perPage = 20) {
        // Generate cache key from filters
        $filterHash = $this->generateFilterHash($filters);
        $listKey = "asset:list:{$filterHash}_page_{$page}";
        $countKey = "asset:count:{$filterHash}";

        // Try to get cached list
        $cachedList = $this->redis->get($listKey);
        $cachedCount = $this->redis->get($countKey);

        if ($cachedList !== false && $cachedCount !== false) {
            $assetIds = json_decode($cachedList, true);

            // Fetch individual assets (from cache or DB)
            $assets = $this->fetchAssetsByIds($assetIds);

            return [
                'data' => $assets,
                'total' => (int)$cachedCount,
                'page' => $page,
                'per_page' => $perPage
            ];
        }

        // Cache miss - query database
        $result = $this->queryDatabase($filters, $page, $perPage);

        // Cache the result
        $assetIds = array_column($result['data'], 'id');
        $this->redis->setex($listKey, self::LIST_TTL, json_encode($assetIds));
        $this->redis->setex($countKey, self::LIST_TTL, $result['total']);

        return $result;
    }

    /**
     * Generate consistent hash from filters
     */
    private function generateFilterHash($filters) {
        ksort($filters); // Ensure consistent ordering
        return md5(json_encode($filters));
    }

    /**
     * Fetch multiple assets efficiently
     */
    private function fetchAssetsByIds($ids) {
        $assets = [];
        $missingIds = [];

        // Try to get from cache first
        foreach ($ids as $id) {
            $asset = $this->redis->hGetAll("asset:{$id}");
            if (!empty($asset)) {
                $assets[$id] = $asset;
            } else {
                $missingIds[] = $id;
            }
        }

        // Batch fetch missing assets from database
        if (!empty($missingIds)) {
            $dbAssets = $this->batchFetchFromDb($missingIds);

            // Cache fetched assets
            foreach ($dbAssets as $asset) {
                $this->cacheAsset($asset);
                $assets[$asset['id']] = $asset;
            }
        }

        // Return in original order
        return array_values(array_intersect_key($assets, array_flip($ids)));
    }
}
```

### 3.3 Cache Invalidation Strategy

**Critical Principle:** On write operations, **DELETE cache**, don't update it.

#### Invalidation Triggers

**Asset Create:**
- Invalidate: List caches for that project/category
- No need to create cache entry (lazy loading will handle it)

**Asset Update:**
```php
public function updateAsset($assetId, $data) {
    // Update database first
    $this->assetModel->update($assetId, $data);

    // Invalidate caches
    $this->invalidateAssetCaches($assetId);
}

private function invalidateAssetCaches($assetId) {
    $asset = $this->assetModel->find($assetId);

    // Delete individual caches
    $this->redis->del("asset:{$assetId}");
    $this->redis->del("asset:{$assetId}:full");

    // Delete related list caches
    $this->invalidateListCaches([
        'project_id' => $asset['project_id'],
        'category_id' => $asset['category_id'],
        'status' => $asset['status']
    ]);
}
```

**Borrowed Tool Create/Update:**
```php
// When borrowed tool is created/updated
public function handleBorrowedToolChange($borrowedTool) {
    // Invalidate the asset cache (status might change to 'borrowed')
    $this->redis->del("asset:{$borrowedTool['asset_id']}");
    $this->redis->del("asset:{$borrowedTool['asset_id']}:full");

    // Invalidate list caches
    $this->invalidateAssetListCaches($borrowedTool['asset_id']);
}
```

**Transfer/Withdrawal Operations:**
- Same pattern: invalidate asset cache when operation changes asset status/location

#### Pattern-Based Invalidation

For list caches, use pattern matching:

```php
public function invalidateListCaches($filters) {
    // Get all matching keys
    $pattern = "asset:list:*";
    $keys = $this->redis->keys($pattern);

    // Filter keys that match our criteria
    $toDelete = [];
    foreach ($keys as $key) {
        // Parse key and check if it matches our filters
        if ($this->keyMatchesFilters($key, $filters)) {
            $toDelete[] = $key;
        }
    }

    // Batch delete
    if (!empty($toDelete)) {
        $this->redis->del($toDelete);
    }

    // Also invalidate count caches
    $countPattern = "asset:count:*";
    $countKeys = $this->redis->keys($countPattern);
    // Similar filtering logic
}
```

**Warning:** Using `KEYS` pattern matching in production can be slow. Consider these alternatives:

1. **Tag-based Invalidation:**
```php
// When caching a list, also add to a set
$filterHash = $this->generateFilterHash($filters);
$this->redis->sAdd("asset:lists:project_{$projectId}", "asset:list:{$filterHash}");

// On invalidation
$lists = $this->redis->sMembers("asset:lists:project_{$projectId}");
$this->redis->del($lists);
$this->redis->del("asset:lists:project_{$projectId}");
```

2. **Time-based Expiration:**
   - Use aggressive TTLs for list caches (5-10 minutes)
   - Accept eventual consistency

### 3.4 Cache Stampede Prevention

**Problem:** When cache expires, multiple simultaneous requests hit the database

**Solution: Probabilistic Early Expiration + Lock**

```php
class CacheStampedeProtection {
    private $redis;

    public function getWithLock($key, $ttl, $callable) {
        // Check cache
        $cached = $this->redis->get($key);

        if ($cached !== false) {
            // Probabilistic early expiration
            $ttlRemaining = $this->redis->ttl($key);
            $expireEarly = $this->shouldExpireEarly($ttl, $ttlRemaining);

            if (!$expireEarly) {
                return json_decode($cached, true);
            }
        }

        // Try to acquire lock
        $lockKey = "{$key}:lock";
        $lockAcquired = $this->redis->set($lockKey, '1', ['NX', 'EX' => 10]);

        if ($lockAcquired) {
            // This request will refresh the cache
            try {
                $data = $callable();
                $this->redis->setex($key, $ttl, json_encode($data));
                return $data;
            } finally {
                $this->redis->del($lockKey);
            }
        } else {
            // Lock not acquired - serve stale data or wait
            if ($cached !== false) {
                return json_decode($cached, true); // Serve stale
            }

            // Wait for lock to be released and retry
            usleep(100000); // 100ms
            return $this->getWithLock($key, $ttl, $callable);
        }
    }

    /**
     * Probabilistic early expiration
     * Helps prevent stampede by refreshing cache before actual expiration
     */
    private function shouldExpireEarly($ttl, $ttlRemaining) {
        $delta = 60; // 60 second window
        $probability = $delta * log(rand(1, PHP_INT_MAX) / PHP_INT_MAX) / $ttlRemaining;
        return $probability < 0;
    }
}
```

---

## 4. Performance Optimizations

### 4.1 Batch Operations

**Problem:** Fetching multiple assets individually is inefficient

**Solution:** Use Redis pipelining and MGET

```php
public function getMultipleAssets($assetIds) {
    // Use pipeline for multiple operations
    $pipeline = $this->redis->pipeline();

    foreach ($assetIds as $id) {
        $pipeline->hGetAll("asset:{$id}");
    }

    $results = $pipeline->execute();

    // Identify missing assets
    $assets = [];
    $missingIds = [];

    foreach ($assetIds as $index => $id) {
        if (!empty($results[$index])) {
            $assets[$id] = $results[$index];
        } else {
            $missingIds[] = $id;
        }
    }

    // Batch fetch missing from database
    if (!empty($missingIds)) {
        $dbAssets = $this->assetModel->findByIds($missingIds);

        // Batch cache them
        $this->batchCacheAssets($dbAssets);

        foreach ($dbAssets as $asset) {
            $assets[$asset['id']] = $asset;
        }
    }

    return $assets;
}
```

### 4.2 Query Result Compression

For large result sets, compress JSON before caching:

```php
public function cacheCompressed($key, $data, $ttl) {
    $json = json_encode($data);
    $compressed = gzcompress($json, 6); // Level 6 compression
    $this->redis->setex($key, $ttl, $compressed);
}

public function getCompressed($key) {
    $compressed = $this->redis->get($key);
    if ($compressed === false) {
        return null;
    }
    $json = gzuncompress($compressed);
    return json_decode($json, true);
}
```

### 4.3 Partial Field Updates

Use Redis Hash field operations for partial updates:

```php
// Update only specific fields without invalidating entire cache
public function updateAssetStatus($assetId, $newStatus) {
    $this->assetModel->update($assetId, ['status' => $newStatus]);

    // Update cache field directly
    $this->redis->hSet("asset:{$assetId}", 'status', $newStatus);

    // Still invalidate full denormalized cache
    $this->redis->del("asset:{$assetId}:full");

    // Invalidate list caches
    $this->invalidateListCaches(['status' => $newStatus]);
}
```

---

## 5. Implementation Roadmap

### Phase 1: Foundation (Week 1-2)

**Goal:** Set up Redis infrastructure and basic caching

1. **Install and configure Redis**
   - Install Redis server
   - Install PHP Redis extension or Predis library
   - Configure Redis connection pooling
   - Set up Redis monitoring

2. **Create cache service layer**
   ```
   /services/cache/
   ├── RedisConnection.php
   ├── AssetCache.php
   ├── CategoryCache.php
   ├── ProjectCache.php
   └── UserCache.php
   ```

3. **Implement Layer 1: Individual entity caching**
   - Asset cache with hash storage
   - Category, project, user caches
   - Basic TTL management

4. **Add cache invalidation to existing update methods**
   - AssetModel::update()
   - AssetModel::delete()

**Success Metrics:**
- Asset detail views: 50% faster
- Database query reduction: 30-40%

### Phase 2: Query Optimization (Week 3-4)

**Goal:** Cache complex queries and relationships

1. **Implement Layer 2: Denormalized query cache**
   - Full asset details with relationships
   - Cache stampede protection

2. **Implement Layer 3: List and pagination cache**
   - Cached pagination results
   - Count query caching
   - Filter-based cache keys

3. **Optimize borrowed tools, transfers, withdrawals**
   - Cache denormalized query results
   - Implement batch fetching

**Success Metrics:**
- List views: 60% faster
- Database query reduction: 60-70%
- Pagination performance: 70% improvement

### Phase 3: Advanced Patterns (Week 5-6)

**Goal:** Implement advanced caching patterns

1. **Implement probabilistic early expiration**
2. **Add cache warming for common queries**
3. **Implement tag-based invalidation**
4. **Add cache compression for large datasets**
5. **Implement cache analytics and monitoring**

**Success Metrics:**
- Cache hit rate: >80%
- Cache stampede incidents: <5% of requests
- Overall system response time: 70% improvement

### Phase 4: Monitoring and Tuning (Ongoing)

1. **Set up monitoring dashboards**
   - Cache hit/miss rates
   - Cache memory usage
   - Eviction rates
   - Slow queries

2. **Performance tuning**
   - Adjust TTL values based on usage patterns
   - Optimize cache key design
   - Fine-tune eviction policies

3. **Documentation and training**
   - Developer guidelines
   - Cache debugging procedures
   - Best practices documentation

---

## 6. Redis Configuration

### 6.1 Recommended Redis Settings

```conf
# /etc/redis/redis.conf

# Memory management
maxmemory 2gb
maxmemory-policy allkeys-lru  # Evict least recently used keys

# Persistence (optional for cache)
save ""  # Disable RDB snapshots for pure cache usage
appendonly no  # No need for AOF persistence

# Performance
tcp-backlog 511
timeout 300
tcp-keepalive 60

# Networking
bind 127.0.0.1
port 6379
protected-mode yes

# Limits
maxclients 10000

# Logging
loglevel notice
logfile /var/log/redis/redis.log
```

### 6.2 PHP Redis Configuration

```php
// config/redis.php
return [
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', 6379),
        'password' => env('REDIS_PASSWORD', null),
        'database' => env('REDIS_DB', 0),
        'timeout' => 2.5,
        'read_timeout' => 2.5,
        'retry_interval' => 100,

        // Connection pooling
        'persistent' => true,
        'persistent_id' => 'constructlink',

        // Serialization
        'serializer' => Redis::SERIALIZER_NONE, // We handle JSON ourselves
    ],

    // Separate database for session storage
    'session' => [
        'database' => 1,
    ],

    // Separate database for queue
    'queue' => [
        'database' => 2,
    ]
];
```

---

## 7. Monitoring and Maintenance

### 7.1 Key Metrics to Monitor

**Cache Performance:**
- Hit rate: Target >80%
- Miss rate: Target <20%
- Eviction rate: Should be low (<10% of operations)
- Memory usage: Keep below 80% of maxmemory

**Application Performance:**
- Average query response time
- Database connection pool usage
- Redis connection pool usage
- Error rates

### 7.2 Monitoring Implementation

```php
class CacheMonitor {
    private $redis;

    public function recordHit($key) {
        $this->redis->incr('cache:stats:hits');
        $this->redis->incr("cache:stats:hits:" . date('Y-m-d-H'));
    }

    public function recordMiss($key) {
        $this->redis->incr('cache:stats:misses');
        $this->redis->incr("cache:stats:misses:" . date('Y-m-d-H'));
    }

    public function getHitRate($hours = 24) {
        $hits = 0;
        $misses = 0;

        for ($i = 0; $i < $hours; $i++) {
            $hour = date('Y-m-d-H', strtotime("-{$i} hours"));
            $hits += (int)$this->redis->get("cache:stats:hits:{$hour}");
            $misses += (int)$this->redis->get("cache:stats:misses:{$hour}");
        }

        $total = $hits + $misses;
        return $total > 0 ? ($hits / $total) * 100 : 0;
    }

    public function getStats() {
        $info = $this->redis->info();

        return [
            'memory_used' => $info['used_memory_human'],
            'memory_peak' => $info['used_memory_peak_human'],
            'connected_clients' => $info['connected_clients'],
            'total_commands' => $info['total_commands_processed'],
            'hit_rate' => $this->getHitRate(),
            'evicted_keys' => $info['evicted_keys'],
        ];
    }
}
```

### 7.3 Cache Health Checks

```php
class CacheHealthCheck {
    public function performHealthCheck() {
        $checks = [
            'connection' => $this->checkConnection(),
            'memory' => $this->checkMemory(),
            'latency' => $this->checkLatency(),
            'hit_rate' => $this->checkHitRate(),
        ];

        return [
            'healthy' => !in_array(false, $checks),
            'checks' => $checks,
            'timestamp' => time()
        ];
    }

    private function checkConnection() {
        try {
            return $this->redis->ping() === '+PONG';
        } catch (Exception $e) {
            return false;
        }
    }

    private function checkMemory() {
        $info = $this->redis->info('memory');
        $used = $info['used_memory'];
        $max = $info['maxmemory'];

        // Alert if over 90% memory usage
        return ($used / $max) < 0.9;
    }

    private function checkLatency() {
        $start = microtime(true);
        $this->redis->ping();
        $latency = (microtime(true) - $start) * 1000;

        // Alert if latency > 10ms
        return $latency < 10;
    }

    private function checkHitRate() {
        $monitor = new CacheMonitor($this->redis);
        $hitRate = $monitor->getHitRate();

        // Alert if hit rate < 60%
        return $hitRate >= 60;
    }
}
```

---

## 8. Common Pitfalls and Solutions

### 8.1 Cache Stampede

**Problem:** Multiple requests hit database simultaneously when cache expires

**Solution:**
- Implement locking mechanism (shown in section 3.4)
- Use probabilistic early expiration
- Implement cache warming for critical data

### 8.2 Stale Data

**Problem:** Cache contains outdated information

**Solution:**
- Aggressive invalidation on writes
- Reasonable TTLs
- Version-based caching:
  ```php
  // Store version with data
  $version = $this->redis->incr('asset:version');
  $this->redis->setex("asset:{$id}:v{$version}", $ttl, $data);
  ```

### 8.3 Memory Exhaustion

**Problem:** Redis runs out of memory

**Solution:**
- Set appropriate maxmemory limit
- Use allkeys-lru eviction policy
- Monitor memory usage
- Implement cache key TTLs
- Compress large values

### 8.4 Cache Invalidation Complexity

**Problem:** Hard to track all dependencies for invalidation

**Solution:**
- Use tag-based invalidation
- Document cache dependencies
- Implement defensive TTLs
- Accept eventual consistency where appropriate

### 8.5 Thundering Herd

**Problem:** When a popular item expires, many requests try to regenerate it

**Solution:**
```php
public function getWithRegeneration($key, $ttl, $generator) {
    $value = $this->redis->get($key);

    if ($value !== false) {
        // Check if value is about to expire (last 10% of TTL)
        $remaining = $this->redis->ttl($key);

        if ($remaining < ($ttl * 0.1)) {
            // Extend TTL while regenerating
            $this->redis->expire($key, 60);

            // Asynchronously regenerate
            $this->asyncRegenerate($key, $ttl, $generator);
        }

        return json_decode($value, true);
    }

    // Cache miss - regenerate synchronously
    return $this->getWithLock($key, $ttl, $generator);
}
```

---

## 9. Testing Strategy

### 9.1 Unit Tests

```php
class AssetCacheTest extends TestCase {
    public function testCacheHit() {
        $cache = new AssetCache($this->redis, $this->assetModel);

        // Pre-populate cache
        $asset = ['id' => 1, 'name' => 'Test Asset'];
        $this->redis->hMSet('asset:1', $asset);

        // Should return from cache
        $result = $cache->getAsset(1);

        $this->assertEquals($asset, $result);
        $this->assertDatabaseNotQueried();
    }

    public function testCacheMiss() {
        $cache = new AssetCache($this->redis, $this->assetModel);

        // No cache - should query database
        $result = $cache->getAsset(1);

        $this->assertNotNull($result);
        $this->assertDatabaseQueried();

        // Should now be in cache
        $cached = $this->redis->hGetAll('asset:1');
        $this->assertNotEmpty($cached);
    }

    public function testInvalidation() {
        $cache = new AssetCache($this->redis, $this->assetModel);

        // Populate cache
        $cache->getAsset(1);

        // Invalidate
        $cache->invalidateAsset(1);

        // Should be removed from cache
        $cached = $this->redis->hGetAll('asset:1');
        $this->assertEmpty($cached);
    }
}
```

### 9.2 Integration Tests

```php
class AssetCacheIntegrationTest extends TestCase {
    public function testFullWorkflow() {
        // Create asset
        $assetId = $this->createAsset([
            'name' => 'Test Asset',
            'category_id' => 1,
            'project_id' => 1
        ]);

        // First fetch - cache miss
        $start = microtime(true);
        $asset1 = $this->getAssetWithDetails($assetId);
        $time1 = microtime(true) - $start;

        // Second fetch - cache hit
        $start = microtime(true);
        $asset2 = $this->getAssetWithDetails($assetId);
        $time2 = microtime(true) - $start;

        // Cache hit should be significantly faster
        $this->assertLessThan($time1 * 0.3, $time2);
        $this->assertEquals($asset1, $asset2);

        // Update asset
        $this->updateAsset($assetId, ['name' => 'Updated Asset']);

        // Fetch again - should reflect update
        $asset3 = $this->getAssetWithDetails($assetId);
        $this->assertEquals('Updated Asset', $asset3['name']);
    }
}
```

### 9.3 Load Testing

```php
// Load test script - simulate concurrent requests
class CacheLoadTest {
    public function testConcurrentReads() {
        $assetId = 1;
        $requests = 100;
        $concurrent = 10;

        $results = $this->runConcurrent($concurrent, function() use ($assetId) {
            $start = microtime(true);
            $this->getAsset($assetId);
            return microtime(true) - $start;
        }, $requests);

        $avgTime = array_sum($results) / count($results);
        $maxTime = max($results);

        echo "Average time: {$avgTime}s\n";
        echo "Max time: {$maxTime}s\n";
        echo "Cache stampede detected: " . ($maxTime > $avgTime * 3 ? 'YES' : 'NO') . "\n";
    }
}
```

---

## 10. Conclusion

### 10.1 Expected Benefits

**Performance Improvements:**
- 60-80% reduction in database queries
- 40-70% faster response times
- 50-60% reduction in server load
- 3-5x increase in concurrent user capacity

**Operational Benefits:**
- Reduced database connection pool exhaustion
- Better user experience during peak loads
- Improved system scalability
- Lower infrastructure costs

### 10.2 Implementation Effort

**Total Estimated Time:** 4-6 weeks

- **Week 1-2:** Infrastructure setup and Layer 1 caching
- **Week 3-4:** Query optimization and Layer 2/3 caching
- **Week 5-6:** Advanced patterns and monitoring
- **Ongoing:** Monitoring, tuning, and optimization

**Required Resources:**
- 1 Senior Developer (full-time)
- 1 DevOps Engineer (part-time for Redis setup)
- Redis server (2-4GB RAM recommended)

### 10.3 Risks and Mitigation

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Cache inconsistency | High | Medium | Aggressive invalidation, defensive TTLs |
| Memory exhaustion | High | Low | Monitoring, eviction policies, compression |
| Cache stampede | Medium | Medium | Locking, early expiration, cache warming |
| Increased complexity | Medium | High | Documentation, training, monitoring |
| Redis downtime | High | Low | Graceful fallback to database |

### 10.4 Success Criteria

**Phase 1 (Week 2):**
- ✓ Cache hit rate > 60% for asset details
- ✓ Response time improvement > 30%
- ✓ No cache-related bugs in production

**Phase 2 (Week 4):**
- ✓ Cache hit rate > 75% overall
- ✓ Response time improvement > 50%
- ✓ Database query reduction > 60%

**Phase 3 (Week 6):**
- ✓ Cache hit rate > 80%
- ✓ Response time improvement > 70%
- ✓ Zero cache stampede incidents
- ✓ Monitoring dashboards operational

### 10.5 Next Steps

1. **Review and approve this strategy** with technical team
2. **Provision Redis server** (development, staging, production)
3. **Set up development environment** with Redis
4. **Begin Phase 1 implementation** (Foundation)
5. **Establish monitoring** from day one
6. **Iterative deployment** with careful testing
7. **Gather metrics** and adjust strategy as needed

---

## 11. References and Resources

**Official Documentation:**
- Redis Official Documentation: https://redis.io/documentation
- PHP Redis Extension: https://github.com/phpredis/phpredis
- Predis Library: https://github.com/predis/predis

**Best Practices:**
- AWS Database Caching Strategies Using Redis: https://docs.aws.amazon.com/whitepapers/latest/database-caching-strategies-using-redis/
- Redis Caching Patterns: https://redis.io/glossary/cache-invalidation/

**Performance Optimization:**
- Redis Memory Optimization: https://redis.io/topics/memory-optimization
- Redis Pipelining: https://redis.io/topics/pipelining

**Monitoring:**
- Redis Monitoring Guide: https://redis.io/topics/admin
- RedisInsight: https://redis.com/redis-enterprise/redis-insight/

---

**Document Version:** 1.0
**Last Updated:** 2025-11-03
**Prepared By:** Claude Code AI Assistant
**For:** ConstructLink Asset Management System
