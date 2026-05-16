<?php
$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
if ($method !== 'POST') { http_response_code(405); exit; }

if (!class_exists('ZipArchive')) { http_response_code(500); echo 'ZipArchive not available'; exit; }

$input = file_get_contents('php://input');
$filePaths = json_decode($input, true);
if (!is_array($filePaths) || empty($filePaths)) { http_response_code(400); exit; }

$basePath = realpath(__DIR__);
$tmpFile = tempnam(sys_get_temp_dir(), 'sica_zip_');

$zip = new ZipArchive();
if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit;
}

foreach ($filePaths as $file) {
    if (!is_string($file)) { continue; }
    $normalized = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $file);
    if (pathinfo($normalized, PATHINFO_EXTENSION) === '') {
        $normalized .= '.html';
    }
    $fullPath = $basePath . DIRECTORY_SEPARATOR . $normalized;
    $realPath = realpath($fullPath);
    if ($realPath === false || strpos($realPath, $basePath) !== 0 || !is_file($realPath)) { continue; }
    $zip->addFile($realPath, str_replace('\\', '/', $file));
}

$zip->close();

$date = date('Ymd-His');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="sica-changes-' . $date . '.zip"');
header('Content-Length: ' . filesize($tmpFile));
header('Cache-Control: no-cache');

readfile($tmpFile);
unlink($tmpFile);
