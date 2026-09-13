<?php
/**
 * Video Stream Handler with Range Request Support
 * Fixes: seeking/timeline jumping to start on PHP built-in server
 */
ini_set('display_errors', 0);
error_reporting(0);

$file = $_GET['file'] ?? '';
$file = basename($file); // prevent directory traversal

if (empty($file)) { http_response_code(400); exit; }

$path = __DIR__.'/admin/uploads/videos/'.$file;
if (!file_exists($path) || !is_file($path)) { http_response_code(404); exit; }

$size = filesize($path);
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$mime = 'video/mp4';
if ($ext === 'webm') $mime = 'video/webm';
elseif ($ext === 'ogg' || $ext === 'ogv') $mime = 'video/ogg';
elseif ($ext === 'mov') $mime = 'video/quicktime';
elseif ($ext === 'm4v') $mime = 'video/x-m4v';
elseif ($ext === 'mkv') $mime = 'video/x-matroska';

// ---------- Handle Range Requests ----------
$start = 0;
$end = $size - 1;
$rangeHeader = $_SERVER['HTTP_RANGE'] ?? '';

if (!empty($rangeHeader) && preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $m)) {
    if ($m[1] !== '') $start = (int)$m[1];
    if ($m[2] !== '') $end = (int)$m[2];

    // Validate
    if ($start > $end || $start >= $size) {
        http_response_code(416);
        header('Content-Range: bytes */'.$size);
        exit;
    }
    if ($end >= $size) $end = $size - 1;

    http_response_code(206);
    header('Content-Range: bytes '.$start.'-'.$end.'/'.$size);
}

// ---------- Send Headers ----------
header('Content-Type: '.$mime);
header('Accept-Ranges: bytes');
header('Content-Length: '.($end - $start + 1));
header('Content-Disposition: inline; filename="'.basename($file).'"');
header('Cache-Control: public, max-age=3600');
header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($path)).' GMT');

// ---------- Stream the file ----------
while (ob_get_level()) ob_end_clean();

$fp = fopen($path, 'rb');
if (!$fp) { http_response_code(500); exit; }

fseek($fp, $start);

$buffer = 1024 * 32; // 32KB chunks
$bytesLeft = $end - $start + 1;

while ($bytesLeft > 0 && !feof($fp)) {
    $read = ($bytesLeft > $buffer) ? $buffer : $bytesLeft;
    echo fread($fp, $read);
    $bytesLeft -= $read;
    flush();
}

fclose($fp);
exit;
