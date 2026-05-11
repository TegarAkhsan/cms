let currentUser, mapInstance = null, editCtrId = null;

document.addEventListener('DOMContentLoaded', async () => {
  const check = await API.check();
  if (!check.authenticated || check.user.role !== 'stakeholder') { window.location.href = '../auth/login.html'; return; }
  currentUser = check.user;
  document.getElementById('userName').textContent = currentUser.name;
  document.getElementById('userInitial').textContent = currentUser.name[0];
  document.getElementById('dateDisplay').textContent = new Date().toLocaleDateString('id-ID', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
  await renderDashboard();
  setAutoRefresh(renderDashboard);
});

const ITEMS_PER_PAGE = 10;
function buildPagination(total, page, callbackName) {
  const pages = Math.ceil(total / ITEMS_PER_PAGE);
  if (pages <= 1) return '';
  let html = `<button class="btn btn-sm btn-ghost" onclick="${callbackName}(${page - 1})" ${page === 1 ? 'disabled' : ''}>&laquo;</button>`;
  for (let i = 1; i <= pages; i++) html += `<button class="btn btn-sm ${i === page ? 'btn-primary' : 'btn-ghost'}" onclick="${callbackName}(${i})">${i}</button>`;
  html += `<button class="btn btn-sm btn-ghost" onclick="${callbackName}(${page + 1})" ${page === pages ? 'disabled' : ''}>&raquo;</button>`;
  return html;
}

async function showSection(name) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  document.getElementById('sec-' + name).classList.add('active');
  document.querySelector(`.nav-item[onclick*="${name}"]`).classList.add('active');
  if (name === 'containers') await renderContainers();
  if (name === 'documents') await renderDocuments();
  if (name === 'tracking') await renderTracking();
  if (name === 'notifications') await renderNotifications();
}

let statusChartInst = null;
let typeChartInst = null;

async function renderDashboard() {
  const [stats, notifs, ctrs, docs] = await Promise.all([API.getStats(), API.getNotifications(), API.getContainers(), API.getDocuments()]);
  document.getElementById('kpiTotal').textContent = stats.total || ctrs.length;
  document.getElementById('kpiActive').textContent = stats.in_transit || ctrs.filter(c => c.status !== 'completed').length;
  document.getElementById('kpiDone').textContent = stats.completed || ctrs.filter(c => c.status === 'completed').length;

  // Update Badges
  const unreadNotifs = notifs.notifications.filter(n => !n.is_read).length;
  document.getElementById('nbNotif').textContent = unreadNotifs;
  document.getElementById('nbNotif').style.display = unreadNotifs > 0 ? 'block' : 'none';
  
  const pendingDocs = docs.filter(d => d.status === 'pending').length;
  document.getElementById('nbDoc').textContent = pendingDocs;
  document.getElementById('nbDoc').style.display = pendingDocs > 0 ? 'block' : 'none';

  document.getElementById('dashNotif').innerHTML = notifs.notifications.slice(0, 4).map(n => `
    <div class="notif-item ${n.is_read ? '' : 'unread'}">
      <div class="notif-dot" style="background:${(STATUS_CONFIG[n.type] || {}).color || '#64748b'}"></div>
      <div><div style="font-size:12px">${n.message}</div><div style="font-size:10px;color:var(--gray)">${formatDateTime(n.created_at)}</div></div>
    </div>
  `).join('') || '<div style="color:var(--gray);font-size:12px">Tidak ada Notifikasi</div>';

  // Render Active Containers List
  const activeCtrs = ctrs.filter(c => c.status !== 'completed').slice(0, 5);
  document.getElementById('ctrCards').innerHTML = activeCtrs.map(c => `
    <div class="ctr-card" onclick="viewDetail('${c.id}')">
      <div style="display:flex;justify-content:space-between;margin-bottom:8px">
        <span class="mono" style="font-size:12px;color:var(--white);font-weight:600;">${c.id}</span>
        ${statusBadge(c.status)}
      </div>
      <div style="font-size:11px;color:var(--gray);margin-bottom:4px">🚢 ${c.vessel}</div>
      <div style="font-size:11px;color:var(--gray)">📍 ${c.origin} &rarr; ${c.destination}</div>
    </div>
  `).join('') || '<div style="color:var(--gray);font-size:12px;text-align:center;padding:20px">Tidak ada kontainer aktif</div>';

  // Prepare Chart Data
  const statusCounts = {};
  const typeCounts = { 'Ekspor': 0, 'Impor': 0 };

  ctrs.forEach(c => {
    statusCounts[c.status] = (statusCounts[c.status] || 0) + 1;
    if (c.booking_status === 'Ekspor' || c.booking_status === 'Impor') {
      typeCounts[c.booking_status]++;
    }
  });

  const chartOpts = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'right', labels: { color: '#cbd5e1', font: { family: "'Space Grotesk', sans-serif", size: 10 }, boxWidth: 12 } }
    }
  };

  if (statusChartInst) statusChartInst.destroy();
  const ctxStatus = document.getElementById('statusChart');
  if (ctxStatus) {
    statusChartInst = new Chart(ctxStatus.getContext('2d'), {
      type: 'doughnut',
      data: {
        labels: Object.keys(statusCounts).map(k => (STATUS_CONFIG[k] || {}).label || k),
        datasets: [{
          data: Object.values(statusCounts),
          backgroundColor: Object.keys(statusCounts).map(k => (STATUS_CONFIG[k] || {}).color || '#64748b'),
          borderWidth: 0,
          hoverOffset: 4
        }]
      },
      options: { ...chartOpts, cutout: '70%' }
    });
  }

  if (typeChartInst) typeChartInst.destroy();
  const ctxType = document.getElementById('typeChart');
  if (ctxType) {
    typeChartInst = new Chart(ctxType.getContext('2d'), {
      type: 'pie',
      data: {
        labels: ['Ekspor', 'Impor'],
        datasets: [{
          data: [typeCounts['Ekspor'], typeCounts['Impor']],
          backgroundColor: ['#2563eb', '#10b981'],
          borderWidth: 0,
          hoverOffset: 4
        }]
      },
      options: chartOpts
    });
  }
}

async function renderTracking() {
  const ctrs = await API.getContainers();
  const active = ctrs.filter(c => c.status !== 'completed');
  
  if (!mapInstance) {
    mapInstance = L.map('trackMap').setView([-7.2000, 112.7350], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(mapInstance);
  } else { 
    mapInstance.eachLayer(l => { if (l instanceof L.Marker) l.remove(); }); 
  }

  // Simulation Zones
  const ZONES = {
    booking:        [-7.2150, 112.7300],
    gate_in:        [-7.2100, 112.7350],
    ship_arrival:   [-7.1850, 112.7450],
    discharge:      [-7.1950, 112.7380],
    yard_map:       [-7.2020, 112.7350],
    clearance:      [-7.2020, 112.7350],
    loading:        [-7.2020, 112.7350],
    ship_departure: [-7.1800, 112.7500],
    delivery:       [-7.2200, 112.7200],
  };

  active.forEach(c => {
    const s = STATUS_CONFIG[c.status] || {};
    const basePos = ZONES[c.status] || [-7.2000, 112.7350];
    const lat = basePos[0] + (Math.random() - 0.5) * 0.0018;
    const lng = basePos[1] + (Math.random() - 0.5) * 0.0018;

    const icon = L.divIcon({ 
      html: `
        <div style="background:${s.color || 'var(--cyan)'}; color:white; padding:4px 12px; border-radius:20px; font-size:10px; font-weight:700; box-shadow:0 4px 12px rgba(0,0,0,.5); border:2px solid rgba(255,255,255,.3); white-space:nowrap; transform:translate(-50%, -50%);">
          ${s.icon} ${c.id}
        </div>`, 
      className: '', 
      iconAnchor: [0, 0] 
    });

    L.marker([lat, lng], { icon }).addTo(mapInstance).bindPopup(`
      <div style="font-family:var(--font); color:#333; min-width:140px">
        <b style="color:var(--bg2); font-size:13px">${c.id}</b><br>
        <span style="font-size:11px;color:#666">🚢 ${c.vessel || '-'}</span><br>
        <div style="margin-top:8px; padding:4px 10px; border-radius:10px; background:${s.bg}; color:${s.color}; font-size:11px; font-weight:700; display:inline-block">
          ${s.icon} ${s.label}
        </div>
      </div>
    `);
  });

  const exportSteps = [
    { key: 'booking', label: 'Booking' },
    { key: 'gate_in', label: 'Gate In' },
    { key: 'yard_map', label: 'Yard Map' },
    { key: 'clearance', label: 'Clearance' },
    { key: 'loading', label: 'Loading' },
    { key: 'ship_departure', label: 'Departure' },
    { key: 'completed', label: 'Selesai' }
  ];

  const importSteps = [
    { key: 'booking', label: 'Booking' },
    { key: 'ship_arrival', label: 'Arrival' },
    { key: 'discharge', label: 'Discharge' },
    { key: 'yard_map', label: 'Yard Map' },
    { key: 'clearance', label: 'Clearance' },
    { key: 'delivery', label: 'Delivery' },
    { key: 'completed', label: 'Selesai' }
  ];

  document.getElementById('trackList').innerHTML = active.map(c => {
    const s = STATUS_CONFIG[c.status] || {};
    const steps = c.booking_status === 'Impor' ? importSteps : exportSteps;
    const idx = steps.findIndex(st => st.key === c.status);
    return `<div style="padding:12px;border:1px solid var(--border);border-radius:12px;margin-bottom:10px">
      <div style="display:flex;justify-content:space-between;margin-bottom:6px"><span class="mono">${c.id}</span>${statusBadge(c.status)}</div>
      <div style="font-size:11px;color:var(--gray)">📍 ${c.position_desc} (${c.booking_status})</div>
      <div style="display:flex;gap:2px;margin-top:8px">
        ${steps.map((st, i) => `<div style="flex:1;height:4px;border-radius:2px;background:${i < idx ? 'var(--green)' : i === idx ? 'var(--cyan)' : 'rgba(255,255,255,.1)'}" title="${st.label}"></div>`).join('')}
      </div>
      <button class="btn btn-ghost btn-sm" style="margin-top:8px" onclick="viewDetail('${c.id}')">👁 Timeline</button>
    </div>`;
  }).join('') || '<div style="color:var(--gray);font-size:12px;text-align:center;padding:20px">Tidak ada kontainer aktif</div>';
}

async function renderContainers(page = 1) {
  const data = await API.getContainers({ search: document.getElementById('ctrSearch').value });
  const start = (page - 1) * ITEMS_PER_PAGE;
  const sliced = data.slice(start, start + ITEMS_PER_PAGE);
  document.getElementById('containerTable').innerHTML = sliced.map(c => `
    <tr><td class="mono">${c.id}</td>
    <td style="font-size:11px;color:var(--gray)">${c.booking_no}</td>
    <td style="font-size:11px">${c.vessel}</td>
    <td style="font-size:11px">${c.commodity}<br><span style="color:var(--gray);font-size:10px">${c.type}</span></td>
    <td style="font-size:11px">${c.booking_status || '-'}</td>
    <td style="font-size:11px">${formatDate(c.eta)}</td>
    <td>${statusBadge(c.status)}</td>
    <td>
      <button class="btn btn-ghost btn-sm" title="View" onclick="viewDetail('${c.id}')">👁</button>
      <button class="btn btn-ghost btn-sm" title="Edit/Update" onclick="openEditContainer('${c.id}')">✏️</button>
    </td></tr>
  `).join('') || '<tr><td colspan="7" style="text-align:center;padding:30px;color:var(--gray)">Tidak ada data</td></tr>';
  document.getElementById('pg-container').innerHTML = buildPagination(data.length, page, 'renderContainers');
}

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
      const dDate = parseSafeDate(d.created_at);
      const localDate = dDate.getFullYear() + '-' + String(dDate.getMonth() + 1).padStart(2, '0') + '-' + String(dDate.getDate()).padStart(2, '0');
      if (localDate !== filterDate) return false;
    }
    return true;
  });

  _allDocData = filtered;

  const start = (page - 1) * ITEMS_PER_PAGE;
  const sliced = filtered.slice(start, start + ITEMS_PER_PAGE);
  document.getElementById('documentTable').innerHTML = sliced.map(d => `
    <tr><td class="mono">${d.id}</td>
    <td style="font-size:12px"><span class="mono">${d.container_id}</span><br>${d.vessel || ''}</td>
    <td style="font-size:11px">📄 ${d.type}</td>
    <td style="font-size:11px">${d.booking_status || '-'}</td>
    <td>${docBadge(d.status)}</td>
    <td style="font-size:11px;color:var(--gray)">${formatDate(d.created_at)}</td>
    <td style="font-size:11px;max-width:150px">${d.notes || '-'}</td>
    <td>
      ${d.filepath ? `<button class="btn btn-ghost btn-sm" title="View" onclick="openDocPreview('${API.resolveUrl(d.filepath).replace(/'/g, "\\'")}', '${d.type.replace(/'/g, "\\'")}', '${d.id.replace(/'/g, "\\'")}')">👁</button>` : `<button class="btn btn-ghost btn-sm" title="Tidak ada file" disabled style="opacity:0.5">👁</button>`}
      ${d.filepath ? `<a href="${API.resolveUrl(d.filepath)}" target="_blank" class="btn btn-ghost btn-sm" title="Download">📥</a>` : ''}
      <button class="btn btn-ghost btn-sm" title="Edit/Update" onclick="openEditDoc('${d.id}')">✏️</button>
    </td></tr>
  `).join('') || '<tr><td colspan="7" style="text-align:center;padding:30px;color:var(--gray)">Belum ada dokumen</td></tr>';
  document.getElementById('pg-document').innerHTML = buildPagination(filtered.length, page, 'renderDocuments');
}

async function openAddContainer() {
  const yr = new Date().getFullYear();
  const ctrs = await API.getContainers();

  document.getElementById('f_id').value = '';
  editCtrId = null;
  document.getElementById('f_id').readOnly = false;
  document.getElementById('modalContainerTitle').textContent = 'Tambah Kontainer';
  document.getElementById('btnSaveContainer').textContent = '🚀 Daftarkan Kontainer';

  const now = new Date();
  const ymd = now.getFullYear() + String(now.getMonth() + 1).padStart(2, '0') + String(now.getDate()).padStart(2, '0');
  const todayBkNums = ctrs.map(c => {
    if (c.booking_no && c.booking_no.startsWith(`CMS-${ymd}-`)) return parseInt(c.booking_no.split('-')[2]);
    return 0;
  }).filter(n => !isNaN(n));
  const bkNext = todayBkNums.length > 0 ? Math.max(...todayBkNums) + 1 : 1;
  document.getElementById('f_booking').value = `CMS-${ymd}-${String(bkNext).padStart(5, '0')}`;
  document.getElementById('hint_bk').textContent = `Nomor Booking hari ini`;

  document.getElementById('f_weight').value = '';
  document.getElementById('f_commodity').value = '';
  document.getElementById('f_eta').value = '';
  document.getElementById('f_origin').value = '';
  document.getElementById('f_dest').value = '';
  document.getElementById('modalContainer').classList.add('open');
}

let isSavingContainer = false;
async function saveContainer() {
  if (isSavingContainer) return;
  isSavingContainer = true;
  try {
    const id = document.getElementById('f_id').value.trim();
    if (!id) { alert('ID Kontainer harus diisi manual'); return; }
    const data = {
      id,
      booking_no: document.getElementById('f_booking').value,
      booking_status: document.getElementById('f_booking_status').value,
      vessel: document.getElementById('f_vessel').value,
      origin: document.getElementById('f_origin').value,
      destination: document.getElementById('f_dest').value,
      type: document.getElementById('f_type').value,
      weight: parseInt(document.getElementById('f_weight').value) || 0,
      commodity: document.getElementById('f_commodity').value,
      eta: document.getElementById('f_eta').value
    };
    if (editCtrId) {
      data.id = editCtrId;
      const res = await API.updateContainer(data);
      if (res.error) { alert(res.error); return; }
      closeModal('modalContainer');
      await renderContainers();
      await renderDashboard(); // Update notifications and stats
      showSuccessModal('Kontainer berhasil diupdate!');
    } else {
      data.status = 'booking';
      const res = await API.createContainer(data);
      if (res.error) { alert(res.error); return; }
      closeModal('modalContainer');
      await renderContainers();
      await renderDashboard(); // Update notifications and stats
      showSuccessModal('Kontainer berhasil didaftarkan!');
    }
  } finally {
    isSavingContainer = false;
  }
}

async function openEditContainer(id) {
  const c = await API.getContainer(id);
  editCtrId = id;
  document.getElementById('modalContainerTitle').textContent = 'Edit Kontainer';
  document.getElementById('btnSaveContainer').textContent = '💾 Simpan Perubahan';
  document.getElementById('f_id').value = c.id;
  document.getElementById('f_id').readOnly = true;
  document.getElementById('f_booking').value = c.booking_no;
  document.getElementById('f_booking_status').value = c.booking_status || 'Ekspor';
  document.getElementById('f_vessel').value = c.vessel;
  document.getElementById('f_origin').value = c.origin;
  document.getElementById('f_dest').value = c.destination;
  document.getElementById('f_type').value = c.type;
  document.getElementById('f_weight').value = c.weight;
  document.getElementById('f_commodity').value = c.commodity;
  document.getElementById('f_eta').value = c.eta ? c.eta.split(' ')[0] : '';
  document.getElementById('modalContainer').classList.add('open');
}

function showSuccessModal(msg) {
  if (typeof showToast === 'function') {
    showToast(msg, 'success');
  } else {
    alert(msg);
  }
}

async function openUploadDoc() {
  const ctrs = await API.getContainers();
  editDocId = null;
  document.getElementById('modalUploadTitle').textContent = 'Upload Dokumen';
  document.getElementById('btnSaveDoc').textContent = 'Submit ke Operator';
  document.getElementById('docContainer').innerHTML = ctrs.map(c => `<option value="${c.id}">${c.id} — ${c.vessel}</option>`).join('');
  document.getElementById('docContainer').disabled = false;
  document.getElementById('docContainer').style.opacity = '1';
  document.getElementById('docNotes').value = '';
  document.getElementById('docFile').value = '';
  document.getElementById('modalUpload').classList.add('open');
}

let isSavingDoc = false;
async function saveDoc() {
  if (isSavingDoc) return;
  isSavingDoc = true;
  try {
    const formData = new FormData();
    if (editDocId) formData.append('id', editDocId);
    formData.append('container_id', document.getElementById('docContainer').value);
    formData.append('type', document.getElementById('docType').value);
    formData.append('notes', document.getElementById('docNotes').value);
    const fileInput = document.getElementById('docFile');
    if (fileInput.files[0]) formData.append('file', fileInput.files[0]);

    const res = await fetch(API.base + '/documents.php', { method: 'POST', credentials: 'include', body: formData });
    const data = await res.json();
    if (data.error) { alert(data.error); return; }
    closeModal('modalUpload');
    await renderDocuments();
    await renderDashboard(); // Update notifications
    showSuccessModal(editDocId ? 'Dokumen berhasil diupdate!' : 'Dokumen berhasil diupload!');
  } finally {
    isSavingDoc = false;
  }
}

async function viewDetail(id) {
  const c = await API.getContainer(id);
  document.getElementById('detailTitle').textContent = `Detail: ${c.id}`;
  document.getElementById('detailSub').textContent = `${c.booking_no} · ${c.vessel} (${c.booking_status})`;
  document.getElementById('detailContent').innerHTML = `
    <div style="margin-bottom:10px">${statusBadge(c.status)} <span style="font-size:11px;color:var(--gray);margin-left:8px">📍 ${c.position_desc || '-'}</span></div>
    <div style="margin-bottom:12px;font-size:11px;line-height:1.6">
        <div style="display:flex"><div style="width:100px;color:var(--gray)">Komoditi:</div><div>${c.commodity || '-'} (${c.type || '-'})</div></div>
        <div style="display:flex"><div style="width:100px;color:var(--gray)">Berat:</div><div>${Number(c.weight || 0).toLocaleString()} kg</div></div>
        <div style="display:flex"><div style="width:100px;color:var(--gray)">Tujuan:</div><div>${c.origin} &rarr; ${c.destination}</div></div>
        <div style="display:flex"><div style="width:100px;color:var(--gray)">Status Booking:</div><div>${c.booking_status || '-'}</div></div>
        <div class="box">
          <div style="font-size:12px;font-weight:700;color:var(--white);margin-bottom:8px">Timeline (${c.booking_status || 'Ekspor'})</div>
          <div class="timeline">${(c.events || []).map((e, i, a) => `<div class="tl-item ${i === a.length - 1 ? 'active' : 'done'}"><div class="tl-event">${e.event}</div><div class="tl-meta">${e.actor} · ${formatDateTime(e.timestamp)}</div></div>`).join('')}</div>
        </div>
    </div>
  `;
  document.getElementById('modalDetail').classList.add('open');
}

function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m => { m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); }); });

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
  ws['!cols'] = [12, 16, 20, 20, 12, 25, 18, 50].map(w => ({ wch: w }));

  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Laporan Dokumen');
  XLSX.writeFile(wb, `Laporan_Dokumen_CMS_${filterLabel}.xlsx`);
}





async function openEditDoc(id) {
  const doc = _allDocData.find(d => d.id === id);
  if (!doc) { alert('Dokumen tidak ditemukan'); return; }

  editDocId = id;
  document.getElementById('modalUploadTitle').textContent = 'Edit Dokumen';
  document.getElementById('btnSaveDoc').textContent = '💾 Simpan Perubahan';
  
  document.getElementById('docContainer').innerHTML = `<option value="${doc.container_id}">${doc.container_id}</option>`;
  document.getElementById('docContainer').disabled = true;
  document.getElementById('docContainer').style.opacity = '0.7';
  
  document.getElementById('docType').value = doc.type;
  document.getElementById('docNotes').value = doc.notes || '';
  document.getElementById('docFile').value = '';

  document.getElementById('modalUpload').classList.add('open');
}

window.openDocPreview = function (filepath, type, id) {
  document.getElementById('previewSub').textContent = `Dokumen: ${type} (${id})`;
  const ext = filepath.split('.').pop().toLowerCase();
  let html = '';
  if (['png', 'jpg', 'jpeg', 'gif'].includes(ext)) {
    html = `<img src="${filepath}" style="max-width:100%; max-height:100%; object-fit:contain;">`;
  } else {
    if (ext === 'pdf') {
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
async function renderNotifications(page = 1) {
  const data = await API.getNotifications();
  const notifs = data.notifications || [];
  const start = (page - 1) * ITEMS_PER_PAGE;
  const sliced = notifs.slice(start, start + ITEMS_PER_PAGE);

  const unreadCount = notifs.filter(n => !n.is_read).length;
  const countEl = document.getElementById('unreadCount');
  if (countEl) countEl.textContent = unreadCount > 0 ? `(${unreadCount} Baru)` : '';

  document.getElementById('notifList').innerHTML = sliced.map(n => `
    <div class="notif-item ${n.is_read ? '' : 'unread'}" onclick="handleNotifClick('${n.id}', '${n.container_id}')">
      <div class="notif-dot" style="background:${(STATUS_CONFIG[n.type] || {}).color || '#64748b'}"></div>
      <div style="flex:1">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px">
          <span style="font-size:13px;font-weight:600;color:var(--white)">${n.container_id || 'Sistem'}</span>
          <span style="font-size:10px;color:var(--gray)">${formatDateTime(n.created_at)}</span>
        </div>
        <div style="font-size:12px;color:var(--text);line-height:1.4">${n.message}</div>
      </div>
    </div>
  `).join('') || '<div style="text-align:center;padding:40px;color:var(--gray)">Tidak ada notifikasi</div>';

  document.getElementById('pg-notif').innerHTML = buildPagination(notifs.length, page, 'renderNotifications');
}

async function handleNotifClick(id, containerId) {
  await API.markRead(id);
  await renderDashboard();
  if (containerId) {
    viewDetail(containerId);
  }
  if (document.getElementById('sec-notifications').classList.contains('active')) {
    renderNotifications();
  }
}

async function markAllRead() {
  await API.markRead('all');
  await renderDashboard();
  if (document.getElementById('sec-notifications').classList.contains('active')) {
    renderNotifications();
  }
  showSuccessModal('Semua notifikasi ditandai telah dibaca');
}
