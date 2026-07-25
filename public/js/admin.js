/**
 * Clicks Leather — Admin Panel JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {

    // ============================================================
    // SIDEBAR TOGGLE (Mobile)
    // ============================================================
    const sidebar = document.getElementById('admin-sidebar');
    const mobileToggle = document.getElementById('mobile-toggle');
    const adminMain = document.getElementById('admin-main');

    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });

        // Close sidebar when clicking on main content (mobile)
        if (adminMain) {
            adminMain.addEventListener('click', function () {
                if (sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                }
            });
        }
    }

    // ============================================================
    // IMAGE PREVIEW ON UPLOAD
    // ============================================================
    const fileInput = document.getElementById('product_image');
    const imagePreview = document.getElementById('image-preview');
    const uploadPlaceholder = document.getElementById('upload-placeholder');
    const currentImage = document.getElementById('current-image');
    const uploadArea = document.getElementById('image-upload-area');

    if (fileInput && imagePreview) {
        fileInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!validTypes.includes(file.type)) {
                    alert('Only JPG, JPEG, and PNG files are allowed.');
                    fileInput.value = '';
                    return;
                }

                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB.');
                    fileInput.value = '';
                    return;
                }

                // Show preview
                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    if (uploadPlaceholder) uploadPlaceholder.style.display = 'none';
                    if (currentImage) currentImage.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });

        // Drag and drop styling
        if (uploadArea) {
            uploadArea.addEventListener('dragover', function (e) {
                e.preventDefault();
                this.classList.add('drag-over');
            });

            uploadArea.addEventListener('dragleave', function () {
                this.classList.remove('drag-over');
            });

            uploadArea.addEventListener('drop', function () {
                this.classList.remove('drag-over');
            });
        }
    }

    // ============================================================
    // ALTERNATE IMAGE PREVIEW ON UPLOAD
    // ============================================================
    const fileInputAlt = document.getElementById('product_image_alt');
    const imagePreviewAlt = document.getElementById('image-preview-alt');
    const uploadPlaceholderAlt = document.getElementById('upload-placeholder-alt');
    const currentImageAlt = document.getElementById('current-image-alt');
    const uploadAreaAlt = document.getElementById('image-upload-area-alt');

    if (fileInputAlt && imagePreviewAlt) {
        fileInputAlt.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!validTypes.includes(file.type)) {
                    alert('Only JPG, JPEG, and PNG files are allowed.');
                    fileInputAlt.value = '';
                    return;
                }

                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB.');
                    fileInputAlt.value = '';
                    return;
                }

                // Show preview
                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreviewAlt.src = e.target.result;
                    imagePreviewAlt.style.display = 'block';
                    if (uploadPlaceholderAlt) uploadPlaceholderAlt.style.display = 'none';
                    if (currentImageAlt) currentImageAlt.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });

        // Drag and drop styling
        if (uploadAreaAlt) {
            uploadAreaAlt.addEventListener('dragover', function (e) {
                e.preventDefault();
                this.classList.add('drag-over');
            });

            uploadAreaAlt.addEventListener('dragleave', function () {
                this.classList.remove('drag-over');
            });

            uploadAreaAlt.addEventListener('drop', function () {
                this.classList.remove('drag-over');
            });
        }
    }

    // ============================================================
    // DYNAMIC VARIANT SIZE FIELDS (based on category)
    // ============================================================
    const categorySelect = document.getElementById('category_id');
    
    if (categorySelect) {
        // Categories that need size fields
        const sizeCategories = ['Leather Jackets', 'Leather Shoes', 'Backpacks'];

        function updateSizeFieldsVisibility() {
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            const categoryName = selectedOption ? selectedOption.getAttribute('data-category') : '';
            const sizeFields = document.querySelectorAll('.size-field');
            const showSize = sizeCategories.includes(categoryName);

            sizeFields.forEach(function (field) {
                field.style.display = showSize ? 'block' : 'block'; // Always show but highlight
                if (showSize) {
                    field.classList.add('size-highlighted');
                } else {
                    field.classList.remove('size-highlighted');
                }
            });

            // Update hint text
            const hint = document.getElementById('variant-hint');
            if (hint) {
                if (showSize) {
                    hint.textContent = 'Size field is important for ' + categoryName + '. Add variants with specific sizes.';
                    hint.style.color = '#C8956C';
                } else {
                    hint.textContent = 'Add product variants below. Size fields are shown for Shoes, Jackets, and similar categories.';
                    hint.style.color = '';
                }
            }
        }

        categorySelect.addEventListener('change', updateSizeFieldsVisibility);
        // Run on page load too
        updateSizeFieldsVisibility();
    }

    // ============================================================
    // AUTO-DISMISS FLASH MESSAGES
    // ============================================================
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(function (msg) {
        setTimeout(function () {
            msg.style.opacity = '0';
            msg.style.transform = 'translateY(-8px)';
            msg.style.transition = 'all 0.3s ease';
            setTimeout(function () {
                msg.remove();
            }, 300);
        }, 5000);
    });

    // ============================================================
    // DELETE CONFIRMATION (double-check)
    // ============================================================
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
});

// ============================================================
// VARIANT ROW MANAGEMENT (Global functions for inline handlers)
// ============================================================
let variantCounter = document.querySelectorAll('.variant-row').length || 1;

function addVariant() {
    variantCounter++;
    const container = document.getElementById('variants-container');
    
    const row = document.createElement('div');
    row.className = 'variant-row';
    row.id = 'variant-row-' + variantCounter;
    row.innerHTML = `
        <div class="variant-field size-field">
            <label>Size</label>
            <input type="text" name="variant_size[]" placeholder="e.g., M, L, 42">
        </div>
        <div class="variant-field">
            <label>Color</label>
            <input type="text" name="variant_color[]" placeholder="e.g., Brown">
        </div>
        <div class="variant-field">
            <label>Stock</label>
            <input type="number" name="variant_stock[]" min="0" value="0">
        </div>
        <button type="button" class="btn-remove-variant" onclick="removeVariant(this)" title="Remove variant">×</button>
    `;
    
    container.appendChild(row);

    // Trigger size field visibility check
    const categorySelect = document.getElementById('category_id');
    if (categorySelect) {
        categorySelect.dispatchEvent(new Event('change'));
    }
}

function removeVariant(btn) {
    const row = btn.closest('.variant-row');
    const container = document.getElementById('variants-container');
    
    // Keep at least one variant row
    if (container.querySelectorAll('.variant-row').length > 1) {
        row.style.opacity = '0';
        row.style.transform = 'translateX(-10px)';
        row.style.transition = 'all 0.2s ease';
        setTimeout(function () {
            row.remove();
        }, 200);
    } else {
        alert('At least one variant row is required.');
    }
}
