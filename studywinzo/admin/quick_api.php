<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once 'config.php';
require_once __DIR__.'/../cloud_upload.php';
requireAdmin();
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function jsonOut($a){ echo json_encode($a); exit; }

// ============ BATCHES ============
if ($action === 'batches') {
    $instId = $_GET['institution'] ?? '';
    $all = getJSON('batches.json', []);
    if ($instId) {
        $all = array_values(array_filter($all, fn($b)=>($b['institutionId']??'')===$instId));
    }
    jsonOut(['success'=>true, 'data'=>$all]);
}

// ============ SUBJECTS ============
if ($action === 'subjects') {
    $bid = $_GET['batch'] ?? '';
    $subs = array_values(array_filter(getJSON('subjects.json', []), fn($s)=>$s['batchId']===$bid));
    jsonOut(['success'=>true, 'data'=>$subs]);
}

// ============ CHAPTERS ============
if ($action === 'chapters') {
    $sid = $_GET['subject'] ?? '';
    $chs = array_values(array_filter(getJSON('chapters.json', []), fn($c)=>$c['subjectId']===$sid));
    jsonOut(['success'=>true, 'data'=>$chs]);
}

// ============ ADD INSTITUTION ============
if ($action === 'add_institution') {
    $name = trim($_POST['name'] ?? '');
    if (!$name) jsonOut(['success'=>false,'error'=>'Name required']);
    $insts = getJSON('institutions.json', []);
    $new = ['id'=>uid('inst'),'name'=>$name,'logo'=>'','color'=>'#2cee82','order'=>count($insts)+1,'created'=>date('Y-m-d H:i:s')];
    $insts[] = $new;
    saveJSON('institutions.json', $insts);
    jsonOut(['success'=>true, 'item'=>$new]);
}

// ============ ADD BATCH ============
if ($action === 'add_batch') {
    $name = trim($_POST['name'] ?? '');
    $instId = trim($_POST['institution_id'] ?? '');
    if (!$name) jsonOut(['success'=>false,'error'=>'Name required']);
    $batches = getJSON('batches.json', []);
    $new = ['id'=>uid('b'),'institutionId'=>$instId,'name'=>$name,'subject'=>'','color'=>'#2cee82','image'=>'','created'=>date('Y-m-d H:i:s')];
    $batches[] = $new;
    saveJSON('batches.json', $batches);
    jsonOut(['success'=>true, 'item'=>$new]);
}

// ============ ADD SUBJECT ============
if ($action === 'add_subject') {
    $name = trim($_POST['name'] ?? '');
    $bid = trim($_POST['batch_id'] ?? '');
    if (!$name || !$bid) jsonOut(['success'=>false,'error'=>'Name & batch required']);
    $subs = getJSON('subjects.json', []);
    $new = ['id'=>uid('s'),'batchId'=>$bid,'name'=>$name,'order'=>0,'created'=>date('Y-m-d H:i:s')];
    $subs[] = $new;
    saveJSON('subjects.json', $subs);
    jsonOut(['success'=>true, 'item'=>$new]);
}

// ============ ADD CHAPTER ============
if ($action === 'add_chapter') {
    $name = trim($_POST['name'] ?? '');
    $sid = trim($_POST['subject_id'] ?? '');
    if (!$name || !$sid) jsonOut(['success'=>false,'error'=>'Name & subject required']);
    $chs = getJSON('chapters.json', []);
    $new = ['id'=>uid('c'),'subjectId'=>$sid,'name'=>$name,'order'=>0,'created'=>date('Y-m-d H:i:s')];
    $chs[] = $new;
    saveJSON('chapters.json', $chs);
    jsonOut(['success'=>true, 'item'=>$new]);
}

// ============ BULK SAVE CONTENT ============
if ($action === 'bulk_save') {
    $chId = trim($_POST['chapter_id'] ?? '');
    $ctype = trim($_POST['ctype'] ?? 'note');
    $titleBase = trim($_POST['title'] ?? '');
    $videoUrl = trim($_POST['video_url'] ?? '');
    $external = trim($_POST['external_url'] ?? '');

    if (!in_array($ctype, ['note','dpp','video','link'])) {
        jsonOut(['success'=>false, 'error'=>'Invalid content type: '.$ctype]);
    }
    if (!$chId) jsonOut(['success'=>false,'error'=>'Chapter required']);

    $content = getJSON('content.json', []);
    $added = [];
    $errors = [];

    // Handle custom thumbnail upload (for videos)
    $thumbFile = '';
    if (!empty($_FILES['thumbnail']['tmp_name']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $cloud = cloudUpload($_FILES['thumbnail']['tmp_name'], 'image', 'studywinzo/thumbnails');
        if ($cloud['success']) $thumbFile = $cloud['url'];
        else $errors[] = 'Thumbnail: ' . $cloud['error'];
    }

    // ========== VIDEO ==========
    if ($ctype === 'video') {
        if (!$videoUrl) jsonOut(['success'=>false, 'error'=>'Video URL required']);
        if (strpos($videoUrl, 'watch?v=') !== false) {
            $videoUrl = 'https://www.youtube.com/embed/' . explode('&', explode('watch?v=', $videoUrl)[1])[0];
        } elseif (strpos($videoUrl, 'youtu.be/') !== false) {
            $videoUrl = 'https://www.youtube.com/embed/' . explode('?', explode('youtu.be/', $videoUrl)[1])[0];
        }
        $content[] = [
            'id' => uid('ct'),
            'chapterId' => $chId,
            'type' => 'video',
            'title' => $titleBase ?: 'Video Lecture',
            'description' => '',
            'file' => '',
            'video_url' => $videoUrl,
            'external_url' => '',
            'thumbnail' => $thumbFile,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s')
        ];
        $added[] = $titleBase ?: 'Video';
    }
    // ========== LINK ==========
    elseif ($ctype === 'link') {
        if (!$external) jsonOut(['success'=>false, 'error'=>'External URL required']);
        $content[] = [
            'id' => uid('ct'),
            'chapterId' => $chId,
            'type' => 'link',
            'title' => $titleBase ?: 'Link',
            'description' => '',
            'file' => '',
            'video_url' => '',
            'external_url' => $external,
            'thumbnail' => '',
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s')
        ];
        $added[] = $titleBase ?: 'Link';
    }
    // ========== NOTE / DPP ==========
    else {
        if (!empty($_FILES['files']) && is_array($_FILES['files']['name']) && count($_FILES['files']['name']) > 0 && $_FILES['files']['name'][0] !== '') {
            $count = count($_FILES['files']['name']);
            $dest = ($ctype === 'dpp') ? UPLOAD_DPP : UPLOAD_NOTES;
            
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) { $errors[] = "Upload error on file $i"; continue; }
                $tmp = $_FILES['files']['tmp_name'][$i];
                $name = $_FILES['files']['name'][$i];
                $size = $_FILES['files']['size'][$i];
                if ($size > 50*1024*1024) { $errors[] = "$name too large"; continue; }
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowed = ['pdf','doc','docx','ppt','pptx','zip','jpg','jpeg','png'];
                if (!in_array($ext, $allowed)) { $errors[] = "$name invalid format"; continue; }
                
                $finalName = 'f_'.time().'_'.$i.'_'.bin2hex(random_bytes(4)).'.'.$ext;
                if (!move_uploaded_file($tmp, $dest.'/'.$finalName)) { $errors[] = "$name move failed"; continue; }
                
                $title = $titleBase ? ($count > 1 ? $titleBase.' - '.($i+1) : $titleBase) : pathinfo($name, PATHINFO_FILENAME);
                $content[] = [
                    'id' => uid('ct'),
                    'chapterId' => $chId,
                    'type' => $ctype,
                    'title' => $title,
                    'description' => '',
                    'file' => $finalName,
                    'video_url' => '',
                    'external_url' => '',
                    'thumbnail' => '',
                    'created' => date('Y-m-d H:i:s'),
                    'updated' => date('Y-m-d H:i:s')
                ];
                $added[] = $title;
            }
        } else {
            jsonOut(['success'=>false, 'error'=>'No files uploaded']);
        }
    }

    saveJSON('content.json', $content);
    jsonOut(['success' => true, 'added' => $added, 'count' => count($added), 'ctype' => $ctype, 'errors' => $errors]);
}

// ============ ALL CONTENT ============
if ($action === 'all_content') {
    $content = getJSON('content.json', []);
    $chapters = getJSON('chapters.json', []);
    $subjects = getJSON('subjects.json', []);
    $batches = getJSON('batches.json', []);
    
    $chMap = []; foreach ($chapters as $c) $chMap[$c['id']] = $c;
    $subMap = []; foreach ($subjects as $s) $subMap[$s['id']] = $s;
    $batchMap = []; foreach ($batches as $b) $batchMap[$b['id']] = $b;
    
    $out = [];
    foreach ($content as $it) {
        $ch = $chMap[$it['chapterId']] ?? null;
        $sub = $ch ? ($subMap[$ch['subjectId']] ?? null) : null;
        $bat = $sub ? ($batchMap[$sub['batchId']] ?? null) : null;
        $out[] = [
            'id' => $it['id'],
            'type' => $it['type'],
            'title' => $it['title'],
            'file' => $it['file'] ?? '',
            'video_url' => $it['video_url'] ?? '',
            'external_url' => $it['external_url'] ?? '',
            'thumbnail' => $it['thumbnail'] ?? '',
            'chapterId' => $it['chapterId'],
            'chapterName' => $ch ? $ch['name'] : 'Unknown',
            'subjectName' => $sub ? $sub['name'] : '?',
            'batchName' => $bat ? $bat['name'] : '?',
            'created' => $it['created'] ?? ''
        ];
    }
    usort($out, function($a, $b){ return strcmp($b['created'], $a['created']); });
    jsonOut(['success' => true, 'data' => $out]);
}

// ============ GET SINGLE CONTENT ============
if ($action === 'get_content') {
    $id = trim($_GET['id'] ?? '');
    if (!$id) jsonOut(['success'=>false, 'error'=>'Missing id']);
    $content = getJSON('content.json', []);
    foreach ($content as $c) if ($c['id'] === $id) { jsonOut(['success'=>true, 'item'=>$c]); }
    jsonOut(['success'=>false, 'error'=>'Not found']);
}

// ============ UPDATE CONTENT ============
if ($action === 'update_content') {
    $id = trim($_POST['id'] ?? '');
    if (!$id) jsonOut(['success'=>false, 'error'=>'Missing id']);
    $content = getJSON('content.json', []);
    $found = false;
    foreach ($content as $i => $c) {
        if ($c['id'] === $id) {
            if (isset($_POST['title'])) $content[$i]['title'] = trim($_POST['title']);
            if (isset($_POST['video_url'])) $content[$i]['video_url'] = trim($_POST['video_url']);
            if (isset($_POST['external_url'])) $content[$i]['external_url'] = trim($_POST['external_url']);
            if (!empty($_FILES['thumbnail']['tmp_name'])) {
                if (!empty($content[$i]['thumbnail']) && file_exists(__DIR__.'/uploads/thumbnails/'.$content[$i]['thumbnail'])) {
                    @unlink(__DIR__.'/uploads/thumbnails/'.$content[$i]['thumbnail']);
                }
                $up = handleUpload('thumbnail', UPLOAD_THUMBS, ['jpg','jpeg','png','webp'], 3);
                if ($up['success']) $content[$i]['thumbnail'] = $up['filename'];
            }
            if (!empty($_FILES['file']['tmp_name'])) {
                $ctype = $content[$i]['type'] ?? 'note';
                $dest = $ctype === 'dpp' ? UPLOAD_DPP : UPLOAD_NOTES;
                if (!empty($content[$i]['file']) && file_exists($dest.'/'.$content[$i]['file'])) {
                    @unlink($dest.'/'.$content[$i]['file']);
                }
                $up = handleUpload('file', $dest, ['pdf','doc','docx','ppt','pptx','zip','jpg','jpeg','png'], 50);
                if ($up['success']) $content[$i]['file'] = $up['filename'];
            }
            $content[$i]['updated'] = date('Y-m-d H:i:s');
            $found = true;
            break;
        }
    }
    if (!$found) jsonOut(['success'=>false, 'error'=>'Content not found']);
    saveJSON('content.json', $content);
    jsonOut(['success' => true]);
}

// ============ REMOVE THUMBNAIL ============
if ($action === 'remove_thumbnail') {
    $id = trim($_POST['id'] ?? '');
    if (!$id) jsonOut(['success'=>false, 'error'=>'Missing id']);
    $content = getJSON('content.json', []);
    foreach ($content as $i => $c) {
        if ($c['id'] === $id) {
            if (!empty($c['thumbnail']) && file_exists(__DIR__.'/uploads/thumbnails/'.$c['thumbnail'])) {
                @unlink(__DIR__.'/uploads/thumbnails/'.$c['thumbnail']);
            }
            $content[$i]['thumbnail'] = '';
            break;
        }
    }
    saveJSON('content.json', $content);
    jsonOut(['success' => true]);
}

// ============ DELETE CONTENT ITEM ============
if ($action === 'delete_content_item') {
    $id = trim($_POST['id'] ?? '');
    if (!$id) jsonOut(['success'=>false, 'error'=>'Missing id']);
    $content = getJSON('content.json', []);
    foreach ($content as $c) if ($c['id'] === $id) {
        if (!empty($c['file'])) foreach ([UPLOAD_NOTES, UPLOAD_DPP] as $d) if (file_exists($d.'/'.$c['file'])) @unlink($d.'/'.$c['file']);
        if (!empty($c['thumbnail']) && file_exists(__DIR__.'/uploads/thumbnails/'.$c['thumbnail'])) @unlink(__DIR__.'/uploads/thumbnails/'.$c['thumbnail']);
        break;
    }
    $content = array_values(array_filter($content, function($c){ return $c['id'] !== $id; }));
    saveJSON('content.json', $content);
    jsonOut(['success' => true]);
}

// ============ DELETE CHAPTER FULL ============
if ($action === 'delete_chapter_full') {
    $id = trim($_POST['id'] ?? '');
    if (!$id) jsonOut(['success'=>false, 'error'=>'Missing id']);
    $content = getJSON('content.json', []);
    foreach ($content as $c) {
        if ($c['chapterId'] === $id) {
            if (!empty($c['file'])) foreach ([UPLOAD_NOTES, UPLOAD_DPP] as $d) if (file_exists($d.'/'.$c['file'])) @unlink($d.'/'.$c['file']);
            if (!empty($c['thumbnail']) && file_exists(__DIR__.'/uploads/thumbnails/'.$c['thumbnail'])) @unlink(__DIR__.'/uploads/thumbnails/'.$c['thumbnail']);
        }
    }
    $content = array_values(array_filter($content, function($c) use ($id){ return $c['chapterId'] !== $id; }));
    saveJSON('content.json', $content);
    $chapters = getJSON('chapters.json', []);
    $chapters = array_values(array_filter($chapters, function($c) use ($id){ return $c['id'] !== $id; }));
    saveJSON('chapters.json', $chapters);
    jsonOut(['success' => true]);
}

// ============ RECENT ============
if ($action === 'recent') {
    $content = getJSON('content.json', []);
    $chapters = getJSON('chapters.json', []);
    $subjects = getJSON('subjects.json', []);
    $batches = getJSON('batches.json', []);
    
    usort($content, function($a,$b){ return strcmp($b['created'] ?? '', $a['created'] ?? ''); });
    $recent = array_slice($content, 0, 6);
    
    $out = [];
    foreach ($recent as $c) {
        $ch = null; foreach ($chapters as $x) if ($x['id'] === $c['chapterId']) $ch = $x;
        $sub = null; if ($ch) foreach ($subjects as $x) if ($x['id'] === $ch['subjectId']) $sub = $x;
        $bat = null; if ($sub) foreach ($batches as $x) if ($x['id'] === $sub['batchId']) $bat = $x;
        $out[] = [
            'title' => $c['title'],
            'type' => $c['type'],
            'batch' => $bat['name'] ?? '?',
            'subject' => $sub['name'] ?? '?',
            'chapter' => $ch['name'] ?? '?',
            'created' => $c['created'] ?? ''
        ];
    }
    jsonOut(['success'=>true, 'data'=>$out]);
}

jsonOut(['success'=>false, 'error'=>'Unknown action']);
