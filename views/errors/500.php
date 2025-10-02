<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error - ConstructLink™</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            text-align: center;
            color: white;
        }
        .error-code {
            font-size: 8rem;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .error-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="error-card">
                    <div class="error-container">
                        <div class="error-code">500</div>
                        <h2 class="mb-4">Server Error</h2>
                        <p class="lead mb-4">
                            Something went wrong on our end. We're working to fix it.
                        </p>
                        <div class="mb-4">
                            <i class="bi bi-exclamation-octagon display-4"></i>
                        </div>
                        
                        <?php if (isset($error) && APP_DEBUG): ?>
                        <div class="alert alert-light text-dark mb-4 text-start">
                            <strong>Error Details:</strong><br>
                            <code><?= htmlspecialchars($error) ?></code>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="/dashboard" class="btn btn-light btn-lg me-md-2">
                                <i class="bi bi-house me-2"></i>Go to Dashboard
                            </a>
                            <button onclick="location.reload()" class="btn btn-outline-light btn-lg">
                                <i class="bi bi-arrow-clockwise me-2"></i>Try Again
                            </button>
                        </div>
                        <div class="mt-4">
                            <small class="opacity-75">
                                Error ID: <?= uniqid() ?> | <?= date('Y-m-d H:i:s') ?>
                            </small>
                        </div>
                        <div class="mt-2">
                            <small class="opacity-75">
                                ConstructLink™ by Ranoa Digital Solutions
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
