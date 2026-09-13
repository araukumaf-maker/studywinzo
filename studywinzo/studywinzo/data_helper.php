<?php
/**
 * Shared data helpers — routes JSON filenames to Supabase.
 * Used by both frontend (index.php) and admin.
 */
require_once __DIR__.'/data_store.php';

function getJSON($file, $default = []) {
    static $cache = [];
    $key = pathinfo($file, PATHINFO_FILENAME);
    if (isset($cache[$key])) return $cache[$key];

    switch ($key) {
        case 'institutions': $d = getTable('institutions'); break;
        case 'batches':      $d = getTable('batches'); break;
        case 'subjects':     $d = getTable('subjects'); break;
        case 'chapters':     $d = getTable('chapters'); break;
        case 'content':      $d = getTable('content', 'created_at'); break;
        default:
            $d = cloudRead($file);
            if ($d === null) $d = $default;
            return $cache[$key] = $d;
    }

    $d = $d ?: [];
    $normalized = [];
    foreach ($d as $row) {
        $item = $row;
        if (isset($row['sort_order'])) $item['order'] = $row['sort_order'];
        if (isset($row['institution_id'])) $item['institutionId'] = $row['institution_id'];
        if (isset($row['batch_id']))     $item['batchId']       = $row['batch_id'];
        if (isset($row['subject_id']))   $item['subjectId']     = $row['subject_id'];
        if (isset($row['chapter_id']))   $item['chapterId']     = $row['chapter_id'];
        if (isset($row['created_at']) && !isset($item['created'])) $item['created'] = $row['created_at'];
        if (isset($row['updated_at']) && !isset($item['updated'])) $item['updated'] = $row['updated_at'];
        $normalized[] = $item;
    }
    return $cache[$key] = $normalized;
}

function saveJSON($file, $data) {
    $key = pathinfo($file, PATHINFO_FILENAME);
    switch ($key) {
        case 'institutions':
            foreach ($data as $i) upsertRow('institutions', ['id'=>$i['id'],'name'=>$i['name']??'','logo'=>$i['logo']??'','color'=>$i['color']??'','sort_order'=>(int)($i['order']??0)]);
            return true;
        case 'batches':
            foreach ($data as $i) upsertRow('batches', ['id'=>$i['id'],'institution_id'=>$i['institutionId']??null,'name'=>$i['name']??'','subject'=>$i['subject']??'','color'=>$i['color']??'','image'=>$i['image']??'','sort_order'=>(int)($i['order']??0)]);
            return true;
        case 'subjects':
            foreach ($data as $i) upsertRow('subjects', ['id'=>$i['id'],'batch_id'=>$i['batchId']??null,'name'=>$i['name']??'','sort_order'=>(int)($i['order']??0)]);
            return true;
        case 'chapters':
            foreach ($data as $i) upsertRow('chapters', ['id'=>$i['id'],'subject_id'=>$i['subjectId']??null,'name'=>$i['name']??'','sort_order'=>(int)($i['order']??0)]);
            return true;
        case 'content':
            foreach ($data as $i) upsertRow('content', ['id'=>$i['id'],'chapter_id'=>$i['chapterId']??null,'type'=>$i['type']??'note','title'=>$i['title']??'','description'=>$i['description']??'','file'=>$i['file']??'','video_url'=>$i['video_url']??'','external_url'=>$i['external_url']??'','thumbnail'=>$i['thumbnail']??'']);
            return true;
        default:
            return cloudWrite($file, $data);
    }
}

/** Returns full URL for any media filename (handles legacy + Supabase URLs). */
function mediaUrl($filename, $folder = 'notes') {
    if (empty($filename)) return '';
    if (preg_match('#^https?://#i', $filename)) return $filename;
    $cfg = supabase_config();
    return "{$cfg['url']}/storage/v1/object/public/studywinzo/{$folder}/{$filename}";
}
