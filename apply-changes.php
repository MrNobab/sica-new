<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

error_reporting(0);
ini_set('display_errors', '0');

$endpointVersion = 'apply-changes-v2.1.0';
header('X-Apply-Changes-Version: ' . $endpointVersion);

$logFile = __DIR__ . DIRECTORY_SEPARATOR . 'apply-changes-error.log';

function write_log($message)
{
    global $logFile;
    @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n", FILE_APPEND);
}

function handle_shutdown()
{
    global $endpointVersion;
    $error = error_get_last();
    if ($error && isset($error['type']) && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
        write_log('FATAL: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        if (!headers_sent()) {
            http_response_code(500);
            echo json_encode(array(
                'success' => false,
                'error' => 'PHP fatal error. Ver apply-changes-error.log',
                'version' => $endpointVersion
            ));
        }
    }
}

register_shutdown_function('handle_shutdown');

function json_response($payload, $status)
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function get_value($array, $key, $defaultValue)
{
    return isset($array[$key]) ? $array[$key] : $defaultValue;
}

function apply_text_by_edit_id($html, $editId, $value)
{
    $safeValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    $pattern = '/(<[a-z0-9:-]+\b[^>]*\bdata-edit-id=["\']' . preg_quote($editId, '/') . '["\'][^>]*>)(.*?)(<\/[a-z0-9:-]+>)/is';
    return preg_replace($pattern, '$1' . $safeValue . '$3', $html, 1);
}

function apply_src_by_edit_id($html, $editId, $value)
{
    $safeValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    $pattern = '/(<[a-z0-9:-]+\b[^>]*\bdata-edit-id=["\']' . preg_quote($editId, '/') . '["\'][^>]*\bsrc=["\'])([^"\']*)(["\'][^>]*>)/i';
    return preg_replace($pattern, '$1' . $safeValue . '$3', $html, 1);
}

function replace_style_property($styleValue, $property, $newValue)
{
    $cleaned = preg_replace('/(^|;)\s*' . preg_quote($property, '/') . '\s*:\s*[^;]*/i', '', $styleValue);
    $cleaned = trim(preg_replace('/;{2,}/', ';', $cleaned), " \t\n\r\0\x0B;");
    if ($cleaned === '') {
        return $property . ': ' . $newValue . ';';
    }
    return $cleaned . '; ' . $property . ': ' . $newValue . ';';
}

function apply_style_by_edit_id($html, $editId, $property, $value)
{
    $safeValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    $tagPattern = '/<([a-z0-9:-]+)\b[^>]*\bdata-edit-id=["\']' . preg_quote($editId, '/') . '["\'][^>]*>/i';

    if (!preg_match($tagPattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
        return $html;
    }

    $fullTag = $matches[0][0];
    $offset = $matches[0][1];
    $stylePattern = '/\bstyle=["\']([^"\']*)["\']/i';

    if (preg_match($stylePattern, $fullTag, $styleMatch)) {
        $newStyle = replace_style_property($styleMatch[1], $property, $safeValue);
        $newTag = preg_replace($stylePattern, 'style="' . $newStyle . '"', $fullTag, 1);
    } else {
        $newTag = rtrim(substr($fullTag, 0, -1)) . ' style="' . $property . ': ' . $safeValue . ';">';
    }

    return substr_replace($html, $newTag, $offset, strlen($fullTag));
}

$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
if ($method !== 'POST') {
    json_response(array('success' => false, 'error' => 'POST required'), 405);
}

$input = file_get_contents('php://input');
if ($input === false || $input === '') {
    json_response(array('success' => false, 'error' => 'Empty input'), 400);
}

$changes = json_decode($input, true);
if (!is_array($changes)) {
    json_response(array('success' => false, 'error' => 'Invalid JSON'), 400);
}

$basePath = __DIR__;
$backupDir = $basePath . DIRECTORY_SEPARATOR . 'backups';
if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0755, true);
}

// Pages treated as global components — changes propagate to all PT HTML pages
$globalPreviewPages = array('_header-preview.html', '_footer-preview.html');

// HTML files that hold the real PT content (root level only, no quiz/en standalone pages)
$globalTargetPages = array(
    'index.html',
    'resultados.html',
    'ferramentas.html',
    'blog.html',
    'sobre.html',
    'artigo.html',
    'artigo-site-lento.html',
    'marketing-digital-clinicas-dentarias.html',
    'marketing-digital-clinicas-dentarias-v2.html',
);

$groupedChanges = array();
$skipped = array();
foreach ($changes as $change) {
    $page = get_value($change, 'page', '');
    $editId = get_value($change, 'editId', '');
    $property = get_value($change, 'property', '');
    if ($page === '' || $editId === '' || $property === '') {
        $skipped[] = array(
            'reason' => 'missing_required_fields',
            'page' => $page,
            'editId' => $editId,
            'property' => $property
        );
        continue;
    }
    // Global preview: fan out to all target pages
    if (in_array(basename($page), $globalPreviewPages)) {
        foreach ($globalTargetPages as $targetPage) {
            if (!isset($groupedChanges[$targetPage])) {
                $groupedChanges[$targetPage] = array();
            }
            $fanChange = $change;
            $fanChange['page'] = $targetPage;
            $groupedChanges[$targetPage][] = $fanChange;
        }
        continue;
    }
    if (!isset($groupedChanges[$page])) {
        $groupedChanges[$page] = array();
    }
    $groupedChanges[$page][] = $change;
}

$files = array();
$errors = array();
$total = 0;

foreach ($groupedChanges as $page => $pageChanges) {
    $normalizedPage = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $page);
    if (pathinfo($normalizedPage, PATHINFO_EXTENSION) === '') {
        $normalizedPage .= '.html';
    }
    $filePath = $basePath . DIRECTORY_SEPARATOR . $normalizedPage;
    $realPath = realpath($filePath);
    $realBase = realpath($basePath);

    if ($realPath === false || $realBase === false || strpos($realPath, $realBase) !== 0 || !is_file($realPath)) {
        $errors[] = 'Invalid path: ' . $page;
        write_log('Invalid path: ' . $page);
        continue;
    }

    $originalHtml = @file_get_contents($realPath);
    if ($originalHtml === false) {
        $errors[] = 'Read error: ' . $page;
        write_log('Read error: ' . $page);
        continue;
    }

    $modifiedHtml = $originalHtml;
    $applied = 0;

    foreach ($pageChanges as $change) {
        $editId = get_value($change, 'editId', '');
        $property = get_value($change, 'property', '');
        $value = get_value($change, 'newValue', get_value($change, 'value', ''));
        $before = $modifiedHtml;

        if ($property === 'text') {
            $modifiedHtml = apply_text_by_edit_id($modifiedHtml, $editId, $value);
        } elseif ($property === 'src') {
            $modifiedHtml = apply_src_by_edit_id($modifiedHtml, $editId, $value);
        } elseif ($property === 'color') {
            $modifiedHtml = apply_style_by_edit_id($modifiedHtml, $editId, 'color', $value);
        } elseif ($property === 'backgroundColor') {
            $modifiedHtml = apply_style_by_edit_id($modifiedHtml, $editId, 'background-color', $value);
        } elseif ($property === 'backgroundImage') {
            if (strpos($value, 'url(') !== 0) {
                $value = 'url("' . $value . '")';
            }
            $modifiedHtml = apply_style_by_edit_id($modifiedHtml, $editId, 'background-image', $value);
        }

        if ($before !== $modifiedHtml) {
            $applied++;
        } else {
            $skipped[] = array(
                'reason' => 'not_applied',
                'page' => $page,
                'editId' => $editId,
                'property' => $property
            );
        }
    }

    if ($applied === 0) {
        continue;
    }

    $backupFile = $backupDir . DIRECTORY_SEPARATOR . basename($page) . '.' . date('Ymd-His') . '.bak';
    @file_put_contents($backupFile, $originalHtml);

    if (@file_put_contents($realPath, $modifiedHtml) === false) {
        $errors[] = 'Save error: ' . $page;
        write_log('Save error: ' . $page);
        continue;
    }

    $files[$page] = array('applied' => $applied);
    $total += $applied;
}

json_response(array(
    'success' => count($errors) === 0,
    'version' => $endpointVersion,
    'files' => $files,
    'errors' => $errors,
    'total' => $total,
    'received' => count($changes),
    'skipped' => $skipped
), 200);
