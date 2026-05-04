/**
 * HeartCare - Heart Rate Monitoring System
 * Global JavaScript
 */

// ---- SIDEBAR TOGGLE ----
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar?.classList.toggle('open');
    overlay?.classList.toggle('open');
}

// ---- CLOCK ----
function updateClock() {
    const el = document.getElementById('liveClock');
    if (!el) return;
    const now = new Date();
    el.textContent = now.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}
setInterval(updateClock, 1000);
updateClock();

// ---- TOAST NOTIFICATIONS ----
const toastContainer = document.getElementById('toastContainer');

function showToast(title, msg, type = 'success', duration = 4000) {
    if (!toastContainer) return;
    const icons = {
        critical: `<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>`,
        warning:  `<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>`,
        success:  `<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`,
        info:     `<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0-4h.01"/></svg>`,
    };
    const colors = { critical:'#ef4444', warning:'#f59e0b', success:'#10b981', info:'#3b82f6' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <div class="toast-icon" style="background:${colors[type]}20;color:${colors[type]}">${icons[type]||icons.info}</div>
        <div class="toast-content">
            <div class="toast-title">${escHtml(title)}</div>
            <div class="toast-msg">${escHtml(msg)}</div>
        </div>
    `;
    toastContainer.appendChild(toast);
    setTimeout(() => { toast.style.opacity='0'; toast.style.transform='translateX(20px)'; toast.style.transition='all 0.3s'; setTimeout(() => toast.remove(), 300); }, duration);
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ---- MODAL HELPERS ----
function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }
document.addEventListener('click', e => {
    if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('open');
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
});

// ---- BPM HELPERS ----
function getBpmStatus(bpm) {
    if (bpm < 60 || bpm > 120) return 'critical';
    if (bpm >= 100)             return 'warning';
    return 'normal';
}
function getBpmBarPct(bpm) {
    return Math.min(100, Math.max(0, ((bpm - 40) / (160 - 40)) * 100));
}
function getBpmClass(status) {
    return { normal:'bpm-normal', warning:'bpm-warning', critical:'bpm-critical' }[status] || 'bpm-normal';
}
function getFillClass(status) {
    return { normal:'fill-normal', warning:'fill-warning', critical:'fill-critical' }[status] || 'fill-normal';
}
function getBadgeClass(status) {
    return { normal:'badge-normal', warning:'badge-warning', critical:'badge-critical', stable:'badge-stable' }[status] || 'badge-normal';
}
function getBadgeLabel(status) {
    return { normal:'Normal', warning:'Warning', critical:'Critical', stable:'Stable' }[status] || status;
}
function timeAgo(ts) {
    const diff = Math.floor((Date.now() - new Date(ts).getTime()) / 1000);
    if (diff < 5)  return 'Just now';
    if (diff < 60) return `${diff}s ago`;
    if (diff < 3600) return `${Math.floor(diff/60)}m ago`;
    return `${Math.floor(diff/3600)}h ago`;
}

// ---- SOUND ALERT ----
let soundEnabled = true;
let audioCtx = null;

function playAlertSound() {
    if (!soundEnabled) return;
    try {
        if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        [880, 1100, 880].forEach((freq, i) => {
            const osc  = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain); gain.connect(audioCtx.destination);
            osc.frequency.value = freq;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.15, audioCtx.currentTime + i * 0.18);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + i * 0.18 + 0.15);
            osc.start(audioCtx.currentTime + i * 0.18);
            osc.stop(audioCtx.currentTime + i * 0.18 + 0.15);
        });
    } catch(e) {}
}

function toggleSound() {
    soundEnabled = !soundEnabled;
    const btn = document.getElementById('soundToggle');
    if (btn) btn.textContent = soundEnabled ? '🔔 Sound On' : '🔕 Sound Off';
}

// ---- TABS ----
function switchTab(tabName, groupId) {
    const group = groupId ? document.getElementById(groupId) : document;
    group.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b.dataset.tab === tabName));
    group.querySelectorAll('.tab-content').forEach(c => c.classList.toggle('active', c.id === 'tab_' + tabName));
}

// ---- LIVE RESPONDER MONITOR ----
let prevCriticalCount = 0;
let isFirstLoad = true;

async function refreshHeartRates() {
    try {
        const res  = await fetch('../api/get_heart_rate.php');
        const data = await res.json();
        if (!data.success) return;
        renderPatientTable(data.patients);
        renderPatientCards(data.patients);
        updateSummaryStats(data.summary);
        handleAlerts(data.patients);
        updateCriticalNavBadge(data.summary.critical);
        isFirstLoad = false;
    } catch(e) {
        console.warn('Refresh error:', e);
    }
}

function renderPatientTable(patients) {
    const tbody = document.getElementById('patientTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';
    patients.forEach(p => {
        const st  = getBpmStatus(p.bpm);
        const pct = getBpmBarPct(p.bpm);
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><div style="font-weight:600">${escHtml(p.name)}</div><div class="td-muted">${escHtml(p.room||'')}</div></td>
            <td class="td-muted">${p.age}</td>
            <td class="td-muted">${escHtml(p.condition||'—')}</td>
            <td>
                <div class="bpm-cell">
                    <div>
                        <span class="bpm-value ${getBpmClass(st)}">${p.bpm}</span>
                        <span class="bpm-unit">BPM</span>
                    </div>
                    <div class="bpm-bar desktop-only">
                        <div class="bpm-bar-fill ${getFillClass(st)}" style="width:${pct}%"></div>
                    </div>
                </div>
            </td>
            <td><span class="badge ${getBadgeClass(st)}"><span class="badge-dot" style="background:currentColor"></span>${getBadgeLabel(st)}</span></td>
            <td class="td-muted">${escHtml(timeAgo(p.last_updated))}</td>
        `;
        tbody.appendChild(row);
    });
}

function renderPatientCards(patients) {
    const container = document.getElementById('patientCardsMobile');
    if (!container) return;
    container.innerHTML = '';
    patients.forEach(p => {
        const st  = getBpmStatus(p.bpm);
        const card = document.createElement('div');
        card.className = `patient-card card-${st}`;
        card.innerHTML = `
            <div class="patient-card-header">
                <div class="patient-card-name">${escHtml(p.name)}</div>
                <span class="badge ${getBadgeClass(st)}">${getBadgeLabel(st)}</span>
            </div>
            <div class="patient-card-body">
                <div class="patient-card-stat">BPM<span class="bpm-value ${getBpmClass(st)}" style="font-size:22px">${p.bpm}</span></div>
                <div class="patient-card-stat">Room<span>${escHtml(p.room||'—')}</span></div>
                <div class="patient-card-stat">Condition<span>${escHtml(p.condition||'—')}</span></div>
                <div class="patient-card-stat">Updated<span>${escHtml(timeAgo(p.last_updated))}</span></div>
            </div>
        `;
        container.appendChild(card);
    });
}

function updateSummaryStats(summary) {
    const set = (id, val) => { const el = document.getElementById(id); if(el) el.textContent = val; };
    set('statTotal',    summary.total    ?? '—');
    set('statNormal',   summary.normal   ?? '—');
    set('statWarning',  summary.warning  ?? '—');
    set('statCritical', summary.critical ?? '—');
}

function handleAlerts(patients) {
    const criticals = patients.filter(p => getBpmStatus(p.bpm) === 'critical');
    const banner    = document.getElementById('alertBanner');
    if (banner) {
        if (criticals.length > 0) {
            banner.style.display = 'flex';
            const names = criticals.map(p => p.name).join(', ');
            document.getElementById('alertText').textContent = `${criticals.length} critical patient(s): ${names}`;
        } else {
            banner.style.display = 'none';
        }
    }
    if (!isFirstLoad && criticals.length > prevCriticalCount) {
        playAlertSound();
        criticals.slice(prevCriticalCount).forEach(p => {
            showToast('⚠️ Critical Alert', `${p.name} — ${p.bpm} BPM`, 'critical', 6000);
        });
    }
    prevCriticalCount = criticals.length;
}

function updateCriticalNavBadge(count) {
    const el = document.getElementById('criticalNavBadge');
    if (el) el.textContent = count || 0;
}

// ---- SIMULATE DATA ----
async function simulateData() {
    try {
        await fetch('../api/simulate_data.php', { method: 'POST' });
        await refreshHeartRates();
    } catch(e) {}
}

// ---- FORMAT DATE ----
function formatDate(ts) {
    return new Date(ts).toLocaleString('en-PH', { month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
}

// ---- ADMIN: DELETE CONFIRM ----
function confirmDelete(url, name, type) {
    if (confirm(`Delete ${type} "${name}"? This cannot be undone.`)) {
        window.location.href = url;
    }
}