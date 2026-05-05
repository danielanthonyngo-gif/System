<?php
session_start();
// Check if Administrator
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrator') {
    header("Location: index.php");
    exit();
}
include 'config.php';

$loggedInUser = "Guest"; 
if (isset($_SESSION['user_id'])) {
    $u_id = $_SESSION['user_id'];
    $u_query = mysqli_query($conn, "SELECT fullname FROM users WHERE id = '$u_id' LIMIT 1");
    if ($u_row = mysqli_fetch_assoc($u_query)) {
        $loggedInUser = $u_row['fullname'];
    }
}

// ==========================================
// NEW: Logic for Unlocking User
// ==========================================
if (isset($_GET['unlock_id'])) {
    $unlock_id = mysqli_real_escape_string($conn, $_GET['unlock_id']);
    // I-reset ang login_attempts sa 0 at gawing 'Active' ang status
    $unlock_sql = "UPDATE users SET status='Active', login_attempts=0 WHERE id='$unlock_id'";
    if (mysqli_query($conn, $unlock_sql)) {
        echo "<script>alert('Account Unlocked Successfully!'); window.location='manage_user.php';</script>";
    }
}

// Logic for Adding User
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user_submit'])) {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']); 
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $status = "Active";

    $sql = "INSERT INTO users (fullname, username, password, role, status, login_attempts) 
            VALUES ('$fullname', '$username', '$hashed_password', '$role', '$status', 0)";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('User Added Successfully!'); window.location='manage_user.php';</script>";
    }
}

// Logic for Editing User
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user_submit'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']); 
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $update_sql = "UPDATE users SET fullname='$fullname', role='$role', status='$status' WHERE id='$user_id'";
    if (mysqli_query($conn, $update_sql)) {
        echo "<script>alert('User Updated Successfully!'); window.location='manage_user.php';</script>";
    }
}

// Logic for Deleting User
if (isset($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    if ($delete_id == $_SESSION['user_id']) {
        echo "<script>alert('Bawal i-delete ang sariling account!'); window.location='manage_user.php';</script>";
    } else {
        if (mysqli_query($conn, "DELETE FROM users WHERE id = '$delete_id'")) {
            echo "<script>alert('User Deleted!'); window.location='manage_user.php';</script>";
        }
    }
}

$query = "SELECT * FROM users ORDER BY id DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspiro | Manage Users</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { 
            --app-bg: #f4f7fe;
            --main-gradient: linear-gradient(135deg, #7A1CAC 0%, #7A1CAC 100%);
            --accent-purple: #8e44ad;
            --sidebar-width: 260px;
        }

        body { background-color: var(--app-bg); font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; }
        .main-content { margin-left: var(--sidebar-width); padding: 35px; }

        .glass-header-container {
            background: white; border-radius: 35px; padding: 25px 40px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.03); margin-bottom: 40px;
        }

        .header-title-section h2 { color: var(--accent-purple); font-weight: 700; font-size: 1.6rem; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .header-title-section p { color: #a3aed0; margin: 0; font-size: 0.95rem; font-weight: 500; }

        .user-nav-section { display: flex; align-items: center; gap: 15px; }
        .user-info-text { text-align: right; }
        .user-name-top { color: #2E073F; font-weight: 600; font-size: 1rem; margin-bottom: 0; }
        .sign-out-link { color: #AD49E1; text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: 0.2s; }
        .profile-avatar-pill { width: 55px; height: 55px; background: var(--main-gradient); color: white; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.4rem; box-shadow: 0 8px 20px rgba(142, 68, 173, 0.25); }

        .table-container { background: white; border-radius: 28px; padding: 20px; box-shadow: 0 15px 35px rgba(111, 66, 193, 0.03); overflow: hidden; border: none; }
        .table thead th { color: #a3aed0; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 20px; border-bottom: 1px solid #f1f1f7; }
        .table tbody td { padding: 18px 20px; color: #2b3674; font-weight: 700; font-size: 0.95rem; }

        /* Status Badges */
        .badge-active { background: #d1fae5; color: #065f46; padding: 6px 14px; border-radius: 10px; font-weight: 800; font-size: 0.7rem; }
        .badge-inactive { background: #fee2e2; color: #991b1b; padding: 6px 14px; border-radius: 10px; font-weight: 800; font-size: 0.7rem; }
        .badge-locked { background: #2E073F; color: #ffffff; padding: 6px 14px; border-radius: 10px; font-weight: 800; font-size: 0.7rem; }
        
        .btn-add { background: var(--main-gradient); color: white; border: none; padding: 12px 28px; border-radius: 18px; font-weight: 800; box-shadow: 0 8px 15px rgba(111, 66, 193, 0.2); transition: 0.3s; }
        .btn-action-edit { background: #efebf1; color: #7A1CAC; border: none; padding: 10px; border-radius: 12px; transition: 0.3s; }
        .btn-action-delete { background: #fff5f5; color: #e53e3e; border: none; padding: 10px; border-radius: 12px; transition: 0.3s; cursor: pointer; display: inline-block; }
        .btn-action-unlock { background: #7A1CAC; color: #fff; border: none; padding: 10px; border-radius: 12px; transition: 0.3s; text-decoration: none; }

        .modal-content { border-radius: 30px; border: none; }
        .modal-header { background: var(--main-gradient); color: white; border-radius: 30px 30px 0 0; padding: 25px; }
        .form-control, .form-select { border-radius: 15px; border: 2px solid #f1f0f7; padding: 12px; font-weight: 600; background: #fcfaff; }

        @media (max-width: 992px) { .main-content { margin-left: 0; padding: 20px; } }
    </style>
</head>
<body>

  <?php include 'aside.php'; ?>

    <div class="main-content">
        <div class="glass-header-container">
            <div class="header-title-section">
                <h2>User Management</h2>
                <p>Configure system access and user roles</p>
            </div>

            <div class="user-nav-section">
                <div class="user-info-text">
                    <div class="user-name-top"><?php echo htmlspecialchars($loggedInUser); ?></div>
                    <a href="logout.php" class="sign-out-link">Sign Out</a>
                </div>
                <div class="profile-avatar-pill">
                    <?php echo strtoupper(substr($loggedInUser, 0, 1)); ?>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 px-2">
            <h5 class="fw-800 m-0" style="color: #2E073F;">User Directory</h5>
            <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-plus me-2"></i> Add New User
            </button>
        </div>

        <div class="table-container">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Profile & Full Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): 
                        // Logic para sa kulay ng Badge
                        if($row['status'] == 'Active') {
                            $statClass = 'badge-active';
                        } elseif($row['status'] == 'locked') {
                            $statClass = 'badge-locked';
                        } else {
                            $statClass = 'badge-inactive';
                        }
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-3">
                                <div style="width:40px; height:40px; background: #f4f7fe; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#6f42c1; font-weight:800;">
                                    <?php echo strtoupper(substr($row['fullname'], 0, 1)); ?>
                                </div>
                                <span><?php echo htmlspecialchars($row['fullname']); ?></span>
                            </div>
                        </td>
                        <td class="text-muted small"><?php echo htmlspecialchars($row['username']); ?></td>
                        <td>
                            <span class="badge bg-light text-dark fw-800" style="font-size:0.7rem; border: 1px solid #f1f1f7; padding: 6px 12px; border-radius: 8px;">
                                <?php echo htmlspecialchars($row['role']); ?>
                            </span>
                        </td>
                        <td><span class="<?php echo $statClass; ?>"><?php echo strtoupper(htmlspecialchars($row['status'])); ?></span></td>
                        <td class="text-center">
                            
                            <!-- NEW: Unlock Button (Only appears if user is locked and logged in as Admin) -->
                            <?php if ($row['status'] === 'locked'): ?>
                                <a href="manage_user.php?unlock_id=<?php echo $row['id']; ?>" class="btn-action-unlock me-1" title="Unlock Account" onclick="return confirm('Unlock this account?')">
                                    <i class="fas fa-lock-open"></i>
                                </a>
                            <?php endif; ?>

                            <button class="btn-action-edit me-1" 
                                onclick="openEditModal('<?php echo $row['id']; ?>', '<?php echo addslashes($row['fullname']); ?>', '<?php echo $row['role']; ?>', '<?php echo $row['status']; ?>')"
                                data-bs-toggle="modal" data-bs-target="#editUserModal">
                                <i class="fas fa-pen"></i>
                            </button>
                            <a href="javascript:void(0);" onclick="confirmDelete('<?php echo $row['id']; ?>')" class="btn-action-delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODALS (ADD/EDIT/ETC - Parehas pa rin sa dati mong code) -->
    <!-- ... [Include mo dito yung existing modals mo] ... -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openEditModal(id, name, role, status) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_fullname').value = name;
            document.getElementById('edit_role').value = role;
            document.getElementById('edit_status').value = status;
        }

        function confirmDelete(id) {
            Swal.fire({
                title: 'Delete User?',
                text: "This action cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#7A1CAC', 
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'YES, DELETE'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "manage_user.php?delete_id=" + id;
                }
            })
        }
    </script>
</body>
</html>