let currentUser, mapInstance=null, editingCtrId=null;

document.addEventListener('DOMContentLoaded', async () => {
  const check = await API.check();
  if (!check.authenticated || check.user.role !== 'operator') { window.location.href='../auth/login.html'; return; }
  currentUser = check.user;
  document.getElementById('userName').textContent = currentUser.name;
  document.getElementById('userInitial').textContent = currentUser.name[0];
  document.getElementById('userPort').textContent = currentUser.port||'Operator';
  document.getElementById('dateDisplay').textContent = new Date().toLocaleDateString('id-ID',{weekday:'short',day:'2-digit',month:'short',year:'numeric'});
  await renderDashboard();
  await updateBadges();
  setAutoRefresh(async()=>{ await renderDashboard(); await updateBadges(); });
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
const PAGE_TITLES={dashboard:['Dashboard Operator','CMS › Dashboard'],containers:['Manajemen Kontainer','CMS › Kontainer'],documents:['Dokumen','CMS › Dokumen'],yard:['Yard Map','CMS › Yard'],tracking:['Live Tracking','CMS › Tracking']};

async function showSection(name) {
  document.querySelectorAll('.section').forEach(s=>s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
  document.getElementById('sec-'+name).classList.add('active');
  document.querySelector(`.nav-item[onclick*="${name}"]`).classList.add('active');
  const [t,b]=PAGE_TITLES[name];
  document.getElementById('pageTitle').textContent=t; document.getElementById('pageBreadcrumb').textContent=b;
  if(name==='containers') await renderContainers();
  if(name==='documents') await renderDocuments();
  if(name==='yard') renderYard();
  if(name==='tracking') await renderTracking();
}

async function renderDashboard() {
  const [stats,ctrs] = await Promise.all([API.getStats(), API.getContainers()]);
  document.getElementById('kpiBar').innerHTML = `
    <div class="kpi-item"><div class="kpi-val">${stats.total}</div><div class="kpi-label">Total</div></div>
    <div class="kpi-item"><div class="kpi-val" style="color:var(--cyan)">${stats.in_transit}</div><div class="kpi-label">Aktif</div></div>
    <div class="kpi-item"><div class="kpi-val" style="color:var(--gold)">${stats.by_status?.discharged||0}</div><div class="kpi-label">Perlu Gate-Out</div></div>
    <div class="kpi-item"><div class="kpi-val" style="color:var(--green)">${stats.completed}</div><div class="kpi-label">Selesai</div></div>
    <div class="kpi-item"><div class="kpi-val" style="color:var(--red)">${stats.pending_docs}</div><div class="kpi-label">Dok Pending</div></div>
  `;
  const needAction = ctrs.filter(c=>['discharged','clearance','gate_in'].includes(c.status));
  document.getElementById('actionTable').innerHTML = needAction.map(c=>`
    <tr><td class="mono">${c.id}</td><td style="font-size:11px">${c.vessel}</td>
    <td>${statusBadge(c.status)}</td>
    <td><button class="btn btn-primary btn-sm" onclick="openUpdateStatus('${c.id}')">✏️ Update</button></td></tr>
  `).join('') || `<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--gray)">Semua selesai diproses ✅</td></tr>`;

  const vessels = [...new Set(ctrs.map(c=>c.vessel))];
  document.getElementById('vesselProgress').innerHTML = vessels.slice(0,4).map(v=>{
    const vCtrs=ctrs.filter(c=>c.vessel===v);
    const done=vCtrs.filter(c=>c.status==='completed').length;
    const pct=vCtrs.length>0?Math.round(done/vCtrs.length*100):0;
    return `<div style="margin-bottom:12px">
      <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px">
        <span style="color:var(--white)">🚢 ${v}</span><span style="color:var(--gray)">${done}/${vCtrs.length} (${pct}%)</span>
      </div>
      <div class="progress-wrap"><div class="progress-fill" style="width:${pct}%;background:linear-gradient(90deg,var(--teal),var(--cyan))"></div></div>
    </div>`;
  }).join('') || '<div style="color:var(--gray);font-size:12px">Tidak ada data</div>';
}

async function renderContainers(page = 1) {
  const data = await API.getContainers({search:document.getElementById('ctrSearch').value,status:document.getElementById('ctrFilter').value});
  const start = (page - 1) * ITEMS_PER_PAGE;
  const sliced = data.slice(start, start + ITEMS_PER_PAGE);
  document.getElementById('containerTable').innerHTML = sliced.map(c=>`
    <tr><td class="mono">${c.id}</td><td style="font-size:11px;color:var(--gray)">${c.booking_no}</td>
    <td style="font-size:11px">${c.vessel}</td>
    <td style="font-size:11px">${c.commodity}<br><span style="color:var(--gray);font-size:10px">${c.type}</span></td>
    <td>${statusBadge(c.status)}</td>
    <td style="font-size:10px;color:var(--gray)">${c.position_desc}</td>
    <td style="white-space:nowrap">
      <button class="btn btn-ghost btn-sm" onclick="viewDetail('${c.id}')">👁</button>
      <button class="btn btn-primary btn-sm" onclick="openUpdateStatus('${c.id}')">✏️</button>
    </td></tr>
  `).join('') || `<tr><td colspan="7" style="text-align:center;padding:30px;color:var(--gray)">Tidak ada data</td></tr>`;
  document.getElementById('pg-container').innerHTML = buildPagination(data.length, page, 'renderContainers');
}

async function openUpdateStatus(id) {
  editingCtrId = id;
  const c = await API.getContainer(id);
  document.getElementById('statusModalSub').textContent = `${id} · ${c.vessel} (${c.booking_status})`;
  
  const statusSelect = document.getElementById('newStatus');
  statusSelect.innerHTML = ''; // Clear existing
  
  const exportFlow = [
    {v:'booking', l:'📋 Booking'},
    {v:'gate_in', l:'🚪 Gate In'},
    {v:'yard_map', l:'🗺️ Yard Map'},
    {v:'clearance', l:'🔍 Clearance'},
    {v:'loading', l:'🏗️ Loading'},
    {v:'ship_departure', l:'🚢 Ship Departure'},
    {v:'completed', l:'✅ Selesai'}
  ];
  
  const importFlow = [
    {v:'booking', l:'📋 Booking'},
    {v:'ship_arrival', l:'🚢 Ship Arrival'},
    {v:'discharge', l:'🏗️ Discharge'},
    {v:'yard_map', l:'🗺️ Yard Map'},
    {v:'clearance', l:'🔍 Clearance'},
    {v:'delivery', l:'🚛 Delivery'},
    {v:'completed', l:'✅ Selesai'}
  ];

  const flow = c.booking_status === 'Impor' ? importFlow : exportFlow;
  
  flow.forEach(s => {
    const opt = document.createElement('option');
    opt.value = s.v;
    opt.textContent = s.l;
    statusSelect.appendChild(opt);
  });

  statusSelect.value = c.status;
  document.getElementById('newPosDesc').value = c.position_desc || '';
  document.getElementById('newLat').value = c.position_lat || '';
  document.getElementById('newLng').value = c.position_lng || '';
  document.getElementById('statusNote').value = '';
  document.getElementById('modalStatus').classList.add('open');
}

async function saveStatus() {
  const res = await API.updateContainer({
    id:editingCtrId, status:document.getElementById('newStatus').value,
    position_desc:document.getElementById('newPosDesc').value,
    position_lat:document.getElementById('newLat').value,
    position_lng:document.getElementById('newLng').value,
    note:document.getElementById('statusNote').value,
  });
  if(res.error){alert(res.error);return;}
  closeModal('modalStatus');
  await renderDashboard(); await renderContainers(); await updateBadges();
  alert('✅ Status berhasil diupdate dan sinkron ke semua dashboard!');
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
  document.getElementById('documentTable').innerHTML = sliced.map(d=>`
    <tr><td class="mono">${d.id}</td>
    <td><span class="mono">${d.container_id}</span><br><span style="font-size:10px;color:var(--gray)">${d.vessel||''}</span></td>
    <td style="font-size:12px">📄 ${d.type}</td>
    <td>${docBadge(d.status)}</td>
    <td style="font-size:11px;color:var(--gray)">${formatDateTime(d.created_at)}</td>
    <td style="font-size:11px;max-width:150px">${d.notes||'-'}</td>
    <td style="white-space:nowrap">
      ${d.filepath ? `<button class="btn btn-ghost btn-sm" onclick="openDocPreview(\'${API.resolveUrl(d.filepath)}\', \'${d.type}\', \'${d.id}\')">👁 Preview</button>` : ''}
      ${d.status==='pending'?`
        <button class="btn btn-success btn-sm" onclick="updateDoc('${d.id}','approved','Disetujui operator')">✅</button>
        <button class="btn btn-danger btn-sm" onclick="promptRevision('${d.id}')">❌</button>
      `:'<span style="font-size:10px;color:var(--gray)">Diproses</span>'}
    </td></tr>
  `).join('') || `<tr><td colspan="7" style="text-align:center;padding:30px;color:var(--gray)">Tidak ada data</td></tr>`;
  document.getElementById('pg-document').innerHTML = buildPagination(filtered.length, page, 'renderDocuments');
}

async function updateDoc(id, status, notes) {
  await API.updateDocument({id,status,notes});
  await renderDocuments(); await updateBadges();
}

function promptRevision(id) {
  const note = prompt('Alasan revisi:');
  if(note !== null) updateDoc(id,'revision',note||'Perlu revisi');
}

async function openUploadDoc() {
  const ctrs = await API.getContainers();
  document.getElementById('docContainer').innerHTML = ctrs.map(c=>`<option value="${c.id}">${c.id} — ${c.vessel}</option>`).join('');
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
  await renderDocuments(); await updateBadges();
  alert('✅ Dokumen berhasil diupload!');
}

// Yard Data Dummy Simulation
window.yardMapState = {};
const YARD_CAPACITY = 250;

async function renderYard() {
  const blocks=['A1','A2','A3','A4','A5','A6','B1','B2','B3','B4','B5','B6','C1','C2','C3','C4','C5','C6'];
  const res = await API.getContainers();
  
  // Reset states
  blocks.forEach(b => {
      window.yardMapState[b] = { count: 0, capacity: YARD_CAPACITY, ctrs: [] };
  });

  // Map containers into their respective blocks dynamically
  res.forEach(c => {
      if (c.status !== 'completed' && c.status !== 'delivery' && c.position_desc) {
          const blockMatch = RegExp('Yard ([A-C][1-6])').exec(c.position_desc);
          if (blockMatch && blockMatch[1]) {
              const b = blockMatch[1];
              if (window.yardMapState[b]) {
                  window.yardMapState[b].ctrs.push(c);
                  window.yardMapState[b].count++;
              }
          }
      }
  });

  document.getElementById('yardGrid').innerHTML = blocks.map(b=>{
    const state = window.yardMapState[b];
    const pct = Math.round((state.count / state.capacity) * 100);
    
    let cls = 'yard-low'; // green by default
    if (pct >= 75) cls = 'yard-high'; // red
    else if (pct >= 30) cls = 'yard-medium'; // orange
    
    return `<div class="yard-block ${cls}" onclick="yardBlockClick('${b}')">
        ${b}<br>
        <span style="font-size:12px;display:block;margin:3px 0">${pct}%</span>
        <span style="font-size:9px">${state.count} / ${state.capacity}</span>
    </div>`;
  }).join('');
}

window.yardBlockClick = async function(blockId) {
    const state = window.yardMapState[blockId];
    document.getElementById('detailTitle').textContent = `Blok Yard: ${blockId}`;
    document.getElementById('detailSub').textContent = `Kapasitas: ${state.count} / ${state.capacity} (${Math.round((state.count / state.capacity) * 100)}%)`;
    
    let contentHtml = '';

    // 1. Current Inventory List
    if (state.count > 0) {
        contentHtml += `<div style="font-size:12px;font-weight:700;margin-bottom:8px;color:var(--white)">Kontainer di Blok Ini:</div>`;
        contentHtml += `<div style="max-height:150px;overflow-y:auto;margin-bottom:16px;border:1px solid var(--border);border-radius:8px">`;
        state.ctrs.forEach(c => {
            contentHtml += `<div style="padding:8px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;font-size:11px">
                <div><b>${c.id}</b> - ${c.vessel}<br><span style="color:var(--gray)">${c.commodity || ''} - ${c.type}</span></div>
                <div>
                   <button class="btn btn-danger btn-sm" onclick="removeFromYard('${c.id}', '${blockId}')" style="padding:2px 6px;font-size:10px">Hapus</button>
                </div>
            </div>`;
        });
        contentHtml += `</div>`;
    } else {
        contentHtml += `<div style="color:var(--gray);font-size:12px;margin-bottom:16px">Blok ini kosong.</div>`;
    }

    // 2. Allocation Form (only if not full)
    if (state.count < state.capacity) {
        contentHtml += `<div style="border-top:1px dashed var(--border);padding-top:16px;">`;
        contentHtml += `<div style="font-size:12px;font-weight:700;margin-bottom:8px;color:var(--cyan)">Tambah Alokasi Kontainer</div>`;
        
        const allCtrs = await API.getContainers();
        const pendingCtrs = allCtrs.filter(c => ['booking', 'gate_in', 'ship_arrival', 'discharge'].includes(c.status) && !c.position_desc?.includes(`Yard ${blockId}`));
        window._pendingCtrsForYard = pendingCtrs;
        
        if (pendingCtrs.length > 0) {
            contentHtml += `
              <div style="margin-bottom:14px">
                <select id="yardSelCtr" style="width:100%;background:#ffffff;border:1px solid var(--border);border-radius:8px;padding:8px;color:#000000;margin-bottom:10px;font-size:12px" onchange="showYardSelDetail()">
                   <option value="" style="color:#000000">-- Pilih Kontainer Tersedia --</option>
                   ${pendingCtrs.map(c => `<option value="${c.id}" style="color:#000000">${c.id} - ${c.owner_name || c.vessel}</option>`).join('')}
                </select>
                <div id="yardSelDetail" style="padding:10px;border:1px solid var(--border);border-radius:10px;display:none;font-size:12px"></div>
              </div>
              <button class="btn btn-primary" style="width:100%;justify-content:center" onclick="allocateYard('${blockId}')">Alokasikan Kesini</button>
            `;
        } else {
            contentHtml += `<div style="color:var(--gray);font-size:11px">Tidak ada kontainer yang menunggu alokasi.</div>`;
        }
        contentHtml += `</div>`;
    } else {
        contentHtml += `<div style="color:var(--red);font-size:12px;font-weight:600;text-align:center;padding:10px;background:rgba(239,68,68,.1);border-radius:8px">Blok ini sudah penuh kapasitasnya.</div>`;
    }

    document.getElementById('detailContent').innerHTML = contentHtml;
    document.getElementById('modalDetail').classList.add('open');
};

window.showYardSelDetail = function() {
    const sel = document.getElementById('yardSelCtr').value;
    const detailDiv = document.getElementById('yardSelDetail');
    if(!sel) { detailDiv.style.display = 'none'; return; }
    const c = window._pendingCtrsForYard.find(x => x.id === sel);
    if(c) {
        detailDiv.innerHTML = `<b>Lokasi Asal:</b> ${c.origin} &rarr; ${c.destination}<br><b>Komoditi:</b> ${c.commodity}<br><b>Saat ini:</b> ${c.position_desc || '-'}`;
        detailDiv.style.display = 'block';
    }
};

window.allocateYard = async function(blockId) {
    const sel = document.getElementById('yardSelCtr').value;
    if(!sel) { alert('Pilih kontainer terlebih dahulu'); return; }
    
    // Update API
    const res = await API.updateContainer({ id: sel, position_desc: `Yard ${blockId}, Tanjung Perak`, status: 'yard_map' });
    if(res.error) { alert(res.error); return; }
    
    closeModal('modalDetail');
    await renderYard();
    alert('✅ Kontainer ' + sel + ' berhasil dialokasikan ke Yard ' + blockId);
};

window.removeFromYard = async function(id, blockId) {
    if(!confirm('Hapus kontainer ' + id + ' dari Yard ' + blockId + '?')) return;
    const res = await API.updateContainer({ id: id, position_desc: '' });
    if(res.error) { alert(res.error); return; }
    closeModal('modalDetail');
    await renderYard();
    alert('✅ Kontainer ' + id + ' berhasil dihapus dari Yard ' + blockId);
};

async function renderTracking() {
  const ctrs = await API.getContainers({status:'delivery'});
  const all  = await API.getContainers();
  const active = all.filter(c=>!['completed'].includes(c.status));
  if(!mapInstance){
    mapInstance=L.map('opMap').setView([-7.2575,112.7521],8);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap'}).addTo(mapInstance);
  } else { mapInstance.eachLayer(l=>{if(l instanceof L.Marker)l.remove();}); }
  active.forEach(c=>{
    const s=STATUS_CONFIG[c.status]||{};
    const icon=L.divIcon({html:`<div style="background:${s.color||'#10b981'};color:white;padding:3px 8px;border-radius:20px;font-size:10px;font-weight:700;box-shadow:0 2px 8px rgba(0,0,0,.3)">${s.icon} ${c.id}</div>`,className:'',iconAnchor:[0,0]});
    L.marker([parseFloat(c.position_lat)||(-7.2575),parseFloat(c.position_lng)||112.7521],{icon}).addTo(mapInstance).bindPopup(`<b>${c.id}</b><br>${c.position_desc}`);
  });
  document.getElementById('deliveryList').innerHTML = ctrs.map(c=>`
    <div style="padding:10px;border:1px solid var(--border);border-radius:10px;margin-bottom:8px">
      <div style="display:flex;justify-content:space-between"><span class="mono">${c.id}</span>${statusBadge(c.status)}</div>
      <div style="font-size:11px;color:var(--gray);margin-top:4px">📍 ${c.position_desc}</div>
      <button class="btn btn-primary btn-sm" style="margin-top:8px" onclick="openUpdateStatus('${c.id}')">✏️ Update</button>
    </div>
  `).join('') || '<div style="color:var(--gray);font-size:12px;text-align:center;padding:20px">Tidak ada delivery</div>';
}

async function viewDetail(id) {
  const c = await API.getContainer(id);
  document.getElementById('detailTitle').textContent=`Detail: ${c.id}`;
  document.getElementById('detailSub').textContent=`${c.booking_no} · ${c.vessel} (${c.booking_status})`;
  document.getElementById('detailContent').innerHTML=`
    <div style="margin-bottom:10px">${statusBadge(c.status)} <span style="font-size:11px;color:var(--gray);margin-left:8px">📍 ${c.position_desc}</span></div>
    <div style="margin-bottom:12px;font-size:11px;line-height:1.6">
        <div style="display:flex"><div style="width:100px;color:var(--gray)">Komoditi:</div><div>${c.commodity || '-'} (${c.type || '-'})</div></div>
        <div style="display:flex"><div style="width:100px;color:var(--gray)">Berat:</div><div>${Number(c.weight||0).toLocaleString()} kg</div></div>
        <div style="display:flex"><div style="width:100px;color:var(--gray)">Pemilik:</div><div>${c.owner_name || '-'}</div></div>
    </div>
    <div style="font-size:12px;font-weight:700;color:var(--white);margin-bottom:8px">Dokumen</div>
    ${(c.documents||[]).map(d=>`<div style="display:flex;justify-content:space-between;padding:8px;background:rgba(255,255,255,.03);border-radius:8px;margin-bottom:5px"><span style="font-size:12px">📄 ${d.type}</span>${docBadge(d.status)}</div>`).join('')||'<div style="font-size:12px;color:var(--gray)">Belum ada</div>'}
    <div style="font-size:12px;font-weight:700;color:var(--white);margin:12px 0 8px">Timeline</div>
    <div class="timeline">${(c.events||[]).map((e,i,a)=>`<div class="tl-item ${i===a.length-1?'active':'done'}"><div class="tl-event">${e.event}</div><div class="tl-meta">${e.actor} · ${formatDateTime(e.timestamp)}</div></div>`).join('')}</div>
  `;
  document.getElementById('modalDetail').classList.add('open');
}

async function updateBadges() {
  const stats = await API.getStats();
  document.getElementById('nbCtr').textContent=stats.by_status?.clearance||0;
  document.getElementById('nbDoc').textContent=stats.pending_docs;
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