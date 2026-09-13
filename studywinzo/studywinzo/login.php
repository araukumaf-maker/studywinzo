<?php
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);

$dataDir = __DIR__.'/data';
@mkdir($dataDir, 0755, true);
$usersFile = $dataDir.'/users.json';
$users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];
if (!is_array($users)) $users = [];

$err = '';

// If already logged in
if (!empty($_SESSION['sw_user'])) { header('Location: index.php'); exit; }

// ---------- Handle AJAX OTP send ----------
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='send_otp') {
    header('Content-Type: application/json');
    $mobile = preg_replace('/[^0-9]/','',$_POST['mobile'] ?? '');
    $mode = $_POST['mode'] ?? 'whatsapp';
    if (strlen($mobile) !== 10) {
        echo json_encode(['success'=>false, 'error'=>'Invalid mobile number']); exit;
    }
    $otp = (string)rand(100000, 999999);
    $otpFile = $dataDir.'/otp_'.$mobile.'.json';
    file_put_contents($otpFile, json_encode([
        'mobile' => $mobile,
        'otp'    => $otp,
        'mode'   => $mode,
        'expires'=> time() + 300,
        'created'=> date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT));
    echo json_encode([
        'success' => true,
        'message' => 'OTP sent via '.$mode,
        'otp_demo'=> $otp,
        'hint'    => 'Demo mode: OTP shown on next step'
    ]);
    exit;
}

// ---------- Handle verify OTP ----------
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='verify') {
    header('Content-Type: application/json');
    $mobile = preg_replace('/[^0-9]/','',$_POST['mobile'] ?? '');
    $otp = trim($_POST['otp'] ?? '');
    $otpFile = $dataDir.'/otp_'.$mobile.'.json';
    if (!file_exists($otpFile)) {
        echo json_encode(['success'=>false, 'error'=>'OTP expired. Request again.']); exit;
    }
    $rec = json_decode(file_get_contents($otpFile), true);
    if ($rec['expires'] < time()) {
        @unlink($otpFile);
        echo json_encode(['success'=>false, 'error'=>'OTP expired. Request again.']); exit;
    }
    if ($rec['otp'] !== $otp) {
        echo json_encode(['success'=>false, 'error'=>'Wrong OTP']); exit;
    }
    // Create user if not exists
    if (!isset($users[$mobile])) {
        $users[$mobile] = [
            'mobile' => $mobile,
            'name'   => 'Student '.substr($mobile,-4),
            'joined' => date('Y-m-d H:i:s'),
            'enrolled' => [],
        ];
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
    }
    $_SESSION['sw_user'] = $mobile;
    @unlink($otpFile);
    echo json_encode(['success'=>true, 'redirect'=>'index.php']);
    exit;
}

// ---------- Handle logout ----------
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport"/>
<title>Study Panda - Login</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script>
tailwind.config = {
  theme: { extend: {
    colors: { brandDark:'#07070d', cardBg:'#0f0d1a', accentPurple:'#7c3aed', accentNeon:'#9333ea', activeGreen:'#00d182', textMuted:'#94a3b8', borderGlow:'rgba(147,51,234,0.35)' },
    boxShadow: {
      'glow-purple':'0 0 25px -3px rgba(139,92,246,0.45)',
      'btn-glow':'0 8px 24px -4px rgba(124,58,237,0.55)',
      'card-halo':'0 0 45px -8px rgba(124,58,237,0.22)'
    }
  } }
}
</script>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<style>
body { font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif; background-color:#06050b; -webkit-tap-highlight-color:transparent; user-select:none; }
.bg-ambient-orb-top { background: radial-gradient(circle, rgba(126,34,206,0.38) 0%, rgba(9,7,20,0) 70%); }
.bg-ambient-orb-bottom { background: radial-gradient(circle, rgba(109,40,217,0.25) 0%, rgba(6,5,11,0) 70%); }
.card-neon-border { background: linear-gradient(180deg, rgba(168,85,247,0.45) 0%, rgba(79,70,229,0.15) 45%, rgba(147,51,234,0.25) 100%); padding:1px; border-radius:28px; }
.input-box-glow:focus-within { box-shadow: 0 0 0 2px rgba(168,85,247,0.7), 0 0 16px -2px rgba(168,85,247,0.45); border-color:#a855f7 !important; }
</style>
</head>
<body class="min-h-screen text-slate-100 flex flex-col items-center justify-between antialiased overflow-x-hidden relative">

<div class="fixed -top-28 -left-28 w-96 h-96 rounded-full bg-ambient-orb-top pointer-events-none blur-2xl z-0"></div>
<div class="fixed top-1/4 -right-32 w-80 h-80 rounded-full bg-ambient-orb-top pointer-events-none blur-3xl opacity-60 z-0"></div>
<div class="fixed -bottom-20 -right-20 w-96 h-96 rounded-full bg-ambient-orb-bottom pointer-events-none blur-2xl z-0"></div>

<header class="w-full max-w-md px-4 pt-3 pb-2 flex items-center justify-between z-20 text-slate-300 border-b border-white/5 bg-[#06050b]/80 backdrop-blur-md sticky top-0">
<div class="flex items-center space-x-3">
<button onclick="if(history.length>1)history.back();else location.href='index.php'" class="p-1 hover:text-white transition active:scale-95">
<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
</button>
<button class="p-1 hover:text-white transition active:scale-95">
<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
</button>
</div>
<div class="flex flex-col items-center justify-center text-center">
<h1 class="text-xs font-semibold text-white tracking-wide">Study Panda</h1>
<span class="text-[10px] text-slate-400 leading-tight font-mono opacity-80"><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'studypanda.live') ?></span>
</div>
<div class="flex items-center space-x-3">
<button class="p-1 hover:text-white transition active:scale-95" onclick="if(navigator.share)navigator.share({title:'Study Panda',url:location.href})">
<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" stroke-linecap="round" stroke-linejoin="round"/></svg>
</button>
<button class="p-1 hover:text-white transition active:scale-95">
<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" stroke-linecap="round" stroke-linejoin="round"/></svg>
</button>
<button class="p-1 hover:text-white transition active:scale-95">
<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
</button>
</div>
</header>

<main class="w-full max-w-md px-4 py-4 sm:py-6 flex-1 flex flex-col items-center justify-center z-10">
<div class="card-neon-border w-full shadow-card-halo transition-all duration-300">
<div class="w-full bg-[#0b0a17]/95 backdrop-blur-2xl rounded-[27px] px-5 py-7 flex flex-col items-center border border-white/5 relative overflow-hidden">

<div class="mb-5 relative flex items-center justify-center">
<div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-violet-600 via-purple-500 to-indigo-500 flex items-center justify-center shadow-glow-purple border border-purple-300/30">
<svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
<path d="M12 2.25c.37 0 .68.25.77.6l1.32 5.06a8.25 8.25 0 005.7 5.7l5.06 1.32c.35.09.6.4.6.77s-.25.68-.6.77l-5.06 1.32a8.25 8.25 0 00-5.7 5.7l-1.32 5.06a.79.79 0 01-.77.6.79.79 0 01-.77-.6l-1.32-5.06a8.25 8.25 0 00-5.7-5.7L.6 16.47a.79.79 0 01-.6-.77c0-.37.25-.68.6-.77l5.06-1.32a8.25 8.25 0 005.7-5.7l1.32-5.06c.09-.35.4-.6.77-.6z"></path>
</svg>
</div>
<div class="absolute -inset-1 bg-purple-500/20 blur-lg rounded-2xl -z-10"></div>
</div>

<h2 class="text-[26px] font-bold text-white text-center tracking-tight mb-2">Welcome Back</h2>
<p class="text-slate-400 text-xs sm:text-sm text-center font-normal leading-relaxed max-w-[280px] mb-6">Access your dashboard and enrolled batches instantly</p>

<div class="w-full bg-[#131124] p-1 rounded-xl flex items-center justify-between border border-white/10 mb-5">
<button class="flex-1 py-2.5 px-2 rounded-lg flex items-center justify-center gap-2 text-xs font-semibold transition-all duration-200 border border-emerald-500/50 bg-[#0d2a22]/90 text-emerald-400 shadow-sm" id="tab-whatsapp" onclick="switchTab('whatsapp')" type="button">
<svg class="w-4 h-4 text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"></path></svg>
<span class="tracking-tight">WhatsApp OTP</span>
</button>
<button class="flex-1 py-2.5 px-2 rounded-lg flex items-center justify-center gap-2 text-xs font-medium transition-all duration-200 text-slate-400 hover:text-slate-200 border border-transparent" id="tab-sms" onclick="switchTab('sms')" type="button">
<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" stroke-linecap="round" stroke-linejoin="round"/></svg>
<span class="tracking-tight">SMS OTP</span>
</button>
</div>

<!-- OTP FORM (initial) -->
<form class="w-full space-y-4" id="form-phone" onsubmit="event.preventDefault(); sendOtp();">
<div>
<label class="block text-[11px] font-bold text-[#b5a7d6] tracking-wider uppercase mb-1.5 ml-0.5" for="mobile-number-input">Mobile Number</label>
<div class="input-box-glow w-full flex items-center bg-[#131024] rounded-xl border border-purple-500/40 transition-all duration-200 overflow-hidden">
<div class="px-3.5 py-3 text-sm font-semibold text-slate-100 border-r border-white/10 flex items-center justify-center bg-white/[0.02]">+91</div>
<div class="relative flex-1 flex items-center px-3">
<svg class="w-4 h-4 text-slate-400 mr-2 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
<input class="w-full bg-transparent border-0 focus:ring-0 text-white text-sm placeholder-slate-500 tracking-wide py-3 px-0 font-medium" id="mobile-number-input" inputmode="numeric" maxlength="10" placeholder="Enter 10-digit number" type="tel"/>
</div>
</div>
</div>

<div class="w-full bg-[#121021]/90 rounded-xl p-3.5 border border-white/10 flex flex-col space-y-2">
<div class="flex items-start gap-2.5">
<svg class="w-4 h-4 text-slate-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" x2="12" y1="16" y2="12"></line><line x1="12" x2="12.01" y1="8" y2="8"></line></svg>
<p class="text-xs text-slate-300 font-normal leading-tight" id="otp-info-text">OTP will be sent directly via <span class="text-[#00d182] font-semibold">WhatsApp (Fast &amp; Recommended)</span>.</p>
</div>
<div class="text-[11px] text-slate-400 pl-6 leading-relaxed">
<span class="text-amber-400 font-semibold">⚡ Tip:</span>
<span id="otp-tip-text">WhatsApp OTP arrives instantly. Ensure your mobile number is registered on Study Panda.</span>
</div>
</div>

<div id="err-msg" class="hidden w-full bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs px-3 py-2 rounded-lg text-center"></div>

<button class="w-full mt-2 py-3.5 px-4 rounded-xl bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-600 text-white text-sm font-semibold tracking-wide shadow-btn-glow active:scale-[0.98] transition-all flex items-center justify-center gap-2" id="btn-submit" type="submit">
<span>Send OTP Code</span>
<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-linecap="round" stroke-linejoin="round"/></svg>
</button>
</form>

<!-- OTP VERIFY FORM (hidden) -->
<form class="w-full space-y-4 hidden" id="form-otp" onsubmit="event.preventDefault(); verifyOtp();">
<div class="text-center">
<p class="text-slate-300 text-xs">OTP sent to <span class="text-white font-semibold" id="otp-mobile-shown">+91 XXXXXXXXXX</span></p>
</div>
<div>
<label class="block text-[11px] font-bold text-[#b5a7d6] tracking-wider uppercase mb-1.5 ml-0.5">Enter 6-Digit OTP</label>
<div class="input-box-glow w-full bg-[#131024] rounded-xl border border-purple-500/40 overflow-hidden">
<input class="w-full bg-transparent border-0 focus:ring-0 text-white text-center text-2xl tracking-[12px] placeholder-slate-500 py-3 px-4 font-bold" id="otp-input" inputmode="numeric" maxlength="6" placeholder="------" type="tel"/>
</div>
</div>
<div id="otp-demo-hint" class="hidden w-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs px-3 py-2 rounded-lg text-center">Demo OTP: <b id="otp-demo-val">------</b></div>
<div id="err-msg2" class="hidden w-full bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs px-3 py-2 rounded-lg text-center"></div>
<button class="w-full py-3.5 rounded-xl bg-gradient-to-r from-emerald-600 via-green-600 to-emerald-600 text-white text-sm font-semibold shadow-btn-glow active:scale-[0.98] flex items-center justify-center gap-2" type="submit">
<span>Verify & Login</span>
<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
</button>
<button class="w-full text-slate-400 text-xs font-medium py-2" onclick="backToPhone()" type="button">← Change number</button>
</form>

<div class="mt-6 text-center">
<a class="text-xs text-slate-400 hover:text-slate-300 transition-colors inline-flex items-center gap-1" href="index.php">
<span>Continue without login →</span>
</a>
</div>

<div class="w-full border-t border-white/5 my-5"></div>

<div class="flex items-center justify-center gap-1.5 text-[10px] tracking-wider text-slate-400 font-semibold uppercase">
<span>POWERED BY</span>
<span class="text-purple-300 font-bold tracking-widest">STUDY PANDA</span>
</div>
</div>
</div>
</main>

<footer class="w-full max-w-md pb-2 pt-1 flex justify-center z-20">
<div class="w-32 h-1 bg-white/20 rounded-full"></div>
</footer>

<script>
let currentMode = 'whatsapp';
let currentMobile = '';

function switchTab(mode){
  currentMode = mode;
  const tw = document.getElementById('tab-whatsapp');
  const ts = document.getElementById('tab-sms');
  const info = document.getElementById('otp-info-text');
  const tip = document.getElementById('otp-tip-text');
  if (mode === 'whatsapp') {
    tw.className = "flex-1 py-2.5 px-2 rounded-lg flex items-center justify-center gap-2 text-xs font-semibold transition-all border border-emerald-500/50 bg-[#0d2a22]/90 text-emerald-400 shadow-sm";
    ts.className = "flex-1 py-2.5 px-2 rounded-lg flex items-center justify-center gap-2 text-xs font-medium transition-all text-slate-400 border border-transparent";
    info.innerHTML = 'OTP will be sent directly via <span class="text-[#00d182] font-semibold">WhatsApp (Fast & Recommended)</span>.';
    tip.innerText = 'WhatsApp OTP arrives instantly. Ensure your mobile number is registered on Study Panda.';
  } else {
    tw.className = "flex-1 py-2.5 px-2 rounded-lg flex items-center justify-center gap-2 text-xs font-medium transition-all text-slate-400 border border-transparent";
    ts.className = "flex-1 py-2.5 px-2 rounded-lg flex items-center justify-center gap-2 text-xs font-semibold transition-all border border-purple-400/40 bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-glow-purple";
    info.innerHTML = 'OTP will be sent directly via <span class="text-sky-400 font-semibold">SMS</span>.';
    tip.innerText = 'SMS OTP arrives within 30 seconds. Ensure your mobile number is registered on Study Panda.';
  }
}

function showErr(msg, id){
  const e = document.getElementById(id);
  e.textContent = msg;
  e.classList.remove('hidden');
}

function hideErr(id){
  document.getElementById(id).classList.add('hidden');
}

function sendOtp(){
  hideErr('err-msg');
  const input = document.getElementById('mobile-number-input');
  const val = input.value.trim();
  if (!/^[0-9]{10}$/.test(val)) {
    showErr('Please enter a valid 10-digit mobile number', 'err-msg');
    input.focus();
    return;
  }
  const btn = document.getElementById('btn-submit');
  const old = btn.innerHTML;
  btn.innerHTML = '<span>Sending code...</span>';
  btn.disabled = true;
  btn.classList.add('opacity-80');

  fetch('login.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:'send_otp', mobile:val, mode:currentMode})
  })
  .then(r => r.json())
  .then(d => {
    btn.innerHTML = old;
    btn.disabled = false;
    btn.classList.remove('opacity-80');
    if (!d.success) { showErr(d.error || 'Failed to send OTP', 'err-msg'); return; }
    currentMobile = val;
    document.getElementById('form-phone').classList.add('hidden');
    document.getElementById('form-otp').classList.remove('hidden');
    document.getElementById('otp-mobile-shown').textContent = '+91 ' + val.slice(0,5) + ' ' + val.slice(5);
    if (d.otp_demo) {
      document.getElementById('otp-demo-hint').classList.remove('hidden');
      document.getElementById('otp-demo-val').textContent = d.otp_demo;
    }
    document.getElementById('otp-input').focus();
  })
  .catch(e => {
    btn.innerHTML = old;
    btn.disabled = false;
    btn.classList.remove('opacity-80');
    showErr('Network error. Try again.', 'err-msg');
  });
}

function verifyOtp(){
  hideErr('err-msg2');
  const otp = document.getElementById('otp-input').value.trim();
  if (!/^[0-9]{6}$/.test(otp)) {
    showErr('Enter 6-digit OTP', 'err-msg2');
    return;
  }
  fetch('login.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:'verify', mobile:currentMobile, otp})
  })
  .then(r => r.json())
  .then(d => {
    if (d.success && d.redirect) {
      window.location.href = d.redirect;
    } else {
      showErr(d.error || 'Verification failed', 'err-msg2');
    }
  })
  .catch(() => showErr('Network error', 'err-msg2'));
}

function backToPhone(){
  document.getElementById('form-otp').classList.add('hidden');
  document.getElementById('form-phone').classList.remove('hidden');
  document.getElementById('otp-input').value = '';
  document.getElementById('otp-demo-hint').classList.add('hidden');
}
</script>
</body></html>
