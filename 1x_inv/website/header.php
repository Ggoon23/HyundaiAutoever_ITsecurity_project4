<?php
// Language translation support
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'ko';
$page = isset($_GET['page']) ? $_GET['page'] : '';

// Load language files if specified
if (!empty($page)) {
    $lang_file = "lang/{$lang}/{$page}";
    if (file_exists($lang_file)) {
        include($lang_file);
    }
}

// Get current page name for active menu
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($current_page); ?> - 1x INV</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Language Switcher Bar -->
    <div class="lang-bar">
        <div class="container">
            <div class="lang-buttons">
                <a href="?lang=ko<?php echo !empty($page) ? '&page='.$page : ''; ?>" class="lang-btn <?php echo $lang === 'ko' ? 'active' : ''; ?>">한국어</a>
                <a href="?lang=en<?php echo !empty($page) ? '&page='.$page : ''; ?>" class="lang-btn <?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            </div>
        </div>
    </div>

    <header>
        <nav class="navbar">
            <div class="container">
                <div class="logo">
                    <h1>1x INV</h1>
                    <p class="tagline">Innovation in Navigation</p>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php?lang=<?php echo $lang; ?>" <?php echo $current_page === 'index' ? 'class="active"' : ''; ?>>HOME</a></li>
                    <li><a href="company.php?lang=<?php echo $lang; ?>" <?php echo $current_page === 'company' ? 'class="active"' : ''; ?>>COMPANY</a></li>
                    <li><a href="product.php?lang=<?php echo $lang; ?>" <?php echo $current_page === 'product' ? 'class="active"' : ''; ?>>PRODUCT</a></li>
                    <li><a href="notice.php?lang=<?php echo $lang; ?>" <?php echo $current_page === 'notice' ? 'class="active"' : ''; ?>>NOTICE</a></li>
                    <li><a href="support.php?lang=<?php echo $lang; ?>" <?php echo $current_page === 'support' ? 'class="active"' : ''; ?>>SUPPORT</a></li>
                </ul>
            </div>
        </nav>
    </header>
