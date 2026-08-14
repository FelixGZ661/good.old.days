<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

const QUIZ_API_KEY_SHA256 = '5faec1fd37ec8b3dd3b5a0763d3c542bcb4f1a243acd298247c53a948cdeecf4';
const QUIZ_FILE = __DIR__ . '/quiz-data.json';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$providedKey = $_SERVER['HTTP_X_QUIZ_KEY'] ?? '';

if (!is_string($providedKey) || $providedKey === '') {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

$providedHash = hash('sha256', $providedKey);

if (!hash_equals(QUIZ_API_KEY_SHA256, $providedHash)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);

if (!is_array($data) || !isset($data['behauptungen']) || !is_array($data['behauptungen'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON: behauptungen[] missing'], JSON_UNESCAPED_UNICODE);
    exit;
}

$behauptungen = [];

foreach ($data['behauptungen'] as $item) {
    if (!is_array($item)) {
        continue;
    }

    $behauptung = trim((string)($item['behauptung'] ?? ''));
    $loesung = strtoupper(trim((string)($item['loesung'] ?? '')));
    $erklaerung = trim((string)($item['erklaerung'] ?? ''));

    if ($behauptung === '' || $erklaerung === '' || !in_array($loesung, ['WAHR', 'FALSCH'], true)) {
        continue;
    }

    $behauptungen[] = [
        'behauptung' => $behauptung,
        'loesung' => $loesung,
        'erklaerung' => $erklaerung,
    ];
}

if (count($behauptungen) < 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No valid statements found'], JSON_UNESCAPED_UNICODE);
    exit;
}

$output = [
    'updated_at' => gmdate('c'),
    'count' => count($behauptungen),
    'behauptungen' => $behauptungen,
];

$json = json_encode(
    $output,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

if ($json === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not encode JSON'], JSON_UNESCAPED_UNICODE);
    exit;
}

$tmp = QUIZ_FILE . '.tmp';

if (file_put_contents($tmp, $json, LOCK_EX) === false || !rename($tmp, QUIZ_FILE)) {
    @unlink($tmp);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save quiz data'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'saved' => count($behauptungen),
    'file' => 'quiz-data.json',
], JSON_UNESCAPED_UNICODE);
