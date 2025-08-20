<?php
/**
 * Default Theme Header Template
 */

// Get theme instance
global $theme;
$theme_vars = $theme->getVars();
?>

<!DOCTYPE html>
<html lang="<?php echo DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : SITE_NAME . ' - Advanced QR Menu System'; ?>">
    <meta name="keywords" content="<?php echo isset($page_keywords) ? $page_keywords : 'restaurant, qr menu, food ordering, dining'; ?>">
    
    <!-- Theme CSS -->
    <style>
        <?php echo $theme->generateCSSVariables(); ?>
    </style>
    <link rel="stylesheet" href="<?php echo $theme->getAssetURL('css/style.css'); ?>">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/assets/img/favicon.ico">
    
    <!-- Additional head content -->
    <?php if (isset($additional_head)) echo $additional_head; ?>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="logo">
                <a href="<?php echo SITE_URL; ?>">
                    <i class="fas fa-moon"></i>
                    <?php echo SITE_NAME; ?>
                </a>
            </div>
            
            <nav class="nav">
                <a href="<?php echo SITE_URL; ?>">Home</a>
                <a href="<?php echo SITE_URL; ?>/menu">Menu</a>
                <a href="<?php echo SITE_URL; ?>/order">Order</a>
                <a href="<?php echo SITE_URL; ?>/about">About</a>
                <a href="<?php echo SITE_URL; ?>/contact">Contact</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo SITE_URL; ?>/admin">Admin</a>
                    <a href="<?php echo SITE_URL; ?>/logout">Logout</a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/login">Login</a>
                <?php endif; ?>
            </nav>
            
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <!-- Mobile Menu -->
        <div class="mobile-menu">
            <div class="container">
                <a href="<?php echo SITE_URL; ?>">Home</a>
                <a href="<?php echo SITE_URL; ?>/menu">Menu</a>
                <a href="<?php echo SITE_URL; ?>/order">Order</a>
                <a href="<?php echo SITE_URL; ?>/about">About</a>
                <a href="<?php echo SITE_URL; ?>/contact">Contact</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo SITE_URL; ?>/admin">Admin</a>
                    <a href="<?php echo SITE_URL; ?>/logout">Logout</a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/login">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main>