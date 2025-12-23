<!-- Admin Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
    <nav class="sidebar-nav">
        <!-- Dashboard -->
        <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="nav-icon">
                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
            </svg>
            <span class="nav-text">Dashboard</span>
        </a>

        <!-- Profiles Section -->
        <div class="nav-section">
            <h4 class="nav-section-title">Profile management</h4>

            <a href="profiles.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'profiles.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="nav-icon">
                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                </svg>
                <span class="nav-text">All profiles</span>
            </a>

            <a href="profiles.php?status=pending" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'profiles.php' && isset($_GET['status']) && $_GET['status'] == 'pending') ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="nav-icon">
                    <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                </svg>
                <span class="nav-text">Pending review</span>
                <?php
                // Get pending count
                $conn = getDatabaseConnection();
                $pending_result = $conn->query("SELECT COUNT(*) as count FROM profiles WHERE status = 'pending'");
                $pending_count = 0;
                if ($pending_result && $pending_result->num_rows > 0) {
                    $pending_count = $pending_result->fetch_assoc()['count'];
                }
                closeDatabaseConnection($conn);

                if ($pending_count > 0):
                ?>
                    <span class="nav-badge"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>

            <a href="profiles.php?status=approved" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'profiles.php' && isset($_GET['status']) && $_GET['status'] == 'approved') ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="nav-icon">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="nav-text">Approved</span>
            </a>

            <a href="profiles.php?status=rejected" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'profiles.php' && isset($_GET['status']) && $_GET['status'] == 'rejected') ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="nav-icon">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span class="nav-text">Rejected</span>
            </a>
        </div>

        <!-- Content Section -->
        <div class="nav-section">
            <h4 class="nav-section-title">Content management</h4>

            <a href="content-manager.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'content-manager.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="nav-icon">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                </svg>
                <span class="nav-text">Edit page content</span>
            </a>

            <a href="media.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'media.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="nav-icon">
                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                </svg>
                <span class="nav-text">Media library</span>
            </a>
        </div>

        <!-- Community Section -->
        <div class="nav-section">
            <h4 class="nav-section-title">Community</h4>

            <a href="sponsors.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'sponsors.php' || basename($_SERVER['PHP_SELF']) == 'sponsor-review.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="nav-icon">
                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                </svg>
                <span class="nav-text">Sponsors & partners</span>
            </a>

            <a href="donations.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'donations.php' || basename($_SERVER['PHP_SELF']) == 'donation-details.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="nav-icon">
                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                </svg>
                <span class="nav-text">Donations</span>
            </a>
        </div>

        <!-- Incubation Section -->
        <div class="nav-section">
            <h4 class="nav-section-title">Incubation Program</h4>

            <a href="incubation-admin-dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'incubation-admin-dashboard.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="nav-icon">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                </svg>
                <span class="nav-text">Dashboard</span>
            </a>

            <a href="incubation-teams.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'incubation-teams.php' || basename($_SERVER['PHP_SELF']) == 'incubation-team-detail.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="nav-icon">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                </svg>
                <span class="nav-text">Teams</span>
            </a>

            <a href="incubation-exercises.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'incubation-exercises.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="nav-icon">
                    <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>
                </svg>
                <span class="nav-text">Exercises</span>
            </a>

            <a href="incubation-reviews.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'incubation-reviews.php' || basename($_SERVER['PHP_SELF']) == 'incubation-review-submission.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="nav-icon">
                    <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                </svg>
                <span class="nav-text">Review Submissions</span>
                <?php
                // Get pending submissions count
                $conn = getDatabaseConnection();
                $submissions_result = $conn->query("SELECT COUNT(*) as count FROM exercise_submissions WHERE status = 'submitted'");
                $submissions_count = 0;
                if ($submissions_result && $submissions_result->num_rows > 0) {
                    $submissions_count = $submissions_result->fetch_assoc()['count'];
                }
                closeDatabaseConnection($conn);

                if ($submissions_count > 0):
                ?>
                    <span class="nav-badge"><?php echo $submissions_count; ?></span>
                <?php endif; ?>
            </a>

            <a href="incubation-reports.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'incubation-reports.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="nav-icon">
                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                </svg>
                <span class="nav-text">Reports</span>
            </a>
        </div>

        <!-- System Section -->
        <div class="nav-section">
            <h4 class="nav-section-title">System</h4>

            <a href="analytics.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="nav-icon">
                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                </svg>
                <span class="nav-text">Analytics</span>
            </a>

            <a href="admin-users.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin-users.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="nav-icon">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                </svg>
                <span class="nav-text">Admin users</span>
            </a>

            <a href="activity-log.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'activity-log.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="nav-icon">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="nav-text">Activity log</span>
            </a>

            <a href="settings.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="nav-icon">
                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                </svg>
                <span class="nav-text">Settings</span>
            </a>
        </div>

        <!-- Bottom Section -->
        <div class="sidebar-bottom">
            <a href="../index.php" class="nav-item" target="_blank">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="nav-icon">
                    <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"/>
                    <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"/>
                </svg>
                <span class="nav-text">View website</span>
            </a>

            <a href="logout.php" class="nav-item nav-item-danger">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="nav-icon">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/>
                </svg>
                <span class="nav-text">Logout</span>
            </a>
        </div>
    </nav>
</aside>
