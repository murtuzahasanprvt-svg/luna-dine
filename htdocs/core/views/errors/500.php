<?php
/**
 * Luna Dine 500 Error View
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
        'site_email' => 'info@lunadine.com'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error - <?php echo htmlspecialchars($settings['site_name']); ?></title>
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
            color: white;
        }
        
        .error-container {
            text-align: center;
            max-width: 600px;
            padding: 40px;
        }
        
        .error-code {
            font-size: 8rem;
            font-weight: bold;
            margin-bottom: 20px;
            text-shadow: 0 0 30px rgba(0,0,0,0.3);
            animation: shake 0.5s ease-in-out infinite;
        }
        
        .error-message {
            font-size: 2rem;
            margin-bottom: 20px;
            opacity: 0.9;
        }
        
        .error-description {
            font-size: 1.2rem;
            margin-bottom: 40px;
            opacity: 0.8;
            line-height: 1.6;
        }
        
        .error-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .btn-primary {
            background: white;
            color: #667eea;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .btn-secondary {
            background: transparent;
            color: white;
            border-color: white;
        }
        
        .btn-secondary:hover {
            background: white;
            color: #667eea;
        }
        
        .error-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        .debug-info {
            margin-top: 30px;
            padding: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            text-align: left;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        
        .debug-info h3 {
            margin-bottom: 10px;
            color: #ffeb3b;
        }
        
        .debug-info p {
            margin-bottom: 5px;
            opacity: 0.8;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        @media (max-width: 768px) {
            .error-code {
                font-size: 6rem;
            }
            
            .error-message {
                font-size: 1.5rem;
            }
            
            .error-description {
                font-size: 1rem;
            }
            
            .error-actions {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <div class="error-code">500</div>
        <h1 class="error-message">Server Error</h1>
        <p class="error-description">
            Our kitchen is experiencing some technical difficulties! 
            Our chefs are working hard to fix this issue. 
            Please try again in a few moments.
        </p>
        <div class="error-actions">
            <a href="/" class="btn btn-primary">Go Home</a>
            <a href="/menu" class="btn btn-secondary">View Menu</a>
            <a href="/contact" class="btn btn-secondary">Contact Support</a>
        </div>
        
        <?php if (DEBUG_MODE): ?>
            <div class="debug-info">
                <h3>Debug Information</h3>
                <p><strong>Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                <p><strong>Request URI:</strong> <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></p>
                <p><strong>Request Method:</strong> <?php echo htmlspecialchars($_SERVER['REQUEST_METHOD']); ?></p>
                <p><strong>User Agent:</strong> <?php echo htmlspecialchars($_SERVER['HTTP_USER_AGENT']); ?></p>
                <p><strong>Remote Address:</strong> <?php echo htmlspecialchars($_SERVER['REMOTE_ADDR']); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            const errorContainer = document.querySelector('.error-container');
            
            // Add click effect to buttons
            document.querySelectorAll('.btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    // Create ripple effect
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.classList.add('ripple');
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
        });
    </script>
    
    <style>
        .btn {
            position: relative;
            overflow: hidden;
        }
        
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple-animation 0.6s ease-out;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    </style>
</body>
</html>