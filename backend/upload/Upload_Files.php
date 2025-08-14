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

// Improved Excel content extraction function
function extractExcelContent($filePath) {
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();
    $data = [];
    
    // Get highest row and column
    $highestRow = $worksheet->getHighestRow();
    $highestColumn = $worksheet->getHighestColumn();
    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
    
    // Process each row (skip header row)
    for ($row = 2; $row <= $highestRow; $row++) {
        $rowData = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $cell = $worksheet->getCellByColumnAndRow($col, $row);
            $rowData[] = $cell->getFormattedValue();
        }
        
        // Only process rows with data in title column (A)
        if (!empty($rowData[0])) {
            $entry = [
                'title' => $rowData[0] ?? '', // Column A
                'description' => $rowData[1] ?? '', // Column B
                'timestamp' => $rowData[2] ?? date('Y-m-d H:i:s'),
                'file_type' => $rowData[3] ?? 'xlsx',
                'original_filename' => $rowData[4] ?? '',
                'file_path' => $rowData[5] ?? '',
                'file_size' => $rowData[6] ?? 0,
                'image_url' => $rowData[8] ?? '', // Column I
                'thumbnail' => $rowData[9] ?? '' // Column J
            ];
            
            // Download and save image if URL exists
            if (!empty($entry['image_url'])) {
                $imageUrl = $entry['image_url'];
                $imageExt = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                $imagePath = 'uploads/images/' . uniqid() . '.' . ($imageExt ?: 'jpg');
                
                if (!file_exists('uploads/images')) {
                    mkdir('uploads/images', 0755, true);
                }
                
                // Download and save the image
                $imageContent = @file_get_contents($imageUrl);
                if ($imageContent !== false) {
                    file_put_contents($imagePath, $imageContent);
                    $entry['image_path'] = $imagePath;
                    
                    // Create thumbnail
                    $thumbnailPath = 'uploads/thumbs/' . basename($imagePath);
                    if (!file_exists('uploads/thumbs')) {
                        mkdir('uploads/thumbs', 0755, true);
                    }
                    createThumbnail($imagePath, $thumbnailPath, 300, 200);
                    $entry['thumbnail_path'] = $thumbnailPath;
                }
            }
            
            $data[] = $entry;
        }
    }
    
    return $data;
}

// Helper function to extract text from different file types
function extractFileContent($filePath, $fileType) {
    $content = '';
    $imagePath = '';
    $thumbnailPath = '';
    $excelData = [];
    
    switch($fileType) {
        case 'txt':
        case 'md':
            $content = file_get_contents($filePath);
            break;
            
        case 'docx':
            if (class_exists('PhpOffice\PhpWord\IOFactory')) {
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
                $content = '';
                foreach($phpWord->getSections() as $section) {
                    foreach($section->getElements() as $element) {
                        if (method_exists($element, 'getElements')) {
                            foreach($element->getElements() as $child) {
                                if (method_exists($child, 'getText')) {
                                    $content .= $child->getText() . ' ';
                                }
                            }
                        } elseif (method_exists($element, 'getText')) {
                            $content .= $element->getText() . ' ';
                        }
                    }
                }
            }
            break;
            
        case 'pdf':
            if (class_exists('Smalot\PdfParser\Parser')) {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                $content = $pdf->getText();
            }
            break;
            
        case 'xlsx':
        case 'xls':
            if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                $excelData = extractExcelContent($filePath);
                if (!empty($excelData[0])) {
                    $firstRow = $excelData[0];
                    $content = $firstRow['description'] ?? '';
                    $imagePath = $firstRow['image_path'] ?? '';
                    $thumbnailPath = $firstRow['thumbnail_path'] ?? '';
                }
            }
            break;
    }
    
    return [
        'content' => trim(substr($content, 0, 2000)),
        'image_path' => $imagePath,
        'thumbnail_path' => $thumbnailPath,
        'excel_data' => $excelData
    ];
}

// Handle file deletion
if (isset($_POST['delete_file'])) {
    $index = $_POST['file_index'];
    $jsonFile = 'blog_data.json';
    
    if (file_exists($jsonFile)) {
        $allBlogs = json_decode(file_get_contents($jsonFile), true) ?: [];
        
        if (isset($allBlogs[$index])) {
            // Delete associated files
            $file = $allBlogs[$index];
            if (!empty($file['image'])) @unlink($file['image']);
            if (!empty($file['file_path'])) @unlink($file['file_path']);
            if (!empty($file['thumbnail'])) @unlink($file['thumbnail']);
            if (!empty($file['excel_data'])) {
                foreach ($file['excel_data'] as $excelRow) {
                    if (!empty($excelRow['image_path'])) @unlink($excelRow['image_path']);
                    if (!empty($excelRow['thumbnail_path'])) @unlink($excelRow['thumbnail_path']);
                }
            }
            
            array_splice($allBlogs, $index, 1);
            file_put_contents($jsonFile, json_encode($allBlogs, JSON_PRETTY_PRINT));
            $message = "✅ File deleted successfully!";
        }
    }
}

// Handle file upload
if (isset($_POST['upload']) && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $fileSize = $file['size'];
    $description = $_POST['description'] ?? '';
    
    // Create uploads directory if it doesn't exist
    if (!file_exists('uploads')) {
        mkdir('uploads', 0755, true);
    }
    
    // Supported file types
    $supportedTypes = ['txt', 'md', 'json', 'docx', 'pdf', 'jpg', 'jpeg', 'png', 'gif', 'xlsx', 'xls'];
    
    if (!in_array($ext, $supportedTypes)) {
        $message = "❌ Unsupported file type.";
    } else {
        $title = pathinfo($filename, PATHINFO_FILENAME);
        $uniqueId = uniqid();
        $filePath = 'uploads/' . $uniqueId . '.' . $ext;
        $content = '';
        $imagePath = '';
        $thumbnailPath = '';
        $excelData = [];
        
        // Move uploaded file
        move_uploaded_file($file['tmp_name'], $filePath);
        
        // Handle image uploads
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $imagePath = $filePath;
            // Create thumbnail for images
            $thumbnailPath = 'uploads/thumbs/' . $uniqueId . '_thumb.' . $ext;
            if (!file_exists('uploads/thumbs')) {
                mkdir('uploads/thumbs', 0755, true);
            }
            createThumbnail($filePath, $thumbnailPath, 300, 200);
        } else {
            // Extract content from non-image files
            $extracted = extractFileContent($filePath, $ext);
            $content = $extracted['content'];
            $imagePath = $extracted['image_path'];
            $thumbnailPath = $extracted['thumbnail_path'];
            $excelData = $extracted['excel_data'];
            
            // For Excel files, use first row data if description is empty
            if (($ext === 'xlsx' || $ext === 'xls') && empty($description) && !empty($excelData[0]['description'])) {
                $description = $excelData[0]['description'];
            }
        }
        
        $blog = [
            "title" => $title,
            "description" => !empty($description) ? $description : $content,
            "timestamp" => date('Y-m-d H:i:s'),
            "file_type" => $ext,
            "original_filename" => $filename,
            "file_path" => $filePath,
            "file_size" => $fileSize,
            "image" => $imagePath,
            "thumbnail" => $thumbnailPath,
            "excel_data" => $excelData
        ];
        
        $jsonFile = 'blog_data.json';
        $allBlogs = [];
        
        if (file_exists($jsonFile)) {
            $allBlogs = json_decode(file_get_contents($jsonFile), true);
            if (!is_array($allBlogs)) $allBlogs = [];
        }
        
        array_unshift($allBlogs, $blog);
        file_put_contents($jsonFile, json_encode($allBlogs, JSON_PRETTY_PRINT));
        $message = "✅ File uploaded successfully!";
        
        // Clear the message after 3 seconds
        echo '<script>
            setTimeout(function() {
                var msg = document.querySelector(".message");
                if (msg) msg.style.display = "none";
            }, 3000);
        </script>';
    }
}

// Helper function to create thumbnails
function createThumbnail($src, $dest, $targetWidth, $targetHeight) {
    $type = exif_imagetype($src);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($src);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($src);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($src);
            break;
        default:
            return false;
    }
    
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Calculate aspect ratio
    $srcRatio = $width / $height;
    $destRatio = $targetWidth / $targetHeight;
    
    if ($destRatio > $srcRatio) {
        $newHeight = $targetHeight;
        $newWidth = $targetHeight * $srcRatio;
    } else {
        $newWidth = $targetWidth;
        $newHeight = $targetWidth / $srcRatio;
    }
    
    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG/GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }
    
    imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumb, $dest, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($thumb, $dest, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($thumb, $dest);
            break;
    }
    
    imagedestroy($image);
    imagedestroy($thumb);
    
    return true;
}

// Read existing files for display
$allBlogs = [];
if (file_exists('blog_data.json')) {
    $allBlogs = json_decode(file_get_contents('blog_data.json'), true) ?: [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>File Management - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
        }
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
        .main-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .upload-container {
            background-color: #1e293b;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.5);
            margin-bottom: 40px;
        }
        h1, h2 {
            color: #e2e8f0;
            margin-bottom: 25px;
        }
        h1 {
            font-size: 32px;
            text-align: center;
        }
        h2 {
            font-size: 24px;
            border-bottom: 2px solid #334155;
            padding-bottom: 10px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 30px;
        }
        input[type="file"] {
            background-color: #0f172a;
            color: #f8fafc;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #334155;
            width: 100%;
        }
        textarea {
            background-color: #0f172a;
            color: #f8fafc;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #334155;
            width: 100%;
            min-height: 100px;
            resize: vertical;
            font-family: inherit;
        }
        input[type="submit"], button[type="submit"] {
            background-color: #3b82f6;
            color: white;
            font-weight: 600;
            padding: 14px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 16px;
            width: 100%;
        }
        input[type="submit"]:hover, button[type="submit"]:hover {
            background-color: #2563eb;
        }
        .message {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            border-radius: 8px;
            font-weight: 600;
            transition: opacity 0.5s ease;
        }
        .success {
            background-color: #14532d;
            color: #4ade80;
        }
        .error {
            background-color: #7f1d1d;
            color: #f87171;
        }
        .file-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .file-item {
            background-color: #1e293b;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            position: relative;
        }
        .file-item:hover {
            transform: translateY(-5px);
        }
        .file-item h3 {
            color: #38bdf8;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .file-item h3 a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s;
        }
        .file-item h3 a:hover {
            color: #7dd3fc;
            text-decoration: underline;
        }
        .file-item p {
            margin-bottom: 8px;
            color: #94a3b8;
        }
        .file-item strong {
            color: #e2e8f0;
        }
        .file-actions {
            position: absolute;
            top: 15px;
            right: 15px;
            display: flex;
            gap: 10px;
        }
        .file-actions button {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 16px;
            transition: color 0.3s;
        }
        .file-actions button:hover {
            color: #f8fafc;
        }
        .file-actions .delete-btn:hover {
            color: #ef4444;
        }
        .file-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .file-images img {
            max-width: 100px;
            max-height: 100px;
            border-radius: 5px;
            object-fit: cover;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            background-color: #1e293b;
            border-radius: 12px;
            color: #94a3b8;
        }
        .excel-preview {
            margin-top: 15px;
            border: 1px solid #334155;
            padding: 10px;
            border-radius: 5px;
        }
        .excel-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .excel-table th, .excel-table td {
            border: 1px solid #334155;
            padding: 8px;
            text-align: left;
        }
        .excel-table th {
            background-color: #1e293b;
            color: #f8fafc;
        }
        .excel-table tr:nth-child(even) {
            background-color: #0f172a;
        }
        .excel-table tr:nth-child(odd) {
            background-color: #1e293b;
        }
        .file-preview-image {
            max-width: 100%;
            max-height: 200px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                padding: 20px;
            }
            .nav-links {
                gap: 15px;
            }
            .file-list {
                grid-template-columns: 1fr;
            }
            .excel-table {
                display: block;
                overflow-x: auto;
            }
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
        <a href="?logout=true" class="logout-btn">Logout</a>
    </nav>
</div>

<div class="main-container">
    <h1>File Management</h1>
    
    <div class="upload-container">
        <h2>📁 Upload a File</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="file" required accept=".txt,.md,.json,.docx,.pdf,.jpg,.jpeg,.png,.gif,.xlsx,.xls" />
            <textarea name="description" placeholder="File description (optional)"></textarea>
            <input type="submit" name="upload" value="Upload">
        </form>
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="file-list-container">
        <h2>📂 Uploaded Files</h2>
        <?php if (empty($allBlogs)): ?>
            <div class="empty-state">
                <p>No files uploaded yet.</p>
            </div>
        <?php else: ?>
            <div class="file-list">
                <?php foreach ($allBlogs as $index => $blog): ?>
                    <div class="file-item">
                        <div class="file-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="file_index" value="<?= $index ?>">
                                <button type="submit" name="delete_file" class="delete-btn" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                        <h3>
                            <a href="<?= htmlspecialchars($blog['file_path'] ?? '#') ?>" target="_blank">
                                <?= htmlspecialchars($blog['title'] ?? 'Untitled') ?>
                            </a>
                        </h3>
                        
                        <?php if (!empty($blog['thumbnail'])): ?>
                            <img src="<?= htmlspecialchars($blog['thumbnail']) ?>" alt="Thumbnail" class="file-preview-image">
                        <?php elseif (!empty($blog['image'])): ?>
                            <img src="<?= htmlspecialchars($blog['image']) ?>" alt="Image" class="file-preview-image">
                        <?php endif; ?>
                        
                        <p><strong>Description:</strong> <?= htmlspecialchars($blog['description'] ?? 'No description') ?></p>
                        <p><strong>Type:</strong> <?= strtoupper($blog['file_type'] ?? 'UNKNOWN') ?></p>
                        <p><strong>Size:</strong> 
                            <?php 
                                if (isset($blog['file_size'])) {
                                    $size = $blog['file_size'];
                                    if ($size < 1024) {
                                        echo $size . ' bytes';
                                    } elseif ($size < 1048576) {
                                        echo round($size/1024, 2) . ' KB';
                                    } else {
                                        echo round($size/1048576, 2) . ' MB';
                                    }
                                } else {
                                    echo 'N/A';
                                }
                            ?>
                        </p>
                        <p><strong>Uploaded:</strong> <?= isset($blog['timestamp']) ? date('M d, Y H:i', strtotime($blog['timestamp'])) : 'Unknown' ?></p>
                        
                        <?php if (!empty($blog['excel_data'])): ?>
                            <div class="excel-preview">
                                <h4>Excel Data:</h4>
                                <table class="excel-table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Description</th>
                                            <th>Image</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($blog['excel_data'] as $row): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['title'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($row['description'] ?? '') ?></td>
                                                <td>
                                                    <?php if (!empty($row['image_path'])): ?>
                                                        <img src="<?= htmlspecialchars($row['image_path']) ?>" style="max-width: 100px;">
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>