<?php

 $display_name = explode(' ', trim($display_name))[0];
?>

<div class="glass-header-container">
        <div class="header-title-section">
            <h2><?php echo $title; ?></h2>
            <p><?php echo $sub_title; ?></p>
            
        </div>

        <div class="user-nav-section">
            <div class="user-dropdown-trigger" id="userDropdownTrigger">
                <div class="user-info-text">
                    <div class="user-name-top"><?php echo htmlspecialchars($display_name); ?></div>
                    <div class="dropdown-arrow">▼</div>
                </div>
                <div class="profile-avatar-pill">
                    <?php echo strtoupper(substr($display_name, 0, 1)); ?>
                </div>
            </div>
            <div class="dropdown-menu" id="userDropdownMenu">
                <div class="dropdown-item username-item">
                    <div class="dropdown-avatar">
                        <?php echo strtoupper(substr($display_name, 0, 1)); ?>
                    </div>
                    <div class="dropdown-user-details">
                       <div class="dropdown-name"><?php echo htmlspecialchars(explode(' ', trim($display_name))[0]); ?></div>
                        <div class="dropdown-email"><?php echo htmlspecialchars($emailname ?? 'user@example.com'); ?></div>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item logout-item">
                    <svg class="logout-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                    Sign Out
                </a>
            </div>
        </div>
    </div>

    <style>


.user-nav-section {
    position: relative;
}

.user-dropdown-trigger {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    padding: 6px 14px 6px 18px;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 80px;
    transition: all 0.25s ease;
    border: 1px solid rgba(255, 255, 255, 0.8);
}

.user-dropdown-trigger:hover {
    background: rgba(255, 255, 255, 0.85);
    border-color: rgba(255, 255, 255, 1);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}

.user-info-text {
    display: flex;
    align-items: center;
    gap: 8px;
}

.user-name-top {
    font-weight: 600;
    font-size: 14px;
    color: #2E073F;
    letter-spacing: -0.2px;
}

.dropdown-arrow {
    font-size: 10px;
    color: #845b8c;
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.user-dropdown-trigger.active .dropdown-arrow {
    transform: rotate(180deg);
}

.profile-avatar-pill {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #7A1CAC, #7A1CAC);
    border-radius: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
    color: white;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

/* Dropdown Menu */
.dropdown-menu {
    position: absolute;
    top: calc(100% + 12px);
    right: 0;
    min-width: 260px;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(12px);
    border-radius: 20px;
    box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.02);
    padding: 8px 0;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-12px);
    transition: opacity 0.28s cubic-bezier(0.2, 0.9, 0.4, 1.1), 
                visibility 0.28s ease,
                transform 0.28s cubic-bezier(0.2, 0.9, 0.4, 1.1);
    z-index: 1000;
}

.dropdown-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 18px;
    text-decoration: none;
    color: #1e293b;
    font-size: 14px;
    font-weight: 500;
    transition: background 0.2s ease;
    cursor: pointer;
}

.dropdown-item:hover {
    background: #f1f5f9;
}

.username-item {
    cursor: default;
    gap: 14px;
}

.username-item:hover {
    background: transparent;
}

.dropdown-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #7A1CAC, #7A1CAC);
    border-radius: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
    color: white;
}

.dropdown-user-details {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.dropdown-name {
    font-weight: 600;
    font-size: 14px;
    color: #1e293b;
}

.dropdown-email {
    font-size: 12px;
    color: #5b6e8c;
}

.dropdown-divider {
    height: 1px;
    background: #e2e8f0;
    margin: 6px 0;
}

.logout-item {
    color: #dc2626;
}

.logout-item:hover {
    background: #fef2f2;
}

.logout-icon {
    margin-right: 2px;
}


.dropdown-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 999;
    display: none;
}

.dropdown-overlay.active {
    display: block;
}
</style>

<script>

(function() {
    const trigger = document.getElementById('userDropdownTrigger');
    const dropdown = document.getElementById('userDropdownMenu');
    
    if (!trigger || !dropdown) return;
    
    let overlay = document.querySelector('.dropdown-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'dropdown-overlay';
        document.body.appendChild(overlay);
    }
    
    function openDropdown() {
        dropdown.classList.add('show');
        trigger.classList.add('active');
        overlay.classList.add('active');
    }
    
    function closeDropdown() {
        dropdown.classList.remove('show');
        trigger.classList.remove('active');
        overlay.classList.remove('active');
    }
    
    function toggleDropdown(e) {
        e.stopPropagation();
        if (dropdown.classList.contains('show')) {
            closeDropdown();
        } else {
            openDropdown();
        }
    }
    
    trigger.addEventListener('click', toggleDropdown);
    
    overlay.addEventListener('click', closeDropdown);
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && dropdown.classList.contains('show')) {
            closeDropdown();
        }
    });
    
    const logoutLink = dropdown.querySelector('.logout-item');
    if (logoutLink) {
        logoutLink.addEventListener('click', function() {
    
        });
    }
})();
</script>