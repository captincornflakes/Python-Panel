<?php

require __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=utf-8');

if (!is_user_logged_in()) {
    http_response_code(401);
    echo "Unauthorized: login required.\n";
    exit;
}

$screenName = isset($_REQUEST['screen']) ? trim($_REQUEST['screen']) : '';
if ($screenName === '') {
    http_response_code(400);
    echo "Missing 'screen' parameter.\n";
    exit;
}

$projectsDir = __DIR__ . '/../../data/projects';
if (!is_dir($projectsDir)) {
    http_response_code(404);
    echo "No projects directory found.\n";
    exit;
}

$files = glob($projectsDir . '/*.json');
if (!$files) {
    http_response_code(404);
    echo "No project files found.\n";
    exit;
}

$logPath = null;

foreach ($files as $filePath) {
    $raw = @file_get_contents($filePath);
    if ($raw === false) {
        continue;
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['projects']) || !is_array($data['projects'])) {
        continue;
    }

    $safeUser = basename($filePath, '.json');
    foreach ($data['projects'] as $p) {
        if (!isset($p['id'])) {
            continue;
        }
        if (isset($p['screen']) && $p['screen'] !== '') {
            $session = $p['screen'];
        } else {
            // Canonical session name: project_<user>_<projectId>
            $session = 'project_' . $safeUser . '_' . $p['id'];
        }
        if ($session !== $screenName) {
            continue;
        }
        $projDir = $projectsDir . '/' . $safeUser . '/' . $p['id'];
        $candidate = $projDir . '/screen.log';
        if (is_file($candidate)) {
            $logPath = $candidate;
            break 2; // found
        }
    }
}

if ($logPath === null) {
    http_response_code(404);
    echo "No log found for screen: {$screenName}\n";
    exit;
}

// Output the tail of the log (last 200 lines) to avoid huge responses.
$linesToShow = 200;
$contents = @file($logPath, FILE_IGNORE_NEW_LINES);
if ($contents === false) {
    http_response_code(500);
    echo "Failed to read log file.\n";
    exit;
}

$total = count($contents);
$start = max(0, $total - $linesToShow);
$slice = array_slice($contents, $start);

echo implode("\n", $slice) . "\n";
