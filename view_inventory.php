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

$display_name = $user_data['fullname'] ?? "Angelo Vicente"; 
$user_role = $user_data['role'] ?? "OJT"; 

// --- 2. FILTER & SEARCH LOGIC ---
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status_filter'] ?? '';
$filter_type = $_GET['type_filter'] ?? '';

// --- 3. UPDATE ASSET LOGIC ---
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

// --- 4. CREATE ASSET LOGIC ---
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

// --- 5. COUNTERS ---
$count_replacement = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assets WHERE status='Replacement'"))['total'] ?? 0;
$count_disposal = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assets WHERE status='For Disposal'"))['total'] ?? 0;
$count_active = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assets WHERE status='Active'"))['total'] ?? 0;
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
            --app-bg: #f8f9fd; 
            --inspiro-purple: #7A1CAC; 
            --sidebar-width: 260px; 
        }
        body { background-color: var(--app-bg); font-family: 'Plus Jakarta Sans', sans-serif; color: #2d3436; }
        .content-wrapper { margin-left: var(--sidebar-width); padding: 30px; min-height: 100vh; }
        
        /* Dashboard Header */
        .glass-header { background: white; border-radius: 20px; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 30px; }
        
        /* Metric Cards */
        .metric-card { background: white; border-radius: 18px; padding: 20px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .metric-val { font-size: 1.5rem; font-weight: 800; }
        
        /* Data Panel */
        .data-panel { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }

        /* PURPLE FILTER DROPDOWN (Based on Image) */
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
            padding: 10px 15px 5px;
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
    </style>
</head>
<body>

<?php include 'aside.php'; ?>

<div class="content-wrapper">
    <!-- Header -->
    <div class="glass-header">
        <div>
            <h4 class="fw-800 mb-0" style="color:var(--inspiro-purple)">INVENTORY MANAGEMENT</h4>
            <p class="text-muted small mb-0">Inspiro Relia Inc. Asset Tracking System</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end">
                <div class="fw-bold text-dark small"><?php echo htmlspecialchars($display_name); ?></div>
                <a href="logout.php" class="text-decoration-none small fw-bold" style="color:var(--inspiro-purple)">Logout</a>
            </div>
            <div style="width:45px; height:45px; background:var(--inspiro-purple); color:white; border-radius:12px; display:flex; align-items:center; justify-content:center; font-weight:800;">
                <?php echo strtoupper(substr($display_name, 0, 1)); ?>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="metric-card d-flex justify-content-between align-items-center"><span class="text-muted fw-bold small">ACTIVE</span><span class="metric-val text-primary"><?php echo $count_active; ?></span></div></div>
        <div class="col-md-4"><div class="metric-card d-flex justify-content-between align-items-center"><span class="text-muted fw-bold small">REPLACEMENT</span><span class="metric-val text-warning"><?php echo $count_replacement; ?></span></div></div>
        <div class="col-md-4"><div class="metric-card d-flex justify-content-between align-items-center"><span class="text-muted fw-bold small">FOR DISPOSAL</span><span class="metric-val text-danger"><?php echo $count_disposal; ?></span></div></div>
    </div>

    <!-- Main Table Panel -->
    <div class="data-panel">
        <div class="row g-3 mb-4 align-items-center">
            <div class="col-md-7">
                <form method="GET" id="filterForm" class="d-flex gap-3">
                    <!-- Search bar -->
                    <div class="position-relative flex-grow-1">
                        <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                        <input type="text" name="search" class="form-control border-0 bg-light p-3 ps-5 rounded-4 shadow-sm" 
                               placeholder="Search Tag, Model, or Serial..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <input type="hidden" name="status_filter" id="status_filter_input" value="<?php echo $filter_status; ?>">
                    <input type="hidden" name="type_filter" id="type_filter_input" value="<?php echo $filter_type; ?>">

                    <!-- IMAGE-STYLE PURPLE DROPDOWN -->
                    <div class="dropdown filter-dropdown">
                        <button class="btn btn-filter dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-filter text-primary"></i> 
                            Filter:<?php 
                                if(!empty($filter_status)) echo $filter_status;
                                elseif(!empty($filter_type)) echo $filter_type;
                                else echo "All"; 
                            ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header">By Status</h6></li>
                            <li><a class="dropdown-item <?php echo $filter_status == '' ? 'active' : ''; ?>" href="#" onclick="applyFilter('status', '')">All Status</a></li>
                            <li><a class="dropdown-item <?php echo $filter_status == 'Active' ? 'active' : ''; ?>" href="#" onclick="applyFilter('status', 'Active')">Active</a></li>
                            <li><a class="dropdown-item <?php echo $filter_status == 'Replacement' ? 'active' : ''; ?>" href="#" onclick="applyFilter('status', 'Replacement')">Replacement</a></li>
                            <li><a class="dropdown-item <?php echo $filter_status == 'For Disposal' ? 'active' : ''; ?>" href="#" onclick="applyFilter('status', 'For Disposal')">For Disposal</a></li>
                            
                            <li><hr class="dropdown-divider"></li>
                            
                            <li><h6 class="dropdown-header">By Type</h6></li>
                            <li><a class="dropdown-item <?php echo $filter_type == 'Laptop' ? 'active' : ''; ?>" href="#" onclick="applyFilter('type', 'Laptop')">Laptops</a></li>
                            <li><a class="dropdown-item <?php echo $filter_type == 'Desktop' ? 'active' : ''; ?>" href="#" onclick="applyFilter('type', 'Desktop')">Desktops</a></li>
                            <li><a class="dropdown-item <?php echo $filter_type == 'Monitor' ? 'active' : ''; ?>" href="#" onclick="applyFilter('type', 'Monitor')">Monitors</a></li>
                        </ul>
                    </div>
                </form>
            </div>
            <div class="col-md-5 text-end">
                <button class="btn p-3 px-4 rounded-4 fw-bold me-2" style="background:var(--inspiro-purple); color:white;" data-bs-toggle="modal" data-bs-target="#createItemModal">
                    <i class="fas fa-plus me-2"></i>New Asset
                </button>
                <button onclick="exportInventoryPDF()" class="btn btn-dark p-3 px-4 rounded-4 fw-bold">
                    <i class="fas fa-file-pdf me-2"></i>Export PDF
                </button>
            </div>
        </div>

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
                            <?php endif; ?>


                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM assets WHERE 1=1";
                        if (!empty($filter_status)) { $f = mysqli_real_escape_string($conn, $filter_status); $sql .= " AND status = '$f'"; }
                        if (!empty($filter_type)) { $t = mysqli_real_escape_string($conn, $filter_type); $sql .= " AND asset_type = '$t'"; }
                        if (!empty($search)) { $s = mysqli_real_escape_string($conn, $search); $sql .= " AND (asset_tag LIKE '%$s%' OR brand_model LIKE '%$s%' OR serial_number LIKE '%$s%')"; }
                        $sql .= " ORDER BY inventory_date DESC";
                        $res = mysqli_query($conn, $sql);
                        
                        while ($row = mysqli_fetch_assoc($res)):
                            $badge = ($row['status'] == 'For Disposal') ? 'st-disposal' : (($row['status'] == 'Replacement') ? 'st-replacement' : 'st-active');
                        ?>
                        <tr>
                            <td class="small fw-600"><?php echo date('M d, Y', strtotime($row['inventory_date'])); ?></td>
                            <td><span class="badge bg-light text-dark fw-bold border"><?php echo $row['asset_tag']; ?></span></td>
                            <td><canvas class="table-qr" data-value="<?php echo "TAG: ".$row['asset_tag']." | SN: ".$row['serial_number']; ?>" style="width:45px; height:45px;"></canvas></td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo $row['brand_model']; ?></div>
                                <div class="small text-muted">SN: <?php echo $row['serial_number']; ?></div>
                            </td>
                            <td class="small"><?php echo $row['location']; ?></td>
                            <td><span class="status-badge <?php echo $badge; ?>"><?php echo $row['status']; ?></span></td>

                             <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Administrator'): ?>
                                <td class="no-export text-center">
                                    <button class="btn btn-sm btn-outline-secondary border-0 editBtn" 
                                        data-id="<?php echo $row['id']; ?>"
                                        data-tag="<?php echo $row['asset_tag']; ?>"
                                        data-serial="<?php echo $row['serial_number']; ?>"
                                        data-model="<?php echo $row['brand_model']; ?>"
                                        data-loc="<?php echo $row['location']; ?>"
                                        data-status="<?php echo $row['status']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
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

<!-- Modal: New Asset (With Real-time QR) -->
<div class="modal fade" id="createItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
            <form action="" method="POST" class="p-4">
                <div class="row">
                    <div class="col-md-8 pe-4">
                        <h3 class="fw-800 mb-4" style="color:var(--inspiro-purple)">Register New Asset</h3>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label-custom">Asset Tag (Leave blank for Auto)</label>
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
                                <input type="text" name="location" class="input-custom" required>
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
                    <button type="submit" name="save_asset" class="btn px-5 py-2 fw-bold ms-2" style="background:var(--inspiro-purple); color:white;">Save Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Update Asset -->
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
                <div class="text-end mt-5">
                    <button type="submit" name="update_asset" class="btn btn-dark px-5 py-2 fw-bold rounded-pill">Apply Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // 1. FILTER FUNCTION
    function applyFilter(type, value) {
        if (type === 'status') document.getElementById('status_filter_input').value = value;
        if (type === 'type') document.getElementById('type_filter_input').value = value;
        document.getElementById('filterForm').submit();
    }

    // 2. GENERATE TABLE QR CODES
    function generateTableQRs() {
        document.querySelectorAll('.table-qr').forEach(canvas => {
            new QRious({ element: canvas, value: canvas.getAttribute('data-value'), size: 120 });
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
        $('#edit_loc').val($(this).data('loc'));
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
    if(urlParams.get('msg') === 'success_create') Swal.fire({ icon: 'success', title: 'Asset Added!', showConfirmButton: false, timer: 1500 });
    if(urlParams.get('msg') === 'success_update') Swal.fire({ icon: 'success', title: 'Record Updated!', showConfirmButton: false, timer: 1500 });
</script>
</body>
</html>