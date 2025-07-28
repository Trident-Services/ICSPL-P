<?php
session_start();

// Auto logout logic
$timeout_duration = 1200; // 20 minutes
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: backend/login.php?timeout=1");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Check login
if (!isset($_SESSION["admin"])) {
    header("Location: login");
    exit();
}
if (isset($_GET["logout"])) {
    session_destroy();
    header("Location: /logout");
    exit();
}

$message = "";

// Handle file upload
if (isset($_POST['upload']) && isset($_FILES['file'])) {
    $file = $_FILES['file']['tmp_name'];
    $filename = $_FILES['file']['name'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);

    if ($ext === 'txt' || $ext === 'md') {
        $content = file_get_contents($file);
    } elseif ($ext === 'json') {
        $content = file_get_contents($file);
    } elseif ($ext === 'docx') {
        require_once 'vendor/autoload.php'; // make sure PHPWord is installed via Composer
        $reader = \PhpOffice\PhpWord\IOFactory::createReader('Word2007');
        $phpWord = $reader->load($file);
        $content = '';
        foreach ($phpWord->getSections() as $section) {
            $elements = $section->getElements();
            foreach ($elements as $element) {
                if (method_exists($element, 'getText')) {
                    $content .= $element->getText() . "\n";
                }
            }
        }
    } else {
        $message = "❌ Unsupported file type.";
    }

    if (!empty($content)) {
        $blog = [
            "title" => pathinfo($filename, PATHINFO_FILENAME),
            "content" => $content,
            "timestamp" => date('Y-m-d H:i:s')
        ];

        $jsonFile = 'blog_data.json';
        $allBlogs = [];

        if (file_exists($jsonFile)) {
            $allBlogs = json_decode(file_get_contents($jsonFile), true);
        }

        array_unshift($allBlogs, $blog); // Add newest first
        file_put_contents($jsonFile, json_encode($allBlogs, JSON_PRETTY_PRINT));
        $message = "✅ File uploaded and converted to JSON successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Files - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #0f172a;
            color: #f8fafc;
            font-family: 'Inter', sans-serif;
            padding-bottom: 50px;
        }
        .header {
            background-color: #1e293b;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #334155;
            flex-wrap: wrap;
        }
        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .admin-info img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #94a3b8;
        }
        .nav-links {
            display: flex;
            gap: 25px;
            align-items: center;
            flex-wrap: wrap;
        }
        .nav-links a {
            color: #f8fafc;
            text-decoration: none;
            font-weight: 600;
            position: relative;
            padding: 10px 6px;
            transition: color 0.3s ease;
        }
        .nav-links a::after {
            content: "";
            position: absolute;
            width: 0%;
            height: 3px;
            bottom: 0;
            left: 0;
            background-color: #3b82f6;
            transition: width 0.3s ease;
            border-radius: 2px;
        }
        .nav-links a:hover {
            color: #38bdf8;
        }
        .nav-links a:hover::after {
            width: 100%;
        }
        .nav-links .logout-btn {
            background-color: #ef4444;
            color: white !important;
            padding: 10px 16px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }
        .nav-links .logout-btn:hover {
            background-color: #dc2626;
        }
        .upload-container {
            max-width: 600px;
            background-color: #1e293b;
            margin: 60px auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.5);
        }
        h2 {
            text-align: center;
            font-size: 28px;
            margin-bottom: 25px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        input[type="file"] {
            background-color: #0f172a;
            color: #f8fafc;
            padding: 12px;
            border-radius: 10px;
            border: none;
        }
        input[type="submit"] {
            background-color: #3b82f6;
            color: white;
            font-weight: 600;
            padding: 14px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #2563eb;
        }
        .message {
            text-align: center;
            margin-top: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="admin-info">
        <img src="https://img.freepik.com/premium-photo/3d-sales-manager-character-leading-with-animated-ambition_893571-11254.jpg" alt="Admin">
        <strong>Welcome, <?php echo htmlspecialchars($_SESSION["admin"] ?? 'Admin'); ?></strong>
    </div>
    <nav class="nav-links">
        <a href="/admin">Dashboard</a>
        <a href="/upload">Uploaded Files</a>
        <a href="/users">User List</a>
        <a href="?/logout=true" class="logout-btn">Logout</a>
    </nav>
</div>

<div class="upload-container">
    <h2>📁 Upload a Blog File</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="file" required accept=".txt,.md,.json,.docx" />
        <input type="submit" name="upload" value="Upload">
    </form>
    <?php if ($message): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
</div>

</body>
</html>
