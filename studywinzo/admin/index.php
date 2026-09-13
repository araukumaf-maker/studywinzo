<?php
require_once 'config.php';
requireAdmin();
require_once '_premium.php';

$batches = getJSON('batches.json', []);
$subjects = getJSON('subjects.json', []);
$chapters = getJSON('chapters.json', []);
$content = getJSON('content.json', []);
$insts = getJSON('institutions.json', []);

$batchCount = count($batches);
$subjectCount = count($subjects);
$chapterCount = count($chapters);
$contentCount = count($content);
$instCount = count($insts);
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0"/>
<title>Admin Dashboard — StudyWinzo</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<?php renderPremiumCSS(); ?>
  <link rel="stylesheet" href="../assets/premium.css">
</head>
<body>

<?php renderPremiumHeader($pageInfo, $settings, $popup, $currentPage); ?>

<div class="adm-main">
<main class="adm-content">

<!-- Stats -->
<div class="stat-grid">
<a href="view-all.php?type=institutions" class="stat-premium" style="color:#3b82f6;text-decoration:none;cursor:pointer;display:block">
<div class="stat-head">
<div class="stat-label">Institutions</div>
<div class="stat-icon-box" style="background:rgba(59,130,246,.15);color:#60a5fa"><i class="ph-bold ph-buildings"></i></div>
</div>
<div class="stat-value"><?= $instCount ?></div>
<div class="stat-note">Coaching institutes</div>
</a>

<a href="view-all.php?type=batches" class="stat-premium" style="color:#a855f7;text-decoration:none;cursor:pointer;display:block">
<div class="stat-head">
<div class="stat-label">Batches</div>
<div class="stat-icon-box" style="background:rgba(168,85,247,.15);color:#c084fc"><i class="ph-bold ph-stack"></i></div>
</div>
<div class="stat-value"><?= $batchCount ?></div>
<div class="stat-note">Active batches</div>
</a>

<a href="view-all.php?type=subjects" class="stat-premium" style="color:#10b981;text-decoration:none;cursor:pointer;display:block">
<div class="stat-head">
<div class="stat-label">Subjects</div>
<div class="stat-icon-box" style="background:rgba(16,185,129,.15);color:#34d399"><i class="ph-bold ph-book-open"></i></div>
</div>
<div class="stat-value"><?= $subjectCount ?></div>
<div class="stat-note">Total subjects</div>
</a>

<a href="view-all.php?type=chapters" class="stat-premium" style="color:#fb923c;text-decoration:none;cursor:pointer;display:block">
<div class="stat-head">
<div class="stat-label">Chapters</div>
<div class="stat-icon-box" style="background:rgba(251,146,60,.15);color:#fb923c"><i class="ph-bold ph-list-numbers"></i></div>
</div>
<div class="stat-value"><?= $chapterCount ?></div>
<div class="stat-note">Total chapters</div>
</a>

<a href="view-all.php?type=content" class="stat-premium" style="color:#ec4899;text-decoration:none;cursor:pointer;display:block">
<div class="stat-head">
<div class="stat-label">Content</div>
<div class="stat-icon-box" style="background:rgba(236,72,153,.15);color:#f472b6"><i class="ph-bold ph-files"></i></div>
</div>
<div class="stat-value"><?= $contentCount ?></div>
<div class="stat-note">Notes · DPP · Video</div>
</a>
</div><!-- Quick Actions -->
<div class="section-header">
<h2 class="section-title"><i class="ph-bold ph-lightning"></i> Quick Actions</h2>
</div>
<div class="action-grid" style="margin-bottom:32px">

<a href="batches.php" class="nav-item"><i class="ph-bold ph-stack"></i> Batch Manager</a>
<a href="institutions.php" class="action-premium" style="color:#a855f7">
<div class="action-icon-box" style="background:rgba(168,85,247,.15);color:#c084fc"><i class="ph-bold ph-buildings"></i></div>
<h3 class="action-title">Institutions</h3>
<p class="action-desc">PW, Next Toppers जैसे coaching institutes add / edit करो</p>
<div class="action-arrow" style="color:#c084fc">Manage →</div>
</a>

<a href="manage.php" class="action-premium" style="color:#3b82f6">
<div class="action-icon-box" style="background:rgba(59,130,246,.15);color:#60a5fa"><i class="ph-bold ph-books"></i></div>
<h3 class="action-title">Content Manager</h3>
<p class="action-desc">Batches, subjects, chapters, notes, DPPs, videos</p>
<div class="action-arrow" style="color:#60a5fa">Open →</div>
</a>

<a href="popup-admin.php" class="action-premium" style="color:#ec4899">
<div class="action-icon-box" style="background:rgba(236,72,153,.15);color:#f472b6"><i class="ph-bold ph-megaphone"></i></div>
<h3 class="action-title">Popup Manager</h3>
<p class="action-desc">Telegram / promo popup settings</p>
<div class="action-arrow" style="color:#f472b6">
<span style="padding:2px 8px;border-radius:10px;font-size:9px;font-weight:800;background:<?= !empty($popup['enabled'])?'rgba(16,185,129,.15)':'rgba(239,68,68,.15)' ?>;color:<?= !empty($popup['enabled'])?'#4ade80':'#f87171' ?>">
<?= !empty($popup['enabled'])?'● ACTIVE':'● OFF' ?>
</span>
<span>Configure →</span>
</div>
</a>

<a href="settings-admin.php" class="action-premium" style="color:#10b981">
<div class="action-icon-box" style="background:rgba(16,185,129,.15);color:#34d399"><i class="ph-bold ph-gear"></i></div>
<h3 class="action-title">Settings</h3>
<p class="action-desc">Logo, institution name और platform config</p>
<div class="action-arrow" style="color:#34d399">Open →</div>
</a>

<a href="api.php?action=all" target="_blank" class="action-premium" style="color:#f97316">
<div class="action-icon-box" style="background:rgba(249,115,22,.15);color:#fb923c"><i class="ph-bold ph-code"></i></div>
<h3 class="action-title">Public API</h3>
<p class="action-desc">सारा data JSON format में देखो</p>
<div class="action-arrow" style="color:#fb923c">View JSON →</div>
</a>

<a href="../index.php" target="_blank" class="action-premium" style="color:#06b6d4">
<div class="action-icon-box" style="background:rgba(6,182,212,.15);color:#22d3ee"><i class="ph-bold ph-globe"></i></div>
<h3 class="action-title">View Site</h3>
<p class="action-desc">User panel को नए tab में open करो</p>
<div class="action-arrow" style="color:#22d3ee">Open ↗</div>
</a>

</div>

<!-- Recent Batches -->
<div class="section-header">
<h2 class="section-title"><i class="ph-bold ph-stack"></i> Recent Batches</h2>
<a href="manage.php" class="btn-premium btn-sm btn-gray">View All →</a>
</div>

<?php if (empty($batches)): ?>
<div class="empty-premium">
<div class="empty-icon">📦</div>
<h3>No batches yet</h3>
<p>Content Manager से अपना पहला batch बनाओ</p>
<a href="manage.php" class="btn-premium btn-sm" style="margin-top:16px">Create First Batch</a>
</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
<?php foreach (array_slice($batches, 0, 6) as $b):
    $instName = '';
    foreach ($insts as $x) if ($x['id']==($b['institutionId']??'')) $instName = $x['name'];
?>
<a href="manage.php?type=subject&batch=<?= urlencode($b['id']) ?>" class="premium-card" style="text-decoration:none;color:inherit;display:block">
<div style="display:flex;align-items:center;gap:14px;margin-bottom:12px">
<div style="width:60px;height:60px;border-radius:14px;overflow:hidden;flex-shrink:0;background:#fff;border:2px solid <?= htmlspecialchars($b['color']??'#2cee82') ?>;display:flex;align-items:center;justify-content:center;padding:4px">
<?php if (!empty($b['image'])): ?>
<img src="<?= htmlspecialchars(mediaUrl($b['image'], 'batches')) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:10px"/>
<?php else: ?>
<span style="font-weight:900;color:<?= htmlspecialchars($b['color']??'#2cee82') ?>;font-size:22px"><?= strtoupper(substr($b['name'],0,1)) ?></span>
<?php endif; ?>
</div>
<div style="min-width:0;flex:1">
<h3 style="margin:0;font-size:15px;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($b['name']) ?></h3>
<?php if ($instName): ?>
<p style="margin:3px 0 0;font-size:11px;color:#60a5fa;font-weight:600"><?= htmlspecialchars($instName) ?></p>
<?php endif; ?>
</div>
</div>
<div style="display:flex;gap:6px;flex-wrap:wrap">
<span style="padding:3px 9px;background:rgba(59,130,246,.15);border:1px solid rgba(59,130,246,.3);border-radius:8px;font-size:10px;font-weight:700;color:#60a5fa">
<i class="ph-bold ph-book-open"></i> <?= htmlspecialchars($b['subject'] ?: 'No tag') ?>
</span>
</div>
</a>
<?php endforeach; ?>
</div>
<?php endif; ?>

</main>
</div>

</body></html>
