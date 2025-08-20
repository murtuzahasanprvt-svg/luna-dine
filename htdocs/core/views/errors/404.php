<?php
/**
 * Luna Dine 404 Error View
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
    <title>404 - Page Not Found - <?php echo htmlspecialchars($settings['site_name']); ?></title>
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
            animation: float 3s ease-in-out infinite;
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
            animation: bounce 2s infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
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
        <div class="error-icon">🚫</div>
        <div class="error-code">404</div>
        <h1 class="error-message">Page Not Found</h1>
        <p class="error-description">
            Oops! The page you're looking for seems to have vanished into the digital void. 
            Don't worry, even the best restaurants occasionally misplace things!
        </p>
        <div class="error-actions">
            <a href="/" class="btn btn-primary">Go Home</a>
            <a href="/menu" class="btn btn-secondary">View Menu</a>
            <a href="/contact" class="btn btn-secondary">Contact Us</a>
        </div>
    </div>
    
    <script>
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            const errorContainer = document.querySelector('.error-container');
            
            // Add mouse movement effect
            document.addEventListener('mousemove', function(e) {
                const x = e.clientX / window.innerWidth;
                const y = e.clientY / window.innerHeight;
                
                errorContainer.style.transform = `translate(${x * 10 - 5}px, ${y * 10 - 5}px)`;
            });
        });
    </script>
</body>
</html>