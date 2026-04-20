// api-client.js — CMS Frontend ↔ PHP/MySQL Backend

const API = {
    base: window.location.origin + window.location.pathname.split('/frontend/')[0] + '/backend/api',

    resolveUrl(path) {
        if (!path) return '';
        if (path.startsWith('http')) return path;
        const root = window.location.origin + window.location.pathname.split('/frontend/')[0];
        return root + (path.startsWith('/') ? '' : '/') + path;
    },

    async call(endpoint, method = 'GET', data = null) {
        const url  = `${this.base}/${endpoint}`;
        const opts = { method, credentials: 'include', headers: { 'Content-Type': 'application/json' } };

        if (data && method === 'GET') {
            const qs = new URLSearchParams(data).toString();
            return fetch(`${url}${qs ? '?' + qs : ''}`, opts).then(r => r.json());
        }
        if (data && method !== 'GET') opts.body = JSON.stringify(data);

        return fetch(url, opts).then(async r => {
            const json = await r.json();
            if (r.status === 401) {
                // Dynamically build login URL so this works on Laragon, XAMPP, or any subdirectory
                const loginUrl = window.location.origin
                    + window.location.pathname.split('/frontend/')[0]
                    + '/frontend/auth/login.html';
                window.location.href = loginUrl;
            }
            return json;
        }).catch(() => ({ error: 'Koneksi server gagal' }));
    },

    // Auth
    login:  (u, p) => API.call('auth.php?action=login', 'POST', { username: u, password: p }),
    logout: ()     => API.call('auth.php?action=logout', 'POST'),
    check:  ()     => API.call('auth.php?action=check'),

    // Containers
    getContainers:   (p = {}) => API.call('containers.php', 'GET', p),
    getContainer:    (id)     => API.call('containers.php', 'GET', { id }),
    createContainer: (d)      => API.call('containers.php', 'POST', d),
    updateContainer: (d)      => API.call('containers.php', 'PUT',  d),
    deleteContainer: (id)     => API.call('containers.php', 'DELETE', { id }),

    // Documents
    getDocuments:   (p = {}) => API.call('documents.php', 'GET', p),
    uploadDocument: (d)      => API.call('documents.php', 'POST', d),
    updateDocument: (d)      => API.call('documents.php', 'PUT',  d),
    deleteDocument: (id)     => API.call('documents.php', 'DELETE', { id }),

    // Notifications
    getNotifications: ()           => API.call('notifications.php'),
    markRead:         (id = 'all') => API.call('notifications.php', 'PUT', { id }),

    // Stats
    getStats: () => API.call('stats.php'),

    // Users (admin)
    getUsers:   (role = null) => API.call('users.php', 'GET', role ? { role } : {}),
    createUser: (d) => API.call('users.php', 'POST', d),
    updateUser: (d) => API.call('users.php', 'PUT',  d),
    deleteUser: (id) => API.call('users.php', 'DELETE', { id }),
};

// ── STATUS CONFIG ─────────────────────────────────────────
const STATUS_CONFIG = {
    booking:      { label:'Booking',              color:'#6366f1', bg:'rgba(99,102,241,.15)',   icon:'📋' },
    gate_in:      { label:'Gate-In Terminal',     color:'#0891b2', bg:'rgba(8,145,178,.15)',    icon:'🚪' },
    on_vessel:    { label:'Di Atas Kapal',         color:'#2563eb', bg:'rgba(37,99,235,.15)',    icon:'🚢' },
    discharged:   { label:'Dibongkar',             color:'#f59e0b', bg:'rgba(245,158,11,.15)',   icon:'⬇️' },
    clearance:    { label:'Clearance Bea Cukai',  color:'#d97706', bg:'rgba(217,119,6,.15)',    icon:'🔍' },
    on_delivery:  { label:'Dalam Pengiriman',      color:'#7c3aed', bg:'rgba(124,58,237,.15)',   icon:'🚛' },
    gate_in_depo: { label:'Gate-In Depo',          color:'#059669', bg:'rgba(5,150,105,.15)',    icon:'🏭' },
    completed:    { label:'Selesai',               color:'#10b981', bg:'rgba(16,185,129,.15)',   icon:'✅' },
    delay:        { label:'Delay',                 color:'#ef4444', bg:'rgba(239,68,68,.15)',    icon:'⚠️' },
};

const DOC_STATUS = {
    pending:  { label:'Menunggu Verifikasi', color:'#f59e0b', bg:'rgba(245,158,11,.15)' },
    approved: { label:'Disetujui',           color:'#10b981', bg:'rgba(16,185,129,.15)' },
    revision: { label:'Perlu Revisi',        color:'#ef4444', bg:'rgba(239,68,68,.15)'  },
};

// ── UTILITIES ─────────────────────────────────────────────
function formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' });
}
function formatDateTime(d) {
    if (!d) return '-';
    return new Date(d).toLocaleString('id-ID', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
}
function statusBadge(status) {
    const s = STATUS_CONFIG[status] || { label:status, color:'#64748b', bg:'rgba(100,116,139,.15)', icon:'📦' };
    return `<span class="badge" style="background:${s.bg};color:${s.color}">${s.icon} ${s.label}</span>`;
}
function docBadge(status) {
    const d = DOC_STATUS[status] || { label:status, color:'#64748b', bg:'rgba(100,116,139,.15)' };
    return `<span class="badge" style="background:${d.bg};color:${d.color}">${d.label}</span>`;
}
async function doLogout() {
    await API.logout();
    const loginUrl = window.location.origin
        + window.location.pathname.split('/frontend/')[0]
        + '/frontend/auth/login.html';
    window.location.href = loginUrl;
}

// Auto-refresh helper
let _autoRefId = null;
function setAutoRefresh(fn, ms = 30000) {
    if (_autoRefId) clearInterval(_autoRefId);
    _autoRefId = setInterval(fn, ms);
}

// Toast notification
function showToast(msg, type = 'success') {
    const colors = { success:'#10b981', error:'#ef4444', info:'#38bdf8', warning:'#f59e0b' };
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:9999;background:#1e293b;border:1px solid ${colors[type]};border-radius:12px;padding:14px 20px;color:#f1f5f9;font-size:13px;font-family:'Space Grotesk',sans-serif;box-shadow:0 8px 32px rgba(0,0,0,.4);animation:slideIn .3s ease`;
    t.innerHTML = `<span style="color:${colors[type]};margin-right:8px">${type==='success'?'✅':type==='error'?'❌':type==='warning'?'⚠️':'ℹ️'}</span>${msg}`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}
// CSS for toast
const style = document.createElement('style');
style.textContent = `@keyframes slideIn{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}`;
document.head.appendChild(style);
