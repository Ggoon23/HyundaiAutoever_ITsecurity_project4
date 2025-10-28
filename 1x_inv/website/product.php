<?php
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'ko';
$page = isset($_GET['page']) ? $_GET['page'] : '';
$spec = isset($_GET['spec']) ? $_GET['spec'] : ''; // Product specification loader

if (!empty($page)) {
    $lang_file = "lang/{$lang}/{$page}";
    if (file_exists($lang_file)) { include($lang_file); }
}

// Load product specification sheets (for internal product catalog)
// Vulnerable: Directory traversal possible via spec parameter
if (!empty($spec)) {
    $spec_file = "specs/{$spec}.php";
    if (file_exists($spec_file)) {
        include($spec_file);
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product - 1x INV</title>
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
                    <li><a href="company.php?lang=<?php echo $lang; ?>">COMPANY</a></li>
                    <li><a href="product.php?lang=<?php echo $lang; ?>" class="active">PRODUCT</a></li>
                    <li><a href="notice.php?lang=<?php echo $lang; ?>">NOTICE</a></li>
                    <li><a href="support.php?lang=<?php echo $lang; ?>">SUPPORT</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <section class="page-header">
            <div class="container">
                <h2><?php echo $lang === 'en' ? 'Products' : '제품'; ?></h2>
                <p><?php echo $lang === 'en' ? 'Innovative solutions for automotive industry' : '자동차 산업을 위한 혁신적인 솔루션'; ?></p>
            </div>
        </section>

        <section class="content-section">
            <div class="container">
                <div class="product-grid">
                    <div class="product-item">
                        <div class="product-header">
                            <h3>NaviPro X1</h3>
                            <span class="product-tag"><?php echo $lang === 'en' ? 'Navigation System' : '네비게이션 시스템'; ?></span>
                        </div>
                        <p class="product-desc">
                            <?php echo $lang === 'en' ? 'High-performance navigation solution for next-generation infotainment systems. Provides real-time traffic information, AI-based route optimization, and voice recognition.' : '차세대 인포테인먼트 시스템을 위한 고성능 네비게이션 솔루션입니다. 실시간 교통정보, AI 기반 경로 최적화, 음성 인식 기능을 제공합니다.'; ?>
                        </p>
                        <ul class="product-features">
                            <li><?php echo $lang === 'en' ? '10.25" Full HD Display' : '10.25인치 Full HD 디스플레이'; ?></li>
                            <li><?php echo $lang === 'en' ? 'Real-time Traffic Information' : '실시간 교통 정보 연동'; ?></li>
                            <li><?php echo $lang === 'en' ? 'AI-based Route Recommendation' : 'AI 기반 경로 추천'; ?></li>
                            <li><?php echo $lang === 'en' ? 'Voice Command Control' : '음성 명령 제어'; ?></li>
                            <li><?php echo $lang === 'en' ? 'Smartphone Integration (CarPlay/Android Auto)' : '스마트폰 연동 (CarPlay/Android Auto)'; ?></li>
                        </ul>
                    </div>

                    <div class="product-item">
                        <div class="product-header">
                            <h3>NaviPro S2</h3>
                            <span class="product-tag"><?php echo $lang === 'en' ? 'Navigation System' : '네비게이션 시스템'; ?></span>
                        </div>
                        <p class="product-desc">
                            <?php echo $lang === 'en' ? 'Compact navigation system optimized for small to mid-size vehicles. Offers essential features at a reasonable price with excellent value.' : '중소형 차량에 최적화된 컴팩트 네비게이션 시스템입니다. 합리적인 가격에 필수 기능을 제공하여 높은 가성비를 자랑합니다.'; ?>
                        </p>
                        <ul class="product-features">
                            <li><?php echo $lang === 'en' ? '7" Touchscreen' : '7인치 터치스크린'; ?></li>
                            <li><?php echo $lang === 'en' ? 'Basic Navigation Features' : '기본 네비게이션 기능'; ?></li>
                            <li><?php echo $lang === 'en' ? 'Bluetooth Connectivity' : '블루투스 연결'; ?></li>
                            <li><?php echo $lang === 'en' ? 'Rear Camera Support' : '후방카메라 지원'; ?></li>
                            <li><?php echo $lang === 'en' ? 'Easy Installation' : '간편한 설치'; ?></li>
                        </ul>
                    </div>

                    <div class="product-item">
                        <div class="product-header">
                            <h3><?php echo $lang === 'en' ? 'OTA Firmware Platform' : 'OTA 펌웨어 플랫폼'; ?></h3>
                            <span class="product-tag"><?php echo $lang === 'en' ? 'OTA Solution' : 'OTA 솔루션'; ?></span>
                        </div>
                        <p class="product-desc">
                            <?php echo $lang === 'en' ? 'Secure and reliable wireless firmware update solution. Optimized for automotive ECUs and compliant with Uptane security framework.' : '안전하고 신뢰할 수 있는 무선 펌웨어 업데이트 솔루션입니다. 차량용 ECU에 최적화되어 있으며, Uptane 보안 프레임워크를 준수합니다.'; ?>
                        </p>
                        <ul class="product-features">
                            <li><?php echo $lang === 'en' ? 'Based on Uptane Security Framework' : 'Uptane 보안 프레임워크 기반'; ?></li>
                            <li><?php echo $lang === 'en' ? 'Delta Update Support (Size Reduction)' : 'Delta 업데이트 지원 (용량 절감)'; ?></li>
                            <li><?php echo $lang === 'en' ? 'Rollback and Recovery Features' : '롤백 및 복구 기능'; ?></li>
                            <li><?php echo $lang === 'en' ? 'Multi-ECU Simultaneous Update' : '멀티 ECU 동시 업데이트'; ?></li>
                            <li><?php echo $lang === 'en' ? 'Real-time Monitoring Dashboard' : '실시간 모니터링 대시보드'; ?></li>
                        </ul>
                    </div>

                    <div class="product-item">
                        <div class="product-header">
                            <h3>Fleet Management System</h3>
                            <span class="product-tag"><?php echo $lang === 'en' ? 'Management' : '관리 시스템'; ?></span>
                        </div>
                        <p class="product-desc">
                            <?php echo $lang === 'en' ? 'Integrated fleet management system for commercial vehicles and fleet operations. Provides real-time vehicle tracking, driving records, and fuel efficiency analysis.' : '상용차 및 차량 관리를 위한 통합 플릿 관리 시스템입니다. 실시간 차량 위치 추적, 운행 기록, 연비 분석 등을 제공합니다.'; ?>
                        </p>
                        <ul class="product-features">
                            <li><?php echo $lang === 'en' ? 'Real-time Vehicle Tracking' : '실시간 차량 위치 추적'; ?></li>
                            <li><?php echo $lang === 'en' ? 'Automatic Driving Record Storage' : '운행 기록 자동 저장'; ?></li>
                            <li><?php echo $lang === 'en' ? 'Fuel Efficiency & Driving Pattern Analysis' : '연비 및 운전 패턴 분석'; ?></li>
                            <li><?php echo $lang === 'en' ? 'Maintenance Schedule Management' : '정비 일정 관리'; ?></li>
                            <li><?php echo $lang === 'en' ? 'Web/Mobile Dashboard' : '웹/모바일 대시보드'; ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </main>

<?php include 'footer.php'; ?>
