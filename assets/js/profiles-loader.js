/**
 * Profiles Loader
 * Handles "Load More" functionality for profiles
 */

document.addEventListener('DOMContentLoaded', function() {
    const loadMoreBtn = document.getElementById('load-more-btn');
    const loadingSpinner = document.getElementById('loading-spinner');
    const profilesContainer = document.getElementById('profiles-container');

    let offset = 9; // Initial load shows 9 profiles
    let isLoading = false;

    if (!loadMoreBtn) return;

    loadMoreBtn.addEventListener('click', async function() {
        if (isLoading) return;

        isLoading = true;
        loadMoreBtn.style.display = 'none';
        loadingSpinner.style.display = 'block';

        try {
            const response = await fetch(`profiles.php?offset=${offset}&limit=8`);
            const data = await response.json();

            if (data.success && data.profiles.length > 0) {
                // Append new profiles
                data.profiles.forEach(profile => {
                    const profileCard = createProfileCard(profile);
                    profilesContainer.appendChild(profileCard);
                });

                offset += data.profiles.length;

                // Hide button if no more profiles
                if (!data.hasMore) {
                    loadMoreBtn.style.display = 'none';
                    showEndMessage();
                } else {
                    loadMoreBtn.style.display = 'inline-block';
                }
            } else {
                loadMoreBtn.style.display = 'none';
                showEndMessage();
            }
        } catch (error) {
            console.error('Error loading profiles:', error);
            showError();
            loadMoreBtn.style.display = 'inline-block';
        } finally {
            loadingSpinner.style.display = 'none';
            isLoading = false;
        }
    });

    /**
     * Create profile card HTML
     */
    function createProfileCard(profile) {
        const card = document.createElement('div');
        card.className = 'profile-card';
        card.dataset.profileId = profile.id;

        // Media HTML
        let mediaHTML;
        if (profile.media_type === 'video' && profile.media_url) {
            mediaHTML = `<video src="${escapeHtml(profile.media_url)}" controls></video>`;
        } else {
            mediaHTML = `<img src="${escapeHtml(profile.profile_image)}" alt="${escapeHtml(profile.full_name)}">`;
        }

        // Truncate description
        let description = profile.short_description;
        if (description.length > 150) {
            description = description.substring(0, 150) + '...';
        }

        // Build card HTML
        card.innerHTML = `
            <div class="profile-media">
                ${mediaHTML}
            </div>
            <div class="profile-content">
                <h3 class="profile-name">${escapeHtml(profile.full_name)}</h3>
                <p class="profile-title">${escapeHtml(profile.title)}</p>
                <p class="profile-description">${escapeHtml(description)}</p>
                <div class="profile-meta">
                    <span class="location">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                        </svg>
                        ${escapeHtml(profile.city)}, ${escapeHtml(profile.district)}
                    </span>
                    ${profile.field_of_study ? `
                        <span class="field">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8.5 2a.5.5 0 0 1 .5.5v9.793l2.146-2.147a.5.5 0 0 1 .708.708l-3 3a.5.5 0 0 1-.708 0l-3-3a.5.5 0 1 1 .708-.708L7.5 12.293V2.5a.5.5 0 0 1 .5-.5z"/>
                            </svg>
                            ${escapeHtml(profile.field_of_study)}
                        </span>
                    ` : ''}
                </div>
                <a href="profile.php?id=${profile.id}" class="profile-link">Read Full Story →</a>
            </div>
        `;

        // Add animation
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';

        setTimeout(() => {
            card.style.transition = 'opacity 0.5s, transform 0.5s';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100);

        return card;
    }

    /**
     * Show end of profiles message
     */
    function showEndMessage() {
        const endMessage = document.createElement('div');
        endMessage.className = 'end-message';
        endMessage.style.cssText = 'text-align: center; padding: 30px; color: #666; font-size: 1.1rem;';
        endMessage.innerHTML = `
            <p>You've seen all our amazing stories!</p>
            <a href="signup.php" class="btn" style="display: inline-block; margin-top: 15px;">Share Your Story</a>
        `;

        const container = document.querySelector('.load-more-container');
        if (container) {
            container.appendChild(endMessage);
        }
    }

    /**
     * Show error message
     */
    function showError() {
        const errorMessage = document.createElement('div');
        errorMessage.className = 'error-message';
        errorMessage.style.cssText = 'background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 20px 0; text-align: center;';
        errorMessage.textContent = 'Failed to load more stories. Please try again.';

        const container = document.querySelector('.load-more-container');
        if (container) {
            container.appendChild(errorMessage);

            setTimeout(() => {
                errorMessage.remove();
            }, 5000);
        }
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text ? String(text).replace(/[&<>"']/g, m => map[m]) : '';
    }

    // Optional: Infinite scroll
    // Uncomment to enable infinite scroll instead of "Load More" button
    /*
    let scrollTimeout;
    window.addEventListener('scroll', function() {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            if (isScrolledToBottom() && !isLoading) {
                loadMoreBtn.click();
            }
        }, 200);
    });

    function isScrolledToBottom() {
        const scrollPosition = window.scrollY + window.innerHeight;
        const pageHeight = document.documentElement.scrollHeight;
        return scrollPosition >= pageHeight - 500; // Trigger 500px before bottom
    }
    */
});
