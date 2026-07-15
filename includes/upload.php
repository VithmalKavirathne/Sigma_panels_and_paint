<?php
// Sigma Panels & Paint - File Upload Utility
// Phase 16 - safe, reusable image/icon upload helper.

require_once __DIR__ . '/config.php';

/**
 * Safely handle an uploaded image/icon.
 *
 * @param string $fileKey     The $_FILES key.
 * @param string $subdir      Sub-directory under uploads/ (e.g. 'seo', 'settings').
 * @param array  $allowedExts Lower-case extensions allowed (e.g. ['png','ico']).
 * @param int    $maxBytes    Max file size in bytes.
 * @return array{path:?string, error:?string, uploaded:bool}
 *         path is a web path like 'uploads/seo/file.png' on success.
 */
function save_upload($fileKey, $subdir, array $allowedExts, $maxBytes = 2097152) {
    if (empty($_FILES[$fileKey]) || ($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'error' => null, 'uploaded' => false];
    }
    $f = $_FILES[$fileKey];

    if ($f['error'] !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'Upload failed. Please try again.', 'uploaded' => false];
    }
    if ($f['size'] > $maxBytes) {
        return ['path' => null, 'error' => 'File too large (max ' . round($maxBytes / 1048576, 1) . 'MB).', 'uploaded' => false];
    }

    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) {
        return ['path' => null, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedExts) . '.', 'uploaded' => false];
    }

    // MIME sanity check (defence in depth; the generated extension is what matters
    // for execution safety, since files are served statically and never as PHP).
    $mime = '';
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        $mime = (string) finfo_file($fi, $f['tmp_name']);
        finfo_close($fi);
    }
    $isImage = strpos($mime, 'image/') === 0;
    $isIco   = ($ext === 'ico') && in_array($mime, ['image/x-icon', 'image/vnd.microsoft.icon', 'image/ico', 'application/octet-stream'], true);
    if ($mime !== '' && !$isImage && !$isIco) {
        return ['path' => null, 'error' => 'That file does not look like a valid image.', 'uploaded' => false];
    }

    $dir = rtrim(UPLOAD_DIR, '/') . '/' . trim($subdir, '/') . '/';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['path' => null, 'error' => 'Could not create the upload folder.', 'uploaded' => false];
    }

    $base = preg_replace('/[^a-z0-9]+/', '-', strtolower(pathinfo($f['name'], PATHINFO_FILENAME)));
    $base = trim($base, '-');
    $base = $base !== '' ? substr($base, 0, 24) : 'file';
    $filename = $base . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;

    if (!move_uploaded_file($f['tmp_name'], $dir . $filename)) {
        return ['path' => null, 'error' => 'Could not save the uploaded file.', 'uploaded' => false];
    }

    return ['path' => 'uploads/' . trim($subdir, '/') . '/' . $filename, 'error' => null, 'uploaded' => true];
}

/**
 * Safely handle an uploaded MP4 video.
 *
 * Applies the same defence-in-depth rules as save_upload() but scoped to video:
 * extension (.mp4 only), MIME (video/mp4), size, a generated unique filename,
 * and a destination inside uploads/ (never trusting the original filename, so
 * no path traversal and no executable file types can land in the folder).
 *
 * @param string $fileKey  The $_FILES key.
 * @param string $subdir   Sub-directory under uploads/ (e.g. 'homepage/videos').
 * @param int    $maxBytes Max file size in bytes (default 25 MB).
 * @return array{path:?string, error:?string, uploaded:bool}
 */
function save_video_upload($fileKey, $subdir, $maxBytes = 26214400) {
    if (empty($_FILES[$fileKey]) || ($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'error' => null, 'uploaded' => false];
    }
    $f = $_FILES[$fileKey];

    if ($f['error'] !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'Video upload failed. Please try again.', 'uploaded' => false];
    }
    if ($f['size'] > $maxBytes) {
        return ['path' => null, 'error' => 'Video too large (max ' . round($maxBytes / 1048576) . 'MB).', 'uploaded' => false];
    }

    // Only ever accept a .mp4 extension - the generated filename below uses this
    // (whitelisted) extension, so nothing executable can be written.
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if ($ext !== 'mp4') {
        return ['path' => null, 'error' => 'Invalid file type. Only .mp4 videos are allowed.', 'uploaded' => false];
    }

    // MIME check: must actually be an MP4 container.
    $mime = '';
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        $mime = (string) finfo_file($fi, $f['tmp_name']);
        finfo_close($fi);
    }
    // Some environments report mp4 as application/octet-stream; accept the common
    // video containers that resolve to an mp4 stream, but reject anything else.
    $allowedMime = ['video/mp4', 'video/x-m4v', 'application/mp4', 'application/octet-stream'];
    if ($mime !== '' && !in_array($mime, $allowedMime, true)) {
        return ['path' => null, 'error' => 'That file does not look like a valid MP4 video.', 'uploaded' => false];
    }

    $dir = rtrim(UPLOAD_DIR, '/') . '/' . trim($subdir, '/') . '/';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['path' => null, 'error' => 'Could not create the upload folder.', 'uploaded' => false];
    }

    $base = preg_replace('/[^a-z0-9]+/', '-', strtolower(pathinfo($f['name'], PATHINFO_FILENAME)));
    $base = trim($base, '-');
    $base = $base !== '' ? substr($base, 0, 24) : 'video';
    $filename = $base . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;

    if (!move_uploaded_file($f['tmp_name'], $dir . $filename)) {
        return ['path' => null, 'error' => 'Could not save the uploaded video.', 'uploaded' => false];
    }

    return ['path' => 'uploads/' . trim($subdir, '/') . '/' . $filename, 'error' => null, 'uploaded' => true];
}

/**
 * Delete a previously-uploaded file referenced by its stored web path
 * (e.g. 'uploads/homepage/videos/foo.mp4'), with strict safety:
 *   - only paths beginning with 'uploads/' are considered;
 *   - the resolved real path must stay inside UPLOAD_DIR (no traversal);
 *   - never deletes anything outside the allowed upload tree.
 *
 * @param string $webPath Stored relative path.
 * @return bool True if a file was deleted (or nothing needed deleting).
 */
function delete_upload_file($webPath) {
    $webPath = trim((string) $webPath);
    if ($webPath === '' || strpos($webPath, 'uploads/') !== 0) {
        return false;
    }
    // Disallow obvious traversal tokens before touching the filesystem.
    if (strpos($webPath, '..') !== false) {
        return false;
    }

    $uploadRoot = realpath(rtrim(UPLOAD_DIR, '/'));
    $relative   = ltrim(substr($webPath, strlen('uploads/')), '/');
    $target     = realpath($uploadRoot . '/' . $relative);

    if ($uploadRoot === false || $target === false) {
        return false; // file already gone or path invalid
    }
    // Ensure the resolved target really lives under the upload root.
    if (strpos($target, $uploadRoot . DIRECTORY_SEPARATOR) !== 0) {
        return false;
    }
    if (is_file($target)) {
        return @unlink($target);
    }
    return false;
}
