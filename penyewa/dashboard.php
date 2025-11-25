<?php
include '../includes/config.php';
checkRole('penyewa');

$user_id = $_SESSION['user_id'];

// Get user data
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get active pemesanan
$pemesanan_query = "SELECT p.*, k.nama_kost, km.nomor_kamar, pb.status as status_pembayaran
                   FROM pemesanans p
                   JOIN kamars km ON p.kamar_id = km.id
                   JOIN kost k ON km.kost_id = k.id
                   LEFT JOIN pembayarans pb ON p.id = pb.pemesanan_id
                   WHERE p.penyewa_id = ? AND p.status IN ('menunggu','dikonfirmasi')
                   ORDER BY p.created_at DESC LIMIT 1";
$stmt = $conn->prepare($pemesanan_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$active_pemesanan = $stmt->get_result()->fetch_assoc();

// Get notifications
$notif_query = "SELECT * FROM notifikasis WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($notif_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Penyewa - Vibes Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: #2c3e50;
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: #bdc3c7;
            padding: 15px 20px;
            border-left: 4px solid transparent;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: #34495e;
            border-left-color: #3498db;
        }
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        .main-content {
            background: #ecf0f1;
            min-height: 100vh;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
        }
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-4">
                    <h4 class="text-center mb-4">Vibes Kost</h4>
                    <div class="text-center mb-4">
                        <img src="../uploads/profil/<?php echo $user['foto_profil']; ?>" 
                             class="rounded-circle" 
                             width="80" 
                             height="80"
                             alt="Profile"
                             onerror="this.src='https://via.placeholder.com/80'">
                        <h6 class="mt-2"><?php echo $user['nama']; ?></h6>
                        <small class="text-muted">Penyewa</small>
                    </div>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a class="nav-link" href="pemesanan.php">
                        <i class="fas fa-bed"></i> Pemesanan
                    </a>
                    <a class="nav-link" href="pembayaran.php">
                        <i class="fas fa-credit-card"></i> Pembayaran
                    </a>
                    <a class="nav-link" href="riwayat.php">
                        <i class="fas fa-history"></i> Riwayat
                    </a>
                    <a class="nav-link" href="notifikasi.php">
                        <i class="fas fa-bell"></i> Notifikasi
                        <?php if ($notifications->num_rows > 0): ?>
                            <span class="badge bg-danger float-end"><?php echo $notifications->num_rows; ?></span>
                        <?php endif; ?>
                    </a>
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a class="nav-link" href="../auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="p-4">
                    <!-- Welcome Section -->
                    <div class="welcome-section">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2>Halo, <?php echo $user['nama']; ?>! 👋</h2>
                                <p class="mb-0">Selamat datang di dashboard penyewa Vibes Kost</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <i class="fas fa-home fa-4x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="text-primary">Pemesanan Aktif</h5>
                                        <h3><?php echo $active_pemesanan ? 1 : 0; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-bed fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card" style="border-left-color: #27ae60;">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="text-success">Menunggu Bayar</h5>
                                        <h3>0</h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clock fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card" style="border-left-color: #e74c3c;">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="text-danger">Notifikasi</h5>
                                        <h3><?php echo $notifications->num_rows; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-bell fa-2x text-danger"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card" style="border-left-color: #f39c12;">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="text-warning">Riwayat</h5>
                                        <h3>0</h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-history fa-2x text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Active Pemesanan -->
                    <?php if ($active_pemesanan): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Pemesanan Aktif</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Kost:</strong> <?php echo $active_pemesanan['nama_kost']; ?></p>
                                            <p><strong>Kamar:</strong> <?php echo $active_pemesanan['nomor_kamar']; ?></p>
                                            <p><strong>Tanggal Masuk:</strong> <?php echo $active_pemesanan['tanggal_masuk']; ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Status Pemesanan:</strong> 
                                                <span class="badge bg-<?php 
                                                    switch($active_pemesanan['status']) {
                                                        case 'menunggu': echo 'warning'; break;
                                                        case 'dikonfirmasi': echo 'success'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>"><?php echo ucfirst($active_pemesanan['status']); ?></span>
                                            </p>
                                            <p><strong>Status Pembayaran:</strong> 
                                                <span class="badge bg-<?php 
                                                    switch($active_pemesanan['status_pembayaran']) {
                                                        case 'lunas': echo 'success'; break;
                                                        case 'menunggu': echo 'warning'; break;
                                                        case 'ditolak': echo 'danger'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>"><?php echo ucfirst($active_pemesanan['status_pembayaran']); ?></span>
                                            </p>
                                            <p><strong>Total Biaya:</strong> Rp <?php echo number_format($active_pemesanan['total_biaya'], 0, ',', '.'); ?></p>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <a href="pembayaran.php" class="btn btn-primary">Lihat Detail Pembayaran</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Quick Actions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-bolt"></i> Aksi Cepat</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-3 mb-3">
                                            <a href="../index.php" class="btn btn-outline-primary btn-lg w-100 py-3">
                                                <i class="fas fa-search fa-2x mb-2"></i><br>
                                                Cari Kost
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="pemesanan.php" class="btn btn-outline-success btn-lg w-100 py-3">
                                                <i class="fas fa-bed fa-2x mb-2"></i><br>
                                                Pemesanan
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="pembayaran.php" class="btn btn-outline-warning btn-lg w-100 py-3">
                                                <i class="fas fa-credit-card fa-2x mb-2"></i><br>
                                                Pembayaran
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="notifikasi.php" class="btn btn-outline-info btn-lg w-100 py-3">
                                                <i class="fas fa-bell fa-2x mb-2"></i><br>
                                                Notifikasi
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>