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
        let touchHandled = false;

        mobileToggle.addEventListener('touchstart', function (e) {
            e.preventDefault();
            e.stopPropagation();
            touchHandled = true;
            sidebar.classList.toggle('open');
        });

        mobileToggle.addEventListener('click', function (e) {
            if (touchHandled) {
                touchHandled = false;
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            e.stopPropagation();
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
        <div class="variant-field color-field">
            <label>Color</label>
            <input type="text" name="variant_color[]" placeholder="e.g., Brown">
        </div>
        <div class="variant-field hex-field">
            <label>Hex Code</label>
            <input type="color" name="variant_hex[]" value="#000000">
        </div>
        <div class="variant-field stock-field">
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

// ============================================================
// GALLERY IMAGE MANAGEMENT
// ============================================================
let galleryImageCounter = 0;

window.addGalleryImage = function() {
    galleryImageCounter++;
    const container = document.getElementById('gallery-images-container');

    if (!container) {
        console.error('Gallery images container not found');
        return;
    }

    const row = document.createElement('div');
    row.className = 'variant-row';
    row.id = 'gallery-image-row-' + galleryImageCounter;
    row.innerHTML = `
        <div class="variant-field" style="flex: 2;">
            <label>Image or Video</label>
            <input type="file" name="gallery_images[]" accept=".jpg,.jpeg,.png,.mp4,.webm,.ogg">
        </div>
        <div class="variant-field">
            <label>Sort Order</label>
            <input type="number" name="gallery_sort_order[]" min="0" value="${galleryImageCounter}">
        </div>
        <button type="button" class="btn-remove-variant" onclick="removeGalleryImage(this)" title="Remove image">×</button>
    `;

    container.appendChild(row);
};

window.removeGalleryImage = function(btn) {
    const row = btn.closest('.variant-row');
    const container = document.getElementById('gallery-images-container');

    if (!row) {
        console.error('Gallery image row not found');
        return;
    }

    row.style.opacity = '0';
    row.style.transform = 'translateX(-10px)';
    row.style.transition = 'all 0.2s ease';
    setTimeout(function () {
        row.remove();
    }, 200);
};

let colorImageCounter = 0;

window.addColorImageRow = function() {
    const container = document.getElementById('color-images-container');

    if (!container) {
        console.error('Color images container not found');
        return;
    }

    const card = document.createElement('div');
    card.className = 'color-image-card';
    card.id = 'color-image-card-' + colorImageCounter;
    card.style.cssText = 'display: inline-block; position: relative;';
    card.innerHTML = `
        <label style="cursor: pointer; display: block;">
            <input type="file" name="color_image_file[]" accept=".jpg,.jpeg,.png" style="display: none;" onchange="previewColorImage(this)">
            <div style="width: 80px; height: 80px; border: 2px dashed var(--border-color); border-radius: var(--radius-sm); display: flex; flex-direction: column; align-items: center; justify-content: center; background: var(--bg-muted);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                    <circle cx="12" cy="13" r="4"/>
                </svg>
                <span style="font-size: 0.7rem; color: var(--text-muted); margin-top: 4px;">Add Image</span>
            </div>
        </label>
        <input type="text" name="color_image_color[]" placeholder="Color" style="width: 80px; font-size: 0.75rem; margin-top: var(--space-xs); padding: 4px;">
        <input type="hidden" name="color_image_id[]" value="">
        <label style="display: block; font-size: 0.75rem; margin-top: var(--space-xs);">
            <input type="checkbox" name="color_image_remove[]" value=""> Remove
        </label>
        <button type="button" class="btn-remove-variant" onclick="removeColorImageRow(this)" title="Remove" style="position: absolute; top: -8px; right: -8px;">×</button>
    `;

    container.appendChild(card);
    colorImageCounter++;
};

window.previewColorImage = function(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const label = input.parentElement;
            const placeholder = label.querySelector('div');
            if (placeholder) {
                placeholder.outerHTML = `
                    <div style="position: relative; width: 80px; height: 80px;">
                        <img src="${e.target.result}" alt="Color image" style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                        <div style="position: absolute; bottom: 4px; right: 4px; background: rgba(0,0,0,0.6); border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                <circle cx="12" cy="13" r="4"/>
                            </svg>
                        </div>
                    </div>
                `;
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
};

window.removeColorImageRow = function(button) {
    const card = button.closest('.color-image-card');
    if (card) {
        card.remove();
    }
};
