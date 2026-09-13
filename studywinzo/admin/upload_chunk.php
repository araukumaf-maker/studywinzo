<?php
/**
 * Chunked Upload Handler
 * Fast upload in 1MB pieces + progress + resume
 */
ini_set('display_errors', 0);
error_reporting(0);
require_once 'config.php';
requireAdmin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Chunk directory
$chunkDir = __DIR__.'/data/chunks';
@mkdir($chunkDir, 0755, true);

// ---------- Initialize upload ----------
if ($action === 'init') {
    $fileId = bin2hex(random_bytes(8));
    $fileName = preg_replace('/[^A-Za-z0-9._-]/', '_', $_POST['name'] ?? 'file');
    $fileSize = (int)($_POST['size'] ?? 0);
    $fileType = $_POST['type'] ?? '';
    $totalChunks = (int)($_POST['chunks'] ?? 0);
    $dest = $_POST['dest'] ?? 'videos'; // videos | notes | dpp | batches | logos | popups | institutions

    $allowed = ['videos','notes','dpp','batches','logos','popups','institutions'];
    if (!in_array($dest, $allowed)) { echo json_encode(['success'=>false,'error'=>'Invalid dest']); exit; }
    if ($fileSize <= 0 || $totalChunks <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid params']); exit; }

    $sessionDir = $chunkDir.'/'.$fileId;
    @mkdir($sessionDir, 0755, true);

    file_put_contents($sessionDir.'/meta.json', json_encode([
        'fileId' => $fileId,
        'name' => $fileName,
        'size' => $fileSize,
        'type' => $fileType,
        'chunks' => $totalChunks,
        'dest' => $dest,
        'received' => 0,
        'created' => time(),
    ], JSON_PRETTY_PRINT));

    echo json_encode(['success'=>true, 'fileId'=>$fileId, 'chunkSize'=>1048576]);
    exit;
}

// ---------- Receive chunk ----------
if ($action === 'chunk') {
    $fileId = preg_replace('/[^a-f0-9]/', '', $_POST['fileId'] ?? '');
    $index = (int)($_POST['index'] ?? -1);
    $sessionDir = $chunkDir.'/'.$fileId;

    if (!is_dir($sessionDir)) { echo json_encode(['success'=>false,'error'=>'Session not found']); exit; }
    if ($index < 0) { echo json_encode(['success'=>false,'error'=>'Invalid chunk index']); exit; }
    if (empty($_FILES['chunk']['tmp_name'])) { echo json_encode(['success'=>false,'error'=>'No chunk data']); exit; }

    $chunkPath = $sessionDir.'/chunk_'.$index;
    if (move_uploaded_file($_FILES['chunk']['tmp_name'], $chunkPath)) {
        // Update received count
        $meta = json_decode(file_get_contents($sessionDir.'/meta.json'), true);
        $meta['received'] = ($meta['received'] ?? 0) + 1;
        file_put_contents($sessionDir.'/meta.json', json_encode($meta, JSON_PRETTY_PRINT));
        echo json_encode(['success'=>true, 'index'=>$index]);
    } else {
        echo json_encode(['success'=>false, 'error'=>'Save failed']);
    }
    exit;
}

// ---------- Finalize (merge chunks) ----------
if ($action === 'finalize') {
    $fileId = preg_replace('/[^a-f0-9]/', '', $_POST['fileId'] ?? '');
    $sessionDir = $chunkDir.'/'.$fileId;

    if (!is_dir($sessionDir)) { echo json_encode(['success'=>false,'error'=>'Session not found']); exit; }

    $meta = json_decode(file_get_contents($sessionDir.'/meta.json'), true);
    $destMap = [
        'videos' => UPLOAD_VIDEOS,
        'notes' => UPLOAD_NOTES,
        'dpp' => UPLOAD_DPP,
        'batches' => UPLOAD_BATCHES,
        'logos' => UPLOAD_LOGOS,
        'popups' => UPLOAD_POPUPS,
        'institutions' => __DIR__.'/uploads/institutions',
    ];
    $destDir = $destMap[$meta['dest']] ?? UPLOAD_VIDEOS;

    $ext = strtolower(pathinfo($meta['name'], PATHINFO_EXTENSION));
    $finalName = 'f_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
    $finalPath = $destDir.'/'.$finalName;

    $out = fopen($finalPath, 'wb');
    if (!$out) { echo json_encode(['success'=>false,'error'=>'Cannot create file']); exit; }

    for ($i = 0; $i < $meta['chunks']; $i++) {
        $chunkPath = $sessionDir.'/chunk_'.$i;
        if (!file_exists($chunkPath)) {
            fclose($out);
            @unlink($finalPath);
            echo json_encode(['success'=>false,'error'=>"Missing chunk $i"]); exit;
        }
        fwrite($out, file_get_contents($chunkPath));
        @unlink($chunkPath);
    }
    fclose($out);

    // Wasmer's filesystem is ephemeral. Persist the completed file in
    // Supabase before deleting the temporary local copy.
    $remotePath = $meta['dest'].'/'.$finalName;
    $remoteUrl = supabaseUpload($finalPath, $remotePath, $meta['type'] ?: 'application/octet-stream');
    if (!$remoteUrl) {
        @unlink($finalPath);
        echo json_encode(['success'=>false,'error'=>'Supabase Storage upload failed']);
        exit;
    }

    // Cleanup
    @unlink($sessionDir.'/meta.json');
    @rmdir($sessionDir);

    echo json_encode([
        'success' => true,
        'filename' => $remoteUrl,
        'url' => $remoteUrl,
        'size' => $meta['size'],
        'path' => $remotePath,
    ]);
    exit;
}

// ---------- Cleanup old sessions ----------
if ($action === 'cleanup') {
    foreach (glob($chunkDir.'/*', GLOB_ONLYDIR) as $dir) {
        $metaFile = $dir.'/meta.json';
        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true);
            if (time() - ($meta['created'] ?? 0) > 3600) {
                foreach (glob($dir.'/*') as $f) @unlink($f);
                @rmdir($dir);
            }
        }
    }
    echo json_encode(['success'=>true]);
    exit;
}

echo json_encode(['success'=>false, 'error'=>'Unknown action']);
