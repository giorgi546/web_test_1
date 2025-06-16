<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (User::isLoggedIn()) {
    header('Location: account.php');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new User($db);
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if ($user->login($email, $password, $remember)) {
        $redirect = $_GET['redirect'] ?? 'account.php';
        header('Location: ' . $redirect);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 1rem;
        }
        
        .auth-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }
        
        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3B82F6, #10B981, #F59E0B);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-logo {
            color: #3B82F6;
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .auth-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 0.5rem;
        }
        
        .auth-subtitle {
            color: #6B7280;
            font-size: 0.95rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            transform: translateY(-1px);
        }
        
        .form-input.error {
            border-color: #EF4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
            pointer-events: none;
        }
        
        .form-input.with-icon {
            padding-left: 45px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 1rem 0;
        }
        
        .checkbox {
            width: 18px;
            height: 18px;
            accent-color: #3B82F6;
        }
        
        .checkbox-label {
            color: #6B7280;
            font-size: 0.9rem;
            cursor: pointer;
        }
        
        .auth-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .auth-btn:hover {
            background: linear-gradient(135deg, #2563EB 0%, #1E40AF 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }
        
        .auth-btn:active {
            transform: translateY(0);
        }
        
        .auth-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .auth-btn:hover::before {
            left: 100%;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #E5E7EB;
        }
        
        .auth-link {
            color: #3B82F6;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .auth-link:hover {
            color: #2563EB;
            text-decoration: underline;
        }
        
        .forgot-password {
            text-align: right;
            margin-top: 0.5rem;
        }
        
        .forgot-password a {
            color: #6B7280;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .forgot-password a:hover {
            color: #3B82F6;
        }
        
        .social-login {
            margin: 1.5rem 0;
        }
        
        .social-divider {
            position: relative;
            text-align: center;
            margin: 1.5rem 0;
        }
        
        .social-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #E5E7EB;
        }
        
        .social-divider span {
            background: white;
            padding: 0 1rem;
            color: #6B7280;
            font-size: 0.9rem;
        }
        
        .social-btn {
            width: 100%;
            padding: 12px;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            background: white;
            color: #374151;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .social-btn:hover {
            border-color: #3B82F6;
            background: #F8FAFC;
            transform: translateY(-1px);
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .auth-card {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Sign in to your account to continue shopping</p>
            </div>
            
            <form method="POST" class="auth-form" id="loginForm">
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input with-icon" 
                            placeholder="Enter your email"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input with-icon" 
                            placeholder="Enter your password"
                            minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                            required
                        >
                    </div>
                    <div class="forgot-password">
                        <a href="forgot-password.php">Forgot your password?</a>
                    </div>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="remember" name="remember" class="checkbox">
                    <label for="remember" class="checkbox-label">Remember me for 30 days</label>
                </div>
                
                <button type="submit" class="auth-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>
            
            <div class="social-divider">
                <span>or continue with</span>
            </div>
            
            <div class="social-login">
                <button type="button" class="social-btn">
                    <i class="fab fa-google"></i>
                    Continue with Google
                </button>
                <button type="button" class="social-btn">
                    <i class="fab fa-facebook"></i>
                    Continue with Facebook
                </button>
            </div>
            
            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php" class="auth-link">Sign up for free</a></p>
                <p><a href="index.php" class="auth-link">← Back to Home</a></p>
            </div>
        </div>
    </div>

    <!-- Display Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Form validation
            $('#loginForm').submit(function(e) {
                let isValid = true;
                
                // Email validation
                const email = $('#email').val();
                if (!email || !validateEmail(email)) {
                    $('#email').addClass('error');
                    isValid = false;
                } else {
                    $('#email').removeClass('error');
                }
                
                // Password validation
                const password = $('#password').val();
                if (!password || password.length < <?php echo PASSWORD_MIN_LENGTH; ?>) {
                    $('#password').addClass('error');
                    isValid = false;
                } else {
                    $('#password').removeClass('error');
                }
                
                if (!isValid) {
                    e.preventDefault();
                    showAlert('Please fill in all fields correctly', 'error');
                }
            });
            
            // Clear errors on input
            $('.form-input').on('input', function() {
                $(this).removeClass('error');
            });
            
            // Auto-hide alerts
            setTimeout(function() {
                $('.alert').fadeOut(300);
            }, 5000);
        });
        
        function validateEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }
        
        function showAlert(message, type = 'info') {
            const alert = `
                <div class="alert alert-${type}" style="position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 12px 20px; border-radius: 8px; max-width: 400px;">
                    ${message}
                </div>
            `;
            
            $('.alert').remove();
            $('body').append(alert);
            
            setTimeout(function() {
                $('.alert').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    </script>
</body>
</html>