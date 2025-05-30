<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
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

$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

switch ($endpoint) {
    case 'get_settings':
        $settings = json_decode(file_get_contents($settingsFile), true);
        if ($settings) {
            echo json_encode($settings);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to read settings']);
        }
        break;
        
    case 'commands':
        // Return the current commands and settings
        $settings = json_decode(file_get_contents($settingsFile), true);
        $commands = [
            'water' => false,
            'stop' => false,
            'settings' => $settings
        ];
        
        // Check if there are any pending commands
        $commandsFile = 'data/commands.json';
        if (file_exists($commandsFile)) {
            $savedCommands = json_decode(file_get_contents($commandsFile), true);
            if ($savedCommands) {
                if (isset($savedCommands['water']) && $savedCommands['water'] === true) {
                    $commands['water'] = true;
                    // Clear the water command after reading it
                    $savedCommands['water'] = false;
                    file_put_contents($commandsFile, json_encode($savedCommands));
                }
                if (isset($savedCommands['stop']) && $savedCommands['stop'] === true) {
                    $commands['stop'] = true;
                }
            }
        }
        
        echo json_encode($commands);
        break;
        
    case 'clear_stop':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $commandsFile = 'data/commands.json';
            if (file_exists($commandsFile)) {
                $commands = json_decode(file_get_contents($commandsFile), true);
                if ($commands) {
                    $commands['stop'] = false;
                    file_put_contents($commandsFile, json_encode($commands));
                }
            }
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        }
        break;
        
    case 'status':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['zones']) || !is_array($data['zones']) || count($data['zones']) !== 5) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid zones data']);
                break;
            }
            
            $status = [
                'master' => (bool)$data['master'],
                'zones' => $data['zones'],
                'timestamp' => date('Y-m-d H:i:s'),
                'remaining_times' => isset($data['remaining_times']) ? $data['remaining_times'] : array_fill(0, 5, 0)
            ];
            
            if (file_put_contents($statusFile, json_encode($status))) {
                echo json_encode(['status' => 'success']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save status']);
            }
        } else {
            $status = json_decode(file_get_contents($statusFile), true);
            if ($status) {
                // Ensure remaining_times exists
                if (!isset($status['remaining_times'])) {
                    $status['remaining_times'] = array_fill(0, 5, 0);
                }
                echo json_encode($status);
            } else {
                echo json_encode([
                    'master' => false,
                    'zones' => array_fill(0, 5, false),
                    'timestamp' => date('Y-m-d H:i:s'),
                    'remaining_times' => array_fill(0, 5, 0)
                ]);
            }
        }
        break;
        
    case 'save':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
                break;
            }
            
            if (!isset($data['hour']) || !isset($data['minute']) || !isset($data['durations'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
                break;
            }
            
            if (!is_array($data['durations']) || count($data['durations']) !== 5) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Invalid durations array']);
                break;
            }
            
            if ($data['hour'] < 0 || $data['hour'] > 23 || $data['minute'] < 0 || $data['minute'] > 59) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Invalid time values']);
                break;
            }
            
            foreach ($data['durations'] as $duration) {
                if (!is_numeric($duration) || $duration < 0 || $duration > 60) {
                    http_response_code(400);
                    echo json_encode(['status' => 'error', 'message' => 'Invalid duration values']);
                    break 2;
                }
            }
            
            if (file_put_contents($settingsFile, json_encode($data))) {
                echo json_encode(['status' => 'success', 'message' => 'Settings saved successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Failed to save settings']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        }
        break;
        
    case 'water':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Set the water command in the commands file
            $commandsFile = 'data/commands.json';
            $commands = [
                'water' => true,
                'stop' => false,
                'settings' => json_decode(file_get_contents($settingsFile), true)
            ];
            
            if (file_put_contents($commandsFile, json_encode($commands))) {
                echo json_encode(['status' => 'success']);
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Failed to save command']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        }
        break;
        
    case 'stop':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Update status file to reflect all zones are off
            $status = [
                'master' => false,
                'zones' => array_fill(0, 5, false),
                'timestamp' => date('Y-m-d H:i:s'),
                'remaining_times' => array_fill(0, 5, 0)  // Reset all remaining times
            ];
            
            // Ensure the status file is updated
            if (file_put_contents($statusFile, json_encode($status))) {
                // Send success response
                echo json_encode(['status' => 'success']);
                
                // Force an immediate status check by the ESP8266
                // This is done by updating the commands endpoint
                $commands = [
                    'water' => false,
                    'stop' => true,
                    'settings' => json_decode(file_get_contents($settingsFile), true)
                ];
                file_put_contents('data/commands.json', json_encode($commands));
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Failed to update status']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found', 'received' => $endpoint]);
}
?> 