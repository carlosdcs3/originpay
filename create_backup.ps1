$timestamp = Get-Date -Format "yyyy-MM-dd-HHmm"
$backupDir = "e:\projetos\DigiKash v1.0.5\DigiKash v1.0.5\backups"
if (-Not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
}
$zipFile = "$backupDir\originpay-before-enterprise-refactor-$timestamp.zip"

$exclude = @(
    "vendor", "node_modules", "core\storage\framework\cache", 
    "core\storage\framework\sessions", "core\storage\framework\views", 
    "core\storage\logs", "core\bootstrap\cache", "core\public\build", 
    "core\public\hot", ".git", ".idea", ".vscode", "*.log", "*.zip", ".env"
)

$source = "e:\projetos\DigiKash v1.0.5\DigiKash v1.0.5"

# We use 7z if available, else Compress-Archive is too slow and hard to exclude
# Let's write a simple C# script in PowerShell to zip it if Compress-Archive is hard to filter
Add-Type -AssemblyName System.IO.Compression.FileSystem
$compressionLevel = [System.IO.Compression.CompressionLevel]::Fastest

$zip = [System.IO.Compression.ZipFile]::Open($zipFile, [System.IO.Compression.ZipArchiveMode]::Create)

$files = Get-ChildItem -Path $source -Recurse -File
foreach ($file in $files) {
    $relativePath = $file.FullName.Substring($source.Length + 1)
    
    $skip = $false
    foreach ($ex in $exclude) {
        if ($ex -like "*\*") {
            if ($relativePath -match "^$([regex]::Escape($ex))") { $skip = $true; break }
        } elseif ($ex -like "*.*") {
            if ($file.Name -like $ex) { $skip = $true; break }
        } else {
            if ($relativePath -match "^$([regex]::Escape($ex))\\") { $skip = $true; break }
        }
    }
    
    if (-not $skip) {
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file.FullName, $relativePath, $compressionLevel) | Out-Null
    }
}
$zip.Dispose()
Write-Host "Backup created at: $zipFile"
