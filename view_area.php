<?php
session_start(); 
include 'config.php';

$display_name = "Guest"; 
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_query = mysqli_query($conn, "SELECT fullname FROM users WHERE id = '$user_id' LIMIT 1");
    if ($row = mysqli_fetch_assoc($user_query)) {
        $display_name = $row['fullname'];
    }
}

$alpha_areas = [
    ['name' => 'BDO'], ['name' => 'BDO Insure'], ['name' => 'BDO Life'], 
    ['name' => 'Pacsan'], ['name' => 'BDO Core'], ['name' => 'Flight Center']
];
$beta_areas = [
    ['name' => 'Grab Support'], ['name' => 'Grab COE'], ['name' => 'Shark Ninja'], 
    ['name' => 'Hallmark'], ['name' => 'ANA'], ['name' => 'AUB']
];
$other_areas = [
    ['name' => "Manila Doctor's Hospital"], ['name' => 'Ignite'], ['name' => 'Viagogo']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspiro | View Areas</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --app-bg: #f4f7fe; /* Patterned after image_d10958.png */
            --main-gradient: linear-gradient(135deg, #7A1CAC 0%, #7A1CAC 100%);
            --accent-purple: #8e44ad;
            --text-main: #2d3436;
            --sidebar-width: 260px;
        }

        body {
            background-color: var(--app-bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            margin: 0;
        }

        .content { 
            margin-left: var(--sidebar-width); 
            padding: 35px; /* Consistent spacing */
        }

        /* --- TOP NAV BAR (EXACT MATCH TO DASHBOARD STYLE) --- */
        .glass-header-container {
            background: white;
            border-radius: 35px; /* High rounding from image */
            padding: 25px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.03);
            margin-bottom: 40px;
        }

        .header-title-section h2 {
            color: var(--accent-purple);
            font-weight: 700;
            font-size: 1.6rem;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header-title-section p {
            color: #a3aed0;
            margin: 0;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .user-nav-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info-text {
            text-align: right;
        }

        .user-name-top {
            color: #2E073F;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0;
        }

        .sign-out-link {
            color: #AD49E1;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: 0.2s;
        }

        .sign-out-link:hover { opacity: 0.7; }

        .profile-avatar-pill {
            width: 55px;
            height: 55px;
            background: var(--main-gradient);
            color: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.4rem;
            box-shadow: 0 8px 20px rgba(142, 68, 173, 0.25);
        }

        /* --- AREA CARDS --- */
        .section-title {
            font-weight: 800;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            color: var(--accent-purple);
            display: flex;
            align-items: center;
            gap: 15px;
            letter-spacing: 1px;
        }
        .section-title::after {
            content: "";
            flex-grow: 1;
            height: 2px;
            background: linear-gradient(90deg, #e2e8f0, transparent);
        }

        .area-card {
            background: white;
            border-radius: 28px;
            border: none;
            box-shadow: 0 10px 25px rgba(0,0,0,0.03);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            overflow: hidden;
            height: 100%;
            position: relative;
        }

        .area-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(111, 66, 193, 0.15);
        }

        .card-header-label {
            background: #fcfaff;
            padding: 15px;
            font-size: 0.75rem;
            font-weight: 800;
            color: #3b1845;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #f1f0f7;
        }

        .pc-icon-wrapper {
            padding: 25px 0;
            font-size: 2.5rem;
            background: var(--main-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #ffffff;
            padding-bottom: 10px;
        }

        .stat-box { padding: 10px 5px; }
        .stat-box h5 { margin: 0; font-weight: 800; color: #1e293b; font-size: 1.2rem; }
        .stat-box small { font-size: 0.6rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; }

        @media (max-width: 992px) {
            .content { margin-left: 0; padding: 20px; }
            .glass-header-container { padding: 20px; border-radius: 20px; }
        }
    </style>
</head>
<body>

<?php include 'aside.php'; ?>

<div class="content">
    <!-- TOP NAV BAR (Matching image_d10958.png style) -->
    <div class="glass-header-container">
        <div class="header-title-section">
            <h2>VIEW AREAS</h2>
            <p>Location Management & Monitoring</p>
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

    <!-- MAIN CONTENT -->
    <div class="section-title">ALPHA BUILDING & OTHERS</div>
    <div class="row g-4 mb-5">
        <?php 
        foreach(array_merge($alpha_areas, $other_areas) as $area): 
            $name = $area['name'];
            $safe_name = mysqli_real_escape_string($conn, $name);
            $res = mysqli_query($conn, "SELECT COUNT(*) as t FROM assets WHERE location = '$safe_name'");
            $count = mysqli_fetch_assoc($res)['t'] ?? 0;
        ?>
        <div class="col-xl-2 col-lg-3 col-md-4 col-6">
            <a href="inventory_page.php?location=<?php echo urlencode($name); ?>" class="text-decoration-none">
                <div class="area-card text-center">
                    <div class="card-header-label"><?php echo $name; ?></div>
                    <div class="pc-icon-wrapper">
                        <i class="fas fa-desktop"></i>
                    </div>
                    <div class="stat-container">
                        <div class="stat-box border-end">
                            <h5 style="color: #6f42c1;"><?php echo $count; ?></h5>
                            <small>In Use</small>
                        </div>
                        <div class="stat-box">
                            <h5>0</h5>
                            <small>Avail</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="section-title">BETA BUILDING</div>
    <div class="row g-4 mb-4">
        <?php foreach($beta_areas as $area): 
            $name = $area['name'];
            $safe_name = mysqli_real_escape_string($conn, $name);
            $res = mysqli_query($conn, "SELECT COUNT(*) as t FROM assets WHERE location = '$safe_name'");
            $count = mysqli_fetch_assoc($res)['t'] ?? 0;
        ?>
        <div class="col-xl-2 col-lg-3 col-md-4 col-6">
            <a href="inventory_page.php?location=<?php echo urlencode($name); ?>" class="text-decoration-none">
                <div class="area-card text-center">
                    <div class="card-header-label"><?php echo $name; ?></div>
                    <div class="pc-icon-wrapper">
                        <i class="fas fa-desktop"></i>
                    </div>
                    <div class="stat-container">
                        <div class="stat-box border-end">
                            <h5 style="color: #d63384;"><?php echo $count; ?></h5>
                            <small>In Use</small>
                        </div>
                        <div class="stat-box">
                            <h5>0</h5>
                            <small>Avail</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>