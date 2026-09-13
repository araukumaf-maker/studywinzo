<?php
/**
 * Backward-compatible upload wrapper.
 *
 * Older admin endpoints call this function as "cloudUpload". Keep that API,
 * but use the same Supabase Storage implementation as the rest of the app.
 */
function cloudUpload($filePath, $fileType = 'image', $folder = 'studywinzo') {
    require_once __DIR__.'/data_store.php';
    if (!is_readable($filePath)) return ['success'=>false,'error'=>'File not found'];

    $folder = trim($folder, '/');
    $bucket = supabase_config()['bucket'];
    if (strpos($folder, $bucket.'/') === 0) {
        $folder = substr($folder, strlen($bucket) + 1);
    }

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $name = 'f_'.time().'_'.bin2hex(random_bytes(4)).($ext ? '.'.$ext : '');
    $remotePath = ($folder ? $folder.'/' : '').$name;
    $mime = function_exists('mime_content_type') ? (mime_content_type($filePath) ?: 'application/octet-stream') : 'application/octet-stream';
    $url = supabaseUpload($filePath, $remotePath, $mime);
    if (!$url) return ['success'=>false,'error'=>'Supabase Storage upload failed'];

    return ['success'=>true,'url'=>$url,'filename'=>$url,'public_id'=>$remotePath];
}