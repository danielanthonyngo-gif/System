<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. KUNIN ANG LOCATION AT 
// 
// 
// 
// US MULA SA URL
$location_id = isset($_GET['location']) ? mysqli_real_escape_string($conn, $_GET['location']) : '';
$current_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'Active';

$client_name = "SYSTEM";
$where_clauses = array();

$where_clauses[] = "a.status = '$current_status'";

if (!empty($location_id)) {
    $client_query = mysqli_query($conn, "SELECT account_id, client_name FROM client_accounts WHERE account_id = '$location_id' OR client_name = '$location_id'");
    if ($c_row = mysqli_fetch_assoc($client_query)) {
        $client_name = strtoupper($c_row['client_name']);
        $db_location_filter = $c_row['account_id'];
    } else {
        $db_location_filter = $location_id;
        $client_name = strtoupper($location_id);
    }
    $where_clauses[] = "(a.location = '$db_location_filter' OR a.location = '$location_id')";
} else {
    $client_name = "ALL AREAS";
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// 2. DINAMIKONG BILANG NG MGA ASSETS (TOTAL BASE SA STATUS)
$count_query = mysqli_query($conn, "SELECT COUNT(a.id) as total FROM assets a $where_sql");
$asset_count = mysqli_fetch_assoc($count_query)['total'] ?? 0;

// --- DITO NATIN DIREKTANG TINARGET ANG ASSET_TYPE COLUMN SA DATABASE ---
// Gumamit ako ng LIKE '%...%' para kahit may spaces o iba ang capitalization (Desktop, desktop, DESKTOP) ay mabibilang pa rin.
$desktop_query = mysqli_query($conn, "SELECT COUNT(a.id) as total FROM assets a $where_sql AND a.asset_type LIKE '%desktop%'");
$desktop_count = ($desktop_query) ? (mysqli_fetch_assoc($desktop_query)['total'] ?? 0) : 0;

$laptop_query = mysqli_query($conn, "SELECT COUNT(a.id) as total FROM assets a $where_sql AND a.asset_type LIKE '%laptop%'");
$laptop_count = ($laptop_query) ? (mysqli_fetch_assoc($laptop_query)['total'] ?? 0) : 0;

$monitor_query = mysqli_query($conn, "SELECT COUNT(a.id) as total FROM assets a $where_sql AND a.asset_type LIKE '%monitor%'");
$monitor_count = ($monitor_query) ? (mysqli_fetch_assoc($monitor_query)['total'] ?? 0) : 0;


// 3. KUNIN ANG DETALYE NG MGA ASSETS PARA SA TABLE
$assets = mysqli_query($conn, "
    SELECT a.*, COALESCE(acc.client_name, a.location) as display_client_name 
    FROM assets a 
    LEFT JOIN client_accounts acc ON a.location = acc.account_id 
    $where_sql
    ORDER BY a.id DESC
");

// --- DINAMIKONG PAGSASAAYOS NG PAMAGAT BASE SA STATUS NG CONTAINER ---
switch ($current_status) {
    case 'Active':
        $dynamic_title = "IN USE ASSETS";
        break;
    case 'For Disposal':
        $dynamic_title = "FOR DISPOSAL";
        break;
    case 'Replacement':
        $dynamic_title = "REPLACEMENT";
        break;
    case 'In Storage':
        $dynamic_title = "IN STORAGE";
        break;
    default:
        $dynamic_title = strtoupper($current_status);
        break;
}

$title        = $dynamic_title;
$sub_title    = "Asset Management System (" . htmlspecialchars($client_name) . ")";
$display_name = $_SESSION['user_full_name'] ?? 'Daniel'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($client_name); ?> - Asset Management System</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root { 
            --app-bg: #f3f4f9;
            --main-purple: #7A1CAC;
            --sidebar-width: 260px;
            --dark-purple: #2E073F;
            --main-gradient: linear-gradient(135deg, #7A1CAC 0%, #7A1CAC 100%);
            --accent-purple: #8e44ad;
        }

        body { 
            background-color: var(--app-bg); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: #2d3436;
            margin: 0;
            overflow-x: hidden;
        }
        
        .content-wrapper { 
            margin-left: var(--sidebar-width); 
            padding: 35px; 
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .glass-header-container {
            background: white;
            border-radius: 35px;
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

        .user-info-text { text-align: right; }

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

        .profile-avatar-pill {
            width: 55px; height: 55px;
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

        /* PARE-PAREHONG STAT CARD STYLES (EXACTLY THE SAME) */
        .single-stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.01);
            border: 1px solid #edf2f7;
            width: 100%;
        }
        
        .icon-square {
            width: 48px; 
            height: 48px; 
            border-radius: 12px;
            display: flex; 
            align-items: center; 
            justify-content: center;
            color: white; 
            font-size: 1.25rem;
            background: #AD49E1; 
        }
        
        .stat-label {
            color: #a3aed0;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.65rem; 
            letter-spacing: 0.5px;
            margin: 0;
        }
        
        .stat-number {
            margin: 0; 
            font-weight: 700; 
            color: #1e293b; 
            letter-spacing: -1px;
            font-size: 1.8rem;
        }

        /* TABLE CARD STYLES */
        .table-card {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.01);
            border: 1px solid #edf2f7;
        }
        .search-container { position: relative; width: 100%; }
        .search-bar {
            padding: 12px 20px 12px 45px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: #f8f9fa;
            width: 100%;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .search-bar:focus {
            outline: none;
            border-color: var(--main-purple);
            background: white;
            box-shadow: 0 0 0 3px rgba(122, 28, 172, 0.1);
        }

        .custom-table thead th {
            color: var(--main-purple); font-size: 0.75rem; text-transform: uppercase;
            letter-spacing: 0.8px; font-weight: 800; padding: 16px;
            border-bottom: 2px solid #edf2f7;
        }
        .custom-table tbody td { padding: 16px; border-bottom: 1px solid #f7fafc; }
        .badge-location {
            background: #f5f3ff; color: var(--main-purple); border-radius: 6px;
            padding: 5px 12px; font-weight: 800; font-size: 0.75rem;
        }

        @media (max-width: 991.98px) {
            .content-wrapper { margin-left: 0; padding: 20px; }
            .table-card { padding: 1.25rem; border-radius: 16px; }
            .glass-header-container { padding: 20px; border-radius: 20px; margin-bottom: 30px; }
        }
    </style>
</head>
<body>

    <?php include 'aside.php'; ?>
    
    <div class="content-wrapper">
        
        <?php include 'header.php'; ?>

        <div class="row mb-4 g-3">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="single-stat-card">
                    <?php 
                        $icon_class = "fa-desktop"; 
                        if ($current_status == 'For Disposal') { $icon_class = "fa-trash-alt"; }
                        if ($current_status == 'Replacement') { $icon_class = "fa-sync-alt"; }
                        if ($current_status == 'In Storage') { $icon_class = "fa-box"; }
                    ?>
                    <div class="icon-square">
                        <i class="fas <?php echo $icon_class; ?>"></i>
                    </div>
                    <div>
                        <p class="stat-label"><?php echo htmlspecialchars($current_status); ?> Assets</p>
                        <h2 class="stat-number"><?php echo $asset_count; ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <div class="single-stat-card">
                    <div class="icon-square">
                        <i class="fas fa-computer"></i>
                    </div>
                    <div>
                        <p class="stat-label">Desktop Assets</p>
                        <h2 class="stat-number"><?php echo $desktop_count; ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <div class="single-stat-card">
                    <div class="icon-square">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <div>
                        <p class="stat-label">Laptop Assets</p>
                        <h2 class="stat-number"><?php echo $laptop_count; ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <div class="single-stat-card">
                    <div class="icon-square">
                        <i class="fas fa-display"></i>
                    </div>
                    <div>
                        <p class="stat-label">Monitor Assets</p>
                        <h2 class="stat-number"><?php echo $monitor_count; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="row mb-4 align-items-center">
                <div class="col-12 col-md-6 col-lg-5 d-flex gap-3">
                    <a href="index.php" class="btn d-inline-flex align-items-center justify-content-center" style="background: white; border: 1px solid #e2e8f0; border-radius: 14px; padding: 12px 18px; color: var(--main-purple); font-weight: 700; font-size: 0.95rem; transition: all 0.2s; gap: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);" onmouseover="this.style.background='#f8f9fa'; this.style.borderColor='var(--main-purple)';" onmouseout="this.style.background='white'; this.style.borderColor='#e2e8f0';">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    
                    <div class="search-container">
                        <i class="fas fa-search position-absolute" style="left: 18px; top: 15px; color: #b4b6c4;"></i>
                        <input type="text" id="assetSearch" class="search-bar" placeholder="Search ID, tag, serial, or model...">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table custom-table align-middle" id="assetTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Inventory Date</th>
                            <th>Asset Tag</th>
                            <th>Serial Number</th>
                            <th>Brand & Model</th>

                            <th>Location</th>
                            <th>Status</th>
                            <th>Date Created</th>
                        </tr>
                    </thead>
                    <tbody id="assetTableBody">
                        <?php if($assets && mysqli_num_rows($assets) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($assets)): ?>
                            <tr class="asset-row">
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['inventory_date'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($row['asset_tag'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($row['serial_number'] ?? '—'); ?></td>
                               
                                <td><?php echo htmlspecialchars($row['brand_model'] ?? '—'); ?></td>
                                <td><span class="badge-location"><?php echo htmlspecialchars($row['display_client_name']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">No assets found for this status.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.getElementById('assetSearch').addEventListener('input', function() {
            let query = this.value.toLowerCase().trim();
            let rows = document.querySelectorAll('#assetTableBody .asset-row');

            rows.forEach(function(row) {
                // Kinukuha nito ang lahat ng text content sa loob ng <tr> (lahat ng td kasama ang serial)
                let text = row.textContent.toLowerCase();
                
                if(text.includes(query)) {
                    row.style.display = ""; // Ipakita ang row kung tugma
                } else {
                    row.style.display = "none"; // Itago kung hindi tugma
                }
            });
        });
    </script>
</body>
</html>