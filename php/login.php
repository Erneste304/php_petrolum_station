<?php
session_start();
require_once 'config/database.php';

// redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            // login success
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'] ?? 'customer'; // Default to customer if not set
            $_SESSION['customer_id'] = $user['customer_id'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid credentials. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Petroleum Station Management</title>
    <!-- Modern Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: url('https://images.unsplash.com/photo-1545642412-ea4ddb1e2a8f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
        }

        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(23, 162, 184, 0.9) 0%, rgba(19, 132, 150, 0.8) 100%);
            z-index: 0;
        }

        .main-container {
            z-index: 1;
            width: 100%;
            padding: 2rem 0;
        }

        .hero-section {
            color: #ffffff;
            padding: 3rem;
            animation: fadeIn 1s ease-in-out;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            background: linear-gradient(45deg, #17a2b8, #10b981);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            font-weight: 300;
            margin-bottom: 2.5rem;
            color: #E2E8F0;
            line-height: 1.6;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            color: #F8FAFC;
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 12px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease;
        }

        .feature-item:hover {
            transform: translateX(10px);
            background: rgba(255, 255, 255, 0.1);
        }

        .feature-icon {
            font-size: 1.8rem;
            margin-right: 1.2rem;
            color: #17a2b8;
            background: rgba(23, 162, 184, 0.2);
            padding: 10px;
            border-radius: 10px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(16px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 3rem;
            color: white;
            animation: slideUp 0.8s ease-out;
        }

        .login-title {
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .login-subtitle {
            text-align: center;
            color: #CBD5E1;
            margin-bottom: 2rem;
        }

        .form-floating>label {
            color: #64748B;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-size: 1.05rem;
        }

        .form-control:focus {
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(23, 162, 184, 0.3);
        }

        .btn-login {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-weight: 600;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(23, 162, 184, 0.4);
            background: linear-gradient(135deg, #138496, #0f6674);
        }

        .register-text {
            text-align: center;
            margin-top: 2rem;
            color: #CBD5E1;
        }

        .register-link {
            color: #17a2b8;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s;
        }

        .register-link:hover {
            color: #93C5FD;
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                left: -20px;
                position: relative;
            }

            to {
                opacity: 1;
                left: 0;
                position: relative;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>

    <div class="container main-container">
        <div class="row align-items-center justify-content-center">
            <!-- Login Form -->
            <div class="col-lg-5 col-md-8 mx-auto">
                <div class="login-card">
                    <h2 class="login-title">Welcome Back</h2>
                    <p class="login-subtitle">Sign in to access your dashboard</p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger bg-danger bg-opacity-25 text-white border-danger border-opacity-50">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                            <label for="username"><i class="bi bi-person me-2"></i>Username</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            <label for="password"><i class="bi bi-lock me-2"></i>Password</label>
                        </div>
                        <button type="submit" class="btn btn-login">
                            Log In <i class="bi bi-arrow-right-circle ms-2"></i>
                        </button>
                    </form>

                    <div class="register-text">
                        Don't have an account yet? <br>
                        <a href="register.php" class="register-link"><i class="bi bi-person-plus me-1"></i> Register here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>