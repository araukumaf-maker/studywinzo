<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Cloudinary data store
require_once __DIR__.'/../data_helper.php';

// Admin credentials
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123');

define('DATA_DIR', __DIR__.'/data');
define('UPLOAD_BATCHES', __DIR__.'/uploads/batches');
define('UPLOAD_LOGOS', __DIR__.'/uploads/logos');
define('UPLOAD_NOTES', __DIR__.'/uploads/notes');
define('UPLOAD_DPP', __DIR__.'/uploads/dpp');
define('UPLOAD_THUMBS', __DIR__.'/uploads/thumbnails');
define('UPLOAD_POPUPS', __DIR__.'/uploads/popups');
define('UPLOAD_VIDEOS', __DIR__.'/uploads/videos');

foreach ([DATA_DIR, UPLOAD_BATCHES, UPLOAD_LOGOS, UPLOAD_NOTES, UPLOAD_DPP, UPLOAD_THUMBS, UPLOAD_POPUPS, UPLOAD_VIDEOS] as $d) @mkdir($d, 0755, true);

function isAdmin(){ return !empty($_SESSION['sw_admin']); }
function requireAdmin(){ if (!isAdmin()) { header('Location: login.php'); exit; } }

// In-request cache
$GLOBALS['_data_cache'] = [];

function handleUpload($fileInput, $destDir, $allowed = ['jpg','jpeg','png','webp','svg','pdf'], $maxMB = 20) {
    if (empty($_FILES[$fileInput]['tmp_name']) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK)
        return ['success'=>false, 'error'=>'No file uploaded'];
        
    $f = $_FILES[$fileInput];
    if ($f['size'] > $maxMB*1024*1024) return ['success'=>false, 'error'=>"Max {$maxMB}MB"];
    
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return ['success'=>false, 'error'=>'Invalid format'];
    
    $name = 'f_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
    
    // Determine folder based on destDir
    $folder = 'misc';
    if (strpos($destDir, 'notes') !== false) $folder = 'notes';
    elseif (strpos($destDir, 'dpp') !== false) $folder = 'dpp';
    elseif (strpos($destDir, 'logos') !== false) $folder = 'logos';
    elseif (strpos($destDir, 'thumbnails') !== false) $folder = 'thumbnails';
    elseif (strpos($destDir, 'batches') !== false) $folder = 'batches';
    elseif (strpos($destDir, 'videos') !== false) $folder = 'videos';
    elseif (strpos($destDir, 'popups') !== false) $folder = 'popups';

    $remotePath = $folder . '/' . $name;
    $mime = mime_content_type($f['tmp_name']) ?: 'application/octet-stream';
    
    // Upload to Supabase Storage
    $url = supabaseUpload($f['tmp_name'], $remotePath, $mime);
    
    if ($url) {
        // Also save local copy as backup (best effort)
        @move_uploaded_file($f['tmp_name'], $destDir.'/'.$name);
        return ['success'=>true, 'filename'=>$name, 'url'=>$url, 'cloud_url'=>$url];
    }
    
    return ['success'=>false, 'error'=>'Upload failed. Check Supabase bucket permissions.'];
}

function uid($prefix='id'){ return $prefix.'_'.bin2hex(random_bytes(5)); }
