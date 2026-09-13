<?php
require_once 'config.php';
requireAdmin();
require_once '_upload_ui.php';
$institutions = getJSON('institutions.json', []);
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0"/>
<title>Batch Manager</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<style>
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent;font-family:'Inter',sans-serif}
body{background:#070b14;color:#e2e8f0;margin:0;padding-bottom:80px;min-height:100vh}

/* Background glows */
.orb{position:fixed;border-radius:50%;filter:blur(80px);opacity:.15;pointer-events:none;z-index:0}
.orb-1{top:-100px;left:-100px;width:400px;height:400px;background:#3b82f6}
.orb-2{bottom:-100px;right:-100px;width:400px;height:400px;background:#7c3aed}

/* Header */
.hdr{background:rgba(7,11,20,.85);backdrop-filter:blur(20px);border-bottom:1px solid rgba(59,130,246,.15);padding:16px 20px;position:sticky;top:0;z-index:30;display:flex;align-items:center;gap:14px}
.hdr h1{margin:0;font-size:17px;font-weight:800;color:#fff;flex:1;display:flex;align-items:center;gap:10px}
.hdr .back{width:40px;height:40px;background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.25);border-radius:11px;display:flex;align-items:center;justify-content:center;color:#60a5fa;text-decoration:none;flex-shrink:0}

.wrap{max-width:800px;margin:0 auto;padding:18px 16px;position:relative;z-index:1}

/* Institution picker */
.inst-scroll{display:flex;gap:10px;overflow-x:auto;padding:4px 2px 14px;scrollbar-width:none}
.inst-scroll::-webkit-scrollbar{display:none}
.inst-pill{background:linear-gradient(145deg,rgba(15,23,42,.8),rgba(11,18,32,.5));border:1.5px solid rgba(59,130,246,.2);border-radius:14px;padding:12px 16px;display:flex;align-items:center;gap:10px;cursor:pointer;transition:all .2s;flex-shrink:0;min-width:130px}
.inst-pill:hover{border-color:rgba(59,130,246,.5)}
.inst-pill.active{border-color:#10b981;background:linear-gradient(145deg,rgba(16,185,129,.15),rgba(5,150,105,.05));box-shadow:0 8px 24px -8px rgba(16,185,129,.4)}
.inst-pill-logo{width:36px;height:36px;border-radius:10px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;padding:2px}
.inst-pill-logo img{width:100%;height:100%;object-fit:contain}
.inst-pill-logo span{color:#3b82f6;font-weight:900;font-size:15px}
.inst-pill-name{font-size:12.5px;font-weight:700;color:#fff;white-space:nowrap}
.inst-pill-sub{font-size:10px;color:#64748b;margin-top:1px}

/* Section */
.sec-title{font-size:11px;font-weight:800;color:#60a5fa;letter-spacing:1.2px;text-transform:uppercase;margin:18px 0 12px;display:flex;align-items:center;gap:8px}
.sec-title::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,rgba(59,130,246,.3),transparent)}

/* Batch cards */
.batch-grid{display:grid;gap:12px}
.batch-item{background:linear-gradient(145deg,rgba(15,23,42,.7),rgba(11,18,32,.5));border:1px solid rgba(59,130,246,.15);border-radius:16px;padding:14px;transition:all .25s;position:relative;overflow:hidden}
.batch-item::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--batch-color);opacity:.7}
.batch-item:hover{border-color:rgba(59,130,246,.4);transform:translateY(-2px);box-shadow:0 12px 32px -12px rgba(37,99,235,.3)}
.batch-head{display:flex;align-items:center;gap:14px}
.batch-thumb{width:66px;height:66px;border-radius:14px;background:#fff;border:2px solid var(--batch-color);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;padding:3px}
.batch-thumb img{width:100%;height:100%;object-fit:cover;border-radius:11px}
.batch-thumb span{font-weight:900;font-size:22px;color:var(--batch-color)}
.batch-info{flex:1;min-width:0}
.batch-name{font-size:15px;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.batch-tag{display:inline-block;font-size:10px;font-weight:700;color:#60a5fa;background:rgba(59,130,246,.15);border:1px solid rgba(59,130,246,.25);padding:2px 8px;border-radius:8px;margin-top:4px}
.batch-count{font-size:11px;color:#64748b;margin-top:4px}
.batch-actions{display:flex;gap:4px;flex-shrink:0;flex-wrap:wrap;justify-content:flex-end;max-width:170px}
.icon-btn{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;cursor:pointer;border:1px solid;background:transparent;transition:all .15s;padding:0}
.icon-btn i{font-size:16px}
.icon-btn.edit{color:#60a5fa;border-color:rgba(59,130,246,.3);background:rgba(59,130,246,.08)}
.icon-btn.edit:hover{background:rgba(59,130,246,.2)}
.icon-btn.del{color:#f87171;border-color:rgba(239,68,68,.3);background:rgba(239,68,68,.08)}
.icon-btn.del:hover{background:rgba(239,68,68,.2)}
.icon-btn.open{color:#34d399;border-color:rgba(16,185,129,.3);background:rgba(16,185,129,.08)}
.icon-btn.open:hover{background:rgba(16,185,129,.2)}
.icon-btn.preview{color:#fbbf24;border-color:rgba(251,191,36,.3);background:rgba(251,191,36,.08);text-decoration:none}
.icon-btn.preview:hover{background:rgba(251,191,36,.2)}

/* Chapters expandable */
.chapters-panel{margin-top:14px;padding-top:14px;border-top:1px dashed rgba(59,130,246,.2);display:none}
.chapters-panel.show{display:block}
.chapter-chips{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px}
.chip{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);color:#34d399;padding:6px 12px;border-radius:20px;font-size:11.5px;font-weight:700;display:flex;align-items:center;gap:6px}
.chip .x{cursor:pointer;color:#f87171;font-size:14px;line-height:1}
.chip .x:hover{color:#ef4444}
.chip-empty{font-size:12px;color:#64748b;padding:8px 0;font-style:italic}
.add-chapter-row{display:flex;gap:8px}
.add-chapter-row input{flex:1;background:#070b14;border:1.5px solid rgba(59,130,246,.25);color:#fff;border-radius:10px;padding:10px 14px;font-size:13px;font-family:inherit;outline:none}
.add-chapter-row input:focus{border-color:#3b82f6}
.add-chapter-row button{background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;padding:0 16px;border-radius:10px;font-weight:700;cursor:pointer;font-size:13px;display:flex;align-items:center;gap:6px;box-shadow:0 4px 12px rgba(16,185,129,.3)}
.upload-btn{margin-top:10px;width:100%;background:linear-gradient(135deg,#2563eb,#3b82f6);color:#fff;border:none;padding:11px;border-radius:10px;font-weight:700;font-size:12.5px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;box-shadow:0 6px 16px rgba(37,99,235,.3)}

/* Empty */
.empty{text-align:center;padding:60px 20px;color:#64748b}
.empty i{font-size:56px;opacity:.25;display:block;margin-bottom:14px}

/* FAB */
.fab{position:fixed;bottom:24px;right:20px;width:58px;height:58px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;cursor:pointer;font-size:26px;display:flex;align-items:center;justify-content:center;box-shadow:0 12px 32px -8px rgba(16,185,129,.6);z-index:40;transition:transform .2s}
.fab:active{transform:scale(.92)}

/* Modal */
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.88);backdrop-filter:blur(6px);z-index:100;display:none;align-items:flex-end;justify-content:center;padding:0}
.modal-bg.show{display:flex;animation:fadeIn .2s}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.modal{background:#0b1220;border:1px solid rgba(59,130,246,.25);border-radius:24px 24px 0 0;padding:22px 20px 28px;width:100%;max-width:500px;max-height:92vh;overflow-y:auto;animation:slideUp .3s}
@keyframes slideUp{from{transform:translateY(40px);opacity:0}to{transform:translateY(0);opacity:1}}
.modal-handle{width:40px;height:4px;background:#374151;border-radius:2px;margin:0 auto 18px}
.modal h2{margin:0 0 18px;font-size:17px;font-weight:800;color:#fff;display:flex;align-items:center;gap:10px}
.lbl{display:block;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
.inp{width:100%;background:#070b14;border:1.5px solid rgba(59,130,246,.25);color:#fff;border-radius:11px;padding:12px 14px;font-size:14px;font-family:inherit;outline:none;margin-bottom:14px}
.inp:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(37,99,235,.15)}
.img-upload{background:#070b14;border:2px dashed rgba(59,130,246,.3);border-radius:14px;padding:18px;text-align:center;cursor:pointer;display:block;transition:all .2s;margin-bottom:14px}
.img-upload:hover{border-color:#3b82f6;background:rgba(59,130,246,.05)}
.img-upload.has-img{border-style:solid;border-color:rgba(16,185,129,.5);background:rgba(16,185,129,.05);padding:12px}
.img-upload input{display:none}
.img-ph{width:64px;height:64px;border-radius:16px;background:rgba(59,130,246,.15);border:1px solid rgba(59,130,246,.3);color:#60a5fa;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:28px}
.img-upload img{width:80px;height:80px;border-radius:12px;object-fit:cover;margin:0 auto 8px;display:block;border:2px solid #10b981}
.img-hint{font-size:11.5px;color:#94a3b8}
.btn-full{width:100%;padding:14px;border-radius:12px;border:none;font-weight:800;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px}
.btn-save{background:linear-gradient(135deg,#10b981,#059669);color:#fff;box-shadow:0 8px 24px -8px rgba(16,185,129,.5)}
.btn-cancel{background:#1e293b;color:#cbd5e1;margin-top:8px}

/* Toast */
.toast{position:fixed;top:20px;left:50%;transform:translateX(-50%) translateY(-100px);background:#111827;border:1px solid rgba(16,185,129,.5);color:#fff;padding:12px 20px;border-radius:30px;font-size:13px;font-weight:700;z-index:200;display:flex;align-items:center;gap:8px;box-shadow:0 12px 32px rgba(0,0,0,.4);transition:transform .3s}
.toast.show{transform:translateX(-50%) translateY(0)}
.toast.err{border-color:rgba(239,68,68,.5)}

.color-row{display:flex;gap:8px;align-items:center;margin-bottom:14px}
.color-row input[type=color]{width:52px;height:46px;border-radius:11px;border:1.5px solid rgba(59,130,246,.25);background:transparent;cursor:pointer;padding:0}
.color-presets{display:flex;gap:6px;flex-wrap:wrap}
.color-preset{width:32px;height:32px;border-radius:8px;border:2px solid transparent;cursor:pointer;padding:0}
.color-preset.active{border-color:#fff}
</style>
  <link rel="stylesheet" href="../assets/premium.css">
</head>
<body>

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="hdr">
<a href="index.php" class="back"><i class="ph-bold ph-arrow-left"></i></a>
<h1><i class="ph-bold ph-stack" style="color:#3b82f6"></i> Batch Manager</h1>
</div>

<div class="wrap">

<!-- INSTITUTIONS -->
<div class="sec-title">🏛️ Institution चुनो</div>
<div class="inst-scroll" id="instScroll">
<div class="inst-pill active" data-id="" onclick="selectInst(this, '')">
<div class="inst-pill-logo"><i class="ph-bold ph-globe" style="color:#3b82f6"></i></div>
<div>
<div class="inst-pill-name">All</div>
<div class="inst-pill-sub">सभी batches</div>
</div>
</div>
<?php foreach ($institutions as $ins): ?>
<div class="inst-pill" data-id="<?= htmlspecialchars($ins['id']) ?>" onclick="selectInst(this, '<?= htmlspecialchars($ins['id']) ?>')">
<div class="inst-pill-logo">
<?php if (!empty($ins['logo'])): ?>
<img src="<?= htmlspecialchars(mediaUrl($ins['logo'], 'institutions')) ?>"/>
<?php else: ?>
<span><?= strtoupper(substr($ins['name'],0,1)) ?></span>
<?php endif; ?>
</div>
<div>
<div class="inst-pill-name"><?= htmlspecialchars($ins['name']) ?></div>
<div class="inst-pill-sub">Batches</div>
</div>
</div>
<?php endforeach; ?>
</div>

<!-- BATCHES -->
<div class="sec-title" id="batchTitle">📦 Batches</div>
<div class="batch-grid" id="batchGrid">
<div class="empty"><i class="ph-bold ph-stack"></i><p>Loading...</p></div>
</div>

</div>

<!-- FAB -->
<button class="fab" id="fabBtn" onclick="openBatchModal()"><i class="ph-bold ph-plus"></i></button>

<!-- BATCH MODAL -->
<div class="modal-bg" id="batchModal" onclick="if(event.target===this)closeBatchModal()">
<div class="modal">
<div class="modal-handle"></div>
<h2 id="modalTitle"><i class="ph-bold ph-stack" style="color:#3b82f6"></i> <span>New Batch</span></h2>
<form id="batchForm" onsubmit="saveBatch(event)">
<input type="hidden" name="id" value="" id="batchId"/>
<input type="hidden" name="institution_id" value="" id="batchInstId"/>

<label class="lbl">Thumbnail</label>
<?php mUpload("image", "Batch Thumbnail", "image/*", "PNG, JPG · 400×400 recommended · max 5MB", ""); ?>

<label class="lbl">Batch Name *</label>
<input type="text" name="name" id="batchName" placeholder="JEE 2026" class="inp" required/>

<label class="lbl">Tag / Category</label>
<input type="text" name="tag" id="batchTag" placeholder="Physics" class="inp"/>

<label class="lbl">Accent Color</label>
<div class="color-row">
<input type="color" name="color" id="batchColor" value="#2cee82"/>
<div class="color-presets">
<?php foreach (['#2cee82','#3b82f6','#a855f7','#ef4444','#f59e0b','#06b6d4','#ec4899'] as $c): ?>
<div class="color-preset" style="background:<?= $c ?>" onclick="pickColor('<?= $c ?>', this)"></div>
<?php endforeach; ?>
</div>
</div>

<button type="submit" class="btn-full btn-save" id="saveBtn"><i class="ph-bold ph-check"></i> Save Batch</button>
<button type="button" class="btn-full btn-cancel" onclick="closeBatchModal()">Cancel</button>
</form>
</div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"><i class="ph-bold ph-check-circle"></i> <span id="toastMsg">Saved</span></div>

<script>
var currentInst = '';
var batchCache = [];

function showToast(msg, isErr){
  var t = document.getElementById('toast');
  document.getElementById('toastMsg').textContent = msg;
  t.classList.toggle('err', !!isErr);
  t.querySelector('i').className = isErr ? 'ph-bold ph-warning-circle' : 'ph-bold ph-check-circle';
  t.classList.add('show');
  clearTimeout(t._timer);
  t._timer = setTimeout(function(){ t.classList.remove('show'); }, 2500);
}

function selectInst(el, id){
  document.querySelectorAll('.inst-pill').forEach(function(p){ p.classList.remove('active'); });
  el.classList.add('active');
  currentInst = id;
  loadBatches();
}

function loadBatches(){
  var grid = document.getElementById('batchGrid');
  grid.innerHTML = '<div class="empty"><i class="ph-bold ph-circle-notch" style="animation:spin 1s linear infinite"></i><p>Loading batches...</p></div>';
  
  fetch('batches_api.php?action=get_batches' + (currentInst ? '&institution=' + encodeURIComponent(currentInst) : ''))
    .then(r => r.json())
    .then(d => {
      if (!d.success) { grid.innerHTML = '<div class="empty"><i class="ph-bold ph-warning"></i><p>Error loading</p></div>'; return; }
      batchCache = d.data;
      renderBatches();
    });
}

function renderBatches(){
  var grid = document.getElementById('batchGrid');
  var total = batchCache.length;
  document.getElementById('batchTitle').innerHTML = '📦 Batches <span style="color:#64748b;font-weight:600;text-transform:none;letter-spacing:0">(' + total + ')</span>';
  
  if (total === 0) {
    grid.innerHTML = '<div class="empty"><i class="ph-bold ph-stack"></i><p>कोई batch नहीं — नीचे + button दबाओ</p></div>';
    return;
  }
  
  var html = '';
  batchCache.forEach(function(b){
    var color = b.color || '#2cee82';
    var imgHtml = b.image ? '<img src="' + mediaSrc(b.image, 'batches') + '"/>' : '<span>' + b.name.charAt(0).toUpperCase() + '</span>';
    
    html += '<div class="batch-item" style="--batch-color:' + color + '">';
    html += '<div class="batch-head">';
    html += '<div class="batch-thumb">' + imgHtml + '</div>';
    html += '<div class="batch-info">';
    html += '<div class="batch-name">' + escapeHtml(b.name) + '</div>';
    if (b.subject) html += '<div class="batch-tag">' + escapeHtml(b.subject) + '</div>';
    html += '<div class="batch-count">📖 ' + b._chapterCount + ' chapters</div>';
    html += '</div>';
    html += '<div class="batch-actions">';
    html += '<a href="../batch.php?id=' + b.id + '" target="_blank" class="icon-btn preview" title="Preview (User View)"><i class="ph-bold ph-eye"></i></a>';
    html += '<button class="icon-btn open" onclick="toggleChapters(\'' + b.id + '\', this)" title="Chapters"><i class="ph-bold ph-book-open"></i></button>';
    html += '<button class="icon-btn edit" onclick="editBatch(\'' + b.id + '\')" title="Edit"><i class="ph-bold ph-pencil-simple"></i></button>';
    html += '<button class="icon-btn del" onclick="deleteBatch(\'' + b.id + '\')" title="Delete"><i class="ph-bold ph-trash"></i></button>';
    html += '</div>';
    html += '</div>';
    
    // Chapters panel (hidden by default)
    html += '<div class="chapters-panel" id="ch-' + b.id + '">';
    html += '<div class="chapter-chips" id="chips-' + b.id + '">';
    if (b._chapters && b._chapters.length > 0) {
      b._chapters.forEach(function(c){
        html += '<div class="chip" data-id="' + c.id + '">' + escapeHtml(c.name) + ' <span class="x" onclick="deleteChapter(\'' + c.id + '\', \'' + b.id + '\')">×</span></div>';
      });
    } else {
      html += '<div class="chip-empty">कोई chapter नहीं</div>';
    }
    html += '</div>';
    html += '<div class="add-chapter-row">';
    html += '<input type="text" placeholder="Chapter name" id="newCh-' + b.id + '" onkeypress="if(event.key===\'Enter\')addChapter(\'' + b.id + '\')"/>';
    html += '<button onclick="addChapter(\'' + b.id + '\')"><i class="ph-bold ph-plus"></i> Add</button>';
    html += '</div>';
    html += '<button class="upload-btn" onclick="location.href=\'manage.php?batch=' + b.id + '&view=browse\'"><i class="ph-bold ph-cloud-arrow-up"></i> Upload Content</button>';
    html += '</div>';
    
    html += '</div>';
  });
  grid.innerHTML = html;
}

function escapeHtml(s){ return String(s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
function mediaSrc(value, folder){
  return /^https?:\/\//i.test(String(value || '')) ? value : 'uploads/' + folder + '/' + value;
}

function toggleChapters(bid, btn){
  var el = document.getElementById('ch-' + bid);
  var isOpen = el.classList.contains('show');
  el.classList.toggle('show');
  btn.querySelector('i').className = isOpen ? 'ph-bold ph-book-open' : 'ph-bold ph-book';
}

// BATCH MODAL
function openBatchModal(){
  if (!currentInst) { showToast('पहले institution select करो', true); return; }
  document.getElementById('modalTitle').querySelector('span').textContent = 'New Batch';
  document.getElementById('batchId').value = '';
  document.getElementById('batchInstId').value = currentInst;
  document.getElementById('batchName').value = '';
  document.getElementById('batchTag').value = '';
  document.getElementById('batchColor').value = '#2cee82';
  // Reset the shared uploader rendered by mUpload().
  if (window.mupClear) mupClear('image');
  document.getElementById('batchModal').classList.add('show');
}

function closeBatchModal(){
  document.getElementById('batchModal').classList.remove('show');
}

function editBatch(id){
  var b = batchCache.find(function(x){ return x.id === id; });
  if (!b) return;
  document.getElementById('modalTitle').querySelector('span').textContent = 'Edit Batch';
  document.getElementById('batchId').value = b.id;
  document.getElementById('batchInstId').value = b.institutionId || currentInst;
  document.getElementById('batchName').value = b.name || '';
  document.getElementById('batchTag').value = b.subject || '';
  document.getElementById('batchColor').value = b.color || '#2cee82';
  // Show an existing thumbnail inside the shared uploader preview.
  if (b.image) {
    var existingPreview = document.getElementById('mup-prev-image');
    var existingImg = document.getElementById('mup-img-image');
    var existingName = document.getElementById('mup-name-image');
    var existingSize = document.getElementById('mup-size-image');
    if (existingPreview && existingImg) {
      existingImg.src = mediaSrc(b.image, 'batches');
      existingImg.style.display = 'block';
      existingPreview.style.display = 'flex';
      if (existingName) existingName.textContent = 'Current thumbnail';
      if (existingSize) existingSize.textContent = 'Choose a new image to replace it';
    }
  } else if (window.mupClear) {
    mupClear('image');
  }
  document.getElementById('batchModal').classList.add('show');
}

function previewImg(input){
  // Kept for compatibility with older pages; mUpload() calls mupChange().
  if (window.mupChange) mupChange(input, 'image');
}

function pickColor(c, el){
  document.getElementById('batchColor').value = c;
  document.querySelectorAll('.color-preset').forEach(function(p){ p.classList.remove('active'); });
  el.classList.add('active');
}

function saveBatch(e){
  e.preventDefault();
  var form = document.getElementById('batchForm');
  var fd = new FormData(form);
  fd.append('action', 'save_batch');
  
  var btn = document.getElementById('saveBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="ph-bold ph-circle-notch" style="animation:spin 1s linear infinite"></i> Saving...';
  
  fetch('batches_api.php', {
    method: 'POST',
    body: fd,
    credentials: 'same-origin',
    headers: { 'Accept': 'application/json' }
  })
    .then(function(r){
      return r.text().then(function(text){
        var data = null;
        try { data = JSON.parse(text); } catch (parseErr) {
          if (r.status === 401 || r.redirected) throw new Error('Admin session expired. Please login again.');
          throw new Error('Server error (' + r.status + '). Please retry.');
        }
        if (!r.ok || !data.success) throw new Error(data.error || 'Thumbnail upload failed');
        return data;
      });
    })
    .then(d => {
      btn.disabled = false;
      btn.innerHTML = '<i class="ph-bold ph-check"></i> Save Batch';
      if (d.success) {
        closeBatchModal();
        showToast(d.message || 'Saved');
        loadBatches();
      } else showToast(d.error || 'Error', true);
    })
    .catch(function(err){
      btn.disabled = false;
      btn.innerHTML = '<i class="ph-bold ph-check"></i> Save Batch';
      showToast(err && err.message ? err.message : 'Upload failed. Please retry.', true);
    });
}

function deleteBatch(id){
  if (!confirm('Delete this batch + all chapters?')) return;
  var fd = new FormData();
  fd.append('action', 'delete_batch');
  fd.append('id', id);
  fetch('batches_api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => { if (d.success) { showToast('Batch deleted'); loadBatches(); } else showToast('Error', true); });
}

function addChapter(bid){
  var inp = document.getElementById('newCh-' + bid);
  var name = inp.value.trim();
  if (!name) return;
  inp.disabled = true;
  
  var fd = new FormData();
  fd.append('action', 'add_chapter');
  fd.append('batch_id', bid);
  fd.append('name', name);
  
  fetch('batches_api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      inp.disabled = false;
      if (d.success) {
        inp.value = '';
        showToast('Chapter added');
        // Update local chip
        var chips = document.getElementById('chips-' + bid);
        var emptyEl = chips.querySelector('.chip-empty');
        if (emptyEl) emptyEl.remove();
        var chip = document.createElement('div');
        chip.className = 'chip';
        chip.dataset.id = d.item.id;
        chip.innerHTML = escapeHtml(name) + ' <span class="x" onclick="deleteChapter(\'' + d.item.id + '\', \'' + bid + '\')">×</span>';
        chips.appendChild(chip);
        // Update count
        var b = batchCache.find(function(x){ return x.id === bid; });
        if (b) { b._chapterCount++; b._chapters.push(d.item); }
        var cnt = document.querySelector('#ch-' + bid).parentElement.querySelector('.batch-count');
        if (cnt) cnt.textContent = '📖 ' + b._chapterCount + ' chapters';
      } else showToast(d.error || 'Error', true);
    });
}

function deleteChapter(cid, bid){
  if (!confirm('Delete chapter?')) return;
  var fd = new FormData();
  fd.append('action', 'delete_chapter');
  fd.append('id', cid);
  fetch('batches_api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        showToast('Chapter deleted');
        var chip = document.querySelector('#chips-' + bid + ' .chip[data-id="' + cid + '"]');
        if (chip) chip.remove();
        var chips = document.getElementById('chips-' + bid);
        if (chips.children.length === 0) chips.innerHTML = '<div class="chip-empty">कोई chapter नहीं</div>';
        var b = batchCache.find(function(x){ return x.id === bid; });
        if (b) {
          b._chapterCount--;
          b._chapters = b._chapters.filter(function(c){ return c.id !== cid; });
          var cnt = document.querySelector('#ch-' + bid).parentElement.querySelector('.batch-count');
          if (cnt) cnt.textContent = '📖 ' + b._chapterCount + ' chapters';
        }
      } else showToast('Error', true);
    });
}

// Init
loadBatches();
</script>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>
</body></html>
