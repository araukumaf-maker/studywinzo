<?php
/**
 * Mobile-friendly upload UI
 * Usage: mUpload('input_name', 'Label', 'image/*', 'hint', 'current_preview_url', $required)
 */
function mUpload($name, $label, $accept = 'image/*', $hint = '', $preview = '', $required = false){ ?>
<div class="mup">
<?php if ($label): ?>
<label class="mup-label"><?= htmlspecialchars($label) ?><?= $required ? ' *' : '' ?></label>
<?php endif; ?>

<label class="mup-btn" for="mup-inp-<?= $name ?>">
<input type="file" id="mup-inp-<?= $name ?>" name="<?= htmlspecialchars($name) ?>" accept="<?= htmlspecialchars($accept) ?>" <?= $required?'required':'' ?> onchange="mupChange(this, '<?= $name ?>')" style="display:none"/>
<span class="mup-btn-icon">
<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
<polyline points="17 8 12 3 7 8"/>
<line x1="12" y1="3" x2="12" y2="15"/>
</svg>
</span>
<span class="mup-btn-text">Choose File</span>
</label>

<div class="mup-preview" id="mup-prev-<?= $name ?>" style="display:none">
<img id="mup-img-<?= $name ?>" src="" alt=""/>
<div class="mup-prev-info">
<div class="mup-prev-name" id="mup-name-<?= $name ?>"></div>
<div class="mup-prev-size" id="mup-size-<?= $name ?>"></div>
</div>
<button type="button" class="mup-remove" onclick="event.preventDefault();mupClear('<?= $name ?>')">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
</button>
</div>

<?php if ($preview): ?>
<div class="mup-current" id="mup-cur-<?= $name ?>">
<img src="<?= htmlspecialchars($preview) ?>" alt=""/>
<span>Current file</span>
</div>
<?php endif; ?>

<?php if ($hint): ?>
<div class="mup-hint"><?= htmlspecialchars($hint) ?></div>
<?php endif; ?>
</div>

<style>
.mup{margin-bottom:16px}
.mup-label{display:block;font-size:12.5px;font-weight:700;color:#94a3b8;letter-spacing:.5px;text-transform:uppercase;margin-bottom:8px}
.mup-btn{display:flex;align-items:center;justify-content:center;gap:10px;background:linear-gradient(135deg,#2563eb,#3b82f6);color:#fff;padding:16px 20px;border-radius:14px;font-weight:700;font-size:15px;cursor:pointer;box-shadow:0 6px 18px rgba(37,99,235,.35);transition:all .15s;text-decoration:none;width:100%;box-sizing:border-box;-webkit-tap-highlight-color:transparent;user-select:none}
.mup-btn:active{transform:scale(.97);box-shadow:0 4px 12px rgba(37,99,235,.3)}
.mup-btn-icon{display:flex;align-items:center;justify-content:center;flex-shrink:0}
.mup-btn-text{font-weight:700}
.mup-preview{display:flex;align-items:center;gap:12px;background:rgba(16,185,129,.08);border:1.5px solid rgba(16,185,129,.35);border-radius:14px;padding:12px;margin-top:12px}
.mup-preview img{width:56px;height:56px;border-radius:10px;object-fit:cover;background:#fff;flex-shrink:0;border:2px solid rgba(16,185,129,.4)}
.mup-prev-info{flex:1;min-width:0}
.mup-prev-name{font-size:13.5px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mup-prev-size{font-size:11.5px;color:#34d399;font-weight:600;margin-top:3px}
.mup-remove{width:40px;height:40px;border-radius:50%;background:rgba(239,68,68,.15);border:1.5px solid rgba(239,68,68,.35);color:#f87171;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;padding:0;-webkit-tap-highlight-color:transparent;transition:all .15s}
.mup-remove:active{background:rgba(239,68,68,.35);transform:scale(.92)}
.mup-current{display:flex;align-items:center;gap:10px;background:rgba(59,130,246,.06);border:1px solid rgba(59,130,246,.2);border-radius:12px;padding:10px 12px;margin-top:10px}
.mup-current img{width:44px;height:44px;border-radius:8px;object-fit:cover;background:#fff;padding:2px;flex-shrink:0}
.mup-current span{font-size:12px;color:#60a5fa;font-weight:700}
.mup-hint{font-size:11.5px;color:#64748b;margin-top:8px;line-height:1.5}
</style>

<script>
if (!window.mupChange) {
  window.mupChange = function(input, name){
    if (!input.files || !input.files[0]) return;
    var f = input.files[0];
    // Max size check (based on accept)
    var maxMB = 10;
    if (input.accept.indexOf('video') !== -1) maxMB = 500;
    if (f.size > maxMB * 1024 * 1024) {
      alert('File too large. Max ' + maxMB + 'MB');
      input.value = '';
      return;
    }
    document.getElementById('mup-name-' + name).textContent = f.name;
    document.getElementById('mup-size-' + name).textContent = (f.size/1024/1024).toFixed(2) + ' MB';
    document.getElementById('mup-prev-' + name).style.display = 'flex';
    var cur = document.getElementById('mup-cur-' + name);
    if (cur) cur.style.display = 'none';
    if (f.type.indexOf('image') === 0) {
      var r = new FileReader();
      r.onload = function(e){
        var img = document.getElementById('mup-img-' + name);
        img.src = e.target.result;
        img.style.display = 'block';
      };
      r.readAsDataURL(f);
    } else {
      var img = document.getElementById('mup-img-' + name);
      img.style.display = 'none';
    }
  };
  window.mupClear = function(name){
    document.getElementById('mup-inp-' + name).value = '';
    document.getElementById('mup-prev-' + name).style.display = 'none';
    var cur = document.getElementById('mup-cur-' + name);
    if (cur) cur.style.display = 'flex';
  };
}
</script>
<?php } ?>
