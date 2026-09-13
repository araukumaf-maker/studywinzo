<?php
ini_set('display_errors', 0);
$adminData = __DIR__.'/admin/data';
$chapterId = preg_replace('/[^a-zA-Z0-9_]/','',$_GET['chapter'] ?? '');
$batchId = preg_replace('/[^a-zA-Z0-9_]/','',$_GET['batch'] ?? '');

require_once __DIR__.'/data_helper.php';
function loadJSON($f, $d=[]){ return getJSON(basename($f), $d); }

$chapters = loadJSON($adminData.'/chapters.json');
$content = loadJSON($adminData.'/content.json');
$batches = loadJSON($adminData.'/batches.json');
$subjects = loadJSON($adminData.'/subjects.json');

$chapter = null;
foreach ($chapters as $c) if ($c['id']===$chapterId) { $chapter = $c; break; }
if (!$chapter) { header('Location: index.php'); exit; }

$batch = null;
foreach ($batches as $b) if ($b['id']===$batchId) { $batch = $b; break; }

$items = array_values(array_filter($content, fn($x)=>$x['chapterId']===$chapterId));
$videos = array_values(array_filter($items, fn($x)=>$x['type']==='video'));
$totalResources = count(array_filter($items, fn($x)=>in_array($x['type'], ['note','dpp','link'])));

// Count videos in all chapters of this batch (for "next video" hint)
$batchChapters = [];
if ($batch) {
    $subIds = [];
    foreach ($subjects as $s) if ($s['batchId']===$batch['id']) $subIds[] = $s['id'];
    $batchChapters = array_values(array_filter($chapters, fn($c)=>in_array($c['subjectId'], $subIds)));
    usort($batchChapters, fn($a,$b)=>($a['order']??0)-($b['order']??0));
}
$currentChapterIndex = -1;
foreach ($batchChapters as $i=>$c) if ($c['id']===$chapterId) { $currentChapterIndex = $i; break; }

// Auto thumbnail from YouTube
function getThumb($videoUrl) {
    if (!$videoUrl) return '';
    if (strpos($videoUrl, 'youtube') !== false || strpos($videoUrl, 'youtu.be') !== false) {
        if (preg_match('/embed\/([a-zA-Z0-9_-]+)/', $videoUrl, $m)) {
            return 'https://img.youtube.com/vi/'.$m[1].'/hqdefault.jpg';
        }
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0"/>
<title><?= htmlspecialchars($chapter['name']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<style>
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent;font-family:'Inter',sans-serif;margin:0;padding:0}
body{background:#030605;color:#fff;min-height:100vh;overflow-x:hidden}
.hdr{padding:16px;display:flex;align-items:center;gap:12px;border-bottom:1px solid #1a2a20;position:sticky;top:0;background:rgba(3,6,5,.95);backdrop-filter:blur(12px);z-index:20}
.back{width:44px;height:44px;border-radius:14px;background:#0e1613;border:1px solid #1f332a;display:flex;align-items:center;justify-content:center;text-decoration:none;flex-shrink:0}
.back i{color:#2cee82;font-size:20px}
.hdr-info{flex:1;min-width:0}
.hdr h1{font-size:16px;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.hdr p{font-size:11px;color:#64748b;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

.wrap{max-width:900px;margin:0 auto;padding:16px;padding-bottom:40px}

/* Section title */
.sec-title{font-size:12px;font-weight:900;color:#94a3b8;letter-spacing:1.5px;text-transform:uppercase;margin:8px 0 14px;padding:0 4px;display:flex;align-items:center;gap:8px}
.sec-title .cnt{margin-left:auto;font-size:11px;font-weight:700;color:#475569;letter-spacing:0;text-transform:none}

/* Video grid */
.video-grid{display:grid;grid-template-columns:1fr;gap:14px}
@media(min-width:640px){.video-grid{grid-template-columns:1fr 1fr}}

.vid-card{background:linear-gradient(145deg,#0a0f0d,#050b09);border:1px solid #1f332a;border-radius:16px;overflow:hidden;text-decoration:none;color:inherit;transition:all .2s;display:block;position:relative}
.vid-card:hover{border-color:rgba(44,238,130,.4);transform:translateY(-3px);box-shadow:0 12px 32px -12px rgba(44,238,130,.3)}
.vid-card:active{transform:scale(.98)}

.vid-thumb{position:relative;width:100%;aspect-ratio:16/9;background:#000;overflow:hidden}
.vid-thumb img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .3s}
.vid-card:hover .vid-thumb img{transform:scale(1.05)}
.vid-thumb-ph{width:100%;height:100%;background:linear-gradient(135deg,#0e2818,#0a1f15);display:flex;align-items:center;justify-content:center;color:#2cee82;font-size:56px}

/* Play overlay */
.vid-play{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:60px;height:60px;border-radius:50%;background:rgba(0,0,0,.7);backdrop-filter:blur(10px);border:2px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;transition:all .2s;z-index:2}
.vid-card:hover .vid-play{background:rgba(16,185,129,.5);border-color:#2cee82;transform:translate(-50%,-50%) scale(1.1)}
.vid-play i{color:#fff;font-size:26px;margin-left:3px}

/* Duration/badge */
.vid-tag{position:absolute;bottom:8px;right:8px;background:rgba(0,0,0,.85);color:#fff;font-size:10.5px;font-weight:800;padding:3px 8px;border-radius:6px;letter-spacing:.4px;z-index:2}

/* Index number badge */
.vid-num{position:absolute;top:8px;left:8px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-size:11px;font-weight:900;padding:4px 9px;border-radius:8px;z-index:2;box-shadow:0 4px 12px rgba(16,185,129,.4)}

/* Info */
.vid-info{padding:12px 14px}
.vid-title{font-size:13.5px;font-weight:700;color:#fff;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.vid-meta{display:flex;align-items:center;gap:8px;margin-top:6px;font-size:10.5px;color:#64748b;font-weight:700}
.vid-meta i{font-size:13px;color:#2cee82}

/* Next chapter hint */
.next-hint{margin-top:24px;background:linear-gradient(145deg,rgba(59,130,246,.08),rgba(37,99,235,.03));border:1px solid rgba(59,130,246,.2);border-radius:16px;padding:16px;display:flex;align-items:center;gap:14px;text-decoration:none;color:inherit;transition:all .2s}
.next-hint:hover{border-color:rgba(59,130,246,.5);transform:translateY(-2px)}
.next-hint .nh-icon{width:44px;height:44px;border-radius:12px;background:rgba(59,130,246,.15);display:flex;align-items:center;justify-content:center;color:#60a5fa;font-size:22px;flex-shrink:0}
.next-hint .nh-info{flex:1;min-width:0}
.next-hint .nh-label{font-size:10.5px;color:#60a5fa;font-weight:800;letter-spacing:1px;text-transform:uppercase}
.next-hint .nh-name{font-size:14px;font-weight:800;color:#fff;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.next-hint .nh-arrow{color:#60a5fa;font-size:20px}

/* Empty */
.no-content{text-align:center;padding:80px 20px;color:#64748b}
.no-content i{font-size:64px;opacity:.25;display:block;margin-bottom:16px;color:#f87171}
.no-content h3{font-size:16px;font-weight:800;color:#94a3b8;margin-bottom:6px}
.no-content p{font-size:13px;line-height:1.5}
</style>
</head>
<body>

<div class="hdr">
<a href="javascript:history.back()" class="back"><i class="ph-bold ph-arrow-left"></i></a>
<div class="hdr-info">
<h1><?= htmlspecialchars($chapter['name']) ?></h1>
<p><?= htmlspecialchars($batch['name'] ?? '') ?> · <?= count($videos) ?> video<?= count($videos)===1?'':'s' ?></p>
</div>
</div>

<div class="wrap">

<?php if (empty($videos)): ?>
<div class="no-content">
<i class="ph-bold ph-video-camera-slash"></i>
<h3>No video lectures yet</h3>
<p>इस chapter में अभी कोई video upload नहीं हुआ<br>जल्द ही आएगा</p>
</div>
<?php else: ?>

<div class="sec-title">
<i class="ph-fill ph-play-circle" style="color:#f87171;font-size:16px"></i>
Video Lectures
<span class="cnt"><?= count($videos) ?> video<?= count($videos)===1?'':'s' ?></span>
</div>

<div class="video-grid">
<?php foreach ($videos as $i=>$vid):
    $customThumb = !empty($vid['thumbnail']) ? 'admin/uploads/thumbnails/'.htmlspecialchars($vid['thumbnail']) : '';
    $autoThumb = getThumb($vid['video_url'] ?? '');
    $thumb = $customThumb ?: $autoThumb;
    $isYt = strpos($vid['video_url'] ?? '', 'youtube') !== false || strpos($vid['video_url'] ?? '', 'youtu.be') !== false;
?>
<a href="watch.php?id=<?= urlencode($vid['id']) ?>" class="vid-card">
<div class="vid-thumb">
<?php if ($thumb): ?>
<img src="<?= $thumb ?>" alt="" />
<?php else: ?>
<div class="vid-thumb-ph"><i class="ph-bold ph-play-circle"></i></div>
<?php endif; ?>
<div class="vid-play"><i class="ph-fill ph-play"></i></div>
<div class="vid-num"><?= $i+1 ?></div>
<div class="vid-tag"><?= $isYt ? 'YT' : 'HLS' ?></div>
</div>
<div class="vid-info">
<div class="vid-title"><?= htmlspecialchars($vid['title']) ?></div>
<div class="vid-meta">
<i class="ph-bold ph-play-circle"></i>
<span>Tap to watch</span>
</div>
</div>
</a>
<?php endforeach; ?>
</div>

<?php endif; ?>

<?php
// Show next chapter hint
$nextChapter = null;
if ($currentChapterIndex >= 0 && $currentChapterIndex < count($batchChapters) - 1) {
    $nextChapter = $batchChapters[$currentChapterIndex + 1];
}
?>
<?php if ($nextChapter): ?>
<a href="chapter.php?chapter=<?= urlencode($nextChapter['id']) ?>&batch=<?= urlencode($batchId) ?>" class="next-hint">
<div class="nh-icon"><i class="ph-bold ph-arrow-right"></i></div>
<div class="nh-info">
<div class="nh-label">Next Chapter</div>
<div class="nh-name"><?= htmlspecialchars($nextChapter['name']) ?></div>
</div>
<i class="ph-bold ph-caret-right nh-arrow"></i>
</a>
<?php endif; ?>

</div>

</body></html>
