    <div class="glass-header-container">
        <div class="header-title-section">
            <h2><?php echo $title; ?></h2>
            <p><?php echo $sub_title; ?></p>
            
        </div>

        <div class="user-nav-section">
            <div class="user-info-text">
                <div class="user-name-top"><?php echo htmlspecialchars($display_name); ?></div>
                <a href="logout.php" class="sign-out-link">Sign Out</a>
            </div>
            <div class="profile-avatar-pill">
                <?php echo strtoupper(substr($display_name, 0, 1)); ?>
            </div>
        </div>
    </div>