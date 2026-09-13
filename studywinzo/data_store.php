<?php
/**
 * Data store — Supabase Relational v2
 */

function supabase_config() {
    return [
        'url'           => 'https://getbmiorthitpqemydlk.supabase.co',
        'secret'        => 'sb_publishable_lvKXKJPV1GRrEIVS0o0zYg_pc6wE9CF',
        'storage_token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImdldGJtaW9ydGhpdHBxZW15ZGxrIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODkyMzg4NDQsImV4cCI6MjEwNDgxNDg0NH0.AjQO0Mrq5tiuEOejh1c-WQMrAydxPy1CEcoNGJzUv38',
    ];
}

/** Core request handler */
function supabase_request($method, $path, $body = null, $extraHeaders = []) {
    $cfg = supabase_config();
    $ch = curl_init($cfg['url'] . '/rest/v1' . $path);
    $headers = array_merge([
        'apikey: '        . $cfg['secret'],
        'Authorization: Bearer ' . $cfg['secret'],
        'Content-Type: application/json',
        'Accept: application/json',
        'Prefer: return=representation',
    ], $extraHeaders);

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
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
    $bucket = 'studywinzo';
    $url = "{$cfg['url']}/storage/v1/object/{$bucket}/{$remoteFileName}";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => file_get_contents($localFilePath),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$cfg['storage_token']}",
            "Content-Type: {$contentType}",
            "x-upsert: true"
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
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

