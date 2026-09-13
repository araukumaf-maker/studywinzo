<?php
/**
 * Premium File Upload Component
 * Usage: renderFilePicker('input_name', 'Label', 'accept', 'hint', 'current_preview_url')
 */
function renderFilePicker($name, $label, $accept = 'image/*', $hint = 'PNG, JPG · max 5MB', $preview = ''){ ?>
<style>
.fu-wrap{margin-bottom:4px}
.fu-label{display:block;font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:.8px;text-transform:uppercase;margin-bottom:8px}
.fu-drop{position:relative;background:linear-gradient(145deg,rgba(15,23,42,.6),rgba(11,18,32,.4));border:2px dashed rgba(59,130,246,.35);border-radius:16px;padding:20px;text-align:center;cursor:pointer;transition:all .25s;overflow:hidden}
.fu-drop:hover{border-color:#3b82f6;background:linear-gradient(145deg,rgba(59,130,246,.08),rgba(37,99,235,.04));transform:translateY(-1px)}
.fu-drop.drag{border-color:#2cee82;background:rgba(44,238,130,.08);transform:scale(1.01)}
.fu-drop.has-file{border-style:solid;border-color:rgba(16,185,129,.5);background:rgba(16,185,129,.06);padding:14px}
.fu-input{display:none}
.fu-icon{width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,rgba(59,130,246,.18),rgba(37,99,235,.08));border:1px solid rgba(59,130,246,.3);display:flex;align-items:center;justify-content:center;color:#60a5fa;margin:0 auto 12px;transition:all .25s}
.fu-drop:hover .fu-icon{transform:scale(1.08) rotate(-3deg);background:linear-gradient(135deg,rgba(59,130,246,.3),rgba(37,99,235,.15))}
.fu-icon svg{width:28px;height:28px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.fu-title{font-size:14px;font-weight:700;color:#e2e8f0;margin-bottom:4px}
.fu-title .blue{color:#60a5fa}
.fu-hint{font-size:11.5px;color:#64748b}
.fu-preview{display:flex;align-items:center;gap:14px;text-align:left}
.fu-preview-img{width:60px;height:60px;border-radius:12px;object-fit:cover;background:#fff;flex-shrink:0;border:2px solid rgba(16,185,129,.4);padding:2px}
.fu-preview-info{flex:1;min-width:0}
.fu-preview-name{font-size:13px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fu-preview-size{font-size:11px;color:#34d399;font-weight:600;margin-top:2px}
.fu-remove{width:34px;height:34px;border-radius:50%;background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.35);color:#f87171;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s;padding:0}
.fu-remove:hover{background:rgba(239,68,68,.3);transform:scale(1.08)}
.fu-remove svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2.5;stroke-linecap:round}
.fu-existing{margin-top:10px;padding:8px 12px;background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);border-radius:10px;display:flex;align-items:center;gap:10px;font-size:11.5px;color:#60a5fa}
.fu-existing img{width:34px;height:34px;border-radius:8px;object-fit:cover;background:#fff;padding:2px;border:1px solid rgba(59,130,246,.3)}
.fu-existing b{color:#fff}
</style>

<div class="fu-wrap">
<label class="fu-label"><?= htmlspecialchars($label) ?></label>

<label class="fu-drop" id="fu-<?= $name ?>" 
       ondragover="event.preventDefault();this.classList.add('drag')" 
       ondragleave="this.classList.remove('drag')" 
       ondrop="fuDrop(event, '<?= $name ?>')">
    <input type="file" name="<?= htmlspecialchars($name) ?>" id="fu-input-<?= $name ?>" accept="<?= htmlspecialchars($accept) ?>" class="fu-input" onchange="fuChange(this, '<?= $name ?>')"/>
    
    <div class="fu-empty" id="fu-empty-<?= $name ?>">
        <div class="fu-icon">
            <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        </div>
        <div class="fu-title">Click to upload <span class="blue">or drag & drop</span></div>
        <div class="fu-hint"><?= htmlspecialchars($hint) ?></div>
    </div>
    
    <div class="fu-preview" id="fu-prev-<?= $name ?>" style="display:none">
        <img class="fu-preview-img" id="fu-img-<?= $name ?>" src="" alt=""/>
        <div class="fu-preview-info">
            <div class="fu-preview-name" id="fu-name-<?= $name ?>">file</div>
            <div class="fu-preview-size" id="fu-size-<?= $name ?>">0 KB</div>
        </div>
        <button type="button" class="fu-remove" onclick="event.preventDefault();event.stopPropagation();fuClear('<?= $name ?>')" title="Remove">
            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
</label>

<?php if (!empty($preview)): ?>
<div class="fu-existing">
    <img src="<?= htmlspecialchars($preview) ?>" alt=""/>
    <span>Current: <b><?= htmlspecialchars(basename($preview)) ?></b></span>
</div>
<?php endif; ?>
</div>

<script>
if (!window.fuChange) {
    window.fuChange = function(input, name){
        if (!input.files || !input.files[0]) return;
        var f = input.files[0];
        var maxMB = 200;
        if (f.size > maxMB * 1024 * 1024) { alert('File too large. Max ' + maxMB + 'MB'); input.value=''; return; }

        document.getElementById('fu-empty-' + name).style.display = 'none';
        document.getElementById('fu-prev-' + name).style.display = 'flex';
        document.getElementById('fu-name-' + name).textContent = f.name;
        document.getElementById('fu-size-' + name).textContent = (f.size/1024).toFixed(1) + ' KB';
        document.getElementById('fu-' + name).classList.add('has-file');

        var img = document.getElementById('fu-img-' + name);
        if (f.type.startsWith('image/')) {
            var r = new FileReader();
            r.onload = function(e){ img.src = e.target.result; img.style.display='block'; };
            r.readAsDataURL(f);
        } else {
            img.src = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%2360a5fa" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
        }
    };
    window.fuClear = function(name){
        document.getElementById('fu-input-' + name).value = '';
        document.getElementById('fu-empty-' + name).style.display = 'block';
        document.getElementById('fu-prev-' + name).style.display = 'none';
        document.getElementById('fu-' + name).classList.remove('has-file');
    };
    window.fuDrop = function(e, name){
        e.preventDefault();
        document.getElementById('fu-' + name).classList.remove('drag');
        var files = e.dataTransfer.files;
        if (files.length > 0) {
            var input = document.getElementById('fu-input-' + name);
            input.files = files;
            window.fuChange(input, name);
        }
    };
}
</script>
<?php } ?>
