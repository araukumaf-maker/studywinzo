<?php
ini_set('display_errors', 0);
error_reporting(0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Expose-Headers: Content-Length, Content-Range');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$url = $_GET['url'] ?? '';
if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400); exit('Missing URL');
}

$baseUrl = dirname($url);
$isM3u8 = (strpos($url, '.m3u8') !== false);

if ($isM3u8) header('Content-Type: application/vnd.apple.mpegurl');
elseif (strpos($url, '.ts') !== false) header('Content-Type: video/mp2t');
else header('Content-Type: application/octet-stream');

$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$proxyBase = $scheme . '://' . $host . '/hls_proxy.php?url=';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_USERAGENT => 'Mozilla/5.0',
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) { http_response_code(502); exit('Proxy error: ' . $error); }

if ($isM3u8 && $httpCode === 200) {
    $lines = explode("\n", $response);
    $newLines = [];
    foreach ($lines as $line) {
        $t = trim($line);
        if (empty($t)) { $newLines[] = $line; continue; }
        if (strpos($t, '#') === 0) {
            if (preg_match('/URI="([^"]+)"/', $t, $m)) {
                $abs = makeAbs($m[1], $baseUrl, $url);
                $t = str_replace('URI="' . $m[1] . '"', 'URI="' . $proxyBase . urlencode($abs) . '"', $t);
            }
            $newLines[] = $t;
        } else {
            $abs = makeAbs($t, $baseUrl, $url);
            $newLines[] = $proxyBase . urlencode($abs);
        }
    }
    $response = implode("\n", $newLines);
}

http_response_code($httpCode);
echo $response;

function makeAbs($path, $baseUrl, $orig) {
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) return $path;
    if (strpos($path, '//') === 0) return 'https:' . $path;
    if (strpos($path, '/') === 0) { $p = parse_url($orig); return $p['scheme'].'://'.$p['host'].$path; }
    return rtrim($baseUrl, '/') . '/' . $path;
}
