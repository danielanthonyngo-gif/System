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
$count_in_use = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assets WHERE status='Active'"))['total'] ?? 0;
$count_disposal = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assets WHERE status='For Disposal'"))['total'] ?? 0;
$count_replacement = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assets WHERE status='Replacement'"))['total'] ?? 0; 
$count_storage = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assets WHERE status='In Storage'"))['total'] ?? 0;

// Kalkulahin ang total at percentages sa PHP para magamit sa mga charts
$total_assets = $count_in_use + $count_disposal + $count_replacement + $count_storage;
$p_in_use = $total_assets > 0 ? round(($count_in_use / $total_assets) * 100, 1) : 0;
$p_disposal = $total_assets > 0 ? round(($count_disposal / $total_assets) * 100, 1) : 0;
$p_replacement = $total_assets > 0 ? round(($count_replacement / $total_assets) * 100, 1) : 0;
$p_storage = $total_assets > 0 ? round(($count_storage / $total_assets) * 100, 1) : 0;

// --- RECENT AUDIT LOGS FOR DASHBOARD ---
$recent_logs_query = "SELECT * FROM audit_log ORDER BY created_at DESC LIMIT 5";
$recent_logs_result = mysqli_query($conn, $recent_logs_query);

// ==========================================
// MULTI-LINE BUILDING CLIENT LOGIC
// ==========================================
function getBuildingClientDetailedData($conn, $building_id) {
    $sql = "SELECT 
                ca.client_name,
                COUNT(a.id) as total_assets,
                SUM(CASE WHEN a.status = 'Active' THEN 1 ELSE 0 END) as active_assets,
                SUM(CASE WHEN a.status = 'For Disposal' THEN 1 ELSE 0 END) as disposal_assets,
                SUM(CASE WHEN a.status = 'Replacement' THEN 1 ELSE 0 END) as replacement_assets
            FROM client_accounts ca
            LEFT JOIN assets a ON ca.account_id = a.location
            WHERE ca.building_id = '$building_id'
            GROUP BY ca.account_id, ca.client_name";
            
    $res = mysqli_query($conn, $sql);
    $labels = [];
    $active_rates = [];
    $disposal_rates = [];
    $replacement_rates = [];
    
    while($row = mysqli_fetch_assoc($res)) {
        $labels[] = $row['client_name'];
        $total = (int)$row['total_assets'];
        
        if ($total > 0) {
            $active_rates[]      = round(((int)$row['active_assets'] / $total) * 100, 1);
            $disposal_rates[]    = round(((int)$row['disposal_assets'] / $total) * 100, 1);
            $replacement_rates[] = round(((int)$row['replacement_assets'] / $total) * 100, 1);
        } else {
            $active_rates[]      = 0;
            $disposal_rates[]    = 0;
            $replacement_rates[] = 0;
        }
    }
    
    return [
        'labels'      => $labels, 
        'active'      => $active_rates, 
        'disposal'    => $disposal_rates, 
        'replacement' => $replacement_rates
    ];
}

// Alpha Building (id: 1), Beta Building (id: 2)
$alpha_line_data = getBuildingClientDetailedData($conn, 1);
$beta_line_data  = getBuildingClientDetailedData($conn, 2);
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root { 
            --main-gradient: linear-gradient(135deg, #7A1CAC 0%, #7A1CAC 100%);
            --accent-purple: #8e44ad;
            --bg-light: #f4f7fe; 
            --sidebar-width: 260px;
        }

        body { 
            background-color: var(--bg-light); 
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #362d36;
            margin: 0;
        }

        .content-wrapper {
            margin-left: var(--sidebar-width);
            padding: 35px;
            min-height: 100vh;
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

        .status-card {
            border: none;
            border-radius: 25px;
            color: white;
            padding: 30px;
            position: relative;
            overflow: hidden;
            transition: 0.3s;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            cursor: pointer;
        }

        .status-card:hover { 
            transform: translateY(-5px); 
        }

        .card-icon { font-size: 3.5rem; opacity: 0.2; position: absolute; right: -10px; bottom: -10px; }
        
        .bg-inuse { background: linear-gradient(135deg, #AD49E1 0%, #AD49E1 100%); }
        .bg-disposal { background: linear-gradient(135deg, #62109F 0%, #62109F 100%); }
        .bg-replacement { background: linear-gradient(135deg, #2E073F 0%, #2E073F 100%); }
        .bg-storage { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); }

        .chart-card {
            background: white;
            border-radius: 30px;
            padding: 35px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.02);
            height: 100%;
        }

        .badge-action {
            padding: 6px 12px; border-radius: 20px; font-weight: 700; font-size: 0.7rem;
            display: inline-block; text-align: center;
        }
        .badge-create { background: #d1fae5; color: #065f46; }
        .badge-update { background: #dbeafe; color: #1e40af; }
        .badge-delete { background: #fee2e2; color: #991b1b; }
        .badge-login { background: #fef3c7; color: #92400e; }
        .badge-lock { background: #2E073F; color: white; }
        .badge-default { background: #f3f4f6; color: #374151; }

        .table thead th { color: #a3aed0; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 15px 20px; border-bottom: 1px solid #f1f1f7; }
        .table tbody td { padding: 15px 20px; color: #2b3674; font-weight: 600; font-size: 0.85rem; vertical-align: middle; }

        @media (max-width: 992px) {
            .content-wrapper { margin-left: 0; padding: 20px; }
            .glass-header-container { padding: 20px; border-radius: 20px; }
        }
    </style>
</head>
<body>

<?php include 'aside.php';
    $title     = "Dashboard";
    $sub_title = "Asset Record & Monitoring"; ?>

<div class="content-wrapper">
  <?php include 'header.php'; ?>
    
    <div class="container-fluid p-0">
        <!-- 1. STATUS CARDS -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <a href="index_page.php?status=Active" class="text-decoration-none">
                    <div class="status-card bg-inuse">
                        <p class="mb-1 text-uppercase small fw-bold" style="letter-spacing: 1px;">In Use Assets</p>
                        <h2 class="display-6 fw-bold mb-0"><?php echo $count_in_use; ?></h2>
                        <i class="fas fa-desktop card-icon"></i>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3">
                <a href="index_page.php?status=For Disposal" class="text-decoration-none">
                    <div class="status-card bg-disposal">
                        <p class="mb-1 text-uppercase small fw-bold" style="letter-spacing: 1px;">For Disposal</p>
                        <h2 class="display-6 fw-bold mb-0"><?php echo $count_disposal; ?></h2>
                        <i class="fas fa-dumpster card-icon"></i>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3">
                <a href="index_page.php?status=Replacement" class="text-decoration-none">
                    <div class="status-card bg-replacement">
                        <p class="mb-1 text-uppercase small fw-bold" style="letter-spacing: 1px;">Replacement</p>
                        <h2 class="display-6 fw-bold mb-0"><?php echo $count_replacement; ?></h2>
                        <i class="fas fa-tools card-icon"></i>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3">
                <a href="index_page.php?status=In Storage" class="text-decoration-none">
                    <div class="status-card bg-storage">
                        <p class="mb-1 text-uppercase small fw-bold" style="letter-spacing: 1px;">In Storage</p>
                        <h2 class="display-6 fw-bold mb-0"><?php echo $count_storage; ?></h2>
                        <i class="fas fa-boxes-stacked card-icon"></i>
                    </div>
                </a>
            </div>
        </div>

        <!-- 2. PIE CHART AT RECENT ACTIVITIES (TOP) -->
        <div class="row g-4 mb-4">
            <div class="col-xl-6 col-lg-12">
                <div class="chart-card">
                    <h5 class="fw-bold mb-4" style="color: #2E073F;">Asset Distribution Breakdown</h5>
                    <div style="height: 400px;">
                        <canvas id="assetPieChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-xl-6 col-lg-12">
                <div class="chart-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold m-0" style="color: #2E073F;">Recent System Activities</h5>
                        <a href="audit.php" class="btn btn-sm btn-light rounded-pill px-3 fw-bold text-uppercase small" style="color: #7A1CAC;">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Entity</th>
                                    <th>Date & Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                while ($row = mysqli_fetch_assoc($recent_logs_result)): 
                                    $badge_class = 'badge-default';
                                    $action_lower = strtolower($row['action']);
                                    if (strpos($action_lower, 'add') !== false || strpos($action_lower, 'create') !== false) {
                                        $badge_class = 'badge-create';
                                    } elseif (strpos($action_lower, 'edit') !== false || strpos($action_lower, 'update') !== false) {
                                        $badge_class = 'badge-update';
                                    } elseif (strpos($action_lower, 'delete') !== false) {
                                        $badge_class = 'badge-delete';
                                    } elseif (strpos($action_lower, 'login') !== false) {
                                        $badge_class = 'badge-login';
                                    } elseif (strpos($action_lower, 'lock') !== false || strpos($action_lower, 'unlock') !== false) {
                                        $badge_class = 'badge-lock';
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-600 text-truncate" style="max-width: 130px;"><?php echo htmlspecialchars($row['user_fullname']); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge-action <?php echo $badge_class; ?>">
                                                <?php echo ucfirst(htmlspecialchars($row['action'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($row['entity_type']): ?>
                                                <span class="small"><strong><?php echo ucfirst(htmlspecialchars($row['entity_type'])); ?></strong></span>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="small"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></div>
                                            <small class="text-muted" style="font-size: 11px;"><?php echo date('h:i:s A', strtotime($row['created_at'])); ?></small>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                
                                <?php if(mysqli_num_rows($recent_logs_result) == 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <i class="fas fa-history fa-2x text-muted mb-2 d-block"></i>
                                            <h6 class="text-muted small">No recent activities found</h6>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. LINE GRAPHS KADA BUILDING -->
        <div class="row g-4">
            <div class="col-xl-6 col-lg-12">
                <div class="chart-card">
                    <h5 class="fw-bold mb-2" style="color: #2E073F;">Alpha Building Client Analysis</h5>
                    <small class="text-muted d-block mb-4">Percentage (%) breakdown ng mga status kada client account</small>
                    <div style="height: 320px;">
                        <canvas id="alphaLineChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-12">
                <div class="chart-card">
                    <h5 class="fw-bold mb-2" style="color: #2E073F;">Beta Building Client Analysis</h5>
                    <small class="text-muted d-block mb-4">Percentage (%) breakdown ng mga status kada client account</small>
                    <div style="height: 320px;">
                        <canvas id="betaLineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const lineChartOptionsBase = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: true, position: 'top', labels: { font: { weight: '600', size: 12 }, usePointStyle: true } },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.raw + '%';
                    }
                }
            }
        },
        scales: {
            y: { 
                beginAtZero: true, 
                max: 100, 
                ticks: { callback: function(value) { return value + '%'; } },
                grid: { color: 'rgba(0, 0, 0, 0.05)' }
            },
            x: { grid: { display: false } }
        }
    };

    // Alpha Line Chart
    const alphaLineCtx = document.getElementById('alphaLineChart').getContext('2d');
    new Chart(alphaLineCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($alpha_line_data['labels']); ?>,
            datasets: [
                {
                    label: 'In Use Rate',
                    data: <?php echo json_encode($alpha_line_data['active']); ?>,
                    borderColor: '#AD49E1',
                    backgroundColor: 'transparent',
                    borderWidth: 3,
                    pointBackgroundColor: '#AD49E1',
                    tension: 0.2
                },
                {
                    label: 'Disposal Rate',
                    data: <?php echo json_encode($alpha_line_data['disposal']); ?>,
                    borderColor: '#62109F',
                    backgroundColor: 'transparent',
                    borderWidth: 3,
                    pointBackgroundColor: '#62109F',
                    tension: 0.2
                },
                {
                    label: 'Replacement Rate',
                    data: <?php echo json_encode($alpha_line_data['replacement']); ?>,
                    borderColor: '#2E073F',
                    backgroundColor: 'transparent',
                    borderWidth: 3,
                    pointBackgroundColor: '#2E073F',
                    tension: 0.2
                }
            ]
        },
        options: lineChartOptionsBase
    });

    // Beta Line Chart
    const betaLineCtx = document.getElementById('betaLineChart').getContext('2d');
    new Chart(betaLineCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($beta_line_data['labels']); ?>,
            datasets: [
                {
                    label: 'In Use Rate',
                    data: <?php echo json_encode($beta_line_data['active']); ?>,
                    borderColor: '#AD49E1',
                    backgroundColor: 'transparent',
                    borderWidth: 3,
                    pointBackgroundColor: '#AD49E1',
                    tension: 0.2
                },
                {
                    label: 'Disposal Rate',
                    data: <?php echo json_encode($beta_line_data['disposal']); ?>,
                    borderColor: '#62109F',
                    backgroundColor: 'transparent',
                    borderWidth: 3,
                    pointBackgroundColor: '#62109F',
                    tension: 0.2
                },
                {
                    label: 'Replacement Rate',
                    data: <?php echo json_encode($beta_line_data['replacement']); ?>,
                    borderColor: '#2E073F',
                    backgroundColor: 'transparent',
                    borderWidth: 3,
                    pointBackgroundColor: '#2E073F',
                    tension: 0.2
                }
            ]
        },
        options: lineChartOptionsBase
    });

    // PIE CHART CONFIG
    const pieCtx = document.getElementById('assetPieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: [
                'In Use: <?php echo $p_in_use; ?>%', 
                'For Disposal: <?php echo $p_disposal; ?>%', 
                'Replacement: <?php echo $p_replacement; ?>%', 
                'In Storage: <?php echo $p_storage; ?>%'
            ],
            datasets: [{
                data: [
                    <?php echo $count_in_use; ?>, 
                    <?php echo $count_disposal; ?>, 
                    <?php echo $count_replacement; ?>, 
                    <?php echo $count_storage; ?>
                ],
                backgroundColor: ['#AD49E1', '#62109F', '#2E073F', '#6c757d'],
                borderWidth: 3,
                borderColor: '#ffffff',
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'bottom', 
                    labels: { 
                        padding: 20, 
                        usePointStyle: true, 
                        font: { weight: '600', size: 14 } 
                    } 
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label.split(':')[0] || '';
                            return label + ': ' + context.raw;
                        }
                    }
                }
            }
        }
    });
</script>

</body>
</html>