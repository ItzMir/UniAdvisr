<?php
declare(strict_types=1);

function handle_upload(array $file, string $subfolder = 'attachments', int $maxSize = 5 * 1024 * 1024): string|false {
    $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        set_flash('error', 'File type not allowed.');
        return false;
    }
    if ($file['size'] > $maxSize) {
        set_flash('error', 'File too large (max 5 MB).');
        return false;
    }
    
    $uploadDir = __DIR__ . '/../../storage/uploads/' . $subfolder;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $storedName = uniqid('upload_', true) . '.' . $ext;
    $dest = $uploadDir . '/' . $storedName;
    
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return 'storage/uploads/' . $subfolder . '/' . $storedName;
    }
    return false;
}