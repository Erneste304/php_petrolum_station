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
    $confirm  = $_POST['confirm_password'];

    if (empty($username) || empty($password) || empty($confirm)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // check if username exists
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Username already taken.';
        } else {
            // Start a transaction as we are inserting into multiple tables
            $pdo->beginTransaction();
            try {
                // 1. Create a blank customer profile for the user
                $stmt = $pdo->prepare('INSERT INTO customer (name) VALUES (?)');
                $stmt->execute([$username]); // Use username as default name
                $customerId = $pdo->lastInsertId();

                // 2. Create the user authentication record and link to customer
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (username, password, role, customer_id) VALUES (?, ?, ?, ?)');
                $stmt->execute([$username, $hash, 'customer', $customerId]);

                $pdo->commit();

                $_SESSION['success'] = 'Registration successful. Please log in.';
                header('Location: login.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Registration failed due to a system error. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Petroleum Station MS</title>
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

        .register-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(16px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 3rem;
            color: white;
            animation: slideUp 0.8s ease-out;
        }

        .register-title {
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .register-subtitle {
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

        .btn-register {
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

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(23, 162, 184, 0.4);
            background: linear-gradient(135deg, #138496, #0f6674);
        }

        .login-text {
            text-align: center;
            margin-top: 2rem;
            color: #CBD5E1;
        }

        .login-link {
            color: #17a2b8;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s;
        }

        .login-link:hover {
            color: #93C5FD;
            text-decoration: underline;
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
            <div class="col-lg-5 col-md-8 mx-auto">
                <div class="register-card">
                    <h2 class="register-title">Join Us</h2>
                    <p class="register-subtitle">Create your account to start managing</p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger bg-danger bg-opacity-25 text-white border-danger border-opacity-50">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                            <label for="username"><i class="bi bi-person me-2"></i>Username</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            <label for="password"><i class="bi bi-lock me-2"></i>Password</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                            <label for="confirm_password"><i class="bi bi-shield-check me-2"></i>Confirm Password</label>
                        </div>
                        <button type="submit" class="btn btn-register">
                            Create Account <i class="bi bi-person-plus ms-2"></i>
                        </button>
                    </form>

                    <div class="login-text">
                        Already have an account? <br>
                        <a href="login.php" class="login-link"><i class="bi bi-box-arrow-in-right me-1"></i> Log in here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>