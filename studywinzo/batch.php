<?php
ini_set('display_errors', 0);
$adminData = __DIR__.'/admin/data';
$batchId = preg_replace('/[^a-zA-Z0-9_]/','',$_GET['id'] ?? '');

require_once __DIR__.'/data_helper.php';
function loadJSON($f, $d=[]){ return getJSON(basename($f), $d); }

$batches = loadJSON($adminData.'/batches.json');
$subjects = loadJSON($adminData.'/subjects.json');
$chapters = loadJSON($adminData.'/chapters.json');
$content = loadJSON($adminData.'/content.json');
$settings = loadJSON($adminData.'/settings.json', ['institution_name'=>'StudyWinzo']);

$batch = null;
foreach ($batches as $b) if ($b['id']===$batchId) { $batch = $b; break; }
if (!$batch) { header('Location: index.php'); exit; }

$subs = array_values(array_filter($subjects, fn($s)=>$s['batchId']===$batchId));
usort($subs, fn($a,$b)=>($a['order']??0)-($b['order']??0));
$subIds = array_column($subs, 'id');
$batchChapters = array_values(array_filter($chapters, fn($c)=>in_array($c['subjectId'], $subIds)));
usort($batchChapters, fn($a,$b)=>($a['order']??0)-($b['order']??0));

$instName = $settings['institution_name'] ?? 'StudyWinzo';
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title><?= htmlspecialchars($batch['name']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<style>
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent;font-family:'Inter',sans-serif;margin:0;padding:0}
body{background:#030605;color:#fff;min-height:100vh;overflow-x:hidden}
.hdr{padding:16px;display:flex;align-items:center;gap:12px;border-bottom:1px solid #1a2a20;position:sticky;top:0;background:rgba(3,6,5,.95);backdrop-filter:blur(12px);z-index:10}
.back{width:44px;height:44px;border-radius:14px;background:#0e1613;border:1px solid #1f332a;display:flex;align-items:center;justify-content:center;text-decoration:none;flex-shrink:0}
.back i{color:#2cee82;font-size:20px}
.hdr-info{flex:1;min-width:0}
.hdr-info h1{font-size:16px;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.hdr-info p{font-size:11px;color:#64748b;margin-top:2px}

.wrap{max-width:700px;margin:0 auto;padding:16px;padding-bottom:40px}

/* Batch hero */
.batch-hero{background:linear-gradient(135deg,rgba(16,185,129,.12),rgba(5,150,105,.05));border:1px solid rgba(16,185,129,.25);border-radius:18px;padding:18px;margin-bottom:20px;display:flex;align-items:center;gap:14px}
.batch-hero-thumb{width:70px;height:70px;border-radius:16px;background:#fff;border:2px solid #2cee82;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;padding:4px}
.batch-hero-thumb img{width:100%;height:100%;object-fit:cover;border-radius:12px}
.batch-hero-thumb span{font-weight:900;color:#2cee82;font-size:24px}
.batch-hero-info{flex:1;min-width:0}
.batch-hero-name{font-size:17px;font-weight:900;color:#fff;line-height:1.2;margin-bottom:6px}
.batch-hero-meta{display:flex;gap:14px;flex-wrap:wrap;font-size:11px;color:#94a3b8;font-weight:600}
.batch-hero-meta span{display:flex;align-items:center;gap:4px}
.batch-hero-meta i{color:#2cee82;font-size:13px}

/* Section title */
.sec-title{font-size:13px;font-weight:800;color:#94a3b8;letter-spacing:1px;text-transform:uppercase;margin:20px 0 12px;padding-left:4px;display:flex;align-items:center;gap:8px}
.sec-title .cnt{font-size:11px;color:#475569;font-weight:700;text-transform:none;letter-spacing:0}

/* Chapter list */
.ch-list{display:flex;flex-direction:column;gap:8px}
.ch-item{background:linear-gradient(145deg,#0a0f0d,#050b09);border:1px solid #1f332a;border-radius:14px;padding:14px;display:flex;align-items:center;gap:14px;text-decoration:none;color:inherit;transition:all .15s}
.ch-item:hover{border-color:rgba(44,238,130,.4);transform:translateX(3px)}
.ch-item:active{transform:scale(.98)}
.ch-num{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#0e2818,#0a1f15);border:1px solid rgba(44,238,130,.3);color:#2cee82;font-weight:900;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.ch-body{flex:1;min-width:0}
.ch-name{font-size:14.5px;font-weight:800;color:#fff;line-height:1.3;word-wrap:break-word}
.ch-meta{display:flex;gap:6px;margin-top:6px;flex-wrap:wrap}
.ch-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:8px;font-size:10px;font-weight:800;letter-spacing:.3px}
.c-note{background:rgba(59,130,246,.15);color:#60a5fa}
.c-dpp{background:rgba(168,85,247,.15);color:#c084fc}
.c-video{background:rgba(239,68,68,.15);color:#f87171}
.c-link{background:rgba(251,146,60,.15);color:#fb923c}
.ch-arrow{color:#2cee82;font-size:16px;flex-shrink:0}

.no-content{text-align:center;padding:80px 20px;color:#64748b}
.no-content i{font-size:56px;opacity:.25;display:block;margin-bottom:14px}
.no-content h3{font-size:16px;font-weight:700;color:#94a3b8;margin-bottom:6px}
.no-content p{font-size:13px}
</style>
</head>
<body>

<div class="hdr">
<a href="javascript:history.back()" class="back"><i class="ph-bold ph-arrow-left"></i></a>
<div class="hdr-info">
<h1><?= htmlspecialchars($batch['name']) ?></h1>
<p><?= count($batchChapters) ?> chapters · Free access</p>
</div>
</div>

<div class="wrap">

<div class="batch-hero">
<div class="batch-hero-thumb">
<?php if (!empty($batch['image'])): ?>
<img src="admin/uploads/batches/<?= htmlspecialchars($batch['image']) ?>" decoding="sync" fetchpriority="high" loading="eager"/>
<?php else: ?>
<span><?= strtoupper(substr($batch['name'],0,1)) ?></span>
<?php endif; ?>
</div>
<div class="batch-hero-info">
<div class="batch-hero-name"><?= htmlspecialchars($batch['name']) ?></div>
<div class="batch-hero-meta">
<span><i class="ph-bold ph-book-open"></i> <?= count($batchChapters) ?> Chapters</span>
<span><i class="ph-bold ph-infinity"></i> Lifetime</span>
<span><i class="ph-fill ph-check-circle"></i> FREE</span>
</div>
</div>
</div>

<?php if (empty($batchChapters)): ?>
<div class="no-content">
<i class="ph-bold ph-book-open"></i>
<h3>No chapters yet</h3>
<p>Content coming soon</p>
</div>
<?php else: ?>

<div class="sec-title">📚 All Chapters <span class="cnt">(<?= count($batchChapters) ?>)</span></div>

<div class="ch-list">
<?php foreach ($batchChapters as $i=>$c):
    $items = array_filter($content, fn($x)=>$x['chapterId']===$c['id']);
    $nNotes = count(array_filter($items, fn($x)=>$x['type']==='note'));
    $nDpps = count(array_filter($items, fn($x)=>$x['type']==='dpp'));
    $nVideos = count(array_filter($items, fn($x)=>$x['type']==='video'));
    $nLinks = count(array_filter($items, fn($x)=>$x['type']==='link'));
    $total = $nNotes + $nDpps + $nVideos + $nLinks;
?>
<a href="chapter.php?chapter=<?= urlencode($c['id']) ?>&batch=<?= urlencode($batchId) ?>" class="ch-item">
<div class="ch-num"><?= $i+1 ?></div>
<div class="ch-body">
<div class="ch-name"><?= htmlspecialchars($c['name']) ?></div>
<div class="ch-meta">
<?php if ($nVideos): ?><span class="ch-badge c-video"><i class="ph-bold ph-play-circle"></i> <?= $nVideos ?> Videos</span><?php endif; ?>
<?php if ($nNotes): ?><span class="ch-badge c-note"><i class="ph-bold ph-file-pdf"></i> <?= $nNotes ?> Notes</span><?php endif; ?>
<?php if ($nDpps): ?><span class="ch-badge c-dpp"><i class="ph-bold ph-note-pencil"></i> <?= $nDpps ?> DPP</span><?php endif; ?>
<?php if ($nLinks): ?><span class="ch-badge c-link"><i class="ph-bold ph-link"></i> <?= $nLinks ?> Links</span><?php endif; ?>
<?php if ($total === 0): ?><span style="font-size:11px;color:#475569">Coming soon</span><?php endif; ?>
</div>
</div>
<i class="ph-bold ph-caret-right ch-arrow"></i>
</a>
<?php endforeach; ?>
</div>

<?php endif; ?>
</div>

</body></html>
