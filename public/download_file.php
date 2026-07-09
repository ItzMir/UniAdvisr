<?php
require_once __DIR__ . '/../app/includes/init.php';
if (!is_logged_in()) die('Access denied');
$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);
if ($type === 'essay') {
    $stmt = $pdo->prepare("SELECT file_path, original_name FROM essays WHERE id = ?");
    $stmt->execute([$id]);
    $file = $stmt->fetch();
} else { // attachment
    $stmt = $pdo->prepare("SELECT file_path, original_name FROM appointment_documents WHERE id = ?");
    $stmt->execute([$id]);
    $file = $stmt->fetch();
}
if (!$file) die('File not found.');
$path = __DIR__ . '/../' . $file['file_path'];
if (!file_exists($path)) die('File missing.');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.$file['original_name'].'"');
readfile($path);