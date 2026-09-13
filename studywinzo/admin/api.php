<?php
require_once 'config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? '';
    $base = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on'?'https':'http').'://'.$_SERVER['HTTP_HOST'];

if ($action === 'batches' || $action === 'all') {
    $batches = getJSON('batches.json', []);
    $settings = getJSON('settings.json', ['logo'=>'', 'institution_name'=>'StudyWinzo']);
    foreach ($batches as &$b) {
        $b['image_url'] = !empty($b['image']) ? mediaUrl($b['image'], 'batches') : '';
    }
    unset($b);
    $settings['logo_url'] = !empty($settings['logo']) ? mediaUrl($settings['logo'], 'logos') : '';
    echo json_encode(['success'=>true, 'batches'=>$batches, 'settings'=>$settings]);
    exit;
}
if ($action === 'settings') {
    $settings = getJSON('settings.json', ['logo'=>'', 'institution_name'=>'StudyWinzo']);
    $settings['logo_url'] = !empty($settings['logo']) ? mediaUrl($settings['logo'], 'logos') : '';
    echo json_encode(['success'=>true, 'data'=>$settings]);
    exit;
}

if ($action === 'popup') {
    $popup = getJSON('popup.json', ['enabled'=>false]);
    if (!empty($popup['image'])) $popup['image_url'] = mediaUrl($popup['image'], 'popups');
    else $popup['image_url'] = '';
    echo json_encode(['success'=>true, 'popup'=>$popup]);
    exit;
}
echo json_encode(['success'=>false, 'error'=>'Unknown action']);
