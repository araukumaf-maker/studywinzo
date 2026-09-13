<?php
require_once 'config.php';
require_once __DIR__.'/../data_helper.php';
requireAdmin();
require_once '_upload_ui.php';
require_once '_file_upload.php';
require_once '_premium.php';

$file = 'popup.json';
$popup = getJSON($file, [
    'enabled' => false, 'image' => '', 'title' => '', 'subtitle' => '',
    'button_text' => '', 'button_url' => '', 'show_again_hours' => 24
]);
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $popup['enabled'] = isset($_POST['enabled']);
    $popup['title'] = trim($_POST['title'] ?? '');
    $popup['subtitle'] = trim($_POST['subtitle'] ?? '');
    $popup['button_text'] = trim($_POST['button_text'] ?? '');
    $popup['button_url'] = trim($_POST['button_url'] ?? '');
    $popup['show_again_hours'] = max(0, (int)($_POST['show_again_hours'] ?? 24));
    // Prefer direct browser→Supabase upload URL
    if (!empty($_POST['image_url']) && preg_match('#^https?://#i', $_POST['image_url'])) {
        $popup['image'] = $_POST['image_url'];
    }
    // Fallback: legacy server-side upload
    elseif (!empty($_FILES['image']['tmp_name'])) {
        $up = handleUpload('image', __DIR__.'/uploads/popups', ['jpg','jpeg','png','webp','gif'], 5);
        if ($up['success']) {
            $popup['image'] = $up['url'] ?? $up['filename'];
        } else $err = 'Image: '.$up['error'];
    }
    if (!$err) { saveJSON($file, $popup); $msg = 'Popup settings saved'; }
}
if (isset($_GET['clear_image'])) {
    $popup['image'] = '';
    saveJSON($file, $popup);
    header('Location: popup-admin.php?d=1'); exit;
}
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Popup Manager — StudyWinzo</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<?php renderPremiumCSS(); ?>
<?php require_once '_direct_upload.php'; ?>
</head>
<body>

<?php renderPremiumHeader($pageInfo, $settings, $popup, $currentPage); ?>

<div class="adm-main">
<main class="adm-content">

<?php if($msg): ?><div class="alert-premium alert-success"><i class="ph-bold ph-check-circle" style="font-size:18px"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert-premium alert-error"><i class="ph-bold ph-warning-circle" style="font-size:18px"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>
<?php if(isset($_GET['d'])): ?><div class="alert-premium alert-error"><i class="ph-bold ph-trash" style="font-size:18px"></i> Image removed</div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr;gap:24px">
<?php if (getenv('PREMIUM_GRID')): ?><?php endif; ?>

<!-- Preview -->
<div class="premium-card">
<div class="section-header">
<h2 class="section-title"><i class="ph-bold ph-eye"></i> Live Preview</h2>
<span style="font-size:11px;color:#64748b;background:rgba(59,130,246,.1);padding:4px 10px;border-radius:12px;font-weight:700">
<?= !empty($popup['enabled'])?'● ACTIVE':'● DISABLED' ?>
</span>
</div>
<div style="display:flex;justify-content:center">
<div style="width:100%;max-width:360px;background:#0a0f0a;border:1px solid #1f332a;border-radius:20px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,.6)">
<?php if (!empty($popup['image'])): ?>
<img src="<?= htmlspecialchars(mediaUrl($popup['image'], 'popups')) ?>" style="width:100%;aspect-ratio:2/1;object-fit:cover;display:block"/>
<?php else: ?>
<div style="width:100%;aspect-ratio:2/1;background:linear-gradient(135deg,#1f2937,#0f172a);display:flex;align-items:center;justify-content:center;color:#475569;font-size:14px">
<i class="ph-bold ph-image" style="font-size:36px"></i>
</div>
<?php endif; ?>
<div style="padding:22px 20px;text-align:center">
<h3 style="color:#fff;font-size:22px;font-weight:800;margin:0 0 10px;font-family:Georgia,serif"><?= htmlspecialchars($popup['title'] ?: 'Popup Title') ?></h3>
<p style="color:#cbd5e1;font-size:15px;margin:0 0 20px;font-family:Georgia,serif"><?= htmlspecialchars($popup['subtitle'] ?: 'Subtitle here') ?></p>
<div style="background:#229ED9;color:#fff;font-weight:700;font-size:15px;padding:14px;border-radius:12px">
✈ <?= htmlspecialchars($popup['button_text'] ?: 'Button Text') ?>
</div>
</div>
</div>
</div>
</div>

<!-- Form -->
<div class="premium-card">
<div class="section-header">
<h2 class="section-title"><i class="ph-bold ph-sliders"></i> Settings</h2>
</div>
<form method="POST" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:18px">
<input type="hidden" name="image_url" value=""/>

<label class="premium-card" style="padding:16px;display:flex;align-items:center;gap:14px;cursor:pointer;margin:0">
<input type="checkbox" name="enabled" <?= $popup['enabled']?'checked':'' ?> style="width:22px;height:22px;accent-color:#3b82f6;cursor:pointer"/>
<div>
<div style="font-size:14px;font-weight:700;color:#fff">Enable Popup</div>
<div style="font-size:11px;color:#64748b;margin-top:2px">User panel पर popup दिखेगा</div>
</div>
</label>

<div>
<label class="label-premium">Popup Image (1200×600 recommended, max 5MB)</label>
<?php if (!empty($popup['image'])): ?>
<div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
<img src="<?= htmlspecialchars(mediaUrl($popup['image'], 'popups')) ?>" style="width:100px;height:64px;object-fit:cover;border-radius:10px;border:1px solid rgba(59,130,246,.3)"/>
<a href="?clear_image=1" class="btn-danger" onclick="return confirm('Remove image?')"><i class="ph-bold ph-trash"></i> Remove</a>
</div>
<?php endif; ?>
<?php 
$currentPreview = !empty($popup['image']) ? 'uploads/popups/'.$popup['image'] : '';
renderFileUpload('image', 'Popup Image', 'image/*', 'PNG, JPG, WEBP · 1200×600 recommended · max 5MB', $currentPreview);
?>
</div>

<div>
<label class="label-premium">Title</label>
<input type="text" name="title" value="<?= htmlspecialchars($popup['title']) ?>" class="form-premium" placeholder="Join Telegram Channel!"/>
</div>

<div>
<label class="label-premium">Subtitle</label>
<textarea name="subtitle" rows="2" class="form-premium" placeholder="Don't miss any updates!"><?= htmlspecialchars($popup['subtitle']) ?></textarea>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
<div>
<label class="label-premium">Button Text</label>
<input type="text" name="button_text" value="<?= htmlspecialchars($popup['button_text']) ?>" class="form-premium" placeholder="Join Now"/>
</div>
<div>
<label class="label-premium">Show Again After (hours)</label>
<input type="number" name="show_again_hours" value="<?= (int)$popup['show_again_hours'] ?>" min="0" class="form-premium"/>
</div>
</div>

<div>
<label class="label-premium">Button URL</label>
<input type="text" name="button_url" value="<?= htmlspecialchars($popup['button_url']) ?>" class="form-premium" placeholder="https://t.me/yourchannel"/>
</div>

<button type="submit" class="btn-premium btn-pink" style="align-self:flex-start">
<i class="ph-bold ph-floppy-disk"></i> Save Popup Settings
</button>
</form>
</div>

</div>

</main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Find the popup form
    var forms = document.querySelectorAll('form');
    forms.forEach(function(frm, idx) {
        if (frm.querySelector('input[name="image"]')) {
            frm.id = frm.id || ('popupForm_' + idx);
            // Ensure hidden field exists
            if (!frm.querySelector('input[name="image_url"]')) {
                var h = document.createElement('input');
                h.type = 'hidden';
                h.name = 'image_url';
                frm.appendChild(h);
            }
            window.SWUpload.bindForm(frm.id, 'image', 'popups', 'image_url');
        }
    });
});
</script>
<!-- sw-direct-upload-init -->
</body></html>
