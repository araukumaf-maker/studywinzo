<?php
ini_set('display_errors', 0);
require_once __DIR__.'/data_helper.php';
$content = getJSON('content.json', []);
$id = preg_replace('/[^a-zA-Z0-9_]/','',$_GET['id'] ?? '');
$item = null;
foreach ($content as $c) if (($c['id'] ?? '') === $id) { $item = $c; break; }
if (!$item || ($item['type'] ?? '') !== 'video') { header('Location: index.php'); exit; }

$videoUrl = $item['video_url'] ?? '';
$isHls = (strpos($videoUrl, '.m3u8') !== false);
// If HLS, route through local proxy to bypass CORS
$videoProxyUrl = $videoUrl;
if ($isHls) {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:9003';
    $proxyBase = $scheme . '://' . $host . '/hls_proxy.php?url=';
    $videoProxyUrl = $proxyBase . urlencode($videoUrl);
}
$isYoutube = !$isHls && (strpos($videoUrl, 'youtube') !== false || strpos($videoUrl, 'youtu.be') !== false);
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title><?= htmlspecialchars($item['title'] ?? 'Video') ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<style>
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent;font-family:'Inter',sans-serif;margin:0;padding:0}
body{background:#030605;color:#fff;min-height:100vh;overflow-x:hidden}
.player-wrap{position:relative;background:#000;width:100%;user-select:none;overflow:hidden}
video{width:100%;display:block;background:#000;max-height:70vh;object-fit:contain}
.tap-layer{position:absolute;top:0;left:0;right:0;bottom:80px;z-index:1}
.controls{position:absolute;left:0;right:0;bottom:0;padding:14px 16px 16px;background:linear-gradient(180deg,transparent,rgba(0,0,0,.95));z-index:10;opacity:0;transition:opacity .3s}
.controls.show{opacity:1}
.controls.hide{opacity:0;pointer-events:none}
.progress-row{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.timeline{flex:1;height:32px;display:flex;align-items:center;cursor:pointer;position:relative;padding:0 4px;touch-action:none;user-select:none}
.timeline-track{width:100%;height:5px;background:rgba(255,255,255,.2);border-radius:5px;position:relative;transition:height .15s}
.timeline:hover .timeline-track{height:7px}
.timeline.dragging .timeline-track{height:8px}
.timeline-buffer{position:absolute;top:0;left:0;height:100%;background:rgba(255,255,255,.25);border-radius:5px;width:0%;transition:width .3s}
.timeline-fill{position:absolute;top:0;left:0;height:100%;background:linear-gradient(90deg,#10b981,#2cee82);border-radius:5px;width:0%}
.timeline-handle{position:absolute;top:50%;transform:translate(-50%,-50%);width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 0 12px rgba(44,238,130,.9);opacity:0;transition:opacity .2s;pointer-events:none;z-index:3}
.timeline:hover .timeline-handle{opacity:1}
.timeline.dragging .timeline-handle{opacity:1;transform:translate(-50%,-50%) scale(1.3)}
.timeline-preview{position:absolute;bottom:40px;transform:translateX(-50%);background:rgba(0,0,0,.95);border:1px solid #2cee82;color:#fff;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:700;font-family:monospace;white-space:nowrap;pointer-events:none;opacity:0;transition:opacity .15s;z-index:20}
.timeline-preview.show{opacity:1}
.time-info{font-size:12px;font-family:monospace;color:#cbd5e1;white-space:nowrap;font-weight:600;min-width:42px;text-align:center}
.btn-row{display:flex;align-items:center;justify-content:space-between;gap:8px}
.btn-group{display:flex;align-items:center;gap:6px}
.cbtn{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);color:#fff;width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .15s;flex-shrink:0;padding:0;position:relative;z-index:11}
.cbtn:active{transform:scale(.9);background:rgba(44,238,130,.4)}
.cbtn i{font-size:19px;pointer-events:none}
.cbtn.play{width:56px;height:56px;background:linear-gradient(135deg,#10b981,#059669);border:none;box-shadow:0 6px 20px rgba(16,185,129,.5)}
.cbtn.play i{font-size:26px}
.center-overlay{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);z-index:5;pointer-events:none;display:flex;align-items:center;justify-content:center}
.big-play{width:86px;height:86px;background:rgba(0,0,0,.7);backdrop-filter:blur(12px);border:2px solid rgba(255,255,255,.35);border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;pointer-events:auto;transition:all .2s;padding:0}
.big-play:hover{background:rgba(16,185,129,.35);border-color:#2cee82}
.big-play i{font-size:42px;color:#fff;margin-left:4px}
.spinner{width:60px;height:60px;border:4px solid rgba(255,255,255,.15);border-top-color:#2cee82;border-radius:50%;animation:spin .8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.skip-pop{position:absolute;top:45%;transform:translateY(-50%);background:rgba(0,0,0,.8);backdrop-filter:blur(8px);border:2px solid rgba(44,238,130,.5);color:#fff;padding:14px 22px;border-radius:20px;font-size:15px;font-weight:800;display:flex;align-items:center;gap:8px;opacity:0;transition:opacity .3s;pointer-events:none;z-index:6}
.skip-pop.show{opacity:1}
.skip-pop.left{left:30px}
.skip-pop.right{right:30px}
.speed-pop{position:absolute;bottom:90px;right:16px;background:rgba(10,15,12,.98);border:1px solid #1f332a;border-radius:14px;padding:8px;display:none;z-index:20;min-width:130px;box-shadow:0 15px 40px rgba(0,0,0,.8)}
.speed-pop.show{display:block}
.spd-btn{display:block;width:100%;padding:10px 16px;border:none;background:transparent;color:#cbd5e1;font-size:13.5px;text-align:left;cursor:pointer;border-radius:9px;font-family:inherit;font-weight:600}
.spd-btn:hover{background:rgba(44,238,130,.15);color:#fff}
.spd-btn.active{background:linear-gradient(135deg,#10b981,#059669);color:#fff}
.top-bar{padding:14px 16px;display:flex;align-items:center;gap:12px;border-bottom:1px solid #1a2a20;background:rgba(3,6,5,.95);backdrop-filter:blur(12px);position:sticky;top:0;z-index:20}
.back-btn{width:42px;height:42px;border-radius:12px;background:#0e1613;border:1px solid #1f332a;display:flex;align-items:center;justify-content:center;text-decoration:none;flex-shrink:0}
.back-btn i{color:#2cee82;font-size:20px}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:8px;background:rgba(59,130,246,.15);border:1px solid rgba(59,130,246,.3);color:#60a5fa;font-size:10px;font-weight:800}
.badge.live{background:rgba(239,68,68,.15);border-color:rgba(239,68,68,.3);color:#f87171}
.vol-slider{position:absolute;bottom:100%;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.9);border:1px solid #1f332a;border-radius:10px;padding:10px 8px;display:none;z-index:15}
.vol-slider.show{display:block}
.vol-slider input{writing-mode:vertical-lr;direction:rtl;height:80px;width:6px;cursor:pointer;accent-color:#2cee82}
.quality-pop{position:absolute;bottom:90px;right:16px;background:rgba(10,15,12,.98);border:1px solid #1f332a;border-radius:14px;padding:8px;display:none;z-index:20;min-width:120px;box-shadow:0 15px 40px rgba(0,0,0,.8)}
.quality-pop.show{display:block}
</style>
</head>
<body>

<div class="top-bar">
<a href="javascript:history.back()" class="back-btn"><i class="ph-bold ph-arrow-left"></i></a>
<div style="flex:1;min-width:0">
<h1 style="font-size:15px;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($item['title'] ?? 'Video') ?></h1>
<p style="font-size:10.5px;color:#2cee82;margin-top:2px">🎬 Now Playing</p>
</div>
<?php if ($isHls): ?>
<span class="badge">HLS</span>
<?php elseif ($isYoutube): ?>
<span class="badge live">YOUTUBE</span>
<?php endif; ?>
</div>

<?php if ($isYoutube): ?>
<!-- YOUTUBE EMBED -->
<div style="width:100%;aspect-ratio:16/9;background:#000">
<iframe src="<?= htmlspecialchars($videoProxyUrl) ?>" style="width:100%;height:100%;border:0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen></iframe>
</div>

<?php elseif ($isHls): ?>
<!-- HLS PLAYER -->
<div class="player-wrap" id="playerWrap">
<video id="videoEl" playsinline webkit-playsinline preload="metadata"></video>

<div class="tap-layer" id="tapLayer"></div>

<div class="center-overlay" id="centerBox">
<button type="button" class="big-play" id="bigPlay"><i class="ph-fill ph-play"></i></button>
<div class="spinner" id="spinner" style="display:none"></div>
</div>

<div class="skip-pop left" id="skipL"><i class="ph-fill ph-arrow-counter-clockwise"></i> 10s</div>
<div class="skip-pop right" id="skipR"><i class="ph-fill ph-arrow-clockwise"></i> 10s</div>

<div class="speed-pop" id="speedPop">
<button type="button" class="spd-btn" data-speed="0.5">0.5x</button>
<button type="button" class="spd-btn" data-speed="0.75">0.75x</button>
<button type="button" class="spd-btn active" data-speed="1">Normal (1x)</button>
<button type="button" class="spd-btn" data-speed="1.25">1.25x</button>
<button type="button" class="spd-btn" data-speed="1.5">1.5x</button>
<button type="button" class="spd-btn" data-speed="2">2x</button>
</div>

<div class="controls show" id="controls">
<div class="progress-row">
<span class="time-info" id="curTime">0:00</span>
<div class="timeline" id="timeline">
<div class="timeline-track">
<div class="timeline-buffer" id="tlBuffer"></div>
<div class="timeline-fill" id="tlFill"></div>
</div>
<div class="timeline-handle" id="tlHandle" style="left:0%"></div>
<div class="timeline-preview" id="tlPreview">0:00</div>
</div>
<span class="time-info" id="durTime">0:00</span>
</div>

<div class="btn-row">
<div class="btn-group">
<button type="button" class="cbtn" id="btnRewind"><i class="ph-bold ph-arrow-counter-clockwise"></i></button>
<button type="button" class="cbtn play" id="btnPlay"><i class="ph-fill ph-play" id="playIcon"></i></button>
<button type="button" class="cbtn" id="btnForward"><i class="ph-bold ph-arrow-clockwise"></i></button>
</div>
<div class="btn-group">
<button type="button" class="cbtn" id="btnMute"><i class="ph-bold ph-speaker-high" id="muteIcon"></i></button>
<button type="button" class="cbtn" id="btnSpeed"><i class="ph-bold ph-gauge"></i></button>
<button type="button" class="cbtn" id="btnFs"><i class="ph-bold ph-arrows-out" id="fsIcon"></i></button>
</div>
</div>
</div>
</div>

<?php else: ?>
<!-- DIRECT FILE (fallback) -->
<div class="player-wrap" id="playerWrap">
<video id="videoEl" playsinline controls preload="metadata" src="<?= htmlspecialchars($videoProxyUrl) ?>" style="width:100%;max-height:70vh"></video>
</div>
<?php endif; ?>

<?php if (!empty($item['description'])): ?>
<div style="padding:16px;border-top:1px solid #1a2a20">
<div style="font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:.8px;text-transform:uppercase;margin-bottom:8px">Description</div>
<p style="color:#cbd5e1;font-size:13.5px;line-height:1.6"><?= nl2br(htmlspecialchars($item['description'])) ?></p>
</div>
<?php endif; ?>

<?php if ($isHls): ?>
<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.15/dist/hls.min.js"></script>
<script>
(function(){
  var v = document.getElementById('videoEl');
  var wrap = document.getElementById('playerWrap');
  var hlsUrl = <?= json_encode($videoProxyUrl) ?>;

  // Load HLS
  if (window.Hls && Hls.isSupported()) {
    var hls = new Hls({
      enableWorker: true,
      lowLatencyMode: false,
      backBufferLength: 90,
      maxBufferLength: 30,
      maxMaxBufferLength: 60,
      startLevel: -1,
      capLevelToPlayerSize: true,
    });
    hls.loadSource(hlsUrl);
    hls.attachMedia(v);
    hls.on(Hls.Events.MANIFEST_PARSED, function(){
      console.log('[HLS] Loaded:', hls.levels.length, 'quality levels');
    });
    hls.on(Hls.Events.ERROR, function(event, data){
      console.error('[HLS Error]', data);
      if (data.fatal) {
        switch(data.type) {
          case Hls.ErrorTypes.NETWORK_ERROR:
            hls.startLoad(); break;
          case Hls.ErrorTypes.MEDIA_ERROR:
            hls.recoverMediaError(); break;
          default:
            showError('Stream error. Retry करें।'); break;
        }
      }
    });
  } else if (v.canPlayType('application/vnd.apple.mpegurl')) {
    // Safari native HLS
    v.src = hlsUrl;
  } else {
    showError('HLS is not supported in this browser');
  }

  function showError(msg){
    var e = document.createElement('div');
    e.style.cssText = 'position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(239,68,68,.9);color:#fff;padding:16px 22px;border-radius:14px;font-size:13px;font-weight:700;z-index:10;text-align:center;max-width:280px';
    e.textContent = '❌ ' + msg;
    wrap.appendChild(e);
  }

  // ===== PLAYER CONTROLS =====
  var controls = document.getElementById('controls');
  var playBtn = document.getElementById('btnPlay');
  var playIcon = document.getElementById('playIcon');
  var bigPlay = document.getElementById('bigPlay');
  var centerBox = document.getElementById('centerBox');
  var spinner = document.getElementById('spinner');
  var timeline = document.getElementById('timeline');
  var tlFill = document.getElementById('tlFill');
  var tlHandle = document.getElementById('tlHandle');
  var tlBuffer = document.getElementById('tlBuffer');
  var tlPreview = document.getElementById('tlPreview');
  var curTime = document.getElementById('curTime');
  var durTime = document.getElementById('durTime');
  var muteBtn = document.getElementById('btnMute');
  var muteIcon = document.getElementById('muteIcon');
  var fsIcon = document.getElementById('fsIcon');
  var speedPop = document.getElementById('speedPop');
  var tapLayer = document.getElementById('tapLayer');
  var hideTimer, lastTap = 0;

  function fmt(s){
    if (!s || isNaN(s)) return '0:00';
    var m = Math.floor(s/60), sec = Math.floor(s%60);
    return m + ':' + (sec<10?'0':'') + sec;
  }

  function showCtrl(){
    controls.classList.add('show');
    controls.classList.remove('hide');
    clearTimeout(hideTimer);
    if (!v.paused) {
      hideTimer = setTimeout(function(){
        controls.classList.remove('show');
        controls.classList.add('hide');
      }, 3500);
    }
  }

  document.getElementById('btnRewind').addEventListener('click', function(e){
    e.stopPropagation();
    if (!v.duration) return;
    v.currentTime = Math.max(0, v.currentTime - 10);
    var el = document.getElementById('skipL');
    el.classList.add('show'); setTimeout(function(){ el.classList.remove('show'); }, 500);
    showCtrl();
  });
  document.getElementById('btnForward').addEventListener('click', function(e){
    e.stopPropagation();
    if (!v.duration) return;
    v.currentTime = Math.min(v.duration, v.currentTime + 10);
    var el = document.getElementById('skipR');
    el.classList.add('show'); setTimeout(function(){ el.classList.remove('show'); }, 500);
    showCtrl();
  });
  playBtn.addEventListener('click', function(e){ e.stopPropagation(); if (v.paused) v.play(); else v.pause(); showCtrl(); });
  bigPlay.addEventListener('click', function(e){ e.stopPropagation(); v.play(); showCtrl(); });

  muteBtn.addEventListener('click', function(e){
    e.stopPropagation();
    v.muted = !v.muted;
    muteIcon.className = v.muted ? 'ph-bold ph-speaker-slash' : 'ph-bold ph-speaker-high';
    showCtrl();
  });

  document.getElementById('btnSpeed').addEventListener('click', function(e){
    e.stopPropagation();
    speedPop.classList.toggle('show');
    showCtrl();
  });

  speedPop.querySelectorAll('.spd-btn').forEach(function(btn){
    btn.addEventListener('click', function(e){
      e.stopPropagation();
      var sp = parseFloat(this.dataset.speed);
      v.playbackRate = sp;
      speedPop.querySelectorAll('.spd-btn').forEach(function(b){ b.classList.remove('active'); });
      this.classList.add('active');
      speedPop.classList.remove('show');
      showCtrl();
    });
  });

  document.getElementById('btnFs').addEventListener('click', function(e){
    e.stopPropagation();
    if (!document.fullscreenElement && !document.webkitFullscreenElement) {
      enterFullscreen();
    } else {
      exitFullscreen();
    }
  });

  function enterFullscreen(){
    var p;
    if (wrap.requestFullscreen) p = wrap.requestFullscreen();
    else if (wrap.webkitRequestFullscreen) p = wrap.webkitRequestFullscreen();
    else if (v.webkitEnterFullscreen) { v.webkitEnterFullscreen(); return; }
    if (p && p.then) p.then(function(){ setTimeout(lockLandscape, 100); }).catch(function(){ lockLandscape(); });
    else setTimeout(lockLandscape, 300);
  }
  function exitFullscreen(){
    try { if (screen.orientation && screen.orientation.unlock) screen.orientation.unlock(); } catch(e){}
    if (document.exitFullscreen) document.exitFullscreen().catch(function(){});
    else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
  }
  function lockLandscape(){
    try { if (screen.orientation && screen.orientation.lock) screen.orientation.lock('landscape').catch(function(){}); } catch(e){}
  }

  document.addEventListener('fullscreenchange', function(){
    fsIcon.className = document.fullscreenElement ? 'ph-bold ph-arrows-in' : 'ph-bold ph-arrows-out';
    if (!document.fullscreenElement) try { if (screen.orientation) screen.orientation.unlock(); } catch(e){}
  });

  // ===== TIME UPDATE =====
  v.addEventListener('loadedmetadata', function(){
    durTime.textContent = fmt(v.duration);
  });

  v.addEventListener('timeupdate', function(){
    if (v.duration) {
      var pct = (v.currentTime / v.duration) * 100;
      tlFill.style.width = pct + '%';
      tlHandle.style.left = pct + '%';
      curTime.textContent = fmt(v.currentTime);
    }
  });

  // HLS buffer
  v.addEventListener('progress', function(){
    if (v.buffered.length > 0 && v.duration) {
      var buffEnd = v.buffered.end(v.buffered.length - 1);
      tlBuffer.style.width = (buffEnd / v.duration * 100) + '%';
    }
  });

  v.addEventListener('play', function(){
    playIcon.className = 'ph-fill ph-pause';
    bigPlay.style.display = 'none';
    spinner.style.display = 'none';
    showCtrl();
  });
  v.addEventListener('pause', function(){
    playIcon.className = 'ph-fill ph-play';
    bigPlay.style.display = 'flex';
    spinner.style.display = 'none';
    controls.classList.add('show'); controls.classList.remove('hide');
    clearTimeout(hideTimer);
  });
  v.addEventListener('waiting', function(){ spinner.style.display = 'block'; bigPlay.style.display = 'none'; });
  v.addEventListener('playing', function(){ spinner.style.display = 'none'; bigPlay.style.display = 'none'; });
  v.addEventListener('ended', function(){
    bigPlay.style.display = 'flex';
    playIcon.className = 'ph-fill ph-play';
    controls.classList.add('show'); controls.classList.remove('hide');
    clearTimeout(hideTimer);
  });

  // ===== TIMELINE =====
  var seeking = false;
  function getPct(clientX){
    var rect = timeline.getBoundingClientRect();
    return Math.max(0, Math.min(1, (clientX - rect.left) / rect.width));
  }
  function updatePreview(clientX){
    if (!v.duration) return;
    var pct = getPct(clientX);
    tlPreview.textContent = fmt(pct * v.duration);
    tlPreview.style.left = (pct * 100) + '%';
    tlPreview.classList.add('show');
    tlFill.style.width = (pct * 100) + '%';
    tlHandle.style.left = (pct * 100) + '%';
    curTime.textContent = fmt(pct * v.duration);
  }
  function commitSeek(clientX){
    if (!v.duration) return;
    v.currentTime = getPct(clientX) * v.duration;
    tlPreview.classList.remove('show');
  }

  timeline.addEventListener('click', function(e){ e.stopPropagation(); commitSeek(e.clientX); showCtrl(); });
  timeline.addEventListener('mousedown', function(e){ e.preventDefault(); e.stopPropagation(); seeking = true; timeline.classList.add('dragging'); updatePreview(e.clientX); });
  document.addEventListener('mousemove', function(e){ if (seeking) { e.preventDefault(); updatePreview(e.clientX); } });
  document.addEventListener('mouseup', function(e){ if (seeking) { seeking = false; timeline.classList.remove('dragging'); commitSeek(e.clientX); } });
  timeline.addEventListener('touchstart', function(e){ e.stopPropagation(); seeking = true; timeline.classList.add('dragging'); updatePreview(e.touches[0].clientX); }, {passive:true});
  document.addEventListener('touchmove', function(e){ if (seeking) { e.preventDefault(); updatePreview(e.touches[0].clientX); } }, {passive:false});
  document.addEventListener('touchend', function(e){ if (seeking) { seeking = false; timeline.classList.remove('dragging'); var t = e.changedTouches[0]; if (t) commitSeek(t.clientX); } });

  timeline.addEventListener('mousemove', function(e){
    if (seeking || !v.duration) return;
    var pct = getPct(e.clientX);
    tlPreview.textContent = fmt(pct * v.duration);
    tlPreview.style.left = (pct * 100) + '%';
    tlPreview.classList.add('show');
  });
  timeline.addEventListener('mouseleave', function(){ if (!seeking) tlPreview.classList.remove('show'); });

  // ===== TAP LAYER =====
  tapLayer.addEventListener('click', function(e){
    var now = Date.now();
    var rect = tapLayer.getBoundingClientRect();
    var x = e.clientX - rect.left;
    var w = rect.width;
    if (now - lastTap < 300 && Math.abs(x - (window._lastX || 0)) < 60) {
      if (x < w/2) { v.currentTime = Math.max(0, v.currentTime - 10); var el = document.getElementById('skipL'); el.classList.add('show'); setTimeout(function(){ el.classList.remove('show'); }, 500); }
      else { v.currentTime = Math.min(v.duration, v.currentTime + 10); var el = document.getElementById('skipR'); el.classList.add('show'); setTimeout(function(){ el.classList.remove('show'); }, 500); }
      lastTap = 0;
    } else {
      lastTap = now; window._lastX = x;
      setTimeout(function(){
        if (Date.now() - lastTap >= 300) {
          if (controls.classList.contains('show') && !v.paused) { controls.classList.remove('show'); controls.classList.add('hide'); }
          else showCtrl();
        }
      }, 300);
    }
  });

  document.addEventListener('keydown', function(e){
    var t = e.target.tagName;
    if (t === 'INPUT' || t === 'TEXTAREA') return;
    if (e.code === 'Space') { e.preventDefault(); if (v.paused) v.play(); else v.pause(); showCtrl(); }
    else if (e.code === 'ArrowRight') { v.currentTime = Math.min(v.duration, v.currentTime + 5); showCtrl(); }
    else if (e.code === 'ArrowLeft') { v.currentTime = Math.max(0, v.currentTime - 5); showCtrl(); }
    else if (e.code === 'ArrowUp') { v.volume = Math.min(1, v.volume + 0.1); showCtrl(); }
    else if (e.code === 'ArrowDown') { v.volume = Math.max(0, v.volume - 0.1); showCtrl(); }
    else if (e.code === 'KeyF') document.getElementById('btnFs').click();
    else if (e.code === 'KeyM') muteBtn.click();
  });

  document.addEventListener('click', function(e){
    if (!speedPop.contains(e.target) && e.target.id !== 'btnSpeed') speedPop.classList.remove('show');
  });

  showCtrl();
})();
</script>
<?php endif; ?>

</body></html>
