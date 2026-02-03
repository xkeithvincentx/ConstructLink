#!/bin/bash
# Verification script for Phase 1 refactoring

echo "=== Phase 1 Refactoring Verification ==="
echo ""

# Check file existence
echo "1. Checking new files..."
files=(
    "middleware/PermissionMiddleware.php"
    "repositories/BrandRepository.php"
    "utils/FormDataProvider.php"
    "utils/ControllerErrorHandler.php"
    "controllers/AssetController.php.backup"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo "   ✓ $file exists"
    else
        echo "   ✗ $file MISSING"
    fi
done

echo ""
echo "2. Checking syntax..."
php -l middleware/PermissionMiddleware.php 2>&1 | grep -q "No syntax errors" && echo "   ✓ PermissionMiddleware.php" || echo "   ✗ PermissionMiddleware.php"
php -l repositories/BrandRepository.php 2>&1 | grep -q "No syntax errors" && echo "   ✓ BrandRepository.php" || echo "   ✗ BrandRepository.php"
php -l utils/FormDataProvider.php 2>&1 | grep -q "No syntax errors" && echo "   ✓ FormDataProvider.php" || echo "   ✗ FormDataProvider.php"
php -l utils/ControllerErrorHandler.php 2>&1 | grep -q "No syntax errors" && echo "   ✓ ControllerErrorHandler.php" || echo "   ✗ ControllerErrorHandler.php"
php -l controllers/AssetController.php 2>&1 | grep -q "No syntax errors" && echo "   ✓ AssetController.php" || echo "   ✗ AssetController.php"

echo ""
echo "3. Line count comparison..."
BEFORE=$(wc -l < controllers/AssetController.php.backup | tr -d ' ')
AFTER=$(wc -l < controllers/AssetController.php | tr -d ' ')
REDUCTION=$((BEFORE - AFTER))
echo "   Before: $BEFORE lines"
echo "   After:  $AFTER lines"
echo "   Reduction: $REDUCTION lines ($((REDUCTION * 100 / BEFORE))%)"

echo ""
echo "4. Checking PermissionMiddleware usage..."
PERM_COUNT=$(grep -c "PermissionMiddleware::requirePermission" controllers/AssetController.php)
echo "   Found $PERM_COUNT permission checks using PermissionMiddleware"

echo ""
echo "5. Checking BrandRepository usage..."
BRAND_COUNT=$(grep -c "BrandRepository" controllers/AssetController.php)
echo "   Found $BRAND_COUNT BrandRepository usages"

echo ""
echo "6. Checking FormDataProvider usage..."
FORM_COUNT=$(grep -c "FormDataProvider" controllers/AssetController.php)
echo "   Found $FORM_COUNT FormDataProvider usages"

echo ""
echo "7. Checking ControllerErrorHandler usage..."
ERROR_COUNT=$(grep -c "ControllerErrorHandler" controllers/AssetController.php)
echo "   Found $ERROR_COUNT ControllerErrorHandler usages"

echo ""
echo "=== Verification Complete ==="
