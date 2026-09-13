<?php
ini_set('display_errors', 0);
session_start();

$adminData = __DIR__.'/admin/data';
require_once __DIR__.'/data_helper.php';
function loadJSON($f,$d=[]){ return getJSON(basename($f), $d); }

$insts = loadJSON($adminData.'/institutions.json');
$batches = loadJSON($adminData.'/batches.json');
$settings = loadJSON($adminData.'/settings.json',['institution_name'=>'StudyWinzo']);
$instId = preg_replace('/[^a-zA-Z0-9_]/','',$_GET['id'] ?? '');

$inst = null;
foreach ($insts as $x) if ($x['id']===$instId) { $inst = $x; break; }
if (!$inst) { header('Location: index.php'); exit; }

// Login gate
$sessionKey = 'sw_logged_'.$instId;
$isLoggedIn = !empty($_SESSION[$sessionKey]);

if (isset($_GET['logout'])) {
    unset($_SESSION[$sessionKey]);
    unset($_SESSION[$sessionKey.'_mobile']);
    header('Location: institution.php?id='.urlencode($instId)); exit;
}

// OTP AJAX
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $mobile = preg_replace('/[^0-9]/','',$_POST['mobile'] ?? '');
    $mode = $_POST['mode'] ?? 'whatsapp';
    if ($_POST['action'] === 'send_otp') {
        if (strlen($mobile) !== 10) { echo json_encode(['success'=>false,'error'=>'Invalid mobile']); exit; }
        $otp = (string)rand(100000, 999999);
        $otpFile = $adminData.'/otp_'.$instId.'_'.$mobile.'.json';
        file_put_contents($otpFile, json_encode(['otp'=>$otp,'mobile'=>$mobile,'expires'=>time()+300], JSON_PRETTY_PRINT));
        echo json_encode(['success'=>true, 'otp_demo'=>$otp]); exit;
    }
    if ($_POST['action'] === 'verify_otp') {
        $otp = trim($_POST['otp'] ?? '');
        $otpFile = $adminData.'/otp_'.$instId.'_'.$mobile.'.json';
        if (!file_exists($otpFile)) { echo json_encode(['success'=>false,'error'=>'OTP expired']); exit; }
        $rec = json_decode(file_get_contents($otpFile), true);
        if ($rec['expires'] < time()) { @unlink($otpFile); echo json_encode(['success'=>false,'error'=>'OTP expired']); exit; }
        if ($rec['otp'] !== $otp) { echo json_encode(['success'=>false,'error'=>'Wrong OTP']); exit; }
        $_SESSION[$sessionKey] = true;
        $_SESSION[$sessionKey.'_mobile'] = $mobile;
        @unlink($otpFile);
        echo json_encode(['success'=>true, 'redirect'=>'institution.php?id='.urlencode($instId)]); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Unknown']); exit;
}

$instBatches = array_values(array_filter($batches, fn($b)=>($b['institutionId']??'')===$instId));
$instName = $settings['institution_name'] ?? 'StudyWinzo';
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title><?= htmlspecialchars($inst['name']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<style>
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent;font-family:'Inter',sans-serif}
body{background:#06050b;color:#fff;min-height:100vh;margin:0;display:flex;align-items:center;justify-content:center;padding:16px}
.orb{position:fixed;border-radius:50%;filter:blur(60px);opacity:.4;pointer-events:none;z-index:0}
.orb1{top:-120px;left:-120px;width:400px;height:400px;background:radial-gradient(circle,#7e22ce,transparent 70%)}
.orb2{bottom:-120px;right:-120px;width:400px;height:400px;background:radial-gradient(circle,#6d28d9,transparent 70%)}

/* LOGIN */
.card{border-radius:28px;padding:1px;background:linear-gradient(180deg,rgba(168,85,247,.5),rgba(79,70,229,.15) 45%,rgba(147,51,234,.3));box-shadow:0 0 60px -8px rgba(124,58,237,.4);position:relative;z-index:10;width:100%;max-width:420px}
.card-inner{background:rgba(11,10,23,.95);backdrop-filter:blur(20px);border-radius:27px;padding:32px 24px;border:1px solid rgba(255,255,255,.05)}
.input-wrap:focus-within{box-shadow:0 0 0 2px rgba(168,85,247,.7);border-color:#a855f7 !important}
.tab-active-wa{border:1px solid rgba(16,185,129,.5) !important;background:rgba(13,42,34,.9) !important;color:#34d399 !important;font-weight:700}
.tab-active-sms{border:1px solid rgba(168,85,247,.4) !important;background:linear-gradient(135deg,#7c3aed,#4f46e5) !important;color:#fff !important;font-weight:700}
.inst-logo{width:64px;height:64px;border-radius:20px;background:#fff;border:2px solid #2cee82;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;overflow:hidden;padding:6px}

/* INSTITUTION PAGE */
.wrap{width:100%;max-width:430px;min-height:100vh;position:relative;z-index:10}
.page-hdr{padding:16px;display:flex;align-items:center;gap:12px;border-bottom:1px solid #1a2a20;position:sticky;top:0;background:rgba(3,6,5,.95);backdrop-filter:blur(12px);z-index:10}
.back{width:44px;height:44px;border-radius:14px;background:#0e1613;border:1px solid #1f332a;display:flex;align-items:center;justify-content:center;text-decoration:none;flex-shrink:0}
.back i{color:#2cee82;font-size:20px}
.hdr-info{flex:1;min-width:0}
.hdr-info h1{font-size:16px;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.hdr-info p{font-size:11px;color:#2cee82;margin-top:2px}
.logout-btn{padding:8px 14px;border-radius:20px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#f87171;font-size:11px;font-weight:700;text-decoration:none;flex-shrink:0}

.search-wrap{padding:12px 16px 0;position:relative;z-index:10}
.search-box{display:flex;align-items:center;gap:10px;background:#0e1214;border:1px solid #1f332a;border-radius:16px;padding:12px 16px}
.search-box:focus-within{border-color:#2cee82;box-shadow:0 0 0 3px rgba(44,238,130,.15)}
.search-box i{color:#64748b;font-size:20px}
.search-box input{flex:1;background:transparent;border:none;outline:none;color:#fff;font-size:14px;font-weight:500;font-family:inherit}
.search-box input::placeholder{color:#64748b}

.content-area{padding:16px;padding-bottom:40px}
.section-title{font-size:14px;font-weight:800;color:#fff;margin:16px 0 12px;display:flex;align-items:center;gap:8px}
.section-title i{color:#2cee82;font-size:18px}

.batch-grid{display:grid;gap:14px}
.batch-card{background:linear-gradient(145deg,#0a0f0d,#050b09);border:1px solid #1f332a;border-radius:20px;overflow:hidden;text-decoration:none;color:inherit;transition:all .25s;display:block;position:relative}
.batch-card:hover{border-color:rgba(44,238,130,.5);transform:translateY(-2px)}
.batch-thumb{width:100%;aspect-ratio:16/10;background:linear-gradient(135deg,#0e2818,#0a1f15);overflow:hidden;position:relative;display:flex;align-items:center;justify-content:center}
.batch-thumb img{width:100%;height:100%;object-fit:cover;display:block;position:absolute;inset:0}
.batch-thumb .letter{font-size:96px;font-weight:900;color:rgba(44,238,130,.35);letter-spacing:-4px;line-height:1}
.batch-thumb-overlay{position:absolute;top:0;right:0;padding:10px;z-index:2}
.batch-badge{background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-size:10px;font-weight:800;letter-spacing:1px;padding:4px 12px;border-radius:20px;box-shadow:0 4px 14px rgba(16,185,129,.5)}
.batch-info{padding:14px 16px 16px}
.batch-name{font-size:16px;font-weight:800;color:#fff;margin:0;line-height:1.3}
.batch-tag{font-size:12px;color:#94a3b8;margin:4px 0 0}
.batch-meta{display:flex;gap:8px;margin-top:10px;align-items:center}
.batch-chip{display:inline-flex;align-items:center;gap:5px;background:rgba(44,238,130,.1);border:1px solid rgba(44,238,130,.25);border-radius:20px;padding:4px 10px;font-size:10.5px;font-weight:700;color:#2cee82}
.batch-arrow{margin-left:auto;width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(16,185,129,.4)}
.batch-arrow i{color:#fff;font-size:16px}

.no-content{text-align:center;padding:60px 20px;color:#64748b}
.no-content i{font-size:56px;opacity:.3;display:block;margin-bottom:14px}

/* OTP form */
.err-box{padding:10px 14px;border-radius:10px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#fca5a5;font-size:12px;text-align:center;display:none;margin-bottom:14px}
.err-box.show{display:block}
.submit-btn{width:100%;padding:14px;border-radius:14px;border:none;background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:8px}
.submit-btn:disabled{opacity:.6}
</style>
</head>
<body>
<div class="orb orb1"></div>
<div class="orb orb2"></div>

<?php if (!$isLoggedIn): ?>

<!-- LOGIN GATE -->
<div class="card">
<div class="card-inner">
<a href="index.php" style="position:absolute;top:16px;left:16px;width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.05);display:flex;align-items:center;justify-content:center;text-decoration:none">
<i class="ph-bold ph-x" style="color:#94a3b8;font-size:16px"></i>
</a>

<div class="inst-logo">
<?php if (!empty($inst['logo'])): ?>
<img src="admin/uploads/institutions/<?= htmlspecialchars($inst['logo']) ?>" style="width:100%;height:100%;object-fit:contain"/>
<?php else: ?>
<span style="font-size:22px;font-weight:900;color:#2cee82"><?= strtoupper(substr($inst['name'],0,1)) ?></span>
<?php endif; ?>
</div>

<h2 style="text-align:center;margin:0 0 6px;font-size:22px;font-weight:800;color:#fff">Welcome to <?= htmlspecialchars($inst['name']) ?></h2>
<p style="text-align:center;margin:0 0 24px;font-size:12.5px;color:#94a3b8">Login to access free batches</p>

<div style="display:flex;background:#131124;padding:4px;border-radius:14px;border:1px solid rgba(255,255,255,.1);margin-bottom:20px">
<button type="button" id="tab-wa" onclick="switchTab('whatsapp')" style="flex:1;padding:10px;border-radius:10px;border:1px solid transparent;background:transparent;color:#94a3b8;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;font-family:inherit">
<i class="ph-fill ph-whatsapp-logo" style="font-size:16px"></i> WhatsApp
</button>
<button type="button" id="tab-sms" onclick="switchTab('sms')" style="flex:1;padding:10px;border-radius:10px;border:1px solid transparent;background:transparent;color:#94a3b8;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;font-family:inherit">
<i class="ph-bold ph-device-mobile" style="font-size:16px"></i> SMS
</button>
</div>

<form id="formPhone" onsubmit="event.preventDefault();sendOtp()" style="display:flex;flex-direction:column;gap:14px">
<div>
<label style="display:block;font-size:11px;font-weight:700;color:#b5a7d6;letter-spacing:.5px;text-transform:uppercase;margin-bottom:6px">Mobile Number</label>
<div class="input-wrap" style="display:flex;align-items:center;background:#131024;border:1px solid rgba(168,85,247,.4);border-radius:12px;overflow:hidden;transition:all .2s">
<div style="padding:12px 14px;font-size:14px;font-weight:600;color:#e2e8f0;border-right:1px solid rgba(255,255,255,.1)">+91</div>
<div style="flex:1;display:flex;align-items:center;padding:0 12px">
<i class="ph-bold ph-device-mobile" style="color:#94a3b8;font-size:16px;margin-right:8px"></i>
<input type="tel" id="mobile" placeholder="10-digit number" maxlength="10" inputmode="numeric" style="flex:1;background:transparent;border:none;outline:none;color:#fff;font-size:14px;font-weight:500;padding:12px 0;font-family:inherit"/>
</div>
</div>
</div>
<div id="errBox" class="err-box"></div>
<button type="submit" id="sendBtn" class="submit-btn">
Send OTP Code <i class="ph-bold ph-arrow-right"></i>
</button>
</form>

<form id="formOtp" onsubmit="event.preventDefault();verifyOtp()" style="display:none;flex-direction:column;gap:14px">
<p style="text-align:center;margin:0;font-size:13px;color:#cbd5e1">OTP sent to <b id="shownMobile" style="color:#fff">+91 XXXXXXXXXX</b></p>
<div>
<label style="display:block;font-size:11px;font-weight:700;color:#b5a7d6;letter-spacing:.5px;text-transform:uppercase;margin-bottom:6px;text-align:center">Enter 6-Digit OTP</label>
<div class="input-wrap" style="background:#131024;border:1px solid rgba(168,85,247,.4);border-radius:12px;overflow:hidden">
<input type="tel" id="otp" placeholder="------" maxlength="6" inputmode="numeric" autocomplete="one-time-code" style="width:100%;background:transparent;border:none;outline:none;color:#fff;font-size:26px;font-weight:800;text-align:center;letter-spacing:12px;padding:14px;font-family:inherit"/>
</div>
</div>
<div id="otpHint" style="display:none;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:12px;padding:12px 14px;text-align:center">
<span style="display:block;font-size:10px;color:#34d399;font-weight:700;letter-spacing:1px;margin-bottom:4px">📱 DEMO OTP (auto-fill हो रहा है...)</span>
<span id="demoOtp" style="display:block;font-size:22px;font-weight:900;color:#fff;font-family:monospace;letter-spacing:4px">------</span>
</div>
<div id="errBox2" class="err-box"></div>
<button type="submit" class="submit-btn" style="background:linear-gradient(135deg,#10b981,#059669)">
Verify & Continue <i class="ph-bold ph-check"></i>
</button>
<button type="button" onclick="backToPhone()" style="width:100%;padding:10px;border-radius:10px;border:none;background:transparent;color:#94a3b8;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit">
← Change number
</button>
</form>
</div>
</div>

<?php else: ?>

<!-- BATCHES PAGE -->
<div class="wrap">

<div class="page-hdr">
<a href="index.php" class="back"><i class="ph-bold ph-arrow-left"></i></a>
<div class="hdr-info">
<h1><?= htmlspecialchars($inst['name']) ?></h1>
<p><i class="ph-fill ph-check-circle"></i> Logged in · <?= count($instBatches) ?> batches</p>
</div>
<a href="?id=<?= urlencode($instId) ?>&logout=1" class="logout-btn">Logout</a>
</div>

<div class="search-wrap">
<div class="search-box">
<i class="ph-bold ph-magnifying-glass"></i>
<input type="text" id="searchInput" placeholder="Search batches..." oninput="doSearch(this.value)"/>
</div>
</div>

<div class="content-area">
<div class="section-title"><i class="ph-bold ph-stack"></i> Your Batches (<?= count($instBatches) ?>)</div>

<?php if (empty($instBatches)): ?>
<div class="no-content">
<i class="ph-bold ph-stack"></i>
<h3 style="font-size:15px;font-weight:700;color:#94a3b8;margin-bottom:6px">No batches yet</h3>
<p style="font-size:13px">Content coming soon</p>
</div>
<?php else: ?>
<div class="batch-grid" id="batchGrid">
<?php foreach ($instBatches as $b):
    $color = htmlspecialchars($b['color'] ?? '#2cee82');
?>
<a href="batch.php?id=<?= urlencode($b['id']) ?>" class="batch-card" data-name="<?= htmlspecialchars(strtolower($b['name'])) ?>">
<div class="batch-thumb">
<?php if (!empty($b['image'])): ?>
<img src="admin/uploads/batches/<?= htmlspecialchars($b['image']) ?>" decoding="sync" fetchpriority="high" loading="eager"/>
<?php else: ?>
<span class="letter"><?= strtoupper(substr($b['name'],0,1)) ?></span>
<?php endif; ?>
<div class="batch-thumb-overlay">
<span class="batch-badge">FREE</span>
</div>
</div>
<div class="batch-info">
<h3 class="batch-name"><?= htmlspecialchars($b['name']) ?></h3>
<?php if (!empty($b['subject'])): ?>
<p class="batch-tag"><?= htmlspecialchars($b['subject']) ?></p>
<?php endif; ?>
<div class="batch-meta">
<span class="batch-chip"><i class="ph-bold ph-book-open"></i> Subjects</span>
<span class="batch-chip"><i class="ph-bold ph-infinity"></i> Lifetime</span>
<span class="batch-arrow"><i class="ph-bold ph-arrow-right"></i></span>
</div>
</div>
</a>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

</div>

<?php endif; ?>

<script>
var currentMode = 'whatsapp';
var currentMobile = '';

function switchTab(m){
  currentMode = m;
  var wa = document.getElementById('tab-wa'), sms = document.getElementById('tab-sms');
  if (m==='whatsapp') {
    wa.style.cssText = 'flex:1;padding:10px;border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;font-family:inherit;font-size:12px;';
    wa.classList.add('tab-active-wa');
    sms.style.cssText = 'flex:1;padding:10px;border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;font-family:inherit;font-size:12px;color:#94a3b8;background:transparent;border:1px solid transparent;';
  } else {
    sms.style.cssText = 'flex:1;padding:10px;border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;font-family:inherit;font-size:12px;';
    sms.classList.add('tab-active-sms');
    wa.style.cssText = 'flex:1;padding:10px;border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;font-family:inherit;font-size:12px;color:#94a3b8;background:transparent;border:1px solid transparent;';
  }
}

function sendOtp(){
  var err = document.getElementById('errBox');
  err.classList.remove('show');
  var mobile = document.getElementById('mobile').value.trim();
  if (!/^[0-9]{10}$/.test(mobile)) { err.textContent='Enter valid 10-digit number'; err.classList.add('show'); return; }
  var btn = document.getElementById('sendBtn');
  btn.disabled = true; btn.innerHTML = 'Sending...';
  var fd = new FormData();
  fd.append('action','send_otp'); fd.append('mobile', mobile); fd.append('mode', currentMode);
  fetch('institution.php?id=<?= urlencode($instId) ?>', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(d){
      btn.disabled = false; btn.innerHTML = 'Send OTP Code <i class="ph-bold ph-arrow-right"></i>';
      if (!d.success) { err.textContent = d.error || 'Failed'; err.classList.add('show'); return; }
      currentMobile = mobile;
      document.getElementById('formPhone').style.display = 'none';
      document.getElementById('formOtp').style.display = 'flex';
      document.getElementById('shownMobile').textContent = '+91 '+mobile.slice(0,5)+' '+mobile.slice(5);
      if (d.otp_demo) {
        document.getElementById('otpHint').style.display = 'block';
        document.getElementById('demoOtp').textContent = d.otp_demo;
        setTimeout(function(){
          var otpInp = document.getElementById('otp');
          otpInp.value = d.otp_demo;
          verifyOtp();
        }, 1200);
      }
    });
}

function verifyOtp(){
  var err = document.getElementById('errBox2');
  err.classList.remove('show');
  var otp = document.getElementById('otp').value.trim();
  if (!/^[0-9]{6}$/.test(otp)) { err.textContent='Enter 6-digit OTP'; err.classList.add('show'); return; }
  var fd = new FormData();
  fd.append('action','verify_otp'); fd.append('mobile', currentMobile); fd.append('otp', otp);
  fetch('institution.php?id=<?= urlencode($instId) ?>', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(d){
      if (d.success && d.redirect) location.href = d.redirect;
      else { err.textContent = d.error || 'Wrong OTP'; err.classList.add('show'); }
    });
}

function backToPhone(){
  document.getElementById('formOtp').style.display = 'none';
  document.getElementById('formPhone').style.display = 'flex';
  document.getElementById('otp').value = '';
  document.getElementById('otpHint').style.display = 'none';
}

function doSearch(q){
  q = (q||'').toLowerCase().trim();
  document.querySelectorAll('.batch-card').forEach(function(c){
    var name = c.dataset.name || '';
    c.style.display = (!q || name.indexOf(q) !== -1) ? 'block' : 'none';
  });
}
</script>
</body></html>
