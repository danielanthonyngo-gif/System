<?php
ob_start();
session_start();
include 'config.php';

$error_msg = "";

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $selected_role = $_POST['role'];

    // Prepared statement para i-check ang user
    // $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = ? LIMIT 1");
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?  LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // STEP 1: Check kung locked na ang status
        if ($user['status'] === 'locked') {
            $error_msg = "<strong>ACCOUNT LOCKED!</strong><br>Masyado nang maraming maling subok. Kontakin ang Administrator.";
        } else {
            // STEP 2: I-verify ang password
            if (password_verify($password, $user['password'])) {
                // SUCCESS: Reset attempts sa 0
                $reset = $conn->prepare("UPDATE users SET login_attempts = 0 WHERE id = ?");
                $reset->bind_param("i", $user['id']);
                $reset->execute();

                $_SESSION['user'] = $user['fullname'];
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role']; 

                header("Location: index.php");
                exit(); 
            } else {
                // WRONG PASSWORD: Dagdagan ang count
                $new_attempts = $user['login_attempts'] + 1;
                
                if ($new_attempts >= 3) {
                    // Lock account na pag naka-3 times na
                    $lock = $conn->prepare("UPDATE users SET login_attempts = ?, status = 'locked' WHERE id = ?");
                    $lock->bind_param("ii", $new_attempts, $user['id']);
                    $lock->execute();
                    $error_msg = "Account Locked! You have reached 3 failed attempts.";
                } else {
                    // Update counter lang
                    $update = $conn->prepare("UPDATE users SET login_attempts = ? WHERE id = ?");
                    $update->bind_param("ii", $new_attempts, $user['id']);
                    $update->execute();
                    
                    $remaining = 3 - $new_attempts;
                    $error_msg = "Maling password! Meron ka na lang <strong>$remaining</strong> subok.";
                }
            }
        }
    } else {
        $error_msg = "Maling Username o Role! Siguraduhing tama ang iyong pinili.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page | Inspiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --brand-purple: #2E073F; --dark-purple: #2E073F; --hover-purple: #59359a; }
        body { background: var(--dark-purple); font-family: 'Segoe UI', sans-serif; min-height: 100vh; display: flex; align-items: center; }
        .login-card { border-radius: 15px; background: #ffffff; }
        .btn-purple { background-color: var(--brand-purple); color: white; border: none; font-weight: 600; }
        .btn-purple:hover { background-color: var(--hover-purple); color: white; }
        .text-purple { color: var(--brand-purple) !important; }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            
            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-danger mt-3" role="alert">
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <div class="card login-card shadow-lg">
                <div class="text-white text-center py-3">
                    <img src="logooo.png" alt="Inspiro Logo" style="width: 200px; max-width: 100%; margin-bottom: 10px;">
                    <h4 class="mb-0 text-purple fw-semibold">INSPIRO RELIA INC.</h4>
                </div>
                <div class="card-body p-4">
                    <form method="POST"> 
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" name="username" class="form-control" placeholder="name@example.com" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="********" required>
                        </div>
                        <div class="d-grid">
                            <button name="login" class="btn btn-purple btn-lg" type="submit">Log in</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>