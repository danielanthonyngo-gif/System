<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Kunin ang string name mula sa URL parameter (Halimbawa: ?location=BDO)
$location = isset($_GET['location']) ? mysqli_real_escape_string($conn, $_GET['location']) : 'BDO';

/**
 * SALUHIN ANG CONFIRM DEPLOYMENT / TRANSFER (AJAX POST REQUEST)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deploy_asset') {
    $asset_tag = mysqli_real_escape_string($conn, $_POST['asset_tag']);
    $equipment_name = mysqli_real_escape_string($conn, $_POST['equipment_name']);
    
    // Saluhin ang sinalang Serial Number mula sa Ajax post request kung mayroon, kundi default sa asset tag
    $serial_number = isset($_POST['serial_number']) ? mysqli_real_escape_string($conn, $_POST['serial_number']) : $asset_tag;
    
    // Kunin ang account_id para sa kasalukuyang lokasyon
    $loc_query = mysqli_query($conn, "SELECT account_id FROM client_accounts WHERE client_name = '$location' LIMIT 1");
    $loc_row = mysqli_fetch_assoc($loc_query);
    $location_id = $loc_row['account_id'] ?? 0;

    if (!empty($asset_tag) && !empty($equipment_name) && $location_id > 0) {
        
        // -------------------------------------------------------------
        // REVISED VALIDATION AT AUTOMATIC TRANSFER LOGIC
        // -------------------------------------------------------------
        $check_duplicate = mysqli_query($conn, "SELECT location, asset_tag, serial_number FROM assets WHERE asset_tag = '$asset_tag' OR serial_number = '$serial_number' LIMIT 1");
        
        if (mysqli_num_rows($check_duplicate) > 0) {
            $existing_asset = mysqli_fetch_assoc($check_duplicate);
            
            // KUNG NANDITO NA SA KASALUKUYANG AREA: I-block para maiwasan ang double deployment
            if ($existing_asset['location'] == $location_id) {
                echo json_encode(['status' => 'error', 'message' => ' This Asset is already deployed in this location!']);
                exit();
            } else {
                // KUNG NASA IBANG AREA: I-update ang location at pangalan papunta sa kasalukuyang area (Auto-Transfer)
                $update_query = "UPDATE assets 
                                 SET location = '$location_id', 
                                     brand_model = '$equipment_name', 
                                     status = 'Active' 
                                 WHERE asset_tag = '$asset_tag' OR serial_number = '$serial_number'";
                
                if (mysqli_query($conn, $update_query)) {
                    echo json_encode(['status' => 'success', 'message' => 'This asset has been successfully transferred to this location!']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Transfer Error: ' . mysqli_error($conn)]);
                }
                exit();
            }
        }
        // -------------------------------------------------------------

        // KUNG WALA PA SA DATABASE: Mag-insert ng bagong asset record
        $insert_query = "INSERT INTO assets (asset_tag, brand_model, location, status, asset_type, serial_number) 
                         VALUES ('$asset_tag', '$equipment_name', '$location_id', 'Active', 'Laptop', '$serial_number')";
        
        if (mysqli_query($conn, $insert_query)) {
            echo json_encode(['status' => 'success', 'message' => 'Asset deployed successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
    }
    exit(); 
}

/**
 * FIXED QUERIES
 */

// 1. Stats Query - Bilangin ang active assets para sa lokasyon gamit ang JOIN
$active_query = mysqli_query($conn, "
    SELECT COUNT(a.id) as t 
    FROM assets a 
    JOIN client_accounts acc ON a.location = acc.account_id 
    WHERE acc.client_name = '$location' AND a.status = 'Active'
");
$active = mysqli_fetch_assoc($active_query)['t'] ?? 0;

// 2. Listahan ng Assets - Kunin ang assets na tumutugma sa client name gamit ang JOIN
$assets = mysqli_query($conn, "
    SELECT a.*, acc.client_name 
    FROM assets a 
    JOIN client_accounts acc ON a.location = acc.account_id 
    WHERE acc.client_name = '$location'");

$current_page = 'view_area.php'; 
$is_embed = (isset($_GET['layout']) && $_GET['layout'] == 'embed');
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        :root { 
            --app-bg: #f8f7ff;
            --main-gradient: linear-gradient(135deg, #7A1CAC 0%, #7A1CAC 100%);
            --sidebar-width: 260px;
            --accent-purple: #2E073F;
            --accent-pink: #7A1CAC;
        }

        body { 
            background-color: var(--app-bg); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: #2d3436;
            margin: 0;
        }
        
        .content-wrapper { 
            margin-left: var(--sidebar-width); 
            padding: 1.5rem; 
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .glass-header {
            background: white;
            border-radius: 20px;
            padding: 1.2rem 1.5rem;
            box-shadow: 0 10px 30px rgba(111, 66, 193, 0.05);
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255,255,255,0.7);
        }

        .stat-card-modern {
            background: white;
            border-radius: 20px;
            padding: 1.2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 10px 25px rgba(111, 66, 193, 0.03);
            border: 1px solid #f1f0f7;
            height: 100%;
        }

        .icon-box {
            width: 48px; height: 48px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.1rem;
            background: var(--main-gradient);
        }

        .table-card {
            background: white;
            border-radius: 25px;
            padding: 1.5rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.02);
            border: 1px solid #f1f0f7;
        }
        
        .search-container { position: relative; width: 100%; }
        .search-bar {
            padding: 12px 20px 12px 45px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #fcfaff;
            width: 100%;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .search-bar:focus { outline: none; border-color: var(--accent-pink); box-shadow: 0 0 0 3px rgba(122, 28, 172, 0.1); }

        .btn-action-main {
            border-radius: 12px; padding: 10px 20px; font-weight: 700;
            white-space: nowrap; transition: all 0.2s;
        }
        .btn-action-main:hover { transform: translateY(-2px); }

        .btn-purple { background: var(--main-gradient); border: none; color: white; }
        .btn-purple:hover { color: white; opacity: 0.9; }

        .custom-table thead th {
            color: #6f42c1; font-size: 0.7rem; text-transform: uppercase;
            letter-spacing: 1px; font-weight: 800; padding: 15px;
            border-bottom: 2px solid #f1f0f7;
        }
        .custom-table tbody td { padding: 15px; border-bottom: 1px solid #f8f9fa; }

        .badge-location {
            background: #f5f3ff; color: #6f42c1; border-radius: 8px;
            padding: 5px 10px; font-weight: 800; font-size: 0.65rem;
            border: 1px solid rgba(111, 66, 193, 0.1);
        }

        .profile-dot {
            width: 40px; height: 40px; background: var(--main-gradient); 
            color: white; border-radius: 12px; display: flex;
            align-items: center; justify-content: center; font-weight: 800;
        }

        .swal2-popup {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            border-radius: 20px !important;
        }

        @media (max-width: 992px) {
            .content-wrapper { margin-left: 0; padding: 1rem; }
            .glass-header { margin-top: 50px; } 
        }

        @media (max-width: 576px) {
            .header-title h4 { font-size: 1rem; }
            .btn-action-main { width: 100%; justify-content: center; display: flex; }
        }
    </style>

    <?php if ($is_embed): ?>
    <style>
        .content-wrapper { 
            margin-left: 0 !important; 
            padding: 15px 5px !important; 
            min-height: auto !important;
        }
        body { background-color: transparent !important; }
    </style>
    <?php endif; ?>
</head>
<body>

    <?php if (!$is_embed) { include 'aside.php'; } ?>
    
    <div class="content-wrapper" style="<?php echo $is_embed ? 'margin-left: 0 !important;' : ''; ?>">
        
        <?php if (!$is_embed): ?>
            <div class="glass-header">
                <div class="header-title">
                    <h4 class="fw-800 m-0"><?php echo htmlspecialchars($location); ?> <span style="color: var(--accent-pink);">INVENTORY</span></h4>
                    <small class="text-muted d-none d-sm-block fw-600">Inspiro Relia Inc. Asset Management</small>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end d-none d-md-block">
                        <div class="small fw-800" style="color: var(--accent-purple);"><?php echo htmlspecialchars($_SESSION['user'] ?? 'User'); ?></div>
                        <a href="logout.php" class="text-decoration-none fw-bold" style="font-size: 0.65rem; color: var(--accent-pink);">SIGN OUT</a>
                    </div>
                    <div class="profile-dot"><?php echo strtoupper(substr($_SESSION['user'] ?? 'U', 0, 1)); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="stat-card-modern">
                    <div class="icon-box"><i class="fas fa-desktop"></i></div>
                    <div>
                        <small class="text-muted fw-800 text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">Active Assets</small>
                        <h3 class="m-0 fw-800" style="color: #1e293b;"><?php echo $active; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="row g-3 mb-4 align-items-center">
                <div class="col-12 col-xl-5">
                    <div class="search-container">
                        <i class="fas fa-search position-absolute" style="left: 18px; top: 15px; color: #b4b6c4;"></i>
                        <input type="text" id="assetSearch" class="search-bar" placeholder="Search tag, serial, or model...">
                    </div>
                </div>
                <div class="col-12 col-xl-7">
                    <div class="d-flex flex-wrap gap-2 justify-content-xl-end">
                        <div class="dropdown filter-dropdown">
                            <button class="btn btn-white border dropdown-toggle btn-action-main shadow-sm" type="button" id="filterDropdown" data-bs-toggle="dropdown">
                                <i class="fas fa-filter me-2 text-primary"></i> 
                                Filter: <span id="activeFilterLabel" class="fw-800">All</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg p-2" style="border-radius: 15px; min-width: 220px;">
                                <li><h6 class="dropdown-header text-uppercase small fw-800 text-muted">By Status</h6></li>
                                <li><a class="dropdown-item rounded-3 active" href="#" onclick="setFilter('All', this, 'All Status')">All Status</a></li>
                                <li><a class="dropdown-item rounded-3" href="#" onclick="setFilter('Active', this, 'Active')">Active</a></li>
                                <li><a class="dropdown-item rounded-3" href="#" onclick="setFilter('Replacement', this, 'Replacement')">Replacement</a></li>
                                <li><a class="dropdown-item rounded-3" href="#" onclick="setFilter('For Disposal', this, 'For Disposal')">For Disposal</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header text-uppercase small fw-800 text-muted">By Type</h6></li>
                                <li><a class="dropdown-item rounded-3" href="#" onclick="setFilter('Laptop', this, 'Laptops')">Laptops</a></li>
                                <li><a class="dropdown-item rounded-3" href="#" onclick="setFilter('Desktop', this, 'Desktops')">Desktops</a></li>
                                <li><a class="dropdown-item rounded-3" href="#" onclick="setFilter('Monitor', this, 'Monitors')">Monitors</a></li>
                            </ul>
                        </div>

                        <button class="btn btn-purple btn-action-main shadow-sm" data-bs-toggle="modal" data-bs-target="#deployAssetModal">
                            <i class="fas fa-plus me-2"></i>New Asset
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table custom-table align-middle" id="assetTable">
                    <thead>
                        <tr>
                            <th>Asset Tag</th>
                            <th>Device Details</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($assets && mysqli_num_rows($assets) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($assets)): ?>
                            <tr class="asset-row" 
                                data-type="<?php echo htmlspecialchars($row['asset_type'] ?? ''); ?>" 
                                data-status="<?php echo htmlspecialchars($row['status'] ?? ''); ?>">
                                <td class="fw-800 text-dark"><?php echo htmlspecialchars($row['asset_tag']); ?></td>
                                <td>
                                    <div class="fw-800 text-primary" style="font-size: 0.9rem;"><?php echo htmlspecialchars($row['brand_model']); ?></div>
                                    <div class="text-muted small fw-600"><?php echo htmlspecialchars($row['serial_number']); ?></div>
                                </td>
                                <td><span class="fw-700 text-muted"><?php echo htmlspecialchars($row['asset_type'] ?? 'N/A'); ?></span></td>
                                <td><span class="badge-location"><?php echo htmlspecialchars($row['client_name']); ?></span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-dark rounded-pill px-3 fw-800" style="font-size: 0.7rem;">PULLOUT</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted fw-600">No assets found in this location.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div id="noResults" style="display:none;" class="text-center py-5 text-muted fw-700">No matching assets found.</div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deployAssetModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="modal-title fw-800"><i class="bi bi-qr-code-scan me-2 text-primary"></i>Scan & Deploy Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="stopScanner()"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div id="reader" class="rounded-4 bg-light overflow-hidden" style="min-height: 250px; border: 2px dashed #d1d5db;">
                                <div class="text-center p-5 text-muted" id="reader-placeholder">
                                    <i class="bi bi-camera fs-1"></i><p class="mt-2 fw-800">Ready to Scan</p>
                                </div>
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                <button type="button" class="btn btn-purple w-100 fw-800 py-2" onclick="toggleCamera()" id="btnPowerText">Start</button>
                                <button type="button" class="btn btn-dark fw-800 py-2" onclick="switchCamera()"><i class="bi bi-arrow-repeat"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <form id="deployForm">
                                <div class="mb-3">
                                    <label class="form-label small fw-800 text-muted">ASSET TAG / SERIAL</label>
                                    <input type="text" class="form-control bg-light fw-800 border-0 p-3 rounded-4" id="assetTag" placeholder="Scan result...">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-800 text-muted">EQUIPMENT NAME</label>
                                    <input type="text" class="form-control p-3 rounded-4 border-1" id="equipmentName" placeholder="e.g. Dell Latitude 3420">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-800 text-muted">CURRENT TARGET AREA</label>
                                    <input type="text" class="form-control bg-light p-3 border-0 rounded-4 fw-800 text-primary" value="<?php echo htmlspecialchars($location); ?>" readonly>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light fw-800 px-4" data-bs-dismiss="modal" onclick="stopScanner()">Cancel</button>
                    <button type="button" id="btnConfirmDeployment" class="btn btn-purple fw-800 px-5 shadow">Confirm Deployment</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        let currentFilterValue = 'All';

        function applyFilters() {
            let search = document.getElementById('assetSearch').value.toLowerCase();
            let rows = document.querySelectorAll('.asset-row');
            let foundCount = 0;

            rows.forEach(row => {
                let type = row.getAttribute('data-type') || '';
                let status = row.getAttribute('data-status') || '';
                let rowText = row.innerText.toLowerCase();

                let matchesSearch = rowText.includes(search);
                let matchesFilter = (currentFilterValue === 'All' || 
                                     type === currentFilterValue || 
                                     status === currentFilterValue);

                if (matchesSearch && matchesFilter) {
                    row.style.display = "";
                    foundCount++;
                } else {
                    row.style.display = "none";
                }
            });

            document.getElementById('noResults').style.display = (foundCount === 0 && rows.length > 0) ? "block" : "none";
        }

        function setFilter(filterVal, element, label) {
            document.querySelectorAll('.filter-dropdown .dropdown-item').forEach(i => i.classList.remove('active'));
            element.classList.add('active');
            document.getElementById('activeFilterLabel').innerText = label;
            currentFilterValue = filterVal;
            applyFilters();
        }

        document.getElementById('assetSearch').addEventListener('keyup', applyFilters);

        let html5QrCode;
        let isScanning = false;
        let currentFacingMode = "environment";

        async function toggleCamera() {
            if (!isScanning) {
                if (!html5QrCode) html5QrCode = new Html5Qrcode("reader");
                try {
                    document.getElementById("reader-placeholder").classList.add('d-none');
                    await html5QrCode.start({ facingMode: currentFacingMode }, { fps: 10, qrbox: 250 }, (text) => {
                        let cleanTag = text.trim();
                        let cleanModel = "";
                        let cleanSerial = "";

                        if (text.includes('|')) {
                            let segments = text.split('|');
                            segments.forEach(segment => {
                                let parts = segment.split(':');
                                if (parts.length >= 2) {
                                    let key = parts[0].toUpperCase().trim();
                                    let val = parts[1].trim();

                                    if (key.includes("TAG")) cleanTag = val;
                                        else if (key.includes("MODEL")) cleanModel = val.replace(/\(\)/g, '').trim(); 
                                        else if (key.includes("SN") || key.includes("SERIAL")) cleanSerial = val;
                                }
                            });
                        }

                        document.getElementById('assetTag').value = cleanTag;
                        if (cleanModel !== "" && document.getElementById('equipmentName')) {
                            document.getElementById('equipmentName').value = cleanModel;
                        }
                        document.getElementById('assetTag').setAttribute('data-extracted-sn', cleanSerial || cleanTag);

                        if (navigator.vibrate) navigator.vibrate(100);
                        stopScanner();
                    });
                    isScanning = true;
                    document.getElementById("btnPowerText").innerText = "Stop";
                } catch (err) { 
                    Swal.fire({ icon: 'error', title: 'Camera Error', text: err });
                }
            } else { stopScanner(); }
        }

        async function stopScanner() {
            if (html5QrCode && isScanning) {
                await html5QrCode.stop();
                isScanning = false;
                document.getElementById("btnPowerText").innerText = "Start";
                document.getElementById("reader-placeholder").classList.remove('d-none');
            }
        }

        async function switchCamera() {
            currentFacingMode = (currentFacingMode === "environment") ? "user" : "environment";
            if (isScanning) { await stopScanner(); toggleCamera(); }
        }

        // AJAX POST PROCESS (HANDLES DEPLOYMENT AND AUTO-TRANSFER)
        document.getElementById('btnConfirmDeployment').addEventListener('click', function () {
            let assetTag = document.getElementById('assetTag').value.trim();
            let equipmentName = document.getElementById('equipmentName').value.trim();

            if (assetTag === "" || equipmentName === "") {
                Swal.fire({
                    icon: 'warning',
                    title: 'Incomplete Fields',
                    text: 'Please fill in all required fields.',
                    confirmButtonColor: '#7A1CAC'
                });
                return;
            }

            let formData = new FormData();
            formData.append('action', 'deploy_asset');
            formData.append('asset_tag', assetTag);
            formData.append('equipment_name', equipmentName);
            
            let extractedSN = document.getElementById('assetTag').getAttribute('data-extracted-sn') || assetTag;
            formData.append('serial_number', extractedSN);

            Swal.fire({
                title: 'Processing Request...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        confirmButtonColor: '#7A1CAC',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(() => {
                        location.reload(); 
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Action Denied',
                        text: data.message,
                        confirmButtonColor: '#2E073F'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: 'May nagka-problema sa pag-send ng data.',
                    confirmButtonColor: '#2E073F'
                });
            });
        });
    </script>
</body>
</html>