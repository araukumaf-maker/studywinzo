<?php
ini_set('display_errors', 0);
session_start();

require_once __DIR__.'/data_helper.php';

$adminData = __DIR__.'/admin/data';
// Legacy shim: route loadJSON() to Supabase-backed getJSON()
function loadJSON($f, $d=[]){ return getJSON(basename($f), $d); }

$insts = loadJSON($adminData.'/institutions.json');
$batches = loadJSON($adminData.'/batches.json');
$settings = loadJSON($adminData.'/settings.json', ['institution_name'=>'StudyWinzo','logo'=>'']);
$popup = loadJSON($adminData.'/popup.json', ['enabled'=>false]);

usort($insts, fn($a,$b)=>($a['order']??0)-($b['order']??0));
$instName = $settings['institution_name'] ?? 'StudyWinzo';
$logo = $settings['logo'] ?? '';
?>
<!DOCTYPE html>
<html class="dark" lang="en"><head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<link rel="preload" as="image" href="admin/uploads/batches/" fetchpriority="high"/>
<title><?= htmlspecialchars($instName) ?> — All Free</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Merriweather:wght@700;900&display=swap" rel="stylesheet"/>
<style>
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
body{font-family:'Inter',sans-serif;background:#030605;color:#fff;min-height:100vh;margin:0}
.hero-title{font-family:'Merriweather',serif}
.glow{position:absolute;top:40px;left:50%;transform:translateX(-50%);width:340px;height:280px;background:radial-gradient(circle,rgba(16,185,129,.16),rgba(0,0,0,0) 80%);pointer-events:none;z-index:0}
.inst-card{background:#0e1214;border:1px solid #1f332a;border-radius:24px;padding:20px 14px;display:flex;flex-direction:column;align-items:center;text-decoration:none;color:inherit;transition:all .2s;cursor:pointer}
.inst-card:active{transform:scale(.97)}

.inst-logo{width:82px;height:82px;border-radius:20px;background:#ffffff;border:2px solid #2cee82;display:flex;align-items:center;justify-content:center;overflow:hidden;margin-bottom:12px;padding:6px}
.inst-logo img{width:100%;height:100%;object-fit:contain}
</style>
</head>
<body>
<div style="max-width:430px;margin:0 auto;min-height:100vh;background:#050705;position:relative;overflow:hidden;padding-bottom:40px">
<div class="glow"></div>

<header style="position:relative;z-index:10;padding:20px;display:flex;justify-content:space-between;align-items:center">
<button onclick="toggleDrawer()" style="background:#0e1613;border:1px solid #1f332a;width:48px;height:48px;border-radius:16px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px">
<span style="width:20px;height:2.5px;background:#1ab073;border-radius:2px"></span>
<span style="width:20px;height:2.5px;background:#1ab073;border-radius:2px"></span>
<span style="width:20px;height:2.5px;background:#1ab073;border-radius:2px"></span>
</button>
<div style="text-align:center;flex:1;padding:0 10px">
<?php if($logo): ?>
<img src="<?= htmlspecialchars(mediaUrl($logo, 'logos')) ?>" style="width:56px;height:56px;border-radius:16px;object-fit:contain;background:#fff;padding:4px;border:2px solid #2cee82;margin:0 auto"/>
<?php else: ?>
<div style="width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#0e1f1a,#10b981);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:900;color:#2cee82;border:2px solid #2cee82;margin:0 auto">SW</div>
<?php endif; ?>
</div>
<button style="background:#0e1613;border:1px solid #1f332a;width:48px;height:48px;border-radius:16px;position:relative;display:flex;align-items:center;justify-content:center">
<i class="ph-bold ph-bell" style="color:#1ab073;font-size:22px"></i>
</button>
</header>

<section style="position:relative;z-index:10;text-align:center;padding:16px 20px">
<h1 class="hero-title" style="font-size:34px;font-weight:900;line-height:1.15;margin:0 0 8px;color:#fff">Welcome to<br/><span style="color:#2cee82;text-shadow:0 2px 12px rgba(44,238,130,.3)"><?= htmlspecialchars($instName) ?></span></h1>
<p style="font-size:14px;font-weight:700;color:#e2e8f0;margin:8px 0">Education Must Be <span style="color:#2cee82">Free</span> For Everyone</p>
</section>


<main style="position:relative;z-index:10;padding:16px">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
<?php if (empty($insts)): ?>
<div style="grid-column:span 2;text-align:center;padding:60px 20px;color:#64748b">
<p style="font-size:48px;opacity:.4;margin:0 0 12px">🏛️</p>
<p style="font-size:14px">No institutions yet</p>
</div>
<?php else: foreach ($insts as $x): ?>
<a href="institution.php?id=<?= urlencode($x['id']) ?>" class="inst-card" data-name="<?= htmlspecialchars($x['name']) ?>" data-inst="institution" data-name="<?= htmlspecialchars($x['name']) ?>" data-inst="institution">
<div class="inst-logo" style="border-color:<?= htmlspecialchars($x['color']) ?>">
<?php if (!empty($x['logo'])): ?>
<img src="<?= htmlspecialchars(mediaUrl($x['logo'], 'institutions')) ?>"/>
<?php else: ?>
<i class="ph-bold ph-buildings" style="font-size:36px;color:<?= htmlspecialchars($x['color']) ?>"></i>
<?php endif; ?>
</div>
<span style="font-size:14px;font-weight:700;color:#e2e8f0;text-align:center;line-height:1.3"><?= htmlspecialchars($x['name']) ?></span>
<div style="margin-top:8px;padding:2px 10px;background:#0e2818;border:1px solid #1f4a2f;border-radius:12px">
<span style="font-size:9px;color:#2cee82;font-weight:800;letter-spacing:1px">FREE</span>
</div>
</a>
<?php endforeach; endif; ?>
</div>
</main>

<footer style="position:relative;z-index:10;text-align:center;padding:20px;font-size:10px;color:#475569">
© 2026 <?= htmlspecialchars($instName) ?> · All Free Forever
</footer>
</div>

<!-- Drawer -->

<style>
/* Premium Sidebar Styles */
.sb-overlay{position:fixed;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(4px);z-index:998;display:none;opacity:0;transition:opacity .3s}
.sb-overlay.show{display:block;opacity:1}
.sb{position:fixed;top:0;left:0;bottom:0;width:300px;background:linear-gradient(180deg,#0a1512 0%,#050a08 100%);z-index:999;transform:translateX(-100%);transition:transform .35s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column;overflow:hidden;box-shadow:8px 0 40px rgba(0,0,0,.8)}
.sb.open{transform:translateX(0)}
.sb::before{content:'';position:absolute;top:-100px;right:-100px;width:300px;height:300px;background:radial-gradient(circle,rgba(16,185,129,.15),transparent 70%);pointer-events:none}
.sb-header{padding:24px 20px 20px;position:relative;border-bottom:1px solid rgba(44,238,130,.08)}
.sb-logo{font-size:26px;font-weight:900;color:#fff;margin:0;letter-spacing:-.5px;display:flex;align-items:center;gap:8px}
.sb-logo span{color:#2cee82;text-shadow:0 0 20px rgba(44,238,130,.4)}
.sb-tagline{font-size:10px;color:#4a5d56;margin:4px 0 0;letter-spacing:2px;font-weight:700}
.sb-profile{margin:16px 16px 8px;padding:14px 16px;background:linear-gradient(135deg,rgba(16,185,129,.12),rgba(5,150,105,.05));border:1px solid rgba(44,238,130,.2);border-radius:16px;display:flex;align-items:center;gap:12px;position:relative;overflow:hidden}
.sb-profile::before{content:'';position:absolute;top:-30px;right:-30px;width:80px;height:80px;background:radial-gradient(circle,rgba(44,238,130,.2),transparent 70%)}
.sb-avatar{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#2cee82,#059669);display:flex;align-items:center;justify-content:center;font-weight:900;color:#000;font-size:18px;flex-shrink:0;box-shadow:0 4px 12px rgba(44,238,130,.4)}
.sb-profile-info{flex:1;min-width:0}
.sb-profile-name{font-size:13.5px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sb-profile-role{font-size:10px;color:#2cee82;font-weight:700;letter-spacing:1px;margin-top:2px;display:flex;align-items:center;gap:5px}
.sb-scroll{flex:1;overflow-y:auto;padding:8px 12px 20px}
.sb-scroll::-webkit-scrollbar{width:3px}
.sb-scroll::-webkit-scrollbar-thumb{background:rgba(44,238,130,.3);border-radius:4px}
.sb-section{font-size:10px;font-weight:800;color:#3d5149;letter-spacing:1.5px;padding:16px 12px 8px;text-transform:uppercase}
.sb-item{display:flex;align-items:center;gap:14px;padding:12px 14px;border-radius:12px;color:#9cb1ce;text-decoration:none;font-size:13.5px;font-weight:500;margin-bottom:3px;transition:all .2s;position:relative;font-family:inherit;background:transparent;border:none;width:100%;cursor:pointer;text-align:left}
.sb-item:hover{background:rgba(44,238,130,.06);color:#e2e8f0;transform:translateX(3px)}
.sb-item:hover .sb-icon{color:#2cee82}
.sb-item.active{background:linear-gradient(135deg,rgba(16,185,129,.25),rgba(5,150,105,.15));color:#fff;font-weight:700;border:1px solid rgba(44,238,130,.3);box-shadow:0 4px 16px rgba(16,185,129,.2)}
.sb-item.active .sb-icon{color:#2cee82;filter:drop-shadow(0 0 6px rgba(44,238,130,.6))}
.sb-item.active::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:60%;background:linear-gradient(180deg,#2cee82,#059669);border-radius:0 4px 4px 0}
.sb-icon{font-size:20px;color:#64748b;transition:all .2s;flex-shrink:0;display:flex;align-items:center;justify-content:center;width:22px}
.sb-badge{margin-left:auto;padding:2px 8px;border-radius:10px;font-size:9.5px;font-weight:800;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;letter-spacing:.5px}
.sb-badge-new{background:linear-gradient(135deg,#2cee82,#059669);color:#000}
.sb-footer{padding:14px 16px;border-top:1px solid rgba(44,238,130,.08);text-align:center}
.sb-footer p{margin:0 0 3px;font-size:10px;color:#4a5d56;font-weight:500}
.sb-close{position:absolute;top:20px;right:16px;width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;cursor:pointer;color:#94a3b8;transition:all .2s}
.sb-close:hover{background:rgba(239,68,68,.15);color:#f87171;border-color:rgba(239,68,68,.3)}
</style>

<div class="sb-overlay" id="drawer-overlay" onclick="toggleDrawer()"></div>
<aside class="sb" id="drawer">

<button class="sb-close" onclick="toggleDrawer()"><i class="ph-bold ph-x" style="font-size:14px"></i></button>

<div class="sb-header">
<h1 class="sb-logo">Study<span>Winzo</span></h1>
<p class="sb-tagline">ALL FREE FOREVER</p>
</div>

<div class="sb-profile">
<div class="sb-avatar">S</div>
<div class="sb-profile-info">
<div class="sb-profile-name">Student</div>
<div class="sb-profile-role"><span style="width:6px;height:6px;border-radius:50%;background:#2cee82;display:inline-block;box-shadow:0 0 6px #2cee82"></span> ONLINE</div>
</div>
</div>

<div class="sb-scroll">

<div class="sb-section">Main</div>
<a href="/" class="sb-item active">
<i class="ph-fill ph-house sb-icon"></i>
<span>Home</span>
</a>

<div class="sb-section">Categories</div>
<a href="#" class="sb-item">
<i class="ph-bold ph-lightning sb-icon" style="color:#fbbf24"></i>
<span>JEE</span>
</a>
<a href="#" class="sb-item">
<i class="ph-bold ph-heartbeat sb-icon" style="color:#f87171"></i>
<span>NEET</span>
</a>
<a href="#" class="sb-item">
<i class="ph-bold ph-graduation-cap sb-icon" style="color:#60a5fa"></i>
<span>Boards</span>
</a>
<a href="#" class="sb-item">
<i class="ph-bold ph-shapes sb-icon" style="color:#c084fc"></i>
<span>Foundation</span>
</a>

<div class="sb-section">Personal</div>
<a href="#" class="sb-item">
<i class="ph-bold ph-bookmark-simple sb-icon"></i>
<span>Saved</span>
</a>
<a href="#" class="sb-item">
<i class="ph-bold ph-download-simple sb-icon"></i>
<span>Downloads</span>
</a>
<a href="#" class="sb-item">
<i class="ph-bold ph-clock-counter-clockwise sb-icon"></i>
<span>History</span>
</a>

<div class="sb-section">Support</div>
<a href="https://wa.me/919999999999" target="_blank" class="sb-item">
<i class="ph-fill ph-whatsapp-logo sb-icon" style="color:#25D366"></i>
<span>WhatsApp Support</span>
</a>
<a href="#" class="sb-item">
<i class="ph-bold ph-question sb-icon"></i>
<span>Help Center</span>
</a>
<a href="#" class="sb-item">
<i class="ph-bold ph-info sb-icon"></i>
<span>About Us</span>
</a>

<div class="sb-section">Account</div>

<a href="admin/login.php" class="sb-item">
<i class="ph-bold ph-shield-check sb-icon" style="color:#fbbf24"></i>
<span>Admin Panel</span>
</a>

</div>

<div class="sb-footer">
<p>© 2026 StudyWinzo</p>
<p>Made with ❤️ for students</p>
</div>

</aside>


<?php if (!empty($popup['enabled']) && !empty($popup['image'])): ?>
<div id="promo-modal" style="position:fixed;inset:0;background:rgba(0,0,0,.88);backdrop-filter:blur(4px);z-index:9999;display:none;align-items:center;justify-content:center;padding:16px;">
<div style="background:#0a0f0a;border:1px solid #1f332a;border-radius:20px;max-width:380px;width:100%;overflow:hidden;position:relative;">
<button onclick="closePromo()" style="position:absolute;top:12px;right:12px;z-index:2;background:rgba(0,0,0,.7);border:none;color:#fff;width:34px;height:34px;border-radius:50%;font-size:20px;cursor:pointer;">×</button>
<img src="<?= htmlspecialchars(mediaUrl($popup['image'], 'popups')) ?>" style="width:100%;aspect-ratio:2/1;object-fit:cover;display:block"/>
<div style="padding:22px 20px;text-align:center;">
<h3 style="color:#fff;font-size:22px;font-weight:800;margin:0 0 10px;font-family:Georgia,serif;"><?= htmlspecialchars($popup['title']) ?></h3>
<p style="color:#cbd5e1;font-size:15px;margin:0 0 20px;font-family:Georgia,serif;"><?= htmlspecialchars($popup['subtitle']) ?></p>
<a href="<?= htmlspecialchars($popup['button_url']) ?>" target="_blank" style="display:block;background:#229ED9;color:#fff;text-decoration:none;font-weight:700;font-size:15px;padding:14px;border-radius:12px;">✈ <?= htmlspecialchars($popup['button_text']) ?></a>
</div>
</div>
</div>
<script>
(function(){var H=<?= (int)$popup['show_again_hours'] ?>;try{var l=localStorage.getItem('sw_promo_seen');if(H>0&&l&&(Date.now()-parseInt(l))<H*3600000)return;}catch(e){}setTimeout(function(){var m=document.getElementById('promo-modal');if(m)m.style.display='flex';},800);})();
function closePromo(){document.getElementById('promo-modal').style.display='none';try{localStorage.setItem('sw_promo_seen',Date.now().toString())}catch(e){}}
document.addEventListener('click',function(e){if(e.target.id==='promo-modal')closePromo()});
</script>
<?php endif; ?>

<script>
function toggleDrawer(){
  var d = document.getElementById('drawer');
  var o = document.getElementById('drawer-overlay');
  if(!d || !o) return;
  if(d.classList.contains('open')){
    d.classList.remove('open');
    o.classList.remove('show');
    document.body.style.overflow = '';
  } else {
    d.classList.add('open');
    o.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
}
</script>

</body></html>
