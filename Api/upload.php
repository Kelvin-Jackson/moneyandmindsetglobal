<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$uploadDir = '../uploads/';

// Create directories if they don't exist
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
if (!file_exists($uploadDir . 'covers/')) {
    mkdir($uploadDir . 'covers/', 0777, true);
}
if (!file_exists($uploadDir . 'pdfs/')) {
    mkdir($uploadDir . 'pdfs/', 0777, true);
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file'])) {
        $file = $_FILES['file'];
        $fileType = $_POST['type'] ?? 'cover'; // 'cover' or 'pdf'
        
        $allowedTypes = [];
        $targetDir = $uploadDir;
        
        if ($fileType === 'cover') {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $targetDir .= 'covers/';
            $maxSize = 5 * 1024 * 1024; // 5MB
        } else {
            $allowedTypes = ['application/pdf'];
            $targetDir .= 'pdfs/';
            $maxSize = 20 * 1024 * 1024; // 20MB
        }
        
        // Validate file type
        if (!in_array($file['type'], $allowedTypes)) {
            $response['message'] = 'Invalid file type';
            echo json_encode($response);
            exit;
        }
        
        // Validate file size
        if ($file['size'] > $maxSize) {
            $response['message'] = 'File too large';
            echo json_encode($response);
            exit;
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $targetPath = $targetDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $response['success'] = true;
            $response['message'] = 'File uploaded successfully';
            $response['file_path'] = 'uploads/' . ($fileType === 'cover' ? 'covers/' : 'pdfs/') . $filename;
        } else {
            $response['message'] = 'Failed to upload file';
        }
    } else {
        $response['message'] = 'No file uploaded';
    }
} else {
    $response['message'] = 'Method not allowed';
}

echo json_encode($response);
?>