<aside>
<?php
// Aside.php - Sidebar Component
$current_page = basename($_SERVER['PHP_SELF']);
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@900&display=swap" rel="stylesheet">
<!-- Font Awesome for the hamburger icon -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    :root {
        --sidebar-gradient: linear-gradient(180deg, #2E073F 0%, #2E073F 100%);
        --sidebar-hover: rgba(255, 255, 255, 0.15);
        --accent-color: #ff9ff3; 
        --sidebar-width: 260px;
        --logo-purple: #902694; 
    }

    /* Sidebar Base */
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
        transition: transform 0.3s ease-in-out;
    }

    /* Brand Section */
    .brand-section {
        padding: 45px 10px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .logo-container {
        font-family: 'Outfit', sans-serif; 
        font-size: 3.2rem; 
        font-weight: 900;
        letter-spacing: 1px; 
        color: #FFFFFF;
        text-transform: lowercase;
        display: flex;
        align-items: baseline;
        line-height: 1;
        user-select: none;
    }

    .logo-r-custom {
        display: inline-block;
        position: relative;
        color: var(--logo-purple);
        margin-left: 5px; 
        margin-right: 15px; 
    }

    .logo-r-custom::after {
        content: '';
        position: absolute;
        top: 18px; 
        right: -18px; 
        width: 14px;
        height: 14px;
        background-color: var(--logo-purple);
        border-radius: 50%;
    }

    .logo-o { margin-left: 10px; }
    .stem { display: inline-block; line-height: 1; }

    /* Nav Menu */
    .nav-menu { padding-top: 10px; }
    .nav-item {
        padding: 15px 25px;
        display: flex;
        align-items: center;
        color: rgba(241, 234, 241, 0.9) !important;
        text-decoration: none !important;
        transition: 0.3s;
        border-left: 5px solid transparent;
        font-size: 1.1rem;
    }
    .nav-item i { margin-right: 15px; width: 25px; text-align: center; }
    .nav-item:hover { background: var(--sidebar-hover); color: white !important; }
    .nav-item.active {
        background: rgba(255, 255, 255, 0.1);
        border-left: 5px solid var(--accent-color);
    }

    /* Content Wrapper */
    .content-wrapper, .main-content { 
        margin-left: var(--sidebar-width); 
        transition: margin 0.3s ease;
    }

    /* Mobile Toggle Button */
    .mobile-toggle {
        display: none;
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1100;
        background: #2E073F;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 8px;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }

    /* Responsive Queries */
    @media (max-width: 992px) {
        .mobile-toggle { display: block; }
        
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .content-wrapper, .main-content {
            margin-left: 0 !important;
        }

        /* Overlay when sidebar is open */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .sidebar-overlay.active { display: block; }
    }
</style>

<!-- Hamburger Button -->
<button class="mobile-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<!-- Overlay for clicking outside sidebar -->
<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

<div class="sidebar" id="sidebar">
    <div class="brand-section">
        <div class="logo-container">
            <span>inspi</span><span class="logo-r-custom"><span class="stem">ı</span></span><span class="logo-o">o</span>
        </div>
    </div>

    <nav class="nav-menu">
        <a href="index.php" class="nav-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i> <span>Dashboard</span>
        </a>
        <a href="view_area.php" class="nav-item <?php echo ($current_page == 'view_area.php') ? 'active' : ''; ?>">
            <i class="fas fa-map-marker-alt"></i> <span>View Areas</span>
        </a>
        <a href="view_inventory.php" class="nav-item <?php echo ($current_page == 'view_inventory.php') ? 'active' : ''; ?>">
            <i class="fas fa-boxes"></i> <span>Inventory</span>
        </a>
        <a href="audit.php" class="nav-item <?php echo ($current_page == 'audit.php') ? 'active' : ''; ?>">
            <i class="fas fa-history"></i> <span>Audit Log</span>
        </a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Administrator'): ?>
            <a href="manage_user.php" class="nav-item <?php echo ($current_page == 'manage_user.php') ? 'active' : ''; ?>">
                <i class="fas fa-users-cog"></i> <span>Manage Users</span>
            </a>
        <?php endif; ?>
    </nav>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}
</script>
</aside>