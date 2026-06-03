<?php
    ob_start();
    session_start();
    include 'config.php';

    if (! isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
    }

    $current_uid = $_SESSION['user_id'];
    $user_res    = mysqli_query($conn, "SELECT fullname, role FROM users WHERE id = '$current_uid'");
    $user_data   = mysqli_fetch_assoc($user_res);

    $_SESSION['fullname'] = $user_data['fullname'];  

    $display_name = $user_data['fullname'] ?? "Angelo Vicente";
    $user_role    = $user_data['role'] ?? "OJT";

    // Initialize filter variables

    $search        = $_GET['search'] ?? '';
    $filter_status = $_GET['status_filter'] ?? '';
    $filter_type   = $_GET['type_filter'] ?? '';

    // Fetch Client Accounts for Dropdown
    $client_accounts_query = mysqli_query($conn, "SELECT account_id, client_name FROM client_accounts ORDER BY client_name ASC");
    $client_accounts       = [];
    while ($ca = mysqli_fetch_assoc($client_accounts_query)) {
        $client_accounts[] = $ca;
    }

    // ==========================================
// UPDATE ASSET
// ==========================================
if (isset($_POST['update_asset'])) {
    $asset_id = mysqli_real_escape_string($conn, $_POST['asset_id']);
    $tag      = mysqli_real_escape_string($conn, $_POST['asset_tag']);
    $serial   = mysqli_real_escape_string($conn, $_POST['serial_number']);
    $model    = mysqli_real_escape_string($conn, $_POST['brand_model']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $status   = mysqli_real_escape_string($conn, $_POST['status']);

    // Check duplicate Asset TAG (exclude current asset)
    $check_tag = mysqli_query($conn, "SELECT id FROM assets_temp WHERE asset_tag = '$tag' AND id != '$asset_id'");
    if (mysqli_num_rows($check_tag) > 0) {
        header("Location: view_inventory.php?msg=error_duplicate_tag");
        exit();
    }

    // Check duplicate SERIAL NUMBER (exclude current asset)
    $check_serial = mysqli_query($conn, "SELECT id FROM assets_temp WHERE serial_number = '$serial' AND id != '$asset_id'");
    if (mysqli_num_rows($check_serial) > 0) {
        header("Location: view_inventory.php?msg=error_duplicate_serial");
        exit();
    }

    $old_query = mysqli_query($conn, "SELECT * FROM assets_temp WHERE id='$asset_id'");
    $old_data  = mysqli_fetch_assoc($old_query);

    $update_query = "UPDATE assets_temp SET asset_tag='$tag', serial_number='$serial', brand_model='$model', location='$location', status='$status' WHERE id='$asset_id'";

    if (mysqli_query($conn, $update_query)) {
        logAudit($conn, 'UPDATE_ASSET', 'asset', $asset_id, $old_data, [
            'asset_tag'     => $tag,
            'serial_number' => $serial,
            'brand_model'   => $model,
            'location'      => $location,
            'status'        => $status,
        ]);
        header("Location: view_inventory.php?msg=success_update");
        exit();
    }
}

// ==========================================
// SAVE NEW ASSET
// ==========================================
if (isset($_POST['save_asset'])) {
    $serial    = mysqli_real_escape_string($conn, $_POST['serial_number']);
    $model     = mysqli_real_escape_string($conn, $_POST['brand_model']);
    $type      = mysqli_real_escape_string($conn, $_POST['type']);
    $loc       = mysqli_real_escape_string($conn, $_POST['location']);
    $date      = mysqli_real_escape_string($conn, $_POST['date']);
    $status    = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Generate or use manual asset tag
    $asset_tag = ! empty($_POST['manual_tag']) 
        ? mysqli_real_escape_string($conn, $_POST['manual_tag']) 
        : "AST-" . strtoupper(substr($type, 0, 1)) . "-" . rand(1000, 9999);

    // Check duplicate Asset TAG
    $check_tag = mysqli_query($conn, "SELECT id FROM assets_temp WHERE asset_tag = '$asset_tag'");
    if (mysqli_num_rows($check_tag) > 0) {
        header("Location: view_inventory.php?msg=error_duplicate_tag");
        exit();
    }

    // Check duplicate SERIAL NUMBER
    $check_serial = mysqli_query($conn, "SELECT id FROM assets_temp WHERE serial_number = '$serial'");
    if (mysqli_num_rows($check_serial) > 0) {
        header("Location: view_inventory.php?msg=error_duplicate_serial");
        exit();
    }

    $insert = "INSERT INTO assets_temp (inventory_date, asset_tag, serial_number, brand_model, asset_type, location, status) VALUES ('$date', '$asset_tag', '$serial', '$model', '$type', '$loc', '$status')";

    if (mysqli_query($conn, $insert)) {
        $new_asset_id = mysqli_insert_id($conn);

        logAudit($conn, 'ADD_ASSET', 'asset', $new_asset_id, null, [
            'inventory_date' => $date,
            'asset_tag'      => $asset_tag,
            'serial_number'  => $serial,
            'brand_model'    => $model,
            'asset_type'     => $type,
            'location'       => $loc,
            'status'         => $status,
        ]);
        header("Location: view_inventory.php?msg=success_create");
        exit();
    }
}

    // for delete action
    if (isset($_GET['delete_id'])) {
        $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);

        $delete_query = mysqli_query($conn, "SELECT * FROM assets_temp WHERE id='$delete_id'");
        $asset_data   = mysqli_fetch_assoc($delete_query);

        if (mysqli_query($conn, "DELETE FROM assets_temp WHERE id='$delete_id'")) {

            logAudit($conn, 'DELETE_ASSET', 'asset', $delete_id, $asset_data, null);
            header("Location: view_inventory.php?msg=success_delete");
            exit();
        }
    }

    $count_replacement = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assets_temp WHERE status='Replacement'"))['total'] ?? 0;
    $count_disposal    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assets_temp WHERE status='For Disposal'"))['total'] ?? 0;
    $count_active      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assets_temp WHERE status='Active'"))['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspiro | Computer Asset Tracking</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>

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

        /* Metric Cards */
        .metric-card { background: white; border-radius: 18px; padding: 20px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .metric-val { font-size: 1.5rem; font-weight: 800; }

        /* Data Panel */
        .data-panel { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }

        /* PURPLE FILTER DROPDOWN */
        .filter-dropdown .btn-filter {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 10px 18px;
            font-weight: 600;
            color: #555;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .filter-dropdown .dropdown-menu {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 8px;
            min-width: 200px;
        }
        .filter-dropdown .dropdown-header {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 800;
            color: #adb5bd;
            padding: 10px 15px
        }
        .filter-dropdown .dropdown-item {
            border-radius: 8px;
            padding: 10px 15px;
            font-weight: 600;
            color: #444;
            font-size: 0.9rem;
            margin-bottom: 2px;
        }
        .filter-dropdown .dropdown-item:hover,
        .filter-dropdown .dropdown-item.active {
            background-color: var(--inspiro-purple) !important;
            color: white !important;
        }
        .filter-dropdown .dropdown-divider { margin: 8px 0; border-top: 1px solid #f1f1f1; }

        /* Table & Badges */
        .status-badge { padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.65rem; text-transform: uppercase; }
        .st-active { background: #E9D5FF; color: #7A1CAC; }
        .st-disposal { background: #FEE2E2; color: #DC2626; }
        .st-replacement { background: #FEF3C7; color: #D97706; }

        /* Form Inputs */
        .input-custom { border-radius: 12px; padding: 12px 15px; border: 1.5px solid #eee; background: #fafafa; font-weight: 600; font-size: 0.9rem; width: 100%; transition: 0.3s; }
        .input-custom:focus { border-color: var(--inspiro-purple); outline: none; background: #fff; }
        .form-label-custom { font-weight: 700; color: #666; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 6px; display: block; }
    /* Metric Cards Layout */
.metric-card { 
    border-radius: 18px; 
    padding: 20px; 
    border: none; 
    box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
    display: flex; 
    justify-content: space-between; 
    align-items: center;
    transition: transform 0.2s;
}

/* Active Style (Purple/Violet) */
.card-active { background: #F3E5F5; color: #7A1CAC; }
.card-active .metric-val { color: #7A1CAC; }

/* Replacement Style (Orange/Yellow) */
.card-replacement { background: #FFF3E0; color: #EF6C00; }
.card-replacement .metric-val { color: #EF6C00; }

/* Disposal Style (Red/Light-Red) */
.card-disposal { background: #FFEBEE; color: #C62828; }
.card-disposal .metric-val { color: #C62828; }
    </style>
</head>
<body>

<?php include 'aside.php';
    $title     = "Temporary Assets";
    $sub_title = "Asset Tracking System"; ?>

<div class="content-wrapper">
     <?php include 'header.php'; ?>

    <div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="metric-card card-active">
            <span class="fw-bold small">ACTIVE</span>
            <span class="metric-val fw-bolder"><?php echo $count_active; ?></span>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="metric-card card-replacement">
            <span class="fw-bold small">REPLACEMENT</span>
            <span class="metric-val fw-bolder"><?php echo $count_replacement; ?></span>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="metric-card card-disposal">
            <span class="fw-bold small">FOR DISPOSAL</span>
            <span class="metric-val fw-bolder"><?php echo $count_disposal; ?></span>
        </div>
    </div>
</div>
    <div class="data-panel">
        <div class="row g-3 mb-4 align-items-center">
            <div class="col-md-7">
                <form method="GET" id="filterForm" class="d-flex gap-3">
                    <div class="position-relative">
                        <input type="text" name="search" class="form-control border-0 bg-light p-3 ps-5 rounded-4 shadow-sm"
                            placeholder="Search Tag, Model, or Serial..." value="<?php echo htmlspecialchars($search); ?>">

                        <button type="button" class="btn btn-light position-absolute top-50 end-0 translate-middle-y me-2 rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 38px; height: 38px; background: #f0f0f0; border: none; cursor: pointer;"
                                id="qrScanBtn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="3" width="20" height="18" rx="2" ry="2"></rect>
                                <line x1="8" y1="9" x2="16" y2="9"></line>
                                <line x1="8" y1="13" x2="16" y2="13"></line>
                                <line x1="8" y1="17" x2="13" y2="17"></line>
                            </svg>
                        </button>
                    </div>

                    <input type="hidden" name="status_filter" id="status_filter_input" value="<?php echo $filter_status; ?>">
                    <input type="hidden" name="type_filter" id="type_filter_input" value="<?php echo $filter_type; ?>">

                    <div class="dropdown filter-dropdown">
                        <button class="btn btn-filter dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-filter text-primary"></i>
                            Filter:<?php
                                       if (! empty($filter_status)) {
                                           echo $filter_status;
                                       } elseif (! empty($filter_type)) {
                                           echo $filter_type;
                                       } else {
                                           echo "All";
                                       }

                                   ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header">By Status</h6></li>
                            <li><a class="dropdown-item <?php echo $filter_type == 'Laptop' ? 'active' : ''; ?>" href="#" onclick="applyFilter('type', 'Laptop')">Laptops</a></li>
                            <li><a class="dropdown-item <?php echo $filter_type == 'Desktop' ? 'active' : ''; ?>" href="#" onclick="applyFilter('type', 'Desktop')">Desktops</a></li>
                            <li><a class="dropdown-item <?php echo $filter_type == 'Monitor' ? 'active' : ''; ?>" href="#" onclick="applyFilter('type', 'Monitor')">Monitors</a></li>
                        </ul>
                    </div>
                </form>
            </div>
            <div class="col-md-5 text-end">
            <button class="btn p-3 px-4 rounded-4 fw-bold me-2"
                    style="background-color: #6f42c1; color: #ffffff !important; border: none;"
                    data-bs-toggle="modal"
                    data-bs-target="#createItemModal">
                <i class="fas fa-plus me-2"></i>New Asset
            </button>

            <div class="dropdown d-inline-block">
                <button class="btn btn-dark p-3 px-4 rounded-4 fw-bold dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                    <i class="fas fa-file-export me-2"></i>Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    <li><a class="dropdown-item" href="#" onclick="exportInventoryPDF()"><i class="fas fa-file-pdf me-2 text-danger"></i>Export as PDF</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportCSV()"><i class="fas fa-file-csv me-2 text-success"></i>Export as CSV</a></li>
                </ul>
            </div>
        </div>
    
        <?php
// GUMAGANA AT NAG-PROPROCESS NG PAGLIPAT SA INVENTORY TABLE
if (isset($_POST['confirm_selection'])) {
    if (!empty($_POST['asset_ids'])) {
        // Kinukuha ang lahat ng napiling ID at sinisigurong ligtas laban sa SQL injection
        $selected_ids = array_map('intval', $_POST['asset_ids']);
        $ids_string = implode(',', $selected_ids);

        // START DATABASE TRANSACTION PARA SIGURADONG LIGTAS ANG PAGLIPAT
        mysqli_begin_transaction($conn);

        try {
            // 1. KOPYAHIN ANG DATA MULA SA assets_temp PAPUNTA SA INVENTORY TABLE
            // Tiyakin na ang mga column names dito ay tumutugma sa eksaktong istruktura ng mga table mo, master
            $sql_insert = "INSERT INTO `assets` (asset_tag, asset_type, brand_model, year_model, serial_number, location, status, inventory_date) 
                           SELECT asset_tag, asset_type, brand_model, year_model, serial_number, location, status, inventory_date 
                           FROM `assets_temp` 
                           WHERE id IN ($ids_string)";
            mysqli_query($conn, $sql_insert);

            // 2. BURAHIN ANG MGA NALIPAT NA DATA MULA SA assets_temp PARA MAWALA SILA SA KASALUKUYANG TALAHANAYAN
            $sql_delete = "DELETE FROM `assets_temp` WHERE id IN ($ids_string)";
            mysqli_query($conn, $sql_delete);

            // I-commit ang pagbabago kung walang naging error
            mysqli_commit($conn);

            echo "<script>alert('Asset Approved " . count($selected_ids) . "'); window.location.href=window.location.href;</script>";
        } catch (Exception $e) {
            // Kapag nagka-error ang isa sa mga query, i-rollback para walang masirang data
            mysqli_rollback($conn);
            echo "<script>alert('Nagkaroon ng problema sa paglipat ng data. Pakisubukang muli.');</script>";
        }
    } else {
        echo "<script>alert('Mangyaring pumili muna ng asset bago mag-confirm.');</script>";
    }
}
?>

<form method="POST" action="">
<div id="table-to-export">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-light">
                        <tr class="text-muted small">
                            <th>DATE</th>
                            <th>ASSET TAG</th>
                            <th>QR</th>
                            <th>ITEM DETAILS</th>
                            <th>LOCATION</th>
                            <th>STATUS</th>
                             <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Administrator'): ?>
                            <th class="no-export text-center">ACTION</th>
                            <th class="no-export text-center">
                                <input type="checkbox" id="selectAll" class="form-check-input"> SELECT ALL
                            </th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $sql = "SELECT * FROM `assets_temp` as a LEFT JOIN client_accounts as b ON a.location=b.account_id";
                            if (! empty($filter_status)) {$f  = mysqli_real_escape_string($conn, $filter_status);
                                $sql          .= " AND status = '$f'";}
                            if (! empty($filter_type)) {$t  = mysqli_real_escape_string($conn, $filter_type);
                                $sql          .= " AND asset_type = '$t'";}
                            if (! empty($search)) {$s  = mysqli_real_escape_string($conn, $search);
                                $sql          .= " AND (asset_tag LIKE '%$s%' OR brand_model LIKE '%$s%' OR serial_number LIKE '%$s%')";}
                            $sql .= " ORDER BY a.inventory_date DESC";
                            $res = mysqli_query($conn, $sql);

                            while ($row = mysqli_fetch_assoc($res)):
                                $badge = ($row['status'] == 'For Disposal') ? 'st-disposal' : (($row['status'] == 'Replacement') ? 'st-replacement' : 'st-active');
                        ?>
                        <tr>
                            <td class="small fw-600"><?php echo date('M d, Y', strtotime($row['inventory_date'])); ?></td>
                            <td><span class="badge bg-light text-dark fw-bold border"><?php echo $row['asset_tag']; ?></span></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary border-0 view-qr-btn" id="view-qr-btn-<?php echo $row['id']; ?>"
                                        data-tag="<?php echo $row['asset_tag']; ?>"
                                        data-serial="<?php echo $row['serial_number']; ?>"
                                        data-model="<?php echo $row['brand_model']; ?>"
                                        data-date="<?php echo date('M d, Y', strtotime($row['inventory_date'])); ?>"
                                        data-type="<?php echo $row['asset_type']; ?>"
                                        data-year="<?php echo $row['year_model']; ?>"
                                        data-loc="<?php echo htmlspecialchars($row['client_name']); ?>"
                                        data-status="<?php echo $row['status']; ?>"
                                        data-qr="TAG: <?php echo $row['asset_tag']; ?> | TYPE: <?php echo $row['asset_type']; ?> | MODEL: <?php echo $row['brand_model']; ?> (<?php echo $row['year_model']; ?>) | SN: <?php echo $row['serial_number']; ?> | LOC: <?php echo htmlspecialchars($row['client_name']); ?> | STATUS: <?php echo $row['status']; ?> | DATE: <?php echo $row['inventory_date']; ?>">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo $row['brand_model']; ?></div>
                                <div class="small text-muted">SN: <?php echo $row['serial_number']; ?></div>
                            </td>
                            <td class="small"><?php echo $row['client_name']; ?></td>
                            <td><span class="status-badge <?php echo $badge; ?>"><?php echo $row['status']; ?></span></td>

                             <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Administrator'): ?>
                                <td class="no-export text-center">
                            <button type="button" class="btn btn-sm btn-outline-secondary border-0 editBtn"
                                data-id="<?php echo $row['id']; ?>"
                                data-tag="<?php echo $row['asset_tag']; ?>"
                                data-serial="<?php echo $row['serial_number']; ?>"
                                data-model="<?php echo $row['brand_model']; ?>"
                                data-loc="<?php echo $row['location']; ?>"
                                data-status="<?php echo $row['status']; ?>">
                                <i class="fas fa-edit"></i>
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-danger border-0 ms-1 deleteBtn"
                                data-id="<?php echo $row['id']; ?>"
                                data-tag="<?php echo $row['asset_tag']; ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                        <td class="no-export text-center">
                            <input type="checkbox" name="asset_ids[]" value="<?php echo $row['id']; ?>" class="form-check-input asset-checkbox">
                        </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Administrator'): ?>
<div id="confirmBtnContainer" class="d-none justify-content-end mt-3 me-3">
    <button type="submit" name="confirm_selection" id="confirmBtn" class="btn btn-primary px-4 fw-bold shadow-sm">
        <i class="fas fa-check-circle me-2"></i> CONFIRM SELECTED
    </button>
</div>
<?php endif; ?>
</form>

<script>
const selectAllCheckbox = document.getElementById('selectAll');
const assetCheckboxes = document.querySelectorAll('.asset-checkbox');
const confirmBtnContainer = document.getElementById('confirmBtnContainer');

function toggleConfirmButtonVisibility() {
    let checkedCount = document.querySelectorAll('.asset-checkbox:checked').length;
    
    if (checkedCount > 0) {
        confirmBtnContainer.classList.remove('d-none');
        confirmBtnContainer.classList.add('d-flex');
    } else {
        confirmBtnContainer.classList.remove('d-flex');
        confirmBtnContainer.classList.add('d-none');
    }
}

if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', function() {
        assetCheckboxes.forEach(cb => {
            cb.checked = this.checked;
        });
        toggleConfirmButtonVisibility();
    });
}

assetCheckboxes.forEach(cb => {
    cb.addEventListener('change', function() {
        if (!this.checked && selectAllCheckbox) {
            selectAllCheckbox.checked = false;
        }
        let allChecked = document.querySelectorAll('.asset-checkbox:checked').length === assetCheckboxes.length;
        if (allChecked && selectAllCheckbox) {
            selectAllCheckbox.checked = true;
        }
        
        toggleConfirmButtonVisibility();
    });
});
</script>

<div class="modal fade" id="createItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
            <form action="" method="POST" class="p-4">
                <div class="row">
                    <div class="col-md-8 pe-4">
                        <h3 class="fw-800 mb-4" style="color:var(--inspiro-purple)">Register New Asset</h3>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label-custom">Asset Tag</label>
                                <input type="text" name="manual_tag" id="in_tag" class="input-custom" placeholder="Optional custom tag">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Serial Number</label>
                                <input type="text" name="serial_number" id="in_serial" class="input-custom" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Brand & Model</label>
                                <input type="text" name="brand_model" id="in_model" class="input-custom" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">Asset Type</label>
                                <select name="type" class="input-custom">
                                    <option>Laptop</option>
                                    <option>Desktop</option>
                                    <option>Monitor</option>
                                    <option>Printer</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">Location</label>
                                <select name="location" class="input-custom" required>
                                    <option value="" disabled selected>Select Client Account</option>
                                    <?php foreach ($client_accounts as $account): ?>
                                        <option value="<?php echo $account['account_id']; ?>">
                                            <?php echo htmlspecialchars($account['client_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">Status</label>
                                <select name="status" class="input-custom">
                                    <option>Active</option>
                                    <option>Replacement</option>
                                    <option>For Disposal</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label-custom">Date Received</label>
                                <input type="date" name="date" class="input-custom" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex flex-column align-items-center justify-content-center border-start bg-light rounded-end-5">
                        <div class="text-center p-4">
                            <label class="form-label-custom mb-3">Live QR Preview</label>
                            <div class="bg-white p-3 rounded-4 shadow-sm">
                                <canvas id="modal_qr_preview"></canvas>
                            </div>
                            <p class="small text-muted mt-3">QR will auto-update as you type.</p>
                        </div>
                    </div>
                </div>
                <div class="text-end mt-4">
                    <button type="button" class="btn btn-light px-4 py-2 fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_asset" class="btn px-5 py-2 fw-bold ms-2" style="background: #6f42c1; color: white;">Save Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
            <form action="" method="POST" class="p-4">
                <input type="hidden" name="asset_id" id="edit_id">
                <h3 class="fw-800 mb-4" style="color:var(--inspiro-purple)">Update Asset Record</h3>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label-custom">Asset Tag (Read Only)</label>
                        <input type="text" name="asset_tag" id="edit_tag" class="input-custom" readonly style="background:#f0f0f0;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-custom">Serial Number</label>
                        <input type="text" name="serial_number" id="edit_serial" class="input-custom" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-custom">Brand & Model</label>
                        <input type="text" name="brand_model" id="edit_model" class="input-custom" required>
                    </div>
                        <div class="col-md-6">
                        <label class="form-label-custom">Location</label>
                        <select name="location" id="edit_loc" class="input-custom" required>
                            <option value="" disabled>Select Client Account</option>
                            <?php foreach ($client_accounts as $account): ?>
                                <option value="<?php echo $account['account_id']; ?>">
                                    <?php echo htmlspecialchars($account['client_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-custom">Status</label>
                        <select name="status" id="edit_status" class="input-custom">
                            <option>Active</option>
                            <option>Replacement</option>
                            <option>For Disposal</option>
                        </select>
                    </div>
                </div>
                <div class="text-end mt-5">
                    <button type="submit" name="update_asset" class="btn btn-dark px-5 py-2 fw-bold rounded-pill">Apply Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="qrViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-body text-center p-4">
                <h6 class="fw-bold mb-3" style="color: #7A1CAC;">QR CODE</h6>
                <div class="bg-white p-3 rounded-4 shadow-sm mb-3">
                    <canvas id="qrViewCanvas"></canvas>
                </div>
                <p class="mb-1"><strong id="qrViewTag"></strong></p>
                <p class="text-muted small mb-1" id="qrViewModel"></p>
                <p class="text-muted small" id="qrViewSN"></p>
                <button type="button" class="btn btn-secondary rounded-4 mt-2" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="qrScannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">Scan QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="qr-reader" style="width: 100%;"></div>
                <div id="qr-reader-results" class="mt-3 text-center"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-4" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
    // 1. FILTER FUNCTION
    function applyFilter(type, value) {
        if (type === 'status') document.getElementById('status_filter_input').value = value;
        if (type === 'type') document.getElementById('type_filter_input').value = value;
        document.getElementById('filterForm').submit();
    }
    
    // Delete Button
    $('.deleteBtn').on('click', function() {
        var assetId = $(this).data('id');
        var assetTag = $(this).data('tag');

        Swal.fire({
            title: 'Delete Asset?',
            text: "Are you sure you want to delete " + assetTag + "? This cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'view_inventory.php?delete_id=' + assetId;
            }
        });
    });
    
    // 2. GENERATE TABLE QR CODES
    function generateTableQRs() {
        document.querySelectorAll('.table-qr').forEach(canvas => {
            new QRious({
                element: canvas,
                value: canvas.getAttribute('data-value'),
                size: 80,
                level: 'M'
            });
        });
    }
    
    // 3. REAL-TIME QR PREVIEW IN MODAL
    function updateModalQR() {
        const tag = document.getElementById('in_tag').value || "AST-PREVIEW";
        const serial = document.getElementById('in_serial').value || "---";
        const qrContent = `TAG: ${tag} | SN: ${serial}`;
        new QRious({ element: document.getElementById('modal_qr_preview'), value: qrContent, size: 200, level: 'M' });
    }

    ['in_tag', 'in_serial'].forEach(id => {
        document.getElementById(id).addEventListener('input', updateModalQR);
    });

    $(document).ready(function() {
        generateTableQRs();
        updateModalQR();
    });

    // 4. EDIT BUTTON MAPPING
    $('.editBtn').on('click', function() {
        $('#edit_id').val($(this).data('id'));
        $('#edit_tag').val($(this).data('tag'));
        $('#edit_serial').val($(this).data('serial'));
        $('#edit_model').val($(this).data('model'));

        // Set the dropdown selection based on the stored ID
        var storedLocId = $(this).data('loc');
        $('#edit_loc').val(storedLocId); // This selects the correct option in the dropdown

        $('#edit_status').val($(this).data('status'));
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    // 5. PDF EXPORT
    function exportInventoryPDF() {
        const tableHtml = document.getElementById('table-to-export').cloneNode(true);
        tableHtml.querySelectorAll('.no-export').forEach(el => el.remove());

        const originalCanvases = document.querySelectorAll('.table-qr');
        const clonedCanvases = tableHtml.querySelectorAll('.table-qr');
        clonedCanvases.forEach((canvas, i) => {
            const img = document.createElement('img');
            img.src = originalCanvases[i].toDataURL("image/png");
            img.style.width = "40px";
            canvas.parentNode.replaceChild(img, canvas);
        });

        const container = document.createElement('div');
        container.style.padding = '30px';
        container.innerHTML = `
            <div style="text-align: center; border-bottom: 2px solid #7A1CAC; margin-bottom: 20px;">
                <h2 style="color: #7A1CAC; margin:0;">INSPIRO RELIA INC.</h2>
                <p style="margin:5px 0;">Asset Inventory Report - ${new Date().toLocaleDateString()}</p>
            </div>
        `;
        container.appendChild(tableHtml);

        const opt = {
            margin: 0.5,
            filename: `Inspiro_Inventory_${new Date().getTime()}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'in', format: 'a4', orientation: 'landscape' }
        };
        html2pdf().set(opt).from(container).save();
    }

   const urlParams = new URLSearchParams(window.location.search);

if(urlParams.get('msg') === 'success_create') {
    Swal.fire({ icon: 'success', title: 'Asset Added!', showConfirmButton: false, timer: 1500 });
}
if(urlParams.get('msg') === 'success_update') {
    Swal.fire({ icon: 'success', title: 'Record Updated!', showConfirmButton: false, timer: 1500 });
}
if(urlParams.get('msg') === 'error_duplicate_tag') {
    Swal.fire({ icon: 'error', title: 'Duplicate Asset Tag', text: 'This asset tag already exists. Please use a different tag.', showConfirmButton: false, timer: 3500 });
}
if(urlParams.get('msg') === 'error_duplicate_serial') {
    Swal.fire({ icon: 'error', title: 'Duplicate Serial Number', text: 'This serial number already exists in the system.', showConfirmButton: false, timer: 3500 });
}
if(urlParams.get('msg') === 'success_delete') {
    Swal.fire({ icon: 'success', title: 'Asset Deleted!', showConfirmButton: false, timer: 1500 });
}

    function exportCSV() {
        let csv = [];
        let rows = document.querySelectorAll("#table-to-export table tr");
        for (let i = 0; i < rows.length; i++) {
            let row = [], cols = rows[i].querySelectorAll("td, th");
            for (let j = 0; j < cols.length; j++) {
                if (cols[j].classList.contains('no-export')) continue;
                row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
            }
            csv.push(row.join(","));
        }
        let blob = new Blob([csv.join("\n")], { type: 'text/csv' });
        let url = window.URL.createObjectURL(blob);
        let a = document.createElement("a");
        a.href = url;
        a.download = "Inventory_Data.csv";
        a.click();
    }

    // 6. QR VIEW BUTTON (Updated with e.currentTarget fix)
    // $(document).on('click', '.view-qr-btn', function(e) {
    //     e.preventDefault();
    //     var $btn = $(e.currentTarget);
    //     var tag = $btn.data('tag');
    //     var sn = $btn.data('serial');
    //     var model = $btn.data('model');
    //     var qrValue = $btn.data('qr');

    //     // Update modal content
    //     $('#qrViewTag').text(tag);
    //     $('#qrViewModel').text(model);
    //     $('#qrViewSN').text('SN: ' + sn);

    //     // Generate QR on the canvas
    //     var canvas = document.getElementById('qrViewCanvas');
    //     new QRious({
    //         element: canvas,
    //         value: qrValue,
    //         size: 200,
    //         level: 'H'
    //     });

    //     // Now show the modal
    //     $('#qrViewModal').modal('show');
    // });

   $(document).on('click', '.view-qr-btn', function(e) {
    e.preventDefault();
    var $btn = $(e.currentTarget);
    
   
    var tag = $btn.data('tag');
    var sn = $btn.data('serial');
    var model = $btn.data('model');
    var date = $btn.data('date');
    var type = $btn.data('type');
    var year = $btn.data('year');
    var loc = $btn.data('loc');
    var status = $btn.data('status');
    var qrValue = $btn.data('qr');  
    
    $('#qrViewTag').text(tag);
    $('#qrViewModel').text(model + (year ? ' (' + year + ')' : ''));
    $('#qrViewSN').text('SN: ' + sn);
    
    
    $('#qrViewType').text(type);
    $('#qrViewLoc').text(loc);
    $('#qrViewStatus').text(status);

    
    var canvas = document.getElementById('qrViewCanvas');
    new QRious({
        element: canvas,
        value: qrValue,
        size: 220,  
        level: 'H'  
    });

    // Now show the modal
    $('#qrViewModal').modal('show');
});

    $(document).on('hidden.bs.modal', '#qrViewModal', function() {
        // Clear QR when modal closes
        var canvas = document.getElementById('qrViewCanvas');
        var ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let html5QrCode = null;
    const qrScanBtn = document.getElementById('qrScanBtn');
    const qrScannerModal = new bootstrap.Modal(document.getElementById('qrScannerModal'));
    const searchInput = document.querySelector('input[name="search"]');

    qrScanBtn.addEventListener('click', function() {
        qrScannerModal.show();

        // Initialize scanner when modal is shown
        setTimeout(() => {
            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode("qr-reader");
            }

            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            };

            html5QrCode.start(
                { facingMode: "environment" }, // Use back camera
                config,
                (decodedText, decodedResult) => {

                    searchInput.value = decodedText;

                    const event = new Event('input', { bubbles: true });
                    searchInput.dispatchEvent(event);

                    const form = searchInput.closest('form');
                    if (form) {
                        form.submit();
                    }

                    if (html5QrCode && html5QrCode.isScanning) {
                        html5QrCode.stop();
                    }
                    qrScannerModal.hide();

                    const resultsDiv = document.getElementById('qr-reader-results');
                    resultsDiv.innerHTML = '<div class="alert alert-success">✓ QR Code scanned: ' + decodedText + '</div>';
                    setTimeout(() => {
                        resultsDiv.innerHTML = '';
                    }, 2000);
                },
                (errorMessage) => {
                    console.log(errorMessage);
                }
            ).catch(err => {
                console.error("Failed to start scanner:", err);
                document.getElementById('qr-reader-results').innerHTML = '<div class="alert alert-danger">Unable to access camera. Please check permissions.</div>';
            });
        }, 500);
    });

    document.getElementById('qrScannerModal').addEventListener('hidden.bs.modal', function() {
        if (html5QrCode && html5QrCode.isScanning) {
            html5QrCode.stop();
        }
        document.getElementById('qr-reader-results').innerHTML = '';
    });
});
</script>
</body>
</html>