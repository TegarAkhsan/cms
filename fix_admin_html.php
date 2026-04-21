<?php
$content = <<<'EOD'
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin — CMS Dashboard</title>
<link rel="icon" href="data:,">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<style>
:root{
  --bg:#080f1e;--bg2:#0d1729;--sidebar:#060d1a;--card:#0f1e35;
  --border:rgba(255,255,255,.07);--blue:#1a4fd6;--cyan:#00d4ff;
  --green:#10b981;--gold:#f59e0b;--red:#ef4444;--orange:#f97316;
  --purple:#7c3aed;--gray:#64748b;--light:#94a3b8;--white:#f1f5f9;
  --text:#cbd5e1;--font:'Space Grotesk',sans-serif;--mono:'JetBrains Mono',monospace;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:var(--font);background:var(--bg);color:var(--text);display:flex;min-height:100vh;}

.sidebar{width:240px;min-height:100vh;background:var(--sidebar);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;z-index:100;}
.sidebar-logo{padding:24px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;}
.sidebar-logo .icon{width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,var(--blue),var(--cyan));display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.sidebar-logo .brand{font-size:16px;font-weight:700;color:var(--white);}
.sidebar-logo .sub{font-size:10px;color:var(--gray);}
.sidebar-nav{padding:12px 10px;flex:1;overflow-y:auto;}
.nav-section{font-size:10px;color:var(--gray);text-transform:uppercase;letter-spacing:1px;font-weight:600;padding:12px 10px 6px;}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;cursor:pointer;transition:all .2s;font-size:13px;color:var(--light);margin-bottom:2px;border:none;background:none;width:100%;text-align:left;}
.nav-item:hover{background:rgba(255,255,255,.05);color:var(--white);}
.nav-item.active{background:rgba(26,79,214,.2);color:var(--cyan);border:1px solid rgba(0,212,255,.15);}
.nav-item .ni{font-size:16px;width:20px;text-align:center;flex-shrink:0;}
.nb{margin-left:auto;background:var(--red);color:white;border-radius:20px;padding:1px 7px;font-size:10px;font-weight:700;}
.sidebar-footer{padding:16px;border-top:1px solid var(--border);}
.user-card{display:flex;align-items:center;gap:10px;padding:10px;background:rgba(255,255,255,.04);border-radius:10px;}
.user-avatar{width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,var(--gold),#d97706);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:white;flex-shrink:0;}
.user-name{font-size:12px;font-weight:600;color:var(--white);}
.user-role{font-size:10px;color:var(--gold);}
.logout-btn{margin-left:auto;background:none;border:none;cursor:pointer;color:var(--gray);font-size:16px;}
.logout-btn:hover{color:var(--red);}

.main{margin-left:240px;flex:1;display:flex;flex-direction:column;}
.topbar{padding:16px 24px;background:var(--bg2);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;}
.topbar h2{font-size:16px;font-weight:700;color:var(--white);}
.breadcrumb{font-size:11px;color:var(--gray);}
.topbar-right{display:flex;align-items:center;gap:12px;}
.date-display{font-size:11px;color:var(--gray);font-family:var(--mono);background:rgba(255,255,255,.04);padding:6px 12px;border-radius:8px;border:1px solid var(--border);}

.content{padding:24px;flex:1;}
.section{display:none;}.section.active{display:block;}

.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;margin-bottom:24px;}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:20px;position:relative;overflow:hidden;transition:transform .2s;}
.stat-card:hover{transform:translateY(-3px);}
.stat-label{font-size:11px;color:var(--gray);text-transform:uppercase;letter-spacing:.8px;font-weight:600;margin-bottom:10px;}
.stat-value{font-size:32px;font-weight:700;color:var(--white);font-family:var(--mono);line-height:1;margin-bottom:8px;}
.stat-icon{position:absolute;bottom:14px;right:16px;font-size:28px;opacity:.4;}

.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
.grid-3{display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:16px;}
.box{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:20px;margin-bottom:16px;}
.box-title{font-size:13px;font-weight:700;color:var(--white);margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;}
.box-title span{color:var(--gray);font-size:11px;font-weight:500;}

.data-table{width:100%;border-collapse:collapse;font-size:12px;}
.data-table th{background:rgba(255,255,255,.04);padding:10px 12px;text-align:left;color:var(--gray);font-size:10px;text-transform:uppercase;letter-spacing:.8px;border-bottom:1px solid var(--border);}
.data-table td{padding:11px 12px;border-bottom:1px solid rgba(255,255,255,.03);color:var(--text);vertical-align:middle;}
.data-table tr:hover td{background:rgba(255,255,255,.02);}
.mono{font-family:var(--mono);font-size:11px;color:var(--cyan);}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:600;white-space:nowrap;}

.btn{padding:8px 16px;border-radius:10px;border:none;cursor:pointer;font-family:var(--font);font-size:12px;font-weight:600;transition:all .2s;display:inline-flex;align-items:center;gap:6px;}
.btn-primary{background:var(--blue);color:white;}.btn-primary:hover{background:#2563eb;}
.btn-success{background:var(--green);color:white;}
.btn-danger{background:var(--red);color:white;}
.btn-warning{background:var(--gold);color:#000;}
.btn-sm{padding:5px 10px;font-size:11px;border-radius:7px;}
.btn-ghost{background:rgba(255,255,255,.06);color:var(--light);border:1px solid var(--border);}
.btn-ghost:hover{background:rgba(255,255,255,.1);}

.filter-bar{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:14px 18px;margin-bottom:16px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;}
.filter-bar label{font-size:11px;color:var(--gray);font-weight:600;}
.filter-bar select{background:rgba(255,255,255,.06);border:1px solid var(--border);border-radius:8px;padding:7px 10px;color:var(--white);font-family:var(--font);font-size:12px;outline:none;}
.filter-bar select option{background:var(--bg2);}
.search-input{padding:7px 12px;background:rgba(255,255,255,.06);border:1px solid var(--border);border-radius:8px;color:var(--white);font-family:var(--font);font-size:12px;outline:none;width:220px;}
.search-input::placeholder{color:var(--gray);}
.search-input:focus,.filter-bar select:focus{border-color:var(--cyan);}

.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:999;display:none;align-items:center;justify-content:center;backdrop-filter:blur(4px);}
.modal-overlay.open{display:flex;}
.modal{background:var(--bg2);border:1px solid var(--border);border-radius:20px;width:620px;max-width:95vw;max-height:90vh;overflow-y:auto;padding:28px;}
.modal h3{font-size:17px;color:var(--white);margin-bottom:6px;}
.modal .sub{font-size:12px;color:var(--gray);margin-bottom:22px;}
.modal-footer{display:flex;gap:10px;justify-content:flex-end;margin-top:20px;padding-top:16px;border-top:1px solid var(--border);}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;}
.form-group{margin-bottom:14px;}
.form-group label{display:block;font-size:11px;color:var(--gray);font-weight:600;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;}
.form-group input,.form-group select,.form-group textarea{width:100%;background:rgba(255,255,255,.06);border:1px solid var(--border);border-radius:10px;padding:10px 12px;color:var(--white);font-family:var(--font);font-size:13px;outline:none;transition:all .2s;}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--cyan);}
.form-group select option{background:var(--bg2);}
.form-group textarea{resize:vertical;min-height:80px;}

.input-autofill{background:rgba(0,212,255,.06)!important;border-color:rgba(0,212,255,.3)!important;color:var(--cyan)!important;font-family:var(--mono)!important;cursor:default;}
.auto-hint{font-size:10px;color:var(--cyan);margin-top:4px;font-family:var(--mono);}

.notif-item{display:flex;gap:12px;padding:12px;background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:12px;margin-bottom:8px;cursor:pointer;}
.notif-item.unread{border-color:rgba(0,212,255,.2);background:rgba(0,212,255,.04);}
.notif-dot{width:8px;height:8px;border-radius:50%;margin-top:4px;flex-shrink:0;}

.timeline{position:relative;padding-left:20px;}
.timeline::before{content:'';position:absolute;left:6px;top:6px;bottom:6px;width:2px;background:var(--border);}
.tl-item{position:relative;padding:0 0 18px 20px;}
.tl-item::before{content:'';position:absolute;left:-8px;top:4px;width:12px;height:12px;border-radius:50%;border:2px solid var(--border);background:var(--bg2);}
.tl-item.done::before{background:var(--green);border-color:var(--green);}
.tl-item.active::before{background:var(--cyan);border-color:var(--cyan);box-shadow:0 0 8px var(--cyan);}
.tl-event{font-size:12px;font-weight:600;color:var(--white);}
.tl-meta{font-size:10px;color:var(--gray);margin-top:2px;}

#adminMap{height:340px;border-radius:12px;}
.map-legend{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;}
.map-legend-item{display:flex;align-items:center;gap:5px;font-size:10px;color:var(--gray);}
.map-legend-dot{width:10px;height:10px;border-radius:50%;}

@keyframes markerPulse{0%,100%{transform:scale(1);}50%{transform:scale(1.15);}}
.marker-updated{animation:markerPulse .6s ease 3;}

.progress-wrap{background:rgba(255,255,255,.08);border-radius:20px;height:6px;overflow:hidden;}
.progress-fill{height:100%;border-radius:20px;transition:width .5s ease;}

.pagination{display:flex;justify-content:center;gap:6px;margin-top:16px;flex-wrap:wrap;}
.pagination .btn{min-width:32px;padding:5px;text-align:center;}

::-webkit-scrollbar{width:6px;}::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:3px;}
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sidebar-logo">
    <div class="icon">⚓</div>
    <div><div class="brand">CMS</div><div class="sub">Admin Panel</div></div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Utama</div>
    <button class="nav-item active" onclick="showSection('dashboard')"><span class="ni">📊</span> Dashboard</button>
    <button class="nav-item" onclick="showSection('containers')"><span class="ni">📦</span> Kontainer <span class="nb" id="nbContainer">0</span></button>
    <button class="nav-item" onclick="showSection('documents')"><span class="ni">📄</span> Dokumen <span class="nb" id="nbDoc">0</span></button>
    <button class="nav-item" onclick="showSection('tracking')"><span class="ni">🗺️</span> Live Tracking</button>
    <div class="nav-section">Manajemen</div>
    <button class="nav-item" onclick="showSection('users')"><span class="ni">👥</span> Pengguna</button>
    <button class="nav-item" onclick="showSection('notifications')"><span class="ni">🔔</span> Notifikasi <span class="nb" id="nbNotif">0</span></button>
    <button class="nav-item" onclick="showSection('reports')"><span class="ni">📈</span> Laporan</button>
  </nav>
  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar" id="userInitial">A</div>
      <div><div class="user-name" id="userName">Admin</div><div class="user-role">Administrator</div></div>
      <button class="logout-btn" onclick="doLogout()">⏻</button>
    </div>
  </div>
</div>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <div>
      <h2 id="pageTitle">Dashboard Admin</h2>
      <div class="breadcrumb" id="pageBreadcrumb">CMS › Dashboard</div>
    </div>
    <div class="topbar-right">
      <div class="date-display" id="dateDisplay">-</div>
    </div>
  </div>

  <div class="content">

    <!-- DASHBOARD -->
    <div class="section active" id="sec-dashboard">
      <div class="stat-grid" id="statGrid"></div>
      <div class="grid-2">
        <div class="box"><div class="box-title">Pergerakan Kontainer <span>12 Bulan Terakhir</span></div><canvas id="movChart" style="max-height:220px"></canvas></div>
        <div class="box"><div class="box-title">Distribusi Status</div><canvas id="statusChart" style="max-height:220px"></canvas></div>
      </div>
      <div class="grid-3">
        <div class="box"><div class="box-title">Kontainer Terbaru</div>
          <table class="data-table"><thead><tr><th>ID</th><th>Vessel</th><th>Rute</th><th>Status</th></tr></thead><tbody id="recentTable"></tbody></table>
        </div>
        <div class="box"><div class="box-title">Notifikasi Terbaru</div><div id="dashNotifList"></div></div>
      </div>
    </div>

    <!-- CONTAINERS -->
    <div class="section" id="sec-containers">
      <div class="filter-bar">
        <input class="search-input" id="ctrSearch" placeholder="🔍 Cari ID, vessel, komoditi..." oninput="renderContainers(1)">
        <label>Status:</label>
        <select id="ctrFilter" onchange="renderContainers(1)">
          <option value="">Semua Status</option>
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
          <thead><tr><th>ID</th><th>Booking</th><th>Vessel</th><th>Rute</th><th>Komoditi</th><th>Status</th><th>Lokasi</th><th>Aksi</th></tr></thead>
          <tbody id="containerTable"></tbody>
        </table>
        <div class="pagination" id="pg-container"></div>
      </div>
    </div>

    <!-- DOCUMENTS -->
    <div class="section" id="sec-documents">
      <div class="filter-bar">
        <input class="search-input" id="docSearch" placeholder="🔍 Cari dokumen..." oninput="renderDocuments(1)">
        <label>Status:</label>
        <select id="docFilter" onchange="renderDocuments(1)">
          <option value="">Semua</option>
          <option value="pending">Pending</option>
          <option value="approved">Disetujui</option>
          <option value="revision">Revisi</option>
        </select>
      
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

    <!-- TRACKING -->
    <div class="section" id="sec-tracking">
      <div class="grid-3">
        <div class="box">
          <div class="box-title">Peta Live Tracking <span id="mapLastUpdate"></span></div>
          <div id="adminMap"></div>
          <div class="map-legend" id="mapLegend"></div>
        </div>
        <div class="box">
          <div class="box-title">Kontainer Aktif <span id="activeCount"></span></div>
          <div id="trackingList" style="max-height:380px;overflow-y:auto"></div>
        </div>
      </div>
    </div>

    <!-- USERS -->
    <div class="section" id="sec-users">
      <div class="filter-bar">
        <button class="btn btn-primary" onclick="openAddUser()">+ Tambah Pengguna</button>
      </div>
      <div class="box">
        <table class="data-table">
          <thead><tr><th>ID</th><th>Nama</th><th>Username</th><th>Role</th><th>Email</th><th>Port/Perusahaan</th><th>Status</th><th>Aksi</th></tr></thead>
          <tbody id="userTable"></tbody>
        </table>
      </div>
    </div>

    <!-- NOTIFICATIONS -->
    <div class="section" id="sec-notifications">
      <div class="box">
        <div class="box-title">Semua Notifikasi <span id="unreadCount"></span>
          <button class="btn btn-ghost btn-sm" onclick="markAllRead()">✓ Tandai Semua Dibaca</button>
        </div>
        <div id="notifList"></div>
        <div class="pagination" id="pg-notif"></div>
      </div>
    </div>

    <!-- REPORTS -->
    <div class="section" id="sec-reports">
      <div class="filter-bar">
        <label>Bulan:</label>
        <select id="rptMonth" onchange="renderReports()">
          <option value="">Semua Bulan</option>
          <option value="1">Januari</option><option value="2">Februari</option>
          <option value="3">Maret</option><option value="4">April</option>
          <option value="5">Mei</option><option value="6">Juni</option>
          <option value="7">Juli</option><option value="8">Agustus</option>
          <option value="9">September</option><option value="10">Oktober</option>
          <option value="11">November</option><option value="12">Desember</option>
        </select>
        <label>Tahun:</label>
        <select id="rptYear" onchange="renderReports()">
          <option value="">Semua Tahun</option>
          <option value="2024">2024</option>
          <option value="2025">2025</option>
          <option value="2026">2026</option>
        </select>
        <button class="btn btn-success" onclick="downloadExcel()">📥 Download Excel</button>
      </div>
      <div class="stat-grid" id="reportStats"></div>
      <div class="grid-2">
        <div class="box"><div class="box-title">Status Kontainer</div><canvas id="reportChart" style="max-height:250px"></canvas></div>
        <div class="box"><div class="box-title">Performa Vessel</div><div id="vesselPerf"></div></div>
      </div>
      <div class="box">
        <div class="box-title">Data Kontainer <span id="reportTableInfo" style="font-weight:normal;"></span></div>
        <table class="data-table">
          <thead><tr><th>ID</th><th>Booking</th><th>Vessel</th><th>Asal</th><th>Tujuan</th><th>Tipe</th><th>Komoditi</th><th>Berat (kg)</th><th>Status</th><th>ETA</th><th>Tanggal</th></tr></thead>
          <tbody id="reportTable"></tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- MODAL: Detail Kontainer -->
<div class="modal-overlay" id="modalDetail">
  <div class="modal" style="width:700px">
    <h3 id="detailTitle">Detail Kontainer</h3>
    <p class="sub" id="detailSub"></p>
    <div id="detailContent"></div>
    <div class="modal-footer"><button class="btn btn-ghost" onclick="closeModal('modalDetail')">Tutup</button></div>
  </div>
</div>

<!-- MODAL: Status Dokumen -->
<div class="modal-overlay" id="modalDocStatus">
  <div class="modal" style="width:440px">
    <h3>Update Status Dokumen</h3>
    <p class="sub" id="docStatusSub"></p>
    <div class="form-group"><label>Status Baru</label>
      <select id="newDocStatus">
        <option value="approved">✅ Disetujui</option>
        <option value="revision">❌ Perlu Revisi</option>
        <option value="pending">⏳ Pending</option>
      </select>
    </div>
    <div class="form-group"><label>Catatan untuk Pengguna</label>
      <textarea id="docStatusNote" placeholder="Catatan akan dikirim ke stakeholder..."></textarea>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modalDocStatus')">Batal</button>
      <button class="btn btn-primary" onclick="saveDocStatus()">Simpan</button>
    </div>
  </div>
</div>

<!-- MODAL: Tambah/Edit Pengguna -->
<div class="modal-overlay" id="modalUser">
  <div class="modal" style="width:480px">
    <h3 id="modalUserTitle">Tambah Pengguna</h3>
    <p class="sub">Data akun pengguna sistem CMS</p>
    <div class="form-row">
      <div class="form-group"><label>Username</label><input id="u_username" placeholder="contoh: operator3"></div>
      <div class="form-group"><label>Password</label><input id="u_password" type="password" placeholder="Kosongkan jika tidak ganti"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Nama Lengkap</label><input id="u_name" placeholder="Nama lengkap"></div>
      <div class="form-group"><label>Role</label>
        <select id="u_role">
          <option value="admin">Admin</option>
          <option value="operator">Operator</option>
          <option value="stakeholder" selected>Stakeholder</option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Email</label><input id="u_email" type="email" placeholder="email@contoh.com"></div>
      <div class="form-group"><label>Port / Perusahaan</label><input id="u_port" placeholder="Tanjung Perak / PT. XYZ"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modalUser')">Batal</button>
      <button class="btn btn-primary" onclick="saveUser()">💾 Simpan</button>
    </div>
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
<script src="../assets/js/admin.js"></script>
</body>
</html>
EOD;

file_put_contents('frontend/dashboards/admin.html', $content);
echo "admin.html restored and updated.\n";
