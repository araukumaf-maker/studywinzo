<?php
/**
 * Data store — Supabase Relational v2
 */

function supabase_config() {
    $url = rtrim((string)(getenv('SUPABASE_URL') ?: ''), '/');
    $publicKey = (string)(getenv('SUPABASE_ANON_KEY') ?: getenv('SUPABASE_API_KEY') ?: getenv('SUPABASE_KEY') ?: '');
    $serverKey = (string)(getenv('SUPABASE_SERVICE_ROLE_KEY') ?: getenv('SUPABASE_API_KEY') ?: $publicKey);
    $storageKey = (string)(getenv('SUPABASE_STORAGE_TOKEN') ?: $serverKey);
    return [
        'url'           => $url,
        'public_key'    => $publicKey,
        'server_key'    => $serverKey,
        'storage_key'   => $storageKey,
        'bucket'        => (string)(getenv('SUPABASE_STORAGE_BUCKET') ?: 'studywinzo'),
    ];
}

/** Core request handler */
function supabase_request($method, $path, $body = null, $extraHeaders = []) {
    $cfg = supabase_config();
    if (!$cfg['url'] || !$cfg['server_key']) {
        error_log('Supabase is not configured. Set SUPABASE_URL and SUPABASE_KEY (or SUPABASE_ANON_KEY).');
        return false;
    }
    $ch = curl_init($cfg['url'] . '/rest/v1' . $path);
    $headers = array_merge([
        'apikey: '        . $cfg['server_key'],
        'Authorization: Bearer ' . $cfg['server_key'],
        'Content-Type: application/json',
        'Accept: application/json',
        'Prefer: return=representation',
    ], $extraHeaders);

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body));
    }
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    // curl_close() removed — no longer needed in PHP 8+

    if ($code >= 200 && $code < 300) {
        return $res === '' ? true : json_decode($res, true);
    }
    error_log("supabase $method $path: HTTP $code | $res | $err");
    return false;
}

// ---------------------------------------------------------
// GENERIC KV STORE
// ---------------------------------------------------------
function cloudWrite($file, $data) {
    $key = pathinfo($file, PATHINFO_FILENAME);
    $payload = [['key' => $key, 'data' => $data, 'updated_at' => gmdate('c')]];
    return supabase_request('POST', '/kv_store?on_conflict=key', $payload, ['Prefer: resolution=merge-duplicates,return=representation']) !== false;
}

function cloudRead($file) {
    $key = pathinfo($file, PATHINFO_FILENAME);
    $res = supabase_request('GET', '/kv_store?key=eq.' . rawurlencode($key) . '&select=data');
    return (is_array($res) && isset($res[0]['data'])) ? $res[0]['data'] : null;
}

// ---------------------------------------------------------
// RELATIONAL TABLE HELPERS
// ---------------------------------------------------------
function getTable($table, $orderBy = 'sort_order') {
    $res = supabase_request('GET', "/$table?select=*&order=$orderBy.asc");
    return $res ?: [];
}

function getFiltered($table, $column, $value, $orderBy = 'sort_order') {
    $res = supabase_request('GET', "/$table?$column=eq." . rawurlencode($value) . "&select=*&order=$orderBy.asc");
    return $res ?: [];
}

function insertRow($table, $data) {
    if (!isset($data['created_at'])) $data['created_at'] = gmdate('c');
    return supabase_request('POST', "/$table", [$data], ['Prefer: return=representation']);
}

function upsertRow($table, $data) {
    if (!isset($data['created_at'])) $data['created_at'] = gmdate('c');
    return supabase_request('POST', "/$table?on_conflict=id", [$data], ['Prefer: resolution=merge-duplicates,return=representation']);
}

function updateRow($table, $id, $data) {
    $data['updated_at'] = gmdate('c');
    return supabase_request('PATCH', "/$table?id=eq." . rawurlencode($id), $data, ['Prefer: return=representation']);
}

function deleteRow($table, $id) {
    return supabase_request('DELETE', "/$table?id=eq." . rawurlencode($id)) !== false;
}

// ---------------------------------------------------------
// SUPABASE STORAGE (File Uploads - PDFs, Images)
// ---------------------------------------------------------
function supabaseUpload($localFilePath, $remoteFileName, $contentType = 'application/octet-stream') {
    $cfg = supabase_config();
    if (!$cfg['url'] || !$cfg['server_key'] || !is_readable($localFilePath)) {
        error_log('Supabase Storage is not configured or upload file is unreadable.');
        return false;
    }
    $bucket = $cfg['bucket'];
    $url = "{$cfg['url']}/storage/v1/object/{$bucket}/{$remoteFileName}";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => file_get_contents($localFilePath),
        CURLOPT_HTTPHEADER => [
            "apikey: {$cfg['storage_key']}",
            "Authorization: Bearer {$cfg['storage_key']}",
            "Content-Type: {$contentType}",
            "x-upsert: true"
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($code >= 200 && $code < 300) {
        // Return the public URL
        return "{$cfg['url']}/storage/v1/object/public/{$bucket}/{$remoteFileName}";
    }
    error_log("supabaseUpload failed: HTTP $code | $res");
    return false;
}

