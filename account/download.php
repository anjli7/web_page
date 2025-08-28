<?php
session_start();
require_once '../php/db.php';

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    die("Unauthorized access. Please login.");
}

$userId = $_SESSION['user_id'];

// 2. Check for required parameters
if (!isset($_GET['id']) || !isset($_GET['type'])) {
    http_response_code(400); // Bad Request
    die("Invalid request. Missing required parameters.");
}

$fileId = intval($_GET['id']);
$type = $_GET['type']; // 'certificate' or 'document'

// 3. Securely fetch file details from the database, ensuring the user has permission
if ($type === 'certificate') {
    // For certificates, join through applications to verify user_id
    $stmt = $conn->prepare(
        "SELECT c.file_path, c.original_name FROM certificate c 
         INNER JOIN applications a ON c.application_id = a.id 
         WHERE c.id = ? AND a.user_id = ?"
    );
} elseif ($type === 'document') {
    // For other documents, join through applications to verify user_id
    $stmt = $conn->prepare(
        "SELECT f.file_path, f.original_name FROM files f 
         INNER JOIN applications a ON f.model_id = a.id 
         WHERE f.id = ? AND a.user_id = ? AND f.model_type = 'application'"
    );
} else {
    http_response_code(400); // Bad Request
    die("Invalid file type specified.");
}

$stmt->bind_param("ii", $fileId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$file = $result->fetch_assoc();

if (!$file) {
    http_response_code(404); // Not Found
    die("File not found or you don't have permission to access this file.");
}

// 4. Construct the full, absolute file path safely
// Assumes your 'uploads' folder is in the root directory, parallel to 'php' and 'user' folders.
$filePath = dirname(__DIR__) . '/' . ltrim($file['file_path'], '/');

if (!file_exists($filePath)) {
    http_response_code(404); // Not Found
    error_log("File not found on server at path: " . $filePath); // For server-side debugging
    die("File does not exist on the server. Please contact an administrator.");
}

// 5. Send the file to the user for download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// Clear output buffer before reading the file
ob_clean();
flush();

readfile($filePath);
exit;


// session_start();
// require_once '../php/db.php';

// // 1. Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     http_response_code(401);
//     die("Unauthorized access. Please login.");
// }

// $userId = $_SESSION['user_id'];

// // 2. Check for required parameters
// if (!isset($_GET['id']) || !isset($_GET['type'])) {
//     http_response_code(400);
//     die("Invalid request.");
// }

// $fileId = intval($_GET['id']);
// $type = $_GET['type'];

// // 3. Securely fetch file details from the 'files' table
// if ($type === 'certificate') {
//     $stmt = $conn->prepare(
//         "SELECT f.file_path, f.original_name FROM files f 
//          INNER JOIN applications a ON f.model_id = a.id 
//          WHERE f.id = ? AND a.user_id = ? AND f.model_type = 'certificate'"
//     );
// } else {
//     http_response_code(400);
//     die("Invalid file type specified.");
// }

// $stmt->bind_param("ii", $fileId, $userId);
// $stmt->execute();
// $result = $stmt->get_result();
// $file = $result->fetch_assoc();

// if (!$file) {
//     http_response_code(404);
//     die("File not found or you don't have permission to access this file.");
// }

// // 4. Construct the full, absolute file path to the physical file
// $filePath = dirname(__DIR__) . '/' . ltrim($file['file_path'], '/');

// if (!file_exists($filePath)) {
//     http_response_code(404);
//     error_log("File not found on server at path: " . $filePath);
//     die("File does not exist on the server. Please contact an administrator.");
// }

// // 5. Send the file to the user for download
// header('Content-Description: File Transfer');
// header('Content-Type: application/octet-stream');
// header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
// header('Expires: 0');
// header('Cache-Control: must-revalidate');
// header('Pragma: public');
// header('Content-Length: ' . filesize($filePath));

// ob_clean();
// flush();

// readfile($filePath);
// exit;

?>
