
## Vibes Kost, Sistem Informasi Penyewaan Kost Berbasis Website

## Nama Kelompok & Anggota
Kelompok 8: - Muhammad Faizal (701230055)
            - Suci Ramadani (701230025)
            - Rosita Br Bangun (701230082)



## Deskripsi Singkat Aplikasi
Perangkat  lunak sistem informasi sewa kost (Vibes Kost) berbasis web ini merupakan perangkat lunak yang digunakan untuk memudahkan pengguna dalam mencari kost-kostan sekitaran Mendalo-Sungai Duren. Berfungsi sebagai saran untuk melihat dan mencari informasi kost-kostan serta melakukan pembayaran tanpa harus datang ke lokasinya langsung.

## Tujuan Sistem / Permasalahan yang Diselesaikan
Aplikasi ini bertujuan untuk:

- Mengatasi kesulitan penyewa dalam mencari kost yang sesuai.
- Menyediakan informasi kamar kost secara real-time.
- Mempermudah pemilik kost dalam memantau pemesanan dan pembayaran.
- Mengurangi miskomunikasi antara penyewa dan pemilik kost dalam proses booking dan verifikasi.
- Mencatat semua proses pemesanan, pembayaran, dan manajemen kamar secara rapi dan aman.

## Teknologi yang Digunakan
Aplikasi ini dibangun menggunakan PHP 8 sebagai bahasa pemrograman utama. Untuk styling, proyek ini memanfaatkan Bootstrap 5 sehingga tampilan menjadi responsif dan modern. Basis data yang digunakan adalah MySQL, yang dikelola melalui struktur relasional sesuai kebutuhan sistem. Dalam proses pengembangan, digunakan berbagai tools seperti GitHub untuk version control, Visual Studio Code (VSCode) sebagai code editor, dan Draw.io san PlantUML untuk pembuatan diagram UML. Untuk deployment, aplikasi ini dihosting menggunakan layanan InfinityFree sebagai platform hosting gratis.

## Cara Menjalankan Aplikasi

## 1. Instalasi (Local XAMPP / Laragon)
1. Clone repository:
   git clone https://github.com/Rafa-Hits/Final_Project_RPL

2. Pindahkan folder ke:
   C:\laragon\www\
3. Buat database baru:
   vibekos
4. Import file SQL:
   /database/vibekos.sql

### 2. Konfigurasi

Edit file:
includes/config.php

Sesuaikan dengan local server:
$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "vibekos";

### 3. Menjalankan Project

Akses di browser:
http://localhost

## Akun Demo
Admin
email: admin@vibeskost.com
password: password

Pemilik Kost
email: pemilik@vibeskost.com
password: password

Penyewa
email: penyewa@vibeskost.com
password: password

## Link Deployment / Demo
Website Hosting = [https://vibeskost.rf.gd](https://vibeskost.rf.gd)
Repo GitHub     = [https://github.com/Rafa-Hits/Final_Project_RPL](https://github.com/Rafa-Hits/Final_Project_RPL) 
Video Demo      =                                                                        

## Screenshot Halaman Utama
<img width="1904" height="973" alt="Screenshot 2025-12-01 162306" src="https://github.com/user-attachments/assets/fb1648b5-fa1e-407c-9bc8-43bfea796910" />


## Catatan Tambahan
Fitur yang Sudah Berjalan:
* Pencarian kost
* Detail kamar
* Pemesanan kost
* Upload bukti pembayaran
* Verifikasi pembayaran oleh pemilik
* Kelola kost & kamar
* Role-based access (Admin, Pemilik, Penyewa)

Keterbatasan Sistem:
* Fitur ubah foto profil untuk penyewa belum bisa
* Fitur review tersedia di database tetapi UI belum ditampilkan
* Tidak ada fitur maps meskipun DB menyiapkan latitude & longitude
* Sistem notifikasi bekerja via database trigger tetapi tampilannya masih sederhana

Petunjuk Penggunaan:
* Jika upload bukti pembayaran error → pastikan folder `uploads/bukti_bayar/` memiliki permission.
* Jika halaman pemesanan blank → cek error log hosting (InfinityFree kadang memblokir query tertentu).
* Pastikan database hosting sama dengan local SQL, terutama nama kolom & tipe data.

## Keterangan Tugas

Project ini dibuat untuk memenuhi Tugas Final Project
Mata Kuliah Rekayasa Perangkat Lunak (RPL)
Dosen Pengampu: Dila Nurlaila, M.Kom

Dokumen yang disiapkan:

* SRS
* Use Case Diagram
* Activity Diagram
* Class Diagram
* ERD
* Struktur Database

## Terima Kasih

Aplikasi ini dibuat sebagai bagian dari pembelajaran analisis kebutuhan, perancangan sistem, dan implementasi web pada mata kuliah Rekayasa Perangkat Lunak.
