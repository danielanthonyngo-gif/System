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
 * FIXED QUERIES:
 * Ginagamit na ang 'client_accounts' na siyang totoong pangalan ng table sa phpMyAdmin mo.
 */

// 1. Stats Query - Bilangin ang ACTIVE assets
$active_query = mysqli_query($conn, "
    SELECT COUNT(a.id) as t 
    FROM assets a 
    JOIN client_accounts acc ON a.location = acc.account_id 
    WHERE acc.client_name = '$location' AND a.status = 'Active'
");
$active = mysqli_fetch_assoc($active_query)['t'] ?? 0;

// 2. Stats Query - Bilangin ang FOR DISPOSAL assets
$disposal_query = mysqli_query($conn, "
    SELECT COUNT(a.id) as t 
    FROM assets a 
    JOIN client_accounts acc ON a.location = acc.account_id 
    WHERE acc.client_name = '$location' AND a.status = 'For Disposal'
");
$disposal = mysqli_fetch_assoc($disposal_query)['t'] ?? 0;

// 3. Stats Query - Bilangin ang REPLACEMENT assets
$replacement_query = mysqli_query($conn, "
    SELECT COUNT(a.id) as t 
    FROM assets a 
    JOIN client_accounts acc ON a.location = acc.account_id 
    WHERE acc.client_name = '$location' AND a.status = 'Replacement'
");
$replacement = mysqli_fetch_assoc($replacement_query)['t'] ?? 0;

// 4. Stats Query - Bilangin ang IN STORAGE assets
$storage_query = mysqli_query($conn, "
    SELECT COUNT(a.id) as t 
    FROM assets a 
    JOIN client_accounts acc ON a.location = acc.account_id 
    WHERE acc.client_name = '$location' AND a.status = 'In Storage'
");
$storage = mysqli_fetch_assoc($storage_query)['t'] ?? 0;


// Listahan ng Assets - Kunin ang lahat ng assets na tumutugma sa client name gamit ang JOIN
$assets = mysqli_query($conn, "
    SELECT a.*, acc.client_name 
    FROM assets a 
    JOIN client_accounts acc ON a.location = acc.account_id 
    WHERE acc.client_name = '$location'");

$current_page = 'view_area.php'; 

// TUKUYIN KUNG EMBEDDED LAYOUT (NASA LOOB NG MODAL POPUP)
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
            cursor: pointer; /* Ginawang mukhang clickable button ang card */
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        /* Hover at Active effect kapag pinindot ang card */
        .stat-card-modern:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(111, 66, 193, 0.08);
        }
        .stat-card-modern:active {
            transform: translateY(0);
        }

        .icon-box {
            width: 48px; height: 48px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.1rem;
        }

        /* Iba't ibang kulay para sa mga status icons */
        .bg-active { background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); }
        .bg-disposal { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); }
        .bg-replacement { background: linear-gradient(135deg, #f1c40f 0%, #f39c12 100%); }
        .bg-storage { background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%); }

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
        body {
            background-color: transparent !important;
        }
    </style>
    <?php endif; ?>
</head>
<body>

    <?php 
        if (!$is_embed) {
            include 'aside.php'; 
        }
    ?>
    
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
                <div class="stat-card-modern" onclick="clickStatCard('Active', 'Active')">
                    <div class="icon-box bg-active"><i class="fas fa-desktop"></i></div>
                    <div>
                        <small class="text-muted fw-800 text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">Active Assets</small>
                        <h3 class="m-0 fw-800" style="color: #1e293b;"><?php echo $active; ?></h3>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="stat-card-modern" onclick="clickStatCard('For Disposal', 'For Disposal')">
                    <div class="icon-box bg-disposal"><i class="fas fa-trash-alt"></i></div>
                    <div>
                        <small class="text-muted fw-800 text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">For Disposal</small>
                        <h3 class="m-0 fw-800" style="color: #1e293b;"><?php echo $disposal; ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="stat-card-modern" onclick="clickStatCard('Replacement', 'Replacement')">
                    <div class="icon-box bg-replacement"><i class="fas fa-sync-alt"></i></div>
                    <div>
                        <small class="text-muted fw-800 text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">Replacement</small>
                        <h3 class="m-0 fw-800" style="color: #1e293b;"><?php echo $replacement; ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="stat-card-modern" onclick="clickStatCard('In Storage', 'In Storage')">
                    <div class="icon-box bg-storage"><i class="fas fa-box"></i></div>
                    <div>
                        <small class="text-muted fw-800 text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">In Storage</small>
                        <h3 class="m-0 fw-800" style="color: #1e293b;"><?php echo $storage; ?></h3>
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
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg p-2" style="border-radius: 15px; min-width: 220px;" id="dropdownMenuFilter">
                                <li><h6 class="dropdown-header text-uppercase small fw-800 text-muted">By Status</h6></li>
                                <li><a class="dropdown-item rounded-3 active" href="#" data-value="All" onclick="setFilter('All', this, 'All Status')">All Status</a></li>
                                <li><a class="dropdown-item rounded-3" href="#" data-value="Active" onclick="setFilter('Active', this, 'Active')">Active</a></li>
                                <li><a class="dropdown-item rounded-3" href="#" data-value="Replacement" onclick="setFilter('Replacement', this, 'Replacement')">Replacement</a></li>
                                <li><a class="dropdown-item rounded-3" href="#" data-value="For Disposal" onclick="setFilter('For Disposal', this, 'For Disposal')">For Disposal</a></li>
                                <li><a class="dropdown-item rounded-3" href="#" data-value="In Storage" onclick="setFilter('In Storage', this, 'In Storage')">In Storage</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header text-uppercase small fw-800 text-muted">By Type</h6></li>
                                <li><a class="dropdown-item rounded-3" href="#" data-value="Laptop" onclick="setFilter('Laptop', this, 'Laptops')">Laptops</a></li>
                                <li><a class="dropdown-item rounded-3" href="#" data-value="Desktop" onclick="setFilter('Desktop', this, 'Desktops')">Desktops</a></li>
                                <li><a class="dropdown-item rounded-3" href="#" data-value="Monitor" onclick="setFilter('Monitor', this, 'Monitors')">Monitors</a></li>
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
                    <h5 class="modal-title fw-800"><i class="bi bi-qr-code-scan me-2 text-primary"></i>Deploy New Asset</h5>
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
                                    <input type="text" class="form-control bg-light fw-800 border-0 p-3 rounded-4" id="assetTag" readonly placeholder="Scan result...">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-800 text-muted">EQUIPMENT NAME</label>
                                    <input type="text" class="form-control p-3 rounded-4 border-1" id="equipmentName" placeholder="e.g. Dell Latitude 3420">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-800 text-muted">LOCATION</label>
                                    <input type="text" class="form-control bg-light p-3 border-0 rounded-4 fw-800 text-primary" value="<?php echo htmlspecialchars($location); ?>" readonly>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light fw-800 px-4" data-bs-dismiss="modal" onclick="stopScanner()">Cancel</button>
                    <button type="button" class="btn btn-purple fw-800 px-5 shadow">Confirm Deployment</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
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
            if(element) {
                element.classList.add('active');
            }
            document.getElementById('activeFilterLabel').innerText = label;
            currentFilterValue = filterVal;
            applyFilters();
        }

        // BAGONG FUNCTION: Taga-salo kapag ang kinlik ng user ay ang mismong Status Card sa itaas
        function clickStatCard(statusVal, label) {
            // I-synchronize ang dropdown UI para sumabay kung ano ang pinindot na Card
            let item = document.querySelector(`#dropdownMenuFilter a[data-value="${statusVal}"]`);
            setFilter(statusVal, item, label);
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
                        document.getElementById('assetTag').value = text;
                        if (navigator.vibrate) navigator.vibrate(100);
                        stopScanner();
                    });
                    isScanning = true;
                    document.getElementById("btnPowerText").innerText = "Stop";
                } catch (err) { alert("Camera Error: " + err); }
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
    </script>
</body>
</html>