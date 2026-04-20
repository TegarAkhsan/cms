# CMS — Container Monitoring System
## PHP + MySQL (XAMPP)

---

## 📁 STRUKTUR FOLDER
```text
cms/
├── backend/              ← File backend PHP (API dan configurasi DB)
│   ├── api/              ← Endpoint API (auth, containers, documents, dll)
│   ├── database/         ← File SQL dan koneksi DB
│   └── uploads/          ← Folder media dokumen (Otomatis Dibuat)
├── frontend/             ← File frontend HTML, CSS, JS
│   ├── auth/             ← Halaman Autentikasi (Login)
│   └── dashboards/       ← Halaman Dashboard (Admin, Operator, Stakeholder)
├── README.md             ← Panduan instalasi
└── setup_db.php          ← Script bantu instalasi database otomatis
```

---

## 🚀 CARA INSTALL (Ikuti Urutan!)

### Langkah 1 — Clone dan Copy File ke htdocs
1. Setelah berhasil melakukan clone repository ke komputer Anda, pastikan nama folder utamanya adalah **`cms`** (ubah namanya jika belum).
2. Pindahkan (Copy atau Cut) seluruh folder **`cms`** tersebut ke dalam folder **`htdocs`** di XAMPP Anda.
3. Umumnya, folder tersebut akan berada di path berikut:
   ```text
   C:\xampp\htdocs\cms\
   ```
   *(Pastikan struktur di web server Anda terbaca sebagai `localhost/cms/...` tanpa folder ganda di dalamnya)*

### Langkah 2 — Jalankan XAMPP
- Buka aplikasi **XAMPP Control Panel** dari desktop atau Start Menu komputer Anda.
- Klik tombol **Start** pada baris **Apache**.
- Klik tombol **Start** pada baris **MySQL**.
- Pastikan modul Apache dan MySQL memiliki latar berwarna **hijau** ✅.

### Langkah 3 — Import Database & Setup
Anda bisa memilih cara otomatis (Paling Direkomendasikan) atau melakukan impor manual menggunakan phpMyAdmin.

#### Opsi A: Cara Otomatis (Sangat Direkomendasikan)
Cara termudah, cukup buka browser dan akses URL berikut untuk menginstall skema database dan data awalnya:
```text
http://localhost/cms/setup_db.php
```
Script tersebut akan otomatis memastikan database bernama `cms_db` terbuat, kemudian meng-import seluruh tabel dan data akun ke dalamnya. Tunggu sampai muncul teks indikator **"ALL DONE!"**. 

#### Opsi B: Cara Manual melalui phpMyAdmin
1. Buka browser dan ketik alamat → `http://localhost/phpmyadmin`
2. Klik menu **New / Baru** pada sidebar di kiri untuk membuat database kosong.
3. Beri nama database **`cms_db`** dan klik tombol **Create / Buat**.
4. Klik pada database `cms_db` di sidebar tersebut, lalu pilih tab **Import** di deretan menu atas.
5. Klik **Choose File** / Browse, dan arahkan ke file import di folder sistem:
   `C:\xampp\htdocs\cms\backend\database\cms_db.sql`
6. Lalu scroll ke bawah dan klik **Go / Import**.
7. Pastikan ada pemberitahuan hijau bahwa data sukses ter-import sepenuhnya ✅.

### Langkah 4 — Buka Aplikasi Login
Jika database telah ter-import sepenuhnya tanpa error, akses sistem menggunakan browser menuju ke alamat instalasi frontend:
```text
http://localhost/cms/frontend/auth/login.html
```

---

## 🔑 AKUN LOGIN DEFAULT

Gunakan rincian user di bawah ini untuk tahapan percobaan awal (Data ini default bawaan `cms_db.sql`). Jangan lupa perhatikan huruf kapital jika diperlukan.

| Role        | Username       | Password   |
|-------------|---------------|------------|
| Admin       | admin          | admin123   |
| Operator    | operator1      | op123      |
| Stakeholder | stakeholder1   | sk123      |

*(Sebagai admin, silakan ganti password operator dan buat akun baru saat mencoba di production).*

---

## ⚙️ KONFIGURASI DATABASE TAMBAHAN

Jika pengguna MySQL di XAMPP Anda **menggunakan custom password** (bukan bawaan instalasi default XAMPP yang bernilai kosong `""`), Anda diharuskan mengedit parameter koneksinya di `backend/database/db.php`:
```php
define('DB_HOST', 'localhost');   // Host MySQL
define('DB_NAME', 'cms_db');      // Nama database 
define('DB_USER', 'root');        // Username MySQL
define('DB_PASS', '');            // ← Isikan password MySQL XAMPP Anda di sini jika ada
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
- Filter Hari, Bulan, Tahun dan Ekspor Laporan Kontainer & Dokumen ke bentuk file Excel (.xlsx)

### 🟢 Operator
- KPI bar status kontainer
- Update status & posisi kontainer (auto-log timeline + notif ke pemilik)
- Upload dokumen dengan file nyata (PDF/JPG/PNG)
- Verifikasi / revisi dokumen dari stakeholder
- Yard Map visual (18 blok A1–C6 dengan simulasi occupancy capacity tracker)
- Live tracking status on-delivery
- Preview Dokumen secara cepat dalam jendela popup

### 🔵 Stakeholder / Pengguna Jasa
- Monitor kontainer milik sendiri
- Progress bar 7 langkah pengiriman 
- Upload dokumen ke operator (file nyata)
- Preview real-time dalam browser dan unduh dokumen yang sudah diapprove
- Resubmit ulang dokumen yang mendapat status revisi
- Widget Panel dan Notifikasi live setiap ada perubahan

---

## 🔄 ALUR SINKRONISASI DATA
```text
Operator / Stakeholder mengupdate Status atau File
                  ↓  (Kirim API Request)
    Backend PHP Memproses Permintaan
                  ↓
 MySQL Eksekusi dan Menyimpan (cms_db)
                  ↓
  Aktivitas baru dimasukkan ke daftar Timeline 
  + Broadcast notifikasi personal terbentuk
                  ↓
Semua pihak yang terhubung memuat data & notifikasi baru
```

---

## 📋 PERSYARATAN SERVER (REQUIREMENTS)
- PHP Runtime versi 7.4 ke atas dengan ekstensi `PDO` dan `PDO_MySQL` ter-enable.
- Database Engine MySQL 5.7+ atau MariaDB 10.3+.
- Apache Web Server.
- ✅ *Sangat dianjurkan menggunakan package **XAMPP** atau **Laragon** versi standar terbaru yang sudah memiliki semua kriteria dependensi yang diperlukan di atas.*
