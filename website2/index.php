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
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>1x INV - Navigation & OTA Firmware Solutions</title>
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
                    <li><a href="index.php?lang=<?php echo $lang; ?>" class="active">HOME</a></li>
                    <li><a href="company.php?lang=<?php echo $lang; ?>">COMPANY</a></li>
                    <li><a href="product.php?lang=<?php echo $lang; ?>">PRODUCT</a></li>
                    <li><a href="notice.php?lang=<?php echo $lang; ?>">NOTICE</a></li>
                    <li><a href="support.php?lang=<?php echo $lang; ?>">SUPPORT</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="hero-content">
                <h2><?php echo $lang === 'en' ? 'Welcome to 1x INV' : '1x INV에 오신 것을 환영합니다'; ?></h2>
                <p><?php echo $lang === 'en' ? 'Leading Provider of Navigation Systems and OTA Firmware Solutions' : '네비게이션 시스템과 OTA 펌웨어 솔루션의 선도 기업'; ?></p>
                <p class="hero-subtitle"><?php echo $lang === 'en' ? 'Driving the Future of Automotive Technology' : '자동차 기술의 미래를 주도합니다'; ?></p>
            </div>
        </section>

        <section class="intro">
            <div class="container">
                <h2><?php echo $lang === 'en' ? 'About 1x INV' : '1x INV 소개'; ?></h2>
                <p><?php echo $lang === 'en' ? '1x INV is a cutting-edge technology company specializing in vehicle navigation systems and OTA (Over-The-Air) firmware solutions.' : '1x INV는 차량용 네비게이션 시스템과 OTA(Over-The-Air) 펌웨어 솔루션을 전문으로 하는 첨단 기술 기업입니다.'; ?></p>
                <p><?php echo $lang === 'en' ? 'We provide innovative products for vehicle connectivity, security, and up-to-date maintenance.' : '차량의 연결성, 보안성, 최신 상태 유지를 위한 혁신적인 제품을 제공합니다.'; ?></p>
            </div>
        </section>

        <section class="features">
            <div class="container">
                <div class="feature-grid">
                    <div class="feature-card">
                        <h3><?php echo $lang === 'en' ? 'Navigation Systems' : '네비게이션 시스템'; ?></h3>
                        <p><?php echo $lang === 'en' ? 'State-of-the-art navigation technology for modern vehicles' : '현대 차량을 위한 최첨단 네비게이션 기술'; ?></p>
                    </div>
                    <div class="feature-card">
                        <h3><?php echo $lang === 'en' ? 'OTA Firmware' : 'OTA 펌웨어'; ?></h3>
                        <p><?php echo $lang === 'en' ? 'Secure and reliable wireless firmware updates' : '안전하고 신뢰할 수 있는 무선 펌웨어 업데이트'; ?></p>
                    </div>
                    <div class="feature-card">
                        <h3><?php echo $lang === 'en' ? '24/7 Support' : '24/7 지원'; ?></h3>
                        <p><?php echo $lang === 'en' ? 'Professional customer and technical support services' : '전문적인 고객 지원 및 기술 지원 서비스'; ?></p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 1x INV. All rights reserved.</p>
            <p>Navigation & OTA Firmware Solutions Provider</p>
        </div>
    </footer>
</body>
</html>
