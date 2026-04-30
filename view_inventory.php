<?php
ob_start(); 
session_start();
include 'config.php'; 

// --- 1. SESSION & IDENTITY CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_uid = $_SESSION['user_id'];
// Kunin ang fullname at role mula sa database
$user_res = mysqli_query($conn, "SELECT fullname, role FROM users WHERE id = '$current_uid'");
$user_data = mysqli_fetch_assoc($user_res);

$display_name = $user_data['fullname'] ?? "Unknown User"; 
$user_role = $user_data['role'] ?? "User"; 
$search = $_GET['search'] ?? '';

// --- 2. UPDATE LOGIC ---
if (isset($_POST['update_asset'])) {
    $asset_id = mysqli_real_escape_string($conn, $_POST['asset_id']);
    $serial = mysqli_real_escape_string($conn, $_POST['serial_number']);
    $model = mysqli_real_escape_string($conn, $_POST['brand_model']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $logged_in_user = mysqli_real_escape_string($conn, $display_name);

    $update_query = "UPDATE assets SET 
                    serial_number='$serial', 
                    brand_model='$model', 
                    location='$location', 
                    status='$status',
                    updated_by='$logged_in_user' 
                    WHERE id='$asset_id'";
    
    if (mysqli_query($conn, $update_query)) {
        header("Location: view_inventory.php?msg=Asset Updated Successfully");
        exit();
    }
}

// --- NEW ASSET LOGIC (QR Code modern inventory) ---
if (isset($_POST['save_asset'])) {
    if ($user_role === 'Administrator') {
        $serial = mysqli_real_escape_string($conn, $_POST['serial_number']);
        $model = mysqli_real_escape_string($conn, $_POST['brand_model']);
        $type = mysqli_real_escape_string($conn, $_POST['type']);
        $loc = mysqli_real_escape_string($conn, $_POST['location']);
        $date = mysqli_real_escape_string($conn, $_POST['date']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        $insert = "INSERT INTO assets (serial_number, brand_model, equipment_type, location, inventory_date, status, updated_by) 
                   VALUES ('$serial', '$model', '$type', '$loc', '$date', '$status', '$display_name')";
        
        if (mysqli_query($conn, $insert)) {
            header("Location: view_inventory.php?msg=New Asset Registered");
            exit();
        }
    } else {
        die("Unauthorized access.");
    }
}

// --- 3. COUNTERS ---
$count_replacement = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assets WHERE status='Replacement'"))['total'] ?? 0;
$count_disposal = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assets WHERE status='For Disposal'"))['total'] ?? 0;
$count_in_use = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assets WHERE status='Active'"))['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspiro | Inventory Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <style>
        :root { 
            --app-bg: #f4f7fe; 
            --main-gradient: linear-gradient(135deg, #7A1CAC 0%, #7A1CAC 100%);
            --accent-purple: #7A1CAC; 
            --sidebar-width: 260px;
        }
        body { background-color: var(--app-bg); font-family: 'Plus Jakarta Sans', sans-serif; color: #2d3436; margin: 0; }
        
        .content-wrapper { margin-left: var(--sidebar-width); padding: 35px; min-height: 100vh; }
        
        .glass-header-container {
            background: white; border-radius: 50px; padding: 15px 45px; 
            display: flex; justify-content: space-between; align-items: center; 
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.04); margin-bottom: 45px;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .header-title-section h2 { color: var(--accent-purple); font-weight: 700; font-size: 1.5rem; margin: 0; }
        .user-nav-section { display: flex; align-items: center; gap: 15px; }
        .profile-avatar-pill {
            width: 50px; height: 50px; background: var(--main-gradient); 
            color: white; border-radius: 18px; display: flex; 
            align-items: center; justify-content: center; font-weight: 700;
        }

        .metric-card { background: white; border-radius: 20px; padding: 1.5rem; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .data-panel { background: white; border-radius: 25px; padding: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        .form-label-custom { font-weight: 700; color: #5d5d5d; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 5px; display: block; }
        .input-custom { border-radius: 5px; padding: 10px 15px; border: 1px solid #ced4da; font-weight: 600; font-size: 0.9rem; width: 100%; outline: none; }
        
        /* Status Badges */
        .status-badge { padding: 6px 12px; border-radius: 10px; font-weight: 700; font-size: 0.7rem; text-transform: uppercase; }
        .st-active { background: #E9D5FF; color: #7A1CAC; }
        .st-disposal { background: #FEE2E2; color: #DC2626; }
        .st-replacement { background: #fff9e6; color: #f39c12; }

        @media (max-width: 992px) { .content-wrapper { margin-left: 0; padding: 20px; } }
    </style>
</head>
<body>

<?php include 'aside.php'; ?>

<div class="content-wrapper" id="pdfContent">
    <div class="glass-header-container no-export">
        <div class="header-title-section">
            <h2>VIEW INVENTORY</h2>
            <p>Asset Management & Monitoring</p>
        </div>
        <div class="user-nav-section">
            <div class="user-info-text text-end">
                <div class="user-name-top"><?php echo htmlspecialchars($display_name); ?></div>
                <a href="logout.php" class="sign-out-link text-decoration-none" style="color: #AD49E1; font-size: 0.8rem; font-weight: 600;">Sign Out</a>
            </div>
            <div class="profile-avatar-pill"><?php echo strtoupper(substr($display_name, 0, 1)); ?></div>
        </div>
    </div>

    <div class="row g-3 mb-4 no-export">
        <div class="col-md-4"><div class="metric-card p-3 d-flex justify-content-between"><span>Replacement</span><h4 class="m-0 fw-800"><?php echo $count_replacement; ?></h4></div></div>
        <div class="col-md-4"><div class="metric-card p-3 d-flex justify-content-between"><span>For Disposal</span><h4 class="m-0 fw-800 text-danger"><?php echo $count_disposal; ?></h4></div></div>
        <div class="col-md-4"><div class="metric-card p-3 d-flex justify-content-between"><span>Active</span><h4 class="m-0 fw-800 text-success"><?php echo $count_in_use; ?></h4></div></div>
    </div>

    <div class="data-panel">
        <div class="row g-3 mb-4 no-export">
            <div class="col-md-6">
                <form method="GET">
                    <input type="text" name="search" class="form-control border-0 bg-light p-3" placeholder="Search asset tag or model..." value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>
            <div class="col-md-6 text-end d-flex gap-2 justify-content-end">
                <?php if ($user_role === 'Administrator'): ?>
                    <button class="btn btn-primary p-3 fw-bold rounded-3 border-0" style="background: var(--main-gradient);" data-bs-toggle="modal" data-bs-target="#createItemModal">
                        <i class="fas fa-plus-circle me-2"></i>Create Item
                    </button>
                <?php endif; ?>
                <button onclick="exportToPDF()" class="btn btn-dark p-3 fw-bold rounded-3"><i class="fas fa-file-pdf me-2"></i>Export PDF</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>DATE</th><th>ASSET TAG</th><th>DETAILS</th><th>LOCATION</th><th>STATUS</th><th class="no-export">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT * FROM assets";
                    if (!empty($search)) {
                        $s = mysqli_real_escape_string($conn, $search);
                        $query .= " WHERE asset_tag LIKE '%$s%' OR brand_model LIKE '%$s%'";
                    }
                    $query .= " ORDER BY inventory_date DESC";
                    $res = mysqli_query($conn, $query);

                    while ($row = mysqli_fetch_assoc($res)):
                        $badge = ($row['status'] == 'For Disposal') ? 'st-disposal' : (($row['status'] == 'Replacement') ? 'st-replacement' : 'st-active');
                    ?>
                    <tr>
                        <td class="small fw-bold"><?php echo date('M d, Y', strtotime($row['inventory_date'])); ?></td>
                        <td><span class="badge bg-light text-dark border"><?php echo $row['asset_tag']; ?></span></td>
                        <td>
                            <div class="fw-bold small text-uppercase"><?php echo $row['brand_model']; ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;"><?php echo $row['serial_number']; ?></div>
                        </td>
                        <td class="small"><?php echo $row['location']; ?></td>
                        <td><span class="status-badge <?php echo $badge; ?>"><?php echo $row['status']; ?></span></td>
                        <td class="no-export">
                            <button class="btn btn-sm btn-dark editBtn" 
                                data-id="<?= $row['id'] ?>"
                                data-tag="<?= $row['asset_tag'] ?>"
                                data-serial="<?= $row['serial_number'] ?>"
                                data-model="<?= $row['brand_model'] ?>"
                                data-location="<?= $row['location'] ?>"
                                data-status="<?= $row['status'] ?>"
                                data-date="<?= $row['inventory_date'] ?>">Edit</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- EDIT MODAL (Based on image_2a5f99.png) -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 10px; background: #f2e9e4;">
            <div class="modal-header border-0 px-4 pt-4">
                <h5 class="fw-bold text-uppercase" style="letter-spacing: 1px;">EDIT ITEM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <hr class="mx-4 my-0" style="border-top: 2px solid #7A1CAC;">
            <div class="modal-body px-4 pb-4">
                <form action="" method="POST">
                    <input type="hidden" name="asset_id" id="edit_id">
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label-custom">Asset Tag</label>
                            <input type="text" id="edit_tag" class="input-custom bg-white mb-3" readonly>
                            <label class="form-label-custom">Serial Number</label>
                            <input type="text" name="serial_number" id="edit_serial" class="input-custom bg-white mb-3" required>
                            <label class="form-label-custom">Brand/Model</label>
                            <input type="text" name="brand_model" id="edit_model" class="input-custom bg-white mb-3" required>
                            <label class="form-label-custom">Type</label>
                            <select name="type" id="edit_type" class="input-custom form-select bg-white"><option value="Laptop">Laptop</option><option value="Desktop">Desktop</option></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-custom">Area</label>
                            <select name="location" id="edit_location" class="input-custom form-select bg-white mb-3"><option>BDO Insure</option><option>Grab COE</option><option>Main Office</option></select>
                            <label class="form-label-custom">Date</label>
                            <input type="date" name="date" id="edit_date" class="input-custom bg-white mb-3">
                            <label class="form-label-custom">Status</label>
                            <select name="status" id="edit_status" class="input-custom form-select bg-white mb-3"><option value="Active">Active / In Use</option><option value="Replacement">Replacement</option><option value="For Disposal">For Disposal</option></select>
                            <label class="form-label-custom">Log</label>
                            <textarea name="log" class="input-custom bg-white" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <div>
                            <button type="submit" name="update_asset" class="btn btn-sm px-4 py-2 fw-bold text-white shadow-sm" style="background: #7A1CAC; border-radius: 5px;">Save Changes</button>
                            <button type="button" class="btn btn-sm btn-secondary px-4 py-2 fw-bold" data-bs-dismiss="modal">Cancel</button>
                        </div>
                        <button type="button" class="btn btn-sm btn-danger px-4 py-2 fw-bold" style="border-radius: 5px;"><i class="fas fa-trash me-1"></i> Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- CREATE MODAL (Original Logic) -->
<div class="modal fade" id="createItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 35px; background: #fcfaff;">
            <div class="modal-header border-0 px-5 pt-5 pb-0">
                <h2 class="fw-800 mb-1" style="color: var(--accent-purple); text-transform: uppercase;">Asset Registration</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-5 pb-5">
                <form action="" method="POST">
                    <div class="row g-4 mt-1">
                        <div class="col-md-6"><label class="form-label-custom">Serial Number</label><input type="text" name="serial_number" class="input-custom" placeholder="Enter Serial" required></div>
                        <div class="col-md-6"><label class="form-label-custom">Brand & Model</label><input type="text" name="brand_model" class="input-custom" placeholder="e.g. HP ProBook 440" required></div>
                        <div class="col-md-3"><label class="form-label-custom">Equipment Type</label><select name="type" class="input-custom form-select"><option value="Laptop">Laptop</option><option value="Desktop">Desktop</option></select></div>
                        <div class="col-md-3"><label class="form-label-custom">Location</label><select name="location" class="input-custom form-select"><option>BDO Insure</option><option>Main Office</option></select></div>
                        <div class="col-md-3"><label class="form-label-custom">Date Acquired</label><input type="date" name="date" class="input-custom" value="<?php echo date('Y-m-d'); ?>"></div>
                        <div class="col-md-3"><label class="form-label-custom">Status</label><select name="status" class="input-custom form-select"><option value="Active">Active</option></select></div>
                    </div>
                    <div class="text-center mt-5"><button type="submit" name="save_asset" class="btn btn-primary p-3 fw-bold rounded-3" style="background: var(--main-gradient); width: 250px;">Save Asset Record</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Export Functionality
    function exportToPDF() {
        const element = document.getElementById('pdfContent');
        const opt = { margin: [0.5, 0.5], filename: 'Inventory_Report.pdf', jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' } };
        html2pdf().set(opt).from(element).save();
    }

    // Edit Logic - Pagpasa ng data sa modal
    document.querySelectorAll('.editBtn').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_tag').value = this.dataset.tag;
            document.getElementById('edit_serial').value = this.dataset.serial;
            document.getElementById('edit_model').value = this.dataset.model;
            document.getElementById('edit_location').value = this.dataset.location;
            document.getElementById('edit_status').value = this.dataset.status;
            document.getElementById('edit_date').value = this.dataset.date;
            new bootstrap.Modal(document.getElementById('editItemModal')).show();
        });
    });
</script>

</body>
</html>