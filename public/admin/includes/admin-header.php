<!-- Admin Header -->
<header class="admin-header">
    <div class="header-left">
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
            <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
            </svg>
        </button>
        <a href="dashboard.php" class="admin-logo">
            <img src="../../assets/images/logob.png" alt="Bihak Center">
            <span>Bihak Admin</span>
        </a>
    </div>

    <div class="header-right">
        <!-- Notifications -->
        <div class="header-item dropdown" id="notificationsDropdown">
            <button class="icon-button" aria-label="Notifications">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                </svg>
                <?php
                // Get pending count
                $conn = getDatabaseConnection();
                $pending_result = $conn->query("SELECT COUNT(*) as count FROM profiles WHERE status = 'pending'");
                $pending_count = $pending_result->fetch_assoc()['count'];
                closeDatabaseConnection($conn);

                if ($pending_count > 0):
                ?>
                    <span class="notification-badge"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu">
                <div class="dropdown-header">
                    <h4>Notifications</h4>
                </div>
                <div class="dropdown-content">
                    <?php if ($pending_count > 0): ?>
                        <a href="profiles.php?status=pending" class="dropdown-item">
                            <div class="dropdown-item-icon">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                                </svg>
                            </div>
                            <div class="dropdown-item-content">
                                <p><strong><?php echo $pending_count; ?></strong> profile<?php echo $pending_count > 1 ? 's' : ''; ?> waiting for review</p>
                            </div>
                        </a>
                    <?php else: ?>
                        <div class="dropdown-item-empty">
                            <p>No new notifications</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- User Profile -->
        <div class="header-item dropdown" id="userDropdown">
            <button class="user-button">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($admin['name'], 0, 1)); ?>
                </div>
                <span class="user-name"><?php echo htmlspecialchars($admin['name']); ?></span>
                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
            <div class="dropdown-menu dropdown-menu-right">
                <div class="dropdown-header">
                    <div class="user-info">
                        <p class="user-info-name"><?php echo htmlspecialchars($admin['name']); ?></p>
                        <p class="user-info-role"><?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?></p>
                    </div>
                </div>
                <div class="dropdown-content">
                    <a href="profile.php" class="dropdown-item">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                        My Profile
                    </a>
                    <a href="settings.php" class="dropdown-item">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                        </svg>
                        Settings
                    </a>
                    <a href="../index.php" class="dropdown-item" target="_blank">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"/>
                            <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"/>
                        </svg>
                        View Website
                    </a>
                </div>
                <div class="dropdown-footer">
                    <a href="logout.php" class="dropdown-item dropdown-item-danger">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/>
                        </svg>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
