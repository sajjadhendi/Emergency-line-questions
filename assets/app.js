/* assets/app.js — مكتبة مشتركة بين admin.php و index.php */
'use strict';

// ─── API Helper ─────────────────────────────────────────────────
const API_BASE = 'api.php';

async function apiGET(action, params = {}) {
  const q = new URLSearchParams({ action, ...params });
  try {
    const r = await fetch(`${API_BASE}?${q}`, { cache: 'no-store' });
    if (!r.ok) throw new Error('HTTP ' + r.status);
    const j = await r.json();
    if (!j.success) { showToast(j.message || 'خطأ', 'err'); return null; }
    return j.data;
  } catch (e) {
    showToast('خطأ في الاتصال بالخادم', 'err');
    console.error('[GET]', action, e);
    return null;
  }
}

async function apiPOST(action, data = {}) {
  try {
    const r = await fetch(`${API_BASE}?action=${encodeURIComponent(action)}`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(data),
    });
    if (!r.ok) throw new Error('HTTP ' + r.status);
    const j = await r.json();
    if (!j.success) { showToast(j.message || 'خطأ', 'err'); return null; }
    return j.data ?? true;
  } catch (e) {
    showToast('خطأ في الاتصال بالخادم', 'err');
    console.error('[POST]', action, e);
    return null;
  }
}

// ─── Loading ─────────────────────────────────────────────────────
let _loadingCount = 0;
function showLoading(on) {
  _loadingCount = Math.max(0, _loadingCount + (on ? 1 : -1));
  const el = document.getElementById('loading');
  if (el) el.classList.toggle('hidden', _loadingCount === 0);
}

// نغلّف API calls تلقائياً بـ loading
async function api(method, action, data = {}) {
  showLoading(true);
  const result = method === 'POST'
    ? await apiPOST(action, data)
    : await apiGET(action, data);
  showLoading(false);
  return result;
}

// ─── Toast ───────────────────────────────────────────────────────
let _toastTimer = null;
function showToast(msg, type = 'ok', ms = 3200) {
  const el = document.getElementById('toast');
  if (!el) return;
  el.textContent = msg;
  el.className = `show ${type}`;
  clearTimeout(_toastTimer);
  _toastTimer = setTimeout(() => el.classList.remove('show'), ms);
}

// ─── Modal ───────────────────────────────────────────────────────
function openModal(id) {
  const el = document.getElementById(id);
  if (el) { el.classList.remove('hidden'); el.querySelector('.modal-box')?.classList.add('fade-up'); }
}
function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('hidden');
}
// إغلاق عند الضغط على الخلفية
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-bg')) {
    e.target.classList.add('hidden');
  }
});

// ─── Helpers ─────────────────────────────────────────────────────
function esc(s) {
  if (s == null) return '';
  return String(s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

function emptyEl(id) {
  const el = document.getElementById(id);
  if (el) el.innerHTML = '';
}

function setHTML(id, html) {
  const el = document.getElementById(id);
  if (el) el.innerHTML = html;
}

function setText(id, text) {
  const el = document.getElementById(id);
  if (el) el.textContent = text;
}

function setVal(id, val) {
  const el = document.getElementById(id);
  if (el) {
    if (el.type === 'checkbox') el.checked = !!val;
    else el.value = (val ?? '');
  }
}

function getVal(id) {
  const el = document.getElementById(id);
  if (!el) return '';
  if (el.type === 'checkbox') return el.checked;
  return el.value;
}

function genUUID() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
    const r = Math.random() * 16 | 0;
    return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
  });
}

function priorityLabel(p) {
  const m = { Echo:'🔴 Echo', Delta:'🟠 Delta', Charlie:'🟡 Charlie', Bravo:'🟢 Bravo', Alpha:'🔵 Alpha' };
  return m[p] || p || '—';
}

function ageLabel(a) {
  return { adult:'بالغون', pediatric:'أطفال', all:'الكل' }[a] || a;
}

// Live clock
function startClock(elId) {
  const el = document.getElementById(elId);
  if (!el) return;
  const tick = () => {
    const n = new Date();
    el.textContent = n.toLocaleTimeString('ar-SA', { hour12: false });
  };
  tick();
  setInterval(tick, 1000);
}
