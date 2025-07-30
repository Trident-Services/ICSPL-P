<?php
session_start();

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$message = "";

if (isset($_POST['upload']) && isset($_FILES['file'])) {
    $file = $_FILES['file']['tmp_name'];
    $filename = $_FILES['file']['name'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);

    if ($ext === 'xlsx') {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $blogs = [];
        foreach (array_slice($rows, 1) as $row) {
            $blogs[] = [
                "title" => $row[0] ?? "Untitled",
                "content" => $row[1] ?? "",
                "image" => $row[2] ?? "",
                "timestamp" => date('Y-m-d H:i:s')
            ];
        }

        $jsonFile = 'blog_data.json';
        $existing = [];

        if (file_exists($jsonFile)) {
            $existing = json_decode(file_get_contents($jsonFile), true);
        }

        $allBlogs = array_merge($blogs, $existing);
        file_put_contents($jsonFile, json_encode($allBlogs, JSON_PRETTY_PRINT));

        $message = "✅ Excel file uploaded and blogs updated successfully!";
    } else {
        $message = "❌ Only .xlsx files are supported.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Blogs (.xlsx)</title>
    <style>
        body { background: #0f172a; color: #f8fafc; font-family: Arial; text-align: center; padding-top: 50px; }
        form { background: #1e293b; padding: 20px; border-radius: 10px; display: inline-block; }
        input[type=file], input[type=submit] { margin: 10px 0; padding: 10px; border-radius: 6px; border: none; }
        input[type=submit] { background-color: #3b82f6; color: white; cursor: pointer; }
        .message { margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>📄 Upload Excel File for Blog</h1>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="file" required accept=".xlsx"><br>
        <input type="submit" name="upload" value="Upload">
    </form>
    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>
</body>
</html>
