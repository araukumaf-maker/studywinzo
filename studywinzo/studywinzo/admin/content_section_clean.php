<?php if ($type === 'content'): 
$chapterId = $_GET['chapter'] ?? ($_POST['chapter_id'] ?? '');
?>
<?php if (!$chapterId): ?>
<div class="premium-card" style="padding:60px 20px;text-align:center">
<div style="font-size:56px;opacity:.3;margin-bottom:14px">📖</div>
<h3 style="color:#94a3b8;font-size:16px;font-weight:700;margin:0 0 6px">Select a chapter first</h3>
<p style="color:#64748b;font-size:13px;margin:0 0 20px">Chapter select करके उसमें content add करो</p>
<a href="?type=chapter&batch=<?= urlencode($batchId) ?>&subject=<?= urlencode($subjectId) ?>" class="btn btn">← Back to Chapters</a>
</div>
<?php else: ?>
<?php
$chapName = '';
foreach ($chapters as $c) if ($c['id']===$chapterId) $chapName = $c['name'];
$items = array_values(array_filter($content, fn($x)=>$x['chapterId']===$chapterId));
usort($items, fn($a,$b)=>strcmp($b['created']??'', $a['created']??''));
$isEdit = isset($_GET['edit_content']) && $editContent;
$currentCtype = $editContent['type'] ?? 'note';
$showForm = $isEdit || count($items)===0;
?>

<!-- ============ ADD CONTENT FORM ============ -->
<div class="card-premium" style="margin-bottom:24px;padding:24px">
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
<h2 style="margin:0;font-size:17px;font-weight:800;color:#fff;display:flex;align-items:center;gap:10px">
<i class="ph-bold <?= $isEdit?'ph-pencil-simple':'ph-plus-circle' ?>" style="color:#fb923c;font-size:22px"></i>
<?= $isEdit?'Edit Content':'Add New Content' ?> → <span style="color:#fb923c"><?= htmlspecialchars($chapName) ?></span>
</h2>
<?php if (!$isEdit && count($items) > 0): ?>
<button type="button" onclick="toggleAddForm()" id="toggleBtn" style="background:linear-gradient(135deg,#fb923c,#f97316);color:#fff;border:none;padding:10px 18px;border-radius:12px;font-weight:700;font-size:13px;cursor:pointer;box-shadow:0 4px 12px rgba(251,146,60,.4)">
<i class="ph-bold ph-plus"></i> Add Content
</button>
<?php endif; ?>
</div>

<form method="POST" enctype="multipart/form-data" id="addForm" style="display:<?= $showForm?'flex':'none' ?>;flex-direction:column;gap:16px">
<input type="hidden" name="action" value="save_content">
<input type="hidden" name="chapter_id" value="<?= htmlspecialchars($chapterId) ?>">
<input type="hidden" name="id" value="<?= htmlspecialchars($editContent['id'] ?? '') ?>">

<!-- Content Type -->
<div>
<label style="display:block;font-size:11px;font-weight:700;color:#94a3b8;margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px">Content Type</label>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px">
<?php foreach ([
  'note' => ['lbl'=>'Note PDF', 'ic'=>'ph-file-pdf', 'd'=>'PDF/DOC'],
  'dpp' => ['lbl'=>'DPP', 'ic'=>'ph-note-pencil', 'd'=>'Practice'],
  'video' => ['lbl'=>'Video', 'ic'=>'ph-play-circle', 'd'=>'YouTube'],
  'link' => ['lbl'=>'Link', 'ic'=>'ph-link', 'd'=>'External']
] as $k=>$t): ?>
<label class="ctype-card <?= $currentCtype===$k?'selected':'' ?>" data-type="<?= $k ?>" onclick="selectCtype('<?= $k ?>')">
<input type="radio" name="ctype" value="<?= $k ?>" <?= $currentCtype===$k?'checked':'' ?> style="display:none"/>
<div style="font-size:22px;color:<?= $currentCtype===$k?'#fb923c':'#64748b' ?>;margin-bottom:6px"><i class="ph-bold <?= $t['ic'] ?>"></i></div>
<div style="font-size:12px;font-weight:700;color:<?= $currentCtype===$k?'#fff':'#94a3b8' ?>"><?= $t['lbl'] ?></div>
<div style="font-size:9.5px;color:#64748b;margin-top:2px"><?= $t['d'] ?></div>
</label>
<?php endforeach; ?>
</div>
</div>

<!-- Title -->
<div>
<label style="display:block;font-size:11px;font-weight:700;color:#94a3b8;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Title *</label>
<input type="text" name="title" value="<?= htmlspecialchars($editContent['title'] ?? '') ?>" placeholder="e.g. Kinematics Chapter Notes" class="form-input" required/>
</div>

<!-- Description -->
<div>
<label style="display:block;font-size:11px;font-weight:700;color:#94a3b8;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Description (optional)</label>
<textarea name="description" rows="2" class="form-input" placeholder="Short description..."><?= htmlspecialchars($editContent['description'] ?? '') ?></textarea>
</div>

<!-- FILE UPLOAD — Note / DPP -->
<div id="fileBlock" style="display:<?= in_array($currentCtype,['note','dpp'])?'block':'none' ?>">
<label style="display:block;font-size:11px;font-weight:700;color:#94a3b8;margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px">📎 Upload File</label>

<div style="background:linear-gradient(145deg,rgba(15,23,42,.6),rgba(11,18,32,.4));border:2px dashed rgba(59,130,246,.3);border-radius:16px;padding:24px;text-align:center;cursor:pointer;transition:all .2s" 
     id="uploadZone"
     onclick="document.getElementById('fileInput').click()"
     ondragover="event.preventDefault();this.style.borderColor='#3b82f6';this.style.background='rgba(59,130,246,.08)'"
     ondragleave="this.style.borderColor='rgba(59,130,246,.3)';this.style.background='linear-gradient(145deg,rgba(15,23,42,.6),rgba(11,18,32,.4))'"
     ondrop="handleDrop(event)">

<input type="file" name="note_file" id="fileInput" accept=".pdf,.doc,.docx,.zip,.ppt,.pptx,.jpg,.jpeg,.png" onchange="showFile(this)" style="display:none"/>

<div id="uploadEmpty">
<div style="width:64px;height:64px;border-radius:16px;background:rgba(59,130,246,.15);border:1px solid rgba(59,130,246,.3);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;color:#60a5fa">
<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
</div>
<div style="font-size:14px;font-weight:700;color:#e2e8f0;margin-bottom:4px">Click to upload or drag & drop</div>
<div style="font-size:11.5px;color:#64748b">PDF, DOC, PPT, ZIP, Image · max 50MB</div>
</div>

<div id="uploadFilled" style="display:none">
<div style="display:flex;align-items:center;gap:14px;text-align:left;background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.3);border-radius:12px;padding:14px">
<div style="width:48px;height:48px;border-radius:12px;background:rgba(16,185,129,.15);display:flex;align-items:center;justify-content:center;color:#34d399;flex-shrink:0">
<i class="ph-bold ph-file" style="font-size:22px"></i>
</div>
<div style="flex:1;min-width:0">
<div id="fileName" style="font-size:13px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">file.pdf</div>
<div id="fileSize" style="font-size:11px;color:#34d399;font-weight:600;margin-top:2px">0 KB</div>
</div>
<button type="button" onclick="event.stopPropagation();clearFile()" style="width:32px;height:32px;border-radius:50%;background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);color:#f87171;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0">
<i class="ph-bold ph-x" style="font-size:14px"></i>
</button>
</div>
</div>

<?php if (!empty($editContent['file'])): ?>
<div style="margin-top:10px;padding:8px 12px;background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);border-radius:10px;font-size:11.5px;color:#60a5fa;display:flex;align-items:center;gap:8px">
<i class="ph-bold ph-paperclip"></i> Current: <b><?= htmlspecialchars($editContent['file']) ?></b>
</div>
<?php endif; ?>
</div>
</div>

<!-- VIDEO URL -->
<div id="videoBlock" style="display:<?= $currentCtype==='video'?'block':'none' ?>">
<label style="display:block;font-size:11px;font-weight:700;color:#94a3b8;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">🎬 YouTube Embed URL</label>
<input type="text" name="video_url" id="videoInput" value="<?= htmlspecialchars($editContent['video_url'] ?? '') ?>" placeholder="https://www.youtube.com/embed/VIDEO_ID" class="form-input"/>
<p style="font-size:11px;color:#64748b;margin:8px 0 0">💡 Regular YouTube link paste करो — auto convert हो जाएगा</p>
</div>

<!-- EXTERNAL LINK -->
<div id="linkBlock" style="display:<?= $currentCtype==='link'?'block':'none' ?>">
<label style="display:block;font-size:11px;font-weight:700;color:#94a3b8;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">🔗 External URL</label>
<input type="text" name="external_url" value="<?= htmlspecialchars($editContent['external_url'] ?? '') ?>" placeholder="https://example.com/resource" class="form-input"/>
</div>

<!-- Buttons -->
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px">
<button type="submit" style="background:linear-gradient(135deg,#fb923c,#f97316);color:#fff;border:none;padding:13px 26px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 8px 20px rgba(251,146,60,.35)">
<i class="ph-bold ph-floppy-disk"></i> <?= $isEdit?'Update Content':'Save Content' ?>
</button>
<?php if($isEdit): ?>
<a href="?type=content&batch=<?= urlencode($batchId) ?>&subject=<?= urlencode($subjectId) ?>&chapter=<?= urlencode($chapterId) ?>" style="background:#1e293b;color:#cbd5e1;padding:13px 22px;border-radius:12px;font-weight:600;font-size:13.5px;text-decoration:none;display:flex;align-items:center;gap:6px">Cancel</a>
<?php endif; ?>
</div>
</form>
</div>

<style>
.ctype-card{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:14px 8px;border:2px solid #1a2332;border-radius:14px;background:#070b14;cursor:pointer;transition:all .2s;text-align:center}
.ctype-card:hover{border-color:rgba(251,146,60,.4);background:#0a1220}
.ctype-card.selected{border-color:#fb923c;background:linear-gradient(135deg,rgba(251,146,60,.15),rgba(249,115,22,.08));box-shadow:0 0 20px -5px rgba(251,146,60,.5)}
</style>

<script>
function selectCtype(type){
  document.querySelectorAll('.ctype-card').forEach(function(x){
    x.classList.remove('selected');
    var ic = x.querySelector('i');
    var ttl = x.querySelector('div:nth-child(2)');
    if (ic) ic.style.color = '#64748b';
    if (ttl) ttl.style.color = '#94a3b8';
  });
  var el = document.querySelector('.ctype-card[data-type="'+type+'"]');
  if (el) {
    el.classList.add('selected');
    var ic = el.querySelector('i');
    var ttl = el.querySelector('div:nth-child(2)');
    if (ic) ic.style.color = '#fb923c';
    if (ttl) ttl.style.color = '#fff';
    el.querySelector('input').checked = true;
  }
  document.getElementById('fileBlock').style.display = (type==='note'||type==='dpp') ? 'block' : 'none';
  document.getElementById('videoBlock').style.display = (type==='video') ? 'block' : 'none';
  document.getElementById('linkBlock').style.display = (type==='link') ? 'block' : 'none';
}

function showFile(input){
  if (!input.files || !input.files[0]) return;
  var f = input.files[0];
  if (f.size > 50 * 1024 * 1024) { alert('Max 50MB allowed'); input.value=''; return; }
  document.getElementById('uploadEmpty').style.display = 'none';
  document.getElementById('uploadFilled').style.display = 'block';
  document.getElementById('fileName').textContent = f.name;
  document.getElementById('fileSize').textContent = (f.size/1024).toFixed(1) + ' KB';
}

function clearFile(){
  document.getElementById('fileInput').value = '';
  document.getElementById('uploadEmpty').style.display = 'block';
  document.getElementById('uploadFilled').style.display = 'none';
}

function handleDrop(e){
  e.preventDefault();
  document.getElementById('uploadZone').style.borderColor = 'rgba(59,130,246,.3)';
  var files = e.dataTransfer.files;
  if (files.length > 0) {
    var input = document.getElementById('fileInput');
    input.files = files;
    showFile(input);
  }
}

function toggleAddForm(){
  var f = document.getElementById('addForm');
  var btn = document.getElementById('toggleBtn');
  if (f.style.display === 'none') {
    f.style.display = 'flex';
    btn.innerHTML = '<i class="ph-bold ph-x"></i> Close';
  } else {
    f.style.display = 'none';
    btn.innerHTML = '<i class="ph-bold ph-plus"></i> Add Content';
  }
}

// YouTube URL auto-convert
document.addEventListener('DOMContentLoaded', function(){
  var vi = document.getElementById('videoInput');
  if (vi) {
    vi.addEventListener('blur', function(){
      var v = vi.value.trim();
      if (v.includes('watch?v=')) v = 'https://www.youtube.com/embed/' + v.split('watch?v=')[1].split('&')[0];
      else if (v.includes('youtu.be/')) v = 'https://www.youtube.com/embed/' + v.split('youtu.be/')[1].split('?')[0];
      vi.value = v;
    });
  }
});
</script>

<!-- ============ CONTENT LIST ============ -->
<div style="display:flex;align-items:center;justify-content:space-between;margin:28px 0 16px;flex-wrap:wrap;gap:12px">
<h2 style="margin:0;font-size:16px;font-weight:800;color:#fff;display:flex;align-items:center;gap:10px">
<i class="ph-bold ph-files" style="color:#fb923c"></i> Content in <?= htmlspecialchars($chapName) ?>
</h2>
<span style="font-size:12px;color:#64748b;font-weight:600"><?= count($items) ?> items</span>
</div>

<?php if (empty($items)): ?>
<div class="premium-card" style="padding:50px 20px;text-align:center">
<div style="font-size:48px;opacity:.3;margin-bottom:12px">📄</div>
<h3 style="color:#94a3b8;font-size:15px;font-weight:700;margin:0 0 6px">No content yet</h3>
<p style="color:#64748b;font-size:13px;margin:0">ऊपर form भरकर content add करो</p>
</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px">
<?php foreach ($items as $it):
    $icons = [
      'note'=>['ic'=>'ph-file-pdf','color'=>'#60a5fa','bg'=>'rgba(59,130,246,.15)','lbl'=>'NOTE'],
      'dpp'=>['ic'=>'ph-note-pencil','color'=>'#c084fc','bg'=>'rgba(168,85,247,.15)','lbl'=>'DPP'],
      'video'=>['ic'=>'ph-play-circle','color'=>'#f87171','bg'=>'rgba(239,68,68,.15)','lbl'=>'VIDEO'],
      'link'=>['ic'=>'ph-link','color'=>'#fb923c','bg'=>'rgba(251,146,60,.15)','lbl'=>'LINK']
    ];
    $meta = $icons[$it['type']] ?? $icons['note'];
?>
<div class="premium-card" style="padding:18px">
<div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:12px">
<div style="width:48px;height:48px;border-radius:12px;background:<?= $meta['bg'] ?>;display:flex;align-items:center;justify-content:center;color:<?= $meta['color'] ?>;font-size:22px;flex-shrink:0">
<i class="ph-bold <?= $meta['ic'] ?>"></i>
</div>
<div style="min-width:0;flex:1">
<span style="display:inline-block;padding:2px 8px;border-radius:6px;background:<?= $meta['bg'] ?>;color:<?= $meta['color'] ?>;font-size:9.5px;font-weight:800;letter-spacing:.5px"><?= $meta['lbl'] ?></span>
<h3 style="margin:6px 0 0;font-size:13.5px;font-weight:700;color:#fff;line-height:1.3"><?= htmlspecialchars($it['title']) ?></h3>
</div>
</div>
<?php if (!empty($it['description'])): ?>
<p style="margin:0 0 12px;font-size:11.5px;color:#94a3b8;line-height:1.4"><?= htmlspecialchars($it['description']) ?></p>
<?php endif; ?>
<?php if (!empty($it['file'])): ?>
<div style="margin-bottom:10px;padding:6px 10px;background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.2);border-radius:8px;display:flex;align-items:center;gap:6px">
<i class="ph-bold ph-paperclip" style="color:#34d399;font-size:12px"></i>
<span style="font-size:10.5px;color:#34d399;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($it['file']) ?></span>
</div>
<?php endif; ?>
<div style="display:flex;gap:8px;flex-wrap:wrap">
<a href="?type=content&batch=<?= urlencode($batchId) ?>&subject=<?= urlencode($subjectId) ?>&chapter=<?= urlencode($chapterId) ?>&edit_content=<?= urlencode($it['id']) ?>" class="btn btn-sm btn-gray"><i class="ph-bold ph-pencil-simple"></i> Edit</a>
<a href="?type=content&batch=<?= urlencode($batchId) ?>&subject=<?= urlencode($subjectId) ?>&chapter=<?= urlencode($chapterId) ?>&delete_content=<?= urlencode($it['id']) ?>" onclick="return confirm('Delete this content?')" class="btn-danger"><i class="ph-bold ph-trash"></i></a>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; ?>
<?php endif; ?>
