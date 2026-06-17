<?php
/**
 * admin.php — لوحة تحكم المسؤول
 * تعمل بالكامل بدون اتصال إنترنت
 */
declare(strict_types=1);
session_start();

define('ADMIN_PASS', 'admin123'); // ← غيّر كلمة المرور

if (isset($_POST['do_login'])) {
    if ($_POST['pass'] === ADMIN_PASS) {
        $_SESSION['em_admin'] = true;
        header('Location: admin.php'); exit;
    }
    $loginErr = 'كلمة المرور غير صحيحة';
}
if (isset($_POST['do_logout'])) {
    session_destroy(); header('Location: admin.php'); exit;
}

$auth = !empty($_SESSION['em_admin']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>لوحة التحكم — نظام بروتوكولات الطوارئ</title>
<link rel="stylesheet" href="assets/style.css">
<style>
/* ── Layout ── */
body { display:flex; }
.sidebar {
  width:240px; min-height:100vh; flex-shrink:0;
  background:var(--bg2); border-left:1px solid var(--border);
  display:flex; flex-direction:column;
  position:sticky; top:0; height:100vh; overflow-y:auto;
}
.sidebar-brand {
  padding:1.1rem 1rem; background:var(--accent);
  display:flex; align-items:center; gap:.6rem;
}
.sidebar-brand h4 { font-size:.9rem; font-weight:900; color:#fff; }
.sidebar-brand .icon { font-size:1.4rem; }
.sidebar nav { padding:.7rem 0; flex:1; }
.nav-title {
  padding:.35rem 1rem; font-size:.67rem; font-weight:700;
  letter-spacing:2px; color:var(--muted); text-transform:uppercase;
}
.nav-link {
  display:flex; align-items:center; gap:.6rem;
  padding:.62rem 1rem; color:rgba(255,255,255,.65);
  text-decoration:none; font-size:.87rem; cursor:pointer;
  border-right:3px solid transparent; transition:all .15s;
  background:none; border-top:none; border-bottom:none; border-left:none;
  width:100%; text-align:right; font-family:var(--font);
}
.nav-link:hover, .nav-link.active {
  background:rgba(230,57,80,.1); color:#fff;
  border-right-color:var(--accent);
}
.nav-link .icon { width:18px; text-align:center; flex-shrink:0; }
.main {
  flex:1; min-width:0; display:flex;
  flex-direction:column; min-height:100vh;
}
.topbar {
  background:var(--bg2); border-bottom:1px solid var(--border);
  padding:.75rem 1.25rem; display:flex; align-items:center;
  justify-content:space-between; position:sticky; top:0; z-index:50;
}
.topbar .bc { font-size:.82rem; color:var(--muted); }
.topbar .bc span { color:var(--text); font-weight:600; }
.page-body { padding:1.25rem; flex:1; }

/* ── Sections ── */
.section { display:none; }
.section.active { display:block; }

/* ── Page Header ── */
.page-hd {
  display:flex; align-items:center; justify-content:space-between;
  margin-bottom:1.1rem; flex-wrap:wrap; gap:.75rem;
}
.page-hd h3 { font-size:1.15rem; font-weight:900; }
.page-hd p  { color:var(--muted); font-size:.83rem; margin-top:.2rem; }

/* ── Protocol card (list) ── */
.proto-grid {
  display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr));
  gap:1rem;
}
.proto-card {
  background:var(--bg2); border:1px solid var(--border);
  border-radius:var(--radius); padding:1rem;
  display:flex; flex-direction:column; gap:.7rem;
  transition:border-color .2s;
}
.proto-card:hover { border-color:var(--border2); }
.proto-card-top { display:flex; align-items:flex-start; gap:.75rem; }
.proto-color-bar { width:4px; border-radius:3px; min-height:44px; flex-shrink:0; }
.proto-code  { font-size:.72rem; color:var(--muted); font-family:monospace; }
.proto-title { font-weight:700; font-size:.93rem; }
.proto-actions { display:flex; gap:.4rem; flex-wrap:wrap; }

/* ── Question Builder ── */
.builder-header {
  background:var(--bg2); border:1px solid var(--border);
  border-radius:var(--radius); padding:.9rem 1.1rem; margin-bottom:1rem;
  display:flex; align-items:center; gap:.85rem; flex-wrap:wrap;
}
.q-card {
  background:var(--bg2); border:1px solid var(--border);
  border-radius:var(--radius); margin-bottom:.75rem;
  overflow:hidden; transition:border-color .2s;
}
.q-card.is-entry { border-color:var(--accent2); }
.q-card-head {
  padding:.8rem 1rem; display:flex; align-items:flex-start;
  gap:.75rem; cursor:pointer;
}
.q-card-head:hover { background:rgba(255,255,255,.02); }
.q-order {
  background:rgba(255,255,255,.07); border-radius:6px;
  padding:.15rem .45rem; font-size:.75rem; font-family:monospace;
  flex-shrink:0; color:var(--muted);
}
.q-meta { flex:1; min-width:0; }
.q-text { font-weight:600; font-size:.9rem; }
.q-sub  { font-size:.75rem; color:var(--muted); margin-top:.2rem; }
.q-badges { display:flex; gap:.35rem; flex-wrap:wrap; margin-top:.3rem; }
.badge {
  font-size:.68rem; font-weight:700; padding:.15rem .45rem;
  border-radius:5px; border:1px solid;
}
.badge-mand  { color:var(--echo);    background:rgba(255,23,68,.1); border-color:rgba(255,23,68,.3); }
.badge-opt   { color:var(--muted);   background:rgba(255,255,255,.05); border-color:var(--border); }
.badge-entry { color:var(--accent2); background:rgba(245,158,11,.1);  border-color:rgba(245,158,11,.3); }
.q-card-actions { display:flex; gap:.35rem; flex-shrink:0; }
.q-card-body {
  border-top:1px solid var(--border); padding:.9rem 1rem;
}

/* ── Option items ── */
.opt-item {
  background:rgba(255,255,255,.03); border:1px solid var(--border);
  border-radius:8px; padding:.65rem .9rem; margin-bottom:.5rem;
  display:flex; align-items:flex-start; gap:.75rem;
}
.opt-stripe {
  width:3px; min-height:36px; border-radius:3px; flex-shrink:0;
}
.opt-info { flex:1; min-width:0; }
.opt-text { font-weight:600; font-size:.87rem; }
.opt-detail { font-size:.75rem; color:var(--muted); margin-top:.2rem; }
.opt-actions { display:flex; gap:.3rem; flex-shrink:0; }
.act-badge {
  font-size:.68rem; font-weight:700; padding:.12rem .4rem;
  border-radius:4px; border:1px solid;
}
.act-next { color:#82b1ff; background:rgba(68,138,255,.1); border-color:rgba(68,138,255,.3); }
.act-end  { color:var(--echo);  background:rgba(255,23,68,.1);  border-color:rgba(255,23,68,.3); }

/* ── Login ── */
.login-wrap {
  min-height:100vh; display:flex; align-items:center;
  justify-content:center;
  background:radial-gradient(ellipse at 50% 0%, #1a0a10 0%, var(--bg) 60%);
}
.login-box {
  background:var(--bg2); border:1px solid var(--border2);
  border-radius:14px; padding:2.2rem; width:340px;
  text-align:center;
}
.login-box .big-icon { font-size:2.8rem; margin-bottom:1rem; }
.login-box h3 { font-weight:900; margin-bottom:.3rem; }
.login-box p { color:var(--muted); font-size:.85rem; margin-bottom:1.8rem; }
.err-box {
  background:rgba(255,23,68,.08); border:1px solid rgba(255,23,68,.25);
  border-radius:8px; padding:.65rem .9rem; margin-bottom:1rem;
  color:var(--echo); font-size:.84rem; text-align:right;
}

/* Responsive */
@media(max-width:768px){
  .sidebar { width:200px; }
}
</style>
</head>
<body>
<?php if (!$auth): ?>
<!-- ══════════════════════ LOGIN ══════════════════════ -->
<div class="login-wrap" style="width:100%">
  <div class="login-box fade-up">
    <div class="big-icon">🛡️</div>
    <h3>لوحة التحكم</h3>
    <p>نظام بروتوكولات الطوارئ الديناميكي</p>
    <?php if (!empty($loginErr)): ?>
      <div class="err-box">⚠️ <?= htmlspecialchars($loginErr) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group" style="text-align:right">
        <label class="lbl">كلمة المرور</label>
        <input type="password" name="pass" class="inp" placeholder="أدخل كلمة المرور" autofocus>
      </div>
      <button type="submit" name="do_login" class="btn btn-primary w100 btn-lg" style="margin-top:.5rem">
        دخول
      </button>
    </form>
  </div>
</div>

<?php else: ?>
<!-- ══════════════════════ ADMIN ══════════════════════ -->

<!-- Sidebar -->
<div class="sidebar">
  <div class="sidebar-brand">
    <span class="icon">❤️</span>
    <h4>نظام الطوارئ</h4>
  </div>
  <nav>
    <div class="nav-title">إدارة</div>
    <button class="nav-link active" id="nl-protocols" onclick="showSection('protocols')">
      <span class="icon">📋</span> البروتوكولات
    </button>
    <button class="nav-link" id="nl-builder" onclick="showSection('builder')">
      <span class="icon">🔀</span> منشئ الأسئلة
    </button>

    <div class="nav-title" style="margin-top:1rem">أدوات</div>
    <a class="nav-link" href="index.php" target="_blank">
      <span class="icon">🖥️</span> واجهة المشغّل
    </a>

    <div style="padding:.75rem 1rem; margin-top:auto">
      <form method="POST">
        <button type="submit" name="do_logout" class="btn btn-ghost w100">
          خروج
        </button>
      </form>
    </div>
  </nav>
</div>

<!-- Main -->
<div class="main">
  <!-- Topbar -->
  <div class="topbar">
    <div class="bc">
      <span id="bc-section">البروتوكولات</span>
    </div>
    <div class="flex items-center gap1">
      <span class="dot"></span>
      <span class="small muted" id="clock"></span>
    </div>
  </div>

  <!-- Page Body -->
  <div class="page-body">

    <!-- ══════════════ PROTOCOLS ══════════════ -->
    <div id="sec-protocols" class="section active">
      <div class="page-hd">
        <div>
          <h3>البروتوكولات</h3>
          <p>إدارة بروتوكولات الطوارئ</p>
        </div>
        <button class="btn btn-primary" onclick="openProtoModal()">
          + بروتوكول جديد
        </button>
      </div>
      <div id="proto-grid" class="proto-grid">
        <div class="muted small">جارٍ التحميل...</div>
      </div>
    </div>

    <!-- ══════════════ BUILDER ══════════════ -->
    <div id="sec-builder" class="section">
      <div class="page-hd">
        <div>
          <h3>منشئ البروتوكول</h3>
          <p>إضافة الأسئلة وربطها بمنطق التفريع</p>
        </div>
      </div>

      <div class="builder-header">
        <div style="flex:1;min-width:200px">
          <label class="lbl">اختر البروتوكول</label>
          <select class="sel" id="builder-proto-sel" onchange="onBuilderProtoChange()">
            <option value="">— اختر —</option>
          </select>
        </div>
        <button class="btn btn-primary" id="btn-add-q" onclick="openQModal()" disabled>
          + إضافة سؤال
        </button>
      </div>

      <div id="builder-body">
        <div class="muted small" style="text-align:center;padding:2rem">
          اختر بروتوكولاً للبدء
        </div>
      </div>
    </div>

  </div><!-- /page-body -->
</div><!-- /main -->

<!-- ═══════════════════════ MODALS ═══════════════════════ -->

<!-- Protocol Modal -->
<div class="modal-bg hidden" id="modal-proto">
  <div class="modal-box">
    <div class="modal-head">
      <h5 id="modal-proto-title">بروتوكول جديد</h5>
      <button class="modal-close" onclick="closeModal('modal-proto')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="pm-id">
      <div class="grid2">
        <div class="form-group">
          <label class="lbl">الرمز *</label>
          <input type="text" id="pm-code" class="inp" placeholder="CARD-01">
        </div>
        <div class="form-group" style="grid-column:span 1">
          <label class="lbl">العنوان *</label>
          <input type="text" id="pm-title" class="inp" placeholder="ألم الصدر / توقف القلب">
        </div>
      </div>
      <div class="form-group">
        <label class="lbl">الوصف</label>
        <textarea id="pm-desc" class="textarea" rows="2" placeholder="وصف مختصر..."></textarea>
      </div>
      <div class="grid3">
        <div class="form-group">
          <label class="lbl">الأولوية</label>
          <select id="pm-priority" class="sel">
            <option value="Echo">🔴 Echo</option>
            <option value="Delta" selected>🟠 Delta</option>
            <option value="Charlie">🟡 Charlie</option>
            <option value="Bravo">🟢 Bravo</option>
            <option value="Alpha">🔵 Alpha</option>
          </select>
        </div>
        <div class="form-group">
          <label class="lbl">الفئة العمرية</label>
          <select id="pm-ag" class="sel">
            <option value="all">الكل</option>
            <option value="adult">بالغون</option>
            <option value="pediatric">أطفال</option>
          </select>
        </div>
        <div class="form-group">
          <label class="lbl">اللون</label>
          <input type="color" id="pm-color" value="#E53E3E"
            style="width:100%;height:40px;border:1px solid var(--border2);border-radius:var(--radius);background:transparent;cursor:pointer;padding:3px;">
        </div>
      </div>
      <div class="grid2">
        <div class="form-group">
          <label class="lbl">ترتيب العرض</label>
          <input type="number" id="pm-sort" class="inp" value="0" min="0">
        </div>
        <div class="form-group" style="padding-top:1.5rem">
          <label class="check-row">
            <input type="checkbox" id="pm-active" checked>
            <span>مفعّل</span>
          </label>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('modal-proto')">إلغاء</button>
      <button class="btn btn-primary" onclick="saveProtocol()">💾 حفظ البروتوكول</button>
    </div>
  </div>
</div>

<!-- Question Modal -->
<div class="modal-bg hidden" id="modal-q">
  <div class="modal-box">
    <div class="modal-head">
      <h5 id="modal-q-title">إضافة سؤال</h5>
      <button class="modal-close" onclick="closeModal('modal-q')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="qm-id">
      <input type="hidden" id="qm-pid">
      <div class="form-group">
        <label class="lbl">نص السؤال *</label>
        <textarea id="qm-text" class="textarea" rows="3" placeholder="اكتب السؤال كاملاً..."></textarea>
      </div>
      <div class="form-group">
        <label class="lbl">نص مساعد (اختياري)</label>
        <textarea id="qm-helper" class="textarea" rows="2" placeholder="تلميح للمشغّل..."></textarea>
      </div>
      <div class="grid3">
        <div class="form-group">
          <label class="lbl">ترتيب الخطوة</label>
          <input type="number" id="qm-order" class="inp" value="1" min="1">
        </div>
        <div class="form-group" style="padding-top:1.5rem">
          <label class="check-row">
            <input type="checkbox" id="qm-mand" checked>
            <span>إلزامي</span>
          </label>
        </div>
        <div class="form-group" style="padding-top:1.5rem">
          <label class="check-row">
            <input type="checkbox" id="qm-entry">
            <span>⭐ نقطة دخول</span>
          </label>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('modal-q')">إلغاء</button>
      <button class="btn btn-primary" onclick="saveQuestion()">💾 حفظ السؤال</button>
    </div>
  </div>
</div>

<!-- Option Modal -->
<div class="modal-bg hidden" id="modal-opt">
  <div class="modal-box" style="max-width:740px">
    <div class="modal-head">
      <h5 id="modal-opt-title">إضافة خيار / تفريع</h5>
      <button class="modal-close" onclick="closeModal('modal-opt')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="om-id">
      <input type="hidden" id="om-qid">
      <input type="hidden" id="om-pid">

      <div class="form-group">
        <label class="lbl">نص الخيار / الإجابة *</label>
        <input type="text" id="om-text" class="inp" placeholder="مثال: نعم، يتنفس بشكل طبيعي">
      </div>

      <div class="grid2">
        <div class="form-group">
          <label class="lbl">نوع الإجراء *</label>
          <select id="om-action" class="sel" onchange="toggleOptFields()">
            <option value="next_question">➡️ انتقال لسؤال تالٍ</option>
            <option value="end_with_instruction">🔴 إنهاء بتعليمات</option>
          </select>
        </div>
        <div class="form-group">
          <label class="lbl">تغيير مستوى الأولوية</label>
          <select id="om-priority" class="sel">
            <option value="">— بدون تغيير —</option>
            <option value="Echo">🔴 Echo</option>
            <option value="Delta">🟠 Delta</option>
            <option value="Charlie">🟡 Charlie</option>
            <option value="Bravo">🟢 Bravo</option>
            <option value="Alpha">🔵 Alpha</option>
          </select>
        </div>
      </div>

      <!-- حقل السؤال التالي -->
      <div class="form-group" id="field-nq">
        <label class="lbl">السؤال التالي *</label>
        <select id="om-nqid" class="sel">
          <option value="">— اختر السؤال —</option>
        </select>
      </div>

      <!-- حقل التعليمات -->
      <div class="form-group hidden" id="field-ins">
        <label class="lbl">تعليمات الإنهاء (DLS) *</label>
        <textarea id="om-ins" class="textarea" rows="5"
          placeholder="التعليمات الطبية الكاملة التي ستظهر للمشغّل..."></textarea>
        <div class="small muted mt1">⚠️ ستظهر هذه التعليمات بشكل بارز باللون الأحمر</div>
      </div>

      <div class="grid2">
        <div class="form-group">
          <label class="lbl">نص التأثير (للتقرير)</label>
          <input type="text" id="om-impact" class="inp"
            placeholder="مثال: المريض يتنفس طبيعياً">
        </div>
        <div class="form-group">
          <label class="lbl">ترتيب العرض</label>
          <input type="number" id="om-sort" class="inp" value="0" min="0">
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('modal-opt')">إلغاء</button>
      <button class="btn btn-primary" onclick="saveOption()">💾 حفظ الخيار</button>
    </div>
  </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal-bg hidden" id="modal-confirm">
  <div class="modal-box" style="max-width:420px">
    <div class="modal-head">
      <h5>تأكيد الحذف</h5>
      <button class="modal-close" onclick="closeModal('modal-confirm')">✕</button>
    </div>
    <div class="modal-body">
      <p id="confirm-msg" style="font-size:.93rem;line-height:1.6"></p>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('modal-confirm')">إلغاء</button>
      <button class="btn btn-danger" id="confirm-btn">حذف</button>
    </div>
  </div>
</div>

<!-- Toast + Loading -->
<div id="toast"></div>
<div id="loading" class="hidden"><div class="spinner"></div></div>

<script src="assets/app.js"></script>
<script>
'use strict';

// ════════════════════════════════════════════════════════════
// حالة التطبيق
// ════════════════════════════════════════════════════════════
const S = {
  protocols:   [],
  questions:   [],
  qMap:        {},   // id → question (للـ dropdowns)
  currentPid:  null, // البروتوكول المختار في Builder
};

startClock('clock');

// ════════════════════════════════════════════════════════════
// التنقل بين الأقسام
// ════════════════════════════════════════════════════════════
function showSection(name) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));

  const sec = document.getElementById('sec-' + name);
  const nav = document.getElementById('nl-' + name);
  if (sec) sec.classList.add('active');
  if (nav) nav.classList.add('active');

  const titles = { protocols:'البروتوكولات', builder:'منشئ البروتوكول' };
  setText('bc-section', titles[name] || name);
}

// ════════════════════════════════════════════════════════════
// PROTOCOLS
// ════════════════════════════════════════════════════════════
async function loadProtocols() {
  const list = await api('GET','get_protocols',{ active_only:0 });
  if (!list) return;
  S.protocols = list;
  fillProtoSelects(list);
  renderProtoGrid(list);
}

function fillProtoSelects(list) {
  const opts = '<option value="">— اختر —</option>' +
    list.map(p => `<option value="${p.id}">${esc(p.code)} — ${esc(p.title)}</option>`).join('');
  ['builder-proto-sel'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    const cur = el.value;
    el.innerHTML = opts;
    if (cur) el.value = cur;
  });
}

function renderProtoGrid(list) {
  const wrap = document.getElementById('proto-grid');
  if (!list.length) {
    wrap.innerHTML = '<div class="muted small" style="grid-column:1/-1;text-align:center;padding:2rem">لا توجد بروتوكولات بعد — أضف أول بروتوكول!</div>';
    return;
  }
  wrap.innerHTML = list.map(p => `
    <div class="proto-card" id="pcard-${p.id}">
      <div class="proto-card-top">
        <div class="proto-color-bar" style="background:${esc(p.color_code)};box-shadow:0 0 8px ${esc(p.color_code)}55"></div>
        <div style="flex:1;min-width:0">
          <div class="proto-code">${esc(p.code)}</div>
          <div class="proto-title">${esc(p.title)}</div>
          <div class="flex items-center gap05 mt1" style="flex-wrap:wrap">
            <span class="pbadge pb-${esc(p.priority_level)}">${esc(p.priority_level)}</span>
            <span class="badge badge-opt">${ageLabel(p.age_group)}</span>
            ${p.is_active
              ? '<span class="badge" style="color:var(--success);background:rgba(16,232,168,.1);border-color:rgba(16,232,168,.3)">● مفعّل</span>'
              : '<span class="badge badge-opt">○ موقوف</span>'}
          </div>
          ${p.description ? `<div class="small muted mt1">${esc(p.description.substring(0,80))}${p.description.length>80?'...':''}</div>` : ''}
        </div>
      </div>
      <div class="proto-actions">
        <button class="btn btn-ghost btn-sm" onclick="openProtoModal(${p.id})">✏️ تعديل</button>
        <button class="btn btn-ghost btn-sm" onclick="openBuilderForProto(${p.id})">🔀 المنشئ</button>
        <button class="btn btn-danger btn-sm" onclick="confirmDelete('protocol',${p.id},'${esc(p.title)}')">🗑️</button>
      </div>
    </div>
  `).join('');
}

// ── فتح نافذة البروتوكول ──────────────────────────────────
function openProtoModal(id = null) {
  if (id) {
    const p = S.protocols.find(x => x.id == id);
    if (!p) return;
    setText('modal-proto-title','تعديل البروتوكول');
    setVal('pm-id',    p.id);
    setVal('pm-code',  p.code);
    setVal('pm-title', p.title);
    setVal('pm-desc',  p.description || '');
    setVal('pm-priority', p.priority_level);
    setVal('pm-ag',    p.age_group);
    setVal('pm-color', p.color_code);
    setVal('pm-sort',  p.sort_order);
    setVal('pm-active', p.is_active);
  } else {
    setText('modal-proto-title','بروتوكول جديد');
    setVal('pm-id',''); setVal('pm-code',''); setVal('pm-title','');
    setVal('pm-desc',''); setVal('pm-priority','Delta');
    setVal('pm-ag','all'); setVal('pm-color','#E53E3E');
    setVal('pm-sort',0); setVal('pm-active',true);
  }
  openModal('modal-proto');
}

async function saveProtocol() {
  const code  = getVal('pm-code').trim();
  const title = getVal('pm-title').trim();
  if (!code || !title) { showToast('الرمز والعنوان إلزاميان','err'); return; }

  const data = {
    id:             getVal('pm-id') || null,
    code, title,
    description:    getVal('pm-desc').trim(),
    priority_level: getVal('pm-priority'),
    age_group:      getVal('pm-ag'),
    color_code:     getVal('pm-color'),
    sort_order:     parseInt(getVal('pm-sort')) || 0,
    is_active:      getVal('pm-active') ? 1 : 0,
  };

  const r = await api('POST','save_protocol', data);
  if (r) {
    closeModal('modal-proto');
    showToast(data.id ? 'تم التحديث ✓' : 'تم الإنشاء ✓');
    await loadProtocols();
  }
}

// ════════════════════════════════════════════════════════════
// BUILDER
// ════════════════════════════════════════════════════════════
function openBuilderForProto(pid) {
  setVal('builder-proto-sel', pid);
  showSection('builder');
  onBuilderProtoChange();
}

async function onBuilderProtoChange() {
  const pid = parseInt(getVal('builder-proto-sel')) || 0;
  document.getElementById('btn-add-q').disabled = !pid;
  S.currentPid = pid || null;

  if (!pid) {
    setHTML('builder-body','<div class="muted small" style="text-align:center;padding:2rem">اختر بروتوكولاً للبدء</div>');
    return;
  }
  await loadBuilderQuestions(pid);
}

async function loadBuilderQuestions(pid) {
  pid = pid || S.currentPid;
  if (!pid) return;
  const list = await api('GET','get_questions_list',{ protocol_id:pid });
  if (!list) return;
  S.questions = list;
  S.qMap = {};
  list.forEach(q => { S.qMap[q.id] = q; });
  renderBuilderQuestions(list);
}

function renderBuilderQuestions(list) {
  const wrap = document.getElementById('builder-body');
  if (!list.length) {
    wrap.innerHTML = `
      <div style="text-align:center;padding:3rem;border:1px dashed var(--border);border-radius:var(--radius)">
        <div style="font-size:2.5rem;margin-bottom:.75rem">❓</div>
        <div class="muted">لا توجد أسئلة بعد — أضف أول سؤال</div>
      </div>`;
    return;
  }

  wrap.innerHTML = list.map(q => `
    <div class="q-card ${q.is_entry_point ? 'is-entry' : ''}" id="qcard-${q.id}">
      <div class="q-card-head" onclick="toggleQBody(${q.id})">
        <span class="q-order">#${q.step_order}</span>
        <div class="q-meta">
          <div class="q-text">${esc(q.question_text)}</div>
          ${q.helper_text ? `<div class="q-sub">💡 ${esc(q.helper_text)}</div>` : ''}
          <div class="q-badges">
            <span class="badge ${q.is_mandatory ? 'badge-mand' : 'badge-opt'}">${q.is_mandatory ? 'إلزامي' : 'اختياري'}</span>
            ${q.is_entry_point ? '<span class="badge badge-entry">⭐ نقطة دخول</span>' : ''}
            <span class="badge badge-opt">${q.options_count} خيارات</span>
          </div>
        </div>
        <div class="q-card-actions" onclick="event.stopPropagation()">
          <button class="btn btn-warning btn-sm" title="نقطة دخول" onclick="setEntry(${q.id})">⭐</button>
          <button class="btn btn-ghost btn-sm"   title="تعديل"     onclick="openQModal(${q.id})">✏️</button>
          <button class="btn btn-primary btn-sm" title="إضافة خيار" onclick="openOptModal(${q.id})">+ خيار</button>
          <button class="btn btn-danger btn-sm"  title="حذف"       onclick="confirmDelete('question',${q.id},'السؤال')">🗑️</button>
        </div>
      </div>
      <div class="q-card-body" id="qbody-${q.id}">
        ${renderOptions(q.options, q.id)}
      </div>
    </div>
  `).join('');
}

function renderOptions(opts, qid) {
  if (!opts || !opts.length)
    return '<div class="muted small">لا توجد خيارات — أضف خيارات لهذا السؤال</div>';

  return opts.map(o => {
    const isEnd    = o.action_type === 'end_with_instruction';
    const stripe   = o.color_hint || (isEnd ? 'var(--echo)' : 'rgba(255,255,255,.15)');
    const nextText = !isEnd && o.next_question_id && S.qMap[o.next_question_id]
      ? 'ينتقل ← ' + S.qMap[o.next_question_id].question_text.substring(0,55) + '...'
      : (isEnd ? '🔴 إنهاء البروتوكول' : '—');
    return `
      <div class="opt-item">
        <div class="opt-stripe" style="background:${stripe}"></div>
        <div class="opt-info">
          <div class="opt-text">${esc(o.option_text)}</div>
          <div class="opt-detail flex items-center gap05" style="flex-wrap:wrap;margin-top:.3rem">
            <span class="act-badge ${isEnd ? 'act-end' : 'act-next'}">${isEnd ? '🔴 إنهاء' : '➡ سؤال'}</span>
            ${o.set_priority ? `<span class="pbadge pb-${esc(o.set_priority)}" style="font-size:.65rem;padding:.1rem .4rem">${esc(o.set_priority)}</span>` : ''}
            <span class="muted small">${esc(nextText)}</span>
          </div>
          ${o.impact_text ? `<div class="small" style="color:var(--accent2);margin-top:.2rem">🏷 ${esc(o.impact_text)}</div>` : ''}
        </div>
        <div class="opt-actions">
          <button class="btn btn-ghost btn-sm"  onclick="openOptModal(${qid},${o.id})">✏️</button>
          <button class="btn btn-danger btn-sm" onclick="confirmDelete('option',${o.id},'الخيار')">🗑️</button>
        </div>
      </div>`;
  }).join('');
}

function toggleQBody(qid) {
  const el = document.getElementById('qbody-' + qid);
  if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

// ── نقطة الدخول ─────────────────────────────────────────
async function setEntry(qid) {
  const r = await api('POST','set_entry_point',{ question_id:qid, protocol_id:S.currentPid });
  if (r !== null) {
    showToast('تم تعيين نقطة الدخول ⭐');
    await loadBuilderQuestions();
  }
}

// ════════════════════════════════════════════════════════════
// QUESTION MODAL
// ════════════════════════════════════════════════════════════
function openQModal(id = null) {
  if (!S.currentPid) { showToast('اختر بروتوكولاً أولاً','warn'); return; }
  if (id) {
    const q = S.questions.find(x => x.id == id);
    if (!q) return;
    setText('modal-q-title','تعديل السؤال');
    setVal('qm-id',    q.id);
    setVal('qm-pid',   S.currentPid);
    setVal('qm-text',  q.question_text);
    setVal('qm-helper',q.helper_text || '');
    setVal('qm-order', q.step_order);
    setVal('qm-mand',  q.is_mandatory);
    setVal('qm-entry', q.is_entry_point);
  } else {
    setText('modal-q-title','إضافة سؤال جديد');
    setVal('qm-id',''); setVal('qm-pid', S.currentPid);
    setVal('qm-text',''); setVal('qm-helper','');
    setVal('qm-order', S.questions.length + 1);
    setVal('qm-mand', true); setVal('qm-entry', false);
  }
  openModal('modal-q');
}

async function saveQuestion() {
  const text = getVal('qm-text').trim();
  if (!text) { showToast('نص السؤال إلزامي','err'); return; }
  const r = await api('POST','save_question',{
    id:             getVal('qm-id') || null,
    protocol_id:    parseInt(getVal('qm-pid')),
    question_text:  text,
    helper_text:    getVal('qm-helper').trim(),
    step_order:     parseInt(getVal('qm-order')) || 1,
    is_mandatory:   getVal('qm-mand') ? 1 : 0,
    is_entry_point: getVal('qm-entry') ? 1 : 0,
  });
  if (r) {
    closeModal('modal-q');
    showToast('تم حفظ السؤال ✓');
    await loadBuilderQuestions();
  }
}

// ════════════════════════════════════════════════════════════
// OPTION MODAL
// ════════════════════════════════════════════════════════════
function fillNextQDropdown(excludeId = null, selectedId = null) {
  const sel = document.getElementById('om-nqid');
  sel.innerHTML = '<option value="">— اختر السؤال التالي —</option>';
  S.questions
    .filter(q => q.id != excludeId)
    .forEach(q => {
      const opt = document.createElement('option');
      opt.value = q.id;
      opt.textContent = `[#${q.step_order}] ${q.question_text.substring(0,60)}`;
      if (selectedId && q.id == selectedId) opt.selected = true;
      sel.appendChild(opt);
    });
}

function toggleOptFields() {
  const isEnd = getVal('om-action') === 'end_with_instruction';
  document.getElementById('field-nq').classList.toggle('hidden', isEnd);
  document.getElementById('field-ins').classList.toggle('hidden', !isEnd);
}

function openOptModal(qid, optId = null) {
  setVal('om-qid', qid);
  setVal('om-pid', S.currentPid);

  if (optId) {
    const q   = S.questions.find(x => x.id == qid);
    const opt = q?.options?.find(o => o.id == optId);
    if (!opt) return;
    setText('modal-opt-title','تعديل الخيار');
    setVal('om-id',       opt.id);
    setVal('om-text',     opt.option_text);
    setVal('om-action',   opt.action_type);
    setVal('om-priority', opt.set_priority || '');
    setVal('om-ins',      opt.instruction_text || '');
    setVal('om-impact',   opt.impact_text || '');
    setVal('om-sort',     opt.sort_order || 0);
    fillNextQDropdown(qid, opt.next_question_id);
  } else {
    setText('modal-opt-title','إضافة خيار جديد');
    setVal('om-id',''); setVal('om-text','');
    setVal('om-action','next_question'); setVal('om-priority','');
    setVal('om-ins',''); setVal('om-impact',''); setVal('om-sort',0);
    fillNextQDropdown(qid);
  }
  toggleOptFields();
  openModal('modal-opt');
}

async function saveOption() {
  const text   = getVal('om-text').trim();
  const action = getVal('om-action');
  const nqid   = parseInt(getVal('om-nqid')) || null;
  const ins    = getVal('om-ins').trim();

  if (!text) { showToast('نص الخيار إلزامي','err'); return; }
  if (action === 'next_question' && !nqid)  { showToast('اختر السؤال التالي','err'); return; }
  if (action === 'end_with_instruction' && !ins) { showToast('تعليمات الإنهاء إلزامية','err'); return; }

  const r = await api('POST','save_option',{
    id:               getVal('om-id') || null,
    question_id:      parseInt(getVal('om-qid')),
    option_text:      text,
    action_type:      action,
    next_question_id: action === 'next_question' ? nqid : null,
    instruction_text: action === 'end_with_instruction' ? ins : null,
    impact_text:      getVal('om-impact').trim() || null,
    set_priority:     getVal('om-priority') || null,
    sort_order:       parseInt(getVal('om-sort')) || 0,
  });
  if (r) {
    closeModal('modal-opt');
    showToast('تم حفظ الخيار ✓');
    await loadBuilderQuestions();
  }
}

// ════════════════════════════════════════════════════════════
// CONFIRM DELETE
// ════════════════════════════════════════════════════════════
function confirmDelete(type, id, name) {
  const msgs = {
    protocol: `⚠️ سيتم حذف البروتوكول "${name}" وجميع أسئلته وخياراته نهائياً!`,
    question: `⚠️ سيتم حذف هذا السؤال وجميع خياراته نهائياً!`,
    option:   `⚠️ سيتم حذف هذا الخيار نهائياً.`,
  };
  setText('confirm-msg', msgs[type] || 'هل أنت متأكد؟');

  const btn = document.getElementById('confirm-btn');
  btn.onclick = async () => {
    closeModal('modal-confirm');
    const actions = { protocol:'delete_protocol', question:'delete_question', option:'delete_option' };
    const r = await api('POST', actions[type], { id });
    if (r !== null) {
      showToast('تم الحذف ✓');
      if (type === 'protocol') await loadProtocols();
      else await loadBuilderQuestions();
    }
  };
  openModal('modal-confirm');
}

// ════════════════════════════════════════════════════════════
// INIT
// ════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
  loadProtocols();
});
</script>
<?php endif; ?>
</body>
</html>
