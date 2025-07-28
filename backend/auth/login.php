<?php
session_start();

// ⏰ Auto logout after 20 minutes (1200 seconds) of inactivity
$timeout_duration = 1200;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: login?timeout=true");
    exit();
}
$_SESSION['last_activity'] = time(); // Update last activity time

// Database connection
$conn = new mysqli("localhost", "root", "root", "icspl");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";

// Show session timeout message if redirected
if (isset($_GET["timeout"]) && $_GET["timeout"] === "true") {
    $error = "⏰ Session expired due to inactivity. Please login again.";
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    // Check if email exists in admin_users table
    $stmt = $conn->prepare("SELECT id, password FROM admin_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($admin_id, $hashedPassword);
        $stmt->fetch();

        if (password_verify($password, $hashedPassword)) {
            // Successful login
            $_SESSION["admin"] = $email;
            $_SESSION["last_activity"] = time(); // Set last activity timestamp

            // Log login attempt
            $log_stmt = $conn->prepare("INSERT INTO admin_login_logs (email) VALUES (?)");
            $log_stmt->bind_param("s", $email);
            $log_stmt->execute();
            $log_stmt->close();

            // Redirect to admin page
            header("Location: /admin");
            exit();
        } else {
            $error = "❌ Invalid password.";
        }
    } else {
        $error = "⚠️ Email not found.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            height: 100vh;
            align-items: center;
            justify-content: center;
            animation: fadeIn 1s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        .login-box h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            font-size: 15px;
            transition: 0.3s;
        }
        .form-group input:focus {
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.4);
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s ease-in-out;
        }
        button:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .error {
            margin-top: 15px;
            text-align: center;
            color: red;
            font-size: 14px;
        }
        .login-icon {
            text-align: center;
            font-size: 40px;
            color: #667eea;
            margin-bottom: 15px;
        }
        .forgot {
            text-align: center;
            margin-top: 15px;
        }
        .forgot a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        .forgot a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-icon">🔐</div>
        <h2>Admin Panel Login</h2>
        <form method="post" autocomplete="off">
            <div class="form-group">
                <input type="email" name="email" placeholder="📧 Email" required autocomplete="off">
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="🔒 Password" required autocomplete="new-password">
            </div>
            <button type="submit">Login</button>
        </form>
        <div class="forgot">
            <a href="/forgot-password">Forgot Password?</a>
        </div>
        <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>
    </div>
</body>
</html>
