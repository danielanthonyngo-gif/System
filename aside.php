<aside class="sidebar">
<?php
// Aside.php - Sidebar Component
$current_page = basename($_SERVER['PHP_SELF']);
?>
123
<style>
    :root {
        /* Gradient Background: Purple to Pink */
        --sidebar-gradient: linear-gradient(180deg, #692a7a 0%, #a24bcf 50%, #d83a8a 100%);
        --sidebar-hover: rgba(255, 255, 255, 0.15);
        --accent-color: #ff9ff3; /* Mas light na pink/purple para sa active indicator */
        --sidebar-width: 260px;
        --logo-dark: #2d2d2d; 
        --logo-purple: #9b59b6;
    }

    /* Sidebar Main Style */
    .sidebar {
        width: var(--sidebar-width);
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        background: var(--sidebar-gradient); /* In-apply ang gradient dito */
        color: white;
        z-index: 1000;
        box-shadow: 4px 0 15px rgba(0,0,0,0.3);
        transition: all 0.3s ease;
    }

    /* Brand Section - Styled like the logo image */
    .brand-section {
        padding: 30px 20px;
        background: #fffeff; /* Light beige contrast para lumitaw ang logo */
        display: flex;
        align-items: center;
        justify-content: center;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .logo-text {
        font-family: 'Arial Black', sans-serif;
        font-size: 2.2rem;
        font-weight: 900;
        letter-spacing: -2px;
        color: var(--logo-dark);
        text-transform: lowercase;
        line-height: 1;
    }

    .logo-text span.accent {
        color: var(--logo-purple);
    }

    /* Navigation Menu */
    .nav-menu {
        padding-top: 25px;
    }

    .nav-item {
        padding: 15px 25px;
        display: flex;
        align-items: center;
        color: rgba(245, 204, 241, 0.9) !important;
        text-decoration: none !important;
        transition: all 0.3s ease;
        border-left: 5px solid transparent;
        font-size: 0.95rem;
        margin-bottom: 4px;
    }

    .nav-item i {
        margin-right: 15px;
        width: 25px;
        text-align: center;
        font-size: 1.1rem;
    }

    /* Hover and Active State */
    .nav-item:hover {
        background: var(--sidebar-hover);
        color: white !important;
    }

    .nav-item.active {
        background: rgba(255, 255, 255, 0.1);
        color: white !important;
        border-left: 5px solid var(--accent-color);
        font-weight: 600;
    }

    /* Content Adjustment */
    .content-wrapper, .content {
        margin-left: var(--sidebar-width);
        transition: all 0.3s ease;
    }

    @media (max-width: 992px) {
        .sidebar { left: -260px; }
        .content-wrapper, .content { margin-left: 0; }
    }
</style>

<div class="sidebar">
    <div class="brand-section">
        <div class="logo-text">
            inspi<span class="accent">r</span>o
        </div>
    </div>

    <nav class="nav-menu">
        <a href="index.php" class="nav-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i> 
            <span>Dashboard</span>
        </a>

        <a href="view_area.php" class="nav-item <?php echo ($current_page == 'view_area.php') ? 'active' : ''; ?>">
            <i class="fas fa-map-marker-alt"></i> 
            <span>View Areas</span>
        </a>

        <a href="view_inventory.php" class="nav-item <?php echo ($current_page == 'view_inventory.php') ? 'active' : ''; ?>">
            <i class="fas fa-boxes"></i> 
            <span>View Inventory</span>
        </a>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Administrator'): ?>
            <a class='d-none'href="create_item.php" class="nav-item <?php echo ($current_page == 'create_item.php') ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i> 
                <span>Create Item</span>
            </a>

            <a href="manage_user.php" class="nav-item <?php echo ($current_page == 'manage_user.php') ? 'active' : ''; ?>">
                <i class="fas fa-users-cog"></i> 
                <span>Manage Users</span>
            </a>
        <?php endif; ?>
    </nav>
</div>
</aside>

aaaaaaaarrr
