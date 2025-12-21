# Manajemen dan Informasi BEM Kampus

Sistem manajemen dan informasi BEM Kampus untuk pengolahan data, manajemen proposal, serta komunikasi antar anggota. Platform ini bertujuan untuk mempermudah pengelolaan kegiatan organisasi BEM dengan berbagai fitur yang berguna bagi pengurus dan anggota.

## Fitur Utama

### 1. **Manajemen Proposal** ✅ SUDAH IMPLEMENTASI
   - Menyimpan, mengelola, dan memproses proposal acara.
   - Fitur upload dan download dokumen proposal (PDF, DOC, DOCX).
   - **Workflow Status Lengkap**: pending_menteri → pending_sekretaris → pending_bendahara → pending_wakil_presiden → pending_presiden → approved/rejected/revisi
   - Kolom keterangan untuk catatan reviewer.
   - Badge status berwarna (kuning untuk pending, hijau untuk approved, merah untuk rejected, dll).
   - Filter proposal berdasarkan status, kementerian, dan tanggal.
   - Badge notifikasi di navigation menu untuk proposal pending.
   - Tracking pengaju proposal per user.

### 2. **Manajemen Anggota** ✅ SUDAH IMPLEMENTASI
   - Data lengkap anggota BEM, termasuk peran, kontak, dan status aktif.
   - **Role Management Lengkap**:
     * Super Admin (full access)
     * Presiden BEM
     * Wakil Presiden BEM
     * Sekretaris
     * Bendahara
     * Menteri
     * Anggota
   - Manajemen role per user dengan Spatie Laravel Permission.
   - Badge jumlah user di navigation.

### 3. **Jadwal Kegiatan**
   - Kalender kegiatan BEM, seperti rapat, acara, dan deadline proposal.
   - Pengingat kegiatan untuk anggota yang terlibat.

### 4. **Manajemen Keuangan**
   - Pengelolaan anggaran dan laporan keuangan.
   - Input dan pemantauan transaksi keuangan (pemasukan dan pengeluaran).
   - Laporan keuangan yang dapat diexport (misal, Excel, PDF).

### 5. **Manajemen Tugas**
   - Pembagian tugas antara anggota BEM.
   - Pengaturan tenggat waktu dan status tugas.
   - Fitur notifikasi jika ada tugas yang mendekati deadline.

### 6. **Sistem Komunikasi**
   - Fitur chat antar anggota untuk mempermudah diskusi.
   - Pengumuman untuk seluruh anggota BEM.

### 7. **Dokumentasi dan Arsip**
   - Tempat penyimpanan dokumen organisasi seperti notulen rapat, foto acara, dan laporan tahunan.
   - Pencarian dokumen berdasarkan kategori atau tag.

### 8. **Pengaturan Pengguna dan Izin Akses** ✅ SUDAH IMPLEMENTASI
   - Manajemen hak akses berdasarkan peran dengan Filament Shield.
   - Fitur login untuk setiap anggota dengan keamanan berbasis role.
   - **Policy & Permission System**: view, create, update, delete, restore, force_delete, dll.
   - Middleware untuk kontrol akses berdasarkan role.
   - Generate permissions otomatis untuk resources.

### 8.5. **Manajemen Kementerian** ✅ SUDAH IMPLEMENTASI
   - CRUD data kementerian (5 kementerian default).
   - Relasi dengan proposal.
   - Badge jumlah kementerian di navigation.

### 8.6. **Dark Mode** ✅ SUDAH IMPLEMENTASI
   - Toggle dark/light mode.
   - Preferensi tersimpan di localStorage.

### 9. **Statistik dan Laporan**
   - Melihat perkembangan aktivitas BEM secara keseluruhan.
   - Laporan mingguan/bulanan mengenai kinerja tim dan penggunaan anggaran.

## Teknologi yang Digunakan

- **Backend**: Laravel 11
- **Admin Panel**: Filament 3
- **Frontend**: Blade, Tailwind CSS, Vite, Alpine.js
- **Database**: SQLite (development)
- **Authentication**: Laravel + Spatie Laravel Permission
- **Role Management**: Filament Shield
- **Icons**: Heroicons

## Cara Instalasi

```bash
# Clone repository
git clone [repo-url]
cd Laravel-SytemManagementORG

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate:fresh --seed

# Build assets
npm run build

# Jalankan server
php artisan serve
```

## Credentials Login

- **Super Admin**: admin@mail.com / password
- **Presiden**: presiden@mail.com / password
- **Sekretaris**: sekretaris@mail.com / password
- **Bendahara**: bendahara@mail.com / password
- **Menteri**: menteri@mail.com / password
- **Anggota**: anggota@mail.com / password

Kontribusi
Jika Anda ingin berkontribusi pada proyek ini, silakan lakukan fork pada repository ini dan buat pull request dengan perubahan yang diinginkan. Pastikan untuk mengikuti pedoman pengkodean yang telah ditetapkan.

Lisensi
Proyek ini menggunakan lisensi MIT. Lihat file LICENSE untuk informasi lebih lanjut.

create by arioveisa.me
