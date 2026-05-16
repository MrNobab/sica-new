<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
error_reporting(0);
ini_set('display_errors', '0');

function json_response($payload, $status) {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'POST required'], 405);
}

$input = file_get_contents('php://input');
if (!$input) {
    json_response(['success' => false, 'error' => 'Empty input'], 400);
}

$data = json_decode($input, true);
if (!$data || empty($data['base64']) || empty($data['filename'])) {
    json_response(['success' => false, 'error' => 'Missing base64 or filename'], 400);
}

$base64 = $data['base64'];
$originalFilename = basename($data['filename']);

// Strip the data:image/...;base64, prefix
if (preg_match('/^data:image\/([a-z0-9+]+);base64,/i', $base64, $matches)) {
    $base64 = preg_replace('/^data:image\/[a-z0-9+]+;base64,/i', '', $base64);
    $ext = strtolower($matches[1]);
    // Normalise extensions
    if ($ext === 'jpeg') $ext = 'jpg';
    if ($ext === 'svg+xml') $ext = 'svg';
} else {
    // Try to get extension from filename
    $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'])) {
        json_response(['success' => false, 'error' => 'Unsupported image type'], 400);
    }
}

$imageData = base64_decode($base64, true);
if ($imageData === false) {
    json_response(['success' => false, 'error' => 'Invalid base64 data'], 400);
}

// Determine save directory
$saveDir = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images';
if (!is_dir($saveDir)) {
    mkdir($saveDir, 0755, true);
}

// Build a clean filename: strip extension, sanitize, add unique suffix
$nameWithoutExt = pathinfo($originalFilename, PATHINFO_FILENAME);
$nameWithoutExt = preg_replace('/[^a-zA-Z0-9_-]/', '-', $nameWithoutExt);
$nameWithoutExt = preg_replace('/-+/', '-', trim($nameWithoutExt, '-'));
if (!$nameWithoutExt) $nameWithoutExt = 'image';

$filename = $nameWithoutExt . '.' . $ext;
$savePath = $saveDir . DIRECTORY_SEPARATOR . $filename;

// If file with same name exists, add a short unique suffix
if (file_exists($savePath)) {
    $filename = $nameWithoutExt . '-' . substr(uniqid(), -6) . '.' . $ext;
    $savePath = $saveDir . DIRECTORY_SEPARATOR . $filename;
}

if (file_put_contents($savePath, $imageData) === false) {
    json_response(['success' => false, 'error' => 'Failed to save image'], 500);
}

// Return the relative path usable in HTML
$relativePath = 'assets/images/' . $filename;

json_response([
    'success' => true,
    'path'    => $relativePath,
    'filename' => $filename
], 200);
