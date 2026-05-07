<?php
// audit_functions.php
function logAudit($conn, $action, $entity_type = null, $entity_id = null, $old_data = null, $new_data = null) {
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $user_fullname = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Guest';
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    
    if (is_array($old_data)) $old_data = json_encode($old_data);
    if (is_array($new_data)) $new_data = json_encode($new_data);
    
    $stmt = mysqli_prepare($conn, "INSERT INTO audit_log (user_id, user_fullname, action, entity_type, entity_id, old_data, new_data, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isssissss", $user_id, $user_fullname, $action, $entity_type, $entity_id, $old_data, $new_data, $ip_address, $user_agent);
    
    return mysqli_stmt_execute($stmt);
}
?>