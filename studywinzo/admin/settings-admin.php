<?php
require_once 'config.php';
requireAdmin();
require_once '_upload_ui.php';

$settings = getJSON('settings.json', ['logo'=>'', 'institution_name'=>'StudyWinzo']);
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $settings['institution_name'] = trim($_POST['institution_name'] ?? 'StudyWinzo');
    if (!empty($_FILES['logo']['tmp_name'])) {
        $up = handleUpload('logo', UPLOAD_LOGOS, ['jpg','jpeg','png','webp','svg'], 5);
        if ($up['success']) {
            if (!empty($settings['logo']) && file_exists(UPLOAD_LOGOS.'/'.$settings['logo'])) @unlink(UPLOAD_LOGOS.'/'.$settings['logo']);
            $settings['logo'] = $up['filename'];
        } else $err = 'Logo: '.$up['error'];
    }
    if (!$err) { saveJSON('settings.json', $settings); $msg = 'Settings saved'; }
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Settings - Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<style>
body{background:#070b14;color:#e2e8f0;font-family:'Inter',sans-serif;margin:0;min-height:100vh}
.card{background:#0b1220;border:1px solid #1a2332;border-radius:20px;padding:24px}
.form-input{width:100%;background:#070b14;border:1px solid #1a2332;color:#fff;border-radius:12px;padding:12px 16px;font-size:14px;font-family:inherit}
.form-input:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.15)}
.btn{background:#2563eb;color:#fff;padding:12px 24px;border-radius:12px;font-weight:600;font-size:14px;border:none;cursor:pointer}
.btn:hover{background:#1d4ed8}
</style>
</head><body>

<header style="background:rgba(7,11,20,.9);backdrop-filter:blur(12px);border-bottom:1px solid #1a2332;padding:16px 20px;position:sticky;top:0;z-index:10">
<div style="max-width:800px;margin:0 auto;display:flex;align-items:center;gap:14px">
<a href="index.php" style="color:#94a3b8;text-decoration:none;font-size:22px"><i class="ph-bold ph-arrow-left"></i></a>
<div>
<h1 style="margin:0;font-size:18px;font-weight:800;color:#fff">Institution Settings</h1>
<p style="margin:2px 0 0;font-size:11px;color:#64748b">Logo और basic info</p>
</div>
</div>
</header>

<main style="max-width:800px;margin:0 auto;padding:24px 20px">

<?php if($msg): ?><div style="padding:14px 18px;border-radius:12px;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:#4ade80;font-size:13px;margin-bottom:20px">✓ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if($err): ?><div style="padding:14px 18px;border-radius:12px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#f87171;font-size:13px;margin-bottom:20px">⚠ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="card">
<form method="POST" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:20px">

<div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
<div style="width:100px;height:100px;border-radius:20px;background:#070b14;border:2px solid #1a2332;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
<?php if (!empty($settings['logo'])): ?>
<img src="uploads/logos/<?= htmlspecialchars($settings['logo']) ?>" style="width:100%;height:100%;object-fit:contain;padding:8px"/>
<?php else: ?>
<i class="ph-bold ph-image" style="font-size:36px;color:#475569"></i>
<?php endif; ?>
</div>
<div style="flex:1;min-width:200px">
<label style="display:block;font-size:12px;font-weight:600;color:#94a3b8;margin-bottom:8px">Institution Logo (PNG/JPG/SVG, max 5MB)</label>
<?php mUpload("logo", "Institution Logo", "image/*", "PNG, JPG, SVG · Transparent background works best · max 3MB", !empty($settings["logo"]) ? "uploads/logos/".htmlspecialchars($settings["logo"]) : ""); ?>
</div>
</div>

<div>
<label style="display:block;font-size:12px;font-weight:600;color:#94a3b8;margin-bottom:8px">Institution Name</label>
<input type="text" name="institution_name" value="<?= htmlspecialchars($settings['institution_name']) ?>" class="form-input" required/>
</div>

<button type="submit" class="btn" style="align-self:flex-start">💾 Save Settings</button>
</form>
</div>

</main>
</body></html>
