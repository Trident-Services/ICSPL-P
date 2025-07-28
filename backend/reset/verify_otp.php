<?php
session_start();
$conn = new mysqli("localhost", "root", "root", "icspl");

$message = "";

if (!isset($_SESSION['reset_email'])) {
    header("Location: /forgot-password");
    exit();
}

$email = $_SESSION['reset_email'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = $_POST["otp"];

    // Check OTP and expiry
    $stmt = $conn->prepare("SELECT otp, otp_expiry FROM admin_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($stored_otp, $otp_expiry);
    $stmt->fetch();
    $stmt->close();

    $current_time = date("Y-m-d H:i:s");

    if ($entered_otp == $stored_otp && $current_time <= $otp_expiry) {
        $_SESSION['otp_verified'] = true;
        header("Location: /reset-password");
        exit();
    } else {
        $message = "❌ Invalid or expired OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            height: 100vh;
            align-items: center;
            justify-content: center;
        }
        .otp-box {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        .otp-box h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 16px;
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
        .error {
            color: red;
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="otp-box">
        <h2>Enter OTP</h2>
        <form method="post">
            <input type="text" name="otp" placeholder="🔢 Enter the OTP" required>
            <button type="submit">Verify OTP</button>
        </form>
        <?php if ($message) echo "<div class='error'>$message</div>"; ?>
    </div>
</body>
</html>
