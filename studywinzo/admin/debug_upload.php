<?php
header('Content-Type: text/plain');
echo "=== PHP Upload Debug ===\n\n";
echo "file_uploads: " . ini_get('file_uploads') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "upload_tmp_dir: " . (ini_get('upload_tmp_dir') ?: sys_get_temp_dir()) . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n\n";

echo "=== Folders ===\n";
$dirs = ['uploads', 'uploads/batches', 'uploads/logos', 'data'];
foreach ($dirs as $d) {
    $p = __DIR__.'/'.$d;
    echo "$d: " . (is_dir($p) ? (is_writable($p) ? "✅ writable" : "❌ NOT writable") : "❌ does not exist") . "\n";
}

echo "\n=== Test Upload ===\n";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "POST received\n";
    echo "\$_FILES: " . print_r($_FILES, true) . "\n";
    if (!empty($_FILES['test']['tmp_name'])) {
        $dest = __DIR__.'/uploads/batches/test_'.time().'.jpg';
        if (move_uploaded_file($_FILES['test']['tmp_name'], $dest)) {
            echo "✅ Upload OK: $dest\n";
        } else {
            echo "❌ Move failed\n";
        }
    }
} else {
    echo "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
}
?>
<form method="POST" enctype="multipart/form-data">
<input type="file" name="test" required>
<button type="submit">Test Upload</button>
</form>
