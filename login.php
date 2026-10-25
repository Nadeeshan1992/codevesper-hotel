<?php
/**
 * Luxury Hotel Management System - Attractive Login Portal
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $db = getDbConnection();
        $stmt = $db->prepare("
            SELECT u.*, r.name as role_name, r.slug as role_slug 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE (u.username = ? OR u.email = ?) AND u.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Login Success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_slug'] = $user['role_slug'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['avatar'] = $user['avatar'];

            // Issue persistent signed cookie for serverless persistence
            issueAuthCookie($user);

            logAudit('Auth', 'User Login', 'User ' . $user['username'] . ' logged into the system.');

            // If waiter, redirect directly to Tablet POS for convenience
            if ($user['role_slug'] === 'waiter') {
                header('Location: pos.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid credentials. Please verify your username and password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | <?= APP_NAME ?> - Luxury Hotel & Restaurant</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6.5.2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --primary-navy: #091224;
            --accent-gold: #d97706;
            --accent-amber: #f59e0b;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(9, 18, 36, 0.95), rgba(15, 23, 42, 0.92)),
                        url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1920&q=80') center/cover no-repeat fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #f8fafc;
        }

        .login-card-container {
            width: 100%;
            max-width: 1050px;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.6), 0 0 40px rgba(217, 119, 6, 0.15);
            overflow: hidden;
        }

        .login-banner-side {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.6) 0%, rgba(9, 18, 36, 0.95) 100%),
                        url('https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=900&q=80') center/cover no-repeat;
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(245, 158, 11, 0.3);
            padding: 8px 18px;
            border-radius: 50px;
            width: fit-content;
        }

        .brand-badge i {
            color: var(--accent-amber);
            font-size: 1.1rem;
        }

        .hotel-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.2;
            letter-spacing: -0.5px;
            margin-top: 20px;
        }

        .hotel-title span {
            color: var(--accent-amber);
            font-style: italic;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.92rem;
            color: #cbd5e1;
            margin-bottom: 12px;
        }

        .feature-item i {
            color: var(--accent-amber);
            width: 20px;
            text-align: center;
        }

        .login-form-side {
            padding: 48px 44px;
        }

        .form-control-custom {
            background: rgba(255, 255, 255, 0.06);
            border: 1.5px solid rgba(255, 255, 255, 0.12);
            color: #fff;
            border-radius: 12px;
            padding: 13px 18px;
            font-size: 0.95rem;
            transition: all 0.25s ease;
        }

        .form-control-custom:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--accent-amber);
            color: #fff;
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.18);
        }

        .form-control-custom::placeholder {
            color: #64748b;
        }

        .input-group-text-custom {
            background: rgba(255, 255, 255, 0.06);
            border: 1.5px solid rgba(255, 255, 255, 0.12);
            border-right: none;
            color: var(--accent-amber);
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
            padding-left: 18px;
            padding-right: 14px;
        }

        .input-group .form-control-custom {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .btn-gold-submit {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #0f172a;
            font-weight: 700;
            font-size: 1.05rem;
            border: none;
            border-radius: 12px;
            padding: 14px;
            width: 100%;
            transition: all 0.25s ease;
            box-shadow: 0 8px 20px rgba(217, 119, 6, 0.35);
        }

        .btn-gold-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 26px rgba(217, 119, 6, 0.45);
            color: #000;
        }

        .btn-gold-submit:active {
            transform: translateY(0);
        }

        /* Demo Role Switcher Badges */
        .demo-roles-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px dashed rgba(255, 255, 255, 0.15);
            border-radius: 14px;
            padding: 16px;
            margin-top: 24px;
        }

        .role-pill {
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #cbd5e1;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            user-select: none;
        }

        .role-pill:hover {
            background: rgba(245, 158, 11, 0.2);
            border-color: var(--accent-amber);
            color: #fff;
            transform: translateY(-2px);
        }

        .role-pill.active {
            background: var(--accent-amber);
            color: #0f172a;
            border-color: var(--accent-amber);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .toggle-password {
            cursor: pointer;
            border-left: none;
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            background: rgba(255, 255, 255, 0.06);
            border: 1.5px solid rgba(255, 255, 255, 0.12);
            color: #94a3b8;
            padding: 0 16px;
        }

        .toggle-password:hover {
            color: #fff;
        }
    </style>
</head>
<body>

<div class="login-card-container">
    <div class="row g-0">
        <!-- Left Luxury Showcase Banner -->
        <div class="col-lg-5 login-banner-side d-none d-lg-flex">
            <div>
                <div class="mb-3">
                    <img src="assets/images/logo.jpg" alt="CodeVesper Logo" style="width: 76px; height: 76px; border-radius: 20px; object-fit: cover; border: 2px solid rgba(0, 229, 255, 0.5); box-shadow: 0 0 25px rgba(0, 229, 255, 0.4);">
                </div>
                <div class="brand-badge">
                    <i class="fa-solid fa-code" style="color: #00e5ff;"></i>
                    <span class="small fw-bold text-uppercase tracking-wider" style="color: #38bdf8;">Hospitality Management ERP</span>
                </div>
                <h1 class="hotel-title">
                    CodeVesper <br>
                    <span style="color: #38bdf8;">Hotel & Restaurant</span>
                </h1>
                <p class="text-secondary small mt-2">
                    Scalable property management for hotels of any capacity, integrated restaurant POS, live kitchen order tickets, staff and inventory controls.
                </p>
            </div>

            <div>
                <div class="feature-item">
                    <i class="fa-solid fa-door-open" style="color: #38bdf8;"></i>
                    <span><strong>Flexible Room Inventory:</strong> Scalable Capacity & Floor Layouts</span>
                </div>
                <div class="feature-item">
                    <i class="fa-solid fa-tablet-screen-button" style="color: #38bdf8;"></i>
                    <span><strong>Waiter Tablet POS:</strong> Fast Touch Screen Ordering</span>
                </div>
                <div class="feature-item">
                    <i class="fa-solid fa-shield-halved" style="color: #38bdf8;"></i>
                    <span><strong>Privileges Management:</strong> Dynamic Role Permissions</span>
                </div>
                <div class="feature-item">
                    <i class="fa-solid fa-file-invoice-dollar" style="color: #38bdf8;"></i>
                    <span><strong>Integrated Billing:</strong> Unified Room & Dining Folio</span>
                </div>

                <div class="pt-3 border-top border-secondary border-opacity-25 mt-3 text-muted small">
                    &copy; <?= date('Y') ?> CodeVesper Suite. All rights reserved.
                </div>
            </div>
        </div>

        <!-- Right Login Form -->
        <div class="col-lg-7 login-form-side">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h2 class="fw-bold mb-1 text-white">Staff Sign In</h2>
                    <p class="text-secondary small mb-0">Select your role or enter credentials to access your terminal</p>
                </div>
                <div>
                    <img src="assets/images/logo.jpg" alt="CodeVesper" style="width: 50px; height: 50px; border-radius: 14px; object-fit: cover; border: 1.5px solid rgba(0, 229, 255, 0.4); box-shadow: 0 0 15px rgba(0, 229, 255, 0.35);">
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger bg-danger bg-opacity-25 border-danger text-light py-2 px-3 small rounded-3 mb-4 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-danger"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="loginForm">
                <!-- Username / Email -->
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-bold">Username or Email</label>
                    <div class="input-group">
                        <span class="input-group-text input-group-text-custom">
                            <i class="fa-regular fa-user"></i>
                        </span>
                        <input type="text" name="username" id="usernameInput" class="form-control form-control-custom" placeholder="e.g. admin or waiter" required autofocus>
                    </div>
                </div>

                <!-- Password with eye toggle -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label text-secondary small fw-bold mb-0">Password</label>
                        <span class="text-muted small" style="font-size: 0.75rem;">Default: password123</span>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text input-group-text-custom">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="passwordInput" class="form-control form-control-custom" placeholder="Enter your password" required>
                        <span class="input-group-text toggle-password" id="togglePasswordBtn">
                            <i class="fa-regular fa-eye" id="eyeIcon"></i>
                        </span>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-gold-submit d-flex align-items-center justify-content-center gap-2">
                    <span>Access Dashboard</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <!-- Quick 1-Click Role Switcher Demo Cards -->
            <div class="demo-roles-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small text-warning fw-bold"><i class="fa-solid fa-bolt me-1"></i> Quick Role Switcher (1-Click Test)</span>
                    <span class="badge bg-secondary bg-opacity-50 small" style="font-size: 0.68rem;">Click to Auto-fill</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="role-pill" onclick="fillRole('admin', 'password123', this)">
                        <i class="fa-solid fa-shield-halved text-primary"></i> Administrator
                    </button>
                    <button type="button" class="role-pill" onclick="fillRole('owner', 'password123', this)">
                        <i class="fa-solid fa-crown text-warning"></i> Hotel Owner
                    </button>
                    <button type="button" class="role-pill" onclick="fillRole('reception', 'password123', this)">
                        <i class="fa-solid fa-bell-concierge text-info"></i> Receptionist
                    </button>
                    <button type="button" class="role-pill" onclick="fillRole('waiter', 'password123', this)">
                        <i class="fa-solid fa-tablet-screen-button text-success"></i> Waiter (Tablet POS)
                    </button>
                </div>
            </div>

            <div class="text-center mt-4">
                <span class="text-secondary small">CodeVesper Hospitality Management Suite &bull; Version 2.0</span>
            </div>
        </div>
    </div>
</div>

<script>
function fillRole(username, password, elem) {
    document.getElementById('usernameInput').value = username;
    document.getElementById('passwordInput').value = password;
    
    document.querySelectorAll('.role-pill').forEach(p => p.classList.remove('active'));
    elem.classList.add('active');
    
    // Add brief animation highlight to submit button
    const btn = document.querySelector('.btn-gold-submit');
    btn.style.transform = 'scale(1.03)';
    setTimeout(() => {
        btn.style.transform = 'scale(1)';
    }, 200);
}

// Password toggle
const togglePasswordBtn = document.getElementById('togglePasswordBtn');
const passwordInput = document.getElementById('passwordInput');
const eyeIcon = document.getElementById('eyeIcon');

if (togglePasswordBtn && passwordInput && eyeIcon) {
    togglePasswordBtn.addEventListener('click', function() {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    });
}
</script>
</body>
</html>
