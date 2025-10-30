/**
 * Signup Form Validation and Submission
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('signupForm');
    const messageContainer = document.getElementById('message-container');
    const profileImageInput = document.getElementById('profile_image');
    const mediaFileInput = document.getElementById('media_file');
    const imagePreview = document.getElementById('image-preview');
    const mediaPreview = document.getElementById('media-preview');

    // File preview handlers
    if (profileImageInput) {
        profileImageInput.addEventListener('change', function(e) {
            handleFilePreview(e.target.files[0], imagePreview, 'image');
        });
    }

    if (mediaFileInput) {
        mediaFileInput.addEventListener('change', function(e) {
            handleFilePreview(e.target.files[0], mediaPreview, 'media');
        });
    }

    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Clear previous messages
        messageContainer.innerHTML = '';

        // Validate form
        if (!validateForm()) {
            return;
        }

        // Show loading state
        const submitBtn = form.querySelector('.btn-submit');
        const originalText = submitBtn.textContent;
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
        form.classList.add('form-loading');

        try {
            // Prepare form data
            const formData = new FormData(form);

            // Submit form
            const response = await fetch('process_signup.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            // Show message
            if (result.success) {
                showMessage(result.message, 'success');
                form.reset();
                imagePreview.style.display = 'none';
                mediaPreview.style.display = 'none';

                // Scroll to message
                messageContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });

                // Optional: Redirect after success
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 5000);
            } else {
                if (result.errors && result.errors.length > 0) {
                    showMessage(result.message, 'error', result.errors);
                } else {
                    showMessage(result.message, 'error');
                }
                messageContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } catch (error) {
            showMessage('An error occurred. Please try again.', 'error');
            console.error('Submission error:', error);
        } finally {
            // Remove loading state
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            form.classList.remove('form-loading');
        }
    });

    /**
     * Validate form before submission
     */
    function validateForm() {
        const errors = [];

        // Validate full name
        const fullName = form.full_name.value.trim();
        if (fullName.length < 3) {
            errors.push('Full name must be at least 3 characters');
        }

        // Validate email
        const email = form.email.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            errors.push('Please enter a valid email address');
        }

        // Validate date of birth
        const dob = new Date(form.date_of_birth.value);
        const today = new Date();
        const age = today.getFullYear() - dob.getFullYear();
        if (age < 13 || age > 35) {
            errors.push('Age must be between 13 and 35 years');
        }

        // Validate title
        const title = form.title.value.trim();
        if (title.length < 10 || title.length > 200) {
            errors.push('Title must be between 10 and 200 characters');
        }

        // Validate short description
        const shortDesc = form.short_description.value.trim();
        if (shortDesc.length < 50) {
            errors.push('Short description must be at least 50 characters');
        }

        // Validate full story
        const fullStory = form.full_story.value.trim();
        const wordCount = fullStory.split(/\s+/).filter(word => word.length > 0).length;
        if (wordCount < 50) {
            errors.push('Full story must be at least 50 words');
        }

        // Validate profile image
        if (!profileImageInput.files || profileImageInput.files.length === 0) {
            errors.push('Profile image is required');
        } else {
            const file = profileImageInput.files[0];
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (file.size > maxSize) {
                errors.push('Profile image must be less than 5MB');
            }
        }

        // Validate additional media if provided
        if (mediaFileInput.files && mediaFileInput.files.length > 0) {
            const file = mediaFileInput.files[0];
            const maxSize = 20 * 1024 * 1024; // 20MB
            if (file.size > maxSize) {
                errors.push('Additional media must be less than 20MB');
            }
        }

        // Validate checkboxes
        if (!form.terms.checked) {
            errors.push('You must agree to share your story publicly');
        }

        if (!form.data_consent.checked) {
            errors.push('You must consent to data processing');
        }

        // Show errors if any
        if (errors.length > 0) {
            showMessage('Please fix the following errors:', 'error', errors);
            messageContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return false;
        }

        return true;
    }

    /**
     * Show message to user
     */
    function showMessage(message, type, errors = []) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-error';

        let html = `<div class="alert ${alertClass}">`;
        html += `<strong>${message}</strong>`;

        if (errors.length > 0) {
            html += '<ul>';
            errors.forEach(error => {
                html += `<li>${error}</li>`;
            });
            html += '</ul>';
        }

        html += '</div>';

        messageContainer.innerHTML = html;
    }

    /**
     * Handle file preview
     */
    function handleFilePreview(file, previewElement, type) {
        if (!file) {
            previewElement.style.display = 'none';
            return;
        }

        const reader = new FileReader();

        reader.onload = function(e) {
            previewElement.innerHTML = '';
            previewElement.classList.add('active');
            previewElement.style.display = 'block';

            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Preview';
                previewElement.appendChild(img);

                const caption = document.createElement('p');
                caption.textContent = `Image: ${file.name} (${formatFileSize(file.size)})`;
                previewElement.appendChild(caption);
            } else if (file.type.startsWith('video/')) {
                const video = document.createElement('video');
                video.src = e.target.result;
                video.controls = true;
                previewElement.appendChild(video);

                const caption = document.createElement('p');
                caption.textContent = `Video: ${file.name} (${formatFileSize(file.size)})`;
                previewElement.appendChild(caption);
            } else {
                previewElement.textContent = `File: ${file.name} (${formatFileSize(file.size)})`;
            }
        };

        reader.readAsDataURL(file);
    }

    /**
     * Format file size
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    // Character counter for textareas
    const textareas = form.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        const maxLength = textarea.getAttribute('maxlength');
        if (maxLength) {
            const counter = document.createElement('small');
            counter.className = 'char-counter';
            textarea.parentNode.appendChild(counter);

            function updateCounter() {
                const remaining = maxLength - textarea.value.length;
                counter.textContent = `${remaining} characters remaining`;
                counter.style.color = remaining < 50 ? '#e74c3c' : '#777';
            }

            textarea.addEventListener('input', updateCounter);
            updateCounter();
        }
    });

    // Auto-expand textareas
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });

    // Validate URLs in real-time
    const urlInputs = form.querySelectorAll('input[type="url"]');
    urlInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value && !isValidURL(this.value)) {
                this.style.borderColor = '#e74c3c';
                showInputError(this, 'Please enter a valid URL');
            } else {
                this.style.borderColor = '';
                removeInputError(this);
            }
        });
    });

    function isValidURL(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }

    function showInputError(input, message) {
        removeInputError(input);
        const error = document.createElement('small');
        error.className = 'input-error';
        error.style.color = '#e74c3c';
        error.textContent = message;
        input.parentNode.appendChild(error);
    }

    function removeInputError(input) {
        const existingError = input.parentNode.querySelector('.input-error');
        if (existingError) {
            existingError.remove();
        }
    }
});
