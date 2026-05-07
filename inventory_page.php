<?php
session_start();
include 'config.php';

// 1. AUTHENTICATION CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. AJAX LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];

    if ($action == 'add') {
        $location = mysqli_real_escape_string($conn, $_POST['location']);
        $tag    = mysqli_real_escape_string($conn, $_POST['asset_tag']);
        $sn     = mysqli_real_escape_string($conn, $_POST['serial_number']);
        $model  = mysqli_real_escape_string($conn, $_POST['brand_model']);
        $type   = mysqli_real_escape_string($conn, $_POST['asset_type']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        $query = "INSERT INTO assets (asset_tag, serial_number, brand_model, asset_type, status, location) 
                  VALUES ('$tag', '$sn', '$model', '$type', '$status', '$location')";
        
        if (mysqli_query($conn, $query)) echo json_encode(['success' => true]);
        else echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        exit();
    }

    if ($action == 'update') {
        $id      = mysqli_real_escape_string($conn, $_POST['id']);
        $model   = mysqli_real_escape_string($conn, $_POST['brand_model']);
        $type    = mysqli_real_escape_string($conn, $_POST['asset_type']);
        $status  = mysqli_real_escape_string($conn, $_POST['status']);
        $new_loc = mysqli_real_escape_string($conn, $_POST['location']); 

        $query = "UPDATE assets SET brand_model='$model', asset_type='$type', status='$status', location='$new_loc' WHERE id='$id'";
        
        if (mysqli_query($conn, $query)) echo json_encode(['success' => true]);
        else echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        exit();
    }
}

// 3. PAGE DATA
$location = isset($_GET['location']) ? mysqli_real_escape_string($conn, $_GET['location']) : 'BDO';
$active_query = mysqli_query($conn, "SELECT COUNT(*) as t FROM assets WHERE location = '$location' AND status = 'Active'");
$active = mysqli_fetch_assoc($active_query)['t'] ?? 0;
$assets = mysqli_query($conn, "SELECT * FROM assets WHERE location = '$location' ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tracking | <?php echo htmlspecialchars($location); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    
    <style>
        :root { 
            --app-bg: #f8f7ff;
            --main-gradient: linear-gradient(135deg, #7A1CAC 0%, #7A1CAC 100%);
            --sidebar-width: 260px;
            --accent-purple: #2E073F;
        }
        body { background-color: var(--app-bg); font-family: 'Plus Jakarta Sans', sans-serif; color: #2d3436; margin: 0; }
        .content-wrapper { margin-left: var(--sidebar-width); padding: 1.5rem; min-height: 100vh; transition: 0.3s; }
        .glass-header { background: white; border-radius: 20px; padding: 1.2rem 2rem; box-shadow: 0 10px 30px rgba(111,66,193,0.05); margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; }
        .header-title h4 { font-weight: 800; color: var(--accent-purple); text-transform: uppercase; }
        .stat-card-modern { background: white; border-radius: 20px; padding: 1.5rem; display: flex; align-items: center; gap: 1rem; border: 1px solid #f1f0f7; }
        .icon-box { width: 50px; height: 50px; border-radius: 15px; background: var(--main-gradient); color: white; display: flex; align-items: center; justify-content: center; }
        .table-card { background: white; border-radius: 25px; padding: 1.5rem; border: 1px solid #f1f0f7; }
        .search-bar { padding: 12px 45px; border-radius: 12px; border: 1px solid #e2e8f0; background: #fcfaff; width: 100%; font-weight: 600; }
        .btn-purple { background: var(--main-gradient); border: none; color: white; border-radius: 12px; padding: 10px 25px; font-weight: 700; }
        .badge-status { padding: 6px 14px; border-radius: 10px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
        .status-active { background: #ecfdf5; color: #059669; }
        .status-replacement { background: #fffbeb; color: #d97706; }
        .status-disposal { background: #fef2f2; color: #dc2626; }
        .status-pulledout { background: #f3f4f6; color: #4b5563; }
        .modal-content { border-radius: 25px; border: none; }
        .form-control, .form-select { border-radius: 10px; padding: 10px; }
        @media (max-width: 992px) { .content-wrapper { margin-left: 0; } }
    </style>
</head>
<body>

    <?php include 'aside.php'; ?>
    
    <div class="content-wrapper">
        <div class="glass-header">
            <div class="header-title">
                <h4 class="m-0"><?php echo htmlspecialchars($location); ?> <span>INVENTORY</span></h4>
                <small class="text-muted fw-600">Asset Management System</small>
            </div>
            <div class="user-profile-box text-end">
                <div class="fw-700"><?php echo $_SESSION['user'] ?? 'Admin'; ?></div>
                <a href="logout.php" class="text-danger fw-800 small text-decoration-none">SIGN OUT</a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card-modern">
                    <div class="icon-box"><i class="fas fa-desktop"></i></div>
                    <div>
                        <small class="text-muted fw-800 text-uppercase" style="font-size: 0.65rem;">Active Assets</small>
                        <h3 class="m-0 fw-800"><?php echo $active; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="row g-3 mb-4 align-items-center">
                <div class="col-md-5">
                    <div class="position-relative">
                        <i class="fas fa-search position-absolute" style="left: 18px; top: 15px; color: #b4b6c4;"></i>
                        <input type="text" id="assetSearch" class="search-bar" placeholder="Search tag, serial, or model...">
                    </div>
                </div>
                <div class="col-md-7 text-end">
                    <a href="view_area.php" class="btn btn-light border rounded-pill px-4 me-2 fw-700">Back</a>
                    <button class="btn btn-purple shadow-sm" data-bs-toggle="modal" data-bs-target="#newAssetModal">
                        <i class="fas fa-plus me-2"></i>New Asset
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="text-muted small fw-800 text-uppercase">
                            <th>Asset Tag</th>
                            <th>QR</th>
                            <th>Device Details</th>
                            <th>Type</th>
                            <th>Current Area</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($assets)): 
                            $qr_data = "TAG: ".$row['asset_tag']." | SN: ".$row['serial_number'];
                        ?>
                        <tr class="asset-row">
                            <td class="fw-800"><?php echo $row['asset_tag']; ?></td>
                            <td>
                                <button type="button" class="btn p-1 border rounded" onclick="viewQR('<?php echo $qr_data; ?>', '<?php echo $row['asset_tag']; ?>')">
                                    <canvas class="table-qr" data-value="<?php echo $qr_data; ?>" style="width:30px; height:30px;"></canvas>
                                </button>
                            </td>
                            <td>
                                <div class="fw-800 text-primary small"><?php echo $row['brand_model']; ?></div>
                                <div class="text-muted smaller"><?php echo $row['serial_number']; ?></div>
                            </td>
                            <td class="fw-700 text-muted small"><?php echo $row['asset_type']; ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo $row['location']; ?></span></td>
                            <td class="text-center">
                                <?php 
                                    $s = $row['status'];
                                    $c = ($s=='Active')?'status-active':(($s=='Replacement')?'status-replacement':(($s=='For Disposal')?'status-disposal':'status-pulledout'));
                                ?>
                                <span class="badge-status <?php echo $c; ?>"><?php echo $s; ?></span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-dark rounded-pill px-3 edit-btn" 
                                    data-id="<?php echo $row['id']; ?>"
                                    data-tag="<?php echo $row['asset_tag']; ?>"
                                    data-model="<?php echo $row['brand_model']; ?>"
                                    data-status="<?php echo $row['status']; ?>"
                                    data-type="<?php echo $row['asset_type']; ?>"
                                    data-location="<?php echo $row['location']; ?>">Edit</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- EDIT MODAL (Updated Location Names) -->
    <div class="modal fade" id="editAssetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editAssetForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-800 text-primary">Update Asset Location</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12"><label class="fw-700 small">Asset Tag</label><input type="text" id="edit_tag" class="form-control bg-light" readonly></div>
                            <div class="col-12"><label class="fw-700 small">Brand & Model</label><input type="text" name="brand_model" id="edit_model" class="form-control" required></div>
                            
                            <div class="col-md-6">
                                <label class="fw-700 small">Asset Type</label>
                                <select name="asset_type" id="edit_type" class="form-select">
                                    <option>Desktop</option><option>Laptop</option><option>Monitor</option><option>UPS</option><option>Printer</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-700 small">Status</label>
                                <select name="status" id="edit_status" class="form-select">
                                    <option>Active</option><option>Replacement</option><option>For Disposal</option><option>In Storage</option>
                                </select>
                            </div>

                            <!-- DITO YUNG MGA NAMES MULA SA SCREENSHOT MO -->
                            <div class="col-12">
                                <label class="fw-700 small text-purple" style="color: #7A1CAC;">Transfer to New Area</label>
                                <select name="location" id="edit_location" class="form-select border-primary" style="background-color: #fcfaff;">
                                    <optgroup label="ALPHA BUILDING">
                                        <option value="BDO">BDO</option>
                                        <option value="BDO INSURE">BDO INSURE</option>
                                        <option value="BDO LIFE">BDO LIFE</option>
                                        <option value="PACSAN">PACSAN</option>
                                        <option value="BDO CORE">BDO CORE</option>
                                        <option value="FLIGHT CENTER">FLIGHT CENTER</option>
                                        <option value="MANILA DOCTOR'S HOSPITAL">MANILA DOCTOR'S HOSPITAL</option>
                                        <option value="IGNITE">IGNITE</option>
                                        <option value="VIAGOGO">VIAGOGO</option>
                                        <option value="ALPHA STORAGE">ALPHA STORAGE</option>
                                    </optgroup>
                                    <optgroup label="BETA BUILDING">
                                        <option value="GRAB SUPPORT">GRAB SUPPORT</option>
                                        <option value="GRAB COE">GRAB COE</option>
                                        <option value="SHARK NINJA">SHARK NINJA</option>
                                        <option value="HALLMARK">HALLMARK</option>
                                        <option value="ANA">ANA</option>
                                        <option value="AUB">AUB</option>
                                        <option value="AUB">BETA STORAGE</option>
                                    </optgroup>
                                </select>
                                <div class="mt-2 text-muted" style="font-size: 0.7rem;">*Kapag pinalitan, automatic na malilipat ang asset sa database area na napili.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-purple px-4">Update Asset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- NEW ASSET MODAL -->
    <div class="modal fade" id="newAssetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="addAssetForm">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="location" value="<?php echo htmlspecialchars($location); ?>">
                    <div class="modal-header border-0 pb-0"><h5 class="fw-800">New Registration</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body pt-2">
                        <div class="row g-3">
                            <div class="col-12"><label class="small fw-700">Tag</label><input type="text" name="asset_tag" class="form-control" required></div>
                            <div class="col-12"><label class="small fw-700">Serial</label><input type="text" name="serial_number" class="form-control" required></div>
                            <div class="col-12"><label class="small fw-700">Brand/Model</label><input type="text" name="brand_model" class="form-control" required></div>
                            <div class="col-md-6"><label class="small fw-700">Type</label><select name="asset_type" class="form-select"><option>Desktop</option><option>Laptop</option><option>Monitor</option></select></div>
                            <div class="col-md-6"><label class="small fw-700">Status</label><select name="status" class="form-select"><option>Active</option></select></div>
                        </div>
                    </div>
                    <div class="modal-footer border-0"><button type="submit" class="btn btn-purple w-100">Save Asset</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewQR(data, tag) {
            Swal.fire({
                title: `QR: ${tag}`,
                html: `<div class="p-3 bg-white d-inline-block border rounded"><canvas id="popup_qr"></canvas></div>`,
                didOpen: () => { new QRious({ element: document.getElementById('popup_qr'), value: data, size: 200 }); }
            });
        }

        function initTableQRs() {
            document.querySelectorAll('.table-qr').forEach(canvas => {
                new QRious({ element: canvas, value: canvas.getAttribute('data-value'), size: 80 });
            });
        }
        window.onload = initTableQRs;

        document.getElementById('assetSearch').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            document.querySelectorAll('.asset-row').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
            });
        });

        // Click Edit Button
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-btn')) {
                const btn = e.target;
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_tag').value = btn.dataset.tag;
                document.getElementById('edit_model').value = btn.dataset.model;
                document.getElementById('edit_status').value = btn.dataset.status;
                document.getElementById('edit_type').value = btn.dataset.type;
                document.getElementById('edit_location').value = btn.dataset.location; 
                
                new bootstrap.Modal(document.getElementById('editAssetModal')).show();
            }
        });

        // Submit Update
        document.getElementById('editAssetForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch(window.location.href, { method: 'POST', body: new FormData(this) })
            .then(res => res.json()).then(data => { 
                if(data.success) {
                    Swal.fire({icon:'success', title:'Asset Moved/Updated', showConfirmButton:false, timer:1000}).then(() => location.reload());
                } else { alert(data.message); }
            });
        });

        // Submit New
        document.getElementById('addAssetForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch(window.location.href, { method: 'POST', body: new FormData(this) })
            .then(res => res.json()).then(data => { if(data.success) location.reload(); });
        });
    </script>
</body>
</html>