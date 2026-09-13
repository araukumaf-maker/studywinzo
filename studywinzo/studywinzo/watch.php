<?php
ini_set('display_errors', 0);
$adminData = __DIR__.'/admin/data';
require_once __DIR__.'/data_helper.php';
$vidId = preg_replace('/[^a-zA-Z0-9_]/','',$_GET['id'] ?? '');

function loadJSON($f, $d=[]){ return getJSON(basename($f), $d); }

$content = loadJSON($adminData.'/content.json');
$chapters = loadJSON($adminData.'/chapters.json');
$subjects = loadJSON($adminData.'/subjects.json');
$batches = loadJSON($adminData.'/batches.json');
$settings = loadJSON($adminData.'/settings.json', ['institution_name'=>'StudyWinzo']);

$video = null;
foreach ($content as $c) if ($c['id']===$vidId && $c['type']==='video') { $video = $c; break; }
if (!$video) { header('Location: index.php'); exit; }

$chapter = null;
foreach ($chapters as $c) if ($c['id']===$video['chapterId']) { $chapter = $c; break; }

$subject = null;
if ($chapter) foreach ($subjects as $s) if ($s['id']===$chapter['subjectId']) { $subject = $s; break; }

$batch = null;
if ($subject) foreach ($batches as $b) if ($b['id']===$subject['batchId']) { $batch = $b; break; }

// Chapter resources
$chapterNotes = [];
$chapterDpps = [];
$chapterLinks = [];
if ($chapter) {
    $chapterNotes = array_values(array_filter($content, fn($x)=>$x['chapterId']===$chapter['id'] && $x['type']==='note'));
    $chapterDpps = array_values(array_filter($content, fn($x)=>$x['chapterId']===$chapter['id'] && $x['type']==='dpp'));
    $chapterLinks = array_values(array_filter($content, fn($x)=>$x['chapterId']===$chapter['id'] && $x['type']==='link'));
}

// Related videos in same chapter
$relatedVideos = [];
if ($chapter) {
    $relatedVideos = array_values(array_filter($content, fn($x)=>$x['chapterId']===$chapter['id'] && $x['type']==='video' && $x['id']!==$vidId));
}

$videoUrl = $video['video_url'] ?? '';
$isYt = strpos($videoUrl, 'youtube') !== false;
$isHls = strpos($videoUrl, '.m3u8') !== false;
$instName = $settings['institution_name'] ?? 'StudyWinzo';
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0"/>
<title><?= htmlspecialchars($video['title']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<style>
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent;font-family:'Inter',sans-serif;margin:0;padding:0}
body{background:#030605;color:#fff;min-height:100vh;overflow-x:hidden}
.wrap{max-width:900px;margin:0 auto;padding-bottom:40px}

/* ============ PLAYER AREA ============ */
.player-shell{position:relative;background:#000;width:100%;user-select:none;overflow:hidden}
video{width:100%;display:block;background:#000;max-height:56.25vw;object-fit:contain}
.yt-frame{width:100%;aspect-ratio:16/9;background:#000}
.yt-frame iframe{width:100%;height:100%;border:0;display:block}

/* Overlay controls */
.controls{position:absolute;left:0;right:0;bottom:0;padding:12px 16px 14px;background:linear-gradient(180deg,transparent,rgba(0,0,0,.95));z-index:10;opacity:1;transition:opacity .3s}
.controls.hide{opacity:0;pointer-events:none}
.ctrl-row{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:10px}
.ctrl-btn{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);color:#fff;width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0}
.ctrl-btn i{font-size:19px;pointer-events:none}
.ctrl-btn.play{background:linear-gradient(135deg,#10b981,#059669);border:none;width:52px;height:52px}
.ctrl-btn.play i{font-size:24px}

/* Timeline */
.timeline{flex:1;height:28px;display:flex;align-items:center;cursor:pointer;position:relative;padding:0 2px}
.timeline-track{width:100%;height:4px;background:rgba(255,255,255,.2);border-radius:4px;position:relative}
.timeline-track:hover{height:6px}
.timeline-fill{position:absolute;top:0;left:0;height:100%;background:linear-gradient(90deg,#10b981,#2cee82);border-radius:4px;width:0%}
.timeline-handle{position:absolute;top:50%;transform:translate(-50%,-50%);width:14px;height:14px;border-radius:50%;background:#fff;box-shadow:0 0 10px rgba(44,238,130,.8);opacity:0}
.timeline:hover .timeline-handle{opacity:1}
.time-info{font-size:11.5px;font-family:monospace;color:#cbd5e1;font-weight:600;min-width:42px;text-align:center}

/* ============ INFO AREA ============ */
.info-bar{padding:16px;display:flex;align-items:flex-start;gap:12px;border-bottom:1px solid #1a2a20}
.info-main{flex:1;min-width:0}
.info-title{font-size:16px;font-weight:800;color:#fff;line-height:1.3;word-wrap:break-word}
.info-path{font-size:11.5px;color:#64748b;margin-top:6px;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.info-path .sep{color:#334155}
.info-path .hl{color:#2cee82;font-weight:700}

/* 3-DOT MENU BUTTON */
.dots-btn{width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0}
.dots-btn:hover{background:rgba(44,238,130,.15);border-color:rgba(44,238,130,.4)}
.dots-btn i{font-size:22px}

/* DROPDOWN MENU */
.menu-bg{position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(2px);z-index:50;display:none;opacity:0;transition:opacity .2s}
.menu-bg.show{display:block;opacity:1}
.menu-sheet{position:fixed;bottom:0;left:0;right:0;background:#0b1220;border-radius:24px 24px 0 0;border-top:1px solid rgba(59,130,246,.25);padding:20px;z-index:60;transform:translateY(100%);transition:transform .3s}
.menu-sheet.show{transform:translateY(0)}
.sheet-handle{width:40px;height:4px;background:#374151;border-radius:2px;margin:0 auto 18px}
.sheet-title{font-size:12px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:1.2px;margin-bottom:12px}
.menu-item{display:flex;align-items:center;gap:14px;padding:14px;border-radius:12px;background:#0a0f1a;border:1px solid #1f2937;margin-bottom:8px;text-decoration:none;color:inherit;cursor:pointer;transition:all .15s}
.menu-item:hover{border-color:rgba(59,130,246,.5);background:#0e1728}
.menu-icon{width:44px;height:44px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.mi-note{background:rgba(59,130,246,.15);color:#60a5fa}
.mi-dpp{background:rgba(168,85,247,.15);color:#c084fc}
.mi-link{background:rgba(251,146,60,.15);color:#fb923c}
.mi-play{background:rgba(239,68,68,.15);color:#f87171}
.menu-info{flex:1;min-width:0}
.menu-name{font-size:13.5px;font-weight:700;color:#fff}
.menu-desc{font-size:11px;color:#64748b;margin-top:2px}
.menu-count{background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-size:10.5px;font-weight:800;padding:3px 9px;border-radius:10px}

/* Sections below */
.sec{padding:16px;border-bottom:1px solid #1a2a20}
.sec-title{font-size:13px;font-weight:800;color:#fff;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.sec-title i{color:#60a5fa}
.sec-title .cnt{font-size:11px;color:#64748b;font-weight:600;margin-left:auto}

/* Resource items */
.res-item{display:flex;align-items:center;gap:12px;padding:11px 12px;border-radius:12px;background:#0a0f0d;border:1px solid #1f332a;margin-bottom:8px;text-decoration:none;color:inherit;transition:all .15s}
.res-item:hover{border-color:rgba(44,238,130,.4);transform:translateX(2px)}
.res-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.res-info{flex:1;min-width:0}
.res-title{font-size:12.5px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.res-sub{font-size:10.5px;color:#64748b;margin-top:2px}
.res-action{font-size:11px;font-weight:800;color:#2cee82;flex-shrink:0}

/* Related videos */
.rel-grid{display:grid;grid-template-columns:1fr;gap:10px}
.rel-item{display:flex;gap:10px;padding:10px;border-radius:12px;background:#0a0f0d;border:1px solid #1f332a;text-decoration:none;color:inherit;transition:all .15s}
.rel-item:hover{border-color:rgba(44,238,130,.4)}
.rel-thumb{width:100px;height:56px;border-radius:9px;background:#000;overflow:hidden;flex-shrink:0;position:relative}
.rel-thumb img{width:100%;height:100%;object-fit:cover}
.rel-thumb .ph{width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#2cee82;font-size:22px}
.rel-info{flex:1;min-width:0;display:flex;flex-direction:column;justify-content:center}
.rel-title{font-size:12.5px;font-weight:700;color:#fff;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.rel-meta{font-size:10px;color:#64748b;margin-top:3px}

@media(min-width:600px){.rel-grid{grid-template-columns:1fr 1fr}}
</style>
</head>
<body>

<div class="wrap">

<?php if ($isYt): ?>
<!-- YOUTUBE -->
<div class="yt-frame">
<iframe src="<?= htmlspecialchars($videoUrl) ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen></iframe>
</div>

<?php elseif ($isHls): ?>
<!-- HLS CUSTOM PLAYER -->
<div class="player-shell" id="playerShell">
<video id="videoEl" playsinline webkit-playsinline preload="metadata"></video>
<div style="position:absolute;inset:0;bottom:60px;z-index:1" id="tapLayer" onclick="toggleControls()"></div>

<div class="controls" id="controls">
<div style="display:flex;align-items:center;gap:10px">
<span class="time-info" id="curTime">0:00</span>
<div class="timeline" id="timeline">
<div class="timeline-track">
<div class="timeline-fill" id="tlFill"></div>
</div>
<div class="timeline-handle" id="tlHandle"></div>
</div>
<span class="time-info" id="durTime">0:00</span>
</div>

<div class="ctrl-row">
<div style="display:flex;gap:8px">
<button class="ctrl-btn" onclick="skip(-10)"><i class="ph-bold ph-arrow-counter-clockwise"></i></button>
<button class="ctrl-btn play" id="playBtn" onclick="togglePlay()"><i class="ph-fill ph-play" id="playIcon"></i></button>
<button class="ctrl-btn" onclick="skip(10)"><i class="ph-bold ph-arrow-clockwise"></i></button>
</div>
<div style="display:flex;gap:8px">
<button class="ctrl-btn" onclick="toggleMute()"><i class="ph-bold ph-speaker-high" id="muteIcon"></i></button>
<button class="ctrl-btn" onclick="toggleFS()"><i class="ph-bold ph-arrows-out"></i></button>
</div>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.15/dist/hls.min.js"></script>
<script>
(function(){
  var v = document.getElementById('videoEl');
  var hlsUrl = 'hls_proxy.php?url=' + encodeURIComponent(<?= json_encode($videoUrl) ?>);
  if (window.Hls && Hls.isSupported()) {
    var hls = new Hls({enableWorker:true, maxBufferLength:30});
    hls.loadSource(hlsUrl);
    hls.attachMedia(v);
    hls.on(Hls.Events.ERROR, function(e,d){
      if (d.fatal) {
        if (d.type === Hls.ErrorTypes.NETWORK_ERROR) hls.startLoad();
        else if (d.type === Hls.ErrorTypes.MEDIA_ERROR) hls.recoverMediaError();
      }
    });
  } else if (v.canPlayType('application/vnd.apple.mpegurl')) {
    v.src = hlsUrl;
  }
  var ctrlHide, dragging=false;
  function fmt(s){ if(!s||isNaN(s))return '0:00'; var m=Math.floor(s/60),x=Math.floor(s%60); return m+':'+(x<10?'0':'')+x; }
  window.togglePlay = function(){ v.paused ? v.play() : v.pause(); };
  window.skip = function(s){ v.currentTime = Math.max(0, Math.min(v.duration||0, v.currentTime+s)); };
  window.toggleMute = function(){ v.muted=!v.muted; document.getElementById('muteIcon').className = v.muted?'ph-bold ph-speaker-slash':'ph-bold ph-speaker-high'; };
  window.toggleFS = function(){ if(!document.fullscreenElement){ document.getElementById('playerShell').requestFullscreen && document.getElementById('playerShell').requestFullscreen(); } else document.exitFullscreen(); };
  window.toggleControls = function(){ document.getElementById('controls').classList.toggle('hide'); };
  v.addEventListener('timeupdate', function(){
    if (v.duration) {
      var p = (v.currentTime/v.duration)*100;
      document.getElementById('tlFill').style.width = p+'%';
      document.getElementById('tlHandle').style.left = p+'%';
      document.getElementById('curTime').textContent = fmt(v.currentTime);
    }
  });
  v.addEventListener('loadedmetadata', function(){ document.getElementById('durTime').textContent = fmt(v.duration); });
  v.addEventListener('play', function(){ document.getElementById('playIcon').className='ph-fill ph-pause'; });
  v.addEventListener('pause', function(){ document.getElementById('playIcon').className='ph-fill ph-play'; });
  var tl = document.getElementById('timeline');
  function seek(e){ var r=tl.getBoundingClientRect(); var p=Math.max(0,Math.min(1,(e.clientX-r.left)/r.width)); if(v.duration) v.currentTime=p*v.duration; }
  tl.addEventListener('click', seek);
  tl.addEventListener('mousedown', function(){dragging=true;});
  document.addEventListener('mousemove', function(e){if(dragging)seek(e);});
  document.addEventListener('mouseup', function(){dragging=false;});
})();
</script>
<?php endif; ?>

<!-- ============ INFO BAR + 3-DOT ============ -->
<div class="info-bar">
<div class="info-main">
<div class="info-title"><?= htmlspecialchars($video['title']) ?></div>
<div class="info-path">
<?php if ($batch): ?><span class="hl"><?= htmlspecialchars($batch['name']) ?></span><span class="sep">›</span><?php endif; ?>
<?php if ($subject): ?><span><?= htmlspecialchars($subject['name']) ?></span><span class="sep">›</span><?php endif; ?>
<?php if ($chapter): ?><span><?= htmlspecialchars($chapter['name']) ?></span><?php endif; ?>
</div>
</div>
<button class="dots-btn" onclick="openMenu()" aria-label="Options">
<i class="ph-bold ph-dots-three-vertical"></i>
</button>
</div>

<!-- ============ CHAPTER RESOURCES BELOW ============ -->
<?php if (!empty($chapterNotes) || !empty($chapterDpps) || !empty($chapterLinks)): ?>
<div class="sec">
<div class="sec-title">
<i class="ph-bold ph-books"></i>
Study Material for <?= htmlspecialchars($chapter['name'] ?? '') ?>
<span class="cnt"><?= count($chapterNotes)+count($chapterDpps)+count($chapterLinks) ?> items</span>
</div>

<?php foreach ($chapterNotes as $it): ?>
<a href="view.php?id=<?= urlencode($it['id']) ?>" target="_blank" class="res-item">
<div class="res-icon" style="background:rgba(59,130,246,.15);color:#60a5fa"><i class="ph-bold ph-file-pdf"></i></div>
<div class="res-info">
<div class="res-title"><?= htmlspecialchars($it['title']) ?></div>
<div class="res-sub">📄 Note</div>
</div>
<div class="res-action">Open →</div>
</a>
<?php endforeach; ?>

<?php foreach ($chapterDpps as $it): ?>
<a href="view.php?id=<?= urlencode($it['id']) ?>" target="_blank" class="res-item">
<div class="res-icon" style="background:rgba(168,85,247,.15);color:#c084fc"><i class="ph-bold ph-note-pencil"></i></div>
<div class="res-info">
<div class="res-title"><?= htmlspecialchars($it['title']) ?></div>
<div class="res-sub">📝 DPP</div>
</div>
<div class="res-action">Open →</div>
</a>
<?php endforeach; ?>

<?php foreach ($chapterLinks as $it): ?>
<a href="<?= htmlspecialchars($it['external_url']) ?>" target="_blank" class="res-item">
<div class="res-icon" style="background:rgba(251,146,60,.15);color:#fb923c"><i class="ph-bold ph-link"></i></div>
<div class="res-info">
<div class="res-title"><?= htmlspecialchars($it['title']) ?></div>
<div class="res-sub">🔗 Link</div>
</div>
<div class="res-action">Open →</div>
</a>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ============ MORE VIDEOS IN CHAPTER ============ -->
<?php if (!empty($relatedVideos)): ?>
<div class="sec">
<div class="sec-title">
<i class="ph-bold ph-play-circle"></i>
More videos in this chapter
<span class="cnt"><?= count($relatedVideos) ?></span>
</div>
<div class="rel-grid">
<?php foreach ($relatedVideos as $rv):
    $thumb = '';
    if (strpos($rv['video_url'] ?? '', 'youtube') !== false && preg_match('/embed\/([a-zA-Z0-9_-]+)/', $rv['video_url'], $m)) {
        $thumb = 'https://img.youtube.com/vi/'.$m[1].'/mqdefault.jpg';
    }
?>
<a href="watch.php?id=<?= urlencode($rv['id']) ?>" class="rel-item">
<div class="rel-thumb">
<?php if ($thumb): ?><img src="<?= htmlspecialchars($thumb) ?>"/><?php else: ?><div class="ph"><i class="ph-bold ph-play-circle"></i></div><?php endif; ?>
</div>
<div class="rel-info">
<div class="rel-title"><?= htmlspecialchars($rv['title']) ?></div>
<div class="rel-meta">▶ Click to play</div>
</div>
</a>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>

</div>

<!-- ============ 3-DOT MENU SHEET ============ -->
<div class="menu-bg" id="menuBg" onclick="closeMenu()"></div>
<div class="menu-sheet" id="menuSheet">
<div class="sheet-handle"></div>
<div class="sheet-title">📚 Chapter Resources — <?= htmlspecialchars($chapter['name'] ?? '') ?></div>

<?php if (!empty($chapterNotes)): ?>
<a href="#notes" onclick="scrollToSec('notes');closeMenu()" class="menu-item">
<div class="menu-icon mi-note"><i class="ph-bold ph-file-pdf"></i></div>
<div class="menu-info">
<div class="menu-name">Notes</div>
<div class="menu-desc">PDF study material</div>
</div>
<div class="menu-count"><?= count($chapterNotes) ?></div>
</a>
<?php endif; ?>

<?php if (!empty($chapterDpps)): ?>
<a href="#dpps" onclick="scrollToSec('dpps');closeMenu()" class="menu-item">
<div class="menu-icon mi-dpp"><i class="ph-bold ph-note-pencil"></i></div>
<div class="menu-info">
<div class="menu-name">DPP</div>
<div class="menu-desc">Daily practice problems</div>
</div>
<div class="menu-count"><?= count($chapterDpps) ?></div>
</a>
<?php endif; ?>

<?php if (!empty($chapterLinks)): ?>
<a href="#links" onclick="scrollToSec('links');closeMenu()" class="menu-item">
<div class="menu-icon mi-link"><i class="ph-bold ph-link"></i></div>
<div class="menu-info">
<div class="menu-name">Extra Links</div>
<div class="menu-desc">External resources</div>
</div>
<div class="menu-count"><?= count($chapterLinks) ?></div>
</a>
<?php endif; ?>

<?php if (!empty($relatedVideos)): ?>
<a href="#related" onclick="scrollToSec('related');closeMenu()" class="menu-item">
<div class="menu-icon mi-play"><i class="ph-bold ph-play-circle"></i></div>
<div class="menu-info">
<div class="menu-name">More Videos</div>
<div class="menu-desc">Other lectures in this chapter</div>
</div>
<div class="menu-count"><?= count($relatedVideos) ?></div>
</a>
<?php endif; ?>

<?php if (empty($chapterNotes) && empty($chapterDpps) && empty($chapterLinks) && empty($relatedVideos)): ?>
<div style="text-align:center;padding:30px;color:#64748b;font-size:13px">
<i class="ph-bold ph-folder-open" style="font-size:36px;opacity:.3;display:block;margin-bottom:8px"></i>
No extra resources for this chapter yet
</div>
<?php endif; ?>

<button onclick="closeMenu()" style="width:100%;padding:14px;border-radius:12px;background:#1e293b;color:#cbd5e1;border:none;font-weight:700;margin-top:8px;font-size:13.5px;cursor:pointer">Close</button>
</div>

<script>
function openMenu(){ document.getElementById('menuBg').classList.add('show'); document.getElementById('menuSheet').classList.add('show'); document.body.style.overflow='hidden'; }
function closeMenu(){ document.getElementById('menuBg').classList.remove('show'); document.getElementById('menuSheet').classList.remove('show'); document.body.style.overflow=''; }
function scrollToSec(id){ var el = document.querySelector('.sec'); if(el) el.scrollIntoView({behavior:'smooth'}); }
</script>

</body></html>
