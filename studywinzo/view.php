<?php
ini_set('display_errors', 0);
require_once __DIR__.'/data_helper.php';
$content = getJSON('content.json', []);
$id = preg_replace('/[^A-Za-z0-9_]/','',$_GET['id'] ?? '');
$item = null;
foreach ($content as $c) if (($c['id'] ?? '') === $id) { $item = $c; break; }
if (!$item) { header('Location: index.php'); exit; }

$type = $item['type'] ?? '';
$file = $item['file'] ?? '';
$isDpp = ($type === 'dpp');
$folder = $isDpp ? 'dpp' : 'notes';
$filePath = $file ? mediaUrl($file, $folder) : '';
$fileExists = $file && $filePath !== '';
$title = $item['title'] ?? 'Document';
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title><?= htmlspecialchars($title) ?></title>
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<style>
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent;font-family:'Inter',sans-serif;margin:0;padding:0}
body{background:#0a0f1a;color:#fff;min-height:100vh;overflow-x:hidden;display:flex;flex-direction:column;overscroll-behavior:none}

.hdr{padding:12px 14px;display:flex;align-items:center;gap:10px;background:rgba(10,15,26,.98);border-bottom:1px solid #1f2937;position:sticky;top:0;z-index:20}
.back{width:40px;height:40px;border-radius:11px;background:#0e1613;border:1px solid #1f332a;display:flex;align-items:center;justify-content:center;text-decoration:none;flex-shrink:0}
.back i{color:#2cee82;font-size:19px}
.hdr-title{flex:1;min-width:0}
.hdr h1{font-size:13.5px;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.hdr p{font-size:10.5px;color:#64748b;margin-top:1px;font-weight:600;letter-spacing:.5px}
.hdr-btn{padding:8px 12px;border-radius:10px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;text-decoration:none;font-size:11.5px;font-weight:700;display:flex;align-items:center;gap:5px;flex-shrink:0;box-shadow:0 4px 12px rgba(16,185,129,.3)}
.hdr-btn i{font-size:14px}

/* Toolbar */
.toolbar{background:#111827;border-bottom:1px solid #1f2937;padding:8px 12px;display:flex;align-items:center;gap:6px;position:sticky;top:64px;z-index:19;overflow-x:auto;scrollbar-width:none}
.toolbar::-webkit-scrollbar{display:none}
.tbtn{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:#cbd5e1;min-width:36px;height:36px;padding:0 10px;border-radius:9px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:12px;font-weight:700;gap:5px;flex-shrink:0;transition:all .15s;font-family:inherit}
.tbtn:hover{background:rgba(59,130,246,.15);border-color:rgba(59,130,246,.4);color:#60a5fa}
.tbtn:active{transform:scale(.95)}
.tbtn:disabled{opacity:.3;cursor:not-allowed}
.tbtn i{font-size:15px}
.page-info{background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.25);color:#60a5fa;padding:8px 12px;border-radius:9px;font-size:12px;font-weight:800;white-space:nowrap;font-family:monospace}
.zoom-info{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#34d399;padding:8px 12px;border-radius:9px;font-size:12px;font-weight:800;font-family:monospace;white-space:nowrap;min-width:64px;text-align:center;cursor:pointer;user-select:none}
.zoom-info:hover{background:rgba(16,185,129,.2)}

/* Viewer */
.viewer{flex:1;background:#05070d;overflow:auto;padding:16px;position:relative;-webkit-overflow-scrolling:touch;overscroll-behavior:contain}
.viewer::-webkit-scrollbar{width:6px;height:6px}
.viewer::-webkit-scrollbar-track{background:#0a0f1a}
.viewer::-webkit-scrollbar-thumb{background:#1f2937;border-radius:3px}

.pages-container{display:flex;flex-direction:column;align-items:center;gap:14px;transform-origin:top center;will-change:transform}

.page-wrap{background:#fff;border-radius:6px;box-shadow:0 8px 24px rgba(0,0,0,.6);overflow:hidden;position:relative;transform-origin:top center;transition:none}
.page-wrap canvas{display:block;max-width:100%;height:auto}
.page-num-badge{position:absolute;top:8px;right:8px;background:rgba(0,0,0,.75);color:#fff;padding:4px 10px;border-radius:8px;font-size:11px;font-weight:800;font-family:monospace;z-index:2}
.page-loading{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:40px;height:40px;border:3px solid rgba(59,130,246,.2);border-top-color:#3b82f6;border-radius:50%;animation:spin 1s linear infinite;display:none}
.page-wrap.loading .page-loading{display:block}
@keyframes spin{to{transform:rotate(360deg)}}

/* Zoom indicator (floating) */
.zoom-hud{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) scale(.8);background:rgba(0,0,0,.85);backdrop-filter:blur(10px);color:#fff;padding:14px 24px;border-radius:16px;font-size:22px;font-weight:900;font-family:monospace;z-index:100;opacity:0;pointer-events:none;transition:all .2s;border:1px solid rgba(16,185,129,.5)}
.zoom-hud.show{opacity:1;transform:translate(-50%,-50%) scale(1)}

/* Loading */
.loading{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:80px 20px;gap:16px}
.spinner{width:52px;height:52px;border:4px solid rgba(59,130,246,.2);border-top-color:#3b82f6;border-radius:50%;animation:spin 1s linear infinite}
.loading p{color:#94a3b8;font-size:13px;font-weight:600}
.loading-progress{width:200px;height:4px;background:rgba(255,255,255,.1);border-radius:4px;overflow:hidden}
.loading-progress div{height:100%;background:linear-gradient(90deg,#10b981,#2cee82);width:0%;transition:width .3s}

/* Empty */
.empty{text-align:center;padding:80px 20px;color:#64748b;flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center}
.empty i{font-size:64px;opacity:.3;display:block;margin-bottom:16px}
.empty h3{color:#94a3b8;font-size:16px;font-weight:700;margin-bottom:8px}
.empty p{font-size:13px;margin-bottom:20px}
.empty a{background:linear-gradient(135deg,#10b981,#059669);color:#fff;padding:10px 22px;border-radius:10px;text-decoration:none;font-weight:700;font-size:13px;box-shadow:0 6px 16px rgba(16,185,129,.3)}

/* Floating page nav */
.page-nav{position:fixed;bottom:20px;right:16px;background:rgba(10,15,26,.95);backdrop-filter:blur(10px);border:1px solid rgba(59,130,246,.3);border-radius:16px;padding:6px;display:none;flex-direction:column;gap:4px;box-shadow:0 12px 32px rgba(0,0,0,.5);z-index:15}
.page-nav.show{display:flex}
.pn-btn{width:44px;height:44px;border-radius:12px;background:rgba(59,130,246,.1);border:none;color:#60a5fa;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:20px;transition:all .15s}
.pn-btn:hover{background:rgba(59,130,246,.25)}
.pn-btn:active{transform:scale(.9)}
.pn-btn:disabled{opacity:.3;cursor:not-allowed}
.pn-count{color:#fff;font-size:9px;font-weight:800;text-align:center;padding:4px 0;font-family:monospace}

/* Toast */
.toast{position:fixed;top:80px;left:50%;transform:translateX(-50%) translateY(-100px);background:#111827;border:1px solid rgba(16,185,129,.5);color:#fff;padding:10px 18px;border-radius:30px;font-size:12px;font-weight:700;z-index:200;box-shadow:0 12px 32px rgba(0,0,0,.4);transition:transform .3s}
.toast.show{transform:translateX(-50%) translateY(0)}
</style>
  <link rel="stylesheet" href="assets/premium.css">
</head>
<body>

<div class="hdr">
<a href="javascript:history.back()" class="back"><i class="ph-bold ph-arrow-left"></i></a>
<div class="hdr-title">
<h1><?= htmlspecialchars($title) ?></h1>
<p><?= $isDpp ? '📝 DPP' : '📄 NOTE' ?></p>
</div>
<?php if ($fileExists): ?>
<a href="<?= htmlspecialchars($filePath) ?>" download class="hdr-btn">
<i class="ph-bold ph-download-simple"></i> Download
</a>
<?php endif; ?>
</div>

<?php if (!$fileExists): ?>
<div class="empty">
<i class="ph-bold ph-file-x"></i>
<h3>File not found</h3>
<p>यह file अभी upload नहीं हुई</p>
<a href="javascript:history.back()">← Go Back</a>
</div>
<?php else: ?>

<div class="toolbar" id="toolbar" style="display:none">
<button class="tbtn" id="prevBtn" onclick="prevPage()"><i class="ph-bold ph-caret-left"></i></button>
<div class="page-info" id="pageInfo">1 / 1</div>
<button class="tbtn" id="nextBtn" onclick="nextPage()"><i class="ph-bold ph-caret-right"></i></button>
<button class="tbtn" onclick="zoomOut()" onmousedown="startHold(-1)" onmouseup="stopHold()" onmouseleave="stopHold()"><i class="ph-bold ph-minus"></i></button>
<div class="zoom-info" id="zoomInfo" onclick="resetZoom()" title="Click to reset">100%</div>
<button class="tbtn" onclick="zoomIn()" onmousedown="startHold(1)" onmouseup="stopHold()" onmouseleave="stopHold()"><i class="ph-bold ph-plus"></i></button>
<button class="tbtn" onclick="fitWidth()" title="Fit width"><i class="ph-bold ph-arrows-out-line-horizontal"></i></button>
</div>

<div class="viewer" id="viewer">
<div class="loading" id="loading">
<div class="spinner"></div>
<p id="loadingText">Loading PDF...</p>
<div class="loading-progress"><div id="loadingBar"></div></div>
</div>
<div class="pages-container" id="pagesContainer"></div>
</div>

<div class="page-nav" id="pageNav">
<button class="pn-btn" id="pnUp" onclick="prevPage()"><i class="ph-bold ph-caret-up"></i></button>
<div class="pn-count" id="pnCount">1<br>/1</div>
<button class="pn-btn" id="pnDown" onclick="nextPage()"><i class="ph-bold ph-down"></i></button>
</div>

<div class="zoom-hud" id="zoomHud">100%</div>
<div class="toast" id="toast">✓</div>

<script>
var pdfDoc = null;
var totalPages = 0;
var currentPage = 1;
var baseScale = 1.0;         // Rendered canvas scale
var displayScale = 1.0;      // CSS transform scale (instant)
var MIN_SCALE = 0.4;
var MAX_SCALE = 4.0;
var rendering = {};
var pageWraps = {};
var renderQueue = [];
var isRendering = false;
var holdTimer = null;
var holdSpeed = 1;

pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';

var pdfUrl = '<?= htmlspecialchars($filePath) ?>';

pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf){
    pdfDoc = pdf;
    totalPages = pdf.numPages;
    document.getElementById('loading').style.display = 'none';
    document.getElementById('toolbar').style.display = 'flex';
    document.getElementById('pageNav').classList.add('show');
    buildPages();
}).catch(function(err){
    document.getElementById('loadingText').textContent = '❌ Failed to load PDF';
    document.getElementById('loadingText').style.color = '#f87171';
    console.error(err);
});

function buildPages(){
    var container = document.getElementById('pagesContainer');
    container.innerHTML = '';
    pageWraps = {};
    
    for (var i = 1; i <= totalPages; i++) {
        var wrap = document.createElement('div');
        wrap.className = 'page-wrap';
        wrap.id = 'page-' + i;
        wrap.dataset.pageNum = i;
        // Reserve space with placeholder aspect ratio (A4)
        wrap.style.aspectRatio = '1 / 1.414';
        wrap.style.width = '100%';
        wrap.style.maxWidth = '800px';
        var badge = document.createElement('div');
        badge.className = 'page-num-badge';
        badge.textContent = 'Page ' + i;
        wrap.appendChild(badge);
        var pl = document.createElement('div');
        pl.className = 'page-loading';
        wrap.appendChild(pl);
        container.appendChild(wrap);
        pageWraps[i] = wrap;
    }
    
    // Render first 3 pages immediately
    var renderFirst = Math.min(3, totalPages);
    for (var j = 1; j <= renderFirst; j++) queueRender(j, true);
    for (var k = renderFirst + 1; k <= totalPages; k++) queueRender(k, false);
}

function queueRender(num, priority){
    if (rendering[num] || (pageWraps[num] && pageWraps[num].dataset.rendered === '1' && baseScale === displayScale)) return;
    if (priority) {
        renderQueue.unshift(num);
    } else {
        if (renderQueue.indexOf(num) === -1) renderQueue.push(num);
    }
    processQueue();
}

function processQueue(){
    if (isRendering) return;
    if (renderQueue.length === 0) return;
    isRendering = true;
    var num = renderQueue.shift();
    renderPage(num).then(function(){
        isRendering = false;
        // Small delay to avoid blocking
        setTimeout(processQueue, 30);
    }).catch(function(){
        isRendering = false;
        setTimeout(processQueue, 30);
    });
}

function renderPage(num){
    if (!pdfDoc || rendering[num]) return Promise.resolve();
    rendering[num] = true;
    if (pageWraps[num]) pageWraps[num].classList.add('loading');
    
    return pdfDoc.getPage(num).then(function(page){
        // Use baseScale for rendering quality
        var viewport = page.getViewport({ scale: baseScale });
        var dpr = Math.min(window.devicePixelRatio || 1, 2);
        var canvas = document.createElement('canvas');
        var ctx = canvas.getContext('2d');
        canvas.width = Math.floor(viewport.width * dpr);
        canvas.height = Math.floor(viewport.height * dpr);
        canvas.style.width = Math.floor(viewport.width) + 'px';
        canvas.style.height = Math.floor(viewport.height) + 'px';
        
        var transform = dpr !== 1 ? [dpr, 0, 0, dpr, 0, 0] : null;
        return page.render({ canvasContext: ctx, transform: transform, viewport: viewport }).promise.then(function(){
            var wrap = pageWraps[num];
            if (!wrap) return;
            var oldCanvas = wrap.querySelector('canvas');
            if (oldCanvas) oldCanvas.remove();
            wrap.insertBefore(canvas, wrap.firstChild);
            wrap.dataset.rendered = '1';
            wrap.classList.remove('loading');
            wrap.style.aspectRatio = '';
            wrap.style.width = '';
            wrap.style.maxWidth = '';
            rendering[num] = false;
        });
    }).catch(function(err){
        rendering[num] = false;
        if (pageWraps[num]) pageWraps[num].classList.remove('loading');
        console.error('Render error page ' + num, err);
    });
}

/* ============================================================
   ZOOM SYSTEM - CSS transform for instant feedback + re-render
   ============================================================ */

function applyDisplayScale(){
    var container = document.getElementById('pagesContainer');
    container.style.transform = 'scale(' + displayScale + ')';
    
    // Adjust container height so scrollbar matches (since transform doesn't affect layout)
    // Use a wrapper trick: transform + margin compensation
    var unscaledHeight = container.scrollHeight / (container.dataset.lastScale || 1);
    // Instead of fighting layout, just scale transform-origin top-left and add wrapper padding
    // Actually simpler: just let the transform overflow naturally
}

function setZoom(newScale, immediate){
    newScale = Math.max(MIN_SCALE, Math.min(MAX_SCALE, newScale));
    if (Math.abs(newScale - displayScale) < 0.01) return;
    displayScale = newScale;
    
    // Instant CSS transform
    var container = document.getElementById('pagesContainer');
    container.style.transform = 'scale(' + displayScale + ')';
    
    // Adjust container's top so it doesn't clip
    // Because transform-origin top center, scale grows outward
    // We need wrapper size fix:
    var scaleFix = document.getElementById('scaleFix');
    
    // Update UI
    document.getElementById('zoomInfo').textContent = Math.round(displayScale * 100) + '%';
    showZoomHud(Math.round(displayScale * 100) + '%');
    
    // Re-render canvas at higher quality after zoom stops
    clearTimeout(window._zoomRenderTimer);
    window._zoomRenderTimer = setTimeout(function(){
        // Only re-render if baseScale differs significantly from displayScale
        var needed = Math.abs(baseScale - displayScale) / displayScale > 0.3;
        if (needed) {
            baseScale = displayScale;
            // Re-render only visible pages
            renderVisiblePages();
        }
    }, 400);
}

function renderVisiblePages(){
    var viewer = document.getElementById('viewer');
    var viewerRect = viewer.getBoundingClientRect();
    var scrollTop = viewer.scrollTop;
    var viewerHeight = viewer.clientHeight;
    
    for (var i = 1; i <= totalPages; i++) {
        var wrap = pageWraps[i];
        if (!wrap) continue;
        var top = wrap.offsetTop;
        var bottom = top + wrap.offsetHeight;
        // Check if page is within viewport (with buffer)
        if (bottom >= scrollTop - 500 && top <= scrollTop + viewerHeight + 500) {
            queueRender(i, true);
        }
    }
}

function zoomIn(){
    var step = displayScale < 1 ? 0.2 : (displayScale < 2 ? 0.25 : 0.5);
    setZoom(displayScale + step);
    flashBtn('zoomInfo');
}
function zoomOut(){
    var step = displayScale <= 1 ? 0.2 : (displayScale <= 2 ? 0.25 : 0.5);
    setZoom(displayScale - step);
    flashBtn('zoomInfo');
}
function resetZoom(){
    setZoom(1);
    showToast('Reset to 100%');
}
function fitWidth(){
    var viewer = document.getElementById('viewer');
    var availableWidth = viewer.clientWidth - 32;
    pdfDoc.getPage(1).then(function(page){
        var vp = page.getViewport({ scale: 1.0 });
        var fit = availableWidth / vp.width;
        fit = Math.round(fit * 100) / 100;
        baseScale = fit;
        setZoom(fit);
        setTimeout(function(){ renderVisiblePages(); }, 100);
        showToast('↔ Fit width');
    });
}

/* ============================================================
   HOLD TO ZOOM - Google Maps style
   ============================================================ */
function startHold(dir){
    holdSpeed = 1;
    holdTimer = setTimeout(function(){
        holdTimer = setInterval(function(){
            setZoom(displayScale + (dir * 0.05 * holdSpeed));
            holdSpeed = Math.min(holdSpeed + 0.2, 3);
        }, 50);
    }, 400);
}
function stopHold(){
    if (holdTimer) {
        clearTimeout(holdTimer);
        clearInterval(holdTimer);
        holdTimer = null;
    }
    holdSpeed = 1;
}

/* ============================================================
   PAGE NAVIGATION
   ============================================================ */
function prevPage(){
    if (currentPage > 1) {
        currentPage--;
        scrollToPage(currentPage);
        updateUI();
        queueRender(currentPage, true);
    }
}
function nextPage(){
    if (currentPage < totalPages) {
        currentPage++;
        scrollToPage(currentPage);
        updateUI();
        queueRender(currentPage, true);
    }
}
function scrollToPage(n){
    var el = pageWraps[n];
    if (!el) return;
    var viewer = document.getElementById('viewer');
    var top = el.offsetTop - 20;
    viewer.scrollTo({ top: top, behavior: 'smooth' });
}

function updateUI(){
    document.getElementById('pageInfo').textContent = currentPage + ' / ' + totalPages;
    document.getElementById('zoomInfo').textContent = Math.round(displayScale * 100) + '%';
    document.getElementById('prevBtn').disabled = currentPage <= 1;
    document.getElementById('nextBtn').disabled = currentPage >= totalPages;
    document.getElementById('pnUp').disabled = currentPage <= 1;
    document.getElementById('pnDown').disabled = currentPage >= totalPages;
    document.getElementById('pnCount').innerHTML = currentPage + '<br>/' + totalPages;
}

/* ============================================================
   SCROLL TRACKING
   ============================================================ */
var scrollThrottle;
document.getElementById('viewer').addEventListener('scroll', function(){
    var viewer = this;
    // Lazy render visible pages
    if (scrollThrottle) return;
    scrollThrottle = setTimeout(function(){
        scrollThrottle = null;
        var scrollTop = viewer.scrollTop;
        var viewerHeight = viewer.clientHeight;
        var center = scrollTop + viewerHeight / 2;
        for (var i = 1; i <= totalPages; i++) {
            var wrap = pageWraps[i];
            if (!wrap) continue;
            var top = wrap.offsetTop;
            var bottom = top + wrap.offsetHeight;
            // Update current page
            if (center >= top && center < bottom) {
                if (currentPage !== i) {
                    currentPage = i;
                    updateUI();
                }
            }
            // Lazy render
            if (bottom >= scrollTop - 800 && top <= scrollTop + viewerHeight + 800) {
                queueRender(i, false);
            }
        }
    }, 100);
}, { passive: true });

/* ============================================================
   ZOOM HUD
   ============================================================ */
var hudTimer;
function showZoomHud(text){
    var hud = document.getElementById('zoomHud');
    hud.textContent = text;
    hud.classList.add('show');
    clearTimeout(hudTimer);
    hudTimer = setTimeout(function(){ hud.classList.remove('show'); }, 900);
}

function flashBtn(id){
    var el = document.getElementById(id);
    el.style.transform = 'scale(1.15)';
    setTimeout(function(){ el.style.transform = ''; }, 150);
}

/* ============================================================
   KEYBOARD
   ============================================================ */
document.addEventListener('keydown', function(e){
    if (e.target.tagName === 'INPUT') return;
    if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { e.preventDefault(); nextPage(); }
    else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { e.preventDefault(); prevPage(); }
    else if (e.key === '+' || e.key === '=') { e.preventDefault(); zoomIn(); }
    else if (e.key === '-') { e.preventDefault(); zoomOut(); }
    else if (e.key === '0') { e.preventDefault(); resetZoom(); }
});

/* ============================================================
   PINCH ZOOM (MOBILE) - Smooth
   ============================================================ */
var pinchStartDist = 0;
var pinchStartScale = 1;
var isPinching = false;
var viewer = document.getElementById('viewer');

viewer.addEventListener('touchstart', function(e){
    if (e.touches.length === 2) {
        isPinching = true;
        pinchStartDist = Math.hypot(
            e.touches[0].clientX - e.touches[1].clientX,
            e.touches[0].clientY - e.touches[1].clientY
        );
        pinchStartScale = displayScale;
    }
}, { passive: true });

viewer.addEventListener('touchmove', function(e){
    if (isPinching && e.touches.length === 2) {
        e.preventDefault();
        var dist = Math.hypot(
            e.touches[0].clientX - e.touches[1].clientX,
            e.touches[0].clientY - e.touches[1].clientY
        );
        var ratio = dist / pinchStartDist;
        var newScale = pinchStartScale * ratio;
        // Live update without re-render (smooth)
        newScale = Math.max(MIN_SCALE, Math.min(MAX_SCALE, newScale));
        displayScale = newScale;
        document.getElementById('pagesContainer').style.transform = 'scale(' + displayScale + ')';
        document.getElementById('zoomInfo').textContent = Math.round(displayScale * 100) + '%';
        showZoomHud(Math.round(displayScale * 100) + '%');
    }
}, { passive: false });

viewer.addEventListener('touchend', function(e){
    if (isPinching && e.touches.length < 2) {
        isPinching = false;
        // Re-render after pinch stops
        clearTimeout(window._zoomRenderTimer);
        window._zoomRenderTimer = setTimeout(function(){
            var needed = Math.abs(baseScale - displayScale) / displayScale > 0.3;
            if (needed) {
                baseScale = displayScale;
                renderVisiblePages();
            }
        }, 400);
    }
}, { passive: true });

/* ============================================================
   DOUBLE TAP TO ZOOM
   ============================================================ */
var lastTap = 0;
var tapX = 0, tapY = 0;
viewer.addEventListener('click', function(e){
    // Only on empty area, not on toolbar buttons
    var now = Date.now();
    if (now - lastTap < 350) {
        // Double tap detected
        if (displayScale > 1.1) {
            resetZoom();
        } else {
            setZoom(2);
            showZoomHud('200%');
        }
        lastTap = 0;
    } else {
        lastTap = now;
    }
});

/* ============================================================
   TOAST
   ============================================================ */
function showToast(msg){
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(t._timer);
    t._timer = setTimeout(function(){ t.classList.remove('show'); }, 1500);
}

/* ============================================================
   INIT
   ============================================================ */
updateUI();
setTimeout(function(){ fitWidth(); }, 300);
</script>

<?php endif; ?>

</body></html>
