<?php
include 'config.php';

// Password validation function
function validatePasswordStrength($password) {
    $errors = array();
    
    // Check minimum length (8 characters)
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    // Check for alphanumeric (letters and numbers)
    if (!preg_match('/[A-Za-z]/', $password)) {
        $errors[] = "Password must contain at least one letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    // Check for special characters
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character (!@#$%^&* etc.)";
    }
    
    return $errors;
}

if (isset($_POST['register'])) {
    
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password']; 
    
    // Validate password strength
    $passwordErrors = validatePasswordStrength($password);
    
    if (!empty($passwordErrors)) {
        // Show validation errors
        $error_message = "Password requirements not met:<br>- " . implode("<br>- ", $passwordErrors);
        echo "<script>alert('$error_message'); window.location='register.php';</script>";
        exit();
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if username already exists
    $check_sql = "SELECT * FROM users WHERE username = '$username'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        echo "<script>alert('Username already exists! Please choose another username.'); window.location='register.php';</script>";
        exit();
    }
    
    $sql = "INSERT INTO users (fullname, username, password) VALUES ('$fullname', '$username', '$hashed_password')";
    
    if (mysqli_query($conn, $sql)) {
        logAudit($conn, 'ADD_USER', 'user', mysqli_insert_id($conn), null, [
            'fullname' => $fullname,
            'username' => $username,
            'role' => $role
        ]);
        echo "<script>alert('Registered Successfully! Please login.'); window.location='login.php';</script>";
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
    <title>Register Page | Inspiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --brand-purple: #6f42c1;
            --dark-purple: #59359a;
        }
        body {
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
            background-color: var(--brand-purple) !important;
            border-radius: 15px 15px 0 0 !important;
            color: white !important;
            border: none;
        }
        .btn-purple {
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
        .form-control:focus {
            border-color: var(--brand-purple);
            box-shadow: 0 0 0 0.25rem rgba(111, 66, 193, 0.25);
        }
        .password-requirements {
            font-size: 12px;
            margin-top: 8px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .password-requirements ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        .password-requirements li {
            margin-bottom: 4px;
            color: #6c757d;
        }
        .password-requirements li.valid {
            color: #198754;
            text-decoration: line-through;
        }
        .password-requirements li.invalid {
            color: #dc3545;
        }
        .requirement-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: #495057;
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
                    <form method="POST" action="" id="registerForm"> 
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input type="text" name="fullname" id="fullname" class="form-control" placeholder="Juan Dela Cruz" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Username / Email</label>
                            <input type="text" name="username" id="username" class="form-control" placeholder="juan123" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Passwordssssssssss</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="********" required>
                            <div class="password-requirements">
                                <div class="requirement-title">Password must contain:</div>
                                <ul>
                                    <li id="charCount">✓ Minimum 8 characters</li>
                                    <li id="letterCheck">✓ At least 1 letter (A-Z, a-z)</li>
                                    <li id="numberCheck">✓ At least 1 number (0-9)</li>
                                    <li id="specialCheck">✓ At least 1 special character (!@#$%^&* etc.)</li>
                                </ul>
                            </div>
                        </div>

                        <div class="d-grid gap-2 col-10 mx-auto">
                            <button name="register" class="btn btn-purple fw-bold" type="submit" id="registerBtn" disabled>Register</button>
                            <button class="btn btn-outline-secondary btn-sm" type="reset" onclick="resetForm()">Clear All</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <small>Already have an account? <a href="login.php" class="text-decoration-none fw-bold text-purple">Login here</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Real-time password validation
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    let isValid = true;
    
    // Check minimum 8 characters
    const charCount = document.getElementById('charCount');
    if (password.length >= 8) {
        charCount.classList.add('valid');
        charCount.classList.remove('invalid');
    } else {
        charCount.classList.remove('valid');
        charCount.classList.add('invalid');
        isValid = false;
    }
    
    // Check for letters
    const letterCheck = document.getElementById('letterCheck');
    if (/[A-Za-z]/.test(password)) {
        letterCheck.classList.add('valid');
        letterCheck.classList.remove('invalid');
    } else {
        letterCheck.classList.remove('valid');
        letterCheck.classList.add('invalid');
        isValid = false;
    }
    
    // Check for numbers
    const numberCheck = document.getElementById('numberCheck');
    if (/[0-9]/.test(password)) {
        numberCheck.classList.add('valid');
        numberCheck.classList.remove('invalid');
    } else {
        numberCheck.classList.remove('valid');
        numberCheck.classList.add('invalid');
        isValid = false;
    }
    
    // Check for special characters
    const specialCheck = document.getElementById('specialCheck');
    if (/[^A-Za-z0-9]/.test(password)) {
        specialCheck.classList.add('valid');
        specialCheck.classList.remove('invalid');
    } else {
        specialCheck.classList.remove('valid');
        specialCheck.classList.add('invalid');
        isValid = false;
    }
    
    // Enable/disable register button
    document.getElementById('registerBtn').disabled = !isValid;
});

// Reset function to clear validation styles
function resetForm() {
    document.getElementById('registerForm').reset();
    document.getElementById('registerBtn').disabled = true;
    
    // Reset validation styles
    const requirements = ['charCount', 'letterCheck', 'numberCheck', 'specialCheck'];
    requirements.forEach(id => {
        const element = document.getElementById(id);
        element.classList.remove('valid', 'invalid');
    });
}

// Form submit validation
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const fullname = document.getElementById('fullname').value.trim();
    const username = document.getElementById('username').value.trim();
    const passwordErrors = [];
    
    // Check if fields are empty
    if (fullname === '') {
        e.preventDefault();
        alert('Please enter your full name');
        return false;
    }
    
    if (username === '') {
        e.preventDefault();
        alert('Please enter your username/email');
        return false;
    }
    
    // Validate password requirements
    if (password.length < 8) {
        passwordErrors.push("Password must be at least 8 characters");
    }
    if (!/[A-Za-z]/.test(password)) {
        passwordErrors.push("Password must contain at least one letter");
    }
    if (!/[0-9]/.test(password)) {
        passwordErrors.push("Password must contain at least one number");
    }
    if (!/[^A-Za-z0-9]/.test(password)) {
        passwordErrors.push("Password must contain at least one special character");
    }
    
    if (passwordErrors.length > 0) {
        e.preventDefault();
        alert("Please meet password requirements:\n- " + passwordErrors.join("\n- "));
        return false;
    }
});

// Optional: Add real-time validation for username availability (AJAX)
document.getElementById('username').addEventListener('blur', function() {
    const username = this.value.trim();
    if (username.length > 0) {
        // You can add AJAX call here to check if username exists
        // This is optional and requires additional backend endpoint
    }
});
</script>

<style>
/* Additional styles for validation */
.valid {
    color: #198754 !important;
    text-decoration: line-through;
}

.invalid {
    color: #dc3545 !important;
    text-decoration: none !important;
}

#registerBtn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

</body>
</html>