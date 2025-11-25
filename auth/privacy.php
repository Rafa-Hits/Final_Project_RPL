<?php
include '../includes/config.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vibes Kost - Sistem Informasi Kost Mendalo-Sungai Duren</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: bold;
            color: #667eea !important;
        }
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .kost-card {
            transition: transform 0.3s ease;
        }
        .kost-card:hover {
            transform: translateY(-5px);
        }
        .footer {
            background: #2c3e50;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-home"></i> Vibes Kost
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="../index.php">Home</a>
                    </li>
                </ul>
                
                <div class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo $_SESSION['nama']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo $_SESSION['role']; ?>/dashboard.php">Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?php echo $_SESSION['role']; ?>/profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="auth/logout.php">Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a class="nav-link" href="register.php">Daftar Akun </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

<!-- Hero Section -->
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center">
                <h1 class="display-4 mb-4">Kebijakan Privasi</h1>
                <p class="lead">Bagaimana kami melindungi dan mengelola data pribadi Anda</p>
            </div>
        </div>
    </div>
</section>

<!-- Privacy Content -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5">
                        <!-- Last Updated -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Terakhir diperbarui:</strong> 1 Januari 2025
                        </div>

                        <h4 class="text-primary mb-4">1. Pengantar</h4>
                        <p>
                            Vibes Kost menghargai privasi Anda dan berkomitmen untuk melindungi data pribadi 
                            yang Anda bagikan kepada kami. Kebijakan Privasi ini menjelaskan bagaimana kami 
                            mengumpulkan, menggunakan, mengungkapkan, dan melindungi informasi pribadi Anda.
                        </p>

                        <h4 class="text-primary mb-4 mt-5">2. Informasi yang Kami Kumpulkan</h4>
                        <h5>2.1. Informasi yang Anda Berikan</h5>
                        <ul>
                            <li><strong>Data Profil:</strong> Nama, email, nomor telepon, alamat, foto profil</li>
                            <li><strong>Data Verifikasi:</strong> KTP, SIM, atau dokumen identitas lainnya</li>
                            <li><strong>Data Finansial:</strong> Informasi rekening bank (untuk pemilik kost)</li>
                            <li><strong>Data Transaksi:</strong> Riwayat pemesanan dan pembayaran</li>
                        </ul>

                        <h5>2.2. Informasi yang Dikumpulkan Otomatis</h5>
                        <ul>
                            <li><strong>Data Teknis:</strong> Alamat IP, jenis browser, perangkat yang digunakan</li>
                            <li><strong>Data Penggunaan:</strong> Halaman yang dikunjungi, waktu akses, klik</li>
                            <li><strong>Data Lokasi:</strong> Informasi lokasi umum (jika diizinkan)</li>
                        </ul>

                        <h4 class="text-primary mb-4 mt-5">3. Cara Kami Menggunakan Informasi</h4>
                        <h5>3.1. Untuk Penyediaan Layanan</h5>
                        <ul>
                            <li>Memproses pendaftaran dan verifikasi akun</li>
                            <li>Memfasilitasi pemesanan dan pembayaran</li>
                            <li>Mengirim notifikasi dan pembaruan penting</li>
                            <li>Memberikan dukungan pelanggan</li>
                        </ul>

                        <h5>3.2. Untuk Peningkatan Layanan</h5>
                        <ul>
                            <li>Menganalisis pola penggunaan untuk perbaikan fitur</li>
                            <li>Mengembangkan produk dan layanan baru</li>
                            <li>Personalisasi pengalaman pengguna</li>
                            <li>Melakukan riset dan pengembangan</li>
                        </ul>

                        <h5>3.3. Untuk Keamanan</h5>
                        <ul>
                            <li>Mendeteksi dan mencegah penipuan</li>
                            <li>Melindungi keamanan sistem dan data</li>
                            <li>Memenuhi kewajiban hukum</li>
                        </ul>

                        <h4 class="text-primary mb-4 mt-5">4. Berbagi Informasi</h4>
                        <p>
                            Kami tidak menjual, memperdagangkan, atau mengalihkan informasi pribadi Anda 
                            kepada pihak ketiga, kecuali dalam kondisi berikut:
                        </p>

                        <h5>4.1. Berbagi yang Diperlukan</h5>
                        <ul>
                            <li><strong>Antar Pengguna:</strong> Informasi kontak antara penyewa dan pemilik kost</li>
                            <li><strong>Penyedia Layanan:</strong> Partner pembayaran dan verifikasi</li>
                            <li><strong>Kepatuhan Hukum:</strong> Memenuhi permintaan hukum yang sah</li>
                        </ul>

                        <h5>4.2. Berbagi dengan Persetujuan</h5>
                        <p>
                            Kami akan meminta persetujuan Anda sebelum membagikan informasi pribadi untuk 
                            keperluan lain di luar yang telah disebutkan.
                        </p>

                        <h4 class="text-primary mb-4 mt-5">5. Penyimpanan dan Keamanan Data</h4>
                        <h5>5.1. Penyimpanan Data</h5>
                        <p>
                            Data pribadi Anda disimpan di server yang aman di wilayah Indonesia. 
                            Kami menyimpan data selama diperlukan untuk menyediakan layanan dan 
                            mematuhi kewajiban hukum.
                        </p>

                        <h5>5.2. Keamanan Data</h5>
                        <ul>
                            <li>Enkripsi data sensitif selama transmisi dan penyimpanan</li>
                            <li>Firewall dan sistem deteksi intrusi</li>
                            <li>Akses terbatas berdasarkan kebutuhan (need-to-know basis)</li>
                            <li>Audit keamanan berkala</li>
                        </ul>

                        <h4 class="text-primary mb-4 mt-5">6. Hak-Hak Anda</h4>
                        <h5>6.1. Hak Akses dan Koreksi</h5>
                        <p>
                            Anda memiliki hak untuk mengakses dan memperbarui informasi pribadi Anda 
                            kapan saja melalui pengaturan akun.
                        </p>

                        <h5>6.2. Hak Penghapusan</h5>
                        <p>
                            Anda dapat meminta penghapusan data pribadi, dengan ketentuan bahwa 
            penghapusan dapat mempengaruhi kemampuan kami untuk menyediakan layanan.
                        </p>

                        <h5>6.3. Hak Pembatasan Pemrosesan</h5>
                        <p>
                            Anda dapat meminta pembatasan pemrosesan data pribadi dalam kondisi tertentu.
                        </p>

                        <h5>6.4. Hak Oposisi</h5>
                        <p>
                            Anda dapat menolak pemrosesan data pribadi untuk pemasaran langsung.
                        </p>

                        <h4 class="text-primary mb-4 mt-5">7. Cookie dan Teknologi Pelacakan</h4>
                        <h5>7.1. Jenis Cookie yang Kami Gunakan</h5>
                        <ul>
                            <li><strong>Cookie Esensial:</strong> Untuk operasional dasar website</li>
                            <li><strong>Cookie Preferensi:</strong> Untuk mengingat pengaturan Anda</li>
                            <li><strong>Cookie Analitik:</strong> Untuk memahami penggunaan website</li>
                            <li><strong>Cookie Pemasaran:</strong> Untuk menampilkan iklan yang relevan</li>
                        </ul>

                        <h5>7.2. Mengelola Cookie</h5>
                        <p>
                            Anda dapat mengelola preferensi cookie melalui pengaturan browser. 
                            Namun, menonaktifkan cookie esensial dapat mempengaruhi fungsi website.
                        </p>

                        <h4 class="text-primary mb-4 mt-5">8. Data Anak-Anak</h4>
                        <p>
                            Layanan kami tidak ditujukan untuk anak di bawah 17 tahun. Kami tidak 
                            secara sadar mengumpulkan informasi pribadi dari anak di bawah 17 tahun. 
                            Jika kami mengetahui telah mengumpulkan informasi dari anak di bawah 17 tahun, 
                            kami akan mengambil langkah untuk menghapus informasi tersebut.
                        </p>

                        <h4 class="text-primary mb-4 mt-5">9. Perubahan Kebijakan Privasi</h4>
                        <p>
                            Kami dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu. 
                            Perubahan akan diumumkan melalui Platform dan berlaku efektif 30 hari 
                            setelah pengumuman. Penggunaan berkelanjutan layanan setelah perubahan 
                            berarti Anda menerima kebijakan yang diperbarui.
                        </p>

                        <h4 class="text-primary mb-4 mt-5">10. Kontak dan Pengaduan</h4>
                        <p>
                            Jika Anda memiliki pertanyaan, kekhawatiran, atau pengaduan mengenai 
                            Kebijakan Privasi ini atau praktik privasi kami, silakan hubungi:
                        </p>
                        <ul>
                            <li><strong>Email:</strong> privacy@vibeskost.com</li>
                            <li><strong>Telepon:</strong> +62 812-3456-7891</li>
                            <li><strong>Alamat:</strong> Jl. Mendalo Raya, Mendalo, Jambi</li>
                        </ul>

                        <h5>10.1. Proses Pengaduan</h5>
                        <p>
                            Kami akan menanggapi semua pengaduan dalam waktu 7 hari kerja dan 
                            berusaha menyelesaikan masalah secara memuaskan.
                        </p>

                        <div class="alert alert-success mt-5">
                            <i class="fas fa-shield-alt"></i>
                            <strong>Komitmen Kami:</strong> Vibes Kost berkomitmen untuk melindungi 
                            privasi Anda dan memastikan bahwa data pribadi Anda dikelola dengan 
                            transparan, aman, dan bertanggung jawab.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container py-5">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5><i class="fas fa-home"></i> Vibes Kost</h5>
                    <p>Sistem informasi kost-kostan terpercaya di wilayah Mendalo - Sungai Duren. Temukan kost impian Anda dengan mudah dan cepat.</p>
                </div>
                <div class="col-md-2 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="../index.php" class="text-light">Home</a></li>                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5>Kontak Kami</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-map-marker-alt"></i> Mendalo - Sungai Duren, Jambi</li>
                        <li><i class="fas fa-phone"></i> +62 822-8501-2956</li>
                        <li><i class="fas fa-envelope"></i> info@vibeskost.com</li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5>Follow Kami</h5>
                    <div class="social-links">
                        <a href="wa.me/+6282285012956" class="text-light me-3"><i class="fab fa-whatsapp fa-2x"></i></a>
                        <a href="https://www.instagram.com/c23si.ofc?igsh=MXFhNWtrb2l6ZHdpYw==" class="text-light me-3"><i class="fab fa-instagram fa-2x"></i></a>
                        <a href="https://www.tiktok.com/@si.c230?is_from_webapp=1&sender_device=pc" class="text-light me-3"><i class="fab fa-tiktok fa-2x"></i></a>
                    </div>
                </div>
            </div>
            <hr class="bg-light">
            <div class="row">
                <div class="col-12 text-center">
                    <p>&copy; 2025 Vibes Kost. All rights reserved. Developed by @Muhammad Faizal @Rosita Br Bangun @Suci Ramadani</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple search functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
        });
    </script>
</body>
</html>