<?php
require_once __DIR__ . '/../includes/db_connection.php'; // adjust path if needed




// Create database if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS `$DB_NAME`");

// Select the database
$conn->select_db($DB_NAME);

// Create users table
$createUsers = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(15),
    gmail VARCHAR(100) UNIQUE,
    services TEXT,
    sectors TEXT,
    additional_message TEXT
) ENGINE=InnoDB;";

// Create admin_users table
$createAdminUsers = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    otp VARCHAR(10),
    otp_expiry DATETIME,
    last_login DATETIME
) ENGINE=InnoDB;";

// Create admin_login_logs table
$createAdminLogs = "CREATE TABLE IF NOT EXISTS admin_login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    login_time DATETIME NOT NULL,
    admin_id INT,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB;";

// Run queries
if ($conn->query($createUsers) === TRUE) {
    echo "✅ users table ready.\n";
} else {
    echo "❌ Error creating users: " . $conn->error . "\n";
}

if ($conn->query($createAdminUsers) === TRUE) {
    echo "✅ admin_users table ready.\n";
} else {
    echo "❌ Error creating admin_users: " . $conn->error . "\n";
}

if ($conn->query($createAdminLogs) === TRUE) {
    echo "✅ admin_login_logs table ready.\n";
} else {
    echo "❌ Error creating admin_login_logs: " . $conn->error . "\n";
}

// Show all tables
$result = $conn->query("SHOW TABLES");
if ($result) {
    echo "\n📋 Tables in database `$DB_NAME`:\n";
    while ($row = $result->fetch_array()) {
        echo "- " . $row[0] . "\n";
    }
}

$conn->close();
?>
