<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
    <div class="card shadow-sm border-0 text-center p-5" style="max-width: 500px; border-radius: 16px;">
        <div class="mb-3 text-warning">
            <i class="fa-solid fa-shield-halved fa-4x"></i>
        </div>
        <h3 class="fw-bold text-dark">Access Denied</h3>
        <p class="text-muted">You do not have permission to access this module. Please contact your Hotel Administrator or check <strong>Privileges Management</strong>.</p>
        <div class="mt-3">
            <a href="dashboard.php" class="btn btn-primary px-4 rounded-pill">
                <i class="fa-solid fa-arrow-left me-1"></i> Return to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
