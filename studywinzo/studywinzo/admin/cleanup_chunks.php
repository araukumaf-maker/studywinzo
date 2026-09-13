<?php
require_once 'config.php';
$chunkDir = __DIR__.'/data/chunks';
$removed = 0;
foreach (glob($chunkDir.'/*', GLOB_ONLYDIR) as $dir) {
    $metaFile = $dir.'/meta.json';
    if (file_exists($metaFile)) {
        $meta = json_decode(file_get_contents($metaFile), true);
        if (time() - ($meta['created'] ?? 0) > 3600) {
            foreach (glob($dir.'/*') as $f) @unlink($f);
            @rmdir($dir);
            $removed++;
        }
    }
}
echo "Removed $removed old sessions\n";
