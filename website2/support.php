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
    <title>Support - 1x INV</title>
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
                    <li><a href="index.php?lang=<?php echo $lang; ?>">HOME</a></li>
                    <li><a href="company.php?lang=<?php echo $lang; ?>">COMPANY</a></li>
                    <li><a href="product.php?lang=<?php echo $lang; ?>">PRODUCT</a></li>
                    <li><a href="notice.php?lang=<?php echo $lang; ?>">NOTICE</a></li>
                    <li><a href="support.php?lang=<?php echo $lang; ?>" class="active">SUPPORT</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <section class="page-header">
            <div class="container">
                <h2>Support</h2>
                <p><?php echo $lang === 'en' ? 'Customer Support and Inquiries' : '고객 지원 및 문의'; ?></p>
            </div>
        </section>

        <section class="content-section">
            <div class="container">
                <div class="support-tabs">
                    <button class="tab-btn active" data-tab="faq"><?php echo $lang === 'en' ? 'FAQ' : '자주 묻는 질문 (FAQ)'; ?></button>
                    <button class="tab-btn" data-tab="inquiry"><?php echo $lang === 'en' ? 'Inquiry' : '문의하기'; ?></button>
                </div>

                <div id="faq-tab" class="tab-content active">
                    <div class="faq-section">
                        <h3><?php echo $lang === 'en' ? 'Frequently Asked Questions' : '자주 묻는 질문'; ?></h3>
                        <div class="faq-list">
                            <div class="faq-item">
                                <h4><?php echo $lang === 'en' ? 'Q. How does OTA firmware update work?' : 'Q. OTA 펌웨어 업데이트는 어떻게 진행되나요?'; ?></h4>
                                <p><?php echo $lang === 'en' ? 'A. OTA updates proceed automatically when the vehicle is connected to the network. Press the confirm button when the update notification appears. Do not turn off the vehicle during the update and park in a safe location.' : 'A. OTA 업데이트는 차량이 네트워크에 연결되어 있을 때 자동으로 진행됩니다. 업데이트 알림이 표시되면 확인 버튼을 눌러주시면 됩니다. 업데이트 중에는 차량 시동을 끄지 마시고, 안전한 장소에 주차해주세요.'; ?></p>
                            </div>
                            <div class="faq-item">
                                <h4><?php echo $lang === 'en' ? 'Q. What is the product warranty period?' : 'Q. 제품 보증 기간은 얼마나 되나요?'; ?></h4>
                                <p><?php echo $lang === 'en' ? 'A. All 1x INV products come with a 2-year manufacturer warranty from the date of purchase. Free repair or replacement is available for product defects during the warranty period.' : 'A. 모든 1x INV 제품은 구매일로부터 2년간 제조사 보증이 제공됩니다. 보증 기간 내 제품 결함 발생 시 무상 수리 또는 교체가 가능합니다.'; ?></p>
                            </div>
                            <div class="faq-item">
                                <h4><?php echo $lang === 'en' ? 'Q. How can I get technical support?' : 'Q. 기술 지원은 어떻게 받을 수 있나요?'; ?></h4>
                                <p><?php echo $lang === 'en' ? 'A. Our technical team will respond quickly if you fill out the inquiry form in the Inquiry tab. For urgent cases, please select "Technical Support" as the inquiry type.' : 'A. 문의하기 탭에서 문의 양식을 작성해주시면 전문 기술팀이 신속하게 답변드립니다. 긴급한 경우 문의 유형을 \'기술 지원\'으로 선택해주세요.'; ?></p>
                            </div>
                            <div class="faq-item">
                                <h4><?php echo $lang === 'en' ? 'Q. How do I purchase products?' : 'Q. 제품 구매는 어떻게 하나요?'; ?></h4>
                                <p><?php echo $lang === 'en' ? 'A. 1x INV products are primarily supplied on a B2B basis. Please select "Purchase Inquiry" in the Inquiry tab and our sales team will assist you.' : 'A. 1x INV 제품은 B2B 공급을 기본으로 하고 있습니다. 문의하기 탭에서 \'구매 문의\' 유형을 선택하여 문의해주시면 영업팀에서 상담해드립니다.'; ?></p>
                            </div>
                            <div class="faq-item">
                                <h4><?php echo $lang === 'en' ? 'Q. What is the difference between NaviPro X1 and S2?' : 'Q. NaviPro X1과 S2의 차이점은 무엇인가요?'; ?></h4>
                                <p><?php echo $lang === 'en' ? 'A. NaviPro X1 is a premium model with 10.25" display, AI-based route recommendations and premium features, while NaviPro S2 is an economical model with 7" display and basic navigation features. Please refer to the PRODUCT page for detailed comparison.' : 'A. NaviPro X1은 10.25인치 디스플레이, AI 기반 경로 추천 등 프리미엄 기능을 제공하는 고급 모델이며, NaviPro S2는 7인치 디스플레이와 기본 네비게이션 기능을 제공하는 경제적인 모델입니다. 자세한 제품 비교는 PRODUCT 페이지를 참고해주세요.'; ?></p>
                            </div>
                            <div class="faq-item">
                                <h4><?php echo $lang === 'en' ? 'Q. What should I do if the OTA update fails?' : 'Q. OTA 업데이트가 실패하면 어떻게 하나요?'; ?></h4>
                                <p><?php echo $lang === 'en' ? 'A. In case of OTA update failure, it will automatically rollback to the previous version. If retry fails, please request technical support through the Inquiry form.' : 'A. OTA 업데이트 실패 시 자동으로 이전 버전으로 롤백됩니다. 재시도해도 실패하는 경우 문의하기를 통해 기술 지원을 요청해주세요.'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="inquiry-tab" class="tab-content">
                    <div class="inquiry-list">
                        <h3><?php echo $lang === 'en' ? 'Inquiry List' : '문의 내역'; ?></h3>
                        <table class="inquiry-table">
                            <thead>
                                <tr>
                                    <th><?php echo $lang === 'en' ? 'No.' : '번호'; ?></th>
                                    <th><?php echo $lang === 'en' ? 'Subject' : '제목'; ?></th>
                                    <th><?php echo $lang === 'en' ? 'Date' : '날짜'; ?></th>
                                    <th><?php echo $lang === 'en' ? 'Status' : '답변 완료'; ?></th>
                                </tr>
                            </thead>
                            <tbody id="inquiryTableBody">
                                <tr>
                                    <td colspan="4" class="loading"><?php echo $lang === 'en' ? 'Loading...' : '로딩 중...'; ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="inquiry-actions">
                            <button class="btn-submit" onclick="location.href='inquiry-form.php?lang=<?php echo $lang; ?>'"><?php echo $lang === 'en' ? 'New Inquiry' : '문의하기'; ?></button>
                        </div>
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

    <!-- Inquiry Detail Modal -->
    <div id="inquiryModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle"><?php echo $lang === 'en' ? 'Inquiry Detail' : '문의 상세'; ?></h2>
            <div id="modalBody"></div>
        </div>
    </div>

    <!-- Password Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <span class="close-password">&times;</span>
            <h2><?php echo $lang === 'en' ? 'Enter Password' : '비밀번호 입력'; ?></h2>
            <p><?php echo $lang === 'en' ? 'This is a private inquiry. Please enter the password.' : '이 문의는 비공개 문의입니다. 비밀번호를 입력해주세요.'; ?></p>
            <input type="password" id="passwordInput" placeholder="<?php echo $lang === 'en' ? 'Password' : '비밀번호'; ?>" />
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closePasswordModal()"><?php echo $lang === 'en' ? 'Cancel' : '취소'; ?></button>
                <button class="btn-submit" onclick="verifyPassword()"><?php echo $lang === 'en' ? 'Submit' : '확인'; ?></button>
            </div>
        </div>
    </div>

    <script>
        // Pass language to JavaScript
        const LANG = '<?php echo $lang; ?>';
    </script>
    <script src="js/inquiry.js"></script>
</body>
</html>
