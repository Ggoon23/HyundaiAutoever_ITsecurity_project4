<?php
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'ko';
$page = isset($_GET['page']) ? $_GET['page'] : '';
if (!empty($page)) {
    $lang_file = "lang/{$lang}/{$page}";
    if (file_exists($lang_file)) { include($lang_file); }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company - 1x INV</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
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
                    <li><a href="index.php?lang=<?php echo $lang; ?>">HOME</a></li>
                    <li><a href="company.php?lang=<?php echo $lang; ?>" class="active">COMPANY</a></li>
                    <li><a href="product.php?lang=<?php echo $lang; ?>">PRODUCT</a></li>
                    <li><a href="notice.php?lang=<?php echo $lang; ?>">NOTICE</a></li>
                    <li><a href="support.php?lang=<?php echo $lang; ?>">SUPPORT</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <section class="page-header">
            <div class="container">
                <h2>Company</h2>
                <p><?php echo $lang === 'en' ? 'Leading innovation in automotive technology' : '자동차 기술의 선도적 혁신'; ?></p>
            </div>
        </section>

        <section class="content-section">
            <div class="container">
                <div class="company-info">
                    <h3><?php echo $lang === 'en' ? 'About 1x INV' : '1x INV 소개'; ?></h3>
                    <p><?php echo $lang === 'en' ? '1x INV is a leading technology company providing next-generation navigation systems and OTA firmware solutions.' : '1x INV는 차세대 네비게이션 시스템과 OTA 펌웨어 솔루션을 제공하는 선도적인 기술 기업입니다.'; ?></p>
                    <p><?php echo $lang === 'en' ? 'We lead the digital transformation of the automotive industry by providing safe and reliable technology solutions.' : '우리는 자동차 산업의 디지털 전환을 이끌며, 안전하고 신뢰할 수 있는 기술 솔루션을 제공합니다.'; ?></p>

                    <h3><?php echo $lang === 'en' ? 'Our Vision' : '비전'; ?></h3>
                    <p><?php echo $lang === 'en' ? 'Revolutionize the driver experience by providing cutting-edge navigation and secure firmware updates for every vehicle.' : '모든 차량에 최첨단 네비게이션과 안전한 펌웨어 업데이트를 제공하여 운전자의 경험을 혁신합니다.'; ?></p>

                    <h3><?php echo $lang === 'en' ? 'Core Values' : '핵심 가치'; ?></h3>
                    <ul class="value-list">
                        <li><strong>Innovation:</strong> <?php echo $lang === 'en' ? 'Continuous technology innovation and R&D' : '지속적인 기술 혁신과 연구개발'; ?></li>
                        <li><strong>Security:</strong> <?php echo $lang === 'en' ? 'Compliance with the highest security standards' : '최고 수준의 보안 표준 준수'; ?></li>
                        <li><strong>Reliability:</strong> <?php echo $lang === 'en' ? 'Reliable products and services' : '신뢰할 수 있는 제품과 서비스'; ?></li>
                        <li><strong>Customer Focus:</strong> <?php echo $lang === 'en' ? 'Customer-centric solutions' : '고객 중심의 솔루션 제공'; ?></li>
                    </ul>

                    <h3>Company Information</h3>
                    <table class="info-table">
                        <tr>
                            <th>Company Name</th>
                            <td>1x INV Co., Ltd.</td>
                        </tr>
                        <tr>
                            <th>Established</th>
                            <td>2020</td>
                        </tr>
                        <tr>
                            <th>CEO</th>
                            <td>Kim Tae-hoon</td>
                        </tr>
                        <tr>
                            <th>Business</th>
                            <td>Navigation Systems, OTA Firmware Solutions</td>
                        </tr>
                        <tr>
                            <th>Location</th>
                            <td>Seoul, South Korea</td>
                        </tr>
                    </table>
                </div>
            </div>
        </section>
    </main>

<?php include 'footer.php'; ?>
