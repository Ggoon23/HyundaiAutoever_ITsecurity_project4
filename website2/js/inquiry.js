/**
 * Inquiry Form Handler
 * Handles form submission to backend API with file upload
 */

document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    const imageInput = document.getElementById('image');
    const tabButtons = document.querySelectorAll('.tab-btn');
    const inquiryTableBody = document.getElementById('inquiryTableBody');
    const lockCheckbox = document.getElementById('lockCheckbox');
    const passwordGroup = document.getElementById('passwordGroup');
    const passwordInput = document.getElementById('password');

    // Form submission
    if (contactForm) {
        contactForm.addEventListener('submit', handleFormSubmit);
    }

    // Image preview
    if (imageInput) {
        imageInput.addEventListener('change', handleImagePreview);
    }

    // Lock checkbox toggle
    if (lockCheckbox && passwordGroup) {
        lockCheckbox.addEventListener('change', function() {
            if (this.checked) {
                passwordGroup.style.display = 'block';
                if (passwordInput) passwordInput.required = true;
            } else {
                passwordGroup.style.display = 'none';
                if (passwordInput) {
                    passwordInput.required = false;
                    passwordInput.value = '';
                }
            }
        });
    }

    // Tab switching
    if (tabButtons.length > 0) {
        tabButtons.forEach((button) => {
            const tabName = button.getAttribute('data-tab');
            button.addEventListener('click', function() {
                showTab(tabName);
                // Load inquiry list when inquiry tab is clicked
                if (tabName === 'inquiry') {
                    loadInquiryList();
                }
            });
        });
    }

    // Load inquiry list if on support page with inquiry tab
    if (inquiryTableBody) {
        loadInquiryList();
    }

    // Check URL hash for direct tab access
    if (window.location.hash === '#inquiry') {
        showTab('inquiry');
        loadInquiryList();
    }
});

// Image preview handler
function handleImagePreview(e) {
    const file = e.target.files[0];
    const previewDiv = document.getElementById('imagePreview');

    // Clear previous preview
    previewDiv.innerHTML = '';

    if (file) {
        // WARNING: Weak validation for vulnerability testing
        // Only check file size
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('파일 크기는 5MB를 초과할 수 없습니다.');
            e.target.value = '';
            return;
        }

        // Show file info (no strict type checking)
        const fileName = document.createElement('p');
        fileName.textContent = `선택된 파일: ${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
        fileName.style.fontSize = '0.9rem';
        fileName.style.color = '#666';
        fileName.style.marginTop = '5px';
        previewDiv.appendChild(fileName);

        // Try to show preview for images
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const img = document.createElement('img');
                img.src = event.target.result;
                img.style.maxWidth = '100%';
                img.style.maxHeight = '300px';
                img.style.marginTop = '10px';
                img.style.borderRadius = '5px';
                previewDiv.appendChild(img);
            };
            reader.readAsDataURL(file);
        }
    }
}

async function handleFormSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const submitBtn = form.querySelector('.btn-submit');
    const originalBtnText = submitBtn.textContent;

    // Validate
    if (!form.privacy.checked) {
        alert('개인정보 수집 및 이용에 동의해주세요.');
        return;
    }

    // Create FormData object (for file upload)
    const formData = new FormData();
    formData.append('name', form.name.value);
    formData.append('company', form.company.value);
    formData.append('email', form.email.value);
    formData.append('phone', form.phone.value);
    formData.append('category', form.category.value);
    formData.append('subject', form.subject.value);
    formData.append('message', form.message.value);

    // Add is_locked and password
    const lockCheckbox = form.is_locked;
    if (lockCheckbox && lockCheckbox.checked) {
        formData.append('is_locked', 'on');
        formData.append('password', form.password.value);
    }

    // Add image file if selected
    const imageFile = form.image.files[0];
    if (imageFile) {
        formData.append('image', imageFile);
    }

    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.textContent = '전송 중...';

    try {
        // API endpoint - update this with your actual API URL
        const apiUrl = 'api/submit_inquiry.php';

        const response = await fetch(apiUrl, {
            method: 'POST',
            body: formData  // Don't set Content-Type header for FormData
        });

        const result = await response.json();

        if (result.success) {
            alert('문의가 접수되었습니다. 빠른 시일 내에 답변드리겠습니다.\n문의번호: ' + result.inquiry_id);
            // Redirect to support page with inquiry tab
            window.location.href = 'support.php#inquiry';
        } else {
            alert('문의 접수에 실패했습니다.\n오류: ' + result.message);
        }

    } catch (error) {
        alert('문의 접수 중 오류가 발생했습니다.\n잠시 후 다시 시도해주세요.');
    } finally {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
    }
}

// Tab switching function
function showTab(tabName) {
    const allTabs = document.querySelectorAll('.tab-content');
    const allButtons = document.querySelectorAll('.tab-btn');

    // Hide all tabs
    allTabs.forEach(tab => {
        tab.classList.remove('active');
    });

    // Deactivate all buttons
    allButtons.forEach(btn => {
        btn.classList.remove('active');
    });

    // Show selected tab
    if (tabName === 'faq') {
        const faqTab = document.getElementById('faq-tab');
        if (faqTab) {
            faqTab.classList.add('active');
        }
        if (allButtons[0]) allButtons[0].classList.add('active');
    } else if (tabName === 'inquiry') {
        const inquiryTab = document.getElementById('inquiry-tab');
        if (inquiryTab) {
            inquiryTab.classList.add('active');
        }
        if (allButtons[1]) allButtons[1].classList.add('active');
    }
}

// Translations
const translations = {
    ko: {
        noData: '등록된 문의가 없습니다.',
        loadError: '데이터를 불러올 수 없습니다.',
        fetchError: '데이터 로딩 중 오류가 발생했습니다.',
        completed: '완료',
        inProgress: '처리중',
        pending: '대기',
        detailLoadError: '문의 내용을 불러올 수 없습니다.',
        detailFetchError: '문의 내용 로딩 중 오류가 발생했습니다.',
        inquiryNo: '문의 번호:',
        author: '작성자:',
        company: '회사:',
        email: '이메일:',
        phone: '연락처:',
        category: '문의 유형:',
        date: '작성일:',
        status: '답변 상태:',
        content: '문의 내용',
        attachment: '첨부 이미지',
        categoryProduct: '제품 문의',
        categoryTechnical: '기술 지원',
        categorySales: '구매 문의',
        categoryPartnership: '파트너십',
        categoryOther: '기타',
        passwordRequired: '비밀번호를 입력해주세요.'
    },
    en: {
        noData: 'No inquiries found.',
        loadError: 'Failed to load data.',
        fetchError: 'An error occurred while loading data.',
        completed: 'Completed',
        inProgress: 'In Progress',
        pending: 'Pending',
        detailLoadError: 'Failed to load inquiry details.',
        detailFetchError: 'An error occurred while loading inquiry details.',
        inquiryNo: 'Inquiry No:',
        author: 'Author:',
        company: 'Company:',
        email: 'Email:',
        phone: 'Phone:',
        category: 'Category:',
        date: 'Date:',
        status: 'Status:',
        content: 'Inquiry Content',
        attachment: 'Attachment',
        categoryProduct: 'Product Inquiry',
        categoryTechnical: 'Technical Support',
        categorySales: 'Sales Inquiry',
        categoryPartnership: 'Partnership',
        categoryOther: 'Other',
        passwordRequired: 'Please enter the password.'
    }
};

// Get current language (default to 'ko' if not set)
const currentLang = typeof LANG !== 'undefined' ? LANG : 'ko';
const t = translations[currentLang] || translations.ko;

// Load inquiry list from API
async function loadInquiryList() {
    const tableBody = document.getElementById('inquiryTableBody');
    if (!tableBody) return;

    try {
        const response = await fetch('api/get_inquiries.php');
        const result = await response.json();

        if (result.success && result.inquiries) {
            if (result.inquiries.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="4" class="no-data">${t.noData}</td></tr>`;
                return;
            }

            let html = '';
            result.inquiries.forEach(inquiry => {
                const date = new Date(inquiry.created_at).toLocaleDateString(currentLang === 'en' ? 'en-US' : 'ko-KR');
                const statusText = inquiry.status === 'completed' ? t.completed : inquiry.status === 'in_progress' ? t.inProgress : t.pending;
                const lockIcon = inquiry.is_locked ? '🔒 ' : '';

                html += `
                    <tr onclick="openInquiry(${inquiry.id}, ${inquiry.is_locked})">
                        <td>${inquiry.id}</td>
                        <td class="text-left">${lockIcon}${inquiry.subject}</td>
                        <td>${date}</td>
                        <td>${statusText}</td>
                    </tr>
                `;
            });
            tableBody.innerHTML = html;
        } else {
            tableBody.innerHTML = `<tr><td colspan="4" class="error">${t.loadError}</td></tr>`;
        }
    } catch (error) {
        tableBody.innerHTML = `<tr><td colspan="4" class="error">${t.fetchError}</td></tr>`;
    }
}

// Global variable to store current inquiry ID
let currentInquiryId = null;

// Open inquiry (check if locked)
function openInquiry(id, isLocked) {
    currentInquiryId = id;

    if (isLocked) {
        document.getElementById('passwordModal').style.display = 'block';
        document.getElementById('passwordInput').value = '';
    } else {
        fetchInquiryDetail(id);
    }
}

// Fetch inquiry detail
async function fetchInquiryDetail(id, password = null) {
    try {
        const url = password
            ? `api/get_inquiry_detail.php?id=${id}&password=${encodeURIComponent(password)}`
            : `api/get_inquiry_detail.php?id=${id}`;

        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            showInquiryDetail(result.inquiry);
        } else {
            alert(result.message || t.detailLoadError);
        }
    } catch (error) {
        alert(t.detailFetchError);
    }
}

// Show inquiry detail in modal
function showInquiryDetail(inquiry) {
    const modal = document.getElementById('inquiryModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');

    const date = new Date(inquiry.created_at).toLocaleString(currentLang === 'en' ? 'en-US' : 'ko-KR');
    const statusText = inquiry.status === 'completed' ? t.completed : inquiry.status === 'in_progress' ? t.inProgress : t.pending;
    const categoryText = {
        'product': t.categoryProduct,
        'technical': t.categoryTechnical,
        'sales': t.categorySales,
        'partnership': t.categoryPartnership,
        'other': t.categoryOther
    }[inquiry.category] || inquiry.category;

    modalTitle.textContent = inquiry.subject;

    modalBody.innerHTML = `
        <div class="inquiry-detail">
            <div class="detail-row">
                <span class="detail-label">${t.inquiryNo}</span>
                <span class="detail-value">${inquiry.id}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">${t.author}</span>
                <span class="detail-value">${inquiry.name}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">${t.company}</span>
                <span class="detail-value">${inquiry.company || '-'}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">${t.email}</span>
                <span class="detail-value">${inquiry.email}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">${t.phone}</span>
                <span class="detail-value">${inquiry.phone}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">${t.category}</span>
                <span class="detail-value">${categoryText}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">${t.date}</span>
                <span class="detail-value">${date}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">${t.status}</span>
                <span class="detail-value">${statusText}</span>
            </div>
            <div class="detail-content">
                <h4>${t.content}</h4>
                <pre>${inquiry.message}</pre>
            </div>
            ${inquiry.image_path ? `<div class="detail-image">
                <h4>${t.attachment}</h4>
                <img src="${inquiry.image_path}" alt="${t.attachment}" />
            </div>` : ''}
        </div>
    `;

    modal.style.display = 'block';
}

// Verify password
async function verifyPassword() {
    const password = document.getElementById('passwordInput').value;

    if (!password) {
        alert(t.passwordRequired);
        return;
    }

    closePasswordModal();
    await fetchInquiryDetail(currentInquiryId, password);
}

// Close password modal
function closePasswordModal() {
    document.getElementById('passwordModal').style.display = 'none';
    document.getElementById('passwordInput').value = '';
}

// Close inquiry modal
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('inquiryModal');
    const passwordModal = document.getElementById('passwordModal');
    const closeBtn = document.querySelector('.close');
    const closePasswordBtn = document.querySelector('.close-password');

    if (closeBtn) {
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }
    }

    if (closePasswordBtn) {
        closePasswordBtn.onclick = closePasswordModal;
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
        if (event.target == passwordModal) {
            closePasswordModal();
        }
    }
});
