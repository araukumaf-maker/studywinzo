<?php
/**
 * Premium Admin Components
 * Include this at top of every admin page after requireAdmin()
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$settings = getJSON('settings.json', ['logo'=>'', 'institution_name'=>'StudyWinzo']);
$popup = getJSON('popup.json', ['enabled'=>false]);
$pageTitles = [
    'index.php' => ['Dashboard', 'Overview of your platform', 'ph-squares-four'],
    'institutions.php' => ['Institutions', 'Manage coaching institutes', 'ph-buildings'],
    'manage.php' => ['Content Manager', 'Batches · Subjects · Chapters', 'ph-books'],
    'popup-admin.php' => ['Popup Manager', 'Marketing & announcements', 'ph-megaphone'],
    'settings-admin.php' => ['Settings', 'Platform configuration', 'ph-gear'],
];
$pageInfo = $pageTitles[$currentPage] ?? ['Admin', 'Management panel', 'ph-shield'];
$initial = strtoupper(substr($settings['institution_name'] ?? 'SW', 0, 1));

function renderPremiumCSS(){ ?>
<style>
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
body{font-family:'Inter',-apple-system,sans-serif;background:#050810;color:#e2e8f0;margin:0;min-height:100vh;overflow-x:hidden}
.bg-orb{position:fixed;border-radius:50%;filter:blur(80px);opacity:.25;pointer-events:none;z-index:0}
.bg-orb-1{top:-150px;left:-150px;width:500px;height:500px;background:radial-gradient(circle,#3b82f6,transparent 70%)}
.bg-orb-2{bottom:-150px;right:-150px;width:500px;height:500px;background:radial-gradient(circle,#7c3aed,transparent 70%)}
.bg-orb-3{top:40%;left:50%;transform:translateX(-50%);width:600px;height:400px;background:radial-gradient(circle,#06b6d4,transparent 70%);opacity:.1}

/* Sidebar */
.adm-sidebar{position:fixed;top:0;left:0;bottom:0;width:270px;background:linear-gradient(180deg,rgba(11,18,32,.98),rgba(7,11,20,.98));backdrop-filter:blur(20px);border-right:1px solid rgba(59,130,246,.15);z-index:100;transform:translateX(-100%);transition:transform .35s cubic-bezier(.4,0,.2,1);overflow-y:auto;box-shadow:8px 0 40px rgba(0,0,0,.5)}
.adm-sidebar.open{transform:translateX(0)}
@media(min-width:1024px){.adm-sidebar{transform:translateX(0)}.adm-main{margin-left:270px}}
.adm-sidebar::-webkit-scrollbar{width:4px}
.adm-sidebar::-webkit-scrollbar-thumb{background:rgba(59,130,246,.3);border-radius:4px}
.adm-brand{padding:24px 22px;display:flex;align-items:center;gap:12px;border-bottom:1px solid rgba(59,130,246,.12)}
.adm-brand-logo{width:48px;height:48px;border-radius:14px;background:#fff;border:2px solid #3b82f6;overflow:hidden;display:flex;align-items:center;justify-content:center;flex-shrink:0;padding:5px;box-shadow:0 4px 16px rgba(59,130,246,.3)}
.adm-brand-logo img{width:100%;height:100%;object-fit:contain}
.adm-brand-text{min-width:0}
.adm-brand-name{font-size:14.5px;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.adm-brand-sub{font-size:10px;color:#60a5fa;font-weight:700;letter-spacing:1.5px;margin-top:3px}
.adm-nav{padding:14px 12px}
.adm-section{font-size:9.5px;font-weight:800;color:#334155;letter-spacing:2px;padding:16px 14px 8px;text-transform:uppercase}
.adm-nav-item{display:flex;align-items:center;gap:13px;padding:11px 14px;border-radius:12px;color:#94a3b8;text-decoration:none;font-size:13.5px;font-weight:500;margin-bottom:2px;transition:all .2s;position:relative}
.adm-nav-item:hover{background:rgba(59,130,246,.08);color:#e2e8f0;transform:translateX(3px)}
.adm-nav-item.active{background:linear-gradient(135deg,rgba(37,99,235,.25),rgba(59,130,246,.12));color:#fff;font-weight:700;border:1px solid rgba(59,130,246,.3);box-shadow:0 4px 16px rgba(37,99,235,.2)}
.adm-nav-item.active::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:60%;background:linear-gradient(180deg,#60a5fa,#2563eb);border-radius:0 4px 4px 0}
.adm-nav-icon{font-size:19px;width:20px;text-align:center;color:#64748b;flex-shrink:0;transition:all .2s;display:inline-flex;align-items:center;justify-content:center}
.adm-nav-item.active .adm-nav-icon{color:#60a5fa;filter:drop-shadow(0 0 6px rgba(96,165,250,.5))}
.adm-nav-item:hover .adm-nav-icon{color:#60a5fa}
.adm-nav-badge{margin-left:auto;padding:2px 8px;border-radius:10px;font-size:9px;font-weight:800;background:linear-gradient(135deg,#10b981,#059669);color:#fff}
.adm-nav-badge.off{background:linear-gradient(135deg,#ef4444,#dc2626)}
.adm-sidebar-footer{padding:16px 22px;border-top:1px solid rgba(59,130,246,.12);text-align:center}
.adm-sidebar-footer p{margin:0 0 2px;font-size:10px;color:#334155}
.adm-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:99;display:none;opacity:0;transition:opacity .3s}
.adm-overlay.show{display:block;opacity:1}

/* Header */
.adm-header{position:sticky;top:0;z-index:50;background:rgba(5,8,16,.85);backdrop-filter:blur(20px);border-bottom:1px solid rgba(59,130,246,.12);padding:16px 22px}
.adm-header-inner{max-width:1300px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:14px}
.adm-header-left{display:flex;align-items:center;gap:14px;min-width:0}
.adm-burger{background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.2);width:44px;height:44px;border-radius:12px;color:#60a5fa;font-size:22px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s}
.adm-burger:hover{background:rgba(59,130,246,.2)}
@media(min-width:1024px){.adm-burger{display:none}}
.adm-title-wrap{min-width:0}
.adm-page-title{font-size:19px;font-weight:800;color:#fff;margin:0;display:flex;align-items:center;gap:10px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.adm-page-sub{font-size:11.5px;color:#64748b;margin:3px 0 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.adm-header-right{display:flex;align-items:center;gap:10px;flex-shrink:0}
.adm-status{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:20px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);font-size:11px;font-weight:700;color:#4ade80}
.adm-status-dot{width:6px;height:6px;border-radius:50%;background:#4ade80;box-shadow:0 0 8px #4ade80;animation:pulse-dot 2s infinite}
@keyframes pulse-dot{0%,100%{opacity:1}50%{opacity:.5}}
.adm-logout{padding:8px 16px;border-radius:20px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#f87171;font-size:11.5px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .2s}
.adm-logout:hover{background:rgba(239,68,68,.2)}

/* Main */
.adm-main{position:relative;z-index:1;min-height:100vh}
.adm-content{max-width:1300px;margin:0 auto;padding:28px 22px;position:relative;z-index:1}

/* Cards */
.premium-card{background:linear-gradient(145deg,rgba(15,23,42,.7),rgba(11,18,32,.5));backdrop-filter:blur(20px);border:1px solid rgba(59,130,246,.15);border-radius:20px;padding:24px;position:relative;overflow:hidden;transition:all .25s}
.premium-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,#3b82f6,transparent);opacity:0;transition:opacity .3s}
.premium-card:hover{border-color:rgba(59,130,246,.35);box-shadow:0 20px 40px -12px rgba(37,99,235,.25);transform:translateY(-2px)}
.premium-card:hover::before{opacity:.6}

/* Stats */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:28px}
.stat-premium{background:linear-gradient(145deg,rgba(15,23,42,.8),rgba(11,18,32,.5));border:1px solid rgba(59,130,246,.12);border-radius:18px;padding:20px;position:relative;overflow:hidden;transition:all .25s}
.stat-premium::after{content:'';position:absolute;top:-30px;right:-30px;width:100px;height:100px;background:radial-gradient(circle,currentColor,transparent 70%);opacity:.08;pointer-events:none}
.stat-premium:hover{transform:translateY(-3px);border-color:currentColor;box-shadow:0 15px 30px -10px currentColor}
.stat-head{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px}
.stat-label{font-size:10px;color:#64748b;font-weight:800;letter-spacing:1.2px;text-transform:uppercase}
.stat-icon-box{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px}
.stat-value{font-size:34px;font-weight:900;color:#fff;line-height:1;letter-spacing:-1px}
.stat-note{font-size:11px;color:#64748b;margin-top:6px}

/* Action cards */
.action-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px}
.action-premium{background:linear-gradient(145deg,rgba(15,23,42,.75),rgba(11,18,32,.55));backdrop-filter:blur(20px);border:1px solid rgba(59,130,246,.15);border-radius:20px;padding:24px;text-decoration:none;color:inherit;display:block;transition:all .25s;position:relative;overflow:hidden}
.action-premium::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,transparent,currentColor,transparent);opacity:.4}
.action-premium:hover{transform:translateY(-4px);border-color:currentColor;box-shadow:0 25px 50px -12px currentColor}
.action-icon-box{width:58px;height:58px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:28px;margin-bottom:16px}
.action-title{font-size:16px;font-weight:800;color:#fff;margin:0 0 6px}
.action-desc{font-size:12.5px;color:#94a3b8;line-height:1.5;margin:0}
.action-arrow{margin-top:16px;font-size:11px;font-weight:800;display:inline-flex;align-items:center;gap:6px}

/* Buttons */
.btn-premium{background:linear-gradient(135deg,#2563eb,#3b82f6);color:#fff;padding:12px 24px;border-radius:12px;font-weight:700;font-size:13.5px;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:8px;text-decoration:none;transition:all .2s;box-shadow:0 8px 20px rgba(37,99,235,.35)}
.btn-premium:hover{transform:translateY(-2px);box-shadow:0 12px 28px rgba(37,99,235,.5)}
.btn-premium:active{transform:scale(.98)}
.btn-green{background:linear-gradient(135deg,#10b981,#059669);box-shadow:0 8px 20px rgba(16,185,129,.35)}
.btn-purple{background:linear-gradient(135deg,#7c3aed,#a855f7);box-shadow:0 8px 20px rgba(124,58,237,.35)}
.btn-orange{background:linear-gradient(135deg,#f97316,#ea580c);box-shadow:0 8px 20px rgba(249,115,22,.35)}
.btn-pink{background:linear-gradient(135deg,#ec4899,#db2777);box-shadow:0 8px 20px rgba(236,72,153,.35)}
.btn-gray{background:linear-gradient(135deg,#334155,#1e293b);box-shadow:0 6px 14px rgba(0,0,0,.3)}
.btn-sm{padding:8px 16px;font-size:12px;box-shadow:none}
.btn-sm:hover{box-shadow:0 6px 14px rgba(37,99,235,.35)}
.btn-danger{background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.3);padding:8px 14px;border-radius:10px;font-size:12px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .2s;cursor:pointer}
.btn-danger:hover{background:rgba(239,68,68,.2)}

/* Form inputs */
.form-premium{width:100%;background:rgba(7,11,20,.8);border:1.5px solid rgba(59,130,246,.2);color:#fff;border-radius:12px;padding:13px 16px;font-size:14px;font-family:inherit;transition:all .2s;outline:none}
.form-premium:focus{border-color:#3b82f6;box-shadow:0 0 0 4px rgba(37,99,235,.15);background:rgba(11,18,32,.95)}
.form-premium::placeholder{color:#475569}
.label-premium{display:block;font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:.8px;text-transform:uppercase;margin-bottom:8px}

/* Alert */
.alert-premium{padding:14px 18px;border-radius:14px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:10px;animation:slideIn .3s}
.alert-success{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);color:#4ade80}
.alert-error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#f87171}
@keyframes slideIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}

/* Section header */
.section-header{display:flex;align-items:center;justify-content:space-between;margin:0 0 16px;gap:12px}
.section-title{font-size:16px;font-weight:800;color:#fff;margin:0;display:flex;align-items:center;gap:10px}
.section-title i{font-size:20px;color:#60a5fa}
.section-count{font-size:12px;color:#64748b;font-weight:600}

/* Empty */
.empty-premium{padding:60px 20px;text-align:center;background:linear-gradient(145deg,rgba(15,23,42,.5),rgba(11,18,32,.3));border:1px dashed rgba(59,130,246,.2);border-radius:20px}
.empty-premium .empty-icon{font-size:56px;opacity:.3;margin-bottom:14px}
.empty-premium h3{color:#94a3b8;font-size:15px;font-weight:700;margin:0 0 6px}
.empty-premium p{color:#64748b;font-size:13px;margin:0}

/* Skeleton shimmer */
.shimmer{background:linear-gradient(90deg,rgba(59,130,246,.05) 25%,rgba(59,130,246,.15) 50%,rgba(59,130,246,.05) 75%);background-size:200% 100%;animation:shimmer 1.5s infinite}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
</style>
<?php }
?>

<?php
function renderPremiumHeader($pageInfo, $settings, $popup, $currentPage){ ?>
<div class="bg-orb bg-orb-1"></div>
<div class="bg-orb bg-orb-2"></div>
<div class="bg-orb bg-orb-3"></div>

<div class="adm-overlay" id="admOverlay" onclick="toggleAdmSidebar()"></div>

<aside class="adm-sidebar" id="admSidebar">
<div class="adm-brand">
<div class="adm-brand-logo">
<?php if (!empty($settings['logo'])): ?>
<img src="uploads/logos/<?= htmlspecialchars($settings['logo']) ?>"/>
<?php else: ?>
<span style="font-weight:900;color:#3b82f6;font-size:18px">SW</span>
<?php endif; ?>
</div>
<div class="adm-brand-text">
<div class="adm-brand-name"><?= htmlspecialchars($settings['institution_name'] ?? 'StudyWinzo') ?></div>
<div class="adm-brand-sub">ADMIN PANEL</div>
</div>
</div>

<nav class="adm-nav">
<div class="adm-section">Main</div>
<a href="index.php" class="adm-nav-item <?= $currentPage==='index.php'?'active':'' ?>">
<i class="ph-bold ph-squares-four adm-nav-icon"></i>
<span>Dashboard</span>
</a>

<div class="adm-section">Content</div>
<a href="batches.php" class="adm-nav-item <?= $currentPage==='batches.php'?'active':'' ?>">
<i class="ph-bold ph-stack adm-nav-icon"></i>
<span>Batch Manager</span>
</a>
<a href="institutions.php" class="adm-nav-item <?= $currentPage==='institutions.php'?'active':'' ?>">
<i class="ph-bold ph-buildings adm-nav-icon"></i>
<span>Institutions</span>
</a>
<a href="manage.php" class="adm-nav-item <?= $currentPage==='manage.php'?'active':'' ?>">
<i class="ph-bold ph-books adm-nav-icon"></i>
<span>Content Manager</span>
</a>

<div class="adm-section">Marketing</div>
<a href="popup-admin.php" class="adm-nav-item <?= $currentPage==='popup-admin.php'?'active':'' ?>">
<i class="ph-bold ph-megaphone adm-nav-icon"></i>
<span>Popup Manager</span>
<span class="adm-nav-badge <?= !empty($popup['enabled'])?'':'off' ?>"><?= !empty($popup['enabled'])?'ON':'OFF' ?></span>
</a>

<div class="adm-section">System</div>
<a href="settings-admin.php" class="adm-nav-item <?= $currentPage==='settings-admin.php'?'active':'' ?>">
<i class="ph-bold ph-gear adm-nav-icon"></i>
<span>Settings</span>
</a>
<a href="api.php?action=all" target="_blank" class="adm-nav-item">
<i class="ph-bold ph-code adm-nav-icon"></i>
<span>Public API</span>
</a>

<div class="adm-section">Account</div>
<a href="../index.php" target="_blank" class="adm-nav-item">
<i class="ph-bold ph-globe adm-nav-icon"></i>
<span>View Site</span>
</a>
<a href="logout.php" class="adm-nav-item" style="color:#f87171">
<i class="ph-bold ph-sign-out adm-nav-icon" style="color:#f87171"></i>
<span>Logout</span>
</a>
</nav>

<div class="adm-sidebar-footer">
<p>© 2026 <?= htmlspecialchars($settings['institution_name'] ?? 'StudyWinzo') ?></p>
<p>Admin Panel v2.0</p>
</div>
</aside>

<header class="adm-header">
<div class="adm-header-inner">
<div class="adm-header-left">
<button class="adm-burger" onclick="toggleAdmSidebar()">
<i class="ph-bold ph-list"></i>
</button>
<div class="adm-title-wrap">
<h1 class="adm-page-title">
<i class="ph-bold <?= $pageInfo[2] ?>" style="color:#60a5fa"></i>
<?= htmlspecialchars($pageInfo[0]) ?>
</h1>
<p class="adm-page-sub"><?= htmlspecialchars($pageInfo[1]) ?></p>
</div>
</div>
<div class="adm-header-right">
<span class="adm-status"><span class="adm-status-dot"></span> ONLINE</span>
<a href="logout.php" class="adm-logout"><i class="ph-bold ph-sign-out"></i> Logout</a>
</div>
</div>
</header>

<script>
function toggleAdmSidebar(){
  var s = document.getElementById('admSidebar');
  var o = document.getElementById('admOverlay');
  if(!s || !o) return;
  if(s.classList.contains('open')){
    s.classList.remove('open'); o.classList.remove('show');
    document.body.style.overflow = '';
  } else {
    s.classList.add('open'); o.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
}
</script>
<?php } ?>

<?php
/**
 * Premium File Upload Component
 * Usage: renderFileUpload('input_name', 'Label text', 'image/*', 'accepted formats hint')
 */
function renderFileUpload($name, $label, $accept = 'image/*', $hint = 'PNG, JPG, SVG, WEBP · max 5MB', $preview = ''){ ?>
<div class="upload-premium" data-name="<?= htmlspecialchars($name) ?>">
<label class="upload-label"><?= htmlspecialchars($label) ?></label>
<label class="upload-drop" onclick="this.querySelector('input').click()">
<input type="file" name="<?= htmlspecialchars($name) ?>" accept="<?= htmlspecialchars($accept) ?>" onchange="handleUpload(this)" style="display:none"/>
<div class="upload-content">
<div class="upload-icon">
<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
<polyline points="17 8 12 3 7 8"/>
<line x1="12" y1="3" x2="12" y2="15"/>
</svg>
</div>
<div class="upload-text">
<div class="upload-title">Click to upload <span style="color:#60a5fa">or drag & drop</span></div>
<div class="upload-hint"><?= htmlspecialchars($hint) ?></div>
</div>
</div>
<div class="upload-preview" style="display:none">
<img class="upload-preview-img" src="" alt=""/>
<div class="upload-preview-info">
<div class="upload-preview-name"></div>
<div class="upload-preview-size"></div>
</div>
<button type="button" class="upload-remove" onclick="event.stopPropagation();removeUpload(this)" title="Remove">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
</button>
</div>
</label>
<?php if (!empty($preview)): ?>
<div class="upload-existing">
<span class="upload-existing-label">Current:</span>
<img src="<?= htmlspecialchars($preview) ?>" class="upload-existing-img" alt=""/>
</div>
<?php endif; ?>
</div>
<script>
function handleUpload(input){
  var wrap = input.closest('.upload-premium');
  var drop = wrap.querySelector('.upload-drop');
  var content = wrap.querySelector('.upload-content');
  var preview = wrap.querySelector('.upload-preview');
  var img = wrap.querySelector('.upload-preview-img');
  var name = wrap.querySelector('.upload-preview-name');
  var size = wrap.querySelector('.upload-preview-size');

  if (!input.files || !input.files[0]) return;
  var f = input.files[0];

  // Check size (5MB)
  if (f.size > 5 * 1024 * 1024) {
    alert('File too large. Max 5MB allowed.');
    input.value = '';
    return;
  }

  name.textContent = f.name;
  size.textContent = (f.size / 1024).toFixed(1) + ' KB';

  if (f.type.startsWith('image/')) {
    var reader = new FileReader();
    reader.onload = function(e){ img.src = e.target.result; };
    reader.readAsDataURL(f);
    img.style.display = 'block';
  } else {
    img.src = '';
    img.style.display = 'none';
  }

  content.style.display = 'none';
  preview.style.display = 'flex';
  drop.classList.add('has-file');
}
function removeUpload(btn){
  var wrap = btn.closest('.upload-premium');
  var input = wrap.querySelector('input[type=file]');
  input.value = '';
  wrap.querySelector('.upload-content').style.display = 'flex';
  wrap.querySelector('.upload-preview').style.display = 'none';
  wrap.querySelector('.upload-drop').classList.remove('has-file');
}
</script>
<?php } ?>

<style>
.upload-premium{margin-bottom:4px}
.upload-label{display:block;font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:.8px;text-transform:uppercase;margin-bottom:8px}
.upload-drop{display:block;background:linear-gradient(145deg,rgba(15,23,42,.6),rgba(11,18,32,.4));border:2px dashed rgba(59,130,246,.3);border-radius:16px;padding:22px;cursor:pointer;transition:all .25s;position:relative;overflow:hidden}
.upload-drop:hover{border-color:#3b82f6;background:linear-gradient(145deg,rgba(59,130,246,.08),rgba(37,99,235,.04));transform:translateY(-1px)}
.upload-drop.has-file{border-style:solid;border-color:rgba(16,185,129,.5);background:linear-gradient(145deg,rgba(16,185,129,.08),rgba(5,150,105,.04))}
.upload-content{display:flex;align-items:center;gap:16px;pointer-events:none}
.upload-icon{width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,rgba(59,130,246,.15),rgba(37,99,235,.08));border:1px solid rgba(59,130,246,.25);display:flex;align-items:center;justify-content:center;color:#60a5fa;flex-shrink:0;transition:all .25s}
.upload-drop:hover .upload-icon{background:linear-gradient(135deg,rgba(59,130,246,.25),rgba(37,99,235,.15));transform:scale(1.05) rotate(-3deg)}
.upload-text{flex:1;min-width:0}
.upload-title{font-size:14px;font-weight:700;color:#e2e8f0;margin-bottom:3px}
.upload-hint{font-size:11.5px;color:#64748b}
.upload-preview{display:flex;align-items:center;gap:14px;pointer-events:none;position:relative}
.upload-preview-img{width:64px;height:64px;border-radius:12px;object-fit:cover;border:2px solid rgba(16,185,129,.4);background:#fff;flex-shrink:0}
.upload-preview-info{flex:1;min-width:0}
.upload-preview-name{font-size:13.5px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.upload-preview-size{font-size:11.5px;color:#34d399;margin-top:3px;font-weight:600}
.upload-remove{position:absolute;top:-10px;right:-10px;width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#ef4444,#dc2626);border:2px solid #050810;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;pointer-events:auto;transition:all .2s;box-shadow:0 4px 12px rgba(239,68,68,.4)}
.upload-remove:hover{transform:scale(1.1)}
.upload-existing{display:flex;align-items:center;gap:10px;margin-top:10px;padding:8px 12px;background:rgba(59,130,246,.05);border:1px solid rgba(59,130,246,.15);border-radius:10px}
.upload-existing-label{font-size:10.5px;color:#64748b;font-weight:700;letter-spacing:.5px;text-transform:uppercase}
.upload-existing-img{width:36px;height:36px;border-radius:8px;object-fit:cover;background:#fff;padding:2px;border:1px solid rgba(59,130,246,.2)}
</style>
