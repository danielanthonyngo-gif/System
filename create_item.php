<?php
session_start();
include 'config.php';

// Security check
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$display_name = $_SESSION['user'] ?? "Master";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inspiro | Asset Registration</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>
        :root {
            --app-bg: #f3f0f7;
            --main-gradient: linear-gradient(135deg, #6f42c1 0%, #d63384 100%);
            --sidebar-width: 260px;
        }

        body { 
            background-color: var(--app-bg); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            margin: 0; 
        }

        .main-wrapper {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
        }

        .top-navbar {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e9ecef;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .content-body { padding: 40px; }

        .card-custom {
            background: white;
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(111, 66, 193, 0.1);
            position: relative;
            overflow: hidden;
            border: none;
        }

        .card-custom::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; height: 6px;
            background: var(--main-gradient);
        }

        .form-label { 
            font-weight: 700; 
            color: #6f42c1; 
            font-size: 0.8rem;
            margin-left: 5px;
        }

        .form-control, .form-select {
            border-radius: 12px;
            padding: 12px 18px;
            border: 2px solid #f1f1f1;
            background: #fafafa;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-save { 
            background: var(--main-gradient);
            color: white; 
            border: none; 
            padding: 16px 60px; 
            border-radius: 15px; 
            font-weight: 800;
            text-transform: uppercase;
            box-shadow: 0 8px 20px rgba(111, 66, 193, 0.3);
            transition: 0.3s;
        }

        .btn-save:hover { transform: scale(1.05); }

        #reader {
            width: 100%;
            border-radius: 15px;
            overflow: hidden;
            border: none !important;
        }

        .btn-camera-trigger {
            background: #f8f0ff;
            border: 2px solid #e0cffc;
            color: #6f42c1;
            border-radius: 12px;
            transition: 0.3s;
        }

        .btn-camera-trigger:hover {
            background: #6f42c1;
            color: white;
        }

        @media (max-width: 992px) { .main-wrapper { margin-left: 0; width: 100%; } }
    </style>
</head>
<body>

    <?php include 'aside.php'; ?>

    <div class="main-wrapper">
        <nav class="top-navbar">
            <div class="fw-bold text-uppercase small tracking-widest text-muted">
                <i class="fas fa-plus-circle me-2" style="color: #d63384;"></i> Registration Portal
            </div>
            <div class="profile-badge d-flex align-items-center gap-2" style="background: #f8f0ff; padding: 5px 15px; border-radius: 50px; border: 1px solid #e0cffc;">
                <span class="small fw-bold" style="color: #6f42c1;">
                    Master <strong><?php echo htmlspecialchars($display_name); ?></strong>
                </span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger rounded-pill fw-bold" style="font-size: 10px;">LOGOUT</a>
            </div>
        </nav>

        <div class="content-body">
            <div class="page-header mb-4">
                <h4><span style="background: var(--main-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">ASSET</span> REGISTRATION</h4>
                <p class="text-muted small">Modern inventory management system for Inspiro Relia Inc.</p>
            </div>
            
            <div class="card-custom">
                <form action="process_asset.php" method="POST">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label class="form-label">Asset Tag / QR Code</label>
                            <div class="input-group">
                                <span class="input-group-text border-0 bg-transparent" style="color: #6f42c1;"><i class="fas fa-qrcode"></i></span>
                                <input type="text" name="asset_tag" id="asset_tag" class="form-control" placeholder="Scan or type here...">
                                <button type="button" class="btn btn-camera-trigger px-3" data-bs-toggle="modal" data-bs-target="#scannerModal">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Serial Number</label>
                            <input type="text" name="serial_number" id="serial_number" class="form-control" placeholder="Enter Serial">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Brand & Model</label>
                            <input type="text" name="brand_model" id="brand_model" class="form-control" placeholder="e.g. HP ProBook 440">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Equipment Type</label>
                            <select name="type" id="equipment_type" class="form-select">
                                <option value="">Select Type</option>
                                <option value="Desktop">Desktop</option>
                                <option value="Laptop">Laptop</option>
                                <option value="Monitor">Monitor</option>
                                <option value="Accessories">Accessories</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Deployment Location</label>
                            <select name="location" class="form-select">
                                <option>BDO</option>
                                <option>Grab COE</option>
                                <option>Shark Ninja</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date Acquired</label>
                            <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Asset Status</label>
                            <select name="status" class="form-select" style="color: #28a745; font-weight: 700;">
                                <option value="Active">● Active</option>
                                <option value="Replacement">● Replacement</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="text-center mt-5">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-check-circle me-2"></i> Save Asset Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="scannerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 25px;">
                <div class="modal-header border-0">
                    <h6 class="modal-title fw-800" style="color: #6f42c1;"><i class="fas fa-camera me-2"></i>SCAN QR CODE</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="stopScanner()"></button>
                </div>
                <div class="modal-body">
                    <div id="reader"></div>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <p class="small text-muted fw-bold">Point the camera at the asset's QR code</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let html5QrCode;

        const scannerModal = document.getElementById('scannerModal');
        scannerModal.addEventListener('shown.bs.modal', function () {
            html5QrCode = new Html5Qrcode("reader");
            const config = { fps: 15, qrbox: { width: 250, height: 250 } };

            html5QrCode.start(
                { facingMode: "environment" }, 
                config,
                (decodedText) => {
                    // Mag-split ng data gamit ang pipe "|"
                    // Format: Tag|Serial|Model|Type
                    const parts = decodedText.split('|');

                    if (parts.length >= 1) document.getElementById('asset_tag').value = parts[0].trim();
                    if (parts.length >= 2) document.getElementById('serial_number').value = parts[1].trim();
                    if (parts.length >= 3) document.getElementById('brand_model').value = parts[2].trim();
                    
                    // Auto-select for Equipment Type dropdown
                    if (parts.length >= 4) {
                        const typeVal = parts[3].trim().toLowerCase();
                        const select = document.getElementById('equipment_type');
                        for (let i = 0; i < select.options.length; i++) {
                            if (select.options[i].value.toLowerCase() === typeVal) {
                                select.selectedIndex = i;
                                break;
                            }
                        }
                    }

                    stopScanner();
                    bootstrap.Modal.getInstance(scannerModal).hide();
                    
                    // Visual feedback: palitan ang background color sandali
                    const fields = ['asset_tag', 'serial_number', 'brand_model', 'equipment_type'];
                    fields.forEach(id => {
                        const el = document.getElementById(id);
                        if(el) {
                            el.style.backgroundColor = "#f0fff4";
                            el.style.borderColor = "#28a745";
                        }
                    });
                }
            ).catch(err => console.error("Scanner Error: ", err));
        });

        function stopScanner() {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().then(() => html5QrCode.clear());
            }
        }

        scannerModal.addEventListener('hidden.bs.modal', stopScanner);
    </script>
</body>
</html>