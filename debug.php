<?php
// File untuk debugging path dan permission
session_start();

echo "<h1>🔍 Debug Information</h1>";

echo "<h2>1. Current Directory Structure:</h2>";
echo "<pre>";
echo "Current File: " . __FILE__ . "\n";
echo "Current Dir: " . __DIR__ . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "</pre>";

echo "<h2>2. Check pages/ folder:</h2>";
echo "<pre>";
if (is_dir('pages')) {
    echo "✅ Folder 'pages' exists\n\n";
    
    echo "Files in pages/:\n";
    $files = scandir('pages');
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $path = 'pages/' . $file;
            $perms = substr(sprintf('%o', fileperms($path)), -4);
            $readable = is_readable($path) ? '✅' : '❌';
            echo "$readable $file (permissions: $perms)\n";
        }
    }
} else {
    echo "❌ Folder 'pages' NOT found!\n";
    echo "Please create folder: " . __DIR__ . "/pages\n";
}
echo "</pre>";

echo "<h2>3. Check specific files:</h2>";
echo "<pre>";
$files_to_check = [
    'pages/test_content.php',
    'pages/inbound_content.php',
    'pages/dashboard_content.php',
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ $file EXISTS\n";
        echo "   Size: " . filesize($file) . " bytes\n";
        echo "   Readable: " . (is_readable($file) ? 'YES' : 'NO') . "\n\n";
    } else {
        echo "❌ $file NOT FOUND\n\n";
    }
}
echo "</pre>";

echo "<h2>4. Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>5. PHP Info:</h2>";
echo "<pre>";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "</pre>";

echo "<h2>6. Test AJAX Path:</h2>";
echo "<pre>";
$test_page = 'test';
$ajax_url = 'pages/' . $test_page . '_content.php';
echo "AJAX URL would be: $ajax_url\n";
echo "Full path: " . __DIR__ . '/' . $ajax_url . "\n";
echo "File exists: " . (file_exists($ajax_url) ? 'YES ✅' : 'NO ❌') . "\n";
echo "</pre>";

echo "<hr>";
echo "<h2>🧪 Quick Actions:</h2>";
echo '<a href="index.php" class="btn">Go to Index</a> ';
echo '<a href="index.php?page=test" class="btn">Test SPA (page=test)</a>';

echo "<style>
body { font-family: Arial; padding: 20px; }
pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
.btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
</style>";
?>