<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['status'])) {
    exit("<div class='text-danger fw-bold'>Direct access not allowed, Master.</div>");
}

$status = mysqli_real_escape_string($conn, $_GET['status']);

// 1. DYNAMIC SUB-COUNTERS PARA SA MGA CARDS SA ITAAS BASE SA STATUS
// Nagka-count base sa `type` ng asset para sa partikular na status na pinindot
$count_all = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assets WHERE status='$status'"))['total'] ?? 0;
$count_desktop = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assets WHERE status='$status' AND LOWER(type)='desktop'"))['total'] ?? 0;
$count_laptop = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assets WHERE status='$status' AND LOWER(type)='laptop'"))['total'] ?? 0;
$count_monitor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM assets WHERE status='$status' AND LOWER(type)='monitor'"))['total'] ?? 0;

// 2. KUNIN ANG MGA ASSET REKORDS
$assets_query = "SELECT * FROM assets WHERE status='$status' ORDER BY id DESC";
$assets_result = mysqli_query($conn, $assets_query);
?>

<style>
    /* Styling para sa sub-cards na hawig sa image_57c625.png */
    .modal-sub-card {
        background: #ffffff;
        border: 1px solid #f1eaf7 !important;
        border-radius: 20px;
        padding: 20px;
        display: flex;
        align-items: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.01);
    }
    .modal-card-icon {
        width: 48px;
        height: 48px;
        background-color: #f1e6fa;
        color: #7A1CAC;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-right: 15px;
    }
    .modal-card-info p {
        margin: 0;
        font-size: 10px;
        font-weight: 700;
        color: #a3aed0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .modal-card-info h3 {
        margin: 0;
        font-weight: 800;
        color: #2E073F;
        font-size: 1.6rem;
    }
    
    /* Table Headers at Rows Style matching image_57c625.png */
    #modalAssetTable th {
        color: #7A1CAC;
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #f4f7fe;
        padding: 15px 10px;
    }
    #modalAssetTable td {
        font-size: 0.85rem;
        font-weight: 500;
        color: #333333;
        padding: 18px 10px;
        vertical-align: middle;
        border-bottom: 1px solid #f4f7fe;
    }
    .location-badge {
        background-color: #f3ebfc;
        color: #7A1CAC;
        font-weight: 800;
        font-size: 11px;
        padding: 4px 12px;
        border-radius: 8px;
        text-transform: uppercase;
    }
</style>

<!-- HEADER NG MODAL (Tulad ng nasa Top Bar ng image_57c625.png) -->
<div class="d-flex justify-content-between align-items-center mb-4 pt-2">
    <div>
        <h3 class="fw-bold m-0 text-uppercase" style="color: #7A1CAC; letter-spacing: 0.5px;"><?php echo htmlspecialchars($status); ?> ASSETS</h3>
        <p class="text-muted m-0 small" style="font-weight: 500;">Asset Management System (ALL AREAS)</p>
    </div>
</div>

<!-- SUB-COUNTER CARDS ROW -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="modal-sub-card">
            <div class="modal-card-icon"><i class="fas fa-desktop"></i></div>
            <div class="modal-card-info">
                <p>Active Assets</p>
                <h3><?php echo $count_all; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="modal-sub-card">
            <div class="modal-card-icon"><i class="fas fa-network-wired"></i></div>
            <div class="modal-card-info">
                <p>Desktop Assets</p>
                <h3><?php echo $count_desktop; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="modal-sub-card">
            <div class="modal-card-icon"><i class="fas fa-laptop"></i></div>
            <div class="modal-card-info">
                <p>Laptop Assets</p>
                <h3><?php echo $count_laptop; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="modal-sub-card">
            <div class="modal-card-icon"><i class="fas fa-tv"></i></div>
            <div class="modal-card-info">
                <p>Monitor Assets</p>
                <h3><?php echo $count_monitor; ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- ACTIONS BAR (Back at Search Input) -->
<div class="d-flex gap-3 mb-4 align-items-center">
    <button type="button" class="btn btn-outline-purple rounded-pill px-4 fw-600 btn-sm" data-bs-dismiss="modal" style="border-color: #e2e8f0; color: #7A1CAC; font-size: 13px;">
        <i class="fas fa-arrow-left me-1"></i> Back
    </button>
    <div class="input-group rounded-3 style-search" style="background-color: #f4f7fe; border: 1px solid #e2e8f0; max-width: 320px;">
        <span class="input-group-text bg-transparent border-0 pe-0"><i class="fas fa-search text-muted"></i></span>
        <input type="text" id="modalSearchInput" class="form-control bg-transparent border-0 form-control-sm py-2" placeholder="Search ID, tag, serial, or model..." style="box-shadow: none; font-size: 13px;">
    </div>
</div>

<!-- MAIN REKORD TABLE (Kopyang-kopya ang columns ng image_57c625.png) -->
<div class="table-responsive">
    <table class="table align-middle" id="modalAssetTable">
        <thead>
            <tr>
                <th style="width: 7%">ID</th>
                <th style="width: 15%">Inventory Date</th>
                <th style="width: 15%">Asset Tag</th>
                <th style="width: 15%">Serial Number</th>
                <th style="width: 20%">Brand & Model</th>
                <th style="width: 13%">Location</th>
                <th style="width: 10%">Status</th>
                <th style="width: 15%">Date Created</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($assets_result) > 0): ?>
                <?php while ($asset = mysqli_fetch_assoc($assets_result)): ?>
                    <tr class="asset-row">
                        <td class="text-secondary fw-600"><?php echo htmlspecialchars($asset['id']); ?></td>
                        <td>
                            <!-- Kung walang 'inventory_date' sa database mo master, palitan mo ng field name na meron ka -->
                            <?php echo !empty($asset['inventory_date']) ? date('Y–m–d', strtotime($asset['inventory_date'])) : date('Y–m–d', strtotime($asset['created_at'])); ?>
                        </td>
                        <td class="fw-600 text-dark"><?php echo htmlspecialchars($asset['asset_tag'] ?? '—'); ?></td>
                        <td class="text-secondary"><?php echo htmlspecialchars($asset['serial'] ?? '—'); ?></td>
                        <td class="fw-600 text-dark"><?php echo htmlspecialchars($asset['model'] ?? '—'); ?></td>
                        <td>
                            <span class="location-badge">
                                <?php echo htmlspecialchars($asset['location'] ?? 'N/A'); ?>
                            </span>
                        </td>
                        <td>
                            <span class="fw-bold text-success"><?php echo htmlspecialchars($asset['status']); ?></span>
                        </td>
                        <td>
                            <div class="p-0 m-0"><?php echo date('Y–m–d', strtotime($asset['created_at'])); ?></div>
                            <small class="text-muted" style="font-size: 11px;"><?php echo date('H:i:s', strtotime($asset['created_at'])); ?></small>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="fas fa-folder-open fa-2x mb-2 d-block text-muted"></i>
                        Walang nakuhang asset sa ilalim ng status na ito, Master.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- LIVE SEARCH IN MODAL -->
<script>
$(document).ready(function(){
    $("#modalSearchInput").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#modalAssetTable tbody tr.asset-row").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
});
</script>