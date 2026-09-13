<?php
require_once 'config.php';
requireAdmin();
require_once '_upload_ui.php';
require_once '_file_upload.php';

$file = 'institutions.json';
$insts = getJSON($file, []);
$msg = ''; $err = '';

// Save
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='save') {
    $id = trim($_POST['id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $color = trim($_POST['color'] ?? '#2cee82');
    $order = (int)($_POST['order'] ?? 0);
    if (!$name) $err = 'Name required';
    else {
        $logo = $_POST['existing_logo'] ?? '';
        if (!empty($_FILES['logo']['tmp_name'])) {
            $up = handleUpload('logo', __DIR__.'/uploads/institutions', ['jpg','jpeg','png','webp','svg'], 3);
            if ($up['success']) $logo = $up['filename'];
            else $err = 'Logo: '.$up['error'];
        }
        if (!$err) {
            if ($id) {
                foreach ($insts as $i=>$x) if ($x['id']===$id) {
                    $insts[$i]['name']=$name; $insts[$i]['color']=$color; $insts[$i]['order']=$order;
                    if ($logo) $insts[$i]['logo']=$logo;
                    break;
                }
                $msg = 'Institution updated';
            } else {
                $insts[] = ['id'=>uid('inst'),'name'=>$name,'logo'=>$logo,'color'=>$color,'order'=>$order,'created'=>date('Y-m-d H:i:s')];
                $msg = 'Institution added';
            }
            saveJSON($file, $insts);
        }
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    foreach ($insts as $x) if ($x['id']===$id && !empty($x['logo']) && file_exists(__DIR__.'/uploads/institutions/'.$x['logo'])) @unlink(__DIR__.'/uploads/institutions/'.$x['logo']);
    $insts = array_values(array_filter($insts, fn($x)=>$x['id']!==$id));
    saveJSON($file, $insts);
    header('Location: institutions.php?d=1'); exit;
}

usort($insts, fn($a,$b)=>($a['order']??0)-($b['order']??0));

$batches = getJSON('batches.json', []);
$edit = null;
if (isset($_GET['edit'])) foreach ($insts as $x) if ($x['id']===$_GET['edit']) $edit = $x;
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Institutions - Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<style>
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent;font-family:'Inter',sans-serif}
body{background:#070b14;color:#e2e8f0;margin:0;min-height:100vh}
.card-premium{background:linear-gradient(145deg,#0b1220,#0f172a);border:1px solid #1a2332;border-radius:20px;padding:24px;transition:all .25s}
.card-premium:hover{border-color:rgba(59,130,246,.5);box-shadow:0 20px 40px -12px rgba(37,99,235,.25);transform:translateY(-2px)}
.form-input{width:100%;background:#070b14;border:1px solid #1a2332;color:#fff;border-radius:12px;padding:12px 16px;font-size:14px;font-family:inherit;transition:all .2s}
.form-input:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.15);background:#0a1220}
.btn{background:linear-gradient(135deg,#2563eb,#3b82f6);color:#fff;padding:11px 22px;border-radius:12px;font-weight:600;font-size:13.5px;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:8px;text-decoration:none;transition:all .15s;box-shadow:0 4px 12px rgba(37,99,235,.3)}
.btn:hover{transform:translateY(-1px)}
.btn-sm{padding:7px 14px;font-size:12px;box-shadow:none}
.btn-green{background:linear-gradient(135deg,#10b981,#059669)}
.btn-gray{background:#1e293b;color:#cbd5e1;box-shadow:none}
.btn-danger{background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.3);padding:7px 14px;border-radius:10px;font-size:12px;text-decoration:none;display:inline-flex;align-items:center;gap:5px}
</style>
</head><body>

<header style="position:sticky;top:0;z-index:50;background:rgba(7,11,20,.9);backdrop-filter:blur(12px);border-bottom:1px solid #1a2332;padding:14px 20px">
<div style="max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between">
<div style="display:flex;align-items:center;gap:14px">
<a href="index.php" style="color:#94a3b8;text-decoration:none;font-size:22px"><i class="ph-bold ph-arrow-left"></i></a>
<div>
<h1 style="margin:0;font-size:18px;font-weight:800;color:#fff;display:flex;align-items:center;gap:8px"><i class="ph-bold ph-buildings" style="color:#60a5fa"></i> Institutions</h1>
<p style="margin:2px 0 0;font-size:11px;color:#64748b">Institutions manage करो (PW, Next Toppers...)</p>
</div>
</div>
<a href="logout.php" class="btn-danger"><i class="ph-bold ph-sign-out"></i> Logout</a>
</div>
</header>

<main style="max-width:1200px;margin:0 auto;padding:24px 20px">

<?php if($msg): ?><div style="padding:14px 18px;border-radius:14px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);color:#34d399;font-size:13px;margin-bottom:20px">✓ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if($err): ?><div style="padding:14px 18px;border-radius:14px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#f87171;font-size:13px;margin-bottom:20px">⚠ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- Add Form -->
<div class="card-premium" style="margin-bottom:24px">
<h2 style="margin:0 0 20px;font-size:16px;font-weight:800;color:#fff;display:flex;align-items:center;gap:10px">
<i class="ph-bold <?= $edit?'ph-pencil-simple':'ph-plus-circle' ?>" style="color:#60a5fa;font-size:22px"></i>
<?= $edit?'Edit Institution':'Add Institution' ?>
</h2>
<form method="POST" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:16px">
<input type="hidden" name="action" value="save">
<input type="hidden" name="id" value="<?= htmlspecialchars($edit['id'] ?? '') ?>">
<input type="hidden" name="existing_logo" value="<?= htmlspecialchars($edit['logo'] ?? '') ?>">

<div style="display:grid;grid-template-columns:2fr 1fr;gap:14px">
<div>
<label style="display:block;font-size:11px;font-weight:700;color:#94a3b8;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Institution Name *</label>
<input type="text" name="name" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" placeholder="Physics Wallah" class="form-input" required/>
</div>
<div>
<label style="display:block;font-size:11px;font-weight:700;color:#94a3b8;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Order</label>
<input type="number" name="order" value="<?= (int)($edit['order'] ?? 0) ?>" class="form-input"/>
</div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;align-items:end">
<div>
<label style="display:block;font-size:11px;font-weight:700;color:#94a3b8;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Logo / Icon (PNG/JPG/SVG, max 3MB)</label>
<?php 
$preview = !empty($edit['logo']) ? 'uploads/institutions/'.$edit['logo'] : '';
renderFileUpload('logo', 'Logo / Icon', 'image/*', 'PNG, JPG, SVG · Transparent BG recommended · max 3MB', $preview);
?>
<?php if (!empty($edit['logo'])): ?>
<img src="uploads/institutions/<?= htmlspecialchars($edit['logo']) ?>" style="width:80px;height:80px;object-fit:contain;border-radius:12px;margin-top:10px;border:2px solid #1a2332;background:#fff;padding:6px"/>
<?php endif; ?>
</div>
<div>
<label style="display:block;font-size:11px;font-weight:700;color:#94a3b8;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Accent Color</label>
<div style="display:flex;align-items:center;gap:12px">
<input type="color" name="color" value="<?= htmlspecialchars($edit['color'] ?? '#2cee82') ?>" style="width:56px;height:44px;border-radius:12px;border:2px solid #1a2332;background:transparent;cursor:pointer"/>
</div>
</div>
</div>

<div style="display:flex;gap:10px">
<button type="submit" class="btn btn-green"><i class="ph-bold <?= $edit?'ph-check':'ph-plus' ?>"></i> <?= $edit?'Update':'Add Institution' ?></button>
<?php if($edit): ?><a href="institutions.php" class="btn btn-gray">Cancel</a><?php endif; ?>
</div>
</form>
</div>

<!-- List -->
<h2 style="font-size:16px;font-weight:800;color:#fff;margin:0 0 16px;display:flex;align-items:center;gap:10px">
<i class="ph-bold ph-buildings" style="color:#60a5fa"></i> All Institutions
<span style="margin-left:auto;font-size:12px;color:#64748b;font-weight:500"><?= count($insts) ?> institutions</span>
</h2>

<?php if (empty($insts)): ?>
<div class="card-premium" style="padding:60px 20px;text-align:center">
<div style="font-size:56px;opacity:.3;margin-bottom:14px">🏛️</div>
<h3 style="margin:0;color:#94a3b8;font-size:16px;font-weight:700">No institutions yet</h3>
</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
<?php foreach ($insts as $x):
    $bCount = count(array_filter($batches, fn($b)=>($b['institutionId']??'')===$x['id']));
?>
<div class="card-premium">
<div style="display:flex;align-items:center;gap:14px;margin-bottom:14px">
<div style="width:64px;height:64px;border-radius:14px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;border:2px solid <?= htmlspecialchars($x['color']) ?>40;padding:6px">
<?php if (!empty($x['logo'])): ?>
<img src="uploads/institutions/<?= htmlspecialchars($x['logo']) ?>" style="width:100%;height:100%;object-fit:contain"/>
<?php else: ?>
<i class="ph-bold ph-buildings" style="font-size:28px;color:<?= htmlspecialchars($x['color']) ?>"></i>
<?php endif; ?>
</div>
<div style="min-width:0;flex:1">
<h3 style="margin:0;font-size:15px;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($x['name']) ?></h3>
<p style="margin:4px 0 0;font-size:11.5px;color:#64748b"><?= $bCount ?> batches</p>
</div>
</div>
<div style="display:flex;gap:8px;flex-wrap:wrap">
<a href="manage.php?type=batch&institution=<?= urlencode($x['id']) ?>" class="btn btn-sm"><i class="ph-bold ph-arrow-right"></i> Batches</a>
<a href="?edit=<?= urlencode($x['id']) ?>" class="btn btn-sm btn-gray"><i class="ph-bold ph-pencil-simple"></i></a>
<a href="?delete=<?= urlencode($x['id']) ?>" onclick="return confirm('Delete this institution?')" class="btn-danger"><i class="ph-bold ph-trash"></i></a>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

</main>
</body></html>
