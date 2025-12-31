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

        // Clear previous messages (both top container and any existing modals)
        messageContainer.innerHTML = '';
        const existingModal = document.getElementById('signupModal');
        if (existingModal) {
            existingModal.remove();
        }

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

                // Redirect after success (modal will show for 3 seconds)
                setTimeout(() => {
                    window.location.href = 'login.php?message=' + encodeURIComponent('Account created successfully! Please log in.');
                }, 3000);
            } else {
                // Build detailed error message
                let errorDetails = [];

                if (result.errors && result.errors.length > 0) {
                    errorDetails = result.errors;
                }

                // Add database error if present (only in development)
                if (result.database_error) {
                    console.error('Database Error:', result.database_error);
                    errorDetails.push('A database error occurred. Please try again or contact support if the problem persists.');
                }

                // Log technical details to console for debugging (not shown to user)
                if (result.error_details) {
                    console.error('Technical Error Details:', result.error_details);
                    console.error('Error Type:', result.error_details.type);
                    console.error('Location:', result.error_details.file, result.error_details.line);
                }

                showMessage(result.message || 'An error occurred while creating your account. Please try again.', 'error', errorDetails);
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
            const maxSize = 2 * 1024 * 1024; // 2MB per image (server limit)
            const maxImages = 3; // Limit to 3 images due to 8MB post_max_size
            const files = Array.from(profileImagesInput.files);

            if (files.length > maxImages) {
                errors.push(`Maximum ${maxImages} images allowed`);
            }

            files.forEach((file, index) => {
                if (file.size > maxSize) {
                    errors.push(`Image ${index + 1} exceeds 2MB limit (size: ${(file.size / 1024 / 1024).toFixed(2)}MB)`);
                }
                if (!file.type.startsWith('image/')) {
                    errors.push(`File ${index + 1} is not a valid image`);
                }
            });
        }

        // Validate security questions
        const securityQuestion1 = form.security_question_1.value;
        const securityQuestion2 = form.security_question_2.value;
        const securityQuestion3 = form.security_question_3.value;
        const securityAnswer1 = form.security_answer_1.value.trim();
        const securityAnswer2 = form.security_answer_2.value.trim();
        const securityAnswer3 = form.security_answer_3.value.trim();

        // Check if all security questions are selected
        if (!securityQuestion1 || !securityQuestion2 || !securityQuestion3) {
            errors.push('Please select all 3 security questions');
        }

        // Check if all answers are provided
        if (!securityAnswer1 || !securityAnswer2 || !securityAnswer3) {
            errors.push('Please provide answers to all 3 security questions');
        }

        // Check if the same question is selected multiple times
        if (securityQuestion1 && securityQuestion2 && securityQuestion1 === securityQuestion2) {
            errors.push('Security questions 1 and 2 must be different');
        }
        if (securityQuestion1 && securityQuestion3 && securityQuestion1 === securityQuestion3) {
            errors.push('Security questions 1 and 3 must be different');
        }
        if (securityQuestion2 && securityQuestion3 && securityQuestion2 === securityQuestion3) {
            errors.push('Security questions 2 and 3 must be different');
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
            return false;
        }

        return true;
    }

    /**
     * Show message to user in a modal popup
     */
    function showMessage(message, type, errors = []) {
        // Remove any existing modal
        const existingModal = document.getElementById('signupModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Determine icon and colors based on type
        const isSuccess = type === 'success';
        const iconColor = isSuccess ? '#10b981' : '#ef4444';
        const iconSvg = isSuccess
            ? '<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'
            : '<path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';

        // Build error list HTML
        let errorListHtml = '';
        if (errors.length > 0) {
            errorListHtml = '<ul style="margin: 15px 0 0 0; padding-left: 20px; text-align: left;">';
            errors.forEach(error => {
                errorListHtml += `<li style="margin: 8px 0; color: #4b5563;">${error}</li>`;
            });
            errorListHtml += '</ul>';
        }

        // Create modal HTML
        const modalHtml = `
            <div id="signupModal" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                animation: fadeIn 0.2s ease;
            ">
                <div style="
                    background: white;
                    border-radius: 12px;
                    padding: 30px;
                    max-width: 500px;
                    width: 90%;
                    max-height: 80vh;
                    overflow-y: auto;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                    animation: slideUp 0.3s ease;
                    text-align: center;
                ">
                    <div style="
                        width: 60px;
                        height: 60px;
                        margin: 0 auto 20px;
                        border-radius: 50%;
                        background: ${iconColor}15;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    ">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="${iconColor}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            ${iconSvg}
                        </svg>
                    </div>
                    <h3 style="
                        font-size: 1.5rem;
                        font-weight: 600;
                        color: #1f2937;
                        margin: 0 0 15px 0;
                    ">${isSuccess ? 'Success!' : 'Oops!'}</h3>
                    <p style="
                        font-size: 1rem;
                        color: #4b5563;
                        line-height: 1.6;
                        margin: 0;
                    ">${message}</p>
                    ${errorListHtml}
                    <button onclick="document.getElementById('signupModal').remove()" style="
                        margin-top: 25px;
                        padding: 12px 32px;
                        background: ${isSuccess ? '#10b981' : '#ef4444'};
                        color: white;
                        border: none;
                        border-radius: 8px;
                        font-size: 1rem;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.2s;
                    " onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                        ${isSuccess ? 'Got it!' : 'OK, I\'ll fix it'}
                    </button>
                </div>
            </div>
        `;

        // Add modal to page
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Add CSS animations if not already present
        if (!document.getElementById('signupModalStyles')) {
            const styles = document.createElement('style');
            styles.id = 'signupModalStyles';
            styles.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes slideUp {
                    from {
                        opacity: 0;
                        transform: translateY(20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
            `;
            document.head.appendChild(styles);
        }

        // Close modal on escape key
        const closeOnEscape = (e) => {
            if (e.key === 'Escape') {
                const modal = document.getElementById('signupModal');
                if (modal) modal.remove();
                document.removeEventListener('keydown', closeOnEscape);
            }
        };
        document.addEventListener('keydown', closeOnEscape);

        // Close modal when clicking outside
        const modal = document.getElementById('signupModal');
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
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
