<?php
/**
 * Luna Dine Login View
 */

// Prevent direct access
if (!defined('LUNA_DINE_ROOT')) {
    exit;
}

// Get system settings
$settings = [];
try {
    $settings_result = $db->select('luna_settings', '*', 'is_system = :is_system', [':is_system' => 'yes']);
    foreach ($settings_result as $row) {
        $settings[$row['key']] = $row['value'];
    }
} catch (Exception $e) {
    // Use defaults
    $settings = [
        'site_name' => 'Luna Dine',
        'site_email' => 'info@lunadine.com',
        'currency_symbol' => '৳'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($settings['site_name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            display: flex;
            min-height: 500px;
        }
        
        .login-info {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: 50px 50px;
            opacity: 0.3;
        }
        
        .login-info h1 {
            font-size: 2.5em;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .login-info p {
            font-size: 1.1em;
            line-height: 1.6;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .login-form {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-form h2 {
            color: #333;
            margin-bottom: 30px;
            font-size: 2em;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
        }
        
        .remember-me input {
            margin-right: 8px;
        }
        
        .forgot-password {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .site-info {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
            color: #666;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                max-width: 400px;
            }
            
            .login-info {
                padding: 30px;
            }
            
            .login-info h1 {
                font-size: 2em;
            }
            
            .login-form {
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-info">
            <h1>🌙 <?php echo htmlspecialchars($settings['site_name']); ?></h1>
            <p>Advanced QR Menu System for Multi-branch Restaurants</p>
            <p>Streamline your restaurant operations with our comprehensive ordering and management system.</p>
        </div>
        
        <div class="login-form">
            <h2>Staff Login</h2>
            
            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_SESSION['login_error']); ?>
                </div>
                <?php unset($_SESSION['login_error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['login_success'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_SESSION['login_success']); ?>
                </div>
                <?php unset($_SESSION['login_success']); ?>
            <?php endif; ?>
            
            <form method="POST" action="/login?action=login">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_SESSION['login_email']) ? htmlspecialchars($_SESSION['login_email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember" value="yes">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="/forgot-password" class="forgot-password">Forgot Password?</a>
                </div>
                
                <button type="submit" class="login-btn">Login</button>
            </form>
            
            <div class="site-info">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_name']); ?></p>
                <p>v<?php echo LUNA_DINE_VERSION; ?></p>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-focus email field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
        
        // Handle form submission with loading state
        document.querySelector('form').addEventListener('submit', function(e) {
            const btn = document.querySelector('.login-btn');
            const originalText = btn.textContent;
            
            btn.textContent = 'Logging in...';
            btn.disabled = true;
            
            setTimeout(() => {
                btn.textContent = originalText;
                btn.disabled = false;
            }, 3000);
        });
    </script>
</body>
</html>