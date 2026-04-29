<?php
ob_start(); // Dagdag ito para sa smooth redirection
session_start();
include 'config.php';

$error_msg = "";

if (isset($_POST['login'])) {
    // Kunin ang data mula sa form
    $username = $_POST['username'];
    $password = $_POST['password'];
    $selected_role = $_POST['role']; // Ang piniling role (Admin o Technical Support)

    // Prepared statement para sa seguridad (i-check ang username at role)
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = ? LIMIT 1");
    $stmt->bind_param("ss", $username, $selected_role);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Ito ang magche-check sa mahabang hash sa database vs sa tinype ni user
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user['fullname'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role']; 

            header("Location: index.php");
            exit(); 
        } else {
            $error_msg = "Maling password! Subukan ulit.";
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
        :root {
            --brand-purple: #2E073F;
            --dark-purple: #2E073F;
            --hover-purple: #59359a;
        }
        body {
            background: var(--dark-purple);
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            border: 1px solid #dee2e6;
            border-radius: 15px;
            background: #ffffff;
        }
        .btn-purple {
            background-color: var(--brand-purple);
            color: white;
            border: none;
            transition: 0.3s;
            font-weight: 600;
        }
        .btn-purple:hover {
            background-color: var(--hover-purple);
            color: white;
        }
        .text-purple {
            color: var(--brand-purple) !important;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--brand-purple);
            box-shadow: 0 0 0 0.25rem rgba(111, 66, 193, 0.25);
        }
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
                    <img src="logooo.png" alt="Inspiro Logo" style="width: 200px; max-width: 100%; height: auto; margin-bottom: 10px;">
                    <h4 class="mb-0 text-purple fw-semibold">INSPIRO RELIA INC.</h4>
                </div>
                <div class="card-body p-4">
                    <form method="POST"> 
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Login As</label>
                            <select name="role" class="form-select" required>
                                <option value="" selected disabled>-- Select Role --</option>
                                <option value="Administrator">Administrator</option>
                                <option value="Technical Support">Technical Support</option>
                            </select>
                        </div>

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
    <div class="text-center mt-4">
        <small class="text-white-50">Inspiro Relia &copy; 2026. All Rights Reserved.</small>
    </div>
</div>
</body>
</html>