<?php
// /app/get-link.php
declare(strict_types=1);
require __DIR__ . '/config.php';

// CORS (ajuste na produção)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'METHOD_NOT_ALLOWED']);
  exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];

$platform = $data['platform'] ?? 'web';
$valid = ['ios','android','web'];
if (!in_array($platform, $valid, true)) $platform = 'web';

try {
  // Tabela: app_links(platform ENUM ios|android|web, url VARCHAR, active TINYINT)
  $stmt = $pdo->prepare("SELECT url FROM app_links WHERE platform = ? AND active = 1 LIMIT 1");
  $stmt->execute([$platform]);
  $row = $stmt->fetch();

  if (!$row) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'LINK_NOT_FOUND']);
    exit;
  }

  echo json_encode(['ok' => true, 'url' => $row['url']]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'QUERY_FAILED']);
}
