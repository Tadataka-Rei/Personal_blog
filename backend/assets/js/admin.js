/* ============================================
   admin.js — Admin panel helpers
   ============================================ */

/**
 * Tags input management
 */
document.addEventListener('DOMContentLoaded', function () {
    const tagsWrappers = document.querySelectorAll('.tags-input-wrapper');
    
    tagsWrappers.forEach(wrapper => {
        const input = wrapper.querySelector('.tag-input');
        const hiddenInput = wrapper.querySelector('.tags-hidden');
        
        if (!input || !hiddenInput) return;
        
        // Add tag on Enter or comma
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const value = this.value.trim();
                if (value) {
                    addTag(wrapper, value);
                    this.value = '';
                    updateHiddenInput(wrapper);
                }
            }
        });
        
        // Remove tag on backspace when input is empty
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && this.value === '') {
                const tags = wrapper.querySelectorAll('.tag-item');
                if (tags.length > 0) {
                    tags[tags.length - 1].remove();
                    updateHiddenInput(wrapper);
                }
            }
        });
        
        // Initialize from hidden input
        const existingTags = hiddenInput.value.split(',').filter(t => t.trim());
        existingTags.forEach(tag => {
            addTag(wrapper, tag.trim());
        });
    });
    
    function addTag(wrapper, text) {
        const tag = document.createElement('span');
        tag.className = 'tag-item';
        tag.innerHTML = `${text} <span class="tag-remove" onclick="this.parentElement.remove(); updateHiddenInput(this.closest('.tags-input-wrapper'))">&times;</span>`;
        
        const input = wrapper.querySelector('.tag-input');
        wrapper.insertBefore(tag, input);
    }
});

/**
 * Update the hidden input with comma-separated tags
 */
function updateHiddenInput(wrapper) {
    const hiddenInput = wrapper.querySelector('.tags-hidden');
    const tags = wrapper.querySelectorAll('.tag-item');
    const values = Array.from(tags).map(tag => tag.textContent.replace('×', '').trim());
    hiddenInput.value = values.join(',');
}

/**
 * Add a new section to the form
 */
function addSection() {
    const container = document.getElementById('sections-container');
    const index = container.children.length;
    
    const sectionDiv = document.createElement('div');
    sectionDiv.className = 'section-editor';
    sectionDiv.innerHTML = `
        <div class="section-header">
            <h4>Section ${index + 1}</h4>
            <button type="button" class="section-remove" onclick="removeSection(this)" title="Remove section">&times;</button>
        </div>
        <div class="form-group">
            <label>Heading</label>
            <input type="text" class="form-control" name="sections[${index}][heading]" placeholder="Section heading" required>
        </div>
        <div class="form-group">
            <label>Content (HTML allowed)</label>
            <textarea class="form-control wysiwyg-editor" name="sections[${index}][paragraphs]" placeholder="Write your content here... HTML tags like <img>, <a>, <code> are allowed" rows="6"></textarea>
        </div>
    `;
    
    container.appendChild(sectionDiv);
    
    // Initialize TinyMCE for the new textarea
    if (typeof tinymce !== 'undefined') {
        const textarea = sectionDiv.querySelector('.wysiwyg-editor');
        textarea.id = 'editor-' + Date.now();
        tinymce.init({
            selector: '#' + textarea.id,
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
            toolbar: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code | removeformat',
            menubar: false,
            branding: false,
            promotion: false,
            height: 300,
            content_style: `
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
                    font-size: 15px; 
                    line-height: 1.7; 
                    color: #e0e0e0; 
                    background: #0d0f1f; 
                    padding: 15px; 
                }
                img { max-width: 100%; height: auto; }
                a { color: #00e1ff; }
                code { background: rgba(0,225,255,.1); padding: 2px 6px; border-radius: 3px; }
            `,
            valid_elements: '*[*]',
            extended_valid_elements: 'img[class|src|alt|title|width|height|loading|style],a[class|href|target|rel|title],code[class],span[class|style],div[class|style]',
            valid_children: '+body[style],+div[style]',
            setup: function (editor) {
                editor.on('change', function () {
                    editor.save();
                });
            }
        });
    }
}

/**
 * Remove a section from the form
 */
function removeSection(btn) {
    if (confirm('Remove this section?')) {
        const section = btn.closest('.section-editor');
        const textarea = section.querySelector('.wysiwyg-editor');
        if (textarea && typeof tinymce !== 'undefined') {
            const editor = tinymce.get(textarea.id);
            if (editor) {
                tinymce.remove(editor);
            }
        }
        section.remove();
        // Renumber sections
        renumberSections();
    }
}

/**
 * Renumber sections after removal
 */
function renumberSections() {
    const container = document.getElementById('sections-container');
    const sections = container.querySelectorAll('.section-editor');
    sections.forEach((section, index) => {
        section.querySelector('h4').textContent = `Section ${index + 1}`;
        const heading = section.querySelector('input[name*="[heading]"]');
        const paragraphs = section.querySelector('textarea[name*="[paragraphs]"]');
        if (heading) heading.name = `sections[${index}][heading]`;
        if (paragraphs) paragraphs.name = `sections[${index}][paragraphs]`;
    });
}

/**
 * Confirm before form submission for delete
 */
function confirmDelete(form) {
    return confirm('Are you sure you want to delete this post? This action cannot be undone.');
}

