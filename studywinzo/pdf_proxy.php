<?php
/**
 * Same-origin PDF proxy for Supabase public storage.
 * Keeps PDF.js working on mobile browsers when storage CORS/range handling is strict.
 */
ini_set('display_errors', '0');
error_reporting(0);
require_once __DIR__ . '/data_store.php';

$url = trim((string)($_GET['url'] ?? ''));
$cfg = supabase_config();
$source = parse_url($url);
$storage = parse_url($cfg['url']);
$publicPath = $source['path'] ?? '';
$allowedPrefix = '/storage/v1/object/public/';

if (!$url || !$source || !$storage || ($source['scheme'] ?? '') !== 'https' || ($storage['scheme'] ?? '') !== 'https' ||
    ($source['host'] ?? '') !== ($storage['host'] ?? '') || strpos($publicPath, $allowedPrefix) !== 0) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Invalid PDF source');
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 90,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_HTTPHEADER => ['Accept: application/pdf,application/octet-stream;q=0.9'],
    CURLOPT_USERAGENT => 'StudyWinzo PDF Viewer',
]);
$body = curl_exec($ch);
$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$error = curl_error($ch);
curl_close($ch);

if ($body === false || $status < 200 || $status >= 300) {
    http_response_code($status >= 400 ? $status : 502);
    header('Content-Type: text/plain; charset=utf-8');
    exit($error ?: 'PDF storage request failed');
}

$download = ($_GET['download'] ?? '') === '1';
header('Content-Type: ' . ($contentType ?: 'application/pdf'));
header('Content-Length: ' . strlen($body));
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="studywinzo-document.pdf"');
echo $body;
