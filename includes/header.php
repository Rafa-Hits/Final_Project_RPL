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
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#kost-list">Cari Kost</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/about.php">Tentang Kami</a>
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
                        <a class="nav-link" href="auth/login.php">Login</a>
                        <a class="nav-link" href="auth/register.php">Daftar Akun </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>