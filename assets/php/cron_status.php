<?php

// Simple cron script to refresh project status for all users.
// Intended to be run via CLI/cron (e.g. `php assets/php/cron_status.php`).
// It scans all per-user project JSON files and, for each project, checks
// if the associated screen session is active, then updates the stored
// status (running/offline) and last_status_check_at.

$projectsDir = __DIR__ . '/../../data/projects';

if (!is_dir($projectsDir)) {
    // Nothing to do if there is no projects directory yet.
    exit(0);
}

$files = glob($projectsDir . '/*.json');
if (!$files) {
    exit(0);
}

foreach ($files as $filePath) {
    $raw = @file_get_contents($filePath);
    if ($raw === false) {
        continue;
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['projects']) || !is_array($data['projects'])) {
        continue;
    }

    // Derive the safe user name from the JSON filename (matches the
    // per-user directory and is already sanitized).
    $safeUser = basename($filePath, '.json');

    // Get current screen sessions once per file for efficiency.
    $output = [];
    $ret    = null;
    @exec('/usr/bin/screen -ls 2>&1', $output, $ret);
    $joined = implode("\n", $output);

    $changed = false;
    foreach ($data['projects'] as &$p) {
        if (!isset($p['id'])) {
            continue;
        }
        if (!empty($p['screen'])) {
            $sessionName = $p['screen'];
        } else {
            // Canonical session name: project_<user>_<projectId>
            $sessionName = 'project_' . $safeUser . '_' . $p['id'];
            $p['screen'] = $sessionName;
            $changed = true;
        }
        if ($sessionName === '') {
            continue;
        }
        $active = (strpos($joined, $sessionName) !== false);
        $newStatus = $active ? 'running' : 'offline';
        if (!isset($p['status']) || $p['status'] !== $newStatus) {
            $p['status'] = $newStatus;
            $changed = true;
        }
        $p['last_status_check_at'] = date('c');
    }
    unset($p);

    if ($changed) {
        @file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }
}

exit(0);
