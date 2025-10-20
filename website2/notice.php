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
    <title>Notice - 1x INV</title>
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
                    <li><a href="product.php?lang=<?php echo $lang; ?>">PRODUCT</a></li>
                    <li><a href="notice.php?lang=<?php echo $lang; ?>" class="active">NOTICE</a></li>
                    <li><a href="support.php?lang=<?php echo $lang; ?>">SUPPORT</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <section class="page-header">
            <div class="container">
                <h2><?php echo $lang === 'en' ? 'Notice' : '공지사항'; ?></h2>
                <p><?php echo $lang === 'en' ? 'Latest news and announcements' : '최신 소식 및 공지사항'; ?></p>
            </div>
        </section>

        <section class="content-section">
            <div class="container">
                <div class="notice-list">
                    <article class="notice-item">
                        <div class="notice-badge important"><?php echo $lang === 'en' ? 'Important' : '중요'; ?></div>
                        <h3><?php echo $lang === 'en' ? 'NaviPro X1 Firmware v2.5.0 Update Notice' : 'NaviPro X1 펌웨어 v2.5.0 업데이트 안내'; ?></h3>
                        <p class="notice-meta">2025-10-15 | <?php echo $lang === 'en' ? 'Product Notice' : '제품공지'; ?></p>
                        <p class="notice-content">
                            <?php echo $lang === 'en' ? 'NaviPro X1 firmware version v2.5.0 has been released. This update includes voice recognition performance improvements, map data updates, and numerous bug fixes. It will be automatically updated via OTA. For manual updates, please refer to the Support page.' : 'NaviPro X1 제품의 새로운 펌웨어 버전 v2.5.0이 출시되었습니다. 이번 업데이트에는 음성인식 성능 개선, 지도 데이터 업데이트, 그리고 다수의 버그 수정이 포함되어 있습니다. OTA를 통해 자동으로 업데이트되며, 수동 업데이트를 원하시는 경우 Support 페이지를 참조해주세요.'; ?>
                        </p>
                    </article>

                    <article class="notice-item">
                        <div class="notice-badge new"><?php echo $lang === 'en' ? 'New' : '신규'; ?></div>
                        <h3><?php echo $lang === 'en' ? 'Fleet Management System Launch' : 'Fleet Management System 출시'; ?></h3>
                        <p class="notice-meta">2025-10-10 | <?php echo $lang === 'en' ? 'New Product' : '신제품'; ?></p>
                        <p class="notice-content">
                            <?php echo $lang === 'en' ? 'Our integrated fleet management system for commercial vehicles has been launched. It provides various features including real-time vehicle tracking, driving record management, and fuel efficiency analysis. For more details, please visit the Product page.' : '상용차 및 차량 관리를 위한 통합 플릿 관리 시스템이 출시되었습니다. 실시간 차량 추적, 운행 기록 관리, 연비 분석 등 다양한 기능을 제공합니다. 자세한 내용은 Product 페이지에서 확인하실 수 있습니다.'; ?>
                        </p>
                    </article>

                    <article class="notice-item">
                        <h3><?php echo $lang === 'en' ? '2025 Chuseok Holiday Customer Support Notice' : '2025년 추석 연휴 고객지원 안내'; ?></h3>
                        <p class="notice-meta">2025-09-25 | <?php echo $lang === 'en' ? 'Operation Notice' : '운영안내'; ?></p>
                        <p class="notice-content">
                            <?php echo $lang === 'en' ? 'Customer support center will be temporarily closed during the Chuseok holiday (September 28 - October 1). For urgent inquiries, please send an email to support@1xinv.com and we will respond sequentially after the holiday.' : '추석 연휴 기간(9월 28일 ~ 10월 1일) 동안 고객지원 센터 운영이 일시 중단됩니다. 긴급 문의사항은 이메일(support@1xinv.com)로 접수해주시면 연휴 종료 후 순차적으로 답변드리겠습니다.'; ?>
                        </p>
                    </article>

                    <article class="notice-item">
                        <h3><?php echo $lang === 'en' ? 'OTA Platform Security Enhancement Update' : 'OTA 플랫폼 보안 강화 업데이트'; ?></h3>
                        <p class="notice-meta">2025-09-15 | <?php echo $lang === 'en' ? 'Security Notice' : '보안공지'; ?></p>
                        <p class="notice-content">
                            <?php echo $lang === 'en' ? 'Security of the OTA firmware platform has been enhanced. The latest version of the Uptane framework has been applied and encryption algorithms have been improved. It will be automatically applied to all customers. No additional action is required.' : 'OTA 펌웨어 플랫폼의 보안이 강화되었습니다. Uptane 프레임워크 최신 버전 적용 및 암호화 알고리즘이 개선되었으며, 모든 고객사에 자동으로 적용됩니다. 추가 조치는 필요하지 않습니다.'; ?>
                        </p>
                    </article>

                    <article class="notice-item">
                        <h3><?php echo $lang === 'en' ? 'NaviPro S2 Price Reduction Notice' : 'NaviPro S2 가격 인하 안내'; ?></h3>
                        <p class="notice-meta">2025-09-01 | <?php echo $lang === 'en' ? 'Pricing Policy' : '가격정책'; ?></p>
                        <p class="notice-content">
                            <?php echo $lang === 'en' ? 'The supply price of NaviPro S2 has been reduced by 10%. This is to provide more customers with reasonable pricing, and will be applied retroactively to existing contract customers. For more details, please contact the sales team.' : 'NaviPro S2 제품의 공급가가 10% 인하되었습니다. 더 많은 고객사에 합리적인 가격으로 제공하기 위한 조치이며, 기존 계약 고객사에도 소급 적용됩니다. 자세한 내용은 영업팀으로 문의주세요.'; ?>
                        </p>
                    </article>

                    <article class="notice-item">
                        <h3><?php echo $lang === 'en' ? '1x INV Technical Seminar Notice' : '1x INV 기술 세미나 개최 안내'; ?></h3>
                        <p class="notice-meta">2025-08-20 | <?php echo $lang === 'en' ? 'Event' : '이벤트'; ?></p>
                        <p class="notice-content">
                            <?php echo $lang === 'en' ? 'We are hosting a technical seminar on "Next-Generation OTA Technology and Future Mobility". Date: September 10, 2025 at 2:00 PM / Location: COEX 3F Conference Room, Seoul. Registration is available through the Inquiry form on the Support page.' : '"차세대 OTA 기술과 미래 모빌리티" 주제로 기술 세미나를 개최합니다. 일시: 2025년 9월 10일 14:00 / 장소: 서울 코엑스 3층 컨퍼런스룸 참가 신청은 Support 페이지의 문의하기를 통해 가능합니다.'; ?>
                        </p>
                    </article>
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
