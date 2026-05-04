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
        :root { --app-bg: #f4f7fe; --main-gradient: linear-gradient(135deg, #7A1CAC 0%, #7A1CAC 100%); --accent-purple: #2E073F; --accent-pink: #7A1CAC; --sidebar-width: 260px; }
        body { background-color: var(--app-bg); font-family: 'Plus Jakarta Sans', sans-serif; color: #2d3436; margin: 0; }
        .content-wrapper { margin-left: var(--sidebar-width); padding: 35px; min-height: 100vh; }
        .glass-header-container { background: white; border-radius: 50px; padding: 15px 45px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.04); margin-bottom: 45px; }
        .metric-card { background: white; border-radius: 20px; padding: 1.5rem; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .data-panel { background: white; border-radius: 25px; padding: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        
        /* Dropdown & Buttons Styling */
        .btn-action-main { border-radius: 15px; padding: 12px 20px; font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; transition: all 0.3s ease; border: 1px solid #e2e8f0; }
        .btn-purple { background: var(--main-gradient); border: none; color: white; }
        .dropdown-menu { border-radius: 15px; border: none; shadow: 0 10px 30px rgba(0,0,0,0.1); padding: 10px; }
        .dropdown-item { border-radius: 8px; font-weight: 600; font-size: 0.85rem; padding: 8px 15px; }
        .dropdown-item.active { background: var(--main-gradient) !important; color: white; }
        
        .status-badge { padding: 6px 12px; border-radius: 10px; font-weight: 700; font-size: 0.7rem; text-transform: uppercase; }
        .st-active { background: #e1d5e7; color: #2E073F; }
        .st-disposal { background: #f8d7da; color: #721c24; }
        .st-replacement { background: #fff3cd; color: #856404; }
        
        .form-label-custom { font-weight: 700; color: var(--accent-purple); font-size: 0.75rem; text-transform: uppercase; margin-bottom: 8px; display: block; }
        .input-custom { border-radius: 15px; padding: 12px 18px; border: 2px solid #f1f1f7; background: #fcfaff; font-weight: 600; font-size: 0.9rem; width: 100%; outline: none; }
    </style>
</head>
<body>

<?php include 'aside.php'; ?>

<div class="content-wrapper">
    <div class="glass-header-container">
        <div>
            <h2 style="color:var(--accent-purple); font-weight:800;">VIEW INVENTORY</h2>
            <p class="m-0 text-muted fw-600">Asset Record & Monitoring</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end">
                <div class="fw-bold"><?php echo htmlspecialchars($display_name); ?></div>
                <a href="logout.php" class="small text-decoration-none fw-bold text-danger">Sign Out</a>
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
            <div class="col-md-5">
                <div class="position-relative">
                    <i class="fas fa-search position-absolute" style="left: 18px; top: 18px; color: #b4b6c4;"></i>
                    <input type="text" id="assetSearch" class="form-control border-0 bg-light p-3 ps-5 rounded-4 fw-600" placeholder="Search tag, serial, or model...">
                </div>
            </div>
            <div class="col-md-7 text-end d-flex justify-content-end gap-2">
                <!-- FILTER BUTTON (EXACT COPY OF IMAGE) -->
<div class="dropdown">
    <button class="btn dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown" 
            style="background: white; border: 1px solid #d1d5db; border-radius: 30px; padding: 12px 28px; display: flex; align-items: center; gap: 10px;">
        
        <!-- Violet Filter Icon -->
        <i class="fas fa-filter" style="color: #7A1CAC; font-size: 1.1rem;"></i> 
        
        <!-- Filter Text -->
        <span style="font-weight: 800; font-size: 1.1rem; color: #1f2937;">
            Filter:<span id="activeFilterLabel" style="color: #1f2937; margin-left: 2px;">All</span>
        </span>
    </button>

    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2" style="border-radius: 15px;">
        <li><h6 class="dropdown-header fw-800 text-muted small">BY STATUS</h6></li>
        <li><a class="dropdown-item active" href="#" onclick="setFilter('status', 'All', this)">All Status</a></li>
        <li><a class="dropdown-item" href="#" onclick="setFilter('status', 'Active', this)">Active</a></li>
        <li><a class="dropdown-item" href="#" onclick="setFilter('status', 'Replacement', this)">Replacement</a></li>
        <li><a class="dropdown-item" href="#" onclick="setFilter('status', 'For Disposal', this)">For Disposal</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><h6 class="dropdown-header fw-800 text-muted small">BY TYPE</h6></li>
        <li><a class="dropdown-item" href="#" onclick="setFilter('type', 'Laptop', this)">Laptops</a></li>
        <li><a class="dropdown-item" href="#" onclick="setFilter('type', 'Desktop', this)">Desktops</a></li>
        <li><a class="dropdown-item" href="#" onclick="setFilter('type', 'Monitor', this)">Monitors</a></li>
    </ul>
</div>

                <button class="btn-purple btn-action-main text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#createItemModal">
                    <i class="fas fa-plus me-2"></i>Create Item
                </button>
                <button onclick="exportInventoryPDF()" class="btn btn-dark btn-action-main text-white shadow-sm">
                    <i class="fas fa-file-pdf me-2"></i>Export PDF
                </button>
            </div>
        </div>

        <div id="table-to-export">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="inventoryTable">
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
                        $sql = "SELECT * FROM assets ORDER BY inventory_date DESC";
                        $res = mysqli_query($conn, $sql);
                        while ($row = mysqli_fetch_assoc($res)):
                            $badge = ($row['status'] == 'For Disposal') ? 'st-disposal' : (($row['status'] == 'Replacement') ? 'st-replacement' : 'st-active');
                            $qr_data = "TAG: ".$row['asset_tag']." | SN: ".$row['serial_number'];
                        ?>
                        <tr class="asset-row" data-status="<?php echo $row['status']; ?>" data-type="<?php echo $row['asset_type']; ?>">
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

<!-- Modal Create & Edit remain the same as your original -->
<!-- (Create Modal and Edit Modal codes here...) -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    let filterStatus = 'All';
    let filterType = 'All';

    // REAL-TIME SEARCH & FILTER MASTER
    function applyFilters() {
        let search = $('#assetSearch').val().toLowerCase();
        let rows = $('.asset-row');
        
        rows.each(function() {
            let row = $(this);
            let text = row.text().toLowerCase();
            let status = row.data('status');
            let type = row.data('type');

            let matchSearch = text.indexOf(search) > -1;
            let matchStatus = (filterStatus === 'All' || status === filterStatus);
            let matchType = (filterType === 'All' || type === filterType);

            if (matchSearch && matchStatus && matchType) {
                row.show();
            } else {
                row.hide();
            }
        });
    }

    function setFilter(category, value, element) {
        // Update labels and active state
        if (category === 'status') {
            filterStatus = value;
            filterType = 'All'; // Reset type if choosing status or vice versa if preferred
        } else {
            filterType = value;
            filterStatus = 'All';
        }

        $('.dropdown-item').removeClass('active');
        $(element).addClass('active');
        $('#activeFilterLabel').text(value);
        
        applyFilters();
    }

    $('#assetSearch').on('keyup', applyFilters);

    // Initial QR Generation
    function generateTableQRs() {
        document.querySelectorAll('.table-qr').forEach(canvas => {
            new QRious({
                element: canvas,
                value: canvas.getAttribute('data-value'),
                size: 100
            });
        });
    }

    $(document).ready(function() {
        generateTableQRs();
    });

    // PDF Export & Edit Handlers (Original Logic)
    $('.editBtn').on('click', function() {
        $('#edit_id').val($(this).data('id'));
        $('#edit_tag').val($(this).data('tag'));
        $('#edit_serial').val($(this).data('serial'));
        $('#edit_model').val($(this).data('model'));
        $('#edit_loc').val($(this).data('loc'));
        $('#edit_status').val($(this).data('status'));
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    function exportInventoryPDF() {
        const tableHtml = document.getElementById('table-to-export').cloneNode(true);
        tableHtml.querySelectorAll('.no-export').forEach(el => el.remove());
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
        const today = new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        container.innerHTML = `<div style="text-align:center; border-bottom:3px solid #7A1CAC; margin-bottom:20px;"><h1 style="color:#7A1CAC; margin:0;">INSPIRO RELIA INC.</h1><p>COMPUTER ASSET REPORT - ${today}</p></div>`;
        container.appendChild(tableHtml);
        html2pdf().set({ margin: 0.3, filename: `Inventory_Report_${today}.pdf`, jsPDF: { format: 'a4', orientation: 'landscape' } }).from(container).save();
    }
</script>
</body>
</html>