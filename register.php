<?php

include 'config.php';

if (isset($_POST['register'])) {
    
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password']; 

    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);


    $sql = "INSERT INTO users (fullname, username, password) VALUES ('$fullname', '$username', '$hashed_password')";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Registered Successfully!'); window.location='login.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --brand-purple: #6f42c1; /* Classic Purple */
            --dark-purple: #59359a;  /* Darker Purple for hover */
        }
        body {
            /* Pure White Background */
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
        }
        .register-card {
            border: 1px solid #dee2e6;
            border-radius: 15px;
            overflow: hidden;
            background: #ffffff;
        }
        .card-header {
            /* Purple Header */
            background-color: var(--brand-purple) !important;
            border-radius: 15px 15px 0 0 !important;
            color: white !important;
            border: none;
        }
        .btn-purple {
            /* Purple Button */
            background-color: var(--brand-purple);
            color: white;
            border: none;
            transition: 0.3s;
        }
        .btn-purple:hover {
            background-color: var(--dark-purple);
            color: white;
        }
        .text-purple {
            color: var(--brand-purple) !important;
        }
        /* Custom Focus Ring for Inputs */
        .form-control:focus {
            border-color: var(--brand-purple);
            box-shadow: 0 0 0 0.25rem rgba(111, 66, 193, 0.25);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card register-card shadow-lg">
                <div class="card-header text-center py-3">
                    <h4 class="mb-0 fw-bold">Create Account</h4>
                    <small>Inspiro Relia Inc.</small>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action=""> 
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input type="text" name="fullname" class="form-control" placeholder="Juan Dela Cruz" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Username / Email</label>
                            <input type="text" name="username" class="form-control" placeholder="juan123" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="********" required>
                        </div>

                        <div class="d-grid gap-2 col-10 mx-auto">
                            <button name="register" class="btn btn-purple fw-bold" type="submit">Register</button>
                            <button class="btn btn-outline-secondary btn-sm" type="reset">Clear All</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <small>May account na? <a href="login.php" class="text-decoration-none fw-bold text-purple">Mag-login dito</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>