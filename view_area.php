<?php
    session_start();
    include 'config.php';

    $display_name = "Guest";
    if (isset($_SESSION['user_id'])) {
        $user_id    = $_SESSION['user_id'];
        $user_query = mysqli_query($conn, "SELECT fullname FROM users WHERE id = '$user_id' LIMIT 1");
        if ($row = mysqli_fetch_assoc($user_query)) {
            $display_name = $row['fullname'];
        }
    }

    // --- HELPER FUNCTION TO DETECT IF THE AREA IS EXISTING ---
    function is_duplicate_area($conn, $new_name) {
        $clean_name = mysqli_real_escape_string($conn, trim($new_name));
        $query = mysqli_query($conn, "SELECT account_id FROM client_accounts WHERE LOWER(client_name) = LOWER('$clean_name') LIMIT 1");
        return mysqli_num_rows($query) > 0;
    }

    // --- ADD LOGIC ---
    $error_msg = "";
    if (isset($_POST['add_area'])) {
        $new_name = trim($_POST['area_name']);
        $building = $_POST['building_type'];
        
        if (!empty($new_name)) {
            if (is_duplicate_area($conn, $new_name)) {
                $error_msg = "The area '" . htmlspecialchars($new_name) . "' already exists!";
            } else {
                $building_id = ($building == 'Alpha') ? 1 : 2;
                $safe_name = mysqli_real_escape_string($conn, $new_name);
                
                $insert_query = "INSERT INTO client_accounts (building_id, client_name, in_use_count, avail_count) VALUES ($building_id, '$safe_name', 0, 0)";
                
                if (mysqli_query($conn, $insert_query)) {
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $error_msg = "Database Error: " . mysqli_error($conn);
                }
            }
        }
    }

    // --- DELETE LOGIC ---
    if (isset($_GET['del_id'])) {
        $target_id = intval($_GET['del_id']);

        //check mu muna kung si $target_id ay may existing assets na naka-assign dito, if yes, abort deletion and show error message
        $check_query = "SELECT COUNT(*) as t FROM assets as a INNER JOIN client_accounts as b ON a.location = b.account_id WHERE b.account_id = $target_id";
        $check_result = mysqli_query($conn, $check_query);
        $row = mysqli_fetch_assoc($check_result);
        if ($row['t'] > 0) {
            $error_msg = "Cannot delete area with existing assets assigned.";
        } else {
            $delete_query = "DELETE FROM client_accounts WHERE account_id = $target_id";
            if (mysqli_query($conn, $delete_query)) {
                $_SESSION['delete_success'] = true;
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
        } else {
            $error_msg = "Failed to delete area: " . mysqli_error($conn);
        }
    }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspiro | View Areas</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        :root {
            --main-gradient: linear-gradient(135deg, #7A1CAC 0%, #7A1CAC 100%);
            --accent-purple: #8e44ad;
            --bg-light: #f4f7fe;
            --sidebar-width: 260px;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #362d36;
            margin: 0;
        }

        .content-wrapper {
            margin-left: var(--sidebar-width);
            padding: 35px;
            min-height: 100vh;
        }

        .glass-header-container {
            background: white;
            border-radius: 35px;
            padding: 25px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.03);
            margin-bottom: 40px;
            width: 100%;
        }

        .header-title-section h2 {
            color: var(--accent-purple);
            font-weight: 700;
            font-size: 1.6rem;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header-title-section p {
            color: #a3aed0;
            margin: 0;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .user-nav-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info-text { text-align: right; }

        .user-name-top {
            color: #2E073F;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0;
        }

        .sign-out-link {
            color: #AD49E1;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: 0.2s;
        }

        .profile-avatar-pill {
            width: 55px; height: 55px;
            background: var(--main-gradient);
            color: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.4rem;
            box-shadow: 0 8px 20px rgba(142, 68, 173, 0.25);
        }

        .btn-add-area {
            background: var(--main-gradient); color: white; border: none; padding: 12px 25px; border-radius: 18px; font-weight: 700; transition: 0.3s;
            box-shadow: 0 8px 15px rgba(122, 28, 172, 0.2);
        }
        .btn-add-area:hover { transform: translateY(-2px); box-shadow: 0 12px 20px rgba(122, 28, 172, 0.3); color: white; }

        .area-card {
            background: white; border-radius: 28px; border: none; transition: 0.4s; overflow: hidden; height: 100%; position: relative; box-shadow: 0 10px 25px rgba(0,0,0,0.03);
        }
        .area-card:hover { transform: translateY(-10px); }

        .delete-overlay {
            position: absolute; top: 10px; right: 10px; background: rgba(255, 0, 0, 0.1); color: #ff4757;
            border: none; width: 25px; height: 25px; border-radius: 8px; font-size: 0.7rem;
            display: flex; align-items: center; justify-content: center; opacity: 0; transition: 0.3s; z-index: 5;
            cursor: pointer;
        }
        .area-card:hover .delete-overlay { opacity: 1; }

        .card-header-label { background: #fcfaff; padding: 15px; font-size: 0.75rem; font-weight: 800; color: #3b1845; text-transform: uppercase; border-bottom: 1px solid #f1f0f7; }
        .pc-icon-wrapper { padding: 25px 0; font-size: 2.5rem; background: var(--main-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-container { display: grid; grid-template-columns: 1fr 1fr; background: #ffffff; padding-bottom: 10px; }
        .stat-box h5 { margin: 0; font-weight: 800; color: #1e293b; }
        .stat-box small { font-size: 0.6rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; }

        .section-title { font-weight: 800; font-size: 1.1rem; margin-bottom: 2rem; color: var(--accent-purple); display: flex; align-items: center; gap: 15px; }
        .section-title::after { content: ""; flex-grow: 1; height: 2px; background: linear-gradient(90deg, #e2e8f0, transparent); }

        @media (max-width: 992px) { .content-wrapper { margin-left: 0; } }

        /* Loader & Pop-up Setup */
        .modal-loader { display: flex; justify-content: center; align-items: center; min-height: 480px; flex-direction: column; gap: 15px; color: #7A1CAC; background: #ffffff; border-radius: 16px; }
        .premium-popup-container { background: #ffffff; border-radius: 16px; overflow: hidden; position: relative; min-height: 480px; }
        .live-inventory-frame { width: 100%; height: 600px; border: none; display: none; border-radius: 16px; background: #ffffff; }
    </style>
</head>
<body>

<?php 
    include 'aside.php';
    $title     = "VIEW AREAS";
    $sub_title = "Location Record & Monitoring"; 
?>

<div class="content-wrapper">

     <?php include 'header.php'; ?>

    <div class="container-fluid p-0">
        
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 mb-4" role="alert" style="border-radius: 18px; box-shadow: 0 4px 15px rgba(255,0,0,0.05);">
                <i class="fas fa-exclamation-circle me-2"></i> <strong>Error:</strong> <?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-end mb-4">
            <button class="btn-add-area" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus-circle me-2"></i> ADD NEW AREA
            </button>
        </div>

        <div class="section-title">ALPHA BUILDING & OTHERS</div>
        <div class="row g-4 mb-5">
            <?php 
                $alpha_query = mysqli_query($conn, "SELECT * FROM client_accounts WHERE building_id = 1 ORDER BY account_id ASC");
                while ($area = mysqli_fetch_assoc($alpha_query)):
                    $id    = $area['account_id'];
                    $name  = $area['client_name'];
                    
                    $res   = mysqli_query($conn, "SELECT COUNT(*) as t FROM assets as a INNER JOIN client_accounts as b ON a.location = b.account_id WHERE b.account_id = " . intval($id));
                    $count = mysqli_fetch_assoc($res)['t'] ?? 0;
            ?>
            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="area-card text-center">
                    <a onclick="confirmDelete(<?php echo $id; ?>, '<?php echo addslashes($name); ?>')" class="delete-overlay"><i class="fas fa-times"></i></a>
                    <a href="#" data-location="<?php echo htmlspecialchars($name); ?>" class="text-decoration-none view-assets-popup-trigger">
                        <div class="card-header-label"><?php echo htmlspecialchars($name); ?></div>
                        <div class="pc-icon-wrapper"><i class="fas fa-desktop"></i></div>
                        <div class="stat-container">
                            <div class="stat-box border-end"><h5><?php echo $count; ?></h5><small>In Use</small></div>
                            <div class="stat-box"><h5>0</h5><small>Avail</small></div>
                        </div>
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <div class="section-title">BETA BUILDING</div>
        <div class="row g-4 mb-4">
            <?php 
                $beta_query = mysqli_query($conn, "SELECT * FROM client_accounts WHERE building_id = 2 ORDER BY account_id ASC");
                while ($area = mysqli_fetch_assoc($beta_query)):
                    $id    = $area['account_id'];
                    $name  = $area['client_name'];
                    
                    $res   = mysqli_query($conn, "SELECT COUNT(*) as t FROM assets as a INNER JOIN client_accounts as b ON a.location = b.account_id WHERE b.account_id = " . intval($id));
                    $count = mysqli_fetch_assoc($res)['t'] ?? 0;
            ?>
            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="area-card text-center">
                    <a onclick="confirmDelete(<?php echo $id; ?>, '<?php echo addslashes($name); ?>')" class="delete-overlay"><i class="fas fa-times"></i></a>
                    <a href="#" data-location="<?php echo htmlspecialchars($name); ?>" class="text-decoration-none view-assets-popup-trigger">
                        <div class="card-header-label"><?php echo htmlspecialchars($name); ?></div>
                        <div class="pc-icon-wrapper"><i class="fas fa-desktop"></i></div>
                        <div class="stat-container">
                            <div class="stat-box border-end"><h5><?php echo $count; ?></h5><small>In Use</small></div>
                            <div class="stat-box"><h5>0</h5><small>Avail</small></div>
                        </div>
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 25px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 style="color: #7A1CAC; font-weight: 800;">ADD NEW AREA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-700">Area Name</label>
                        <input type="text" name="area_name" class="form-control" required style="border-radius: 12px; padding: 12px; border: 1px solid #e2e8f0;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-700">Select Building</label>
                        <select name="building_type" class="form-select" style="border-radius: 12px; padding: 12px; border: 1px solid #e2e8f0;">
                            <option value="Alpha">Alpha Building</option>
                            <option value="Beta">Beta Building</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" name="add_area" class="btn-add-area w-100">SAVE NEW AREA</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="assetsPopModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 24px; border: none; background-color: #f8fafc; box-shadow: 0 30px 70px rgba(46, 7, 63, 0.25);">
            <div class="modal-header border-0 px-4 pt-4 pb-2 d-flex align-items-center justify-content-between" style="background: white; border-top-left-radius: 24px; border-top-right-radius: 24px;">
                <div class="d-flex align-items-center gap-3">
                    <div style="background: linear-gradient(135deg, rgba(122, 28, 172, 0.1) 0%, rgba(46, 7, 63, 0.1) 100%); width: 50px; height: 50px; border-radius: 16px; display: flex; align-items: center; justify-content: center; color: #7A1CAC;">
                        <i class="fas fa-boxes-stacked fa-lg"></i>
                    </div>
                    <div>
                        <h4 class="fw-800 m-0" style="color: #2E073F; font-size: 1.4rem; letter-spacing: -0.3px;"><span id="popModalLocationName" style="color: #7A1CAC;">AREA</span> INVENTORY</h4>
                        <small class="text-muted fw-600" style="font-size: 0.85rem;">
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close" style="background-color: #f1f5f9; padding: 10px; border-radius: 50%; font-size: 0.75rem;"></button>
            </div>
            
            <div class="modal-body p-3">
                <div class="premium-popup-container">
                    <div class="modal-loader" id="popWindowLoader">
                        <div class="spinner-border" role="status" style="width: 3rem; height: 3rem; color: #7A1CAC; border-width: 4px;"></div>
                        <span class="fw-700 text-muted mt-2">Connecting live database logs...</span>
                    </div>
                    <iframe id="popupLiveFrame" class="live-inventory-frame" src=""></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function confirmDelete(id, areaName) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to delete '" + areaName + "'. This cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#7A1CAC', 
            cancelButtonColor: '#ff4757',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            background: '#ffffff',
            borderRadius: '25px'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "?del_id=" + id;
            }
        });
    }

    $(document).ready(function() {
        $('.view-assets-popup-trigger').on('click', function(e) {
            e.preventDefault();
            
            let targetLocation = $(this).data('location');
            $('#popModalLocationName').text(targetLocation.toUpperCase());
            
            $('#popWindowLoader').show();
            $('#popupLiveFrame').hide();
            
            var assetsModal = new bootstrap.Modal(document.getElementById('assetsPopModal'));
            assetsModal.show();
            
            // DITO ANG SIKRETONG FIX: Nagpasa tayo ng embed flag sa URL parameter nang hindi binabago ang inner page configurations!
            let queryUrl = 'inventory_page.php?location=' + encodeURIComponent(targetLocation) + '&layout=embed';
            $('#popupLiveFrame').attr('src', queryUrl);
            
            $('#popupLiveFrame').on('load', function() {
                $('#popWindowLoader').hide();
                $(this).show();
            });
        });
    });
</script>

<?php if (!empty($error_msg)): ?>
<script>
    var addModal = new bootstrap.Modal(document.getElementById('addModal'));
    addModal.show();
</script>
<?php endif; ?>

<?php if (isset($_SESSION['delete_success'])): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Deleted!',
        text: 'The area has been successfully deleted.',
        timer: 2500,
        showConfirmButton: false,
        background: '#ffffff',
        borderRadius: '25px'
    });
</script>
<?php unset($_SESSION['delete_success']); ?>
<?php endif; ?>

</body>
</html>