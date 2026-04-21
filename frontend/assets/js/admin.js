// ── STATE
let currentUser   = null;
let mapInstance   = null;
let mapMarkers    = {};
let movChart, stChart, rptChart;
let editingCtr    = null;
let editingDoc    = null;
let editingUser   = null;
let _allReportData = [];

const ITEMS_PER_PAGE = 10;

function buildPagination(total, page, callbackName) {
  const pages = Math.ceil(total / ITEMS_PER_PAGE);
  if (pages <= 1) return '';
  let html = `<button class="btn btn-sm btn-ghost" onclick="${callbackName}(${page-1})" ${page===1?'disabled':''}>&laquo;</button>`;
  for (let i = 1; i <= pages; i++) {
    html += `<button class="btn btn-sm ${i===page?'btn-primary':'btn-ghost'}" onclick="${callbackName}(${i})">${i}</button>`;
  }
  html += `<button class="btn btn-sm btn-ghost" onclick="${callbackName}(${page+1})" ${page===pages?'disabled':''}>&raquo;</button>`;
  return html;
}

// ── INIT
document.addEventListener('DOMContentLoaded', async () => {
  const check = await API.check();
  if (!check.authenticated || check.user.role !== 'admin') {
    window.location.href = '../auth/login.html'; return;
  }
  currentUser = check.user;
  document.getElementById('userName').textContent    = currentUser.name;
  document.getElementById('userInitial').textContent = currentUser.name[0].toUpperCase();
  document.getElementById('dateDisplay').textContent =
    new Date().toLocaleDateString('id-ID',{weekday:'long',day:'2-digit',month:'long',year:'numeric'});

  await renderDashboard();
  await updateBadges();

  setInterval(async () => {
    await updateBadges();
    if (document.getElementById('sec-tracking').classList.contains('active')) {
      await refreshMapMarkers();
    }
  }, 30000);
});

// ── NAVIGASI
const PAGE_TITLES = {
  dashboard:     ['Dashboard Admin',        'CMS › Dashboard'],
  containers:    ['Manajemen Kontainer',    'CMS › Kontainer'],
  documents:     ['Manajemen Dokumen',      'CMS › Dokumen'],
  tracking:      ['Live Tracking',          'CMS › Live Tracking'],
  users:         ['Manajemen Pengguna',     'CMS › Pengguna'],
  notifications: ['Notifikasi Sistem',      'CMS › Notifikasi'],
  reports:       ['Laporan & Statistik',    'CMS › Laporan'],
};

async function showSection(name) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  document.getElementById('sec-' + name).classList.add('active');
  const navItem = document.querySelector(`.nav-item[onclick*="${name}"]`);
  if(navItem) navItem.classList.add('active');
  
  const [t, b] = PAGE_TITLES[name] || ['Dashboard', 'CMS Tools'];
  document.getElementById('pageTitle').textContent      = t;
  document.getElementById('pageBreadcrumb').textContent = b;

  if (name === 'containers')    await renderContainers();
  if (name === 'documents')     await renderDocuments();
  if (name === 'tracking')      await renderTracking();
  if (name === 'users')         await renderUsers();
  if (name === 'notifications') await renderNotifications();
  if (name === 'reports')       await renderReports();
}

// ── DASHBOARD
async function renderDashboard() {
  const [stats, notifs, ctrs] = await Promise.all([
    API.getStats(), API.getNotifications(), API.getContainers()
  ]);

  document.getElementById('statGrid').innerHTML = `
    <div class="stat-card"><div class="stat-label">Total Kontainer</div><div class="stat-value">${stats.total}</div><div class="stat-icon">📦</div></div>
    <div class="stat-card"><div class="stat-label">In Transit</div><div class="stat-value" style="color:var(--cyan)">${stats.in_transit}</div><div class="stat-icon">🚢</div></div>
    <div class="stat-card"><div class="stat-label">Selesai</div><div class="stat-value" style="color:var(--green)">${stats.completed}</div><div class="stat-icon">✅</div></div>
    <div class="stat-card"><div class="stat-label">Dok Pending</div><div class="stat-value" style="color:var(--gold)">${stats.pending_docs}</div><div class="stat-icon">📄</div></div>

  `;

  const ctx1 = document.getElementById('movChart').getContext('2d');
  if (movChart) movChart.destroy();
  movChart = new Chart(ctx1, {
    type: 'line',
    data: {
      labels: (stats.monthly_data||[]).map(m => m.month),
      datasets: [{ label:'Kontainer', data:(stats.monthly_data||[]).map(m=>m.total),
        borderColor:'#00d4ff', backgroundColor:'rgba(0,212,255,.08)', fill:true, tension:0.4 }]
    },
    options: { responsive:true, plugins:{legend:{labels:{color:'#94a3b8',font:{size:10}}}},
      scales:{ x:{ticks:{color:'#64748b'}}, y:{ticks:{color:'#64748b',stepSize:1}} } }
  });

  const ctx2 = document.getElementById('statusChart').getContext('2d');
  if (stChart) stChart.destroy();
  const by = stats.by_status || {};
  stChart = new Chart(ctx2, {
    type: 'doughnut',
    data: {
      labels: Object.keys(by).map(k => STATUS_CONFIG[k]?.label || k),
      datasets: [{ data: Object.values(by),
        backgroundColor:['#6366f1','#0891b2','#2563eb','#f59e0b','#d97706','#7c3aed','#10b981','#ef4444'] }]
    },
    options: { responsive:true, plugins:{legend:{position:'right',labels:{color:'#94a3b8',font:{size:10}}}} }
  });

  document.getElementById('recentTable').innerHTML = ctrs.slice(0,5).map(c =>
    `<tr><td class="mono">${c.id}</td><td style="font-size:11px">${c.vessel}</td>
    <td style="font-size:10px;color:var(--gray)">${c.origin}→${c.destination}</td>
    <td>${statusBadge(c.status)}</td></tr>`
  ).join('');

  const nc = {success:'#10b981',warning:'#f59e0b',danger:'#ef4444',info:'#00d4ff'};
  document.getElementById('dashNotifList').innerHTML = (notifs.notifications||[]).slice(0,5).map(n =>
    `<div class="notif-item ${n.is_read?'':'unread'}">
      <div class="notif-dot" style="background:${nc[n.type]||'#64748b'}"></div>
      <div><div style="font-size:12px">${n.message}</div>
      <div style="font-size:10px;color:var(--gray)">${formatDateTime(n.created_at)}</div></div>
    </div>`
  ).join('') || '<div style="color:var(--gray);font-size:12px;text-align:center;padding:16px">Tidak ada notifikasi</div>';
}

// ── CONTAINERS
async function renderContainers(page = 1) {
  const data = await API.getContainers({
    search: document.getElementById('ctrSearch').value,
    status: document.getElementById('ctrFilter').value,
  });
  const start  = (page - 1) * ITEMS_PER_PAGE;
  const sliced = data.slice(start, start + ITEMS_PER_PAGE);
  document.getElementById('containerTable').innerHTML = sliced.map(c =>
    `<tr>
      <td class="mono">${c.id}</td>
      <td style="font-size:11px;color:var(--gray)">${c.booking_no}</td>
      <td style="font-size:11px">${c.vessel}<br><span style="color:var(--gray);font-size:10px">${c.voyage}</span></td>
      <td style="font-size:11px">${c.origin}→${c.destination}</td>
      <td style="font-size:11px">${c.commodity}<br><span style="color:var(--gray);font-size:10px">${c.type}</span></td>
      <td>${statusBadge(c.status)}</td>
      <td style="font-size:10px;color:var(--gray);max-width:130px">${c.position_desc}</td>
      <td style="white-space:nowrap">
        <button class="btn btn-ghost btn-sm" onclick="viewDetail('${c.id}')">👁</button>
        <button class="btn btn-danger btn-sm" onclick="deleteContainer('${c.id}')">🗑</button>
      </td>
    </tr>`
  ).join('') || `<tr><td colspan="8" style="text-align:center;padding:30px;color:var(--gray)">Tidak ada data kontainer</td></tr>`;
  document.getElementById('pg-container').innerHTML = buildPagination(data.length, page, 'renderContainers');
}

async function deleteContainer(id) {
  if (!confirm(`Hapus kontainer ${id}?\\n\\nSemua data terkait akan ikut terhapus.`)) return;
  const res = await API.deleteContainer(id);
  if (res.error) { showToast(res.error,'error'); return; }
  await renderContainers();
  await updateBadges();
  showToast(`🗑️ Kontainer ${id} berhasil dihapus`);
}

async function viewDetail(id) {
  const c = await API.getContainer(id);
  if (!c) return;
  document.getElementById('detailTitle').textContent = `Detail: ${c.id}`;
  document.getElementById('detailSub').textContent   = `${c.booking_no} · ${c.vessel} · ${c.origin}→${c.destination}`;
  document.getElementById('detailContent').innerHTML = `
    <div style="margin-bottom:10px">${statusBadge(c.status)} <span style="font-size:11px;color:var(--gray);margin-left:8px">📍 ${c.position_desc}</span></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px">
      ${[['Tipe',c.type],['Berat',Number(c.weight).toLocaleString()+' kg'],['Komoditi',c.commodity],['ETA',formatDate(c.eta)],['Pemilik',c.owner_name||'-'],['Operator',c.operator_name||'-']]
        .map(([l,v])=>`<div style="background:rgba(255,255,255,.03);border-radius:8px;padding:8px 12px">
          <div style="font-size:10px;color:var(--gray)">${l}</div>
          <div style="font-size:12px;color:var(--white);font-weight:600">${v||'-'}</div></div>`).join('')}
    </div>
    <div style="font-size:12px;font-weight:700;color:var(--white);margin-bottom:8px">📄 Dokumen (${(c.documents||[]).length})</div>
    ${(c.documents||[]).map(d=>`<div style="display:flex;justify-content:space-between;padding:8px;background:rgba(255,255,255,.03);border-radius:8px;margin-bottom:5px">
      <span style="font-size:12px">📄 ${d.type}</span>${docBadge(d.status)}</div>`).join('')
      || '<div style="font-size:12px;color:var(--gray);margin-bottom:10px">Belum ada dokumen</div>'}
    <div style="font-size:12px;font-weight:700;color:var(--white);margin:12px 0 8px">📍 Timeline</div>
    <div class="timeline">${(c.events||[]).map((e,i,a)=>
      `<div class="tl-item ${i===a.length-1?'active':'done'}">
        <div class="tl-event">${e.event}</div>
        <div class="tl-meta">${e.actor} · ${formatDateTime(e.timestamp)}</div>
        ${e.note?`<div style="font-size:10px;color:var(--gray)">${e.note}</div>`:''}
      </div>`).join('')}</div>`;
  document.getElementById('modalDetail').classList.add('open');
}

// ── DOCUMENTS
let _allDocData = [];
async function renderDocuments(page = 1) {
  const reqData = {
    search: document.getElementById('docSearch')?.value || '',
    status: document.getElementById('docFilter')?.value || '',
  };
  const data = await API.getDocuments(reqData);
  
  const filterDate = document.getElementById('docDate')?.value || '';
  const filtered = data.filter(d => {
    if (!d.created_at) return true;
    if (filterDate) {
      const dDate = new Date(d.created_at);
      const localDate = dDate.getFullYear() + '-' + String(dDate.getMonth() + 1).padStart(2, '0') + '-' + String(dDate.getDate()).padStart(2, '0');
      if (localDate !== filterDate) return false;
    }
    return true;
  });
  
  _allDocData = filtered;

  const start  = (page - 1) * ITEMS_PER_PAGE;
  const sliced = filtered.slice(start, start + ITEMS_PER_PAGE);
  document.getElementById('documentTable').innerHTML = sliced.map(d =>
    `<tr>
      <td class="mono">${d.id}</td>
      <td><span class="mono">${d.container_id}</span><br><span style="font-size:10px;color:var(--gray)">${d.vessel||''}</span></td>
      <td style="font-size:12px">📄 ${d.type}</td>
      <td>${docBadge(d.status)}</td>
      <td style="font-size:11px;color:var(--gray)">${formatDateTime(d.created_at)}</td>
      <td style="font-size:11px;max-width:160px">${d.notes||'-'}</td>
      <td style="white-space:nowrap">
        ${d.filepath ? `<button class="btn btn-ghost btn-sm" onclick="openDocPreview(\'${API.resolveUrl(d.filepath)}\', \'${d.type}\', \'${d.id}\')">👁 Preview</button>` : '<span style="font-size:10px;color:var(--gray)">Tidak ada file</span>'}
        <button class="btn btn-ghost btn-sm" onclick="openDocStatus('${d.id}','${d.status}','${d.type}')">✏️ Update</button>
      </td>
    </tr>`
  ).join('') || `<tr><td colspan="7" style="text-align:center;padding:30px;color:var(--gray)">Tidak ada dokumen</td></tr>`;
  document.getElementById('pg-document').innerHTML = buildPagination(filtered.length, page, 'renderDocuments');
}

function openDocStatus(id, status, type) {
  editingDoc = id;
  document.getElementById('docStatusSub').textContent  = `Dokumen: ${type} (${id})`;
  document.getElementById('newDocStatus').value         = status;
  document.getElementById('docStatusNote').value        = '';
  document.getElementById('modalDocStatus').classList.add('open');
}

async function saveDocStatus() {
  const res = await API.updateDocument({
    id: editingDoc,
    status: document.getElementById('newDocStatus').value,
    notes:  document.getElementById('docStatusNote').value,
  });
  if (res.error) { showToast(res.error,'error'); return; }
  closeModal('modalDocStatus');
  await renderDocuments();
  await updateBadges();
  showToast('✅ Status dokumen berhasil diupdate!');
}

// ── LIVE TRACKING
async function renderTracking() {
  if (!mapInstance) {
    mapInstance = L.map('adminMap').setView([-7.2575, 112.7521], 9);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap'
    }).addTo(mapInstance);
  }
  await refreshMapMarkers();

  if (!window._trackInterval) {
    window._trackInterval = setInterval(async () => {
      if (document.getElementById('sec-tracking').classList.contains('active')) {
        await refreshMapMarkers();
      }
    }, 15000);
  }
}

async function refreshMapMarkers() {
  const containers = await API.getContainers();
  const active     = containers.filter(c => c.status !== 'completed');
  const activeIds  = active.map(c => c.id);

  Object.keys(mapMarkers).forEach(id => {
    if (!activeIds.includes(id)) { mapMarkers[id].remove(); delete mapMarkers[id]; }
  });

  active.forEach(c => {
    const s   = STATUS_CONFIG[c.status] || { label:c.status, color:'#64748b', icon:'📦' };
    const lat = parseFloat(c.position_lat) || -7.2575;
    const lng = parseFloat(c.position_lng) || 112.7521;

    const iconHtml = `<div style="background:${s.color};color:white;padding:4px 10px;border-radius:20px;font-size:10px;font-weight:700;white-space:nowrap;box-shadow:0 3px 10px rgba(0,0,0,.4)">${s.icon} ${c.id}</div>`;
    const icon = L.divIcon({ html: iconHtml, className:'', iconAnchor:[0,0] });
    const popup = `<b>${c.id}</b><br>${c.vessel}<br>${c.origin}→${c.destination}<br>📍 ${c.position_desc||'-'}`;

    if (mapMarkers[c.id]) {
      mapMarkers[c.id].setLatLng(L.latLng(lat, lng));
      mapMarkers[c.id].setIcon(icon);
      mapMarkers[c.id].setPopupContent(popup);
    } else {
      mapMarkers[c.id] = L.marker([lat, lng], { icon }).addTo(mapInstance).bindPopup(popup);
    }
  });

  document.getElementById('activeCount').textContent = `${active.length} kontainer`;
  document.getElementById('mapLastUpdate').textContent = `Diupdate: ${new Date().toLocaleTimeString('id-ID')}`;

  document.getElementById('trackingList').innerHTML = active.map(c => {
    const s   = STATUS_CONFIG[c.status] || {};
    const lat = parseFloat(c.position_lat) || -7.2575;
    const lng = parseFloat(c.position_lng) || 112.7521;
    return `<div data-id="${c.id}" style="padding:10px;border:1px solid var(--border);border-radius:10px;margin-bottom:8px;cursor:pointer"
      onclick="mapInstance.setView([${lat},${lng}],14);mapMarkers['${c.id}']?.openPopup()">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <span class="mono">${c.id}</span>${statusBadge(c.status)}
      </div>
      <div style="font-size:11px;color:var(--gray);margin-top:4px">📍 ${c.position_desc||'-'}</div>
      <div style="font-size:10px;color:var(--gray)">${c.vessel} · ${c.origin}→${c.destination}</div>
    </div>`;
  }).join('') || '<div style="color:var(--gray);text-align:center;padding:20px;font-size:12px">Tidak ada kontainer aktif</div>';

  document.getElementById('mapLegend').innerHTML = [...new Set(active.map(c=>c.status))].map(st => {
    const s = STATUS_CONFIG[st] || {};
    return `<div class="map-legend-item"><div class="map-legend-dot" style="background:${s.color||'#64748b'}"></div><span>${s.label||st}</span></div>`;
  }).join('');
}

// ── USERS
async function renderUsers() {
  const users = await API.getUsers();
  const rc    = { admin:'#f59e0b', operator:'#10b981', stakeholder:'#00d4ff' };
  document.getElementById('userTable').innerHTML = (users||[]).map(u =>
    `<tr>
      <td class="mono">${u.id}</td>
      <td style="font-weight:600;color:var(--white)">${u.name}</td>
      <td class="mono" style="font-size:11px">${u.username}</td>
      <td><span class="badge" style="background:${rc[u.role]}20;color:${rc[u.role]}">${u.role.toUpperCase()}</span></td>
      <td style="font-size:11px;color:var(--gray)">${u.email||'-'}</td>
      <td style="font-size:11px;color:var(--gray)">${u.port||'-'}</td>
      <td>
        ${u.status === 'pending' ? '<span class="badge" style="background:rgba(239,68,68,0.2);color:var(--red)">PENDING</span>' : '<span class="badge" style="background:rgba(16,185,129,0.2);color:var(--green)">VERIFIED</span>'}
      </td>
      <td>
        ${u.status === 'pending' ? `<button class="btn btn-success btn-sm" onclick="verifyUser(${u.id}, '${u.name}', '${u.email}', '${u.port}', '${u.role}')">✔️ Verifikasi</button>` : ''}
        <button class="btn btn-ghost btn-sm" onclick="openEditUser(${u.id},'${u.username}','${u.name}','${u.role}','${u.email||''}','${u.port||''}')">✏️</button>
        <button class="btn btn-danger btn-sm" onclick="deleteUser(${u.id})">🗑</button>
      </td>
    </tr>`
  ).join('');
}

async function verifyUser(id, name, email, port, role) {
  if (!confirm('Verifikasi pengguna ini?')) return;
  const data = { id: id, name: name, email: email, port: port, role: role, status: 'verified' };
  const res = await API.updateUser(data);
  if (res.error) { showToast(res.error, 'error'); return; }
  await renderUsers();
  showToast('✅ Pengguna berhasil diverifikasi!');
}

function openAddUser() {
  editingUser = null;
  document.getElementById('modalUserTitle').textContent = 'Tambah Pengguna Baru';
  ['u_username','u_password','u_name','u_email','u_port'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('u_role').value = 'stakeholder';
  document.getElementById('modalUser').classList.add('open');
}

function openEditUser(id, username, name, role, email, port) {
  editingUser = id;
  document.getElementById('modalUserTitle').textContent = `Edit Pengguna: ${name}`;
  document.getElementById('u_username').value = username;
  document.getElementById('u_password').value = '';
  document.getElementById('u_name').value     = name;
  document.getElementById('u_role').value     = role;
  document.getElementById('u_email').value    = email;
  document.getElementById('u_port').value     = port;
  document.getElementById('modalUser').classList.add('open');
}

async function saveUser() {
  const data = {
    id:       editingUser,
    username: document.getElementById('u_username').value,
    password: document.getElementById('u_password').value,
    name:     document.getElementById('u_name').value,
    role:     document.getElementById('u_role').value,
    email:    document.getElementById('u_email').value,
    port:     document.getElementById('u_port').value,
  };
  const res = editingUser ? await API.updateUser(data) : await API.createUser(data);
  if (res.error) { showToast(res.error,'error'); return; }
  closeModal('modalUser');
  await renderUsers();
  showToast(editingUser ? '✅ Pengguna berhasil diupdate!' : '✅ Pengguna baru berhasil ditambahkan!');
}

async function deleteUser(id) {
  if (!confirm('Hapus pengguna ini?')) return;
  const res = await API.deleteUser(id);
  if (res.error) { showToast(res.error,'error'); return; }
  await renderUsers();
  showToast('🗑️ Pengguna berhasil dihapus');
}

// ── NOTIFICATIONS
async function renderNotifications(page = 1) {
  const data   = await API.getNotifications();
  const notifs = data.notifications || [];
  const start  = (page - 1) * ITEMS_PER_PAGE;
  const sliced = notifs.slice(start, start + ITEMS_PER_PAGE);

  document.getElementById('unreadCount').textContent = `${data.unread_count} belum dibaca`;
  const nc = { success:'#10b981', warning:'#f59e0b', danger:'#ef4444', info:'#00d4ff' };
  document.getElementById('notifList').innerHTML = sliced.map(n =>
    `<div class="notif-item ${n.is_read?'':'unread'}" onclick="markRead('${n.id}')">
      <div class="notif-dot" style="background:${nc[n.type]||'#64748b'}"></div>
      <div style="flex:1">
        <div style="font-size:12px">${n.message}</div>
        <div style="font-size:10px;color:var(--gray)">📦 ${n.container_id||'-'} · ${formatDateTime(n.created_at)}</div>
      </div>
      ${!n.is_read?'<span style="font-size:10px;color:var(--cyan)">BARU</span>':''}
    </div>`
  ).join('') || '<div style="color:var(--gray);text-align:center;padding:30px">Tidak ada notifikasi</div>';
  document.getElementById('pg-notif').innerHTML = buildPagination(notifs.length, page, 'renderNotifications');
}

async function markRead(id) { await API.markRead(id); await renderNotifications(); await updateBadges(); }
async function markAllRead() { await API.markRead('all'); await renderNotifications(); await updateBadges(); }

// ── REPORTS
async function renderReports() {
  const month = document.getElementById('rptMonth').value;
  const year  = document.getElementById('rptYear').value;

  const allCtrs = await API.getContainers();
  const stats   = await API.getStats();

  let filtered = allCtrs.filter(c => {
    const d = new Date(c.created_at);
    if (month && d.getMonth() + 1 !== parseInt(month)) return false;
    if (year  && d.getFullYear() !== parseInt(year))   return false;
    return true;
  });

  _allReportData = filtered;

  document.getElementById('reportTableInfo').textContent = `${filtered.length} data ditemukan`;

  const total     = filtered.length;
  const completed = filtered.filter(c=>c.status==='completed').length;
  document.getElementById('reportStats').innerHTML = `
    <div class="stat-card"><div class="stat-label">Total (Filter)</div><div class="stat-value">${total}</div><div class="stat-icon">📦</div></div>
    <div class="stat-card"><div class="stat-label">Selesai</div><div class="stat-value" style="color:var(--green)">${completed}</div><div class="stat-icon">✅</div></div>
    <div class="stat-card"><div class="stat-label">Completion Rate</div><div class="stat-value">${total>0?Math.round(completed/total*100):0}%</div><div class="stat-icon">📊</div></div>
    <div class="stat-card"><div class="stat-label">Dok Pending</div><div class="stat-value" style="color:var(--gold)">${stats.pending_docs}</div><div class="stat-icon">📄</div></div>
  `;

  const byStatus = {};
  filtered.forEach(c => { byStatus[c.status] = (byStatus[c.status]||0) + 1; });
  const ctx = document.getElementById('reportChart').getContext('2d');
  if (rptChart) rptChart.destroy();
  rptChart = new Chart(ctx, {
    type: 'bar',
    data: { labels: Object.keys(byStatus).map(k=>STATUS_CONFIG[k]?.label||k),
      datasets:[{ label:'Jumlah', data:Object.values(byStatus),
        backgroundColor:['#6366f1','#0891b2','#2563eb','#f59e0b','#d97706','#7c3aed','#10b981','#ef4444'], borderRadius:6 }] },
    options:{ responsive:true, plugins:{legend:{display:false}}, scales:{x:{ticks:{color:'#64748b'}}, y:{ticks:{color:'#64748b',stepSize:1}}} }
  });

  const vessels = [...new Set(filtered.map(c=>c.vessel))];
  document.getElementById('vesselPerf').innerHTML = vessels.map(v => {
    const vCtrs = filtered.filter(c=>c.vessel===v);
    const done  = vCtrs.filter(c=>c.status==='completed').length;
    const pct   = vCtrs.length>0?Math.round(done/vCtrs.length*100):0;
    return `<div style="margin-bottom:12px">
      <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px">
        <span style="color:var(--white)">🚢 ${v}</span><span style="color:var(--gray)">${done}/${vCtrs.length} (${pct}%)</span>
      </div>
      <div class="progress-wrap"><div class="progress-fill" style="width:${pct}%;background:linear-gradient(90deg,var(--blue),var(--cyan))"></div></div>
    </div>`;
  }).join('') || '<div style="color:var(--gray);font-size:12px">Tidak ada data</div>';

  document.getElementById('reportTable').innerHTML = filtered.map(c =>
    `<tr>
      <td class="mono">${c.id}</td>
      <td style="font-size:11px;color:var(--gray)">${c.booking_no||'-'}</td>
      <td style="font-size:11px">${c.vessel||'-'}</td>
      <td style="font-size:11px">${c.origin||'-'}</td>
      <td style="font-size:11px">${c.destination||'-'}</td>
      <td style="font-size:11px">${c.type||'-'}</td>
      <td style="font-size:11px">${c.commodity||'-'}</td>
      <td style="font-size:11px;text-align:right">${Number(c.weight||0).toLocaleString()}</td>
      <td>${statusBadge(c.status)}</td>
      <td style="font-size:11px;color:var(--gray)">${formatDate(c.eta)}</td>
      <td style="font-size:11px;color:var(--gray)">${formatDate(c.created_at)}</td>
    </tr>`
  ).join('') || `<tr><td colspan="11" style="text-align:center;padding:30px;color:var(--gray)">Tidak ada data untuk filter ini</td></tr>`;
}

// ── DOWNLOAD EXCEL
async function downloadExcel() {
  if (_allReportData.length === 0) {
    await renderReports();
  }
  if (_allReportData.length === 0) {
    showToast('Tidak ada data untuk diunduh', 'warning'); return;
  }

  const month = document.getElementById('rptMonth').value;
  const year  = document.getElementById('rptYear').value;
  const monthNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  const filterLabel = `${month?monthNames[parseInt(month)]+'-':''}${year||'Semua'}`;

  const headers = ['ID Kontainer','Booking No','Vessel','Asal','Tujuan','Tipe','Komoditi','Berat (kg)','Status','ETA','Tanggal Dibuat'];
  const rows = _allReportData.map(c => [
    c.id, c.booking_no||'', c.vessel||'', c.origin||'', c.destination||'',
    c.type||'', c.commodity||'', c.weight||0,
    STATUS_CONFIG[c.status]?.label || c.status,
    c.eta ? c.eta.split('T')[0] : '',
    c.created_at ? c.created_at.split('T')[0] : ''
  ]);

  const wsData = [headers, ...rows];
  const ws     = XLSX.utils.aoa_to_sheet(wsData);

  ws['!cols'] = [16,14,20,14,14,12,16,10,18,12,14].map(w=>({wch:w}));

  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Laporan Kontainer');
  XLSX.writeFile(wb, `Laporan_CMS_${filterLabel}.xlsx`);
  showToast(`📥 File Laporan_CMS_${filterLabel}.xlsx berhasil diunduh!`);
}

// ── BADGES
async function updateBadges() {
  const stats = await API.getStats();
  document.getElementById('nbContainer').textContent = stats.by_status?.discharged || 0;
  document.getElementById('nbDoc').textContent       = stats.pending_docs || 0;
  const nbNotif = document.getElementById('nbNotif');
  if (nbNotif) {
      nbNotif.textContent = stats.unread_notifs || 0;
      nbNotif.style.background = (stats.unread_notifs > 0) ? 'var(--red)' : '';
  }
}

// ── MODAL CLOSE
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}
document.querySelectorAll('.modal-overlay').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) closeModal(m.id); });
});

async function downloadDocExcel() {
  if (!_allDocData || _allDocData.length === 0) {
    alert('Tidak ada data dokumen untuk diunduh'); return;
  }
  
  const filterDate = document.getElementById('docDate')?.value || '';
  const filterLabel = filterDate ? filterDate : 'Semua';

  const headers = ['ID Dokumen', 'ID Kontainer', 'Vessel', 'Tipe Dokumen', 'Status', 'Catatan', 'Tanggal Dibuat', 'Link File'];
  const rows = _allDocData.map(d => [
    d.id,
    d.container_id || '',
    d.vessel || '',
    d.type || '',
    d.status || '',
    d.notes || '',
    d.created_at ? d.created_at.split('T').join(' ').split('.')[0] : '',
    d.filepath || ''
  ]);

  const wsData = [headers, ...rows];
  const ws = XLSX.utils.aoa_to_sheet(wsData);
  ws['!cols'] = [12, 16, 20, 20, 12, 25, 18, 50].map(w=>({wch:w}));

  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Laporan Dokumen');
  XLSX.writeFile(wb, `Laporan_Dokumen_CMS_${filterLabel}.xlsx`);
}


window.openDocPreview = function(filepath, type, id) {
  document.getElementById('previewSub').textContent = `Dokumen: ${type} (${id})`;
  const ext = filepath.split('.').pop().toLowerCase();
  let html = '';
  if (['png','jpg','jpeg','gif'].includes(ext)) {
    html = `<img src="${filepath}" style="max-width:100%; max-height:100%; object-fit:contain;">`;
  } else {
    // try object for pdf for better compatibility over iframe
    if(ext === 'pdf') {
        html = `<object data="${filepath}" type="application/pdf" width="100%" height="100%">
                  <iframe src="${filepath}" style="width:100%; height:100%; border:none;"></iframe>
                </object>`;
    } else {
        html = `<iframe src="${filepath}" style="width:100%; height:100%; border:none;"></iframe>`;
    }
  }
  document.getElementById('previewContainer').innerHTML = html;
  document.getElementById('previewDownloadBtn').href = filepath;
  document.getElementById('modalDocPreview').classList.add('open');
};