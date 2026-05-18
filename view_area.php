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

    // --- INITIALIZE LISTS ---
    if (! isset($_SESSION['alpha_list'])) {
    $_SESSION['alpha_list'] = [
        ['name' => 'BDO'], ['name' => 'BDO Insure'], ['name' => 'BDO Life'],
        ['name' => 'Pacsan'], ['name' => 'BDO Core'], ['name' => 'Flight Center'],
        ['name' => "Manila Doctor's Hospital"], ['name' => 'Ignite'], ['name' => 'Viagogo'],
    ];
    }
    if (! isset($_SESSION['beta_list'])) {
    $_SESSION['beta_list'] = [
        ['name' => 'Grab Support'], ['name' => 'Grab COE'], ['name' => 'Shark Ninja'],
        ['name' => 'Hallmark'], ['name' => 'ANA'], ['name' => 'AUB'],
    ];
    }

    // --- HELPER FUNCTION TO CHECK DUPLICATES ---
    function is_duplicate_area($new_name) {
        $clean_name = strtolower(trim($new_name));
        
        // Check sa Alpha List
        foreach ($_SESSION['alpha_list'] as $area) {
            if (strtolower(trim($area['name'])) === $clean_name) {
                return true;
            }
        }
        // Check sa Beta List
        foreach ($_SESSION['beta_list'] as $area) {
            if (strtolower(trim($area['name'])) === $clean_name) {
                return true;
            }
        }
        return false;
    }

    // --- ADD LOGIC ---
    $error_msg = "";
    if (isset($_POST['add_area'])) {
    $new_name = trim($_POST['area_name']);
    $building = $_POST['building_type'];
    if (! empty($new_name)) {
        // I-reject kung duplicate sa kahit anong listahan
        if (is_duplicate_area($new_name)) {
            $error_msg = "The area '" . htmlspecialchars($new_name) . "' already exists!";
        } else {
            if ($building == 'Alpha') {
                $_SESSION['alpha_list'][] = ['name' => $new_name];
            } else { 
                $_SESSION['beta_list'][] = ['name' => $new_name];
            }
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
    }

    // --- DELETE LOGIC ---
    if (isset($_GET['del'])) {
    $target = $_GET['del'];
    $type   = $_GET['type'];
    if ($type == 'alpha') {
        foreach ($_SESSION['alpha_list'] as $k => $v) {if ($v['name'] == $target) {
            unset($_SESSION['alpha_list'][$k]);
        }
        }
        $_SESSION['alpha_list'] = array_values($_SESSION['alpha_list']);
    } else {
        foreach ($_SESSION['beta_list'] as $k => $v) {if ($v['name'] == $target) {
            unset($_SESSION['beta_list'][$k]);
        }
        }
        $_SESSION['beta_list'] = array_values($_SESSION['beta_list']);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
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

        .status-card {
            border: none;
            border-radius: 25px;
            color: white;
            padding: 30px;
            position: relative;
            overflow: hidden;
            transition: 0.3s;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
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
        }
        .area-card:hover .delete-overlay { opacity: 1; }

        .card-header-label { background: #fcfaff; padding: 15px; font-size: 0.75rem; font-weight: 800; color: #3b1845; text-transform: uppercase; border-bottom: 1px solid #f1f0f7; }
        .pc-icon-wrapper { padding: 25px 0; font-size: 2.5rem; background: var(--main-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-container { display: grid; grid-template-columns: 1fr 1fr; background: #ffffff; padding-bottom: 10px; }
        .stat-box h5 { margin: 0; font-weight: 800; color: #1e293b; }
        .stat-box small { font-size: 0.6rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; }

        .section-title { font-weight: 800; font-size: 1.1rem; margin-bottom: 2rem; color: var(--accent-purple); display: flex; align-items: center; gap: 15px; }
        .section-title::after { content: ""; flex-grow: 1; height: 2px; background: linear-gradient(90deg, #e2e8f0, transparent); }

        @media (max-width: 992px) { .content { margin-left: 0; } }
    </style>
</head>
<body>

<?php include 'aside.php';
    $title     = "VIEW AREAS";
$sub_title = "Location Record & Monitoring"; ?>

<div class="content-wrapper">

     <?php include 'header.php'; ?>

    <div class="container-fluid p-0">
        
        <!-- ERROR NOTIFICATION BANNER -->
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 mb-4" role="alert" style="border-radius: 18px; box-shadow: 0 4px 15px rgba(255,0,0,0.05);">
                <i class="fas fa-exclamation-circle me-2"></i> <strong>Error:</strong> <?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="disabled" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- BUTTON ALIGNED TO THE RIGHT -->
        <div class="d-flex justify-content-end mb-4">
            <button class="btn-add-area" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus-circle me-2"></i> ADD NEW AREA
            </button>
        </div>

        <!-- ALPHA -->
        <div class="section-title">ALPHA BUILDING & OTHERS</div>
        <div class="row g-4 mb-5">
            <?php foreach ($_SESSION['alpha_list'] as $area):
                    $name  = $area['name'];
                    $res   = mysqli_query($conn, "SELECT COUNT(*) as t FROM assets WHERE location = '" . mysqli_real_escape_string($conn, $name) . "'");
                    $count = mysqli_fetch_assoc($res)['t'] ?? 0;
            ?>
            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="area-card text-center">
                    <a href="?del=<?php echo urlencode($name); ?>&type=alpha" class="delete-overlay" onclick="return confirm('Delete area?')"><i class="fas fa-times"></i></a>
                    <a href="inventory_page.php?location=<?php echo urlencode($name); ?>" class="text-decoration-none">
                        <div class="card-header-label"><?php echo $name; ?></div>
                        <div class="pc-icon-wrapper"><i class="fas fa-desktop"></i></div>
                        <div class="stat-container">
                            <div class="stat-box border-end"><h5><?php echo $count; ?></h5><small>In Use</small></div>
                            <div class="stat-box"><h5>0</h5><small>Avail</small></div>
                        </div>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- BETA -->
        <div class="section-title">BETA BUILDING</div>
        <div class="row g-4 mb-4">
            <?php foreach ($_SESSION['beta_list'] as $area):
                    $name  = $area['name'];
                    $res   = mysqli_query($conn, "SELECT COUNT(*) as t FROM assets WHERE location = '" . mysqli_real_escape_string($conn, $name) . "'");
                    $count = mysqli_fetch_assoc($res)['t'] ?? 0;
            ?>
            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="area-card text-center">
                    <a href="?del=<?php echo urlencode($name); ?>&type=beta" class="delete-overlay" onclick="return confirm('Delete area?')"><i class="fas fa-times"></i></a>
                    <a href="inventory_page.php?location=<?php echo urlencode($name); ?>" class="text-decoration-none">
                        <div class="card-header-label"><?php echo $name; ?></div>
                        <div class="pc-icon-wrapper"><i class="fas fa-desktop"></i></div>
                        <div class="stat-container">
                            <div class="stat-box border-end"><h5><?php echo $count; ?></h5><small>In Use</small></div>
                            <div class="stat-box"><h5>0</h5><small>Avail</small></div>
                        </div>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
</div>
</div>

<!-- MODAL -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 25px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 style="color: #7A1CAC; font-weight: 800;">ADD NEW AREA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-700">Area Name</label>
                        <input type="text" name="area_name" class="form-control" required style="border-radius: 12px; padding: 12px; border: 1px solid #e2e8f0;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-700">Select Building</label>
                        <select name="building_type" class="form-select" style="border-radius: 12px; padding: 12px; border: 1px solid #e2e8f0;">
                            <option value="Alpha">Alpha Building & Others</option>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Script para awtomatikong magpakita ang modal ulit kapag may error -->
<?php if (!empty($error_msg)): ?>
<script>
    var addModal = new bootstrap.Modal(document.getElementById('addModal'));
    addModal.show();
</script>
<?php endif; ?>

</body>
</html>