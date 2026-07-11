<?php
$timestamp = date('Y-m-d-Hi');
$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$zipFile = $backupDir . '/originpay-before-enterprise-refactor-' . $timestamp . '.zip';

$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Failed to create ZIP file\n");
}

$baseDir = __DIR__;
$excludeDirs = [
    'vendor', 'node_modules', 'core/storage/framework/cache', 'core/storage/framework/sessions',
    'core/storage/framework/views', 'core/storage/logs', 'core/bootstrap/cache', 'core/public/build',
    'core/public/hot', '.git', '.idea', '.vscode'
];
$excludeExts = ['.log', '.zip'];
$excludeFiles = ['.env'];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
    $path = $file->getPathname();
    $relativePath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $path);
    $relativePathNormalized = str_replace('\\', '/', $relativePath);

    // Check exclusions
    $exclude = false;
    foreach ($excludeDirs as $dir) {
        if (strpos($relativePathNormalized, $dir) === 0) {
            $exclude = true;
            break;
        }
    }
    
    if ($exclude) continue;

    foreach ($excludeFiles as $exFile) {
        if ($file->getFilename() === $exFile) {
            $exclude = true;
            break;
        }
    }

    if ($exclude) continue;

    foreach ($excludeExts as $ext) {
        if (substr($file->getFilename(), -strlen($ext)) === $ext) {
            $exclude = true;
            break;
        }
    }

    if ($exclude) continue;

    if ($file->isFile()) {
        $zip->addFile($path, $relativePathNormalized);
    } elseif ($file->isDir()) {
        $zip->addEmptyDir($relativePathNormalized);
    }
}

$zip->close();
echo "Backup created at: " . $zipFile . "\n";
