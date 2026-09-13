<?php
require_once 'config.php';
requireAdmin();

$type = $_GET['type'] ?? 'institutions';
$msg = ''; $err = '';

$institutions = getJSON('institutions.json', []);
$batches = getJSON('batches.json', []);
$subjects = getJSON('subjects.json', []);
$chapters = getJSON('chapters.json', []);
$content = getJSON('content.json', []);

// ============ DELETE ACTIONS ============
if (isset($_GET['del'])) {
    $id = $_GET['del'];
    $delType = $_GET['deltype'] ?? '';
    if ($delType === 'inst') {
        foreach ($institutions as $i) if ($i['id']===$id && !empty($i['logo'])) @unlink(__DIR__.'/uploads/institutions/'.$i['logo']);
        $institutions = array_values(array_filter($institutions, fn($x)=>$x['id']!==$id));
        saveJSON('institutions.json', $institutions);
    } elseif ($delType === 'batch') {
        foreach ($batches as $b) if ($b['id']===$id && !empty($b['image'])) @unlink(UPLOAD_BATCHES.'/'.$b['image']);
        $batches = array_values(array_filter($batches, fn($x)=>$x['id']!==$id));
        $subsRemoved = array_filter($subjects, fn($s)=>$s['batchId']===$id);
        $subsIds = array_column($subsRemoved, 'id');
        $subjects = array_values(array_filter($subjects, fn($s)=>$s['batchId']!==$id));
        $chapters = array_values(array_filter($chapters, fn($c)=>!in_array($c['subjectId'], $subsIds)));
        saveJSON('batches.json', $batches);
        saveJSON('subjects.json', $subjects);
        saveJSON('chapters.json', $chapters);
    } elseif ($delType === 'subject') {
        $subjects = array_values(array_filter($subjects, fn($x)=>$x['id']!==$id));
        $chapters = array_values(array_filter($chapters, fn($c)=>$c['subjectId']!==$id));
        saveJSON('subjects.json', $subjects);
        saveJSON('chapters.json', $chapters);
    } elseif ($delType === 'chapter') {
        $chapters = array_values(array_filter($chapters, fn($x)=>$x['id']!==$id));
        $content = array_values(array_filter($content, fn($c)=>$c['chapterId']!==$id));
        saveJSON('chapters.json', $chapters);
        saveJSON('content.json', $content);
    } elseif ($delType === 'content') {
        foreach ($content as $c) if ($c['id']===$id) {
            if (!empty($c['file'])) {
                foreach ([UPLOAD_NOTES, UPLOAD_DPP] as $d) if (file_exists($d.'/'.$c['file'])) @unlink($d.'/'.$c['file']);
            }
            break;
        }
        $content = array_values(array_filter($content, fn($x)=>$x['id']!==$id));
        saveJSON('content.json', $content);
    }
    header('Location: view-all.php?type='.$type.'&deleted=1'); exit;
}

// ============ EDIT ACTIONS ============
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='rename') {
    $id = trim($_POST['id'] ?? '');
    $newName = trim($_POST['name'] ?? '');
    $editType = $_POST['edit_type'] ?? '';
    if ($newName) {
        if ($editType === 'institution') {
            foreach ($institutions as $i=>$x) if ($x['id']===$id) { $institutions[$i]['name']=$newName; break; }
            saveJSON('institutions.json', $institutions);
        } elseif ($editType === 'batch') {
            foreach ($batches as $i=>$x) if ($x['id']===$id) { $batches[$i]['name']=$newName; break; }
            saveJSON('batches.json', $batches);
        } elseif ($editType === 'subject') {
            foreach ($subjects as $i=>$x) if ($x['id']===$id) { $subjects[$i]['name']=$newName; break; }
            saveJSON('subjects.json', $subjects);
        } elseif ($editType === 'chapter') {
            foreach ($chapters as $i=>$x) if ($x['id']===$id) { $chapters[$i]['name']=$newName; break; }
            saveJSON('chapters.json', $chapters);
        } elseif ($editType === 'content') {
            foreach ($content as $i=>$x) if ($x['id']===$id) { $content[$i]['title']=$newName; break; }
            saveJSON('content.json', $content);
        }
        header('Location: view-all.php?type='.$type.'&saved=1'); exit;
    }
}

// ============ SEARCH ============
$search = trim($_GET['q'] ?? '');

function matchesSearch($item, $fields, $search) {
    if (!$search) return true;
    $s = strtolower($search);
    foreach ($fields as $f) {
        if (strpos(strtolower($item[$f] ?? ''), $s) !== false) return true;
    }
    return false;
}

// Current type config
$types = [
    'institutions' => ['Instutions', 'ph-buildings', '#a855f7', 'inst'],
    'batches' => ['Batches', 'ph-stack', '#3b82f6', 'batch'],
    'subjects' => ['Subjects', 'ph-book-open', '#10b981', 'subject'],
    'chapters' => ['Chapters', 'ph-list-numbers', '#fb923c', 'chapter'],
    'content' => ['Content', 'ph-files', '#ec4899', 'content'],
];
if (!isset($types[$type])) $type = 'institutions';
$tc = $types[$type];

// Get items for current type
$items = [];
if ($type === 'institutions') {
    $items = array_values(array_filter($institutions, fn($x)=>matchesSearch($x, ['name'], $search)));
} elseif ($type === 'batches') {
    $items = array_values(array_filter($batches, fn($x)=>matchesSearch($x, ['name','subject'], $search)));
    // Add institution name
    $instMap = [];
    foreach ($institutions as $i) $instMap[$i['id']] = $i['name'];
    foreach ($items as &$it) $it['_instName'] = $instMap[$it['institutionId'] ?? ''] ?? 'No inst';
    unset($it);
} elseif ($type === 'subjects') {
    $items = array_values(array_filter($subjects, fn($x)=>matchesSearch($x, ['name'], $search)));
    $batchMap = [];
    foreach ($batches as $b) $batchMap[$b['id']] = $b['name'];
    foreach ($items as &$it) $it['_batchName'] = $batchMap[$it['batchId'] ?? ''] ?? 'No batch';
    unset($it);
} elseif ($type === 'chapters') {
    $items = array_values(array_filter($chapters, fn($x)=>matchesSearch($x, ['name'], $search)));
    // Map to subject + batch
    $subMap = [];
    foreach ($subjects as $s) $subMap[$s['id']] = $s['name'];
    $batchMap = [];
    foreach ($batches as $b) $batchMap[$b['id']] = $b['name'];
    foreach ($items as &$it) {
        $it['_subjectName'] = $subMap[$it['subjectId'] ?? ''] ?? '?';
        $subId = $it['subjectId'] ?? '';
        $bId = '';
        foreach ($subjects as $s) if ($s['id']===$subId) { $bId = $s['batchId']; break; }
        $it['_batchName'] = $batchMap[$bId] ?? '?';
    }
    unset($it);
} elseif ($type === 'content') {
    $items = array_values(array_filter($content, fn($x)=>matchesSearch($x, ['title','description'], $search)));
    $chMap = [];
    foreach ($chapters as $c) $chMap[$c['id']] = $c['name'];
    foreach ($items as &$it) $it['_chapterName'] = $chMap[$it['chapterId'] ?? ''] ?? '?';
    unset($it);
}

// Counts for tabs
$counts = [
    'institutions' => count($institutions),
    'batches' => count($batches),
    'subjects' => count($subjects),
    'chapters' => count($chapters),
    'content' => count($content),
];
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0"/>
<title>Manage <?= htmlspecialchars($tc[0]) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<style>
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent;font-family:'Inter',sans-serif}
body{background:#0a0f1a;color:#e2e8f0;margin:0;padding-bottom:60px;min-height:100vh}
.hdr{background:rgba(10,15,26,.98);backdrop-filter:blur(12px);border-bottom:1px solid #1f2937;padding:14px 16px;display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:50}
.hdr h1{margin:0;font-size:16px;font-weight:800;color:#fff;flex:1;display:flex;align-items:center;gap:8px}
.hdr .back{width:38px;height:38px;background:#1e293b;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#cbd5e1;text-decoration:none;flex-shrink:0}
.hdr .add-btn{background:linear-gradient(135deg,#10b981,#059669);color:#fff;padding:9px 14px;border-radius:10px;font-weight:700;font-size:12px;text-decoration:none;display:flex;align-items:center;gap:5px;flex-shrink:0}

.wrap{max-width:900px;margin:0 auto;padding:16px}

/* Tabs */
.tabs{display:flex;gap:6px;background:#111827;border:1px solid #1f2937;padding:5px;border-radius:12px;margin-bottom:14px;overflow-x:auto;scrollbar-width:none}
.tabs::-webkit-scrollbar{display:none}
.tab{flex:1;min-width:100px;padding:10px 12px;border-radius:9px;text-align:center;font-size:12px;font-weight:700;color:#94a3b8;cursor:pointer;text-decoration:none;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:6px;white-space:nowrap}
.tab.active{background:linear-gradient(135deg,#2563eb,#3b82f6);color:#fff;box-shadow:0 4px 12px rgba(37,99,235,.35)}
.tab i{font-size:15px}
.tab .cnt{background:rgba(255,255,255,.15);padding:1px 6px;border-radius:8px;font-size:10px;font-weight:800}

/* Search */
.search-wrap{margin-bottom:14px;position:relative}
.search-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#64748b;font-size:16px;pointer-events:none}
.search-inp{width:100%;background:#111827;border:1.5px solid #1f2937;color:#fff;border-radius:12px;padding:12px 14px 12px 42px;font-size:13.5px;font-family:inherit;outline:none;transition:all .2s}
.search-inp:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(37,99,235,.15)}
.search-inp::placeholder{color:#64748b}
.search-clear{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.08);border:none;color:#94a3b8;width:24px;height:24px;border-radius:50%;cursor:pointer;display:none;align-items:center;justify-content:center;font-size:14px}
.search-clear.show{display:flex}

/* Items */
.item{background:#111827;border:1px solid #1f2937;border-radius:14px;padding:14px;margin-bottom:10px;display:flex;align-items:center;gap:14px;transition:all .15s}
.item:hover{border-color:rgba(59,130,246,.4)}
.item-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;overflow:hidden;background:rgba(59,130,246,.15);color:#60a5fa;padding:3px}
.item-icon img{width:100%;height:100%;object-fit:cover;border-radius:9px}
.item-info{flex:1;min-width:0}
.item-title{font-size:14px;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.item-sub{font-size:11px;color:#64748b;margin-top:3px;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.item-sub .badge{background:rgba(59,130,246,.15);border:1px solid rgba(59,130,246,.25);color:#60a5fa;padding:2px 7px;border-radius:6px;font-weight:700;font-size:10px}
.item-actions{display:flex;gap:5px;flex-shrink:0}
.icon-btn{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;cursor:pointer;border:1px solid;background:transparent;transition:all .15s;padding:0;text-decoration:none}
.icon-btn i{font-size:16px}
.icon-btn.edit{color:#60a5fa;border-color:rgba(59,130,246,.3);background:rgba(59,130,246,.08)}
.icon-btn.del{color:#f87171;border-color:rgba(239,68,68,.3);background:rgba(239,68,68,.08)}
.icon-btn.view{color:#34d399;border-color:rgba(16,185,129,.3);background:rgba(16,185,129,.08)}

/* Empty */
.empty{text-align:center;padding:60px 20px;color:#64748b}
.empty i{font-size:56px;opacity:.25;display:block;margin-bottom:14px}

/* Alert */
.msg{padding:12px 16px;border-radius:12px;font-size:13px;font-weight:600;margin-bottom:14px;display:flex;align-items:center;gap:10px}
.msg-ok{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);color:#34d399}
.msg-err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#f87171}

/* Modal */
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.85);backdrop-filter:blur(6px);z-index:100;display:none;align-items:center;justify-content:center;padding:16px}
.modal-bg.show{display:flex}
.modal{background:#0b1220;border:1px solid rgba(59,130,246,.3);border-radius:20px;padding:24px;max-width:420px;width:100%}
.modal h2{font-size:16px;font-weight:800;color:#fff;margin:0 0 16px;display:flex;align-items:center;gap:10px}
.modal input{width:100%;background:#070b14;border:1.5px solid #374151;color:#fff;border-radius:10px;padding:12px 14px;font-size:14px;font-family:inherit;outline:none;margin-bottom:14px}
.modal input:focus{border-color:#3b82f6}
.modal-btns{display:flex;gap:8px}
.modal-btns button{flex:1;padding:12px;border-radius:10px;border:none;font-weight:700;font-size:13.5px;cursor:pointer;font-family:inherit}
.btn-save{background:linear-gradient(135deg,#10b981,#059669);color:#fff}
.btn-cancel{background:#1e293b;color:#cbd5e1}
</style>
  <link rel="stylesheet" href="../assets/premium.css">
</head>
<body>

<div class="hdr">
<a href="index.php" class="back"><i class="ph-bold ph-arrow-left"></i></a>
<h1><i class="ph-bold <?= $tc[1] ?>" style="color:<?= $tc[2] ?>"></i> <?= htmlspecialchars($tc[0]) ?></h1>
<a href="manage.php" class="add-btn"><i class="ph-bold ph-plus"></i> Add</a>
</div>

<div class="wrap">

<?php if(isset($_GET['deleted'])): ?><div class="msg msg-ok"><i class="ph-bold ph-trash"></i> Deleted successfully</div><?php endif; ?>
<?php if(isset($_GET['saved'])): ?><div class="msg msg-ok"><i class="ph-bold ph-check-circle"></i> Saved successfully</div><?php endif; ?>

<!-- TABS -->
<div class="tabs">
<?php foreach ($types as $k => $v): ?>
<a href="?type=<?= $k ?>" class="tab <?= $type===$k?'active':'' ?>">
<i class="ph-bold <?= $v[1] ?>"></i> <?= $v[0] ?>
<span class="cnt"><?= $counts[$k] ?></span>
</a>
<?php endforeach; ?>
</div>

<!-- SEARCH -->
<div class="search-wrap">
<i class="ph-bold ph-magnifying-glass"></i>
<input type="text" class="search-inp" id="searchInp" placeholder="Search <?= strtolower($tc[0]) ?>..." value="<?= htmlspecialchars($search) ?>" oninput="doSearch(this.value)"/>
<button class="search-clear <?= $search?'show':'' ?>" id="searchClear" onclick="clearSearch()"><i class="ph-bold ph-x"></i></button>
</div>

<!-- LIST -->
<?php if (empty($items)): ?>
<div class="empty">
<i class="ph-bold <?= $tc[1] ?>"></i>
<h3 style="font-size:15px;font-weight:700;color:#94a3b8;margin-bottom:6px"><?= $search ? 'No results found' : 'No '.strtolower($tc[0]).' yet' ?></h3>
<p style="font-size:13px"><?= $search ? 'Try different keywords' : 'Add using the + button' ?></p>
</div>
<?php else: foreach ($items as $it):

    // Get display info based on type
    $title = $sub = $iconHtml = '';
    if ($type === 'institutions') {
        $title = $it['name'];
        $sub = '<span class="badge">' . substr($it['id'], -6) . '</span>';
        $iconHtml = !empty($it['logo']) ? '<img src="'.htmlspecialchars(mediaUrl($it['logo'], 'institutions')).'"/>' : '<i class="ph-bold ph-buildings"></i>';
    } elseif ($type === 'batches') {
        $title = $it['name'];
        $sub = '<span class="badge">' . htmlspecialchars($it['_instName']) . '</span>';
        $iconHtml = !empty($it['image']) ? '<img src="'.htmlspecialchars(mediaUrl($it['image'], 'batches')).'"/>' : '<i class="ph-bold ph-stack"></i>';
    } elseif ($type === 'subjects') {
        $title = $it['name'];
        $sub = '<span class="badge">' . htmlspecialchars($it['_batchName']) . '</span>';
        $iconHtml = '<i class="ph-bold ph-book-open"></i>';
    } elseif ($type === 'chapters') {
        $title = $it['name'];
        $sub = '<span class="badge">' . htmlspecialchars($it['_batchName']) . '</span><span class="badge">' . htmlspecialchars($it['_subjectName']) . '</span>';
        $iconHtml = '<i class="ph-bold ph-list-numbers"></i>';
    } elseif ($type === 'content') {
        $title = $it['title'];
        $typeLabels = ['note'=>'📄 Note','dpp'=>'📝 DPP','video'=>'🎬 Video','link'=>'🔗 Link'];
        $sub = '<span class="badge">' . ($typeLabels[$it['type']] ?? 'File') . '</span><span class="badge">' . htmlspecialchars($it['_chapterName']) . '</span>';
        $iconHtml = '<i class="ph-bold ph-file"></i>';
    }
?>
<div class="item">
<div class="item-icon"><?= $iconHtml ?></div>
<div class="item-info">
<div class="item-title"><?= htmlspecialchars($title) ?></div>
<div class="item-sub"><?= $sub ?></div>
</div>
<div class="item-actions">
<?php if ($type === 'batches'): ?>
<a href="../batch.php?id=<?= urlencode($it['id']) ?>" target="_blank" class="icon-btn view" title="View"><i class="ph-bold ph-eye"></i></a>
<a href="manage.php?batch=<?= urlencode($it['id']) ?>&view=browse" class="icon-btn edit" title="Manage"><i class="ph-bold ph-pencil-simple"></i></a>
<?php else: ?>
<button class="icon-btn edit" onclick='editItem(<?= json_encode($it['id']) ?>, <?= json_encode($title) ?>, "<?= $tc[3] ?>")' title="Rename"><i class="ph-bold ph-pencil-simple"></i></button>
<?php endif; ?>
<a href="?type=<?= urlencode($type) ?>&del=<?= urlencode($it['id']) ?>&deltype=<?= $tc[3] ?>" class="icon-btn del" onclick="return confirm('Delete this item? This cannot be undone.')" title="Delete"><i class="ph-bold ph-trash"></i></a>
</div>
</div>
<?php endforeach; endif; ?>

</div>

<!-- EDIT MODAL -->
<div class="modal-bg" id="editModal" onclick="if(event.target===this)closeEdit()">
<div class="modal">
<h2><i class="ph-bold ph-pencil-simple" style="color:#60a5fa"></i> Rename</h2>
<form method="POST" id="editForm">
<input type="hidden" name="action" value="rename"/>
<input type="hidden" name="id" id="editId"/>
<input type="hidden" name="edit_type" id="editType"/>
<input type="text" name="name" id="editName" placeholder="New name" required/>
<div class="modal-btns">
<button type="submit" class="btn-save">Save</button>
<button type="button" class="btn-cancel" onclick="closeEdit()">Cancel</button>
</div>
</form>
</div>
</div>

<script>
var searchTimer;
function doSearch(val){
  clearTimeout(searchTimer);
  var clearBtn = document.getElementById('searchClear');
  if (val) clearBtn.classList.add('show'); else clearBtn.classList.remove('show');
  searchTimer = setTimeout(function(){
    var url = new URL(location.href);
    url.searchParams.set('type', '<?= $type ?>');
    if (val) url.searchParams.set('q', val); else url.searchParams.delete('q');
    location.href = url.toString();
  }, 400);
}
function clearSearch(){
  var url = new URL(location.href);
  url.searchParams.set('type', '<?= $type ?>');
  url.searchParams.delete('q');
  location.href = url.toString();
}
function editItem(id, name, type){
  document.getElementById('editId').value = id;
  document.getElementById('editType').value = type;
  document.getElementById('editName').value = name;
  document.getElementById('editModal').classList.add('show');
  setTimeout(function(){ document.getElementById('editName').focus(); }, 100);
}
function closeEdit(){ document.getElementById('editModal').classList.remove('show'); }
</script>

</body></html>
