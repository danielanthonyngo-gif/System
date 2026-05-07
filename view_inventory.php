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
$stmt = $conn->prepare("SELECT fullname, role FROM users WHERE id = ?");
$stmt->bind_param("i", $current_uid);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

$display_name = $user_data['fullname'] ?? "User"; 
$user_role = $_SESSION['role'] ?? $user_data['role'] ?? "OJT"; 

// --- 2. FILTER & SEARCH LOGIC ---
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status_filter'] ?? '';
$filter_type = $_GET['type_filter'] ?? '';

// --- 3. UPDATE ASSET LOGIC (Admin Only) ---
if (isset($_POST['update_asset']) && $user_role === 'Administrator') {
    $stmt = $conn->prepare("UPDATE assets SET asset_tag=?, serial_number=?, brand_model=?, location=?, status=? WHERE id=?");
    $stmt->bind_param("sssssi", $_POST['asset_tag'], $_POST['serial_number'], $_POST['brand_model'], $_POST['location'], $_POST['status'], $_POST['asset_id']);
    $stmt->execute();
    header("Location: view_inventory.php?msg=success_update");
    exit();
}

// --- 4. CREATE ASSET LOGIC ---
if (isset($_POST['save_asset'])) {
    $type = $_POST['type'];
    $date = $_POST['date'];
    $serial = $_POST['serial_number'];
    $model = $_POST['brand_model'];
    $loc = $_POST['location'];
    $status = $_POST['status'];
    $asset_tag = !empty($_POST['manual_tag']) ? $_POST['manual_tag'] : "AST-" . strtoupper(substr($type, 0, 1)) . "-" . rand(1000, 9999);

    $stmt = $conn->prepare("INSERT INTO assets (inventory_date, asset_tag, serial_number, brand_model, asset_type, location, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $date, $asset_tag, $serial, $model, $type, $loc, $status);
    $stmt->execute();
    header("Location: view_inventory.php?msg=success_create");
    exit();
}

// --- 5. DELETE ASSET LOGIC (Admin Only) ---
if (isset($_GET['delete_id']) && $user_role === 'Administrator') {
    $stmt = $conn->prepare("DELETE FROM assets WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete_id']);
    $stmt->execute();
    header("Location: view_inventory.php?msg=success_delete");
    exit();
}

function getAssetCount($conn, $status) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM assets WHERE status=?");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res['total'] ?? 0;
}

$count_active = getAssetCount($conn, 'Active');
$count_replacement = getAssetCount($conn, 'Replacement');
$count_disposal = getAssetCount($conn, 'For Disposal');
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
        
        .glass-header { background: white; border-radius: 20px; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 30px; }
        .metric-card { background: white; border-radius: 18px; padding: 20px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .metric-val { font-size: 1.5rem; font-weight: 800; }
        .data-panel { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }

        /* Filter Styles */
        .filter-dropdown .btn-filter { background: white; border: 1px solid #e0e0e0; border-radius: 10px; padding: 10px 18px; font-weight: 600; color: #555; display: flex; align-items: center; gap: 8px; transition: all 0.2s; }
        
        /* Fixed Buttons Styling */
        .hover-lift { transition: all 0.3s ease; }
        .hover-lift:hover { transform: translateY(-2px); box-shadow: 0 8px 15px rgba(0,0,0,0.1) !important; filter: brightness(1.1); }
        .hover-lift:active { transform: translateY(0); }

        /* Status Badges */
        .status-badge { padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.65rem; text-transform: uppercase; }
        .st-active { background: #E9D5FF; color: #7A1CAC; }
        .st-disposal { background: #FEE2E2; color: #DC2626; }
        .st-replacement { background: #FEF3C7; color: #D97706; }

        .input-custom { border-radius: 12px; padding: 12px 15px; border: 1.5px solid #eee; background: #fafafa; font-weight: 600; font-size: 0.9rem; width: 100%; transition: 0.3s; }
        .input-custom:focus { border-color: var(--inspiro-purple); outline: none; background: #fff; }
        .form-label-custom { font-weight: 700; color: #666; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 6px; display: block; }
        
        .qr-btn { transition: transform 0.2s; cursor: pointer; border: 1px solid #eee; padding: 5px; border-radius: 8px; background: white; }
        .qr-btn:hover { transform: scale(1.1); border-color: var(--inspiro-purple); }

        @media (max-width: 992px) {
            .content-wrapper { margin-left: 0; padding: 15px; }
            .glass-header { flex-direction: column; text-align: center; gap: 15px; }
        }
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
            <!-- Search & Filters -->
            <div class="col-lg-7">
                <form method="GET" id="filterForm" class="d-flex flex-wrap gap-2">
                    <div class="position-relative flex-grow-1" style="min-width: 250px;">
                        <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                        <input type="text" name="search" class="form-control border-0 bg-light p-3 ps-5 rounded-4 shadow-sm" 
                               placeholder="Search Tag, Model, or Serial..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <input type="hidden" name="status_filter" id="status_filter_input" value="<?php echo $filter_status; ?>">
                    <input type="hidden" name="type_filter" id="type_filter_input" value="<?php echo $filter_type; ?>">

                    <div class="dropdown filter-dropdown">
                        <button class="btn btn-filter shadow-sm dropdown-toggle h-100" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-filter text-primary"></i> 
                            Filter: <?php echo (!empty($filter_status) ? $filter_status : (!empty($filter_type) ? $filter_type : "All")); ?>
                        </button>
                        <ul class="dropdown-menu shadow">
                            <li><h6 class="dropdown-header">Status</h6></li>
                            <li><a class="dropdown-item" href="#" onclick="applyFilter('status', '')">All Status</a></li>
                            <li><a class="dropdown-item" href="#" onclick="applyFilter('status', 'Active')">Active</a></li>
                            <li><a class="dropdown-item" href="#" onclick="applyFilter('status', 'Replacement')">Replacement</a></li>
                            <li><a class="dropdown-item" href="#" onclick="applyFilter('status', 'For Disposal')">For Disposal</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Asset Type</h6></li>
                            <li><a class="dropdown-item" href="#" onclick="applyFilter('type', 'Laptop')">Laptops</a></li>
                            <li><a class="dropdown-item" href="#" onclick="applyFilter('type', 'Desktop')">Desktops</a></li>
                            <li><a class="dropdown-item" href="#" onclick="applyFilter('type', 'Monitor')">Monitors</a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-light rounded-4 px-3" onclick="window.location.href='view_inventory.php'"><i class="fas fa-redo"></i></button>
                </form>
            </div>

            <!-- Action Buttons (NEW ASSET & PDF) -->
            <div class="col-lg-5">
                <div class="d-flex justify-content-lg-end gap-2">
                    <button class="btn d-flex align-items-center justify-content-center hover-lift shadow-sm" 
                            style="background:var(--inspiro-purple); color:white; padding: 12px 20px; border-radius: 14px; font-weight: 700;" 
                            data-bs-toggle="modal" data-bs-target="#createItemModal">
                        <i class="fas fa-plus-circle me-2"></i>New Asset
                    </button>
                    <button onclick="exportInventoryPDF()" 
                            class="btn btn-dark d-flex align-items-center justify-content-center hover-lift shadow-sm" 
                            style="padding: 12px 20px; border-radius: 14px; font-weight: 700;">
                        <i class="fas fa-file-pdf me-2 text-danger"></i>Export PDF
                    </button>
                </div>
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
                            <?php if ($user_role === 'Administrator'): ?>
                            <th class="no-export text-center">ACTION</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM assets WHERE 1=1";
                        if (!empty($filter_status)) $query .= " AND status = '" . mysqli_real_escape_string($conn, $filter_status) . "'";
                        if (!empty($filter_type)) $query .= " AND asset_type = '" . mysqli_real_escape_string($conn, $filter_type) . "'";
                        if (!empty($search)) {
                            $s = mysqli_real_escape_string($conn, $search);
                            $query .= " AND (asset_tag LIKE '%$s%' OR brand_model LIKE '%$s%' OR serial_number LIKE '%$s%')";
                        }
                        $query .= " ORDER BY inventory_date DESC";
                        $res = mysqli_query($conn, $query);
                        
                        while ($row = mysqli_fetch_assoc($res)):
                            $badge = ($row['status'] == 'For Disposal') ? 'st-disposal' : (($row['status'] == 'Replacement') ? 'st-replacement' : 'st-active');
                            $qr_data = "TAG: ".$row['asset_tag']." | SN: ".$row['serial_number'];
                        ?>
                        <tr>
                            <td class="small fw-600"><?php echo date('M d, Y', strtotime($row['inventory_date'])); ?></td>
                            <td><span class="badge bg-light text-dark fw-bold border"><?php echo $row['asset_tag']; ?></span></td>
                            <td>
                                <button type="button" class="qr-btn" onclick="viewQR('<?php echo $qr_data; ?>', '<?php echo $row['asset_tag']; ?>')">
                                    <canvas class="table-qr" data-value="<?php echo $qr_data; ?>" style="width:40px; height:40px;"></canvas>
                                </button>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo $row['brand_model']; ?></div>
                                <div class="small text-muted">SN: <?php echo $row['serial_number']; ?></div>
                            </td>
                            <td class="small"><?php echo $row['location']; ?></td>
                            <td><span class="status-badge <?php echo $badge; ?>"><?php echo $row['status']; ?></span></td>

                             <?php if ($user_role === 'Administrator'): ?>
                                <td class="no-export text-center">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary border-0 editBtn" 
                                            data-id="<?php echo $row['id']; ?>"
                                            data-tag="<?php echo $row['asset_tag']; ?>"
                                            data-serial="<?php echo $row['serial_number']; ?>"
                                            data-model="<?php echo $row['brand_model']; ?>"
                                            data-loc="<?php echo $row['location']; ?>"
                                            data-status="<?php echo $row['status']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger border-0" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
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

<!-- Modal: Create Asset -->
<div class="modal fade" id="createItemModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
            <form action="" method="POST" class="p-4">
                <div class="row">
                    <div class="col-md-8 pe-md-4">
                        <h3 class="fw-800 mb-4" style="color:var(--inspiro-purple)">Register New Asset</h3>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label-custom">Asset Tag (Blank for Auto)</label>
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
                                <label class="form-label-custom">Type</label>
                                <select name="type" class="input-custom">
                                    <option>Laptop</option><option>Desktop</option><option>Monitor</option><option>Printer</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">Location</label>
                                <input type="text" name="location" class="input-custom" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">Status</label>
                                <select name="status" class="input-custom">
                                    <option>Active</option><option>Replacement</option><option>For Disposal</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label-custom">Date Received</label>
                                <input type="date" name="date" class="input-custom" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex flex-column align-items-center justify-content-center border-start bg-light rounded-end-5 mt-md-0 mt-4 p-4">
                        <label class="form-label-custom mb-3">QR Preview</label>
                        <div class="bg-white p-3 rounded-4 shadow-sm">
                            <canvas id="modal_qr_preview"></canvas>
                        </div>
                    </div>
                </div>
                <div class="text-end mt-4">
                    <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_asset" class="btn px-5 py-2 fw-bold ms-2 rounded-pill shadow" style="background:var(--inspiro-purple); color:white;">Save Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Update Asset -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
            <form action="" method="POST" class="p-4">
                <input type="hidden" name="asset_id" id="edit_id">
                <h3 class="fw-800 mb-4" style="color:var(--inspiro-purple)">Update Asset</h3>
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
                            <option>Active</option><option>Replacement</option><option>For Disposal</option>
                        </select>
                    </div>
                </div>
                <div class="text-end mt-5">
                    <button type="submit" name="update_asset" class="btn btn-dark px-5 py-2 fw-bold rounded-pill shadow">Update Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function applyFilter(type, value) {
        if (type === 'status') document.getElementById('status_filter_input').value = value;
        if (type === 'type') document.getElementById('type_filter_input').value = value;
        document.getElementById('filterForm').submit();
    }

    function generateTableQRs() {
        document.querySelectorAll('.table-qr').forEach(canvas => {
            new QRious({ element: canvas, value: canvas.getAttribute('data-value'), size: 120 });
        });
    }

    function updateModalQR() {
        const tag = document.getElementById('in_tag').value || "PREVIEW";
        const serial = document.getElementById('in_serial').value || "---";
        new QRious({ element: document.getElementById('modal_qr_preview'), value: `TAG: ${tag} | SN: ${serial}`, size: 200 });
    }

    function viewQR(data, tag) {
        Swal.fire({
            title: `<span style="color:#7A1CAC">Asset Tag: ${tag}</span>`,
            html: `
                <div style="background:#fff; padding:20px; display:inline-block; border-radius:15px; border:2px solid #7A1CAC;">
                    <canvas id="swal_qr"></canvas>
                </div>
                <p class="mt-3 fw-bold text-muted small">${data}</p>
            `,
            showCloseButton: true,
            showConfirmButton: false,
            didOpen: () => {
                new QRious({ element: document.getElementById('swal_qr'), value: data, size: 250 });
            }
        });
    }

    ['in_tag', 'in_serial'].forEach(id => document.getElementById(id)?.addEventListener('input', updateModalQR));

    $(document).ready(function() {
        generateTableQRs();
        updateModalQR();

        $('.editBtn').on('click', function() {
            $('#edit_id').val($(this).data('id'));
            $('#edit_tag').val($(this).data('tag'));
            $('#edit_serial').val($(this).data('serial'));
            $('#edit_model').val($(this).data('model'));
            $('#edit_loc').val($(this).data('loc'));
            $('#edit_status').val($(this).data('status'));
            new bootstrap.Modal(document.getElementById('editModal')).show();
        });
    });

    function confirmDelete(id) {
        Swal.fire({
            title: 'Delete Asset?',
            text: "This cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#7A1CAC',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = `view_inventory.php?delete_id=${id}`;
        });
    }

    function exportInventoryPDF() {
        const tableHtml = document.getElementById('table-to-export').cloneNode(true);
        tableHtml.querySelectorAll('.no-export').forEach(el => el.remove());
        
        const originalCanvases = document.querySelectorAll('.table-qr');
        tableHtml.querySelectorAll('.table-qr').forEach((canvas, i) => {
            const img = document.createElement('img');
            img.src = originalCanvases[i].toDataURL("image/png");
            img.style.width = "40px";
            canvas.parentNode.replaceChild(img, canvas);
        });

        const container = document.createElement('div');
        container.style.padding = '30px';
        container.innerHTML = `<h2 style="color:#7A1CAC; text-align:center; border-bottom:2px solid #7A1CAC; padding-bottom:10px;">INSPIRO RELIA INC. ASSET REPORT</h2>`;
        container.appendChild(tableHtml);

        html2pdf().set({ 
            margin: 0.5, 
            filename: 'Inspiro_Inventory.pdf', 
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { format: 'a4', orientation: 'landscape' } 
        }).from(container).save();
    }

    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    const toastConfig = { toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 };
    
    if(msg === 'success_create') Swal.fire({ ...toastConfig, icon: 'success', title: 'Asset Created' });
    if(msg === 'success_update') Swal.fire({ ...toastConfig, icon: 'success', title: 'Asset Updated' });
    if(msg === 'success_delete') Swal.fire({ ...toastConfig, icon: 'error', title: 'Asset Deleted' });
</script>
</body>
</html>