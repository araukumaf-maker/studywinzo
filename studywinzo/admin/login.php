<?php
require_once 'config.php';
if (isAdmin()) { header('Location: index.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $u = trim($_POST['user'] ?? '');
    $p = trim($_POST['pass'] ?? '');
    if ($u === ADMIN_USER && $p === ADMIN_PASS) {
        $_SESSION['sw_admin'] = true;
        session_write_close();
        header('Location: index.php'); exit;
    }
    $err = 'Invalid credentials — try admin / admin123';
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Admin Login</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>body{font-family:system-ui,sans-serif;background:#0a0f1a;min-height:100vh}</style>
</head><body class="flex items-center justify-center p-4">
<div class="w-full max-w-sm bg-[#111827] border border-slate-700 rounded-2xl p-7 shadow-2xl">
<h1 class="text-xl font-bold text-white text-center mb-1">Admin Panel</h1>
<p class="text-slate-400 text-xs text-center mb-5">StudyWinzo</p>
<?php if($err): ?><div class="mb-4 px-3 py-2 rounded-lg bg-red-500/10 border border-red-500/30 text-red-300 text-xs text-center"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<form method="POST" class="space-y-4">
<input type="text" name="user" value="admin" placeholder="Username" class="w-full bg-[#0a0f1a] border border-slate-700 text-white rounded-lg px-3 py-2.5 text-sm" required/>
<input type="password" name="pass" placeholder="admin123" class="w-full bg-[#0a0f1a] border border-slate-700 text-white rounded-lg px-3 py-2.5 text-sm" required/>
<button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg text-sm">Login</button>
</form>
<p class="text-center text-slate-500 text-[11px] mt-4">admin / admin123</p>
</div></body></html>
