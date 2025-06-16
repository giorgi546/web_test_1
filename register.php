<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (User::isLoggedIn()) {
    header('Location: account.php');
    exit;
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new User($db);
    
    $data = [
        'email' => trim($_POST['email']),
        'password' => $_POST['password'],
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'phone' => trim($_POST['phone']) ?: null
    ];
    
    // Confirm password validation
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $_SESSION['error'] = "Passwords do not match";
    } else if ($user->register($data)) {
        header('Location: login.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Create your account and start shopping today">
    
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            padding: 2rem 1rem;
            position: relative;
            overflow: hidden;
        }
        
        .auth-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(ellipse at top right, rgba(255,255,255,0.1) 0%, transparent 50%),
                        radial-gradient(ellipse at bottom left, rgba(255,255,255,0.05) 0%, transparent 50%);
        }
        
        .auth-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            padding: 2.5rem;
            width: 100%;
            max-width: 500px;
            position: relative;
            overflow: hidden;
            z-index: 2;
        }
        
        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #10B981, #3B82F6, #F59E0B);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-logo {
            color: #10B981;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
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
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #10B981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            transform: translateY(-1px);
        }
        
        .form-input.error {
            border-color: #EF4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
            pointer-events: none;
            transition: color 0.3s ease;
        }
        
        .form-input.with-icon {
            padding-left: 45px;
        }
        
        .form-input:focus + .input-icon {
            color: #10B981;
        }
        
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.85rem;
        }
        
        .strength-bar {
            height: 4px;
            background: #E5E7EB;
            border-radius: 2px;
            margin-top: 0.25rem;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        
        .strength-weak .strength-fill { 
            width: 33%; 
            background: linear-gradient(90deg, #EF4444, #F87171); 
        }
        
        .strength-medium .strength-fill { 
            width: 66%; 
            background: linear-gradient(90deg, #F59E0B, #FBBF24); 
        }
        
        .strength-strong .strength-fill { 
            width: 100%; 
            background: linear-gradient(90deg, #10B981, #34D399); 
        }
        
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin: 1rem 0;
        }
        
        .checkbox {
            width: 18px;
            height: 18px;
            accent-color: #10B981;
            margin-top: 2px;
        }
        
        .checkbox-label {
            color: #6B7280;
            font-size: 0.9rem;
            cursor: pointer;
            line-height: 1.4;
        }
        
        .checkbox-label a {
            color: #10B981;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .checkbox-label a:hover {
            color: #059669;
            text-decoration: underline;
        }
        
        .auth-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .auth-btn:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }
        
        .auth-btn:active {
            transform: translateY(0);
        }
        
        .auth-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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
            color: #10B981;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .auth-link:hover {
            color: #059669;
            text-decoration: underline;
        }
        
        .field-help {
            font-size: 0.8rem;
            color: #6B7280;
            margin-top: 0.25rem;
        }
        
        .required {
            color: #EF4444;
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
        
        .social-login {
            margin: 1.5rem 0;
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
            border-color: #10B981;
            background: #F0FDF4;
            transform: translateY(-1px);
        }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #E5E7EB;
            border-top: 4px solid #10B981;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .auth-card {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }
            
            .auth-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="loading-overlay" id="loadingOverlay">
                <div class="loading-spinner"></div>
            </div>
            
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1 class="auth-title">Create Account</h1>
                <p class="auth-subtitle">Join us today and start your shopping journey</p>
            </div>
            
            <form method="POST" class="auth-form" id="registerForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name" class="form-label">
                            First Name <span class="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <input 
                                type="text" 
                                id="first_name" 
                                name="first_name" 
                                class="form-input with-icon" 
                                placeholder="Enter your first name"
                                value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                                required
                                maxlength="100"
                                autocomplete="given-name"
                            >
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name" class="form-label">
                            Last Name <span class="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <input 
                                type="text" 
                                id="last_name" 
                                name="last_name" 
                                class="form-input with-icon" 
                                placeholder="Enter your last name"
                                value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                                required
                                maxlength="100"
                                autocomplete="family-name"
                            >
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">
                        Email Address <span class="required">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input with-icon" 
                            placeholder="Enter your email address"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required
                            maxlength="255"
                            autocomplete="email"
                        >
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                    <div class="field-help">We'll send you a verification email</div>
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number</label>
                    <div class="input-wrapper">
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            class="form-input with-icon" 
                            placeholder="Enter your phone number"
                            value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                            pattern="[0-9\-\+\(\)\s]+"
                            maxlength="20"
                            autocomplete="tel"
                        >
                        <i class="fas fa-phone input-icon"></i>
                    </div>
                    <div class="field-help">Optional - for order updates and notifications</div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        Password <span class="required">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input with-icon" 
                            placeholder="Create a strong password"
                            minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                            required
                            autocomplete="new-password"
                        >
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-text">Password strength: <span id="strengthText">Too short</span></div>
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                    </div>
                    <div class="field-help">
                        Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters. Use letters, numbers, and symbols for better security.
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">
                        Confirm Password <span class="required">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-input with-icon" 
                            placeholder="Confirm your password"
                            minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                            required
                            autocomplete="new-password"
                        >
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                    <div id="passwordMatch" class="field-help"></div>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="terms" name="terms" class="checkbox" required>
                    <label for="terms" class="checkbox-label">
                        I agree to the <a href="terms.php" target="_blank">Terms of Service</a> 
                        and <a href="privacy.php" target="_blank">Privacy Policy</a>
                    </label>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="newsletter" name="newsletter" class="checkbox">
                    <label for="newsletter" class="checkbox-label">
                        Send me promotional emails about special offers and new products
                    </label>
                </div>
                
                <button type="submit" class="auth-btn" id="submitBtn" disabled>
                    <i class="fas fa-user-plus"></i>
                    Create My Account
                </button>
            </form>
            
            <div class="social-divider">
                <span>or sign up with</span>
            </div>
            
            <div class="social-login">
                <button type="button" class="social-btn" onclick="signUpWithGoogle()">
                    <i class="fab fa-google" style="color: #DB4437;"></i>
                    Continue with Google
                </button>
                <button type="button" class="social-btn" onclick="signUpWithFacebook()">
                    <i class="fab fa-facebook" style="color: #4267B2;"></i>
                    Continue with Facebook
                </button>
            </div>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php" class="auth-link">Sign in here</a></p>
                <p><a href="index.php" class="auth-link">← Back to Home</a></p>
            </div>
        </div>
    </div>

    <!-- Display Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" style="position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 12px 20px; border-radius: 8px; max-width: 400px; background: #D1FAE5; border: 1px solid #10B981; color: #065F46;">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error" style="position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 12px 20px; border-radius: 8px; max-width: 400px; background: #FEE2E2; border: 1px solid #EF4444; color: #991B1B;">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Password strength checker
            $('#password').on('input', function() {
                checkPasswordStrength($(this).val());
                checkPasswordMatch();
                updateSubmitButton();
            });
            
            // Confirm password checker
            $('#confirm_password').on('input', function() {
                checkPasswordMatch();
                updateSubmitButton();
            });
            
            // Form validation
            $('#registerForm').submit(function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    return false;
                }
                
                // Show loading overlay
                $('#loadingOverlay').show();
            });
            
            // Clear errors on input
            $('.form-input').on('input', function() {
                $(this).removeClass('error');
            });
            
            // Terms checkbox
            $('#terms').on('change', function() {
                updateSubmitButton();
            });
            
            // Real-time form validation
            $('.form-input[required]').on('blur', function() {
                validateField($(this));
            });
            
            // Auto-hide alerts
            setTimeout(function() {
                $('.alert').fadeOut(300);
            }, 5000);
        });
        
        function checkPasswordStrength(password) {
            const strengthBar = $('#passwordStrength');
            const strengthText = $('#strengthText');
            const strengthFill = $('#strengthFill');
            
            let strength = 0;
            let text = 'Too short';
            let className = '';
            
            if (password.length >= <?php echo PASSWORD_MIN_LENGTH; ?>) {
                strength = 1;
                text = 'Weak';
                className = 'strength-weak';
                
                // Check for mixed case, numbers, and symbols
                const hasLower = /[a-z]/.test(password);
                const hasUpper = /[A-Z]/.test(password);
                const hasNumbers = /[0-9]/.test(password);
                const hasSymbols = /[!@#$%^&*(),.?":{}|<>]/.test(password);
                
                if (password.length >= 10 && hasUpper && hasNumbers) {
                    strength = 2;
                    text = 'Medium';
                    className = 'strength-medium';
                    
                    if (password.length >= 12 && hasSymbols && hasLower) {
                        strength = 3;
                        text = 'Strong';
                        className = 'strength-strong';
                    }
                }
            }
            
            strengthText.text(text);
            strengthBar.removeClass('strength-weak strength-medium strength-strong').addClass(className);
        }
        
        function checkPasswordMatch() {
            const password = $('#password').val();
            const confirmPassword = $('#confirm_password').val();
            const matchDiv = $('#passwordMatch');
            
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    matchDiv.html('<span style="color: #10B981;"><i class="fas fa-check"></i> Passwords match</span>');
                    $('#confirm_password').removeClass('error');
                } else {
                    matchDiv.html('<span style="color: #EF4444;"><i class="fas fa-times"></i> Passwords do not match</span>');
                    $('#confirm_password').addClass('error');
                }
            } else {
                matchDiv.html('');
                $('#confirm_password').removeClass('error');
            }
        }
        
        function updateSubmitButton() {
            const password = $('#password').val();
            const confirmPassword = $('#confirm_password').val();
            const termsChecked = $('#terms').is(':checked');
            const firstName = $('#first_name').val().trim();
            const lastName = $('#last_name').val().trim();
            const email = $('#email').val().trim();
            
            const isValid = password.length >= <?php echo PASSWORD_MIN_LENGTH; ?> && 
                           password === confirmPassword && 
                           termsChecked &&
                           firstName &&
                           lastName &&
                           validateEmail(email);
            
            $('#submitBtn').prop('disabled', !isValid);
            
            if (isValid) {
                $('#submitBtn').html('<i class="fas fa-user-plus"></i> Create My Account');
            } else {
                $('#submitBtn').html('<i class="fas fa-user-plus"></i> Please Complete Form');
            }
        }
        
        function validateField(field) {
            const value = field.val().trim();
            const fieldName = field.attr('name');
            let isValid = true;
            
            if (field.prop('required') && !value) {
                isValid = false;
            } else if (fieldName === 'email' && !validateEmail(value)) {
                isValid = false;
            } else if (fieldName === 'password' && value.length < <?php echo PASSWORD_MIN_LENGTH; ?>) {
                isValid = false;
            }
            
            if (isValid) {
                field.removeClass('error');
            } else {
                field.addClass('error');
            }
            
            return isValid;
        }
        
        function validateForm() {
            let isValid = true;
            
            // Required fields validation
            const requiredFields = ['first_name', 'last_name', 'email', 'password', 'confirm_password'];
            requiredFields.forEach(function(field) {
                const input = $('#' + field);
                if (!input.val().trim()) {
                    input.addClass('error');
                    isValid = false;
                } else {
                    input.removeClass('error');
                }
            });
            
            // Email validation
            const email = $('#email').val();
            if (!validateEmail(email)) {
                $('#email').addClass('error');
                isValid = false;
            }
            
            // Password validation
            const password = $('#password').val();
            const confirmPassword = $('#confirm_password').val();
            
            if (password.length < <?php echo PASSWORD_MIN_LENGTH; ?>) {
                $('#password').addClass('error');
                isValid = false;
            }
            
            if (password !== confirmPassword) {
                $('#confirm_password').addClass('error');
                isValid = false;
            }
            
            // Terms checkbox
            if (!$('#terms').is(':checked')) {
                showAlert('You must agree to the Terms of Service', 'error');
                isValid = false;
            }
            
            if (!isValid) {
                showAlert('Please correct the errors in the form', 'error');
            }
            
            return isValid;
        }
        
        function validateEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }
        
        function showAlert(message, type = 'info') {
            const alertClass = type === 'error' ? 'alert-error' : 'alert-success';
            const bgColor = type === 'error' ? '#FEE2E2' : '#D1FAE5';
            const borderColor = type === 'error' ? '#EF4444' : '#10B981';
            const textColor = type === 'error' ? '#991B1B' : '#065F46';
            
            const alert = `
                <div class="alert ${alertClass}" style="position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 12px 20px; border-radius: 8px; max-width: 400px; background: ${bgColor}; border: 1px solid ${borderColor}; color: ${textColor};">
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
        
        // Social login functions (placeholders)
        function signUpWithGoogle() {
            showAlert('Google registration coming soon!', 'info');
        }
        
        function signUpWithFacebook() {
            showAlert('Facebook registration coming soon!', 'info');
        }
        
        // Auto-fill form validation on page load
        updateSubmitButton();
    </script>
</body>
</html>