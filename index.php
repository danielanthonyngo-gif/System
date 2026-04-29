<?php
session_start();
include 'config.php';

if (!$conn) {
    die("<div style='color:red; padding:20px;'>Master, mali ang database settings mo: " . mysqli_connect_error() . "</div>");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$query = "SELECT fullname FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $user_data = mysqli_fetch_assoc($result);
    $display_name = $user_data['fullname'];
} else {
    $display_name = "User"; 
}

// --- ASSET TRACKING LOGIC ---
$count_in_use = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assets WHERE status='Active'"))['total'];
$count_disposal = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assets WHERE status='For Disposal'"))['total'];
$count_replacement = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assets WHERE status='Replacement'"))['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspiro | Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { 
            --main-gradient: linear-gradient(135deg, #6f42c1 0%, #d63384 100%);
            --accent-purple: #8e44ad;
            --bg-light: #f4f7fe; /* Patterned after image_d10958.png */
            --sidebar-width: 260px;
        }

        body { 
            background-color: var(--bg-light); 
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #2d3436;
            margin: 0;
        }

        .content-wrapper {
            margin-left: var(--sidebar-width);
            padding: 35px; /* Consistent margin from the edge */
            min-height: 100vh;
        }

        /* --- TOP NAV BAR (EXACT MATCH TO image_d10958.png) --- */
        .glass-header-container {
            background: white;
            border-radius: 35px; /* High rounding as seen in image */
            padding: 25px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.03);
            margin-bottom: 40px;
            width: 100%;
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
            text-align: right; /* Aligned to right as per screenshot */
        }

        .user-name-top {
            color: #8e44ad;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0;
        }

        .sign-out-link {
            color: #ff5e91; /* Pinkish color for Sign Out */
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
            border-radius: 20px; /* Smooth rounded square style */
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.4rem;
            box-shadow: 0 8px 20px rgba(142, 68, 173, 0.25);
        }

        /* --- DASHBOARD CARDS & CONTENT --- */
        .status-card {
            border: none;
            border-radius: 25px;
            color: white;
            padding: 30px;
            position: relative;
            overflow: hidden;
            transition: 0.3s;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        .status-card:hover { transform: translateY(-5px); }
        .card-icon { font-size: 3.5rem; opacity: 0.2; position: absolute; right: -10px; bottom: -10px; }
        
        .bg-inuse { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .bg-disposal { background: linear-gradient(135deg, #ff6b6b 0%, #ee5253 100%); }
        .bg-replacement { background: linear-gradient(135deg, #4834d4 0%, #686de0 100%); }

        .calendar-card {
            background: white;
            border-radius: 30px;
            padding: 35px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.02);
        }

        .current-day {
            background: var(--main-gradient);
            color: white;
            width: 40px; height: 40px;
            line-height: 40px;
            border-radius: 12px;
            font-weight: 800;
            display: inline-block;
        }

        @media (max-width: 992px) {
            .content-wrapper { margin-left: 0; padding: 20px; }
            .glass-header-container { padding: 20px; border-radius: 20px; }
        }
    </style>
</head>
<body>

<?php include 'aside.php'; ?>

<div class="content-wrapper">
    <!-- TOP NAV BAR (Patterned from image_d10958.png) -->
    <div class="glass-header-container">
        <div class="header-title-section">
            <h2>DASHBOARD</h2>
            <p>Asset Record & Monitoring</p>
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

    <!-- MAIN DASHBOARD CONTENT -->
    <div class="container-fluid p-0">
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="status-card bg-inuse">
                    <p class="mb-1 text-uppercase small fw-800" style="letter-spacing: 1px;">In Use Assets</p>
                    <h2 class="display-6 fw-800 mb-0"><?php echo $count_in_use; ?></h2>
                    <i class="fas fa-desktop card-icon"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="status-card bg-disposal">
                    <p class="mb-1 text-uppercase small fw-800" style="letter-spacing: 1px;">For Disposal</p>
                    <h2 class="display-6 fw-800 mb-0"><?php echo $count_disposal; ?></h2>
                    <i class="fas fa-dumpster card-icon"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="status-card bg-replacement">
                    <p class="mb-1 text-uppercase small fw-800" style="letter-spacing: 1px;">Replacement</p>
                    <h2 class="display-6 fw-800 mb-0"><?php echo $count_replacement; ?></h2>
                    <i class="fas fa-tools card-icon"></i>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="calendar-card">
                    <h5 class="fw-800 mb-4" style="color: #2b3674;">Inventory Calendar</h5>
                    <div class="table-responsive">
                        <table class="table table-borderless text-center align-middle">
                            <thead>
                                <tr style="color: #a3aed0; font-weight: 700; font-size: 0.85rem;">
                                    <th>MON</th><th>TUE</th><th>WED</th><th>THU</th><th>FRI</th><th>SAT</th><th>SUN</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <?php
                                    $today = date('j'); 
                                    $daysInMonth = date('t'); 
                                    $firstDayOfMonth = date('N', strtotime(date('Y-m-01'))); 

                                    for ($i = 1; $i < $firstDayOfMonth; $i++) {
                                        echo "<td></td>";
                                    }

                                    for ($day = 1; $day <= $daysInMonth; $day++) {
                                        $spanClass = ($day == $today) ? 'class="current-day"' : 'style="font-weight: 700; color: #2b3674;"';
                                        echo "<td class='p-3'><span $spanClass>$day</span></td>";

                                        if (($day + $firstDayOfMonth - 1) % 7 == 0) {
                                            echo "</tr><tr>";
                                        }
                                    }
                                    ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>