<aside class="sidebar">
<?php
// Aside.php - Sidebar Component
$current_page = basename($_SERVER['PHP_SELF']);
?>

<link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@700&display=swap" rel="stylesheet">

<style>
    :root {
        /* Sidebar Gradient Background */
        --sidebar-gradient: linear-gradient(180deg, #2E073F 0%, #2E073F 100%);
        --sidebar-hover: rgba(255, 255, 255, 0.15);
        --accent-color: #ff9ff3; 
        --sidebar-width: 260px;
        
        /* Logo Colors from Image */
        --logo-black: #F3F4F4;
        --logo-purple: #AD49E1; 
        --brand-bg: #2E073F; 
    }

    /* Sidebar Main Style */
    .sidebar {
        width: var(--sidebar-width);
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        background: var(--sidebar-gradient);
        color: white;
        z-index: 1000;
        box-shadow: 4px 0 15px rgba(0,0,0,0.3);
        transition: all 0.3s ease;
    }

    /* Brand Section - Ginayang white background gaya ng image */
    .brand-section {
        padding: 30px 20px;
        background: var(--brand-bg); 
        display: flex;
        align-items: center;
        justify-content: center;
        border-bottom: 1px solid rgba(0,0,0,0.1);
    }

    /* Logo Styling */
    .logo-container {
        display: flex;
        align-items: baseline;
        font-family: 'Ubuntu', sans-serif; /* Rounded font style */
        font-size: 2.7rem;
        font-weight: 700;
        letter-spacing: -2.5px; /* Siksik na mga letra gaya ng sa image */
        color: var(--logo-black);
        text-transform: lowercase;
        line-height: 1;
        user-select: none;
    }

    /* Purple color for the 'r' */
    .logo-r {
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
        color: rgba(241, 234, 241, 0.9) !important;
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

    /* Hover and Active States */
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

    /* Content Adjustment for pages using this sidebar */
    .content-wrapper, .content {
        margin-left: var(--sidebar-width);
        transition: all 0.3s ease;
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
        .sidebar { left: -260px; }
        .content-wrapper, .content { margin-left: 0; }
    }
</style>

<div class="sidebar">
    <div class="brand-section">
        <div class="logo-container">
            inspi<span class="logo-r">r</span>o
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
            <a class='d-none' href="create_item.php" class="nav-item <?php echo ($current_page == 'create_item.php') ? 'active' : ''; ?>">
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