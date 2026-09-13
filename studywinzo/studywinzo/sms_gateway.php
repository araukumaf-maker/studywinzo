<?php
/**
 * SMS Gateway Integration
 * Sender ID: PWALLAH (6 chars — TRAI rule for transactional SMS)
 * Supports: Fast2SMS, MSG91, Twilio, Demo mode
 */
ini_set('display_errors', 0);

function send_otp_sms($mobile, $otp, $mode = 'whatsapp') {
    // ---------- CONFIG ----------
    $provider = 'demo';  // 'demo' | 'fast2sms' | 'msg91' | 'twilio'
    
    $fast2sms_key = 'YOUR_FAST2SMS_API_KEY';
    $msg91_key    = 'YOUR_MSG91_AUTH_KEY';
    $msg91_sender = 'PWALLA';
    $msg91_template = 'YOUR_TEMPLATE_ID';
    
    $twilio_sid   = 'YOUR_TWILIO_SID';
    $twilio_token = 'YOUR_TWILIO_TOKEN';
    $twilio_from  = '+1XXXXXXXXXX';

    $message = "Your PW - Physics Wallah OTP is $otp. Valid for 5 minutes. Do not share with anyone. - PWALLAH";
    $sender_id = 'PWALLA'; // 6-char sender ID (shows as "PWALLA" on phone)

    // ---------- DEMO ----------
    if ($provider === 'demo') {
        return [
            'success' => true,
            'provider' => 'demo',
            'sender' => 'Physics Wallah (PW)',
            'otp' => $otp,
            'message' => $message,
            'note' => 'Demo mode — OTP shown on screen. Configure real provider for actual SMS.'
        ];
    }

    // ---------- FAST2SMS (India — cheap, easy) ----------
    if ($provider === 'fast2sms') {
        $url = 'https://www.fast2sms.com/dev/bulkV2';
        $data = [
            'route' => 'dlt',
            'sender_id' => 'PWALLA',       // DLT-approved sender ID
            'message' => 'YOUR_DLT_TEMPLATE_ID', // DLT template ID
            'variables_values' => $otp,
            'flash' => 0,
            'numbers' => $mobile,
        ];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => ['authorization: '.$fast2sms_key, 'Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json = json_decode($res, true);
        return [
            'success' => ($code === 200 && !empty($json['return'])),
            'provider' => 'fast2sms',
            'sender' => 'PWALLA',
            'response' => $json ?: $res
        ];
    }

    // ---------- MSG91 ----------
    if ($provider === 'msg91') {
        $url = 'https://control.msg91.com/api/v5/flow/';
        $payload = [
            'template_id' => $msg91_template,
            'short_url' => '0',
            'recipients' => [['mobiles' => '91'.$mobile, 'OTP' => $otp]]
        ];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['authkey: '.$msg91_key, 'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($res, true);
        return [
            'success' => !empty($json['type']) && $json['type'] === 'success',
            'provider' => 'msg91',
            'sender' => 'PWALLA',
            'response' => $json ?: $res
        ];
    }

    // ---------- TWILIO ----------
    if ($provider === 'twilio') {
        $url = "https://api.twilio.com/2010-04-01/Accounts/$twilio_sid/Messages.json";
        $data = ['From' => $twilio_from, 'To' => '+91'.$mobile, 'Body' => $message];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_USERPWD => "$twilio_sid:$twilio_token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($res, true);
        return [
            'success' => !empty($json['sid']),
            'provider' => 'twilio',
            'sender' => $twilio_from,
            'response' => $json ?: $res
        ];
    }

    return ['success' => false, 'error' => 'Unknown provider'];
}
