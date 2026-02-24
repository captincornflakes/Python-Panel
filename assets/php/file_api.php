<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json');

if (!is_user_logged_in()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error'   => 'Unauthorized: login required.',
    ]);
    exit;
}

$username = $_SESSION['user'] ?? null;

if ($username === null || $username === '') {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'No username found in session.',
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = strtolower(trim($_REQUEST['action'] ?? ''));

$allowed_actions = [
    'list',    // list files/directories within a project
    'upload',  // upload a file into a project directory
    'delete',  // delete a file or directory (recursive for dirs)
    'get',     // get file contents
    'save',    // write file contents (create/update)
    'rename',  // rename a file or directory within a project
];

if ($action === '' || !in_array($action, $allowed_actions, true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Invalid or missing action.',
        'allowed' => $allowed_actions,
    ]);
    exit;
}

/**
 * Ensure a project id is in the expected safe format.
 */
function sanitize_project_id(string $projectId): ?string
{
    $projectId = trim($projectId);
    if ($projectId === '') {
        return null;
    }
    // Project ids are generated from [a-zA-Z0-9]; enforce a conservative pattern.
    if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $projectId)) {
        return null;
    }
    return $projectId;
}

/**
 * Sanitize a relative path inside a project so it cannot escape the project root.
 * Returns a normalized path using forward slashes, or null if invalid.
 */
function sanitize_relative_path(?string $path): ?string
{
    if ($path === null) {
        return '';
    }

    // Normalize separators and trim whitespace
    $path = str_replace('\\', '/', $path);
    $path = trim($path);

    if ($path === '' || $path === '.' || $path === './') {
        return '';
    }

    // Remove leading slashes
    $path = ltrim($path, '/');

    // Disallow parent traversal or Windows drive prefixes
    if (strpos($path, '..') !== false) {
        return null;
    }
    if (preg_match('/^[A-Za-z]:/', $path)) {
        return null;
    }

    // Collapse duplicate slashes
    $path = preg_replace('#/+?#', '/', $path);

    return $path;
}

/**
 * Resolve the absolute project directory for the given user and project id.
 * Exits with an error response if the directory cannot be resolved.
 */
function resolve_project_dir_or_exit(string $username, string $projectId): string
{
    $projectsDir = __DIR__ . '/../../data/projects';
    if (!is_dir($projectsDir) && !mkdir($projectsDir, 0770, true) && !is_dir($projectsDir)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => 'Failed to access projects directory.',
        ]);
        exit;
    }

    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $username);
    $userDir = $projectsDir . '/' . $safeName;
    if (!is_dir($userDir) && !mkdir($userDir, 0770, true) && !is_dir($userDir)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => 'Failed to access user project directory.',
        ]);
        exit;
    }

    $projectDir = $userDir . '/' . $projectId;
    // Ensure the per-project directory exists so file operations and uploads
    // can succeed even for older projects or ones created before directories
    // were provisioned.
    if (!is_dir($projectDir) && !mkdir($projectDir, 0770, true) && !is_dir($projectDir)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => 'Failed to access project directory.',
        ]);
        exit;
    }

    return $projectDir;
}

/**
 * Recursively delete a directory tree.
 */
function delete_tree(string $path): void
{
    if (!is_dir($path)) {
        @unlink($path);
        return;
    }

    $items = @scandir($path);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $full = $path . DIRECTORY_SEPARATOR . $item;
        if (is_dir($full)) {
            delete_tree($full);
        } else {
            @unlink($full);
        }
    }

    @rmdir($path);
}

$response = [
    'success' => false,
    'action'  => $action,
    'user'    => $username,
    'method'  => $method,
];

switch ($action) {
    case 'list':
        $projectIdRaw = $_REQUEST['project_id'] ?? '';
        $projectId    = sanitize_project_id((string)$projectIdRaw);
        if ($projectId === null) {
            http_response_code(400);
            $response['error'] = 'Invalid or missing project_id.';
            break;
        }

        $subPathRaw = $_REQUEST['path'] ?? null;
        $subPath    = sanitize_relative_path($subPathRaw);
        if ($subPath === null) {
            http_response_code(400);
            $response['error'] = 'Invalid path parameter.';
            break;
        }

        $projectDir = resolve_project_dir_or_exit($username, $projectId);
        $targetDir  = $projectDir;
        if ($subPath !== '') {
            $targetDir .= '/' . $subPath;
        }

        if (!is_dir($targetDir)) {
            http_response_code(404);
            $response['error'] = 'Directory not found.';
            break;
        }

        $items = @scandir($targetDir);
        if ($items === false) {
            http_response_code(500);
            $response['error'] = 'Failed to read directory.';
            break;
        }

        $entries = [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $targetDir . '/' . $item;
            $isDir = is_dir($full);
            // Ignore dot-prefixed directories (e.g. .venv, .git) as system folders
            if ($isDir && isset($item[0]) && $item[0] === '.') {
                continue;
            }
            $relPath = $subPath !== '' ? ($subPath . '/' . $item) : $item;

            $entries[] = [
                'name'     => $item,
                'path'     => $relPath,
                'type'     => $isDir ? 'dir' : 'file',
                'size'     => $isDir ? null : (@filesize($full) ?: 0),
                'modified' => @filemtime($full) ? date('c', @filemtime($full)) : null,
            ];
        }

        $response['success'] = true;
        $response['message'] = 'Directory listed.';
        $response['project_id'] = $projectId;
        $response['base_path']  = $subPath;
        $response['entries']    = $entries;
        break;

    case 'upload':
        if ($method !== 'POST') {
            http_response_code(405);
            $response['error'] = 'Upload must use POST.';
            break;
        }

        $projectIdRaw = $_REQUEST['project_id'] ?? '';
        $projectId    = sanitize_project_id((string)$projectIdRaw);
        if ($projectId === null) {
            http_response_code(400);
            $response['error'] = 'Invalid or missing project_id.';
            break;
        }

        if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            http_response_code(400);
            $response['error'] = 'Missing or invalid file upload.';
            break;
        }

        $subPathRaw = $_REQUEST['path'] ?? null;
        $subPath    = sanitize_relative_path($subPathRaw);
        if ($subPath === null) {
            http_response_code(400);
            $response['error'] = 'Invalid path parameter.';
            break;
        }

        $projectDir = resolve_project_dir_or_exit($username, $projectId);
        $targetDir  = $projectDir;
        if ($subPath !== '') {
            $targetDir .= '/' . $subPath;
        }

        if (!is_dir($targetDir) && !mkdir($targetDir, 0770, true) && !is_dir($targetDir)) {
            http_response_code(500);
            $response['error'] = 'Failed to create target directory.';
            break;
        }

        $originalName = $_FILES['file']['name'] ?? 'upload.bin';
        $originalName = is_string($originalName) ? $originalName : 'upload.bin';
        $baseName     = basename($originalName);
        // Conservative filename sanitization
        $safeName     = preg_replace('/[^a-zA-Z0-9._-]/', '_', $baseName);
        if ($safeName === '' || $safeName === '.' || $safeName === '..') {
            $safeName = 'upload.bin';
        }

        $targetPath = $targetDir . '/' . $safeName;

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            http_response_code(500);
            $response['error'] = 'Failed to move uploaded file.';
            break;
        }

        $response['success']    = true;
        $response['message']    = 'File uploaded.';
        $response['project_id'] = $projectId;
        $response['path']       = $subPath !== '' ? ($subPath . '/' . $safeName) : $safeName;
        break;

    case 'delete':
        $projectIdRaw = $_REQUEST['project_id'] ?? '';
        $projectId    = sanitize_project_id((string)$projectIdRaw);
        if ($projectId === null) {
            http_response_code(400);
            $response['error'] = 'Invalid or missing project_id.';
            break;
        }

        $targetPathRaw = $_REQUEST['path'] ?? null;
        $targetPathRel = sanitize_relative_path($targetPathRaw);
        if ($targetPathRel === null || $targetPathRel === '') {
            http_response_code(400);
            $response['error'] = 'Invalid or missing path parameter.';
            break;
        }

        $projectDir  = resolve_project_dir_or_exit($username, $projectId);
        $absolute    = $projectDir . '/' . $targetPathRel;

        if (!file_exists($absolute)) {
            http_response_code(404);
            $response['error'] = 'File or directory not found.';
            break;
        }

        delete_tree($absolute);

        $response['success']    = true;
        $response['message']    = 'Deleted.';
        $response['project_id'] = $projectId;
        $response['path']       = $targetPathRel;
        break;

    case 'get':
        $projectIdRaw = $_REQUEST['project_id'] ?? '';
        $projectId    = sanitize_project_id((string)$projectIdRaw);
        if ($projectId === null) {
            http_response_code(400);
            $response['error'] = 'Invalid or missing project_id.';
            break;
        }

        $targetPathRaw = $_REQUEST['path'] ?? null;
        $targetPathRel = sanitize_relative_path($targetPathRaw);
        if ($targetPathRel === null || $targetPathRel === '') {
            http_response_code(400);
            $response['error'] = 'Invalid or missing path parameter.';
            break;
        }

        $projectDir = resolve_project_dir_or_exit($username, $projectId);
        $absolute   = $projectDir . '/' . $targetPathRel;

        if (!is_file($absolute)) {
            http_response_code(404);
            $response['error'] = 'File not found.';
            break;
        }

        $contents = @file_get_contents($absolute);
        if ($contents === false) {
            http_response_code(500);
            $response['error'] = 'Failed to read file.';
            break;
        }

        $response['success']    = true;
        $response['message']    = 'File contents retrieved.';
        $response['project_id'] = $projectId;
        $response['path']       = $targetPathRel;
        $response['content']    = $contents;
        break;

    case 'save':
        if ($method !== 'POST') {
            http_response_code(405);
            $response['error'] = 'Save must use POST.';
            break;
        }

        $projectIdRaw = $_REQUEST['project_id'] ?? '';
        $projectId    = sanitize_project_id((string)$projectIdRaw);
        if ($projectId === null) {
            http_response_code(400);
            $response['error'] = 'Invalid or missing project_id.';
            break;
        }

        $targetPathRaw = $_REQUEST['path'] ?? null;
        $targetPathRel = sanitize_relative_path($targetPathRaw);
        if ($targetPathRel === null || $targetPathRel === '') {
            http_response_code(400);
            $response['error'] = 'Invalid or missing path parameter.';
            break;
        }

        // We accept content from POST form data; if not set, try raw body
        $content = $_POST['content'] ?? null;
        if ($content === null) {
            $raw = file_get_contents('php://input');
            $content = $raw !== false ? $raw : '';
        }

        $projectDir = resolve_project_dir_or_exit($username, $projectId);
        $absolute   = $projectDir . '/' . $targetPathRel;

        $dirName = dirname($absolute);
        if (!is_dir($dirName) && !mkdir($dirName, 0770, true) && !is_dir($dirName)) {
            http_response_code(500);
            $response['error'] = 'Failed to create parent directory.';
            break;
        }

        if (@file_put_contents($absolute, $content) === false) {
            http_response_code(500);
            $response['error'] = 'Failed to write file.';
            break;
        }

        $response['success']    = true;
        $response['message']    = 'File saved.';
        $response['project_id'] = $projectId;
        $response['path']       = $targetPathRel;
        break;

    case 'rename':
        if ($method !== 'POST') {
            http_response_code(405);
            $response['error'] = 'Rename must use POST.';
            break;
        }

        $projectIdRaw = $_REQUEST['project_id'] ?? '';
        $projectId    = sanitize_project_id((string)$projectIdRaw);
        if ($projectId === null) {
            http_response_code(400);
            $response['error'] = 'Invalid or missing project_id.';
            break;
        }

        $targetPathRaw = $_REQUEST['path'] ?? null;
        $targetPathRel = sanitize_relative_path($targetPathRaw);
        if ($targetPathRel === null || $targetPathRel === '') {
            http_response_code(400);
            $response['error'] = 'Invalid or missing path parameter.';
            break;
        }

        $newNameRaw = $_REQUEST['new_name'] ?? '';
        $newNameRaw = is_string($newNameRaw) ? trim($newNameRaw) : '';
        if ($newNameRaw === '' || $newNameRaw === '.' || $newNameRaw === '..') {
            http_response_code(400);
            $response['error'] = 'Invalid or missing new_name.';
            break;
        }

        // new_name should be a simple name, not a path
        if (strpos($newNameRaw, '/') !== false || strpos($newNameRaw, '\\') !== false) {
            http_response_code(400);
            $response['error'] = 'new_name must not contain path separators.';
            break;
        }

        $safeNewName = preg_replace('/[^A-Za-z0-9._-]/', '_', $newNameRaw);
        if ($safeNewName === '' || $safeNewName === '.' || $safeNewName === '..') {
            http_response_code(400);
            $response['error'] = 'new_name is not valid after sanitization.';
            break;
        }

        $projectDir = resolve_project_dir_or_exit($username, $projectId);
        $oldAbsolute = $projectDir . '/' . $targetPathRel;

        if (!file_exists($oldAbsolute)) {
            http_response_code(404);
            $response['error'] = 'File or directory not found.';
            break;
        }

        $parentDir = dirname($oldAbsolute);
        $newAbsolute = $parentDir . '/' . $safeNewName;

        if (file_exists($newAbsolute)) {
            http_response_code(409);
            $response['error'] = 'A file or directory with the new name already exists.';
            break;
        }

        if (!@rename($oldAbsolute, $newAbsolute)) {
            http_response_code(500);
            $response['error'] = 'Failed to rename file or directory.';
            break;
        }

        // Build the new relative path by replacing the last segment
        $parts = explode('/', $targetPathRel);
        $parts[count($parts) - 1] = $safeNewName;
        $newRelPath = implode('/', $parts);

        $response['success']    = true;
        $response['message']    = 'Renamed.';
        $response['project_id'] = $projectId;
        $response['old_path']   = $targetPathRel;
        $response['path']       = $newRelPath;
        $response['new_name']   = $safeNewName;
        break;
}

echo json_encode($response);
