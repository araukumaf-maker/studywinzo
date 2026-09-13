<?php
require_once 'config.php';
requireAdmin();

$batches = getJSON('batches.json', []);
$institutions = getJSON('institutions.json', []);

// AJAX save
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='save_content') {
    header('Content-Type: application/json');
    $chId = trim($_POST['chapter_id'] ?? '');
    $ctype = $_POST['ctype'] ?? 'note';
    $title = trim($_POST['title'] ?? '');
    $videoUrl = trim($_POST['video_url'] ?? '');
    $thumbFile = '';

    if (!$chId) { echo json_encode(['success'=>false,'error'=>'Chapter required']); exit; }
    if (!$title) $title = ucfirst($ctype);

    // Handle video thumbnail upload
    if (!empty($_FILES['thumb']['tmp_name'])) {
        $up = handleUpload('thumb', UPLOAD_THUMBS, ['jpg','jpeg','png','webp'], 3);
        if ($up['success']) $thumbFile = $up['filename'];
    }

    $content = getJSON('content.json', []);
    $item = [
        'id' => uid('ct'), 'chapterId'=>$chId, 'type'=>$ctype, 'title'=>$title,
        'description'=>'', 'file'=>'', 'video_url'=>$videoUrl, 'external_url'=>'',
        'thumbnail' => $thumbFile,
        'created'=>date('Y-m-d H:i:s'), 'updated'=>date('Y-m-d H:i:s')
    ];

    // Handle file upload for note/dpp
    if (in_array($ctype, ['note','dpp']) && !empty($_FILES['file']['tmp_name'])) {
        $dest = $ctype==='dpp' ? UPLOAD_DPP : UPLOAD_NOTES;
        $up = handleUpload('file', $dest, ['pdf','doc','docx','zip','jpg','jpeg','png','ppt','pptx'], 50);
        if ($up['success']) $item['file'] = $up['filename'];
    }

    $content[] = $item;
    saveJSON('content.json', $content);
    echo json_encode(['success'=>true, 'item'=>$item]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0"/>
<title>Content Manager</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<style>
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent;font-family:'Inter',sans-serif}
body{background:#070b14;color:#e2e8f0;margin:0;padding-bottom:80px;min-height:100vh;overflow-x:hidden}

/* ambient background */
.orb{position:fixed;border-radius:50%;filter:blur(100px);opacity:.12;pointer-events:none;z-index:0}
.orb-1{top:-150px;left:-150px;width:500px;height:500px;background:#3b82f6}
.orb-2{bottom:-150px;right:-150px;width:500px;height:500px;background:#a855f7}
.orb-3{top:40%;left:50%;width:400px;height:400px;background:#10b981;opacity:.06}

/* Header */
.hdr{background:rgba(7,11,20,.85);backdrop-filter:blur(20px);border-bottom:1px solid rgba(59,130,246,.15);padding:16px 20px;position:sticky;top:0;z-index:30;display:flex;align-items:center;gap:14px}
.hdr h1{margin:0;font-size:17px;font-weight:900;color:#fff;flex:1;display:flex;align-items:center;gap:10px;letter-spacing:-.3px}
.hdr .back{width:40px;height:40px;background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.25);border-radius:11px;display:flex;align-items:center;justify-content:center;color:#60a5fa;text-decoration:none;flex-shrink:0}
.hdr .logout{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#f87171;width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;text-decoration:none;flex-shrink:0}

.wrap{max-width:900px;margin:0 auto;padding:20px 16px;position:relative;z-index:1}

/* Progress bar */
.steps{display:flex;gap:8px;margin-bottom:20px}
.step-dot{flex:1;height:4px;background:rgba(255,255,255,.08);border-radius:3px;transition:all .3s;position:relative;overflow:hidden}
.step-dot.active{background:linear-gradient(90deg,#10b981,#2cee82);box-shadow:0 0 12px rgba(16,185,129,.6)}
.step-dot.done{background:linear-gradient(90deg,#10b981,#2cee82)}

/* Cards */
.card{background:linear-gradient(145deg,rgba(15,23,42,.7),rgba(11,18,32,.5));backdrop-filter:blur(20px);border:1px solid rgba(59,130,246,.15);border-radius:20px;padding:22px;margin-bottom:16px;position:relative;overflow:hidden;transition:all .3s}
.card:focus-within{border-color:rgba(59,130,246,.4);box-shadow:0 8px 32px -12px rgba(37,99,235,.3)}
.card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,#3b82f6,transparent);opacity:0;transition:opacity .3s}
.card:focus-within::before{opacity:.7}

/* Step badge */
.step-head{display:flex;align-items:center;gap:12px;margin-bottom:16px}
.step-num{width:32px;height:32px;border-radius:10px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;font-weight:900;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 14px rgba(59,130,246,.4)}
.step-title{font-size:15px;font-weight:900;color:#fff;letter-spacing:-.2px}
.step-sub{font-size:11px;color:#64748b;margin-top:1px;font-weight:600}

/* Inputs */
.sel,.inp{width:100%;background:rgba(7,11,20,.8);border:1.5px solid rgba(59,130,246,.2);color:#fff;border-radius:12px;padding:15px 16px;font-size:15px;min-height:52px;font-family:inherit;outline:none;transition:all .2s;font-weight:600}
.sel{-webkit-appearance:none;appearance:none;background-image:url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2360a5fa' stroke-width='2.5' stroke-linecap='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center;background-size:16px;padding-right:42px;cursor:pointer}
.sel:focus,.inp:focus{border-color:#3b82f6;box-shadow:0 0 0 4px rgba(37,99,235,.15);background:rgba(11,18,32,.95)}
.sel:disabled{opacity:.4;cursor:not-allowed}
.sel option{background:#0b1220;color:#fff;padding:10px}
.inp::placeholder{color:#475569;font-weight:500}

.row{margin-bottom:12px}
.row:last-child{margin-bottom:0}
.row-label{display:block;font-size:10.5px;font-weight:800;color:#60a5fa;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:6px}
.row-label .opt{color:#475569;font-weight:600;letter-spacing:0;text-transform:none;font-size:10px;margin-left:4px}

/* Quick add btn */
.qa-btn{width:52px;height:52px;border-radius:11px;background:rgba(16,185,129,.1);border:1.5px solid rgba(16,185,129,.35);color:#34d399;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s;padding:0}
.qa-btn:hover{background:rgba(16,185,129,.25);transform:scale(1.05)}
.qa-btn i{font-size:20px;pointer-events:none}

/* Type pills */
.type-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}
.type-pill{padding:18px 8px;min-height:80px;text-align:center;border:2px solid #1f2937;border-radius:14px;cursor:pointer;background:rgba(7,11,20,.6);transition:all .2s;font-size:11.5px;font-weight:800;color:#64748b;position:relative;overflow:hidden}
.type-pill i{display:block;font-size:24px;margin-bottom:6px;transition:transform .2s}
.type-pill:hover{border-color:rgba(59,130,246,.4);color:#94a3b8}
.type-pill:hover i{transform:scale(1.15)}
.type-pill.sel-note{border-color:#60a5fa;background:linear-gradient(135deg,rgba(59,130,246,.18),rgba(37,99,235,.08));color:#fff;box-shadow:0 8px 24px -8px rgba(59,130,246,.5)}
.type-pill.sel-note i{color:#60a5fa}
.type-pill.sel-dpp{border-color:#c084fc;background:linear-gradient(135deg,rgba(168,85,247,.18),rgba(126,34,206,.08));color:#fff;box-shadow:0 8px 24px -8px rgba(168,85,247,.5)}
.type-pill.sel-dpp i{color:#c084fc}
.type-pill.sel-video{border-color:#f87171;background:linear-gradient(135deg,rgba(239,68,68,.18),rgba(220,38,38,.08));color:#fff;box-shadow:0 8px 24px -8px rgba(239,68,68,.5)}
.type-pill.sel-video i{color:#f87171}
.type-pill.sel-link{border-color:#fb923c;background:linear-gradient(135deg,rgba(251,146,60,.18),rgba(234,88,12,.08));color:#fff;box-shadow:0 8px 24px -8px rgba(251,146,60,.5)}
.type-pill.sel-link i{color:#fb923c}

/* Drop zones */
.drop-zone{background:linear-gradient(145deg,rgba(15,23,42,.6),rgba(11,18,32,.4));border:2px dashed rgba(59,130,246,.3);border-radius:16px;padding:32px 20px;min-height:180px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;cursor:pointer;transition:all .25s;position:relative;overflow:hidden}
.drop-zone:hover{border-color:#3b82f6;background:linear-gradient(145deg,rgba(59,130,246,.1),rgba(37,99,235,.04));transform:translateY(-1px)}
.drop-zone.drag{border-color:#10b981;background:rgba(16,185,129,.1);transform:scale(1.01)}
.drop-zone.has-file{border-style:solid;border-color:rgba(16,185,129,.6);background:rgba(16,185,129,.06);padding:16px}
.dz-icon{width:60px;height:60px;border-radius:16px;background:linear-gradient(135deg,rgba(59,130,246,.2),rgba(37,99,235,.1));border:1px solid rgba(59,130,246,.35);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:#60a5fa;font-size:28px;transition:transform .25s}
.drop-zone:hover .dz-icon{transform:scale(1.08) rotate(-4deg)}
.dz-title{font-size:14.5px;font-weight:800;color:#e2e8f0;margin-bottom:4px}
.dz-title .hl{color:#60a5fa}
.dz-hint{font-size:11.5px;color:#64748b;line-height:1.5}

/* File preview */
.file-preview{display:none;align-items:center;gap:14px;text-align:left}
.file-preview.show{display:flex}
.fp-icon{width:56px;height:56px;border-radius:14px;background:rgba(16,185,129,.15);border:1px solid rgba(16,185,129,.35);display:flex;align-items:center;justify-content:center;color:#34d399;font-size:24px;flex-shrink:0}
.fp-info{flex:1;min-width:0}
.fp-name{font-size:13.5px;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fp-size{font-size:11px;color:#34d399;font-weight:700;margin-top:3px}
.fp-remove{width:36px;height:36px;border-radius:50%;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.35);color:#f87171;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;padding:0}
.fp-remove:hover{background:rgba(239,68,68,.25)}

/* Video URL + Thumb section */
.video-fields{display:none;flex-direction:column;gap:14px}
.video-fields.show{display:flex}

.thumb-upload{background:linear-gradient(145deg,rgba(239,68,68,.06),rgba(220,38,38,.03));border:2px dashed rgba(239,68,68,.3);border-radius:16px;padding:18px;text-align:center;cursor:pointer;transition:all .25s;position:relative;overflow:hidden}
.thumb-upload:hover{border-color:#f87171;background:rgba(239,68,68,.08)}
.thumb-upload.has-thumb{border-style:solid;border-color:rgba(239,68,68,.5);padding:12px}
.thumb-upload input{display:none}
.thumb-ph{width:80px;height:80px;border-radius:14px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#f87171;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:32px}
.thumb-img{width:140px;height:80px;border-radius:10px;object-fit:cover;margin:0 auto 8px;display:block;border:2px solid rgba(239,68,68,.5)}
.thumb-hint{font-size:12px;color:#94a3b8;font-weight:600}
.thumb-hint .hl{color:#f87171}
.thumb-hint-sm{font-size:10.5px;color:#64748b;margin-top:4px}

/* Submit btn */
.submit-btn{width:100%;padding:20px 18px;min-height:60px;border-radius:16px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-size:15px;font-weight:900;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px;box-shadow:0 12px 32px -8px rgba(16,185,129,.6);letter-spacing:.3px;margin-top:16px;transition:all .2s;font-family:inherit}
.submit-btn:hover{transform:translateY(-2px);box-shadow:0 16px 40px -8px rgba(16,185,129,.7)}
.submit-btn:active{transform:scale(.98)}
.submit-btn:disabled{opacity:.6;cursor:not-allowed;transform:none}
.submit-btn i{font-size:22px}

/* Alert */
.alert{padding:14px 18px;border-radius:14px;font-size:13px;font-weight:700;margin-bottom:16px;display:none;align-items:center;gap:10px;animation:slideIn .3s}
.alert.show{display:flex}
.alert i{font-size:20px;flex-shrink:0}
.alert-ok{background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.35);color:#34d399}
.alert-err{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.35);color:#f87171}
@keyframes slideIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}

/* Filter tabs */
.filter-tabs{display:flex;gap:6px;margin-bottom:14px;overflow-x:auto;scrollbar-width:none;padding:2px}
.filter-tabs::-webkit-scrollbar{display:none}
.ftab{padding:9px 16px;border-radius:20px;background:rgba(15,23,42,.7);border:1.5px solid rgba(59,130,246,.2);color:#94a3b8;font-size:12px;font-weight:800;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:6px;transition:all .15s;font-family:inherit;flex-shrink:0}
.ftab:hover{border-color:rgba(59,130,246,.5);color:#fff}
.ftab.active{background:linear-gradient(135deg,#2563eb,#3b82f6);color:#fff;border-color:transparent;box-shadow:0 4px 14px rgba(37,99,235,.4)}
.ftab i{font-size:14px}

/* Content item card */
.content-item{background:linear-gradient(145deg,rgba(15,23,42,.7),rgba(11,18,32,.5));border:1px solid rgba(59,130,246,.15);border-radius:14px;padding:14px;margin-bottom:10px;display:flex;align-items:center;gap:12px;transition:all .15s}
.content-item:hover{border-color:rgba(59,130,246,.4);transform:translateX(2px)}
.ci-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;overflow:hidden}
.ci-icon img{width:100%;height:100%;object-fit:cover}
.ci-icon.i-note{background:rgba(59,130,246,.15);color:#60a5fa}
.ci-icon.i-dpp{background:rgba(168,85,247,.15);color:#c084fc}
.ci-icon.i-video{background:rgba(239,68,68,.15);color:#f87171}
.ci-icon.i-link{background:rgba(251,146,60,.15);color:#fb923c}
.ci-info{flex:1;min-width:0}
.ci-title{font-size:13.5px;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ci-path{font-size:10.5px;color:#64748b;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ci-badge{display:inline-block;font-size:9.5px;font-weight:800;padding:2px 8px;border-radius:8px;letter-spacing:.5px;text-transform:uppercase;margin-right:6px}
.ci-badge.b-note{background:rgba(59,130,246,.15);color:#60a5fa}
.ci-badge.b-dpp{background:rgba(168,85,247,.15);color:#c084fc}
.ci-badge.b-video{background:rgba(239,68,68,.15);color:#f87171}
.ci-badge.b-link{background:rgba(251,146,60,.15);color:#fb923c}
.ci-actions{display:flex;gap:5px;flex-shrink:0}
.ci-btn{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;cursor:pointer;border:1px solid;background:transparent;transition:all .15s;padding:0;text-decoration:none}
.ci-btn i{font-size:15px}
.ci-btn.del{color:#f87171;border-color:rgba(239,68,68,.3);background:rgba(239,68,68,.08)}
.ci-btn.del:hover{background:rgba(239,68,68,.2)}
.ci-btn.view{color:#34d399;border-color:rgba(16,185,129,.3);background:rgba(16,185,129,.08)}
.ci-btn.view:hover{background:rgba(16,185,129,.2)}
.ci-btn.edit{color:#60a5fa;border-color:rgba(59,130,246,.3);background:rgba(59,130,246,.08)}
.ci-btn.edit:hover{background:rgba(59,130,246,.2)}

/* Chapter group header */
.chapter-group{margin-bottom:20px}
.chapter-head{background:linear-gradient(135deg,rgba(251,146,60,.15),rgba(249,115,22,.05));border:1px solid rgba(251,146,60,.3);border-radius:14px;padding:12px 16px;margin-bottom:10px;display:flex;align-items:center;gap:10px}
.ch-num{width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,#fb923c,#f97316);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:12px;flex-shrink:0}
.ch-name{flex:1;font-size:14px;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ch-count{font-size:10.5px;font-weight:800;color:#fb923c;background:rgba(251,146,60,.15);padding:3px 10px;border-radius:10px;flex-shrink:0}
.ch-del{width:34px;height:34px;border-radius:9px;background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.35);color:#f87171;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;padding:0}
.ch-del:hover{background:rgba(239,68,68,.3)}

/* Recent */
.recent-title{font-size:12px;font-weight:900;color:#60a5fa;letter-spacing:1.2px;text-transform:uppercase;margin:24px 0 12px;display:flex;align-items:center;gap:8px}
.recent-item{background:linear-gradient(145deg,rgba(15,23,42,.6),rgba(11,18,32,.4));border:1px solid rgba(59,130,246,.12);border-radius:14px;padding:12px 14px;margin-bottom:8px;display:flex;align-items:center;gap:12px}
.ri-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:20px}
.ri-info{flex:1;min-width:0}
.ri-title{font-size:13px;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ri-path{font-size:10.5px;color:#64748b;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ri-time{font-size:10px;color:#475569;font-weight:700;flex-shrink:0}

/* Modal */
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.85);backdrop-filter:blur(8px);z-index:100;display:none;align-items:flex-end;justify-content:center;padding:0}
.modal-bg.show{display:flex}
.modal{background:#0b1220;border:1px solid rgba(59,130,246,.3);border-radius:24px 24px 0 0;padding:24px 20px;width:100%;max-width:500px;animation:slideUp .3s}
@keyframes slideUp{from{transform:translateY(40px);opacity:0}to{transform:translateY(0);opacity:1}}
.modal-handle{width:40px;height:4px;background:#374151;border-radius:2px;margin:0 auto 20px}
.modal h3{font-size:16px;font-weight:900;color:#fff;margin:0 0 16px;display:flex;align-items:center;gap:10px}
.modal-btns{display:flex;gap:8px;margin-top:8px}
.modal-btns button{flex:1;padding:14px;border-radius:12px;border:none;font-weight:800;font-size:14px;cursor:pointer;font-family:inherit}
.btn-g{background:linear-gradient(135deg,#10b981,#059669);color:#fff}
.btn-c{background:#1e293b;color:#cbd5e1}
</style>
  <link rel="stylesheet" href="../assets/premium.css">
</head>
<body>

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="hdr">
<a href="index.php" class="back"><i class="ph-bold ph-arrow-left"></i></a>
<h1>Content Manager</h1>
<a href="logout.php" class="logout"><i class="ph-bold ph-sign-out"></i></a>
</div>

<div class="wrap">

<!-- Progress -->
<div class="steps">
<div class="step-dot active" id="sd1"></div>
<div class="step-dot" id="sd2"></div>
<div class="step-dot" id="sd3"></div>
</div>

<div class="alert" id="alert"><i class="ph-bold ph-check-circle"></i> <span id="alertMsg"></span></div>

<!-- STEP 1 -->
<div class="card">
<div class="step-head">
<div class="step-num">1</div>
<div>
<div class="step-title">Select Path</div>
<div class="step-sub">Institution → Batch → Subject → Chapter</div>
</div>
</div>

<div class="row">
<label class="row-label">Institution <span class="opt">(optional — All by default)</span></label>
<div style="display:flex;gap:8px">
<select class="sel" id="instSel" style="flex:1" onchange="loadBatches()">
<option value="">🌐 All Institutions</option>
<?php foreach ($institutions as $ins): ?>
<option value="<?= htmlspecialchars($ins['id']) ?>"><?= htmlspecialchars($ins['name']) ?></option>
<?php endforeach; ?>
</select>
<button class="qa-btn" onclick="openAddModal('institution')"><i class="ph-bold ph-plus"></i></button>
</div>
</div>

<div class="row">
<label class="row-label">Batch <span class="opt">*</span></label>
<div style="display:flex;gap:8px">
<select class="sel" id="batchSel" style="flex:1" onchange="loadSubjects()">
<option value="">-- Select Batch --</option>
</select>
<button class="qa-btn" onclick="openAddModal('batch')"><i class="ph-bold ph-plus"></i></button>
</div>
</div>

<div class="row">
<label class="row-label">Subject <span class="opt">*</span></label>
<div style="display:flex;gap:8px">
<select class="sel" id="subjectSel" style="flex:1" onchange="loadChapters()" disabled>
<option value="">-- Select Subject --</option>
</select>
<button class="qa-btn" onclick="openAddModal('subject')"><i class="ph-bold ph-plus"></i></button>
</div>
</div>

<div class="row">
<label class="row-label">Chapter <span class="opt">*</span></label>
<div style="display:flex;gap:8px">
<select class="sel" id="chapterSel" style="flex:1" disabled onchange="updateSteps()">
<option value="">-- Select Chapter --</option>
</select>
<button class="qa-btn" onclick="openAddModal('chapter')"><i class="ph-bold ph-plus"></i></button>
</div>
</div>
</div>

<!-- STEP 2 -->
<div class="card">
<div class="step-head">
<div class="step-num">2</div>
<div>
<div class="step-title">Content Type</div>
<div class="step-sub">क्या upload करना है?</div>
</div>
</div>

<div class="type-grid">
<div class="type-pill sel-note" id="pill-note" onclick="pickType('note')">
<i class="ph-bold ph-file-pdf"></i>Note
</div>
<div class="type-pill" id="pill-dpp" onclick="pickType('dpp')">
<i class="ph-bold ph-note-pencil"></i>DPP
</div>
<div class="type-pill" id="pill-video" onclick="pickType('video')">
<i class="ph-bold ph-play-circle"></i>Video
</div>
<div class="type-pill" id="pill-link" onclick="pickType('link')">
<i class="ph-bold ph-link"></i>Link
</div>
</div>
</div>

<!-- STEP 3 -->
<div class="card">
<div class="step-head">
<div class="step-num">3</div>
<div>
<div class="step-title">Upload Content</div>
<div class="step-sub" id="uploadSubtitle">Drop notes or PDF files</div>
</div>
</div>

<div class="row">
<label class="row-label">Title <span class="opt">(blank = auto from filename)</span></label>
<input type="text" class="inp" id="titleInp" placeholder="Kinematics Chapter Notes"/>
</div>

<!-- Files drop zone (for note/dpp) -->
<div id="filesBlock">
<label class="drop-zone" id="dropZone" for="fileInput">
<input type="file" id="fileInput" multiple accept=".pdf,.doc,.docx,.ppt,.pptx,.zip" onchange="addFiles(this.files)"/>
<div id="dzEmpty">
<div class="dz-icon"><i class="ph-bold ph-upload-simple"></i></div>
<div class="dz-title">Click or drop files <span class="hl">here</span></div>
<div class="dz-hint">Multiple files allowed · PDF, DOC, PPT, ZIP · max 50MB each</div>
</div>
<div class="file-preview" id="dzPreview">
<div class="fp-icon"><i class="ph-bold ph-file"></i></div>
<div class="fp-info">
<div class="fp-name" id="fpName">file.pdf</div>
<div class="fp-size" id="fpSize">0 MB</div>
</div>
<button type="button" class="fp-remove" onclick="removeFile(event)"><i class="ph-bold ph-x"></i></button>
</div>
</label>
</div>

<!-- Video fields (URL + Thumbnail) -->
<div class="video-fields" id="videoBlock">
<div class="row">
<label class="row-label">🎬 Video URL <span class="opt">(YouTube or .m3u8)</span></label>
<input type="text" class="inp" id="videoUrlInp" placeholder="https://www.youtube.com/watch?v=VIDEO_ID"/>
</div>

<div class="row">
<label class="row-label">🖼️ Video Thumbnail <span class="opt">(optional — auto from YouTube)</span></label>
<label class="thumb-upload" id="thumbUpload">
<input type="file" id="thumbInput" accept="image/*" onchange="previewThumb(this)"/>
<div id="thumbEmpty">
<div class="thumb-ph"><i class="ph-bold ph-image"></i></div>
<div class="thumb-hint">Click to upload <span class="hl">custom thumbnail</span></div>
<div class="thumb-hint-sm">PNG, JPG, WEBP · 16:9 recommended · max 3MB</div>
</div>
<div id="thumbFilled" style="display:none">
<img id="thumbImg" class="thumb-img" src="" alt=""/>
<div class="thumb-hint">✓ Thumbnail selected</div>
<div class="thumb-hint-sm" id="thumbName">thumbnail.jpg</div>
</div>
</label>
</div>
</div>

<!-- Link field -->
<div id="linkBlock" style="display:none">
<div class="row">
<label class="row-label">🔗 External URL</label>
<input type="text" class="inp" id="linkUrlInp" placeholder="https://example.com/resource"/>
</div>
</div>

<button class="submit-btn" id="submitBtn" onclick="submitContent()">
<i class="ph-bold ph-cloud-arrow-up"></i> Upload Content
</button>
</div>

<!-- RECENT -->

<!-- VIEW & MANAGE ALL CONTENT -->
<div class="recent-title" style="color:#c084fc;margin-top:32px">
<i class="ph-bold ph-list-bullets"></i> View & Manage All Content
</div>

<div class="filter-tabs">
<button class="ftab active" data-type="all" onclick="loadAllContent('all')"><i class="ph-bold ph-squares-four"></i> All</button>
<button class="ftab" data-type="note" onclick="loadAllContent('note')"><i class="ph-bold ph-file-pdf"></i> Notes</button>
<button class="ftab" data-type="dpp" onclick="loadAllContent('dpp')"><i class="ph-bold ph-note-pencil"></i> DPP</button>
<button class="ftab" data-type="video" onclick="loadAllContent('video')"><i class="ph-bold ph-play-circle"></i> Videos</button>
<button class="ftab" data-type="link" onclick="loadAllContent('link')"><i class="ph-bold ph-link"></i> Links</button>
</div>

<div style="position:relative;margin-bottom:14px">
<i class="ph-bold ph-magnifying-glass" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#64748b;font-size:16px;pointer-events:none"></i>
<input type="text" id="contentSearch" class="inp" placeholder="Search all content..." style="padding-left:42px" oninput="filterAllContent(this.value)"/>
</div>

<div id="allContentList">
<div style="text-align:center;color:#475569;padding:40px 20px;font-size:13px">
<i class="ph-bold ph-circle-notch" style="animation:spin 1s linear infinite;display:block;font-size:28px;margin:0 auto 10px;opacity:.4"></i>
Loading content...
</div>
</div>



</div>


<!-- EDIT CONTENT MODAL -->
<div class="modal-bg" id="editContentModal" onclick="if(event.target===this)closeEditItem()">
<div class="modal" style="max-height:90vh;overflow-y:auto">
<div class="modal-handle"></div>
<h3><i class="ph-bold ph-pencil-simple" style="color:#60a5fa"></i> Edit Content</h3>

<input type="hidden" id="editItemId"/>

<div style="margin-bottom:14px">
<label style="display:block;font-size:10.5px;font-weight:800;color:#60a5fa;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:6px">Title</label>
<input type="text" class="inp" id="editItemTitle" placeholder="Content title"/>
</div>

<!-- Video URL (only for video type) -->
<div id="editVideoField" style="display:none;margin-bottom:14px">
<label style="display:block;font-size:10.5px;font-weight:800;color:#f87171;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:6px">🎬 Video URL</label>
<input type="text" class="inp" id="editItemVideoUrl" placeholder="https://youtube.com/watch?v=..."/>
</div>

<!-- Link URL (only for link type) -->
<div id="editLinkField" style="display:none;margin-bottom:14px">
<label style="display:block;font-size:10.5px;font-weight:800;color:#fb923c;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:6px">🔗 External URL</label>
<input type="text" class="inp" id="editItemExternalUrl" placeholder="https://..."/>
</div>

<!-- Thumbnail (for video) -->
<div id="editThumbField" style="display:none;margin-bottom:14px">
<label style="display:block;font-size:10.5px;font-weight:800;color:#f87171;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:6px">🖼️ Thumbnail</label>
<div id="editThumbCurrent" style="margin-bottom:10px"></div>
<label for="editThumbInput" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:12px;border:2px dashed rgba(239,68,68,.3);border-radius:12px;cursor:pointer;background:rgba(239,68,68,.04);color:#f87171;font-weight:700;font-size:13px">
<i class="ph-bold ph-upload-simple"></i> Upload new thumbnail
<input type="file" id="editThumbInput" accept="image/*" style="display:none" onchange="previewEditThumb(this)"/>
</label>
<div id="editThumbPreview" style="display:none;margin-top:10px;text-align:center">
<img id="editThumbImg" style="max-width:100%;max-height:120px;border-radius:10px;border:2px solid rgba(16,185,129,.4)"/>
</div>
</div>

<!-- File (for note/dpp) -->
<div id="editFileField" style="display:none;margin-bottom:14px">
<label style="display:block;font-size:10.5px;font-weight:800;color:#60a5fa;letter-spacing:1.2px;text-transform:uppercase;margin-bottom:6px">📎 Replace File</label>
<div id="editFileCurrent" style="font-size:11px;color:#64748b;padding:8px 12px;background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);border-radius:10px;margin-bottom:8px"></div>
<label for="editFileInput" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:12px;border:2px dashed rgba(59,130,246,.3);border-radius:12px;cursor:pointer;background:rgba(59,130,246,.04);color:#60a5fa;font-weight:700;font-size:13px">
<i class="ph-bold ph-upload-simple"></i> Upload new file
<input type="file" id="editFileInput" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip" style="display:none"/>
</label>
</div>

<div class="modal-btns">
<button class="btn-g" onclick="saveEditItem()"><i class="ph-bold ph-check"></i> Save Changes</button>
<button class="btn-c" onclick="closeEditItem()">Cancel</button>
</div>
</div>
</div>

<!-- ADD MODAL -->
<div class="modal-bg" id="addModal" onclick="if(event.target===this)closeAddModal()">
<div class="modal">
<div class="modal-handle"></div>
<h3 id="addModalTitle"><i class="ph-bold ph-plus-circle" style="color:#60a5fa"></i> Add New</h3>
<input type="text" class="inp" id="addModalInput" placeholder="Name" style="margin-bottom:16px"/>
<div class="modal-btns">
<button class="btn-g" onclick="saveAddModal()">Save</button>
<button class="btn-c" onclick="closeAddModal()">Cancel</button>
</div>
</div>
</div>

<script>
var currentType = 'note';
var currentFiles = [];
var addModalMode = '';

function showAlert(type, msg){
  var a = document.getElementById('alert');
  a.className = 'alert alert-' + type + ' show';
  a.querySelector('i').className = type === 'ok' ? 'ph-bold ph-check-circle' : 'ph-bold ph-warning-circle';
  document.getElementById('alertMsg').textContent = msg;
  clearTimeout(a._t);
  a._t = setTimeout(function(){ a.classList.remove('show'); }, 4000);
}

// ============ LOADERS ============
function loadBatches(){
  var inst = document.getElementById('instSel').value;
  var bsel = document.getElementById('batchSel');
  var ssel = document.getElementById('subjectSel');
  var csel = document.getElementById('chapterSel');
  bsel.innerHTML = '<option value="">-- Select Batch --</option>';
  ssel.innerHTML = '<option value="">-- Select Subject --</option>';
  csel.innerHTML = '<option value="">-- Select Chapter --</option>';
  ssel.disabled = true; csel.disabled = true;
  document.getElementById('sd2').classList.remove('active');
  document.getElementById('sd3').classList.remove('active');
  
  var url = 'quick_api.php?action=batches';
  if (inst) url += '&institution=' + encodeURIComponent(inst);
  fetch(url).then(function(r){ return r.json(); }).then(function(d){
    if (d.success && d.data.length > 0) {
      d.data.forEach(function(b){ bsel.innerHTML += '<option value="'+b.id+'">'+esc(b.name)+'</option>'; });
    }
  });
  document.getElementById('sd1').classList.add('active');
}

function loadSubjects(){
  var bid = document.getElementById('batchSel').value;
  var ssel = document.getElementById('subjectSel');
  var csel = document.getElementById('chapterSel');
  ssel.innerHTML = '<option value="">-- Select Subject --</option>';
  csel.innerHTML = '<option value="">-- Select Chapter --</option>';
  csel.disabled = true;
  if (!bid) { ssel.disabled = true; return; }
  fetch('quick_api.php?action=subjects&batch=' + bid).then(function(r){ return r.json(); }).then(function(d){
    if (d.success) {
      d.data.forEach(function(s){ ssel.innerHTML += '<option value="'+s.id+'">'+esc(s.name)+'</option>'; });
      ssel.disabled = false;
    }
  });
}

function loadChapters(){
  var sid = document.getElementById('subjectSel').value;
  var csel = document.getElementById('chapterSel');
  csel.innerHTML = '<option value="">-- Select Chapter --</option>';
  if (!sid) { csel.disabled = true; return; }
  fetch('quick_api.php?action=chapters&subject=' + sid).then(function(r){ return r.json(); }).then(function(d){
    if (d.success) {
      d.data.forEach(function(c){ csel.innerHTML += '<option value="'+c.id+'">'+esc(c.name)+'</option>'; });
      csel.disabled = false;
    }
  });
}

function updateSteps(){
  var bid = document.getElementById('batchSel').value;
  var sid = document.getElementById('subjectSel').value;
  var cid = document.getElementById('chapterSel').value;
  if (bid) document.getElementById('sd1').classList.add('active');
  if (sid) document.getElementById('sd2').classList.add('active');
  if (cid) document.getElementById('sd3').classList.add('active');
}

// ============ TYPE ============
function pickType(t){
  currentType = t;
  ['note','dpp','video','link'].forEach(function(x){ document.getElementById('pill-' + x).className = 'type-pill'; });
  document.getElementById('pill-' + t).classList.add('sel-' + t);

  document.getElementById('filesBlock').style.display = (t === 'note' || t === 'dpp') ? 'block' : 'none';
  document.getElementById('videoBlock').classList.toggle('show', t === 'video');
  document.getElementById('linkBlock').style.display = (t === 'link') ? 'block' : 'none';

  var subs = {
    note: 'Drop PDF or document files',
    dpp: 'Drop practice sheet files',
    video: 'Add YouTube or HLS video URL',
    link: 'Add external link'
  };
  document.getElementById('uploadSubtitle').textContent = subs[t] || 'Upload content';
  
  // Clear files when switching type
  if (t !== 'note' && t !== 'dpp') {
    currentFiles = [];
    renderFile();
  }
}

// ============ FILES ============
function addFiles(fileList){
  for (var i = 0; i < fileList.length; i++){
    var f = fileList[i];
    if (f.size > 50 * 1024 * 1024) { showAlert('err', f.name + ' is too large (>50MB)'); continue; }
    currentFiles.push(f);
  }
  renderFile();
}

function renderFile(){
  var dz = document.getElementById('dropZone');
  if (currentFiles.length === 0) {
    document.getElementById('dzEmpty').style.display = 'block';
    document.getElementById('dzPreview').classList.remove('show');
    dz.classList.remove('has-file');
    return;
  }
  dz.classList.add('has-file');
  document.getElementById('dzEmpty').style.display = 'none';
  var prev = document.getElementById('dzPreview');
  prev.classList.add('show');
  if (currentFiles.length === 1) {
    document.getElementById('fpName').textContent = currentFiles[0].name;
    document.getElementById('fpSize').textContent = (currentFiles[0].size/1024/1024).toFixed(2) + ' MB';
  } else {
    document.getElementById('fpName').textContent = currentFiles.length + ' files selected';
    var total = currentFiles.reduce(function(s, f){ return s + f.size; }, 0);
    document.getElementById('fpSize').textContent = (total/1024/1024).toFixed(2) + ' MB total';
  }
}

function removeFile(e){
  e.preventDefault(); e.stopPropagation();
  currentFiles = [];
  renderFile();
  document.getElementById('fileInput').value = '';
}

// Drag & drop
var dz = document.getElementById('dropZone');
['dragenter','dragover'].forEach(function(e){ dz.addEventListener(e, function(ev){ ev.preventDefault(); dz.classList.add('drag'); }); });
['dragleave','drop'].forEach(function(e){ dz.addEventListener(e, function(ev){ ev.preventDefault(); dz.classList.remove('drag'); }); });
dz.addEventListener('drop', function(ev){
  ev.preventDefault();
  if (ev.dataTransfer.files.length) addFiles(ev.dataTransfer.files);
});

// ============ THUMBNAIL ============
function previewThumb(input){
  if (!input.files || !input.files[0]) return;
  var f = input.files[0];
  if (f.size > 3 * 1024 * 1024) { showAlert('err', 'Thumbnail too large (max 3MB)'); input.value=''; return; }
  // INSTANT: use URL.createObjectURL (0ms, no base64 conversion)
  var objectUrl = URL.createObjectURL(f);
  document.getElementById('thumbImg').src = objectUrl;
  document.getElementById('thumbEmpty').style.display = 'none';
  document.getElementById('thumbFilled').style.display = 'block';
  document.getElementById('thumbName').textContent = f.name;
  document.getElementById('thumbUpload').classList.add('has-thumb');
}

// ============ SUBMIT ============
function submitContent(){
  var chId = document.getElementById('chapterSel').value;
  if (!chId) { showAlert('err', 'Please select a chapter first'); return; }

  var title = document.getElementById('titleInp').value.trim();
  var videoUrl = document.getElementById('videoUrlInp').value.trim();
  var linkUrl = document.getElementById('linkUrlInp').value.trim();

  if ((currentType === 'note' || currentType === 'dpp') && currentFiles.length === 0) {
    showAlert('err', 'Please select at least one file'); return;
  }
  if (currentType === 'video' && !videoUrl) { showAlert('err', 'Please add video URL'); return; }
  if (currentType === 'link' && !linkUrl) { showAlert('err', 'Please add link URL'); return; }

  var btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="ph-bold ph-circle-notch" style="animation:spin 1s linear infinite"></i> Uploading...';

  // Build FormData
  var fd = new FormData();
  fd.append('action', 'bulk_save');
  fd.append('chapter_id', chId);
  fd.append('ctype', currentType);
  fd.append('title', title);

  if (currentType === 'note' || currentType === 'dpp') {
    // Only add files for note/dpp
    if (currentFiles.length > 0) {
      currentFiles.forEach(function(f){ fd.append('files[]', f); });
    }
  } else if (currentType === 'video') {
    if (!videoUrl) { showAlert('err', 'Video URL required'); btn.disabled=false; btn.innerHTML='<i class="ph-bold ph-cloud-arrow-up"></i> Upload Content'; return; }
    fd.append('video_url', videoUrl);
    // Attach thumbnail if selected
    var thumbInp = document.getElementById('thumbInput');
    if (thumbInp && thumbInp.files && thumbInp.files[0]) {
      fd.append('thumbnail', thumbInp.files[0]);
    }
  } else if (currentType === 'link') {
    if (!linkUrl) { showAlert('err', 'Link URL required'); btn.disabled=false; btn.innerHTML='<i class="ph-bold ph-cloud-arrow-up"></i> Upload Content'; return; }
    fd.append('external_url', linkUrl);
  }

  var xhr = new XMLHttpRequest();
  xhr.open('POST', 'quick_api.php', true);
  xhr.onload = function(){
    btn.disabled = false;
    btn.innerHTML = '<i class="ph-bold ph-cloud-arrow-up"></i> Upload Content';
    try {
      var d = JSON.parse(xhr.responseText);
      if (d.success) {
        showAlert('ok', '✓ ' + (d.count || 1) + ' item(s) uploaded successfully!');
        currentFiles = [];
        renderFile();
        document.getElementById('titleInp').value = '';
        document.getElementById('videoUrlInp').value = '';
        document.getElementById('linkUrlInp').value = '';
        document.getElementById('fileInput').value = '';
        var ti = document.getElementById('thumbInput');
        if (ti) ti.value = '';
        var tempty = document.getElementById('thumbEmpty');
        if (tempty) tempty.style.display = 'block';
        var tfilled = document.getElementById('thumbFilled');
        if (tfilled) tfilled.style.display = 'none';
        var tu = document.getElementById('thumbUpload');
        if (tu) tu.classList.remove('has-thumb');
        fetchAllContent();
      } else {
        showAlert('err', d.error || 'Upload failed');
      }
    } catch(e) {
      showAlert('err', 'Server error: ' + e.message);
    }
  };
  xhr.onerror = function(){
    btn.disabled = false;
    btn.innerHTML = '<i class="ph-bold ph-cloud-arrow-up"></i> Upload Content';
    showAlert('err', 'Network error');
  };
  xhr.send(fd);
}


// ============ MODAL ============
function openAddModal(mode){
  addModalMode = mode;
  var titles = { institution: 'Add Institution', batch: 'Add Batch', subject: 'Add Subject', chapter: 'Add Chapter' };
  document.getElementById('addModalTitle').innerHTML = '<i class="ph-bold ph-plus-circle" style="color:#60a5fa"></i> ' + titles[mode];
  document.getElementById('addModalInput').value = '';
  document.getElementById('addModalInput').placeholder = titles[mode].replace('Add ', '') + ' name';
  document.getElementById('addModal').classList.add('show');
  setTimeout(function(){ document.getElementById('addModalInput').focus(); }, 100);
}
function closeAddModal(){ document.getElementById('addModal').classList.remove('show'); }

function saveAddModal(){
  var name = document.getElementById('addModalInput').value.trim();
  if (!name) { alert('Please enter a name'); return; }
  var fd = new FormData();
  fd.append('name', name);
  if (addModalMode === 'institution') {
    fd.append('action', 'add_institution');
  } else if (addModalMode === 'batch') {
    var inst = document.getElementById('instSel').value;
    if (!inst) { showAlert('err', 'Please select an institution first'); return; }
    fd.append('action', 'add_batch');
    fd.append('institution_id', inst);
  } else if (addModalMode === 'subject') {
    var bid = document.getElementById('batchSel').value;
    if (!bid) { showAlert('err', 'Please select a batch first'); return; }
    fd.append('action', 'add_subject');
    fd.append('batch_id', bid);
  } else if (addModalMode === 'chapter') {
    var sid = document.getElementById('subjectSel').value;
    if (!sid) { showAlert('err', 'Please select a subject first'); return; }
    fd.append('action', 'add_chapter');
    fd.append('subject_id', sid);
  }
  fetch('quick_api.php', { method: 'POST', body: fd }).then(function(r){ return r.json(); }).then(function(d){
    if (d.success) {
      closeAddModal();
      showAlert('ok', name + ' added');
      if (addModalMode === 'batch') loadBatches();
      else if (addModalMode === 'subject') loadSubjects();
      else if (addModalMode === 'chapter') loadChapters();
    } else {
      showAlert('err', d.error || 'Failed');
    }
  });
}

function esc(s){ return String(s||'').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

// ============ VIEW ALL CONTENT ============
var allContentData = [];
var currentFilter = 'all';
var currentSearch = '';

function loadAllContent(filter){
  currentFilter = filter;
  document.querySelectorAll('.ftab').forEach(function(t){ t.classList.remove('active'); });
  document.querySelector('.ftab[data-type="' + filter + '"]').classList.add('active');
  fetchAllContent();
}

function fetchAllContent(){
  fetch('quick_api.php?action=all_content').then(function(r){ return r.json(); }).then(function(d){
    if (d.success) {
      allContentData = d.data;
      renderAllContent();
    } else {
      document.getElementById('allContentList').innerHTML = '<div style="text-align:center;color:#f87171;padding:20px;font-size:13px">Failed to load</div>';
    }
  }).catch(function(){
    document.getElementById('allContentList').innerHTML = '<div style="text-align:center;color:#f87171;padding:20px;font-size:13px">Network error</div>';
  });
}

function filterAllContent(q){
  currentSearch = q.toLowerCase().trim();
  renderAllContent();
}

function renderAllContent(){
  var list = document.getElementById('allContentList');
  var filtered = allContentData;
  
  // Filter by type
  if (currentFilter !== 'all') {
    filtered = filtered.filter(function(x){ return x.type === currentFilter; });
  }
  // Filter by search
  if (currentSearch) {
    filtered = filtered.filter(function(x){
      return (x.title || '').toLowerCase().indexOf(currentSearch) !== -1
          || (x.chapterName || '').toLowerCase().indexOf(currentSearch) !== -1
          || (x.batchName || '').toLowerCase().indexOf(currentSearch) !== -1
          || (x.subjectName || '').toLowerCase().indexOf(currentSearch) !== -1;
    });
  }
  
  if (filtered.length === 0) {
    list.innerHTML = '<div style="text-align:center;color:#475569;padding:40px 20px;font-size:13px"><i class="ph-bold ph-folder-open" style="font-size:44px;opacity:.25;display:block;margin-bottom:12px"></i>' + (currentSearch ? 'No results found' : 'No content yet') + '</div>';
    return;
  }
  
  // Group by chapter
  var groups = {};
  filtered.forEach(function(it){
    var key = it.chapterId;
    if (!groups[key]) groups[key] = { chapterName: it.chapterName, batchName: it.batchName, subjectName: it.subjectName, items: [] };
    groups[key].items.push(it);
  });
  
  var icons = {
    note: ['ph-file-pdf', 'i-note', 'b-note', 'NOTE'],
    dpp: ['ph-note-pencil', 'i-dpp', 'b-dpp', 'DPP'],
    video: ['ph-play-circle', 'i-video', 'b-video', 'VIDEO'],
    link: ['ph-link', 'i-link', 'b-link', 'LINK']
  };
  
  var html = '';
  var chapterIndex = 0;
  Object.keys(groups).forEach(function(chId){
    var g = groups[chId];
    chapterIndex++;
    html += '<div class="chapter-group">';
    html += '<div class="chapter-head">';
    html += '<div class="ch-num">' + chapterIndex + '</div>';
    html += '<div class="ch-name">' + esc(g.chapterName) + '</div>';
    html += '<div class="ch-count">' + g.items.length + ' items</div>';
    html += '<button class="ch-del" onclick="deleteChapterGroup(\'' + chId + '\', \'' + esc(g.chapterName) + '\')" title="Delete whole chapter"><i class="ph-bold ph-trash"></i></button>';
    html += '</div>';
    
    html += '<div style="font-size:10.5px;color:#64748b;padding:0 4px 8px;font-weight:700">' + esc(g.batchName) + ' › ' + esc(g.subjectName) + '</div>';
    
    g.items.forEach(function(it){
      var ic = icons[it.type] || icons.note;
      var thumbHtml = '';
      if (it.type === 'video' && it.thumbnail) {
        thumbHtml = '<img src="' + (/^https?:\/\//i.test(String(it.thumbnail)) ? it.thumbnail : 'uploads/thumbnails/' + it.thumbnail) + '"/>';
      } else if (it.type === 'video' && it.video_url) {
        var m = it.video_url.match(/embed\/([a-zA-Z0-9_-]+)/);
        if (m) thumbHtml = '<img src="https://img.youtube.com/vi/' + m[1] + '/mqdefault.jpg"/>';
      }
      if (!thumbHtml) thumbHtml = '<i class="ph-bold ' + ic[0] + '"></i>';
      
      var viewUrl = it.type === 'video' ? '../watch.php?id=' + it.id : (it.type === 'note' || it.type === 'dpp' ? '../view.php?id=' + it.id : (it.external_url || '#'));
      
      html += '<div class="content-item">';
      html += '<div class="ci-icon ' + ic[1] + '">' + thumbHtml + '</div>';
      html += '<div class="ci-info">';
      html += '<div class="ci-title"><span class="ci-badge ' + ic[2] + '">' + ic[3] + '</span>' + esc(it.title) + '</div>';
      html += '<div class="ci-path">' + esc(it.batchName) + ' › ' + esc(it.subjectName) + ' › ' + esc(it.chapterName) + '</div>';
      html += '</div>';
      html += '<div class="ci-actions">';
      html += '<button class="ci-btn edit" onclick="openEditItem(\'' + it.id + '\')" title="Edit"><i class="ph-bold ph-pencil-simple"></i></button>';
      html += '<a href="' + viewUrl + '" target="_blank" class="ci-btn view" title="View"><i class="ph-bold ph-eye"></i></a>';
      html += '<button class="ci-btn del" onclick="deleteContentItem(\'' + it.id + '\')" title="Delete"><i class="ph-bold ph-trash"></i></button>';
      html += '</div>';
      html += '</div>';
    });
    html += '</div>';
  });
  
  list.innerHTML = html;
}

function deleteContentItem(id){
  if (!confirm('Delete this content permanently?')) return;
  var fd = new FormData();
  fd.append('action', 'delete_content_item');
  fd.append('id', id);
  fetch('quick_api.php', { method: 'POST', body: fd }).then(function(r){ return r.json(); }).then(function(d){
    if (d.success) {
      showAlert('ok', 'Content deleted');
      fetchAllContent();
      
    } else showAlert('err', d.error || 'Failed');
  });
}

function deleteChapterGroup(chId, chName){
  if (!confirm('Delete chapter "' + chName + '" and all its content? This cannot be undone.')) return;
  var fd = new FormData();
  fd.append('action', 'delete_chapter_full');
  fd.append('id', chId);
  fetch('quick_api.php', { method: 'POST', body: fd }).then(function(r){ return r.json(); }).then(function(d){
    if (d.success) {
      showAlert('ok', 'Chapter and its content deleted');
      fetchAllContent();
    } else showAlert('err', d.error || 'Failed');
  });
}

// Init
loadBatches();

fetchAllContent();

// ============ EDIT CONTENT ============
var editingItem = null;

function openEditItem(id){
  // Fetch item data
  fetch('quick_api.php?action=get_content&id=' + encodeURIComponent(id))
    .then(function(r){ return r.json(); })
    .then(function(d){
      if (!d.success) { showAlert('err', 'Could not load item'); return; }
      editingItem = d.item;
      
      document.getElementById('editItemId').value = d.item.id;
      document.getElementById('editItemTitle').value = d.item.title || '';
      document.getElementById('editItemVideoUrl').value = d.item.video_url || '';
      document.getElementById('editItemExternalUrl').value = d.item.external_url || '';
      
      // Show/hide fields by type
      var t = d.item.type || 'note';
      document.getElementById('editVideoField').style.display = (t === 'video') ? 'block' : 'none';
      document.getElementById('editLinkField').style.display = (t === 'link') ? 'block' : 'none';
      document.getElementById('editThumbField').style.display = (t === 'video') ? 'block' : 'none';
      document.getElementById('editFileField').style.display = (t === 'note' || t === 'dpp') ? 'block' : 'none';
      
      // Thumbnail preview
      var thumbHtml = '';
      if (t === 'video') {
        var thumbSrc = '';
        if (d.item.thumbnail) {
          thumbSrc = /^https?:\/\//i.test(String(d.item.thumbnail)) ? d.item.thumbnail : 'uploads/thumbnails/' + d.item.thumbnail;
        } else if (d.item.video_url) {
          var m = d.item.video_url.match(/embed\/([a-zA-Z0-9_-]+)/);
          if (m) thumbSrc = 'https://img.youtube.com/vi/' + m[1] + '/mqdefault.jpg';
        }
        if (thumbSrc) {
          thumbHtml = '<div style="display:flex;align-items:center;gap:10px;padding:10px;background:rgba(16,185,129,.06);border:1px solid rgba(16,185,129,.2);border-radius:10px"><img src="' + thumbSrc + '" style="width:80px;height:45px;object-fit:cover;border-radius:6px"/><div style="flex:1;font-size:11px;color:#34d399;font-weight:700">Current thumbnail</div><button type="button" onclick="removeEditThumb()" style="background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);color:#f87171;width:30px;height:30px;border-radius:8px;cursor:pointer;padding:0"><i class="ph-bold ph-x"></i></button></div>';
        } else {
          thumbHtml = '<div style="font-size:11px;color:#64748b;padding:8px;text-align:center">No thumbnail</div>';
        }
      }
      document.getElementById('editThumbCurrent').innerHTML = thumbHtml;
      document.getElementById('editThumbPreview').style.display = 'none';
      document.getElementById('editThumbInput').value = '';
      
      // File preview
      if (t === 'note' || t === 'dpp') {
        var fileText = d.item.file ? 'Current: ' + d.item.file : 'No file';
        document.getElementById('editFileCurrent').textContent = fileText;
        document.getElementById('editFileInput').value = '';
      }
      
      document.getElementById('editContentModal').classList.add('show');
    });
}

function closeEditItem(){
  document.getElementById('editContentModal').classList.remove('show');
  editingItem = null;
}

function previewEditThumb(input){
  if (!input.files || !input.files[0]) return;
  var f = input.files[0];
  if (f.size > 3 * 1024 * 1024) { showAlert('err', 'Thumbnail too large (max 3MB)'); input.value=''; return; }
  var r = new FileReader();
  r.onload = function(e){
    document.getElementById('editThumbImg').src = e.target.result;
    document.getElementById('editThumbPreview').style.display = 'block';
  };
  r.readAsDataURL(f);
}

function removeEditThumb(){
  if (!editingItem) return;
  if (!confirm('Remove thumbnail?')) return;
  var fd = new FormData();
  fd.append('action', 'remove_thumbnail');
  fd.append('id', editingItem.id);
  fetch('quick_api.php', { method: 'POST', body: fd }).then(function(r){ return r.json(); }).then(function(d){
    if (d.success) {
      document.getElementById('editThumbCurrent').innerHTML = '<div style="font-size:11px;color:#64748b;padding:8px;text-align:center">No thumbnail</div>';
      editingItem.thumbnail = '';
      showAlert('ok', 'Thumbnail removed');
    }
  });
}

function saveEditItem(){
  if (!editingItem) return;
  var fd = new FormData();
  fd.append('action', 'update_content');
  fd.append('id', editingItem.id);
  fd.append('title', document.getElementById('editItemTitle').value);
  if (editingItem.type === 'video') {
    fd.append('video_url', document.getElementById('editItemVideoUrl').value);
  }
  if (editingItem.type === 'link') {
    fd.append('external_url', document.getElementById('editItemExternalUrl').value);
  }
  var thumbInput = document.getElementById('editThumbInput');
  if (thumbInput && thumbInput.files[0]) fd.append('thumbnail', thumbInput.files[0]);
  var fileInput = document.getElementById('editFileInput');
  if (fileInput && fileInput.files[0]) fd.append('file', fileInput.files[0]);
  
  var btns = document.querySelectorAll('#editContentModal .btn-g');
  btns.forEach(function(b){ b.disabled = true; b.innerHTML = 'Saving...'; });
  
  fetch('quick_api.php', { method: 'POST', body: fd }).then(function(r){ return r.json(); }).then(function(d){
    btns.forEach(function(b){ b.disabled = false; b.innerHTML = '<i class="ph-bold ph-check"></i> Save Changes'; });
    if (d.success) {
      closeEditItem();
      showAlert('ok', 'Content updated');
      fetchAllContent();
    } else {
      showAlert('err', d.error || 'Failed to save');
    }
  }).catch(function(){
    btns.forEach(function(b){ b.disabled = false; b.innerHTML = '<i class="ph-bold ph-check"></i> Save Changes'; });
    showAlert('err', 'Network error');
  });
}

</script>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>
</body></html>
