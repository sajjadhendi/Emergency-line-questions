<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>شاشة المشغّل — نظام بروتوكولات الطوارئ</title>
<link rel="stylesheet" href="assets/style.css">
<style>
/* ════════════════════════════════════════════
   شاشة المشغّل — Dispatcher Screen
   ════════════════════════════════════════════ */
body { display:flex; flex-direction:column; }

/* ── Header ──────────────────────────────── */
.header {
  height:58px; background:var(--bg2);
  border-bottom:1px solid var(--border);
  display:flex; align-items:center; padding:0 1.25rem; gap:1rem;
  position:sticky; top:0; z-index:100; flex-shrink:0;
}
.hdr-brand {
  display:flex; align-items:center; gap:.5rem;
  font-weight:900; font-size:.95rem; white-space:nowrap;
}
.hdr-brand .pulse { animation:pulse 2s infinite; }
.hdr-sep { width:1px; height:26px; background:var(--border); flex-shrink:0; }
.hdr-info {
  display:flex; align-items:center; gap:1.25rem; flex:1;
  overflow:hidden; flex-wrap:wrap;
}
.hdr-chip {
  display:flex; align-items:center; gap:.35rem;
  font-size:.78rem; white-space:nowrap;
}
.hdr-chip .lbl { font-weight:700; color:var(--muted); }
.hdr-chip .val { font-weight:600; }

.pr-chip {
  padding:.22rem .7rem; border-radius:6px;
  font-size:.76rem; font-weight:900; border:1px solid;
  transition:all .35s;
}
.pr-Echo    { color:var(--echo);    background:rgba(255,23,68,.12);  border-color:rgba(255,23,68,.4); }
.pr-Delta   { color:var(--delta);   background:rgba(255,109,0,.12);  border-color:rgba(255,109,0,.4); }
.pr-Charlie { color:var(--charlie); background:rgba(255,214,0,.12);  border-color:rgba(255,214,0,.4); }
.pr-Bravo   { color:var(--bravo);   background:rgba(0,230,118,.12);  border-color:rgba(0,230,118,.4); }
.pr-Alpha   { color:var(--alpha);   background:rgba(68,138,255,.12); border-color:rgba(68,138,255,.4); }

.hdr-actions { margin-right:auto; display:flex; gap:.5rem; align-items:center; flex-shrink:0; }

/* ── Layout ──────────────────────────────── */
.layout {
  flex:1; display:grid;
  grid-template-columns:290px 1fr 270px;
  min-height:calc(100vh - 58px);
}
@media(max-width:1024px){
  .layout { grid-template-columns:240px 1fr; }
  .trail-panel { display:none; }
}
@media(max-width:700px){
  .layout { grid-template-columns:1fr; }
  .proto-panel { display:none; }
}

/* ── Protocol Panel ──────────────────────── */
.proto-panel {
  background:var(--bg2); border-left:1px solid var(--border);
  overflow-y:auto;
}
.panel-hd {
  padding:1rem; border-bottom:1px solid var(--border);
  position:sticky; top:0; background:var(--bg2); z-index:5;
}
.panel-hd h6 {
  font-size:.7rem; font-weight:700; color:var(--muted);
  letter-spacing:2px; text-transform:uppercase; margin-bottom:.6rem;
}
.filter-row { display:flex; gap:.3rem; }
.fbtn {
  flex:1; padding:.35rem .4rem; text-align:center;
  background:rgba(255,255,255,.04); border:1px solid var(--border);
  border-radius:7px; color:var(--muted); font-size:.76rem; font-weight:600;
  cursor:pointer; transition:all .15s; font-family:var(--font);
}
.fbtn.active { background:rgba(230,57,80,.12); border-color:rgba(230,57,80,.35); color:var(--accent); }

.proto-list { padding:.7rem; display:flex; flex-direction:column; gap:.4rem; }
.proto-item {
  background:var(--bg3); border:1px solid var(--border);
  border-radius:9px; padding:.75rem .85rem;
  cursor:pointer; transition:all .15s; display:flex; gap:.6rem;
}
.proto-item:hover  { border-color:var(--border2); transform:translateX(-2px); }
.proto-item.sel    { border-color:var(--accent); background:rgba(230,57,80,.07); }
.proto-cbar { width:4px; border-radius:2px; flex-shrink:0; min-height:38px; }
.proto-info .code  { font-size:.7rem; color:var(--muted); font-family:monospace; }
.proto-info .title { font-weight:700; font-size:.86rem; margin-top:.1rem; }
.proto-info .sub   { font-size:.7rem; margin-top:.25rem; }

/* ── Stage ───────────────────────────────── */
.stage {
  overflow-y:auto; display:flex; flex-direction:column;
  align-items:center; padding:1.75rem 1.25rem;
  background:var(--bg);
  background-image:
    linear-gradient(rgba(255,255,255,.015) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.015) 1px, transparent 1px);
  background-size:40px 40px;
}

/* Welcome */
.welcome {
  flex:1; display:flex; flex-direction:column;
  align-items:center; justify-content:center;
  text-align:center; max-width:440px; margin:auto;
}
.welcome .big-ico { font-size:4rem; margin-bottom:1.25rem; animation:float 3s ease-in-out infinite; }
@keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
.welcome h2 { font-size:1.3rem; font-weight:900; margin-bottom:.5rem; }
.welcome p  { color:var(--muted); font-size:.9rem; line-height:1.7; }

/* Progress */
.prog-wrap { width:100%; max-width:680px; margin-bottom:1.5rem; }
.prog-info { display:flex; justify-content:space-between; font-size:.74rem; color:var(--muted); margin-bottom:.35rem; }
.prog-track { height:3px; background:rgba(255,255,255,.06); border-radius:2px; overflow:hidden; }
.prog-fill  { height:100%; border-radius:2px;
  background:linear-gradient(90deg,var(--accent),var(--accent2));
  transition:width .5s cubic-bezier(.4,0,.2,1); }

/* Question Card */
.q-card-wrap { width:100%; max-width:680px; }

@keyframes slideIn {
  from { opacity:0; transform:translateX(28px); }
  to   { opacity:1; transform:translateX(0); }
}
.q-anim { animation:slideIn .38s cubic-bezier(.4,0,.2,1) both; }

.q-step-lbl {
  font-size:.71rem; color:var(--muted);
  letter-spacing:2px; text-transform:uppercase; margin-bottom:.5rem;
}
.q-text  { font-size:1.25rem; font-weight:800; line-height:1.55; color:#fff; margin-bottom:.75rem; }
.q-hint  {
  background:rgba(245,158,11,.06); border:1px solid rgba(245,158,11,.18);
  border-radius:8px; padding:.6rem .85rem;
  font-size:.82rem; color:var(--accent2); margin-bottom:1.25rem;
  display:flex; gap:.5rem; align-items:flex-start;
}

/* Options */
.opts-list { display:flex; flex-direction:column; gap:.65rem; }

.opt-btn {
  width:100%; background:var(--bg3); border:1px solid var(--border);
  border-radius:11px; padding:.9rem 1.1rem;
  color:var(--text); font-family:var(--font); font-size:.92rem;
  font-weight:600; text-align:right; cursor:pointer;
  transition:all .18s; display:flex; align-items:center; gap:.75rem;
  position:relative; overflow:hidden;
}
.opt-btn:hover {
  border-color:rgba(255,255,255,.22);
  transform:translateY(-2px);
  box-shadow:0 6px 20px rgba(0,0,0,.35);
}
.opt-btn.is-end { border-color:rgba(255,23,68,.2); background:rgba(255,23,68,.04); }
.opt-btn.is-end:hover { border-color:var(--echo); }
.opt-btn-stripe {
  position:absolute; right:0; top:0; bottom:0; width:3px;
  border-radius:0 11px 11px 0;
}
.opt-btn-lbl { flex:1; text-align:right; }
.opt-btn-ico { margin-right:auto; font-size:.85rem; flex-shrink:0; }

.skip-row { text-align:center; margin-top:1rem; }
.btn-skip {
  background:none; border:1px dashed rgba(255,255,255,.14);
  color:var(--muted); font-family:var(--font);
  font-size:.8rem; padding:.42rem 1.25rem; border-radius:8px;
  cursor:pointer; transition:all .15s;
}
.btn-skip:hover { border-color:rgba(255,255,255,.3); color:var(--text); }

/* ── Final Screen ────────────────────────── */
.final-wrap { width:100%; max-width:680px; animation:slideIn .4s ease both; }

.final-alert {
  background:rgba(255,23,68,.07); border:1px solid rgba(255,23,68,.3);
  border-radius:12px; padding:1.35rem; margin-bottom:1.25rem;
  position:relative; overflow:hidden;
}
.final-alert::before {
  content:''; position:absolute; top:0; right:0;
  width:4px; height:100%; background:var(--echo);
}
.final-hd { display:flex; align-items:flex-start; gap:.75rem; margin-bottom:1rem; }
.final-hd-ico { font-size:1.6rem; flex-shrink:0; animation:blink 1.4s infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.55} }
.final-title { font-size:1.05rem; font-weight:900; color:#fff; }
.final-sub   { font-size:.78rem; color:rgba(255,255,255,.45); margin-top:.15rem; }
.final-ins   {
  font-size:.93rem; line-height:2; color:#ffcdd2;
  white-space:pre-wrap; direction:rtl;
}
.final-pr-badge {
  display:inline-flex; align-items:center; gap:.45rem;
  padding:.35rem .9rem; border-radius:7px;
  font-size:.85rem; font-weight:900; margin-top:1rem;
  border:1px solid;
}

/* Summary card */
.summary-card {
  background:var(--bg2); border:1px solid var(--border);
  border-radius:11px; padding:1.1rem; margin-bottom:1rem;
}
.summary-ttl {
  font-size:.7rem; font-weight:700; color:var(--muted);
  letter-spacing:2px; text-transform:uppercase; margin-bottom:.85rem;
}
.summary-row {
  display:flex; align-items:flex-start; gap:.7rem;
  padding:.5rem 0; border-bottom:1px solid rgba(255,255,255,.04);
}
.summary-row:last-child { border-bottom:none; }
.summary-num {
  width:22px; height:22px; border-radius:50%;
  background:rgba(255,255,255,.06);
  display:flex; align-items:center; justify-content:center;
  font-size:.7rem; color:var(--muted); flex-shrink:0;
}
.summary-q { font-size:.77rem; color:var(--muted); }
.summary-a { font-size:.87rem; font-weight:700; color:var(--text); margin-top:.15rem; }
.summary-imp { font-size:.73rem; color:var(--accent2); margin-top:.15rem; }

/* Final actions */
.final-actions { display:flex; gap:.65rem; flex-wrap:wrap; margin-top:.25rem; }

/* ── Trail Panel ─────────────────────────── */
.trail-panel {
  background:var(--bg2); border-right:1px solid var(--border);
  overflow-y:auto;
}
.trail-list { padding:.75rem; }
.trail-item {
  background:var(--bg3); border:1px solid var(--border);
  border-radius:8px; padding:.65rem .8rem; margin-bottom:.45rem;
  border-right:3px solid var(--accent);
}
.trail-q { font-size:.73rem; color:var(--muted); margin-bottom:.25rem; }
.trail-a { font-size:.83rem; font-weight:700; }
.trail-imp { font-size:.71rem; color:var(--accent2); margin-top:.15rem; }

.timer-disp {
  text-align:center; padding:1rem;
  font-family:monospace; font-size:1.4rem; font-weight:700;
  color:var(--accent2);
}
.timer-disp.over2  { color:var(--echo); animation:blink .6s infinite; }
</style>
</head>
<body>

<!-- ══════════════════════════ HEADER ══════════════════════════ -->
<div class="header">
  <div class="hdr-brand">
    <span class="dot pulse"></span>
    🛡️ مركز اتصالات الطوارئ
  </div>
  <div class="hdr-sep"></div>
  <div class="hdr-info">
    <div class="hdr-chip">
      <span class="lbl">البروتوكول:</span>
      <span class="val" id="hdr-proto">لم يُحدَّد</span>
    </div>
    <div class="hdr-chip">
      <span class="lbl">الأولوية:</span>
      <span id="hdr-pr" class="pr-chip pr-Delta">Delta</span>
    </div>
    <div class="hdr-chip">
      <span class="lbl">الخطوة:</span>
      <span class="val" id="hdr-step">—</span>
    </div>
  </div>
  <div class="hdr-actions">
    <span id="hdr-timer-wrap" class="hidden hdr-chip">
      ⏱ <span id="hdr-timer" style="font-family:monospace">00:00</span>
    </span>
    <button class="btn btn-danger btn-sm" onclick="resetSession()">↺ إعادة</button>
    <a href="admin.php" class="btn btn-ghost btn-sm">⚙️ الإدارة</a>
  </div>
</div>

<!-- ══════════════════════════ LAYOUT ══════════════════════════ -->
<div class="layout">

  <!-- ─── Protocol Panel ──────────────────── -->
  <div class="proto-panel">
    <div class="panel-hd">
      <h6>اختيار البروتوكول</h6>
      <div class="filter-row">
        <button class="fbtn active" onclick="filterProtos('all',this)">الكل</button>
        <button class="fbtn" onclick="filterProtos('adult',this)">بالغ</button>
        <button class="fbtn" onclick="filterProtos('pediatric',this)">طفل</button>
      </div>
    </div>
    <div class="proto-list" id="proto-list">
      <div class="muted small" style="text-align:center;padding:1.5rem">
        ⏳ جارٍ التحميل...
      </div>
    </div>
  </div>

  <!-- ─── Stage ────────────────────────────── -->
  <div class="stage" id="stage">

    <!-- Welcome -->
    <div class="welcome" id="welcome">
      <div class="big-ico">❤️</div>
      <h2>جاهز للإرسال</h2>
      <p>اختر بروتوكول الطوارئ المناسب من القائمة لبدء التقييم الديناميكي خطوة بخطوة.</p>
      <div class="flex gap1 mt2" style="font-size:.78rem;color:var(--muted)">
        <span>🔒 آمن</span>
        <span>⚡ فوري</span>
        <span>🌐 يعمل أوف لاين</span>
      </div>
    </div>

    <!-- Active Question -->
    <div id="q-wrap" class="hidden" style="width:100%;max-width:680px">
      <!-- Progress -->
      <div class="prog-wrap">
        <div class="prog-info">
          <span id="prog-lbl">الخطوة 1</span>
          <span id="prog-pct" style="color:var(--accent)">0%</span>
        </div>
        <div class="prog-track">
          <div class="prog-fill" id="prog-fill" style="width:0%"></div>
        </div>
      </div>
      <!-- Card Slot -->
      <div id="q-slot" class="q-card-wrap"></div>
    </div>

    <!-- Final Screen -->
    <div id="final-wrap" class="hidden" style="width:100%;max-width:680px"></div>

  </div>

  <!-- ─── Trail Panel ──────────────────────── -->
  <div class="trail-panel">
    <div class="panel-hd" style="padding-bottom:.75rem">
      <h6>مسار الجلسة</h6>
      <div id="trail-timer" class="timer-disp hidden">00:00</div>
    </div>
    <div class="trail-list" id="trail-list">
      <div class="muted small" style="text-align:center;padding:1.5rem;opacity:.5">
        الإجابات ستظهر هنا
      </div>
    </div>
  </div>

</div>

<!-- Toast + Loading -->
<div id="toast"></div>
<div id="loading" class="hidden"><div class="spinner"></div></div>

<script src="assets/app.js"></script>
<script>
'use strict';

// ════════════════════════════════════════════
// حالة التطبيق
// ════════════════════════════════════════════
const DS = {
  allProtos:     [],
  currentProto:  null,
  currentQ:      null,
  sessionUUID:   null,
  stepNum:       0,
  priority:      'Delta',
  history:       [],  // {question, answer, impact, priority}
  locked:        false,
  timerSec:      0,
  timerRef:      null,
  ageFilter:     'all',
};

// ════════════════════════════════════════════
// Timer
// ════════════════════════════════════════════
function startTimer() {
  DS.timerSec = 0;
  clearInterval(DS.timerRef);
  document.getElementById('hdr-timer-wrap').classList.remove('hidden');
  document.getElementById('trail-timer').classList.remove('hidden');

  DS.timerRef = setInterval(() => {
    DS.timerSec++;
    const f = fmtTime(DS.timerSec);
    document.getElementById('hdr-timer').textContent = f;
    const tt = document.getElementById('trail-timer');
    tt.textContent = f;
    tt.className = 'timer-disp' + (DS.timerSec > 120 ? ' over2' : '');
  }, 1000);
}

function stopTimer() { clearInterval(DS.timerRef); }

function fmtTime(sec) {
  return String(Math.floor(sec/60)).padStart(2,'0') + ':' + String(sec%60).padStart(2,'0');
}

// ════════════════════════════════════════════
// Protocols
// ════════════════════════════════════════════
async function loadProtos() {
  const list = await api('GET','get_protocols',{ active_only:1 });
  if (!list) return;
  DS.allProtos = list;
  renderProtos(list);
}

function filterProtos(ag, btn) {
  document.querySelectorAll('.fbtn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  DS.ageFilter = ag;
  const filtered = ag === 'all'
    ? DS.allProtos
    : DS.allProtos.filter(p => p.age_group === ag || p.age_group === 'all');
  renderProtos(filtered);
}

function renderProtos(list) {
  const wrap = document.getElementById('proto-list');
  if (!list.length) {
    wrap.innerHTML = '<div class="muted small" style="text-align:center;padding:1.5rem">لا توجد بروتوكولات</div>';
    return;
  }
  const prColors = { Echo:'var(--echo)',Delta:'var(--delta)',Charlie:'var(--charlie)',Bravo:'var(--bravo)',Alpha:'var(--alpha)' };
  wrap.innerHTML = list.map(p => `
    <div class="proto-item ${DS.currentProto?.id==p.id?'sel':''}"
         id="pi-${p.id}" onclick="startProto(${p.id})">
      <div class="proto-cbar" style="background:${esc(p.color_code)};box-shadow:0 0 6px ${esc(p.color_code)}55"></div>
      <div class="proto-info" style="flex:1;min-width:0">
        <div class="code">${esc(p.code)}</div>
        <div class="title">${esc(p.title)}</div>
        <div class="sub flex items-center gap05" style="flex-wrap:wrap">
          <span style="color:${prColors[p.priority_level]||'#aaa'};font-weight:700">${p.priority_level}</span>
          ${p.age_group!=='all'?`<span class="muted">· ${ageLabel(p.age_group)}</span>`:''}
        </div>
      </div>
    </div>
  `).join('');
}

// ════════════════════════════════════════════
// Start Protocol
// ════════════════════════════════════════════
async function startProto(pid) {
  if (DS.locked) { showToast('أنهِ الجلسة الحالية أولاً','warn'); return; }

  const data = await api('GET','get_protocol_tree',{ protocol_id:pid });
  if (!data) { showToast('فشل تحميل البروتوكول','err'); return; }

  resetState();
  DS.currentProto = data.protocol;
  DS.sessionUUID  = genUUID();
  DS.priority     = data.protocol.priority_level;

  // تحديد البروتوكول في القائمة
  document.querySelectorAll('.proto-item').forEach(el => el.classList.remove('sel'));
  document.getElementById('pi-'+pid)?.classList.add('sel');

  // Header
  setText('hdr-proto', data.protocol.title);
  updatePrChip(DS.priority);

  // إخفاء الترحيب
  document.getElementById('welcome').classList.add('hidden');

  // بدء المؤقت
  startTimer();

  // أول سؤال
  if (data.entry_question) {
    showQuestion(data.entry_question);
  } else {
    showToast('البروتوكول لا يحتوي على أسئلة — أضف أسئلة من لوحة التحكم','warn');
  }
}

// ════════════════════════════════════════════
// Show Question
// ════════════════════════════════════════════
function showQuestion(q) {
  DS.currentQ = q;
  DS.stepNum++;

  // إظهار wrap
  const qwrap = document.getElementById('q-wrap');
  qwrap.classList.remove('hidden');

  // Progress
  const pct = Math.min(DS.stepNum * 12, 88);
  document.getElementById('prog-fill').style.width = pct + '%';
  document.getElementById('prog-lbl').textContent  = 'الخطوة ' + DS.stepNum;
  document.getElementById('prog-pct').textContent  = pct + '%';
  setText('hdr-step', 'الخطوة ' + DS.stepNum);

  const opts = q.options || [];
  const html = `
    <div class="q-card-wrap q-anim">
      <div class="q-step-lbl">
        ${esc(DS.currentProto?.title||'')} · الخطوة ${DS.stepNum}
      </div>
      <div class="q-text">${esc(q.question_text)}</div>
      ${q.helper_text
        ? `<div class="q-hint"><span>💡</span><span>${esc(q.helper_text)}</span></div>`
        : ''}
      <div class="opts-list">
        ${opts.length
          ? opts.map(o => renderOptBtn(o)).join('')
          : '<div class="muted small">لا توجد خيارات — راجع لوحة التحكم</div>'}
      </div>
      ${!q.is_mandatory
        ? `<div class="skip-row">
             <button class="btn-skip" onclick="skipQ()">
               ⏭ تخطّي هذا السؤال (اختياري)
             </button>
           </div>`
        : ''}
    </div>`;

  const slot = document.getElementById('q-slot');
  // تحريك خفيف
  slot.style.opacity = '0';
  slot.style.transform = 'translateX(18px)';
  slot.style.transition = 'all .22s ease';
  setTimeout(() => {
    slot.innerHTML = html;
    slot.style.opacity = '1';
    slot.style.transform = 'translateX(0)';
  }, 180);

  document.getElementById('stage').scrollTo({ top:0, behavior:'smooth' });
}

function renderOptBtn(o) {
  const isEnd   = o.action_type === 'end_with_instruction';
  const stripe  = o.color_hint || (isEnd ? 'var(--echo)' : 'rgba(255,255,255,.12)');
  const ico     = isEnd
    ? '<span style="color:var(--echo)">⛔</span>'
    : '<span style="color:var(--muted)">→</span>';

  // نمرر الـ option كـ JSON مُرمَّز
  const optJson = esc(JSON.stringify(o));

  return `
    <button class="opt-btn ${isEnd?'is-end':''}"
            onclick='selectOpt(${JSON.stringify(o)})'>
      <div class="opt-btn-stripe" style="background:${stripe}"></div>
      <span class="opt-btn-lbl">${esc(o.option_text)}</span>
      ${o.set_priority
        ? `<span class="pbadge pb-${esc(o.set_priority)}" style="font-size:.68rem">${esc(o.set_priority)}</span>`
        : ''}
      <span class="opt-btn-ico">${ico}</span>
    </button>`;
}

// ════════════════════════════════════════════
// Select Option — منطق التفريع
// ════════════════════════════════════════════
async function selectOpt(o) {
  if (DS.locked) return;

  // تغيير الأولوية
  if (o.set_priority) {
    DS.priority = o.set_priority;
    updatePrChip(o.set_priority);
  }

  // تسجيل في السجل
  DS.history.push({
    question: DS.currentQ.question_text,
    answer:   o.option_text,
    impact:   o.impact_text || null,
    priority: o.set_priority || null,
  });
  renderTrail();

  // ─── إنهاء البروتوكول ─────────────────────
  if (o.action_type === 'end_with_instruction') {
    endProto(o);
    return;
  }

  // ─── الانتقال للسؤال التالي ────────────────
  if (o.action_type === 'next_question' && o.next_question_id) {
    const nextQ = await api('GET','get_question',{ question_id: o.next_question_id });
    if (nextQ) {
      showQuestion(nextQ);
    } else {
      showToast('السؤال التالي غير متاح','err');
    }
  } else {
    // لا سؤال تالٍ
    endProto({ instruction_text:'اكتملت جميع أسئلة البروتوكول بنجاح.' });
  }
}

// تخطّي سؤال اختياري
async function skipQ() {
  DS.history.push({
    question: DS.currentQ?.question_text || '—',
    answer:   '— تم التخطّي —',
    impact:   null,
  });
  renderTrail();

  // ابحث عن أقرب سؤال أعلى step_order
  const pid = DS.currentProto?.id;
  if (!pid) return;
  const qlist = await api('GET','get_questions_list',{ protocol_id:pid });
  if (qlist) {
    const curOrder = DS.currentQ?.step_order || 0;
    const next = qlist
      .filter(q => q.step_order > curOrder && q.id !== DS.currentQ?.id)
      .sort((a,b) => a.step_order - b.step_order)[0];
    if (next) {
      const full = await api('GET','get_question',{ question_id:next.id });
      if (full) { showQuestion(full); return; }
    }
  }
  endProto({ instruction_text:'تمت مراجعة البروتوكول. لا توجد أسئلة إضافية.' });
}

// ════════════════════════════════════════════
// End Protocol
// ════════════════════════════════════════════
function endProto(o) {
  DS.locked = true;
  stopTimer();

  // إخفاء منطقة السؤال
  document.getElementById('q-wrap').classList.add('hidden');

  // Progress 100%
  document.getElementById('prog-fill').style.width = '100%';
  document.getElementById('prog-pct').textContent  = '100%';
  setText('hdr-step','مكتمل ✓');

  const prColors = {
    Echo:'var(--echo)',Delta:'var(--delta)',Charlie:'var(--charlie)',
    Bravo:'var(--bravo)',Alpha:'var(--alpha)',
  };
  const prC = prColors[DS.priority] || '#aaa';

  const summaryRows = DS.history.map((h,i) => `
    <div class="summary-row">
      <div class="summary-num">${i+1}</div>
      <div>
        <div class="summary-q">${esc(h.question.substring(0,70))}${h.question.length>70?'...':''}</div>
        <div class="summary-a">${esc(h.answer)}</div>
        ${h.impact ? `<div class="summary-imp">🏷 ${esc(h.impact)}</div>` : ''}
      </div>
    </div>`).join('');

  const html = `
    <div class="final-wrap">

      <div class="final-alert">
        <div class="final-hd">
          <span class="final-hd-ico">⚠️</span>
          <div>
            <div class="final-title">تعليمات الإرسال الطبي</div>
            <div class="final-sub">قراءة إلزامية — هذه التعليمات ختامية ونهائية</div>
          </div>
        </div>
        <div class="final-ins">${esc(o.instruction_text || 'اكتمل البروتوكول.')}</div>
        <div>
          <span class="final-pr-badge"
            style="color:${prC};background:${prC}22;border-color:${prC}55">
            ● الأولوية النهائية: ${esc(DS.priority)}
          </span>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-ttl">ملخص الجلسة — ${DS.history.length} خطوة</div>
        ${summaryRows || '<div class="muted small">لا توجد إجابات مسجّلة</div>'}
      </div>

      <div class="final-actions">
        <button class="btn btn-success" onclick="copyReport()">📋 نسخ التقرير</button>
        <button class="btn btn-danger"  onclick="resetSession()">↺ جلسة جديدة</button>
        <button class="btn btn-primary" onclick="saveLog()">💾 حفظ السجل</button>
      </div>
    </div>`;

  const fw = document.getElementById('final-wrap');
  fw.innerHTML = html;
  fw.classList.remove('hidden');
  document.getElementById('stage').scrollTo({ top:0, behavior:'smooth' });
}

// ════════════════════════════════════════════
// Trail Panel
// ════════════════════════════════════════════
function renderTrail() {
  const wrap = document.getElementById('trail-list');
  if (!DS.history.length) {
    wrap.innerHTML = '<div class="muted small" style="text-align:center;padding:1.5rem;opacity:.5">الإجابات ستظهر هنا</div>';
    return;
  }
  wrap.innerHTML = [...DS.history].reverse().map((h,i) => {
    const step = DS.history.length - i;
    return `<div class="trail-item">
      <div class="trail-q">#${step} ${esc(h.question.substring(0,50))}${h.question.length>50?'...':''}</div>
      <div class="trail-a">${esc(h.answer)}</div>
      ${h.impact ? `<div class="trail-imp">🏷 ${esc(h.impact)}</div>` : ''}
    </div>`;
  }).join('');
}

// ════════════════════════════════════════════
// Priority Chip
// ════════════════════════════════════════════
function updatePrChip(pr) {
  const el = document.getElementById('hdr-pr');
  el.className = `pr-chip pr-${pr}`;
  el.textContent = pr;
}

// ════════════════════════════════════════════
// Copy Report
// ════════════════════════════════════════════
function copyReport() {
  const lines = [
    '══════════════════════════════════',
    'تقرير جلسة بروتوكول الطوارئ',
    '══════════════════════════════════',
    `البروتوكول : ${DS.currentProto?.title || '—'}`,
    `الرمز      : ${DS.currentProto?.code  || '—'}`,
    `الأولوية   : ${DS.priority}`,
    `الخطوات    : ${DS.history.length}`,
    `المدة      : ${fmtTime(DS.timerSec)}`,
    '',
    '── مسار الإجابات ──',
    ...DS.history.map((h,i) =>
      `${i+1}. ${h.question}\n   → ${h.answer}${h.impact?'\n   🏷 '+h.impact:''}`
    ),
    '',
    '══════════════════════════════════',
  ];
  navigator.clipboard.writeText(lines.join('\n'))
    .then(()  => showToast('تم نسخ التقرير ✓'))
    .catch(()  => showToast('تعذّر النسخ','warn'));
}

// ════════════════════════════════════════════
// Save Log
// ════════════════════════════════════════════
async function saveLog() {
  if (!DS.sessionUUID || !DS.currentProto) return;
  const finalIns = document.querySelector('.final-ins')?.textContent || '';
  const r = await api('POST','save_session_log',{
    session_uuid:      DS.sessionUUID,
    protocol_id:       DS.currentProto.id,
    final_priority:    DS.priority,
    final_instruction: finalIns,
    answers_json:      DS.history,
    status:            'completed',
  });
  if (r) showToast('تم حفظ السجل ✓');
}

// ════════════════════════════════════════════
// Reset
// ════════════════════════════════════════════
function resetState() {
  stopTimer();
  Object.assign(DS, {
    currentProto:null, currentQ:null, sessionUUID:null,
    stepNum:0, priority:'Delta', history:[], locked:false, timerSec:0,
  });
}

function resetSession() {
  resetState();
  document.getElementById('welcome').classList.remove('hidden');
  document.getElementById('q-wrap').classList.add('hidden');
  const fw = document.getElementById('final-wrap');
  fw.classList.add('hidden'); fw.innerHTML = '';
  document.getElementById('q-slot').innerHTML = '';
  document.getElementById('hdr-timer-wrap').classList.add('hidden');
  document.getElementById('trail-timer').classList.add('hidden');
  document.getElementById('prog-fill').style.width = '0%';
  document.getElementById('prog-pct').textContent  = '0%';
  setText('hdr-proto','لم يُحدَّد');
  setText('hdr-step','—');
  updatePrChip('Delta');
  renderTrail();
  document.querySelectorAll('.proto-item').forEach(el => el.classList.remove('sel'));
}

// ════════════════════════════════════════════
// Init
// ════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', loadProtos);
</script>
</body>
</html>
