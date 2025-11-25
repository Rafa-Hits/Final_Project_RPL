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
            <a class="navbar-brand" href="index.php">
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
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php#kost-list">Cari Kost</a>
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
                        <a class="nav-link" href="login.php">Login</a>
                        <a class="nav-link" href="register.php">Daftar Akun </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

<!-- Hero Section -->
<section class="py-5 bg-primary text-white text-center">
    <div class="container">
        <h1 class="fw-bold mb-2">Tentang Kami</h1>
        <p class="mb-0">Mengenal Lebih Dekat Vibes Kost</p>
    </div>
</section>

<!-- Content -->
<section class="py-4">
    <div class="container">
        <div class="col-lg-8 mx-auto">

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4">

                    <div class="small text-muted mb-3">
                        <i class="fas fa-building"></i> Vibes Kost | Est. 2025
                    </div>

                    <div class="mb-4">
                        <h5 class="fw-bold text-primary">
                            <i class="fas fa-info-circle me-2"></i>1. Siapa Kami?
                        </h5>
                        <p class="mb-0">
                            Vibes Kost adalah platform yang menyediakan layanan informasi kost
                            dan membantu pemilik kost dalam mengelola data kamar, penyewa, serta transaksi
                            secara lebih mudah, cepat, dan terstruktur.
                        </p>
                    </div>

                    <hr>

                    <div class="mb-4">
                        <h5 class="fw-bold text-primary">
                            <i class="fas fa-bullseye me-2"></i>2. Visi Kami
                        </h5>
                        <p class="mb-0">
                            Menjadi platform pengelolaan kost yang modern dan terpercaya,
                            dengan memberikan pengalaman terbaik bagi pemilik dan penyewa.
                        </p>
                    </div>

                    <hr>

                    <div class="mb-4">
                        <h5 class="fw-bold text-primary">
                            <i class="fas fa-lightbulb me-2"></i>3. Misi Kami
                        </h5>
                        <ul class="mb-0">
                            <li>Menyediakan sistem pengelolaan kost yang praktis dan efisien</li>
                            <li>Mempermudah komunikasi antara pemilik dan penyewa</li>
                            <li>Menghadirkan layanan berbasis teknologi yang mudah diakses</li>
                        </ul>
                    </div>

                    <hr>

                    <div class="mb-4">
                        <h5 class="fw-bold text-primary">
                            <i class="fas fa-cogs me-2"></i>4. Layanan Kami
                        </h5>
                        <ul class="mb-0">
                            <li>Manajemen data kamar kost</li>
                            <li>Pencatatan penyewa dan transaksi</li>
                            <li>Informasi ketersediaan kamar</li>
                        </ul>
                    </div>

                    <hr>

                    <div class="alert alert-success text-center">
                        <i class="fas fa-heart"></i>
                        Kami berkomitmen memberikan layanan terbaik bagi seluruh pengguna Vibes Kost.
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
                        <li><a href="../index.php" class="text-light">Home</a></li>
                        <li><a href="../#kost-list" class="text-light">Cari Kost</a></li>
                    </ul>
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
