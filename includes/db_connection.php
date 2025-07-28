<?php
// includes/db_connection.php

// Load Composer's autoloader (if Dotenv is installed)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';

    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->safeLoad(); // Does not throw error if .env is missing
    }
}

// Fetch DB credentials from environment
$DB_HOST = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
$DB_USER = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root');
$DB_PASS = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');
$DB_NAME = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'icspl');

// Create secure MySQLi connection
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Handle connection error
if ($conn->connect_error) {
    error_log("[DB ERROR] Connection failed: " . $conn->connect_error);
    http_response_code(500);
    exit("Service is temporarily unavailable.");
}

// Set charset (important for UTF-8 support)
$conn->set_charset("utf8mb4");

// Optional: set strict SQL mode for security
$conn->query("SET SESSION sql_mode='STRICT_ALL_TABLES'");
