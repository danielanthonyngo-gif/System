<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. KUNIN ANG LOCATION AT STATUS
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

// 2. DINAMIKONG PAGBILANG
$count_query = mysqli_query($conn, "SELECT COUNT(a.id) as total FROM assets a $where_sql");
$asset_count = mysqli_fetch_assoc($count_query)['total'] ?? 0;

$desktop_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(a.id) as total FROM assets a $where_sql AND a.asset_type LIKE '%desktop%'"))['total'] ?? 0;
$laptop_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(a.id) as total FROM assets a $where_sql AND a.asset_type LIKE '%laptop%'"))['total'] ?? 0;
$monitor_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(a.id) as total FROM assets a $where_sql AND a.asset_type LIKE '%monitor%'"))['total'] ?? 0;

// 3. PAGKUHA NG TABLE DATA
$assets = mysqli_query($conn, "SELECT a.*, COALESCE(acc.client_name, a.location) as display_client_name FROM assets a LEFT JOIN client_accounts acc ON a.location = acc.account_id $where_sql ORDER BY a.id DESC");

$icon_class = ($current_status == 'For Disposal') ? 'fa-trash-alt' : (($current_status == 'Replacement') ? 'fa-sync-alt' : (($current_status == 'In Storage') ? 'fa-box' : 'fa-desktop'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Asset Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --main-purple: #7A1CAC; --app-bg: #f3f4f9; }
        body { background-color: var(--app-bg); font-family: 'Plus Jakarta Sans', sans-serif; }
        .content-wrapper { padding: 40px; }
        
        .single-stat-card {
            background: white; border-radius: 20px; padding: 20px;
            display: flex; align-items: center; gap: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #edf2f7; height: 100%;
        }
        .icon-square {
            width: 50px; height: 50px; border-radius: 15px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.2rem; background: var(--main-purple);
        }
        .stat-label { color: #a3aed0; font-weight: 600; text-transform: uppercase; font-size: 0.65rem; margin: 0; }
        .stat-number { margin: 0; font-weight: 700; color: #1e293b; font-size: 1.6rem; }
        .table-card { background: white; border-radius: 24px; padding: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.01); margin-top: 30px; }
        
        /* SEARCH BAR FIX */
        .search-container { position: relative; width: 100%; }
        .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #b4b6c4; }
        .search-bar { padding: 12px 20px 12px 45px; border-radius: 14px; border: 1px solid #e2e8f0; width: 100%; }
        
        .custom-table thead th { color: var(--main-purple); font-size: 0.75rem; text-transform: uppercase; padding: 16px; border-bottom: 2px solid #edf2f7; }
        .badge-location { background: #f5f3ff; color: var(--main-purple); padding: 5px 12px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; }
    </style>
</head>
<body>

<div class="content-wrapper container-fluid">
    <div class="row g-3 mb-4 justify-content-center">
        <?php 
        $stats = [
            ['label' => "$current_status Assets", 'count' => $asset_count, 'icon' => $icon_class],
            ['label' => 'Desktop Assets', 'count' => $desktop_count, 'icon' => 'fa-computer'],
            ['label' => 'Laptop Assets', 'count' => $laptop_count, 'icon' => 'fa-laptop'],
            ['label' => 'Monitor Assets', 'count' => $monitor_count, 'icon' => 'fa-display']
        ];
        foreach ($stats as $stat): ?>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="single-stat-card">
                    <div class="icon-square"><i class="fas <?php echo $stat['icon']; ?>"></i></div>
                    <div>
                        <p class="stat-label"><?php echo $stat['label']; ?></p>
                        <h2 class="stat-number"><?php echo $stat['count']; ?></h2>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="table-card">
        <div class="search-container mb-3">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="assetSearch" class="search-bar" placeholder="Search ID, tag, serial, or model...">
        </div>

        <div class="table-responsive">
            <table class="table custom-table align-middle" id="assetTable">
                <thead>
                    <tr><th>ID</th><th>Tag</th><th>Serial</th><th>Model</th><th>Location</th><th>Status</th></tr>
                </thead>
                <tbody id="assetTableBody">
                    <?php while($row = mysqli_fetch_assoc($assets)): ?>
                    <tr class="asset-row">
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['asset_tag'] ?? '—'; ?></td>
                        <td><?php echo $row['serial_number'] ?? '—'; ?></td>
                        <td><?php echo $row['brand_model'] ?? '—'; ?></td>
                        <td><span class="badge-location"><?php echo $row['display_client_name']; ?></span></td>
                        <td><?php echo $row['status']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.getElementById('assetSearch').addEventListener('input', function() {
        let query = this.value.toLowerCase().trim();
        let rows = document.querySelectorAll('.asset-row');
        
        rows.forEach(row => {
            // Sinisiguro na mase-search ang lahat ng columns sa row
            let rowText = row.textContent.toLowerCase();
            row.style.display = rowText.includes(query) ? "" : "none";
        });
    });
</script>
</body>
</html>