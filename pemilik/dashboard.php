<?php
include '../includes/config.php';
checkRole('pemilik');

$user_id = $_SESSION['user_id'];

// Get dashboard statistics
$stats_query = "SELECT 
    COUNT(DISTINCT k.id) as total_kost,
    COUNT(DISTINCT km.id) as total_kamar,
    COUNT(DISTINCT CASE WHEN km.status = 'tersedia' THEN km.id END) as kamar_tersedia,
    COUNT(DISTINCT p.id) as total_pemesanan,
    COUNT(DISTINCT CASE WHEN p.status = 'menunggu' THEN p.id END) as pemesanan_menunggu,
    COUNT(DISTINCT CASE WHEN pb.status = 'menunggu' THEN pb.id END) as pembayaran_menunggu,
    COALESCE(SUM(CASE WHEN pb.status = 'lunas' THEN pb.nominal ELSE 0 END), 0) as total_pendapatan
FROM users u
LEFT JOIN kost k ON u.id = k.pemilik_id
LEFT JOIN kamars km ON k.id = km.kost_id
LEFT JOIN pemesanans p ON km.id = p.kamar_id
LEFT JOIN pembayarans pb ON p.id = pb.pemesanan_id
WHERE u.id = ?";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get recent pemesanan
$recent_query = "SELECT p.*, u.nama as penyewa_nama, k.nama_kost, km.nomor_kamar, pb.status as status_pembayaran
                FROM pemesanans p
                JOIN users u ON p.penyewa_id = u.id
                JOIN kamars km ON p.kamar_id = km.id
                JOIN kost k ON km.kost_id = k.id
                LEFT JOIN pembayarans pb ON p.id = pb.pemesanan_id
                WHERE k.pemilik_id = ?
                ORDER BY p.created_at DESC 
                LIMIT 5";
$stmt = $conn->prepare($recent_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_pemesanan = $stmt->get_result();

// Get pending payments
$pending_query = "SELECT pb.*, p.kode_booking, u.nama as penyewa_nama, k.nama_kost, km.nomor_kamar
                 FROM pembayarans pb
                 JOIN pemesanans p ON pb.pemesanan_id = p.id
                 JOIN users u ON p.penyewa_id = u.id
                 JOIN kamars km ON p.kamar_id = km.id
                 JOIN kost k ON km.kost_id = k.id
                 WHERE k.pemilik_id = ? AND pb.status = 'menunggu'
                 ORDER BY pb.created_at DESC 
                 LIMIT 5";
$stmt = $conn->prepare($pending_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_payments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pemilik - Vibes Kost</title>
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
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
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
                        <img src="../uploads/profil/<?php echo $_SESSION['foto_profil']; ?>" 
                             class="rounded-circle" 
                             width="80" 
                             height="80"
                             alt="Profile"
                             onerror="this.src='https://via.placeholder.com/80'">
                        <h6 class="mt-2"><?php echo $_SESSION['nama']; ?></h6>
                        <small class="text-muted">Pemilik Kost</small>
                    </div>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link" href="manage_kost.php">
                        <i class="fas fa-home"></i> Kelola Kost
                    </a>
                    <a class="nav-link" href="manage_kamar.php">
                        <i class="fas fa-bed"></i> Kelola Kamar
                    </a>
                    <a class="nav-link" href="konfirmasi_pembayaran.php">
                        <i class="fas fa-credit-card"></i> Konfirmasi Pembayaran
                        <?php if ($stats['pembayaran_menunggu'] > 0): ?>
                            <span class="badge bg-danger float-end"><?php echo $stats['pembayaran_menunggu']; ?></span>
                        <?php endif; ?>
                    </a>
                    <a class="nav-link" href="laporan.php">
                        <i class="fas fa-chart-bar"></i> Laporan
                    </a>
                    <a class="nav-link" href="notifikasi.php">
                        <i class="fas fa-bell"></i> Notifikasi
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
                                <h2>Halo, <?php echo $_SESSION['nama']; ?>! 👋</h2>
                                <p class="mb-0">Selamat datang di dashboard pemilik kost. Kelola properti Anda dengan mudah.</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <i class="fas fa-building fa-4x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="text-primary">Total Kost</h5>
                                        <h3><?php echo $stats['total_kost']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-home fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card" style="border-left-color: #27ae60;">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="text-success">Kamar Tersedia</h5>
                                        <h3><?php echo $stats['kamar_tersedia']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-bed fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card" style="border-left-color: #e74c3c;">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="text-danger">Pembayaran Menunggu</h5>
                                        <h3><?php echo $stats['pembayaran_menunggu']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clock fa-2x text-danger"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card" style="border-left-color: #f39c12;">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="text-warning">Total Pendapatan</h5>
                                        <h3>Rp <?php echo number_format($stats['total_pendapatan'], 0, ',', '.'); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-money-bill-wave fa-2x text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Pending Payments -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-clock"></i> Pembayaran Menunggu</h5>
                                    <span class="badge bg-light text-danger"><?php echo $stats['pembayaran_menunggu']; ?> menunggu</span>
                                </div>
                                <div class="card-body">
                                    <?php if ($pending_payments->num_rows > 0): ?>
                                        <div class="list-group list-group-flush">
                                            <?php while ($payment = $pending_payments->fetch_assoc()): ?>
                                                <div class="list-group-item">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1"><?php echo $payment['penyewa_nama']; ?></h6>
                                                        <small>Rp <?php echo number_format($payment['nominal'], 0, ',', '.'); ?></small>
                                                    </div>
                                                    <p class="mb-1"><?php echo $payment['nama_kost']; ?> - Kamar <?php echo $payment['nomor_kamar']; ?></p>
                                                    <small class="text-muted">Kode: <?php echo $payment['kode_pembayaran']; ?></small>
                                                    <div class="mt-2">
                                                        <a href="konfirmasi_pembayaran.php?payment_id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-primary">Verifikasi</a>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted text-center">Tidak ada pembayaran menunggu</p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <a href="konfirmasi_pembayaran.php" class="btn btn-outline-danger btn-sm">Lihat Semua</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Pemesanan -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-history"></i> Pemesanan Terbaru</h5>
                                    <span class="badge bg-light text-primary"><?php echo $recent_pemesanan->num_rows; ?> pesanan</span>
                                </div>
                                <div class="card-body">
                                    <?php if ($recent_pemesanan->num_rows > 0): ?>
                                        <div class="list-group list-group-flush">
                                            <?php while ($pemesanan = $recent_pemesanan->fetch_assoc()): ?>
                                                <div class="list-group-item">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1"><?php echo $pemesanan['penyewa_nama']; ?></h6>
                                                        <span class="badge bg-<?php 
                                                            switch($pemesanan['status']) {
                                                                case 'menunggu': echo 'warning'; break;
                                                                case 'dikonfirmasi': echo 'success'; break;
                                                                case 'ditolak': echo 'danger'; break;
                                                                default: echo 'secondary';
                                                            }
                                                        ?>"><?php echo ucfirst($pemesanan['status']); ?></span>
                                                    </div>
                                                    <p class="mb-1"><?php echo $pemesanan['nama_kost']; ?> - Kamar <?php echo $pemesanan['nomor_kamar']; ?></p>
                                                    <small class="text-muted">Kode: <?php echo $pemesanan['kode_booking']; ?></small>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted text-center">Belum ada pemesanan</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
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
                                            <a href="manage_kost.php" class="btn btn-outline-primary btn-lg w-100 py-3">
                                                <i class="fas fa-plus-circle fa-2x mb-2"></i><br>
                                                Tambah Kost
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="manage_kamar.php" class="btn btn-outline-success btn-lg w-100 py-3">
                                                <i class="fas fa-bed fa-2x mb-2"></i><br>
                                                Kelola Kamar
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="konfirmasi_pembayaran.php" class="btn btn-outline-warning btn-lg w-100 py-3">
                                                <i class="fas fa-credit-card fa-2x mb-2"></i><br>
                                                Verifikasi Bayar
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="laporan.php" class="btn btn-outline-info btn-lg w-100 py-3">
                                                <i class="fas fa-chart-bar fa-2x mb-2"></i><br>
                                                Lihat Laporan
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