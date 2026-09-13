<?php
/**
 * Fast Chunked Upload Component
 */
function renderFastUpload($name, $label, $accept, $dest, $preview = '', $hint = '', $maxMB = 200){ ?>
<style>
.fu2-wrap{margin-bottom:4px}
.fu2-label{display:block;font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:.8px;text-transform:uppercase;margin-bottom:8px}
.fu2-drop{position:relative;background:linear-gradient(145deg,rgba(15,23,42,.6),rgba(11,18,32,.4));border:2px dashed rgba(59,130,246,.35);border-radius:16px;padding:22px;text-align:center;cursor:pointer;transition:all .25s;overflow:hidden}
.fu2-drop:hover{border-color:#3b82f6;background:linear-gradient(145deg,rgba(59,130,246,.08),rgba(37,99,235,.04))}
.fu2-drop.drag{border-color:#2cee82;background:rgba(44,238,130,.08)}
.fu2-drop.has-file{border-style:solid;border-color:rgba(16,185,129,.5);background:rgba(16,185,129,.06);padding:14px}
.fu2-input{display:none}
.fu2-icon{width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,rgba(59,130,246,.18),rgba(37,99,235,.08));border:1px solid rgba(59,130,246,.3);display:flex;align-items:center;justify-content:center;color:#60a5fa;margin:0 auto 12px}
.fu2-icon svg{width:28px;height:28px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.fu2-title{font-size:14px;font-weight:700;color:#e2e8f0;margin-bottom:4px}
.fu2-title .blue{color:#60a5fa}
.fu2-hint{font-size:11.5px;color:#64748b}
.fu2-preview{display:flex;align-items:center;gap:14px;text-align:left}
.fu2-preview-img{width:60px;height:60px;border-radius:12px;object-fit:cover;background:#fff;flex-shrink:0;border:2px solid rgba(16,185,129,.4);padding:2px}
.fu2-preview-info{flex:1;min-width:0}
.fu2-preview-name{font-size:13px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fu2-preview-size{font-size:11px;color:#34d399;font-weight:600;margin-top:2px}
.fu2-remove{width:34px;height:34px;border-radius:50%;background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.35);color:#f87171;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;padding:0}
.fu2-remove svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2.5;stroke-linecap:round}
.fu2-existing{margin-top:10px;padding:8px 12px;background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);border-radius:10px;display:flex;align-items:center;gap:10px;font-size:11.5px;color:#60a5fa}
.fu2-existing img{width:34px;height:34px;border-radius:8px;object-fit:cover;background:#fff;padding:2px}
.fu2-existing b{color:#fff}

/* Progress */
.fu2-progress{display:none;margin-top:12px;padding:14px;background:rgba(15,23,42,.7);border:1px solid rgba(59,130,246,.3);border-radius:14px}
.fu2-progress.show{display:block}
.fu2-prog-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.fu2-prog-title{font-size:12.5px;font-weight:700;color:#e2e8f0}
.fu2-prog-pct{font-size:13px;font-weight:800;color:#2cee82;font-family:monospace}
.fu2-prog-bar{height:8px;background:rgba(255,255,255,.1);border-radius:5px;overflow:hidden;position:relative}
.fu2-prog-fill{height:100%;background:linear-gradient(90deg,#10b981,#2cee82);border-radius:5px;width:0%;transition:width .2s}
.fu2-prog-meta{display:flex;justify-content:space-between;margin-top:8px;font-size:11px;color:#64748b}
.fu2-prog-speed{color:#2cee82;font-weight:700}
.fu2-status{font-size:11px;color:#94a3b8;margin-top:6px;text-align:center}
</style>

<div class="fu2-wrap">
<label class="fu2-label"><?= htmlspecialchars($label) ?></label>

<div class="fu2-drop" id="fu2-<?= $name ?>" 
     onclick="document.getElementById('fu2-input-<?= $name ?>').click()"
     ondragover="event.preventDefault();this.classList.add('drag')" 
     ondragleave="this.classList.remove('drag')" 
     ondrop="fu2Drop(event, '<?= $name ?>', '<?= $dest ?>', <?= $maxMB ?>)">
    
    <input type="file" id="fu2-input-<?= $name ?>" accept="<?= htmlspecialchars($accept) ?>" class="fu2-input" onchange="fu2Pick(this, '<?= $name ?>', '<?= $dest ?>', <?= $maxMB ?>)"/>
    
    <div class="fu2-empty" id="fu2-empty-<?= $name ?>">
        <div class="fu2-icon">
            <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        </div>
        <div class="fu2-title">Click to upload <span class="blue">or drag & drop</span></div>
        <div class="fu2-hint"><?= htmlspecialchars($hint ?: 'Max '.$maxMB.'MB · Fast upload') ?></div>
    </div>
    
    <div class="fu2-preview" id="fu2-prev-<?= $name ?>" style="display:none">
        <img class="fu2-preview-img" id="fu2-img-<?= $name ?>" src="" alt=""/>
        <div class="fu2-preview-info">
            <div class="fu2-preview-name" id="fu2-name-<?= $name ?>">file</div>
            <div class="fu2-preview-size" id="fu2-size-<?= $name ?>">0 KB</div>
        </div>
        <button type="button" class="fu2-remove" onclick="event.stopPropagation();fu2Clear('<?= $name ?>')">
            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
</div>

<div class="fu2-progress" id="fu2-prog-<?= $name ?>">
    <div class="fu2-prog-head">
        <span class="fu2-prog-title" id="fu2-prog-title-<?= $name ?>">Uploading...</span>
        <span class="fu2-prog-pct" id="fu2-prog-pct-<?= $name ?>">0%</span>
    </div>
    <div class="fu2-prog-bar"><div class="fu2-prog-fill" id="fu2-prog-fill-<?= $name ?>"></div></div>
    <div class="fu2-prog-meta">
        <span id="fu2-prog-status-<?= $name ?>">Chunk 0 / 0</span>
        <span class="fu2-prog-speed" id="fu2-prog-speed-<?= $name ?>">0 KB/s</span>
    </div>
</div>

<!-- Hidden input stores final filename -->
<input type="hidden" name="<?= htmlspecialchars($name) ?>_final" id="fu2-final-<?= $name ?>" value=""/>

<?php if (!empty($preview)): ?>
<div class="fu2-existing">
    <img src="<?= htmlspecialchars($preview) ?>" alt=""/>
    <span>Current: <b><?= htmlspecialchars(basename($preview)) ?></b></span>
</div>
<?php endif; ?>
</div>

<script>
if (!window.fu2Pick) {
    var fu2State = {};

    window.fu2Pick = function(input, name, dest, maxMB){
        if (!input.files || !input.files[0]) return;
        var f = input.files[0];
        if (f.size > maxMB * 1024 * 1024) { alert('Max ' + maxMB + 'MB'); input.value=''; return; }
        startUpload(f, name, dest);
    };

    window.fu2Drop = function(e, name, dest, maxMB){
        e.preventDefault();
        document.getElementById('fu2-' + name).classList.remove('drag');
        if (e.dataTransfer.files.length > 0) {
            var f = e.dataTransfer.files[0];
            if (f.size > maxMB * 1024 * 1024) { alert('Max ' + maxMB + 'MB'); return; }
            startUpload(f, name, dest);
        }
    };

    window.fu2Clear = function(name){
        var finalEl = document.getElementById('fu2-final-' + name);
        if (finalEl) finalEl.value = '';
        var previewEl = document.getElementById('fu2-prev-' + name);
        if (previewEl) previewEl.style.display = 'none';
        var emptyEl = document.getElementById('fu2-empty-' + name);
        if (emptyEl) emptyEl.style.display = 'block';
        var dropEl = document.getElementById('fu2-' + name);
        if (dropEl) dropEl.classList.remove('has-file');
        var inp = document.getElementById('fu2-input-' + name);
        if (inp) inp.value = '';
    };

    function startUpload(file, name, dest){
        // Show preview instantly
        document.getElementById('fu2-empty-' + name).style.display = 'none';
        document.getElementById('fu2-prev-' + name).style.display = 'flex';
        document.getElementById('fu2-name-' + name).textContent = file.name;
        document.getElementById('fu2-size-' + name).textContent = (file.size/1024/1024).toFixed(2) + ' MB';
        document.getElementById('fu2-' + name).classList.add('has-file');

        // Show image preview if image
        var img = document.getElementById('fu2-img-' + name);
        if (file.type.startsWith('image/')) {
            var r = new FileReader();
            r.onload = function(e){ img.src = e.target.result; };
            r.readAsDataURL(file);
        } else {
            img.src = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%2360a5fa" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
        }

        // Progress UI
        var prog = document.getElementById('fu2-prog-' + name);
        prog.classList.add('show');
        document.getElementById('fu2-prog-title-' + name).textContent = 'Uploading: ' + file.name;

        // Chunked upload
        var chunkSize = 1024 * 1024; // 1MB
        var totalChunks = Math.ceil(file.size / chunkSize);
        var fileId = null;
        var startTime = Date.now();
        var uploadedBytes = 0;

        // Init
        var fd = new FormData();
        fd.append('action', 'init');
        fd.append('name', file.name);
        fd.append('size', file.size);
        fd.append('type', file.type);
        fd.append('chunks', totalChunks);
        fd.append('dest', dest);

        fetch('upload_chunk.php', {method:'POST', body:fd})
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (!d.success) throw new Error(d.error || 'Init failed');
            fileId = d.fileId;
            uploadChunk(0);

            function uploadChunk(idx){
                if (idx >= totalChunks) {
                    finalize();
                    return;
                }
                var start = idx * chunkSize;
                var end = Math.min(start + chunkSize, file.size);
                var blob = file.slice(start, end);
                var cfd = new FormData();
                cfd.append('action', 'chunk');
                cfd.append('fileId', fileId);
                cfd.append('index', idx);
                cfd.append('chunk', blob, 'chunk_' + idx);

                fetch('upload_chunk.php', {method:'POST', body:cfd})
                .then(function(r){ return r.json(); })
                .then(function(res){
                    if (!res.success) throw new Error(res.error || 'Chunk failed');
                    uploadedBytes += (end - start);
                    var pct = Math.round((uploadedBytes / file.size) * 100);
                    var elapsed = (Date.now() - startTime) / 1000;
                    var speed = elapsed > 0 ? (uploadedBytes / 1024 / elapsed) : 0;

                    document.getElementById('fu2-prog-fill-' + name).style.width = pct + '%';
                    document.getElementById('fu2-prog-pct-' + name).textContent = pct + '%';
                    document.getElementById('fu2-prog-status-' + name).textContent = 'Chunk ' + (idx+1) + ' / ' + totalChunks;
                    document.getElementById('fu2-prog-speed-' + name).textContent = formatSpeed(speed);

                    uploadChunk(idx + 1);
                })
                .catch(function(err){
                    document.getElementById('fu2-prog-title-' + name).textContent = '❌ Error: ' + err.message;
                });
            }

            function finalize(){
                document.getElementById('fu2-prog-title-' + name).textContent = '⚙️ Processing...';
                var ffd = new FormData();
                ffd.append('action', 'finalize');
                ffd.append('fileId', fileId);
                fetch('upload_chunk.php', {method:'POST', body:ffd})
                .then(function(r){ return r.json(); })
                .then(function(res){
                    if (!res.success) throw new Error(res.error || 'Finalize failed');
                    document.getElementById('fu2-final-' + name).value = res.filename;
                    document.getElementById('fu2-prog-title-' + name).textContent = '✅ Upload complete';
                    document.getElementById('fu2-prog-fill-' + name).style.width = '100%';
                    setTimeout(function(){
                        prog.classList.remove('show');
                    }, 1500);
                })
                .catch(function(err){
                    document.getElementById('fu2-prog-title-' + name).textContent = '❌ ' + err.message;
                });
            }
        })
        .catch(function(err){
            document.getElementById('fu2-prog-title-' + name).textContent = '❌ ' + err.message;
        });

        function formatSpeed(kbps){
            if (kbps > 1024) return (kbps/1024).toFixed(2) + ' MB/s';
            return kbps.toFixed(0) + ' KB/s';
        }
    }
}
</script>
<?php } ?>
