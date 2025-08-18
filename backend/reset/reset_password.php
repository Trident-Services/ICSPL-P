<?php
session_start();
// ✅ Use DB connection from includes/db_connection.php
$conn = require __DIR__ . "/../../includes/db_connection.php";

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['otp_verified'])) {
    header("Location: /forgot-password");
    exit();
}

$email = $_SESSION['reset_email'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if ($new_password !== $confirm_password) {
        $message = "❌ Passwords do not match.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE admin_users SET password = ?, otp = NULL, otp_expiry = NULL WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        $stmt->execute();
        $stmt->close();

        // Clear session
        unset($_SESSION['reset_email']);
        unset($_SESSION['otp_verified']);

        $message = "✅ Password reset successful! You can now <a href='login.php'>log in</a>.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            height: 100vh;
            align-items: center;
            justify-content: center;
        }
        .reset-box {
            background: white;
            padding: 60px;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
        }
        .reset-box h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        .input-wrapper {
            position: relative;
            margin-bottom: 20px;
        }
        input[type="password"], input[type="text"] {
            width: 100%;
            padding: 10px 10px 10px 1px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 14px;
            color: #667eea;
            user-select: none;
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        .message {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }
        .message a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="reset-box">
        <h2>Reset Your Password</h2>
        <form method="post" autocomplete="off">
            <div class="input-wrapper">
                <input type="password" name="password" id="password" placeholder="🔒 New Password" required autocomplete="new-password">
                <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
            </div>
            <div class="input-wrapper">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="🔒 Confirm Password" required autocomplete="new-password">
                <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
            </div>
            <button type="submit">Update Password</button>
        </form>
        <?php if ($message) echo "<div class='message'>$message</div>"; ?>
    </div>

    <script>
        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            if (input.type === "password") {
                input.type = "text";
            } else {
                input.type = "password";
            }
        }
    </script>
</body>
</html>
