<?php
session_start();
$conn = new mysqli("localhost", "root", "root", "icspl");

// Session timeout
$timeout_duration = 1200;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: /login?timeout=1");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

if (!isset($_SESSION["admin"])) {
    header("Location: /login");
    exit();
}

// Add admin user
$add_success = $add_error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_admin'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $add_error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $add_error = "Password must be at least 6 characters.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admin_users (email, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $hashedPassword);
        if ($stmt->execute()) {
            $add_success = "Admin user added successfully.";
        } else {
            $add_error = "Email already exists or failed to add.";
        }
        $stmt->close();
    }
}

// Delete admin user
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);

    if (isset($_SESSION["admin_id"]) && $_SESSION["admin_id"] == $delete_id) {
        $add_error = "You cannot delete your own account.";
    } else {
        $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();
        header("Location: /admin-users");
        exit();
    }
}

// Fetch admin users
$result = $conn->query("SELECT id, email FROM admin_users");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Users</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="autocomplete" content="off">
    <style>
        body { background-color: #0f172a; color: white; font-family: 'Inter', sans-serif; margin: 0; padding: 20px; }
        h1 { text-align: center; margin-bottom: 20px; }
        .container { max-width: 800px; margin: auto; background: #1e293b; padding: 20px; border-radius: 12px; box-shadow: 0 0 12px rgba(0,0,0,0.5); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #334155; text-align: left; }
        th { background-color: #0f172a; color: #38bdf8; }
        tr:hover td { background-color: #334155; }
        form { display: flex; flex-direction: column; gap: 12px; margin-top: 20px; }
        input[type="email"], input[type="password"] {
            padding: 10px;
            border-radius: 6px;
            border: none;
            font-size: 16px;
            background-color: #334155;
            color: white;
        }
        input[type="submit"] {
            background-color: #3b82f6;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        input[type="submit"]:hover {
            background-color: #2563eb;
        }
        .error { color: #f87171; margin-top: 10px; }
        .success { color: #4ade80; margin-top: 10px; }
        .delete-btn {
            background-color: #ef4444;
            color: white;
            padding: 6px 10px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }
        .delete-btn:hover {
            background-color: #dc2626;
        }
        .back-link {
            display: block;
            margin-top: 20px;
            color: #60a5fa;
            text-decoration: none;
            text-align: center;
        }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h1>👥 Admin User Management</h1>

    <?php if ($add_error): ?>
        <div class="error"><?= htmlspecialchars($add_error) ?></div>
    <?php elseif ($add_success): ?>
        <div class="success"><?= htmlspecialchars($add_success) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <input type="email" name="email" placeholder="Admin Email" required autocomplete="off">
        <input type="password" name="password" placeholder="Password (min 6 characters)" required autocomplete="new-password">
        <input type="submit" name="add_admin" value="➕ Add Admin User">
    </form>

    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Email</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td>
                        <form method="get" autocomplete="off" onsubmit="return confirm('Are you sure to delete this admin?')">
                            <input type="hidden" name="delete" value="<?= $row['id'] ?>">
                            <button type="submit" class="delete-btn">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="3">No admin users found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <a href="/admin" class="back-link">← Back to Dashboard</a>
</div>
</body>
</html>
