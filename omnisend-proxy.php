<?php
// SECURITY: Never commit the real API key to a public repository (GitHub etc.)
// If using version control, add omnisend-proxy.php to .gitignore

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$OMNISEND_API_KEY = '6981e17d27460b4ce15c57f3-Zvvm0gMWqFwiYLIviVvYk6lCu78e6N1d0ZFymIbq1fVZ7UgTOr';

$rawBody = file_get_contents('php://input');
error_log('Omnisend proxy input: ' . $rawBody);

$input = json_decode($rawBody, true);

if (empty($input['email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Email is required']);
    exit;
}

$tags = ['quiz-nao-qualificado', 'clinica-dentaria'];
if (($input['source'] ?? '') === 'popup') {
    $tags[] = 'website-popup';
}

$contactStatus = ($input['source'] ?? '') === 'popup' ? 'subscribed' : 'nonSubscribed';

$payload = [
    'email'      => $input['email'],
    'status'     => $contactStatus,
    'statusDate' => date('c'),
    'tags'       => $tags,
    'customProperties' => [
        'quiz_step1' => $input['step1'] ?? '',
        'quiz_step4' => $input['step4'] ?? '',
        'quiz_date'  => date('c'),
        'quiz_source' => 'quiz-clinicas-pt'
    ],
    'sendWelcomeEmail' => false
];

if (!empty($input['name'])) {
    $payload['firstName'] = $input['name'];
}

$ch = curl_init('https://api.omnisend.com/v3/contacts');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'X-API-KEY: ' . $OMNISEND_API_KEY
    ]
]);

$response = curl_exec($ch);
$status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

error_log('Omnisend response status: ' . $status);
error_log('Omnisend response body: ' . $response);

http_response_code($status);
echo $response;
