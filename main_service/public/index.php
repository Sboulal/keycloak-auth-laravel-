<?php
header('Content-Type: application/json');

$uri = $_SERVER['REQUEST_URI'];

if (strpos($uri, '/ptc') !== false) {
    echo json_encode([
        'service' => 'main-service',
        'endpoint' => '/ptc',
        'status' => 'OK',
        'message' => 'PTC endpoint working fine!'
    ]);
} elseif (strpos($uri, '/prv') !== false) {
    echo json_encode([
        'service' => 'main-service',
        'endpoint' => '/prv',
        'status' => 'OK',
        'message' => 'PRV (private) endpoint works!'
    ]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
}
