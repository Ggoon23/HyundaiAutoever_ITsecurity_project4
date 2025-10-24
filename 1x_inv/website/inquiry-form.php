<?php
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'ko';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'en' ? 'Inquiry Form' : '문의하기'; ?> - 1x INV</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="lang-bar">
        <div class="container">
            <div class="lang-buttons">
                <a href="?lang=ko" class="lang-btn <?php echo $lang === 'ko' ? 'active' : ''; ?>">한국어</a>
                <a href="?lang=en" class="lang-btn <?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
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
                    <li><a href="support.php?lang=<?php echo $lang; ?>">SUPPORT</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <section class="page-header">
            <div class="container">
                <h2><?php echo $lang === 'en' ? 'Submit Inquiry' : '문의하기'; ?></h2>
                <p><?php echo $lang === 'en' ? 'Please fill out the form below' : '아래 양식을 작성해주세요'; ?></p>
            </div>
        </section>

        <section class="content-section">
            <div class="container">
                <form id="contactForm" class="contact-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name"><?php echo $lang === 'en' ? 'Name' : '이름'; ?> <span class="required">*</span></label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="company"><?php echo $lang === 'en' ? 'Company' : '회사명'; ?></label>
                            <input type="text" id="company" name="company">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email"><?php echo $lang === 'en' ? 'Email' : '이메일'; ?> <span class="required">*</span></label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone"><?php echo $lang === 'en' ? 'Phone' : '연락처'; ?> <span class="required">*</span></label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="category"><?php echo $lang === 'en' ? 'Inquiry Type' : '문의 유형'; ?> <span class="required">*</span></label>
                        <select id="category" name="category" required>
                            <option value=""><?php echo $lang === 'en' ? 'Select inquiry type' : '문의 유형을 선택하세요'; ?></option>
                            <option value="product"><?php echo $lang === 'en' ? 'Product Inquiry' : '제품 문의'; ?></option>
                            <option value="technical"><?php echo $lang === 'en' ? 'Technical Support' : '기술 지원'; ?></option>
                            <option value="sales"><?php echo $lang === 'en' ? 'Sales Inquiry' : '구매 문의'; ?></option>
                            <option value="partnership"><?php echo $lang === 'en' ? 'Partnership' : '파트너십'; ?></option>
                            <option value="other"><?php echo $lang === 'en' ? 'Other' : '기타'; ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="subject"><?php echo $lang === 'en' ? 'Subject' : '제목'; ?> <span class="required">*</span></label>
                        <input type="text" id="subject" name="subject" required>
                    </div>

                    <div class="form-group">
                        <label for="message"><?php echo $lang === 'en' ? 'Message' : '문의 내용'; ?> <span class="required">*</span></label>
                        <textarea id="message" name="message" rows="8" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="image"><?php echo $lang === 'en' ? 'Attachment (Image)' : '첨부 파일 (이미지)'; ?></label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <div id="imagePreview"></div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="lockCheckbox" name="is_locked">
                            <?php echo $lang === 'en' ? 'Private inquiry (password protected)' : '비공개 문의 (비밀번호 설정)'; ?>
                        </label>
                    </div>

                    <div class="form-group" id="passwordGroup" style="display: none;">
                        <label for="password"><?php echo $lang === 'en' ? 'Password' : '비밀번호'; ?> <span class="required">*</span></label>
                        <input type="password" id="password" name="password">
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="privacy" name="privacy" required>
                            <?php echo $lang === 'en' ? 'I agree to the collection and use of personal information' : '개인정보 수집 및 이용에 동의합니다'; ?> <span class="required">*</span>
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="history.back()"><?php echo $lang === 'en' ? 'Cancel' : '취소'; ?></button>
                        <button type="submit" class="btn-submit"><?php echo $lang === 'en' ? 'Submit' : '제출'; ?></button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <script>
        const LANG = '<?php echo $lang; ?>';
    </script>
    <script src="js/inquiry.js"></script>
<?php include 'footer.php'; ?>
