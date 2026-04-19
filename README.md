# CMS — Container Monitoring System
## PHP + MySQL (XAMPP)

---

## 📁 STRUKTUR FOLDER
```
cms/
├── index.html            ← Halaman Login
├── admin.html            ← Dashboard Admin
├── operator.html         ← Dashboard Operator
├── stakeholder.html      ← Dashboard Pengguna Jasa
├── api-client.js         ← API Client (frontend ↔ PHP)
├── fix_passwords.php     ← Jalankan SEKALI lalu hapus!
├── api/
│   ├── auth.php          ← Login / Logout / Session
│   ├── containers.php    ← CRUD Kontainer
│   ├── documents.php     ← Upload & Verifikasi Dokumen
│   ├── notifications.php ← Sistem Notifikasi
│   ├── stats.php         ← Statistik Dashboard
│   └── users.php         ← Manajemen Pengguna
├── database/
│   ├── db.php            ← Koneksi MySQL PDO
│   └── cms_db.sql        ← Script SQL (import ke phpMyAdmin)
└── uploads/              ← File upload (otomatis dibuat)
```

---

## 🚀 CARA INSTALL (Ikuti Urutan!)

### Langkah 1 — Jalankan XAMPP
- Buka **XAMPP Control Panel**
- Klik **Start** pada **Apache**
- Klik **Start** pada **MySQL**
- Pastikan keduanya hijau ✅

### Langkah 2 — Import Database
1. Buka browser → `http://localhost/phpmyadmin`
2. Klik **tab SQL** di bagian atas
3. Buka file `database/cms_db.sql` dengan Notepad
4. **Copy semua isinya** → Paste ke kotak SQL phpMyAdmin
5. Klik **Go**
6. Pastikan muncul database **`cms_db`** di sidebar kiri ✅

### Langkah 3 — Copy File ke htdocs
```
C:\xampp\htdocs\cms\
```
Letakkan **semua file & folder** di sini.

### Langkah 4 — Set Password
Buka browser → `http://localhost/cms/fix_passwords.php`

Tunggu sampai muncul pesan hijau "✅ Semua password berhasil diset!"

> ⚠️ **HAPUS `fix_passwords.php`** setelah selesai!

### Langkah 5 — Buka Aplikasi
```
http://localhost/cms/index.html
```

---

## 🔑 AKUN LOGIN

| Role        | Username     | Password   |
|-------------|-------------|------------|
| Admin       | admin        | admin123   |
| Operator 1  | operator1    | op123      |
| Operator 2  | operator2    | op456      |
| Stakeholder 1 | stakeholder1 | sk123    |
| Stakeholder 2 | stakeholder2 | sk456    |

---

## ⚙️ KONFIGURASI DATABASE
Edit file `database/db.php` jika perlu:
```php
define('DB_HOST', 'localhost');   // Host MySQL
define('DB_NAME', 'cms_db');      // Nama database
define('DB_USER', 'root');        // Username MySQL
define('DB_PASS', '');            // Password (kosong = default XAMPP)
```

---

## ✅ FITUR SISTEM

### 🔴 Admin
- Dashboard statistik + Chart.js (line chart & doughnut)
- CRUD Kontainer (tambah, edit, hapus)
- Update status dokumen (approve / revisi / pending)
- Peta tracking real-time (Leaflet.js + OpenStreetMap)
- Manajemen pengguna (tambah, edit, hapus, ganti password)
- Laporan & statistik performa operator/vessel

### 🟢 Operator
- KPI bar status kontainer
- Update status & posisi kontainer (auto-log timeline + notif ke pemilik)
- Upload dokumen dengan file nyata (PDF/JPG/PNG)
- Verifikasi / revisi dokumen dari stakeholder
- Yard Map visual (18 blok A1–C6 dengan occupancy)
- Live tracking on-delivery

### 🔵 Stakeholder / Pengguna Jasa
- Monitor kontainer milik sendiri
- Progress bar 7 langkah pengiriman
- Upload dokumen ke operator (file nyata)
- Unduh dokumen yang sudah diapprove
- Resubmit dokumen yang perlu revisi
- Notifikasi real-time setiap perubahan

---

## 🔄 SINKRONISASI DATA
```
Operator update status
      ↓
PHP simpan ke MySQL (cms_db)
      ↓
Auto: catat timeline + kirim notifikasi ke pemilik
      ↓
Semua role refresh halaman → data terbaru dari MySQL
```

---

## 📋 REQUIREMENTS
- PHP 7.4+ dengan extension PDO + PDO_MySQL
- MySQL 5.7+ atau MariaDB 10.3+
- Apache Web Server
- Semua sudah tersedia di XAMPP ✅
