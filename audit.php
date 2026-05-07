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
        $display_name = $loggedInUser;
    }
}



// Handle filter and pagination
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$where_clause = "WHERE 1=1";
$search = "";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where_clause .= " AND (user_fullname LIKE '%$search%' OR action LIKE '%$search%' OR entity_type LIKE '%$search%' OR ip_address LIKE '%$search%')";
}
if (isset($_GET['action']) && !empty($_GET['action'])) {
    $action = mysqli_real_escape_string($conn, $_GET['action']);
    $where_clause .= " AND action = '$action'";
}
if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $user_id = mysqli_real_escape_string($conn, $_GET['user_id']);
    $where_clause .= " AND user_id = '$user_id'";
}
if (isset($_GET['from_date']) && !empty($_GET['from_date'])) {
    $from_date = mysqli_real_escape_string($conn, $_GET['from_date']);
    $where_clause .= " AND DATE(created_at) >= '$from_date'";
}
if (isset($_GET['to_date']) && !empty($_GET['to_date'])) {
    $to_date = mysqli_real_escape_string($conn, $_GET['to_date']);
    $where_clause .= " AND DATE(created_at) <= '$to_date'";
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM audit_log $where_clause";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

// Get audit logs
$query = "SELECT * FROM audit_log $where_clause ORDER BY created_at DESC LIMIT $offset, $limit";
$result = mysqli_query($conn, $query);

// Get distinct actions for filter dropdown
$actions_query = "SELECT DISTINCT action FROM audit_log ORDER BY action";
$actions_result = mysqli_query($conn, $actions_query);

// Get users for filter dropdown
$users_query = "SELECT id, fullname FROM users ORDER BY fullname";
$users_result = mysqli_query($conn, $users_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspiro | Audit Trail</title>
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
        .profile-avatar-pill { width: 55px; height: 55px; background: var(--main-gradient); color: white; border-radius: 25px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.4rem; box-shadow: 0 8px 20px rgba(142, 68, 173, 0.25); }

        .filter-card {
            background: white; border-radius: 28px; padding: 20px; margin-bottom: 25px;
            box-shadow: 0 15px 35px rgba(111, 66, 193, 0.03);
        }
        
        .table-container { background: white; border-radius: 28px; padding: 20px; box-shadow: 0 15px 35px rgba(111, 66, 193, 0.03); border: none; }
        .table thead th { color: #a3aed0; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 20px; border-bottom: 1px solid #f1f1f7; }
        .table tbody td { padding: 18px 20px; color: #2b3674; font-weight: 600; font-size: 0.85rem; vertical-align: middle; }

        .badge-action {
            padding: 6px 12px; border-radius: 20px; font-weight: 700; font-size: 0.7rem;
            display: inline-block; text-align: center;
        }
        .badge-create { background: #d1fae5; color: #065f46; }
        .badge-update { background: #dbeafe; color: #1e40af; }
        .badge-delete { background: #fee2e2; color: #991b1b; }
        .badge-login { background: #fef3c7; color: #92400e; }
        .badge-lock { background: #2E073F; color: white; }
        .badge-default { background: #f3f4f6; color: #374151; }

        .view-details-btn {
            background: #f4f7fe; border: none; padding: 8px 12px; border-radius: 10px;
            color: #7A1CAC; font-size: 0.75rem; cursor: pointer; transition: 0.2s;
        }
        .view-details-btn:hover { background: #7A1CAC; color: white; }

        .json-preview {
            max-width: 300px; overflow: hidden; text-overflow: ellipsis;
            white-space: nowrap; font-size: 0.75rem; color: #6c757d;
        }

        /* Modal styles */
        .modal-content { border-radius: 30px; border: none; }
        .modal-header { background: var(--main-gradient); color: white; border-radius: 30px 30px 0 0; padding: 20px 25px; }

        @media (max-width: 992px) {
            .main-content { margin-left: 0; padding: 20px; }
        }
    </style>
</head>
<body>

<?php include 'aside.php'; ?>

<div class="main-content">
    <?php
        $title = "Audit Trail";
        $sub_title = "Track all system activities and user actions";
        include 'header.php';
    ?>

    <!-- Filter Section -->
    <div class="filter-card">
        <form method="GET" action="audit.php" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-600 small">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name, action, IP..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-600 small">Action Type</label>
                <select name="action" class="form-select">
                    <option value="">All Actions</option>
                    <?php while($act = mysqli_fetch_assoc($actions_result)): ?>
                        <option value="<?php echo $act['action']; ?>" <?php echo (isset($_GET['action']) && $_GET['action'] == $act['action']) ? 'selected' : ''; ?>>
                            <?php echo ucfirst($act['action']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-600 small">User</label>
                <select name="user_id" class="form-select">
                    <option value="">All Users</option>
                    <?php 
                    mysqli_data_seek($users_result, 0);
                    while($user = mysqli_fetch_assoc($users_result)): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo (isset($_GET['user_id']) && $_GET['user_id'] == $user['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['fullname']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-600 small">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?php echo isset($_GET['from_date']) ? $_GET['from_date'] : ''; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-600 small">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?php echo isset($_GET['to_date']) ? $_GET['to_date'] : ''; ?>">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-add w-100" style="padding: 10px;">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
            <div class="col-md-1">
                <a href="audit.php" class="btn btn-light w-100" style="padding: 10px; border-radius: 18px;">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Audit Logs Table -->
    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-800 m-0" style="color: #2E073F;">
                <i class="fas fa-history me-2"></i> System Activity Logs
            </h5>
            <div>
                <span class="text-muted small">Total: <?php echo $total_rows; ?> records</span>
                <select class="form-select form-select-sm d-inline-block w-auto ms-2" onchange="window.location.href='audit.php?limit='+this.value">
                    <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25 per page</option>
                    <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50 per page</option>
                    <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100 per page</option>
                </select>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>IP Address</th>
                        <th>Date & Time</th>
                        <th class="text-center">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = $offset + 1;
                    while ($row = mysqli_fetch_assoc($result)): 
                        $badge_class = 'badge-default';
                        $action_lower = strtolower($row['action']);
                        if (strpos($action_lower, 'add') !== false || strpos($action_lower, 'create') !== false) {
                            $badge_class = 'badge-create';
                        } elseif (strpos($action_lower, 'edit') !== false || strpos($action_lower, 'update') !== false) {
                            $badge_class = 'badge-update';
                        } elseif (strpos($action_lower, 'delete') !== false) {
                            $badge_class = 'badge-delete';
                        } elseif (strpos($action_lower, 'login') !== false) {
                            $badge_class = 'badge-login';
                        } elseif (strpos($action_lower, 'lock') !== false || strpos($action_lower, 'unlock') !== false) {
                            $badge_class = 'badge-lock';
                        }
                    ?>
                        <tr>
                            <td class="text-muted small"><?php echo $counter++; ?></td>
                            <td>
                                <div class="fw-600"><?php echo htmlspecialchars($row['user_fullname']); ?></div>
                                <small class="text-muted">ID: <?php echo $row['user_id']; ?></small>
                            </td>
                            <td>
                                <span class="badge-action <?php echo $badge_class; ?>">
                                    <?php echo ucfirst(htmlspecialchars($row['action'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if($row['entity_type']): ?>
                                    <strong><?php echo ucfirst(htmlspecialchars($row['entity_type'])); ?></strong>
                                    <?php if($row['entity_id']): ?>
                                        <br><small class="text-muted">ID: <?php echo $row['entity_id']; ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><code class="small"><?php echo htmlspecialchars($row['ip_address']); ?></code></td>
                            <td>
                                <div><?php echo date('M d, Y', strtotime($row['created_at'])); ?></div>
                                <small class="text-muted"><?php echo date('h:i:s A', strtotime($row['created_at'])); ?></small>
                            </td>
                            <td class="text-center">
                                <button class="view-details-btn" onclick="viewDetails(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    
                    <?php if(mysqli_num_rows($result) == 0): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fas fa-history fa-3x text-muted mb-3 d-block"></i>
                                <h6 class="text-muted">No audit logs found</h6>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="text-muted small">Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_rows); ?> of <?php echo $total_rows; ?> entries</div>
            <nav>
                <ul class="pagination mb-0">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page-1; ?>&limit=<?php echo $limit; ?><?php echo isset($_GET['search']) ? '&search='.$_GET['search'] : ''; ?><?php echo isset($_GET['action']) ? '&action='.$_GET['action'] : ''; ?><?php echo isset($_GET['user_id']) ? '&user_id='.$_GET['user_id'] : ''; ?>">Previous</a>
                    </li>
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?><?php echo isset($_GET['search']) ? '&search='.$_GET['search'] : ''; ?><?php echo isset($_GET['action']) ? '&action='.$_GET['action'] : ''; ?><?php echo isset($_GET['user_id']) ? '&user_id='.$_GET['user_id'] : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page+1; ?>&limit=<?php echo $limit; ?><?php echo isset($_GET['search']) ? '&search='.$_GET['search'] : ''; ?><?php echo isset($_GET['action']) ? '&action='.$_GET['action'] : ''; ?><?php echo isset($_GET['user_id']) ? '&user_id='.$_GET['user_id'] : ''; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-700">Audit Log Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modalContent">
                <!-- Content will be inserted here -->
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-3 fw-700" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function viewDetails(data) {
    const modalBody = document.getElementById('modalContent');
    
    let oldDataHtml = '<span class="text-muted">No data</span>';
    let newDataHtml = '<span class="text-muted">No data</span>';
    
    if (data.old_data && data.old_data !== 'null') {
        try {
            let parsed = JSON.parse(data.old_data);
            oldDataHtml = '<pre class="bg-light p-3 rounded" style="font-size: 12px;">' + JSON.stringify(parsed, null, 2) + '</pre>';
        } catch(e) {
            oldDataHtml = '<div class="bg-light p-3 rounded">' + escapeHtml(data.old_data) + '</div>';
        }
    }
    
    if (data.new_data && data.new_data !== 'null') {
        try {
            let parsed = JSON.parse(data.new_data);
            newDataHtml = '<pre class="bg-light p-3 rounded" style="font-size: 12px;">' + JSON.stringify(parsed, null, 2) + '</pre>';
        } catch(e) {
            newDataHtml = '<div class="bg-light p-3 rounded">' + escapeHtml(data.new_data) + '</div>';
        }
    }
    
    modalBody.innerHTML = `
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="text-muted small text-uppercase mb-1">User</label>
                <div class="fw-600">${escapeHtml(data.user_fullname)}</div>
                <small class="text-muted">User ID: ${data.user_id}</small>
            </div>
            <div class="col-md-6">
                <label class="text-muted small text-uppercase mb-1">Action</label>
                <div><strong>${escapeHtml(data.action)}</strong></div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="text-muted small text-uppercase mb-1">Entity</label>
                <div>${data.entity_type ? ucfirst(escapeHtml(data.entity_type)) : '—'}</div>
                ${data.entity_id ? '<small class="text-muted">ID: ' + data.entity_id + '</small>' : ''}
            </div>
            <div class="col-md-6">
                <label class="text-muted small text-uppercase mb-1">Date & Time</label>
                <div>${new Date(data.created_at).toLocaleString()}</div>
                <small class="text-muted">IP: ${escapeHtml(data.ip_address)}</small>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-12">
                <label class="text-muted small text-uppercase mb-1">Old Data</label>
                ${oldDataHtml}
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <label class="text-muted small text-uppercase mb-1">New Data</label>
                ${newDataHtml}
            </div>
        </div>
    `;
    
    new bootstrap.Modal(document.getElementById('detailsModal')).show();
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}
</script>

<style>
.btn-add {
    background: var(--main-gradient);
    color: white;
    border: none;
    padding: 12px 28px;
    border-radius: 18px;
    font-weight: 800;
    transition: 0.3s;
}
.btn-add:hover {
    transform: translateY(-2px);
    color: white;
}
.form-control, .form-select {
    border-radius: 15px;
    border: 2px solid #f1f0f7;
    padding: 10px 15px;
    font-weight: 600;
}
.page-item.active .page-link {
    background: #7A1CAC;
    border-color: #7A1CAC;
}
.page-link {
    color: #7A1CAC;
    border-radius: 10px;
    margin: 0 3px;
}
</style>

</body>
</html>