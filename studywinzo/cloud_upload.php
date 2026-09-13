<?php
function cloudUpload($filePath, $fileType = 'image', $folder = 'studywinzo') {
    $cloudName = 'tfw0teo9';
    $apiKey    = '519537719448766';
    $apiSecret = 'E2hPyLeLJEghShQz_v_NmhO_7Wc';

    if (!file_exists($filePath)) return ['success'=>false,'error'=>'File not found'];

    $timestamp = time();

    // Params to sign — alphabetical order, exclude file, api_key, signature
    $params = [
        'folder'    => $folder,
        'timestamp' => $timestamp,
    ];
    ksort($params);

    $pairs = [];
    foreach ($params as $k => $v) $pairs[] = "$k=$v";
    $paramsToSign = implode('&', $pairs);
    $signature = sha1($paramsToSign . $apiSecret);

    $resourceType = 'image';
    if ($fileType === 'video') $resourceType = 'video';
    elseif ($fileType === 'pdf' || $fileType === 'raw') $resourceType = 'raw';

    $postFields = $params;
    $postFields['file']      = new CURLFile($filePath);
    $postFields['api_key']   = $apiKey;
    $postFields['signature'] = $signature;

    $url = "https://api.cloudinary.com/v1_1/{$cloudName}/{$resourceType}/upload";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $json = json_decode($response, true);
    if ($httpCode === 200 && isset($json['secure_url'])) {
        return [
            'success' => true,
            'url' => $json['secure_url'],
            'public_id' => $json['public_id'],
            'filename' => $json['secure_url'],
        ];
    }
    return ['success'=>false, 'error' => $json['error']['message'] ?? "HTTP $httpCode"];
}
