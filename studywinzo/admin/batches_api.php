<?php
ini_set('display_errors', 0);
require_once 'config.php';
require_once __DIR__.'/../cloud_upload.php';
requireAdmin();
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function jsonOut($a){ echo json_encode($a); exit; }

// GET batches by institution
if ($action === 'get_batches') {
    $instId = $_GET['institution'] ?? '';
    $batches = getJSON('batches.json', []);
    $subjects = getJSON('subjects.json', []);
    $chapters = getJSON('chapters.json', []);
    
    if ($instId) $batches = array_values(array_filter($batches, fn($b)=>($b['institutionId']??'')===$instId));
    
    // Add chapter counts
    foreach ($batches as &$b) {
        $subIds = [];
        foreach ($subjects as $s) if ($s['batchId']===$b['id']) $subIds[] = $s['id'];
        $b['_chapters'] = array_values(array_filter($chapters, fn($c)=>in_array($c['subjectId'], $subIds)));
        $b['_chapterCount'] = count($b['_chapters']);
    }
    unset($b);
    
    jsonOut(['success'=>true, 'data'=>$batches]);
}

// GET chapters by batch
if ($action === 'get_chapters') {
    $bid = $_GET['batch'] ?? '';
    $subjects = getJSON('subjects.json', []);
    $chapters = getJSON('chapters.json', []);
    
    $subIds = [];
    foreach ($subjects as $s) if ($s['batchId']===$bid) $subIds[] = $s['id'];
    $ch = array_values(array_filter($chapters, fn($c)=>in_array($c['subjectId'], $subIds)));
    jsonOut(['success'=>true, 'data'=>$ch]);
}

// SAVE batch (add or update)
if ($action === 'save_batch') {
    $id = trim($_POST['id'] ?? '');
    $instId = trim($_POST['institution_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $tag = trim($_POST['tag'] ?? '');
    $color = trim($_POST['color'] ?? '#2cee82');
    
    if (!$name || !$instId) jsonOut(['success'=>false, 'error'=>'Name and institution required']);
    
    $batches = getJSON('batches.json', []);
    $subjects = getJSON('subjects.json', []);
    
    $img = $_POST['existing_image'] ?? '';
    if (!empty($_FILES['image']['tmp_name'])) {
        if (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            jsonOut(['success'=>false, 'error'=>'Thumbnail upload failed']);
        }
        $tmp = $_FILES['image']['tmp_name'];
        $mime = function_exists('mime_content_type') ? (mime_content_type($tmp) ?: '') : '';
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if ($mime && !in_array($mime, $allowedMimes, true)) {
            jsonOut(['success'=>false, 'error'=>'Thumbnail must be JPG, PNG, WEBP or GIF']);
        }
        if ((int)($_FILES['image']['size'] ?? 0) > 5 * 1024 * 1024) {
            jsonOut(['success'=>false, 'error'=>'Thumbnail must be 5MB or smaller']);
        }
        $cloud = cloudUpload($tmp, 'image', 'studywinzo/batches');
        if ($cloud['success']) $img = $cloud['url'];
        else jsonOut(['success'=>false, 'error'=>'Image: '.$cloud['error']]);
    }
    
    if ($id) {
        foreach ($batches as $i=>$b) if ($b['id']===$id) {
            $batches[$i]['name'] = $name;
            $batches[$i]['subject'] = $tag;
            $batches[$i]['color'] = $color;
            if ($img) $batches[$i]['image'] = $img;
            break;
        }
        $msg = 'Batch updated';
    } else {
        $id = uid('b');
        $batches[] = [
            'id'=>$id, 'institutionId'=>$instId, 'name'=>$name, 'subject'=>$tag,
            'color'=>$color, 'image'=>$img, 'created'=>date('Y-m-d H:i:s')
        ];
        // Auto subject
        $subjects[] = [
            'id'=>uid('s'), 'batchId'=>$id, 'name'=>$tag ?: $name,
            'order'=>0, 'created'=>date('Y-m-d H:i:s')
        ];
        saveJSON('subjects.json', $subjects);
        $msg = 'Batch added';
    }
    saveJSON('batches.json', $batches);
    jsonOut(['success'=>true, 'id'=>$id, 'message'=>$msg]);
}

// DELETE batch
if ($action === 'delete_batch') {
    $id = trim($_POST['id'] ?? '');
    $batches = getJSON('batches.json', []);
    $subjects = getJSON('subjects.json', []);
    $chapters = getJSON('chapters.json', []);
    
    foreach ($batches as $b) if ($b['id']===$id && !empty($b['image'])) @unlink(UPLOAD_BATCHES.'/'.$b['image']);
    $batches = array_values(array_filter($batches, fn($b)=>$b['id']!==$id));
    $subjects = array_values(array_filter($subjects, fn($s)=>$s['batchId']!==$id));
    saveJSON('batches.json', $batches);
    saveJSON('subjects.json', $subjects);
    jsonOut(['success'=>true]);
}

// ADD chapter
if ($action === 'add_chapter') {
    $bid = trim($_POST['batch_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    if (!$bid || !$name) jsonOut(['success'=>false, 'error'=>'Missing fields']);
    
    $subjects = getJSON('subjects.json', []);
    $chapters = getJSON('chapters.json', []);
    
    $subId = '';
    foreach ($subjects as $s) if ($s['batchId']===$bid) { $subId = $s['id']; break; }
    if (!$subId) {
        $subId = uid('s');
        $subjects[] = ['id'=>$subId, 'batchId'=>$bid, 'name'=>'General', 'order'=>0, 'created'=>date('Y-m-d H:i:s')];
        saveJSON('subjects.json', $subjects);
    }
    
    // Check duplicate
    foreach ($chapters as $c) if ($c['subjectId']===$subId && strtolower($c['name'])===strtolower($name)) {
        jsonOut(['success'=>false, 'error'=>'Chapter already exists']);
    }
    
    $new = ['id'=>uid('c'), 'subjectId'=>$subId, 'name'=>$name, 'order'=>count($chapters), 'created'=>date('Y-m-d H:i:s')];
    $chapters[] = $new;
    saveJSON('chapters.json', $chapters);
    jsonOut(['success'=>true, 'item'=>$new]);
}

// DELETE chapter
if ($action === 'delete_chapter') {
    $id = trim($_POST['id'] ?? '');
    $chapters = getJSON('chapters.json', []);
    $chapters = array_values(array_filter($chapters, fn($c)=>$c['id']!==$id));
    saveJSON('chapters.json', $chapters);
    jsonOut(['success'=>true]);
}

// RENAME chapter
if ($action === 'rename_chapter') {
    $id = trim($_POST['id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    if (!$name) jsonOut(['success'=>false, 'error'=>'Name required']);
    $chapters = getJSON('chapters.json', []);
    foreach ($chapters as $i=>$c) if ($c['id']===$id) { $chapters[$i]['name']=$name; break; }
    saveJSON('chapters.json', $chapters);
    jsonOut(['success'=>true]);
}

// Reorder chapters
if ($action === 'reorder_chapters') {
    $order = $_POST['order'] ?? '';
    $ids = explode(',', $order);
    $chapters = getJSON('chapters.json', []);
    foreach ($chapters as $i=>$c) {
        $pos = array_search($c['id'], $ids);
        if ($pos !== false) $chapters[$i]['order'] = $pos;
    }
    usort($chapters, fn($a,$b)=>($a['order']??0)-($b['order']??0));
    saveJSON('chapters.json', $chapters);
    jsonOut(['success'=>true]);
}

jsonOut(['success'=>false, 'error'=>'Unknown action']);
