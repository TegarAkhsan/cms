<?php
$content = <<<'EOD'
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Operator — CMS</title>
<link rel="icon" href="data:,">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<style>
:root{--bg:#071210;--bg2:#0c1e1a;--sidebar:#061008;--card:#0e1e19;--border:rgba(255,255,255,.07);--green:#10b981;--teal:#0d9488;--cyan:#22d3ee;--gold:#f59e0b;--red:#ef4444;--gray:#64748b;--light:#94a3b8;--white:#f1f5f9;--text:#cbd5e1;--font:'Space Grotesk',sans-serif;--mono:'JetBrains Mono',monospace;}
*{margin:0;padding:0;box-sizing:border-box;}body{font-family:var(--font);background:var(--bg);color:var(--text);display:flex;min-height:100vh;}
.sidebar{width:240px;min-height:100vh;background:var(--sidebar);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;z-index:100;}
.sidebar-logo{padding:24px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;}
.sidebar-logo .icon{width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,var(--teal),var(--cyan));display:flex;align-items:center;justify-content:center;font-size:18px;}
.brand{font-size:16px;font-weight:700;color:var(--white);}.sub-role{font-size:10px;color:var(--gray);}
.sidebar-nav{padding:12px 10px;flex:1;}
.nav-section{font-size:10px;color:var(--gray);text-transform:uppercase;letter-spacing:1px;font-weight:600;padding:12px 10px 6px;}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;cursor:pointer;transition:all .2s;font-size:13px;color:var(--light);margin-bottom:2px;border:none;background:none;width:100%;text-align:left;}
.nav-item:hover{background:rgba(255,255,255,.05);color:var(--white);}
.nav-item.active{background:rgba(16,185,129,.15);color:var(--cyan);border:1px solid rgba(34,211,238,.15);}
.nav-item .ni{font-size:16px;width:20px;text-align:center;}
.nb{margin-left:auto;background:var(--red);color:white;border-radius:20px;padding:1px 7px;font-size:10px;font-weight:700;}
.sidebar-footer{padding:16px;border-top:1px solid var(--border);}
.user-card{display:flex;align-items:center;gap:10px;padding:10px;background:rgba(255,255,255,.04);border-radius:10px;}
.user-avatar{width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,var(--teal),var(--green));display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:white;}
.user-name{font-size:12px;font-weight:600;color:var(--white);}.user-role{font-size:10px;color:var(--green);}
.logout-btn{margin-left:auto;background:none;border:none;cursor:pointer;color:var(--gray);font-size:16px;}
.logout-btn:hover{color:var(--red);}
.main{margin-left:240px;flex:1;display:flex;flex-direction:column;}
.topbar{padding:16px 24px;background:var(--bg2);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;}
.topbar h2{font-size:16px;font-weight:700;color:var(--white);}
.breadcrumb{font-size:11px;color:var(--gray);}
.content{padding:24px;flex:1;}
.section{display:none;}.section.active{display:block;}
.kpi-bar{display:flex;background:rgba(255,255,255,.04);border-radius:12px;overflow:hidden;margin-bottom:20px;}
.kpi-item{flex:1;padding:14px 16px;text-align:center;border-right:1px solid var(--border);}
.kpi-item:last-child{border-right:none;}
.kpi-val{font-size:22px;font-weight:700;color:var(--white);font-family:var(--mono);}
.kpi-label{font-size:10px;color:var(--gray);margin-top:4px;text-transform:uppercase;letter-spacing:.6px;}
.box{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:20px;margin-bottom:16px;}
.box-title{font-size:13px;font-weight:700;color:var(--white);margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;}
.box-title span{font-size:11px;color:var(--gray);font-weight:500;}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
.grid-3{display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:16px;}
.data-table{width:100%;border-collapse:collapse;font-size:12px;}
.data-table th{background:rgba(255,255,255,.04);padding:10px 12px;text-align:left;color:var(--gray);font-size:10px;text-transform:uppercase;border-bottom:1px solid var(--border);}
.data-table td{padding:10px 12px;border-bottom:1px solid rgba(255,255,255,.03);vertical-align:middle;}
.data-table tr:hover td{background:rgba(255,255,255,.02);}
.mono{font-family:var(--mono);font-size:11px;color:var(--cyan);}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:600;white-space:nowrap;}
.btn{padding:8px 16px;border-radius:10px;border:none;cursor:pointer;font-family:var(--font);font-size:12px;font-weight:600;transition:all .2s;display:inline-flex;align-items:center;gap:6px;}
.btn-primary{background:var(--teal);color:white;}.btn-primary:hover{background:var(--green);}
.btn-success{background:var(--green);color:white;}
.btn-danger{background:var(--red);color:white;}
.btn-sm{padding:5px 10px;font-size:11px;border-radius:7px;}
.btn-ghost{background:rgba(255,255,255,.06);color:var(--light);border:1px solid var(--border);}
.filter-bar{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:14px 18px;margin-bottom:16px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;}
.filter-bar label{font-size:11px;color:var(--gray);font-weight:600;}
.filter-bar select{background:rgba(255,255,255,.06);border:1px solid var(--border);border-radius:8px;padding:7px 10px;color:var(--white);font-family:var(--font);font-size:12px;outline:none;}
.filter-bar select option{background:var(--bg2);}
.search-input{padding:7px 12px;background:rgba(255,255,255,.06);border:1px solid var(--border);border-radius:8px;color:var(--white);font-family:var(--font);font-size:12px;outline:none;width:200px;}
.search-input::placeholder{color:var(--gray);}
.search-input:focus,.filter-bar select:focus{border-color:var(--cyan);}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:999;display:none;align-items:center;justify-content:center;backdrop-filter:blur(4px);}
.modal-overlay.open{display:flex;}
.modal{background:var(--bg2);border:1px solid var(--border);border-radius:20px;width:560px;max-width:95vw;max-height:90vh;overflow-y:auto;padding:28px;}
.modal h3{font-size:17px;color:var(--white);margin-bottom:6px;}
.modal .sub{font-size:12px;color:var(--gray);margin-bottom:22px;}
.modal-footer{display:flex;gap:10px;justify-content:flex-end;margin-top:20px;padding-top:16px;border-top:1px solid var(--border);}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.form-group{margin-bottom:14px;}
.form-group label{display:block;font-size:11px;color:var(--gray);font-weight:600;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;}
.form-group input,.form-group select,.form-group textarea{width:100%;background:rgba(255,255,255,.06);border:1px solid var(--border);border-radius:10px;padding:10px 12px;color:var(--white);font-family:var(--font);font-size:13px;outline:none;}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--cyan);}
.form-group select option{background:var(--bg2);}
.form-group textarea{resize:vertical;min-height:80px;}
.timeline{position:relative;padding-left:20px;}
.timeline::before{content:'';position:absolute;left:6px;top:6px;bottom:6px;width:2px;background:var(--border);}
.tl-item{position:relative;padding:0 0 18px 20px;}
.tl-item::before{content:'';position:absolute;left:-8px;top:4px;width:12px;height:12px;border-radius:50%;border:2px solid var(--border);background:var(--bg2);}
.tl-item.done::before{background:var(--green);border-color:var(--green);}
.tl-item.active::before{background:var(--cyan);border-color:var(--cyan);box-shadow:0 0 8px var(--cyan);}
.tl-event{font-size:12px;font-weight:600;color:var(--white);}
.tl-meta{font-size:10px;color:var(--gray);margin-top:2px;}
.progress-wrap{background:rgba(255,255,255,.08);border-radius:20px;height:6px;overflow:hidden;}
.progress-fill{height:100%;border-radius:20px;}
#opMap{height:300px;border-radius:12px;}
.yard-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:8px;}
.yard-block{border-radius:10px;padding:10px 6px;text-align:center;font-size:11px;font-weight:700;color:white;cursor:pointer;}
.yard-low{background:#065f46;}.yard-medium{background:#b45309;}.yard-high{background:#991b1b;}
::-webkit-scrollbar{width:6px;}::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:3px;}
/* PAGINATION */
.pagination{display:flex;justify-content:center;gap:6px;margin-top:16px;}
.pagination .btn{min-width:30px;padding:5px;text-align:center;}
</style>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
</head>
<body>
<div class="sidebar">
  <div class="sidebar-logo">
    <div class="icon">⚓</div>
    <div><div class="brand">CMS</div><div class="sub-role">Operator Panel</div></div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Operasional</div>
    <button class="nav-item active" onclick="showSection('dashboard')"><span class="ni">📊</span> Dashboard</button>
    <button class="nav-item" onclick="showSection('containers')"><span class="ni">📦</span> Kontainer <span class="nb" id="nbCtr">0</span></button>
    <button class="nav-item" onclick="showSection('documents')"><span class="ni">📄</span> Dokumen <span class="nb" id="nbDoc">0</span></button>
    <button class="nav-item" onclick="showSection('yard')"><span class="ni">🏭</span> Yard Map</button>
    <button class="nav-item" onclick="showSection('tracking')"><span class="ni">🗺️</span> Live Tracking</button>
  </nav>
  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar" id="userInitial">O</div>
      <div><div class="user-name" id="userName">Operator</div><div class="user-role" id="userPort">Operator</div></div>
      <button class="logout-btn" onclick="doLogout()">⏻</button>
    </div>
  </div>
</div>

<div class="main">
  <div class="topbar">
    <div><h2 id="pageTitle">Dashboard Operator</h2><div class="breadcrumb" id="pageBreadcrumb">CMS › Dashboard</div></div>
    <div style="font-size:11px;color:var(--gray);font-family:var(--mono)" id="dateDisplay"></div>
  </div>
  <div class="content">

    <div class="section active" id="sec-dashboard">
      <div class="kpi-bar" id="kpiBar"></div>
      <div class="grid-2">
        <div class="box"><div class="box-title">Perlu Tindakan Segera</div><table class="data-table"><thead><tr><th>ID</th><th>Vessel</th><th>Status</th><th>Aksi</th></tr></thead><tbody id="actionTable"></tbody></table></div>
        <div class="box"><div class="box-title">Progress Vessel</div><div id="vesselProgress"></div></div>
      </div>
    </div>

    <div class="section" id="sec-containers">
      <div class="filter-bar">
        <input class="search-input" id="ctrSearch" placeholder="🔍 Cari kontainer..." oninput="renderContainers(1)">
        <label>Status:</label>
        <select id="ctrFilter" onchange="renderContainers(1)">
          <option value="">Semua</option>
          <option value="booking">Booking</option>
          <option value="gate_in">Gate In</option>
          <option value="ship_arrival">Ship Arrival</option>
          <option value="discharge">Discharge</option>
          <option value="yard_map">Yard Map</option>
          <option value="clearance">Clearance</option>
          <option value="loading">Loading</option>
          <option value="ship_departure">Ship Departure</option>
          <option value="delivery">Delivery</option>
          <option value="completed">Selesai</option>
        </select>
      </div>
      <div class="box">
        <table class="data-table">
          <thead><tr><th>ID</th><th>Booking</th><th>Vessel</th><th>Komoditi</th><th>Status</th><th>Lokasi</th><th>Aksi</th></tr></thead>
          <tbody id="containerTable"></tbody>
        </table>
        <div class="pagination" id="pg-container"></div>
      </div>
    </div>

    <div class="section" id="sec-documents">
      <div class="filter-bar">
        <input class="search-input" id="docSearch" placeholder="🔍 Cari dokumen..." oninput="renderDocuments(1)">
        <label>Status:</label>
        <select id="docFilter" onchange="renderDocuments(1)">
          <option value="">Semua</option><option value="pending">Pending</option>
          <option value="approved">Disetujui</option><option value="revision">Revisi</option>
        </select>
        <button class="btn btn-primary" onclick="openUploadDoc()">+ Upload Dokumen</button>
      
        <label>Tanggal:</label>
        <input type="date" id="docDate" onchange="renderDocuments(1)" style="background:rgba(255,255,255,.06);border:1px solid var(--border);border-radius:8px;padding:7px 10px;color:var(--white);font-family:var(--font);font-size:12px;outline:none;">
        <button class="btn btn-success" onclick="downloadDocExcel()">📥 Laporan Excel</button>
      </div>
      <div class="box">
        <table class="data-table">
          <thead><tr><th>ID</th><th>Kontainer</th><th>Tipe</th><th>Status</th><th>Tanggal</th><th>Catatan</th><th>Aksi</th></tr></thead>
          <tbody id="documentTable"></tbody>
        </table>
        <div class="pagination" id="pg-document"></div>
      </div>
    </div>

    <div class="section" id="sec-yard">
      <div class="box">
        <div class="box-title">Mini Map Yard <span>Tanjung Perak Terminal</span></div>
        <div style="text-align:center;padding:8px;background:rgba(16,185,129,.1);border-radius:8px;margin-bottom:12px;font-size:12px;color:var(--green);font-weight:600">🚢 DERMAGA</div>
        <div class="yard-grid" id="yardGrid"></div>
        <div style="text-align:center;padding:8px;background:rgba(239,68,68,.1);border-radius:8px;margin-top:12px;font-size:12px;color:var(--red);font-weight:600">🚛 GATE AREA</div>
      </div>
    </div>

    <div class="section" id="sec-tracking">
      <div class="grid-3">
        <div class="box"><div class="box-title">Peta Real-Time</div><div id="opMap"></div></div>
        <div class="box"><div class="box-title">On Delivery</div><div id="deliveryList"></div></div>
      </div>
    </div>

  </div>
</div>

<!-- MODAL: Update Status -->
<div class="modal-overlay" id="modalStatus">
  <div class="modal">
    <h3>Update Status Kontainer</h3><p class="sub" id="statusModalSub"></p>
    <div class="form-group"><label>Status Baru</label>
      <select id="newStatus">
        <!-- Dinamis diisi oleh JS -->
      </select>
    </div>
    <div class="form-group"><label>Deskripsi Posisi</label><input id="newPosDesc" placeholder="Yard B-04, Tanjung Perak"></div>
    <div class="form-row">
      <div class="form-group"><label>Latitude</label><input id="newLat" type="number" step="0.0001"></div>
      <div class="form-group"><label>Longitude</label><input id="newLng" type="number" step="0.0001"></div>
    </div>
    <div class="form-group"><label>Catatan</label><textarea id="statusNote" placeholder="Keterangan tindakan..."></textarea></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modalStatus')">Batal</button>
      <button class="btn btn-primary" onclick="saveStatus()">Update Status</button>
    </div>
  </div>
</div>

<!-- MODAL: Upload Doc -->
<div class="modal-overlay" id="modalUpload">
  <div class="modal" style="width:480px">
    <h3>Upload Dokumen</h3><p class="sub">Upload dokumen untuk kontainer</p>
    <div class="form-group"><label>Kontainer</label><select id="docContainer"></select></div>
    <div class="form-group"><label>Tipe Dokumen</label>
      <select id="docType"><option>Bill of Lading</option><option>Packing List</option><option>Delivery Order</option><option>Surat Jalan</option><option>Manifest</option><option>Bukti Gate-In</option><option>Bukti Gate-Out</option></select>
    </div>
    <div class="form-group"><label>Upload File (PDF/JPG/PNG)</label><input type="file" id="docFile" accept=".pdf,.jpg,.jpeg,.png" style="padding:8px"></div>
    <div class="form-group"><label>Catatan</label><textarea id="docNotes" placeholder="Catatan tambahan..."></textarea></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modalUpload')">Batal</button>
      <button class="btn btn-primary" onclick="saveDoc()">Upload</button>
    </div>
  </div>
</div>

<!-- MODAL: Detail -->
<div class="modal-overlay" id="modalDetail">
  <div class="modal" style="width:640px">
    <h3 id="detailTitle">Detail</h3><p class="sub" id="detailSub"></p>
    <div id="detailContent"></div>
    <div class="modal-footer"><button class="btn btn-ghost" onclick="closeModal('modalDetail')">Tutup</button></div>
  </div>
</div>


<!-- MODAL: Preview Dokumen -->
<div class="modal-overlay" id="modalDocPreview">
  <div class="modal" style="width:800px; max-width:95vw;">
    <h3>Preview Dokumen</h3>
    <p class="sub" id="previewSub"></p>
    <div id="previewContainer" style="width:100%; height:500px; max-height:70vh; background:rgba(0,0,0,0.5); border-radius:8px; overflow:hidden; display:flex; align-items:center; justify-content:center; margin-bottom:14px;">
    </div>
    <div class="modal-footer">
       <a id="previewDownloadBtn" href="" target="_blank" class="btn btn-success">⬇️ Download File</a>
       <button class="btn btn-ghost" onclick="closeModal('modalDocPreview')">Tutup</button>
    </div>
  </div>
</div>

<script src="../assets/js/api-client.js"></script>
<script src="../assets/js/operator.js"></script>
</body>
</html>
EOD;

file_put_contents('frontend/dashboards/operator.html', $content);
echo "operator.html restored and updated.\n";
