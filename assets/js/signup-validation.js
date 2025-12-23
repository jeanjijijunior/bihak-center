/**
 * Signup Form Validation and Submission with Multiple Image Upload Support
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('signupForm');
    const messageContainer = document.getElementById('message-container');
    const profileImagesInput = document.getElementById('profile_images');
    const imagesPreviewContainer = document.getElementById('images-preview-container');
    const imageDescriptionsContainer = document.getElementById('image-descriptions-container');

    let selectedFiles = [];

    // Multiple images preview handler
    if (profileImagesInput) {
        profileImagesInput.addEventListener('change', function(e) {
            handleMultipleImagesPreview(e.target.files);
        });
    }

    /**
     * Handle multiple images preview with description fields
     */
    function handleMultipleImagesPreview(files) {
        selectedFiles = Array.from(files);

        if (selectedFiles.length === 0) {
            imagesPreviewContainer.innerHTML = '';
            imageDescriptionsContainer.innerHTML = '';
            imageDescriptionsContainer.style.display = 'none';
            return;
        }

        // Clear previous previews
        imagesPreviewContainer.innerHTML = '';
        imageDescriptionsContainer.innerHTML = '';
        imageDescriptionsContainer.style.display = 'block';

        selectedFiles.forEach((file, index) => {
            // Create preview item
            const previewItem = document.createElement('div');
            previewItem.className = 'image-preview-item';
            previewItem.style.cssText = 'position: relative; display: inline-block; margin: 10px;';

            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Preview ' + (index + 1);
                img.style.cssText = 'width: 150px; height: 150px; object-fit: cover; border-radius: 8px; border: 2px solid #e2e8f0;';

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.innerHTML = '&times;';
                removeBtn.className = 'remove-image-btn';
                removeBtn.style.cssText = 'position: absolute; top: 5px; right: 5px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 25px; height: 25px; cursor: pointer; font-size: 18px; line-height: 1;';
                removeBtn.onclick = function() {
                    removeImage(index);
                };

                const caption = document.createElement('p');
                caption.textContent = file.name;
                caption.style.cssText = 'font-size: 0.85rem; color: #718096; margin-top: 5px; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;';

                previewItem.appendChild(img);
                previewItem.appendChild(removeBtn);
                previewItem.appendChild(caption);
                imagesPreviewContainer.appendChild(previewItem);
            };
            reader.readAsDataURL(file);

            // Create description field
            const descField = document.createElement('div');
            descField.className = 'form-group';
            descField.style.marginBottom = '15px';
            descField.innerHTML = `
                <label for="image_description_${index}">Description for image ${index + 1} (optional)</label>
                <input type="text"
                       id="image_description_${index}"
                       name="image_descriptions[]"
                       class="form-control"
                       placeholder="Describe this image..."
                       maxlength="200"
                       style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;">
                <small style="color: #718096; font-size: 0.85rem;">Max 200 characters</small>
            `;
            imageDescriptionsContainer.appendChild(descField);
        });
    }

    /**
     * Remove image from selection
     */
    function removeImage(index) {
        selectedFiles.splice(index, 1);

        // Create new FileList
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        profileImagesInput.files = dt.files;

        // Re-render previews
        handleMultipleImagesPreview(selectedFiles);
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
                imagesPreviewContainer.innerHTML = '';
                imageDescriptionsContainer.innerHTML = '';
                imageDescriptionsContainer.style.display = 'none';

                // Scroll to message
                messageContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });

                // Optional: Redirect after success
                setTimeout(() => {
                    window.location.href = 'login.php?message=' + encodeURIComponent('Account created successfully! Please log in.');
                }, 3000);
            } else {
                // Build detailed error message
                let errorDetails = [];

                if (result.errors && result.errors.length > 0) {
                    errorDetails = result.errors;
                }

                // Add database error if present
                if (result.database_error) {
                    errorDetails.push('Database Error: ' + result.database_error);
                }

                // Add error details for debugging
                if (result.error_details) {
                    console.error('Error Details:', result.error_details);
                    errorDetails.push('Error Type: ' + result.error_details.type);
                }

                showMessage(result.message || 'An error occurred', 'error', errorDetails);
                messageContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } catch (error) {
            // More detailed error message
            let errorMsg = 'An error occurred while processing your request. ';

            if (error.message) {
                errorMsg += 'Details: ' + error.message;
            }

            showMessage(errorMsg, 'error', [
                'Please check your internet connection',
                'Make sure all required fields are filled',
                'Check the browser console (F12) for more details'
            ]);

            console.error('Submission error:', error);
            console.error('Error stack:', error.stack);
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

        // Validate passwords
        const password = form.password.value;
        const passwordConfirm = form.password_confirm.value;

        if (password.length < 8) {
            errors.push('Password must be at least 8 characters');
        }

        if (password !== passwordConfirm) {
            errors.push('Passwords do not match');
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

        // Validate profile images
        if (!profileImagesInput.files || profileImagesInput.files.length === 0) {
            errors.push('At least one profile image is required');
        } else {
            const maxSize = 5 * 1024 * 1024; // 5MB per image
            const files = Array.from(profileImagesInput.files);

            if (files.length > 5) {
                errors.push('Maximum 5 images allowed');
            }

            files.forEach((file, index) => {
                if (file.size > maxSize) {
                    errors.push(`Image ${index + 1} exceeds 5MB limit`);
                }
                if (!file.type.startsWith('image/')) {
                    errors.push(`File ${index + 1} is not a valid image`);
                }
            });
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
