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
            font-family: 'Plus Jakarta Sans', sans-serif; 
            margin: 0; 
            color: #2d3436;
        }
        
        .content-wrapper { 
            margin-left: var(--sidebar-width); 
            padding: 2.5rem; 
            min-height: 100vh;
        }

        .glass-header {
            background: white;
            border-radius: 25px;
            padding: 1.5rem 2.5rem;
            box-shadow: 0 10px 30px rgba(111, 66, 193, 0.05);
            margin-bottom: 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

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
            font-weight: 600;
        }

        .btn-action-main {
            border-radius: 15px; 
            padding: 12px 20px; 
            font-weight: 700;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
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
        }

        .profile-dot {
            width: 45px; height: 45px; background: var(--main-gradient); 
            color: white; border-radius: 15px; display: flex;
            align-items: center; justify-content: center; font-weight: 800;
        }

        @media (max-width: 992px) { .content-wrapper { margin-left: 0; padding: 1.5rem; } }
    </style>
</head>
<body>

    <?php include 'aside.php'; ?>
    
    <div class="content-wrapper">
        <div class="glass-header">
            <div>
                <h4 class="fw-800 m-0"><?php echo htmlspecialchars($location); ?> <span style="background: var(--main-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">INVENTORY</span></h4>
                <small class="text-muted fw-700">Asset Management System</small>
            </div>
            <div class="profile-dot"><?php echo strtoupper(substr($_SESSION['user'] ?? 'A', 0, 1)); ?></div>
        </div>

        <div class="table-card">
            <!-- TOP BAR -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <div class="position-relative flex-grow-1" style="min-width: 300px;">
                    <i class="fas fa-search position-absolute" style="left: 18px; top: 17px; color: #b4b6c4;"></i>
                    <input type="text" id="assetSearch" class="search-bar" placeholder="Search asset tag, serial, or model...">
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <a href="view_area.php" class="btn btn-light btn-action-main border text-muted">
                        <i class="fas fa-arrow-left me-2"></i> Back
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
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table custom-table align-middle" id="assetTable">
                    <thead>
                        <tr>
                            <th>Asset Tag</th>
                            <th>Details</th>
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
                            <td>
                                <div class="fw-700"><?php echo $row['brand_model']; ?></div>
                                <div class="small text-muted"><?php echo $row['serial_number']; ?></div>
                            </td>
                            <td><span class="fw-600 text-muted"><?php echo $row['asset_type'] ?? 'N/A'; ?></span></td>
                            <td>
                                <span class="badge bg-light text-primary border rounded-pill px-3"><?php echo $row['location']; ?></span>
                                <span class="d-none status-cell"><?php echo $row['status']; ?></span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-secondary rounded-pill px-3">View</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <tr id="noResultsRow" style="display: none;">
                            <td colspan="5" class="text-center py-5 text-muted fw-700">No assets found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Scanner Modal -->
    <div class="modal fade" id="deployAssetModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 25px;">
                <div class="modal-header border-0 p-4">
                    <h5 class="fw-800 m-0">Scan QR Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="stopScanner()"></button>
                </div>
                <div class="modal-body p-4 pt-0">
                    <div id="reader" class="rounded-4 overflow-hidden" style="background: #f8f9fa;"></div>
                    <button class="btn btn-purple w-100 mt-3 py-3 fw-800 text-white rounded-4" onclick="toggleCamera()" id="btnPower">Start Scanner</button>
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

                if (matchesSearch && matchesFilter) {
                    row.style.display = "";
                    found = true;
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

        let html5QrCode;
        async function toggleCamera() {
            if (!html5QrCode) html5QrCode = new Html5Qrcode("reader");
            const btn = document.getElementById('btnPower');
            
            if (btn.innerText === "Start Scanner") {
                await html5QrCode.start({ facingMode: "environment" }, { fps: 10, qrbox: 250 }, (txt) => {
                    alert("Scanned: " + txt);
                    stopScanner();
                });
                btn.innerText = "Stop Scanner";
            } else {
                stopScanner();
            }
        }

        async function stopScanner() {
            if (html5QrCode) {
                await html5QrCode.stop();
                document.getElementById('btnPower').innerText = "Start Scanner";
            }
        }
    </script>
</body>
</html>