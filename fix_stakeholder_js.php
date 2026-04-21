<?php
$content = <<<'EOD'
let currentUser, mapInstance=null;

document.addEventListener('DOMContentLoaded', async () => {
  const check = await API.check();
  if (!check.authenticated || check.user.role !== 'stakeholder') { window.location.href='../auth/login.html'; return; }
  currentUser = check.user;
  document.getElementById('userName').textContent = currentUser.name;
  document.getElementById('userInitial').textContent = currentUser.name[0];
  document.getElementById('dateDisplay').textContent = new Date().toLocaleDateString('id-ID',{weekday:'short',day:'2-digit',month:'short',year:'numeric'});
  await renderDashboard();
  setAutoRefresh(renderDashboard);
});

const ITEMS_PER_PAGE = 10;
function buildPagination(total, page, callbackName) {
  const pages = Math.ceil(total / ITEMS_PER_PAGE);
  if(pages <= 1) return '';
  let html = `<button class="btn btn-sm btn-ghost" onclick="${callbackName}(${page - 1})" ${page===1?'disabled':''}>&laquo;</button>`;
  for(let i=1; i<=pages; i++) html += `<button class="btn btn-sm ${i===page?'btn-primary':'btn-ghost'}" onclick="${callbackName}(${i})">${i}</button>`;
  html += `<button class="btn btn-sm btn-ghost" onclick="${callbackName}(${page + 1})" ${page===pages?'disabled':''}>&raquo;</button>`;
  return html;
}

async function showSection(name) {
  document.querySelectorAll('.section').forEach(s=>s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
  document.getElementById('sec-'+name).classList.add('active');
  document.querySelector(`.nav-item[onclick*="${name}"]`).classList.add('active');
  if(name==='containers') await renderContainers();
  if(name==='documents') await renderDocuments();
  if(name==='tracking') await renderTracking();
}

async function renderDashboard() {
  const [stats, notifs] = await Promise.all([API.getStats(), API.getNotifications()]);
  document.getElementById('kpiTotal').textContent=stats.total;
  document.getElementById('kpiActive').textContent=stats.in_transit;
  document.getElementById('kpiDone').textContent=stats.completed;
  document.getElementById('dashNotif').innerHTML=notifs.notifications.slice(0,4).map(n=>`
    <div class="notif-item ${n.is_read?'':'unread'}">
      <div class="notif-dot" style="background:${(STATUS_CONFIG[n.type]||{}).color||'#64748b'}"></div>
      <div><div style="font-size:12px">${n.message}</div><div style="font-size:10px;color:var(--gray)">${formatDateTime(n.created_at)}</div></div>
    </div>
  `).join('')||'<div style="color:var(--gray);font-size:12px">Tidak ada Notifikasi</div>';
}

async function renderTracking() {
  const ctrs=await API.getContainers();
  const active=ctrs.filter(c=>c.status!=='completed');
  if(!mapInstance){
    mapInstance=L.map('trackMap').setView([-7.2575,112.7521],8);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap'}).addTo(mapInstance);
  } else {mapInstance.eachLayer(l=>{if(l instanceof L.Marker)l.remove();});}
  active.forEach(c=>{
    const s=STATUS_CONFIG[c.status]||{};
    const icon=L.divIcon({html:`<div style="background:${s.color||'#2563eb'};color:white;padding:4px 10px;border-radius:20px;font-size:10px;font-weight:700;box-shadow:0 3px 10px rgba(0,0,0,.4)">${s.icon} ${c.id}</div>`,className:'',iconAnchor:[0,0]});
    L.marker([parseFloat(c.position_lat)||(-7.2575),parseFloat(c.position_lng)||112.7521],{icon}).addTo(mapInstance).bindPopup(`<b>${c.id}</b><br>${c.vessel}<br>${c.position_desc}`);
  });

  const exportSteps = [
    {key:'booking', label:'Booking'},
    {key:'gate_in', label:'Gate In'},
    {key:'yard_map', label:'Yard Map'},
    {key:'clearance', label:'Clearance'},
    {key:'loading', label:'Loading'},
    {key:'ship_departure', label:'Departure'},
    {key:'completed', label:'Selesai'}
  ];
  
  const importSteps = [
    {key:'booking', label:'Booking'},
    {key:'ship_arrival', label:'Arrival'},
    {key:'discharge', label:'Discharge'},
    {key:'yard_map', label:'Yard Map'},
    {key:'clearance', label:'Clearance'},
    {key:'delivery', label:'Delivery'},
    {key:'completed', label:'Selesai'}
  ];

  document.getElementById('trackList').innerHTML=active.map(c=>{
    const s=STATUS_CONFIG[c.status]||{};
    const steps = c.booking_status === 'Impor' ? importSteps : exportSteps;
    const idx=steps.findIndex(st=>st.key===c.status);
    return `<div style="padding:12px;border:1px solid var(--border);border-radius:12px;margin-bottom:10px">
      <div style="display:flex;justify-content:space-between;margin-bottom:6px"><span class="mono">${c.id}</span>${statusBadge(c.status)}</div>
      <div style="font-size:11px;color:var(--gray)">📍 ${c.position_desc} (${c.booking_status})</div>
      <div style="display:flex;gap:2px;margin-top:8px">
        ${steps.map((st,i)=>`<div style="flex:1;height:4px;border-radius:2px;background:${i<idx?'var(--green)':i===idx?'var(--cyan)':'rgba(255,255,255,.1)'}" title="${st.label}"></div>`).join('')}
      </div>
      <button class="btn btn-ghost btn-sm" style="margin-top:8px" onclick="viewDetail('${c.id}')">👁 Timeline</button>
    </div>`;
  }).join('')||'<div style="color:var(--gray);font-size:12px;text-align:center;padding:20px">Tidak ada kontainer aktif</div>';
}

async function renderContainers(page = 1) {
  const data=await API.getContainers({search:document.getElementById('ctrSearch').value});
  const start = (page - 1) * ITEMS_PER_PAGE;
  const sliced = data.slice(start, start + ITEMS_PER_PAGE);
  document.getElementById('containerTable').innerHTML=sliced.map(c=>`
    <tr><td class="mono">${c.id}</td>
    <td style="font-size:11px;color:var(--gray)">${c.booking_no}</td>
    <td style="font-size:11px">${c.vessel}</td>
    <td style="font-size:11px">${c.commodity}<br><span style="color:var(--gray);font-size:10px">${c.type}</span></td>
    <td style="font-size:11px">${formatDate(c.eta)}</td>
    <td>${statusBadge(c.status)}</td>
    <td><button class="btn btn-ghost btn-sm" onclick="viewDetail('${c.id}')">👁 Detail</button></td></tr>
  `).join('')||'<tr><td colspan="7" style="text-align:center;padding:30px;color:var(--gray)">Tidak ada data</td></tr>';
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
      const dDate = new Date(d.created_at);
      const localDate = dDate.getFullYear() + '-' + String(dDate.getMonth() + 1).padStart(2, '0') + '-' + String(dDate.getDate()).padStart(2, '0');
      if (localDate !== filterDate) return false;
    }
    return true;
  });
  
  _allDocData = filtered;

  const start  = (page - 1) * ITEMS_PER_PAGE;
  const sliced = filtered.slice(start, start + ITEMS_PER_PAGE);
  document.getElementById('documentTable').innerHTML=sliced.map(d=>`
    <tr><td class="mono">${d.id}</td>
    <td style="font-size:12px"><span class="mono">${d.container_id}</span><br>${d.vessel||''}</td>
    <td style="font-size:11px">📄 ${d.type}</td>
    <td>${docBadge(d.status)}</td>
    <td style="font-size:11px;color:var(--gray)">${formatDate(d.created_at)}</td>
    <td style="font-size:11px;max-width:150px">${d.notes||'-'}</td>
    <td>
      ${d.filepath ? `<button class="btn btn-ghost btn-sm" onclick="openDocPreview(\'${API.resolveUrl(d.filepath)}\', \'${d.type}\', \'${d.id}\')">👁 View</button>` : ''}
    </td></tr>
  `).join('')||'<tr><td colspan="7" style="text-align:center;padding:30px;color:var(--gray)">Belum ada dokumen</td></tr>';
  document.getElementById('pg-document').innerHTML = buildPagination(filtered.length, page, 'renderDocuments');
}

async function openAddContainer() {
  const yr = new Date().getFullYear();
  const ctrs = await API.getContainers();
  const vessels = [...new Set(ctrs.map(c => c.vessel).filter(x => x))];
  const origins = [...new Set(ctrs.map(c => c.origin).filter(x => x))];
  const dests = [...new Set(ctrs.map(c => c.destination).filter(x => x))];
  const types = [...new Set(ctrs.map(c => c.type).filter(x => x))];
  if (vessels.length > 0) document.getElementById('f_vessel').innerHTML = vessels.map(v => `<option value="${v}">${v}</option>`).join('');
  if (origins.length > 0) document.getElementById('f_origin').innerHTML = origins.map(o => `<option value="${o}">${o}</option>`).join('');
  if (dests.length > 0) document.getElementById('f_dest').innerHTML = dests.map(d => `<option value="${d}">${d}</option>`).join('');
  if (types.length > 0) document.getElementById('f_type').innerHTML = types.map(t => `<option value="${t}">${t}</option>`).join('');

  document.getElementById('f_id').value = '';
  const bkNums = ctrs.map(c => parseInt((c.booking_no||'').replace(/\D/g,''))).filter(n=>!isNaN(n));
  const bkNext = bkNums.length > 0 ? Math.max(...bkNums) + 1 : 1;
  document.getElementById('f_booking').value = `BK-${yr}-${String(bkNext).padStart(4,'0')}`;
  document.getElementById('hint_bk').textContent = `Nomor Booking tahun ${yr}`;
  
  document.getElementById('f_voyage').value = '';
  document.getElementById('f_weight').value = '';
  document.getElementById('f_commodity').value = '';
  document.getElementById('f_eta').value = '';
  document.getElementById('modalContainer').classList.add('open');
}

async function saveContainer() {
  const id = document.getElementById('f_id').value.trim();
  if (!id) { alert('ID Kontainer harus diisi manual'); return; }
  const data = {
    id,
    booking_no:   document.getElementById('f_booking').value,
    booking_status: document.getElementById('f_booking_status').value,
    vessel:       document.getElementById('f_vessel').value,
    voyage:       document.getElementById('f_voyage').value,
    origin:       document.getElementById('f_origin').value,
    destination:  document.getElementById('f_dest').value,
    type:         document.getElementById('f_type').value,
    weight:       parseInt(document.getElementById('f_weight').value)||0,
    commodity:    document.getElementById('f_commodity').value,
    eta:          document.getElementById('f_eta').value,
    status:       'booking'
  };
  const res = await API.createContainer(data);
  if(res.error){alert(res.error);return;}
  closeModal('modalContainer');
  await renderContainers();
  alert('✅ Kontainer berhasil didaftarkan!');
}

async function openUploadDoc() {
  const ctrs = await API.getContainers();
  document.getElementById('docContainer').innerHTML = ctrs.map(c => `<option value="${c.id}">${c.id} — ${c.vessel}</option>`).join('');
  document.getElementById('docNotes').value='';
  document.getElementById('modalUpload').classList.add('open');
}

async function saveDoc() {
  const formData = new FormData();
  formData.append('container_id', document.getElementById('docContainer').value);
  formData.append('type', document.getElementById('docType').value);
  formData.append('notes', document.getElementById('docNotes').value);
  const fileInput = document.getElementById('docFile');
  if(fileInput.files[0]) formData.append('file', fileInput.files[0]);

  const res = await fetch(API.base + '/documents.php', {method:'POST',credentials:'include',body:formData});
  const data = await res.json();
  if(data.error){alert(data.error);return;}
  closeModal('modalUpload');
  await renderDocuments();
  alert('✅ Dokumen berhasil diupload!');
}

async function viewDetail(id) {
  const c = await API.getContainer(id);
  document.getElementById('detailTitle').textContent=`Detail: ${c.id}`;
  document.getElementById('detailSub').textContent=`${c.booking_no} · ${c.vessel} (${c.booking_status})`;
  document.getElementById('detailContent').innerHTML=`
    <div style="margin-bottom:10px">${statusBadge(c.status)} <span style="font-size:11px;color:var(--gray);margin-left:8px">📍 ${c.position_desc||'-'}</span></div>
    <div style="margin-bottom:12px;font-size:11px;line-height:1.6">
        <div style="display:flex"><div style="width:100px;color:var(--gray)">Komoditi:</div><div>${c.commodity || '-'} (${c.type || '-'})</div></div>
        <div style="display:flex"><div style="width:100px;color:var(--gray)">Berat:</div><div>${Number(c.weight||0).toLocaleString()} kg</div></div>
        <div style="display:flex"><div style="width:100px;color:var(--gray)">Tujuan:</div><div>${c.origin} &rarr; ${c.destination}</div></div>
    </div>
    <div style="font-size:12px;font-weight:700;color:var(--white);margin-bottom:8px">Timeline</div>
    <div class="timeline">${(c.events||[]).map((e,i,a)=>`<div class="tl-item ${i===a.length-1?'active':'done'}"><div class="tl-event">${e.event}</div><div class="tl-meta">${e.actor} · ${formatDateTime(e.timestamp)}</div></div>`).join('')}</div>
  `;
  document.getElementById('modalDetail').classList.add('open');
}

function closeModal(id){document.getElementById(id).classList.remove('open');}
document.querySelectorAll('.modal-overlay').forEach(m=>{m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('open');});});

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
EOD;

file_put_contents('frontend/assets/js/stakeholder.js', $content);
echo "stakeholder.js fixed and updated.\n";
