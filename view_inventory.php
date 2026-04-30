<?php
ob_start(); 
session_start();
include 'config.php'; 

// --- 1. SESSION & USER CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_uid = $_SESSION['user_id'];
$user_res = mysqli_query($conn, "SELECT fullname, role FROM users WHERE id = '$current_uid'");
$user_data = mysqli_fetch_assoc($user_res);

$display_name = $user_data['fullname'] ?? "Unknown User"; 
$user_role = $user_data['role'] ?? "User"; 
$search = $_GET['search'] ?? '';

// --- 2. UPDATE ASSET LOGIC ---
if (isset($_POST['update_asset'])) {
    $asset_id = mysqli_real_escape_string($conn, $_POST['asset_id']);
    $tag = mysqli_real_escape_string($conn, $_POST['asset_tag']);
    $serial = mysqli_real_escape_string($conn, $_POST['serial_number']);
    $model = mysqli_real_escape_string($conn, $_POST['brand_model']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $update_query = "UPDATE assets SET asset_tag='$tag', serial_number='$serial', brand_model='$model', location='$location', status='$status' WHERE id='$asset_id'";
    mysqli_query($conn, $update_query);
    header("Location: view_inventory.php?msg=success_update");
    exit();
}

// --- 3. CREATE ASSET LOGIC ---
if (isset($_POST['save_asset'])) {
    $serial = mysqli_real_escape_string($conn, $_POST['serial_number']);
    $model = mysqli_real_escape_string($conn, $_POST['brand_model']);
    $type = mysqli_real_escape_string($conn, $_POST['type']); 
    $loc = mysqli_real_escape_string($conn, $_POST['location']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $asset_tag = !empty($_POST['manual_tag']) ? mysqli_real_escape_string($conn, $_POST['manual_tag']) : "AST-" . strtoupper(substr($type, 0, 1)) . "-" . rand(1000, 9999);

    $insert = "INSERT INTO assets (inventory_date, asset_tag, serial_number, brand_model, asset_type, location, status) VALUES ('$date', '$asset_tag', '$serial', '$model', '$type', '$loc', '$status')";
    mysqli_query($conn, $insert);
    header("Location: view_inventory.php?msg=success_create");
    exit();
}

// --- 4. COUNTERS ---
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root { --app-bg: #f4f7fe; --main-gradient: linear-gradient(135deg, #7A1CAC 0%, #7A1CAC 100%); --accent-purple: #8e44ad; --sidebar-width: 260px; }
        body { background-color: var(--app-bg); font-family: 'Plus Jakarta Sans', sans-serif; color: #2d3436; margin: 0; }
        .content-wrapper { margin-left: var(--sidebar-width); padding: 35px; min-height: 100vh; }
        .glass-header-container { background: white; border-radius: 50px; padding: 15px 45px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.04); margin-bottom: 45px; }
        .metric-card { background: white; border-radius: 20px; padding: 1.5rem; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .data-panel { background: white; border-radius: 25px; padding: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        .form-label-custom { font-weight: 700; color: var(--accent-purple); font-size: 0.75rem; text-transform: uppercase; margin-bottom: 8px; display: block; }
        .input-custom { border-radius: 15px; padding: 12px 18px; border: 2px solid #f1f1f7; background: #fcfaff; font-weight: 600; font-size: 0.9rem; width: 100%; outline: none; }
        .btn-create-item { background: var(--main-gradient); color: white; border: none; padding: 12px 25px; border-radius: 15px; font-weight: 800; text-transform: uppercase; }
        .status-badge { padding: 6px 12px; border-radius: 10px; font-weight: 700; font-size: 0.7rem; text-transform: uppercase; }
        .st-active { background: #b198be; color: #2E073F; }
        .st-disposal { background: #f8d7da; color: #721c24; }
        .st-replacement { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>

<?php include 'aside.php'; ?>

<div class="content-wrapper">
    <div class="glass-header-container">
        <div>
            <h2 style="color:var(--accent-purple); font-weight:600;">VIEW INVENTORY</h2>
            <p class="m-0">Asset Management & Monitoring</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end">
                <div class="fw-bold"><?php echo htmlspecialchars($display_name); ?></div>
                <a href="logout.php" class="text-danger small text-decoration-none fw-bold">Sign Out</a>
            </div>
            <div style="width:50px; height:50px; background:var(--main-gradient); color:white; border-radius:15px; display:flex; align-items:center; justify-content:center; font-weight:800;">
                <?php echo strtoupper(substr($display_name, 0, 1)); ?>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="metric-card d-flex justify-content-between align-items-center"><span class="fw-bold text-muted">Replacement</span><h4 class="m-0 fw-800"><?php echo $count_replacement; ?></h4></div></div>
        <div class="col-md-4"><div class="metric-card d-flex justify-content-between align-items-center"><span class="fw-bold text-muted text-danger">For Disposal</span><h4 class="m-0 fw-800 text-danger"><?php echo $count_disposal; ?></h4></div></div>
        <div class="col-md-4"><div class="metric-card d-flex justify-content-between align-items-center"><span class="fw-bold text-muted text-success">Active Assets</span><h4 class="m-0 fw-800 text-success"><?php echo $count_in_use; ?></h4></div></div>
    </div>

    <div class="data-panel">
        <div class="row g-3 mb-4 align-items-center">
            <div class="col-md-6">
                <form method="GET"><input type="text" name="search" class="form-control border-0 bg-light p-3 rounded-4" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>"></form>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn-create-item me-2" data-bs-toggle="modal" data-bs-target="#createItemModal">
                    <i class="fas fa-plus me-2"></i>Create Item
                </button>
                <button onclick="exportInventoryPDF()" class="btn btn-dark p-3 fw-bold rounded-4">
                    <i class="fas fa-file-pdf me-2"></i>Export To PDF
                </button>
            </div>
        </div>

        <div id="table-to-export">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr class="text-muted small">
                            <th>INVENTORY DATE</th>
                            <th>ASSET TAG</th>
                            <th>DETAILS</th>
                            <th>LOCATION</th>
                            <th>STATUS</th>
                            <th class="no-export text-center">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM assets";
                        if (!empty($search)) {
                            $s = mysqli_real_escape_string($conn, $search);
                            $sql .= " WHERE asset_tag LIKE '%$s%' OR brand_model LIKE '%$s%' OR serial_number LIKE '%$s%'";
                        }
                        $sql .= " ORDER BY inventory_date DESC";
                        $res = mysqli_query($conn, $sql);
                        while ($row = mysqli_fetch_assoc($res)):
                            $badge = ($row['status'] == 'For Disposal') ? 'st-disposal' : (($row['status'] == 'Replacement') ? 'st-replacement' : 'st-active');
                        ?>
                        <tr>
                            <td class="fw-bold"><?php echo date('F d, Y', strtotime($row['inventory_date'])); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo $row['asset_tag']; ?></span></td>
                            <td><b><?php echo $row['brand_model']; ?></b><br><small class="text-muted">S/N: <?php echo $row['serial_number']; ?></small></td>
                            <td><?php echo $row['location']; ?></td>
                            <td><span class="status-badge <?php echo $badge; ?>"><?php echo strtoupper($row['status']); ?></span></td>
                            <td class="no-export text-center">
                                <button class="btn btn-sm btn-outline-dark border-0 editBtn" 
                                    data-id="<?php echo $row['id']; ?>"
                                    data-tag="<?php echo $row['asset_tag']; ?>"
                                    data-serial="<?php echo $row['serial_number']; ?>"
                                    data-model="<?php echo $row['brand_model']; ?>"
                                    data-loc="<?php echo $row['location']; ?>"
                                    data-status="<?php echo $row['status']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- CREATE MODAL (ORIGINAL DESIGN) -->
<div class="modal fade" id="createItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 30px;">
            <form action="" method="POST" class="p-5">
                <h2 class="fw-800 mb-4" style="color:var(--accent-purple)">REGISTER ASSET</h2>
                <div class="row g-4">
                    <div class="col-md-12">
                        <label class="form-label-custom">Asset Tag (Optional)</label>
                        <input type="text" name="manual_tag" class="input-custom" placeholder="Leave blank to auto-generate">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-custom">Serial Number</label>
                        <input type="text" name="serial_number" class="input-custom" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-custom">Brand & Model</label>
                        <input type="text" name="brand_model" class="input-custom" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-custom">Type</label>
                        <select name="type" class="input-custom">
                            <option value="Laptop">Laptop</option>
                            <option value="Desktop">Desktop</option>
                            <option value="Monitor">Monitor</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-custom">Location</label>
                        <input type="text" name="location" class="input-custom" placeholder="e.g. Main Office" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-custom">Inventory Date</label>
                        <input type="date" name="date" class="input-custom" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-custom">Status</label>
                        <select name="status" class="input-custom">
                            <option>Active</option>
                            <option>Replacement</option>
                            <option>For Disposal</option>
                        </select>
                    </div>
                </div>
                <div class="text-center mt-5">
                    <button type="submit" name="save_asset" class="btn-create-item px-5">Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 30px;">
            <form action="" method="POST" class="p-5">
                <input type="hidden" name="asset_id" id="edit_id">
                <h2 class="fw-800 mb-4" style="color:var(--accent-purple)">UPDATE ASSET</h2>
                <div class="row g-4">
                    <div class="col-md-12">
                        <label class="form-label-custom">Asset Tag</label>
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
                        <input type="text" name="location" id="edit_loc" class="input-custom" required>
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
                <div class="text-center mt-5">
                    <button type="submit" name="update_asset" class="btn btn-primary px-5 py-3 fw-bold rounded-pill">Update Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // EDIT BUTTON HANDLER
    $('.editBtn').on('click', function() {
        $('#edit_id').val($(this).data('id'));
        $('#edit_tag').val($(this).data('tag'));
        $('#edit_serial').val($(this).data('serial'));
        $('#edit_model').val($(this).data('model'));
        $('#edit_loc').val($(this).data('loc'));
        $('#edit_status').val($(this).data('status'));
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    // CENTERED LANDSCAPE PDF EXPORT
    function exportInventoryPDF() {
        const tableHtml = document.getElementById('table-to-export').cloneNode(true);
        const actionElements = tableHtml.querySelectorAll('.no-export');
        actionElements.forEach(el => el.remove());

        const container = document.createElement('div');
        container.style.width = '1000px'; 
        container.style.margin = '0 auto';
        container.style.padding = '20px';
        container.style.backgroundColor = 'white';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.alignItems = 'center';

        const today = new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        const time = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        
        container.innerHTML = `
            <div style="width: 100%; text-align: center; border-bottom: 3px solid #7A1CAC; padding-bottom: 10px; margin-bottom: 25px; font-family: 'Plus Jakarta Sans', sans-serif;">
                <h1 style="color: #7A1CAC; margin: 0; font-size: 32px; font-weight: 800; letter-spacing: 1px;">INSPIRO RELIA INC.</h1>
                <p style="font-weight: 700; margin: 5px 0; font-size: 16px; color: #333;">COMPUTER ASSET RECORD SYSTEM</p>
                <p style="color: #8e44ad; font-weight: 800; margin: 5px 0; font-size: 18px; text-transform: uppercase;">Inventory Report</p>
                <p style="margin: 5px 0; font-size: 14px;">Inventory Date: <b>${today}</b></p>
                <div style="margin-top: 10px; font-size: 12px; color: #666;">
                    Report Generated: ${today} ${time} | By: ${'<?php echo $display_name; ?>'}
                </div>
            </div>
        `;
        
        const table = tableHtml.querySelector('table');
        table.style.width = '100%'; 
        table.style.borderCollapse = 'collapse';
        container.appendChild(tableHtml);

        const opt = {
            margin: [0.5, 0.5, 0.5, 0.5],
            filename: `Inspiro_Report_${today}.pdf`,
            image: { type: 'jpeg', quality: 1 },
            html2canvas: { scale: 2, useCORS: true, width: 1050 },
            jsPDF: { unit: 'in', format: 'a4', orientation: 'landscape' }
        };

        html2pdf().set(opt).from(container).save();
    }

    // Alerts
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('msg') === 'success_create') Swal.fire({ icon: 'success', title: 'Asset Saved!', confirmButtonColor: '#7A1CAC' });
    if(urlParams.get('msg') === 'success_update') Swal.fire({ icon: 'success', title: 'Update Successful!', confirmButtonColor: '#7A1CAC' });
</script>
</body>
</html>