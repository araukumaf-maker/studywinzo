<?php
/**
 * Standalone Add Content Handler
 * URL: manage_content.php?action=save
 */
require_once 'config.php';
requireAdmin();

header('Content-Type: application/json');
$action = $_POST['action'] ?? '';

if ($action !== 'save_content') { echo json_encode(['success'=>false,'error'=>'Invalid action']); exit; }

$id = trim($_POST['id'] ?? '');
$chId = trim($_POST['chapter_id'] ?? '');
$ctype = $_POST['ctype'] ?? 'note';
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$videoUrl = trim($_POST['video_url'] ?? '');
$externalUrl = trim($_POST['external_url'] ?? '');

if (!$title || !$chId) { echo json_encode(['success'=>false,'error'=>'Title and chapter required']); exit; }

$content = getJSON('content.json', []);

$item = [
    'id' => $id ?: uid('ct'),
    'chapterId' => $chId,
    'type' => $ctype,
    'title' => $title,
    'description' => $description,
    'video_url' => $videoUrl,
    'external_url' => $externalUrl,
    'file' => '',
    'updated' => date('Y-m-d H:i:s'),
];

if ($id) {
    foreach ($content as $c) if ($c['id']===$id) {
        $item['file'] = $c['file'] ?? '';
        $item['created'] = $c['created'] ?? date('Y-m-d H:i:s');
        break;
    }
} else {
    $item['created'] = date('Y-m-d H:i:s');
}

// Handle file upload
$fileField = $ctype === 'dpp' ? 'dpp_file' : 'note_file';
if (!empty($_FILES[$fileField]['tmp_name'])) {
    $dest = $ctype === 'dpp' ? UPLOAD_DPP : UPLOAD_NOTES;
    $up = handleUpload($fileField, $dest, ['pdf','doc','docx','zip','jpg','jpeg','png','ppt','pptx'], 50);
    if ($up['success']) {
        if (!empty($item['file']) && file_exists($dest.'/'.$item['file'])) @unlink($dest.'/'.$item['file']);
        $item['file'] = $up['filename'];
    } else {
        echo json_encode(['success'=>false,'error'=>'File: '.$up['error']]); exit;
    }
}

// Save
$found = false;
foreach ($content as $i=>$c) if ($c['id']===$item['id']) { $content[$i]=$item; $found=true; break; }
if (!$found) $content[] = $item;

if (saveJSON('content.json', $content)) {
    echo json_encode(['success'=>true, 'item'=>$item, 'message'=>'Content saved successfully']);
} else {
    echo json_encode(['success'=>false, 'error'=>'Could not save']);
}
