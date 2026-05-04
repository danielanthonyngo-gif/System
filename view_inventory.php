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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    
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
        .btn-signout { color: var(--accent-purple); transition: all 0.2s ease; }
        .btn-signout:hover { color: #7A1CAC; text-decoration: underline !important; opacity: 0.8; }
        .qr-img-table { width: 50px; height: 50px; }
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
                <a href="logout.php" class="btn-signout small text-decoration-none fw-bold">Sign Out</a>
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
                            <th>QR CODE</th>
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
                            $qr_data = "TAG: ".$row['asset_tag']." | SN: ".$row['serial_number'];
                        ?>
                        <tr>
                            <td class="fw-bold"><?php echo date('F d, Y', strtotime($row['inventory_date'])); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo $row['asset_tag']; ?></span></td>
                            <td><canvas class="table-qr" data-value="<?php echo $qr_data; ?>" style="width:50px; height:50px;"></canvas></td>
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

<div class="modal fade" id="createItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 30px;">
            <form action="" method="POST" class="p-5">
                <div class="row">
                    <div class="col-md-9">
                        <h2 class="fw-800 mb-4" style="color:var(--accent-purple)">REGISTER ASSET</h2>
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label class="form-label-custom">Asset Tag (Optional)</label>
                                <input type="text" name="manual_tag" id="in_tag" class="input-custom" placeholder="Leave blank to auto-generate">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Serial Number</label>
                                <input type="text" name="serial_number" id="in_serial" class="input-custom" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Brand & Model</label>
                                <input type="text" name="brand_model" id="in_model" class="input-custom" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-custom">Type</label>
                                <select name="type" id="in_type" class="input-custom">
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
                    </div>
                    <div class="col-md-3 d-flex flex-column align-items-center justify-content-center border-start">
                        <label class="form-label-custom mb-3">QR Preview</label>
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 20px; border: 2px dashed #ccc;">
                            <canvas id="modal_qr_preview"></canvas>
                        </div>
                        <p class="small text-muted mt-2">Real-time Update</p>
                    </div>
                </div>
                <div class="text-center mt-5">
                    <button type="submit" name="save_asset" class="btn-create-item px-5">Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
    // 1. GENERATE TABLE QR CODES
    function generateTableQRs() {
        document.querySelectorAll('.table-qr').forEach(canvas => {
            new QRious({
                element: canvas,
                value: canvas.getAttribute('data-value'),
                size: 100
            });
        });
    }

    // 2. REAL-TIME QR PREVIEW FOR CREATE MODAL
    function updateModalQR() {
        const tag = document.getElementById('in_tag').value || "AUTO-GENERATED";
        const serial = document.getElementById('in_serial').value || "---";
        const model = document.getElementById('in_model').value || "---";
        const qrContent = `TAG: ${tag} | SN: ${serial} | MODEL: ${model}`;

        new QRious({
            element: document.getElementById('modal_qr_preview'),
            value: qrContent,
            size: 160,
            level: 'M'
        });
    }

    // Listeners for Real-time
    ['in_tag', 'in_serial', 'in_model'].forEach(id => {
        document.getElementById(id).addEventListener('input', updateModalQR);
    });

    $(document).ready(function() {
        generateTableQRs();
        updateModalQR(); // Initial preview
    });

    // 3. EDIT BUTTON HANDLER
    $('.editBtn').on('click', function() {
        $('#edit_id').val($(this).data('id'));
        $('#edit_tag').val($(this).data('tag'));
        $('#edit_serial').val($(this).data('serial'));
        $('#edit_model').val($(this).data('model'));
        $('#edit_loc').val($(this).data('loc'));
        $('#edit_status').val($(this).data('status'));
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    // 4. PDF EXPORT WITH QR IMAGES
    function exportInventoryPDF() {
        const tableHtml = document.getElementById('table-to-export').cloneNode(true);
        tableHtml.querySelectorAll('.no-export').forEach(el => el.remove());

        // Convert Canvas QR to Image QR for PDF compatibility
        const originalCanvases = document.querySelectorAll('.table-qr');
        const clonedCanvases = tableHtml.querySelectorAll('.table-qr');
        clonedCanvases.forEach((canvas, i) => {
            const img = document.createElement('img');
            img.src = originalCanvases[i].toDataURL("image/png");
            img.style.width = "45px";
            canvas.parentNode.replaceChild(img, canvas);
        });

        const container = document.createElement('div');
        container.style.padding = '20px';
        container.style.backgroundColor = 'white';
        
        const today = new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        container.innerHTML = `
            <div style="text-align: center; border-bottom: 3px solid #7A1CAC; margin-bottom: 20px; font-family: sans-serif;">
                <h1 style="color: #7A1CAC; margin:0;">INSPIRO RELIA INC.</h1>
                <p style="margin:5px 0;">COMPUTER ASSET REPORT</p>
                <p style="font-size:12px; color:#666;">Date: ${today}</p>
            </div>
        `;
        container.appendChild(tableHtml);

        const opt = {
            margin: 0.3,
            filename: `Inventory_Report_${today}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
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