<?php
// Muy simple endpoint para manejar favoritos.
// IMPORTANTE: Esto NO tiene autenticación real. Para uso interno solamente.
// Guarda lista en favorites.json (mismo directorio).
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$storageFile = __DIR__ . DIRECTORY_SEPARATOR . 'favorites.json';
if (!file_exists($storageFile)) {
    file_put_contents($storageFile, json_encode([]), LOCK_EX);
}
// Cargar actuales
$raw = file_get_contents($storageFile);
$current = json_decode($raw, true);
if (!is_array($current)) { $current = []; }
// Normalizar (solo strings únicas)
$current = array_values(array_unique(array_filter(array_map('strval', $current))));
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    echo json_encode(['favorites' => $current]);
    exit;
}
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!is_array($data) || !isset($data['name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato inválido']);
    exit;
}
$name = trim((string)$data['name']);
if ($name === '' || strlen($name) > 180) {
    http_response_code(422);
    echo json_encode(['error' => 'Nombre inválido']);
    exit;
}
$action = $data['action'] ?? 'toggle';
if ($action === 'add') {
    if (!in_array($name, $current, true)) { $current[] = $name; }
} elseif ($action === 'remove') {
    $current = array_values(array_filter($current, fn($x) => $x !== $name));
} else { // toggle
    if (in_array($name, $current, true)) {
        $current = array_values(array_filter($current, fn($x) => $x !== $name));
    } else {
        $current[] = $name;
    }
}
// Guardar
file_put_contents($storageFile, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
// Responder
echo json_encode(['favorites' => $current]);
