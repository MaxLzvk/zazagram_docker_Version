// ============================================================
// create_post.js — Image preview + filter selection
// ============================================================

function previewImage(input) {
    if (!input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview   = document.getElementById('image-preview');
        const placeholder = document.getElementById('upload-placeholder');
        const filterSection = document.getElementById('filter-section');
        if (preview) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            preview.id = 'image-preview'; // keep the id for filter preview
        }
        if (placeholder) placeholder.style.display = 'none';
        if (filterSection) filterSection.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}

function applyPreviewFilter(filterName) {
    const preview = document.getElementById('image-preview');
    if (!preview) return;
    // Remove all filter classes
    preview.className = preview.className.replace(/\bfilter-\S+/g, '');
    preview.classList.add('filter-' + filterName);

    // Highlight selected filter option
    document.querySelectorAll('.filter-option').forEach(opt => {
        opt.classList.remove('selected');
        if (opt.querySelector('input').value === filterName) {
            opt.classList.add('selected');
        }
    });
}

// ── Character counter for caption ────────────────────────
const captionField = document.querySelector('textarea[name="caption"]');
const charCount    = document.querySelector('.char-count');
if (captionField && charCount) {
    captionField.addEventListener('input', () => {
        charCount.textContent = captionField.value.length + ' / 2200';
    });
}

// ── Drag & drop onto upload area ─────────────────────────
const uploadArea = document.getElementById('upload-area');
if (uploadArea) {
    uploadArea.addEventListener('dragover', e => {
        e.preventDefault();
        uploadArea.style.borderColor = 'var(--primary)';
    });
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.style.borderColor = '';
    });
    uploadArea.addEventListener('drop', e => {
        e.preventDefault();
        uploadArea.style.borderColor = '';
        const file = e.dataTransfer.files[0];
        if (!file || !file.type.startsWith('image/')) return;
        const input = document.getElementById('image-input');
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        previewImage(input);
    });
}
