<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$location = isset($_GET['location']) ? mysqli_real_escape_string($conn, $_GET['location']) : 'BDO';

// Stats Query
$active = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM assets WHERE location = '$location' AND status = 'Active'"))['t'] ?? 0;

// Kunin ang listahan ng assets
$assets = mysqli_query($conn, "SELECT * FROM assets WHERE location = '$location'");

$current_page = 'view_area.php'; 
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>

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
            background-image: 
                radial-gradient(at 0% 0%, rgba(111, 66, 193, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(214, 51, 132, 0.05) 0px, transparent 50%);
            font-family: 'Plus Jakarta Sans', sans-serif; 
            margin: 0; 
            color: #2d3436;
        }
        
        .content-wrapper { 
            margin-left: var(--sidebar-width); 
            padding: 2.5rem; 
            min-height: 100vh;
        }

        /* Modern Glass Header */
        .glass-header {
            background: white;
            border-radius: 25px;
            padding: 1.5rem 2.5rem;
            box-shadow: 0 10px 30px rgba(111, 66, 193, 0.05);
            margin-bottom: 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255,255,255,0.7);
        }

        /* Stat Card Modern */
        .stat-card-modern {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            width: 280px;
            box-shadow: 0 10px 25px rgba(111, 66, 193, 0.03);
            border: 1px solid #f1f0f7;
        }
        .icon-box {
            width: 56px; height: 56px; border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.3rem;
            background: var(--main-gradient);
            box-shadow: 0 8px 15px rgba(111, 66, 193, 0.2);
        }

        /* Modern Table Card */
        .table-card {
            background: white;
            border-radius: 30px;
            padding: 2.5rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.02);
            border: 1px solid #f1f0f7;
        }
        
        .search-bar {
            padding: 14px 20px 14px 45px;
            border-radius: 15px;
            border: 1px solid #e2e8f0;
            background: #fcfaff;
            width: 100%;
            max-width: 380px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-purple {
            background: var(--main-gradient);
            border: none; 
            color: white;
        }

        .filter-dropdown .dropdown-item {
            padding: 10px 15px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .filter-dropdown .dropdown-item.active {
            background: var(--main-gradient) !important;
            color: white !important;
        .search-bar:focus {
            border-color: var(--accent-purple);
            box-shadow: 0 0 0 4px rgba(111, 66, 193, 0.1);
            outline: none;
        }

        .filter-dropdown .dropdown-header {
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 800;
            color: #adb5bd;
            padding: 10px 15px 5px;
        }

        .filter-dropdown .dropdown-divider {
            margin: 8px 0;
            border-top: 1px solid #f1f1f1;
        }

        .custom-table thead th {
            color: #6f42c1; font-size: 0.75rem; text-transform: uppercase;
            letter-spacing: 1.2px; font-weight: 800; padding: 18px 15px;
            border-bottom: 2px solid #f1f0f7;
        }
        
        .custom-table tbody td { padding: 20px 15px; font-size: 0.95rem; border-bottom: 1px solid #fcfaff; }

        .badge-location {
            background: #f5f3ff; color: #6f42c1; border-radius: 10px;
            padding: 7px 14px; font-weight: 800; font-size: 0.7rem;
            border: 1px solid rgba(111, 66, 193, 0.1);
        }

        .profile-dot {
            width: 45px; height: 45px; background: var(--main-gradient); 
            color: white; border-radius: 15px; display: flex;
            align-items: center; justify-content: center; font-weight: 800;
            box-shadow: 0 5px 15px rgba(111, 66, 193, 0.2);
        }

        .btn-action-main {
            border-radius: 15px; padding: 12px 24px; font-weight: 700;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .btn-action-main:hover { transform: translateY(-3px); }

        .btn-purple {
            background: var(--main-gradient);
            border: none; color: white;
        }
        .btn-purple:hover { color: white; box-shadow: 0 8px 20px rgba(111, 66, 193, 0.3); }

        @media (max-width: 992px) { .content-wrapper { margin-left: 0; padding: 1.5rem; } }
    </style>
</head>
<body>

    <?php include 'aside.php'; ?>
    
    <div class="content-wrapper">
        <div class="glass-header">
            <div>
                <h4 class="fw-800 m-0"><?php echo htmlspecialchars($location); ?> <span style="background: var(--main-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">INVENTORY</span></h4>
                <small class="text-muted fw-700">Inspiro Relia Inc. Asset Management</small>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-md-block">
                    <div class="small fw-800" style="color: var(--accent-purple);"><?php echo $_SESSION['user']; ?></div>
                    <a href="logout.php" class="text-decoration-none fw-800" style="font-size: 0.7rem; color: var(--accent-pink);">SIGN OUT</a>
                </div>
                <div class="profile-dot"><?php echo strtoupper(substr($_SESSION['user'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="d-flex gap-3 mb-4">
            <div class="stat-card-modern">
                <div class="icon-box"><i class="fas fa-desktop"></i></div>
                <div>
                    <small class="text-muted fw-800 text-uppercase" style="font-size: 0.6rem; letter-spacing: 1px;">Active Assets</small>
                    <h2 class="m-0 fw-800" style="color: #1e293b;"><?php echo $active; ?></h2>
                </div>
            </div>
            <div class="profile-dot"><?php echo strtoupper(substr($_SESSION['user'] ?? 'A', 0, 1)); ?></div>
        </div>

        <div class="table-card">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-5 gap-3">
                <div class="position-relative flex-grow-1">
                    <i class="fas fa-search position-absolute" style="left: 18px; top: 17px; color: #b4b6c4;"></i>
                    <input type="text" id="assetSearch" class="search-bar" placeholder="Search asset tag, serial, or model...">
                </div>
                <div class="d-flex gap-2">
                    <a href="view_area.php" class="btn btn-light btn-action-main text-muted border px-4">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>

                    <!-- SINGLE BUTTON FILTER DROPDOWN -->
                    <div class="dropdown filter-dropdown">
                        <button class="btn btn-action-main dropdown-toggle border bg-white shadow-sm" type="button" id="filterDropdown" data-bs-toggle="dropdown" style="border-radius: 20px;">
                            <i class="fas fa-filter me-2" style="color: #0d6efd;"></i> 
                            Filter: <span id="activeFilterLabel" class="fw-800" style="color: var(--accent-purple);">All</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg p-2" style="border-radius: 15px; min-width: 200px;">
                            <li><h6 class="dropdown-header">By Status</h6></li>
                            <li><a class="dropdown-item rounded-3 active" href="#" onclick="setFilter('All', this, 'All Status')">All Status</a></li>
                            <li><a class="dropdown-item rounded-3" href="#" onclick="setFilter('Active', this, 'Active')">Active</a></li>
                            <li><a class="dropdown-item rounded-3" href="#" onclick="setFilter('Replacement', this, 'Replacement')">Replacement</a></li>
                            <li><a class="dropdown-item rounded-3" href="#" onclick="setFilter('For Disposal', this, 'For Disposal')">For Disposal</a></li>
                            
                            <li><hr class="dropdown-divider"></li>
                            
                            <li><h6 class="dropdown-header">By Type</h6></li>
                            <li><a class="dropdown-item rounded-3" href="#" onclick="setFilter('Laptop', this, 'Laptops')">Laptops</a></li>
                            <li><a class="dropdown-item rounded-3" href="#" onclick="setFilter('Desktop', this, 'Desktops')">Desktops</a></li>
                            <li><a class="dropdown-item rounded-3" href="#" onclick="setFilter('Monitor', this, 'Monitors')">Monitors</a></li>
                        </ul>
                    </div>

                    <button type="button" class="btn btn-purple btn-action-main text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#deployAssetModal">
                        <i class="fas fa-plus me-2"></i> New Asset
                    <button type="button" class="btn btn-purple btn-action-main shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#deployAssetModal">
                        <i class="fas fa-plus me-2"></i>New Asset
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table custom-table align-middle" id="assetTable">
                    <thead>
                        <tr>
                            <th>Inventory Date</th>
                            <th>Asset Tag</th>
                            <th>Device Details</th>
                            <th>Type</th>
                            <th>Location/Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($assets)): ?>
                        <tr class="asset-row" 
                            data-type="<?php echo $row['asset_type'] ?? 'N/A'; ?>" 
                            data-status="<?php echo $row['status'] ?? 'N/A'; ?>">
                            <td class="fw-800"><?php echo $row['asset_tag']; ?></td>
                        <tr>
                            <td class="text-muted fw-600"><?php echo date("M d, Y", strtotime($row['created_at'] ?? 'now')); ?></td>
                            <td class="fw-800 text-dark"><?php echo $row['asset_tag']; ?></td>
                            <td>
                                <div class="fw-800" style="color: #4338ca;"><?php echo $row['brand_model']; ?></div>
                                <div class="text-muted small fw-600" style="font-size: 0.75rem;"><?php echo $row['serial_number']; ?></div>
                            </td>
                            <td><span class="fw-600 text-muted"><?php echo $row['asset_type'] ?? 'N/A'; ?></span></td>
                            <td>
                                <span class="badge bg-light text-primary border rounded-pill px-3"><?php echo $row['location']; ?></span>
                                <span class="d-none status-cell"><?php echo $row['status']; ?></span>
                            </td>
                            <td><span class="text-muted fw-700"><?php echo $row['type'] ?? 'N/A'; ?></span></td>
                            <td><span class="badge-location"><?php echo $row['location']; ?></span></td>
                            <td class="text-center">
                                <button class="btn btn-outline-dark btn-sm rounded-pill px-4 fw-800" style="font-size: 0.75rem;">PULLOUT</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if(mysqli_num_rows($assets) == 0): ?>
                        <tr class="no-data"><td colspan="6" class="text-center py-5 text-muted fw-700">No assets found in this location.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL SECTION -->
    <div class="modal fade" id="deployAssetModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 30px; overflow: hidden;">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="modal-title fw-800" style="color: var(--accent-purple);"><i class="bi bi-qr-code-scan me-2"></i>Deploy New Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="stopScanner()"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div id="reader" class="rounded-4 bg-light overflow-hidden" style="min-height: 300px; border: 2px dashed #d1d5db;">
                                <div class="text-center p-5 text-muted" id="reader-placeholder">
                                    <i class="bi bi-camera fs-1"></i><p class="mt-2 fw-800">Ready to Scan</p>
                                </div>
                            </div>
                            <div class="mt-3 btn-group w-100 shadow-sm rounded-4 overflow-hidden">
                                <button type="button" class="btn btn-purple fw-800 py-3" onclick="toggleCamera()" id="btnPowerText">Start Camera</button>
                                <button type="button" class="btn btn-dark fw-800 py-3" onclick="switchCamera()">Switch</button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <form id="deployForm">
                                <div class="mb-3">
                                    <label class="form-label small fw-800 text-muted text-uppercase">Asset Tag / Serial</label>
                                    <input type="text" class="form-control bg-light fw-800 border-0 p-3 rounded-4" id="assetTag" readonly placeholder="Waiting for scan...">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-800 text-muted text-uppercase">Equipment Name</label>
                                    <input type="text" class="form-control p-3 rounded-4 border-0 bg-light fw-600" id="equipmentName" placeholder="e.g. Dell Optiplex 7010">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-800 text-muted text-uppercase">Deployment Location</label>
                                    <input type="text" class="form-control bg-light p-3 border-0 rounded-4 fw-800" value="<?php echo htmlspecialchars($location); ?>" readonly style="color: var(--accent-purple);">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light fw-800 px-4 py-2 rounded-3" data-bs-dismiss="modal" onclick="stopScanner()">Cancel</button>
                    <button type="button" class="btn btn-purple fw-800 px-5 py-2 rounded-3 shadow">Confirm Deployment</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentFilterValue = 'All';

        function applyFilters() {
            let search = document.getElementById('assetSearch').value.toLowerCase();
            let rows = document.querySelectorAll('.asset-row');
            let found = false;

            rows.forEach(row => {
                let text = row.innerText.toLowerCase();
                let type = row.getAttribute('data-type');
                let status = row.getAttribute('data-status');
                
                let matchesSearch = text.includes(search);
                let matchesFilter = (currentFilterValue === 'All' || type === currentFilterValue || status === currentFilterValue);
        // --- SEARCH BAR LOGIC ---
        document.getElementById('assetSearch').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('#assetTable tbody tr');

            rows.forEach(row => {
                // Laktawan ang 'no-data' row kung mayroon man
                if (row.classList.contains('no-data')) return;

                let text = row.innerText.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
            document.getElementById('noResultsRow').style.display = found ? "none" : "";
        }

        function setFilter(filterVal, element, label) {
            document.querySelectorAll('.filter-dropdown .dropdown-item').forEach(i => i.classList.remove('active'));
            element.classList.add('active');
            document.getElementById('activeFilterLabel').innerText = label;
            currentFilterValue = filterVal;
            applyFilters();
        }

        document.getElementById('assetSearch').addEventListener('keyup', applyFilters);
        });

        // --- SCANNER LOGIC ---
        let html5QrCode;
        let isScanning = false;
        let currentFacingMode = "environment";

        async function toggleCamera() {
            if (!isScanning) {
                if (!html5QrCode) html5QrCode = new Html5Qrcode("reader");
                try {
                    document.getElementById("reader-placeholder").classList.add('d-none');
                    await html5QrCode.start({ facingMode: currentFacingMode }, { fps: 10, qrbox: 250 }, onScanSuccess);
                    isScanning = true;
                    document.getElementById("btnPowerText").innerText = "Stop Camera";
                } catch (err) { alert("Camera Error: " + err); }
            } else { stopScanner(); }
        }

        async function stopScanner() {
            if (html5QrCode && isScanning) {
                await html5QrCode.stop();
                isScanning = false;
                document.getElementById("btnPowerText").innerText = "Start Camera";
                document.getElementById("reader-placeholder").classList.remove('d-none');
            }
        }

        function onScanSuccess(decodedText) {
            document.getElementById('assetTag').value = decodedText;
            if (navigator.vibrate) navigator.vibrate(100);
            stopScanner();
        }

        async function switchCamera() {
            currentFacingMode = (currentFacingMode === "environment") ? "user" : "environment";
            if (isScanning) { await stopScanner(); toggleCamera(); }
        }
    </script>
</body>
</html>