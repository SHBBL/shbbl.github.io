<?php
// Enable CORS for GitHub Pages
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// File paths for storing data
$settingsFile = 'data/settings.json';
$statusFile = 'data/status.json';

// Create data directory if it doesn't exist
if (!file_exists('data')) {
    mkdir('data', 0777, true);
}

// Initialize default files if they don't exist
if (!file_exists($settingsFile)) {
    $defaultSettings = [
        'hour' => 6,
        'minute' => 0,
        'durations' => [1,1,1,1,1]
    ];
    file_put_contents($settingsFile, json_encode($defaultSettings));
}

if (!file_exists($statusFile)) {
    $defaultStatus = [
        'master' => false,
        'zones' => array_fill(0, 5, false),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    file_put_contents($statusFile, json_encode($defaultStatus));
}

// Your actual API endpoint URL - replace with your real API server
$API_SERVER = 'https://your-actual-api-server.com';

// Get the endpoint from the request
$endpoint = $_GET['endpoint'] ?? '';

// Function to forward the request to the actual API server
function forwardRequest($method, $endpoint, $data = null) {
    global $API_SERVER;
    
    $url = $API_SERVER . '/' . $endpoint;
    $ch = curl_init($url);
    
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ]
    ];
    
    if ($data !== null) {
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }
    
    curl_setopt_array($ch, $options);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        http_response_code(500);
        echo json_encode(['error' => 'API request failed: ' . curl_error($ch)]);
        exit();
    }
    
    curl_close($ch);
    http_response_code($httpCode);
    echo $response;
}

// Handle different endpoints
switch ($endpoint) {
    case 'get_settings':
        forwardRequest('GET', 'settings');
        break;
        
    case 'save':
        $data = json_decode(file_get_contents('php://input'), true);
        forwardRequest('POST', 'settings', $data);
        break;
        
    case 'status':
        forwardRequest('GET', 'status');
        break;
        
    case 'water':
        forwardRequest('POST', 'water');
        break;
        
    case 'stop':
        forwardRequest('POST', 'stop');
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}
?> 