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

// Logic for Unlocking User
if (isset($_GET['unlock_id'])) {
    $unlock_id = mysqli_real_escape_string($conn, $_GET['unlock_id']);
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

    $checkUser = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($checkUser) > 0) {
        echo "<script>alert('Error: Username already exists!'); window.location='manage_user.php';</script>";
    } else {
        $sql = "INSERT INTO users (fullname, username, password, role, status, login_attempts) 
                VALUES ('$fullname', '$username', '$hashed_password', '$role', '$status', 0)";
        
        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('User Added Successfully!'); window.location='manage_user.php';</script>";
        }
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
        
        /* Main Content Responsiveness */
        .main-content { margin-left: var(--sidebar-width); padding: 35px; transition: all 0.3s ease; }

        .glass-header-container {
            background: white; border-radius: 35px; padding: 25px 40px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.03); margin-bottom: 40px;
            flex-wrap: wrap; gap: 20px;
        }

        .header-title-section h2 { color: var(--accent-purple); font-weight: 700; font-size: 1.6rem; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .header-title-section p { color: #a3aed0; margin: 0; font-size: 0.95rem; font-weight: 500; }

        .user-nav-section { display: flex; align-items: center; gap: 15px; }
        .user-info-text { text-align: right; }
        .user-name-top { color: #2E073F; font-weight: 600; font-size: 1rem; margin-bottom: 0; }
        .sign-out-link { color: #AD49E1; text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: 0.2s; }
        .profile-avatar-pill { width: 55px; height: 55px; background: var(--main-gradient); color: white; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.4rem; box-shadow: 0 8px 20px rgba(142, 68, 173, 0.25); }

        .table-container { background: white; border-radius: 28px; padding: 20px; box-shadow: 0 15px 35px rgba(111, 66, 193, 0.03); border: none; }
        .table thead th { color: #a3aed0; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 20px; border-bottom: 1px solid #f1f1f7; white-space: nowrap; }
        .table tbody td { padding: 18px 20px; color: #2b3674; font-weight: 700; font-size: 0.95rem; }

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

        /* Tablet & Mobile Styles */
        @media (max-width: 992px) { 
            .main-content { margin-left: 0; padding: 20px; } 
            .glass-header-container { border-radius: 20px; padding: 20px; text-align: center; justify-content: center; }
            .user-nav-section { flex-direction: column-reverse; gap: 5px; }
            .user-info-text { text-align: center; }
        }

        @media (max-width: 576px) {
            .header-title-section h2 { font-size: 1.3rem; }
            .btn-add { width: 100%; padding: 15px; }
            .d-flex.justify-content-between { flex-direction: column; gap: 15px; align-items: flex-start !important; }
        }
    </style>
</head>
<body>

  <?php include 'aside.php'; ?>

    <div class="main-content">
        <!-- Header -->
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

        <!-- Table Responsive Wrapper -->
        <div class="table-container">
            <div class="table-responsive">
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
                                    <div style="min-width:40px; height:40px; background: #f4f7fe; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#6f42c1; font-weight:800;">
                                        <?php echo strtoupper(substr($row['fullname'], 0, 1)); ?>
                                    </div>
                                    <span class="text-nowrap"><?php echo htmlspecialchars($row['fullname']); ?></span>
                                </div>
                            </td>
                            <td class="text-muted small"><?php echo htmlspecialchars($row['username']); ?></td>
                            <td>
                                <span class="badge bg-light text-dark fw-800" style="font-size:0.7rem; border: 1px solid #f1f1f7; padding: 6px 12px; border-radius: 8px;">
                                    <?php echo htmlspecialchars($row['role']); ?>
                                </span>
                            </td>
                            <td><span class="<?php echo $statClass; ?>"><?php echo strtoupper(htmlspecialchars($row['status'])); ?></span></td>
                            <td class="text-center text-nowrap">
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
    </div>

    <!-- MODAL: ADD NEW USER (MANAGER REMOVED) -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-700">Add New User Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="manage_user.php" method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-700">Full Name</label>
                            <input type="text" name="fullname" class="form-control" placeholder="Enter Full Name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-700">Username / Email</label>
                            <input type="text" name="username" class="form-control" placeholder="Enter Username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-700">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Create Password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-700">User Role</label>
                            <select name="role" class="form-select" required>
                                <option value="Technical Support">Technical Support</option>
                                <option value="Administrator">Administrator</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light rounded-3 fw-700" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_user_submit" class="btn btn-add px-4">Create Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: EDIT USER (MANAGER REMOVED) -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-700">Edit User Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="manage_user.php" method="POST">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-700">Full Name</label>
                            <input type="text" name="fullname" id="edit_fullname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-700">Role</label>
                            <select name="role" id="edit_role" class="form-select">
                                <option value="Technical Support">Technical Support</option>
                                <option value="Administrator">Administrator</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-700">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light rounded-3 fw-700" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="edit_user_submit" class="btn btn-add px-4">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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