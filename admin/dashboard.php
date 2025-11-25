<?php
include '../includes/config.php';
checkRole('admin');

$user_id = $_SESSION['user_id'];

// Get statistics
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'penyewa' AND is_active = 1) as total_penyewa,
        (SELECT COUNT(*) FROM users WHERE role = 'pemilik' AND is_active = 1) as total_pemilik,
        (SELECT COUNT(*) FROM kost WHERE status = 'tersedia') as total_kost,
        (SELECT COUNT(*) FROM kamars WHERE status = 'tersedia') as total_kamar_tersedia,
        (SELECT COUNT(*) FROM pemesanans WHERE status = 'menunggu') as pemesanan_menunggu,
        (SELECT COUNT(*) FROM pembayarans WHERE status = 'menunggu') as pembayaran_menunggu,
        (SELECT COUNT(*) FROM notifikasis WHERE is_read = 0) as notifikasi_baru,
        (SELECT SUM(nominal) FROM pembayarans WHERE status = 'lunas' AND DATE(created_at) = CURDATE()) as pendapatan_hari_ini
";
$stats = $conn->query($stats_query)->fetch_assoc();

// Get recent activities
$activities_query = "
    SELECT 
        p.kode_booking,
        u.nama as penyewa_nama,
        k.nama_kost,
        p.status,
        p.created_at
    FROM pemesanans p
    JOIN users u ON p.penyewa_id = u.id
    JOIN kamars km ON p.kamar_id = km.id
    JOIN kost k ON km.kost_id = k.id
    ORDER BY p.created_at DESC
    LIMIT 10
";
$activities = $conn->query($activities_query);

// Get recent users
$recent_users_query = "
    SELECT nama, email, role, created_at 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
";
$recent_users = $conn->query($recent_users_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Vibes Kost</title>
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
            padding: 12px 20px;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: #34495e;
            border-left-color: #e74c3c;
        }
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #e74c3c;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .welcome-section {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(231, 76, 60, 0.1);
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
                        <small class="text-muted">Administrator</small>
                    </div>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link" href="manage_users.php">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                    <a class="nav-link" href="manage_kost.php">
                        <i class="fas fa-building"></i> Manage Kost
                    </a>
                    <a class="nav-link" href="manage_pemesanan.php">
                        <i class="fas fa-bed"></i> Manage Pemesanan
                    </a>
                    <a class="nav-link" href="manage_pembayaran.php">
                        <i class="fas fa-credit-card"></i> Manage Pembayaran
                    </a>
                    <a class="nav-link" href="backup.php">
                        <i class="fas fa-database"></i> Backup Data
                    </a>
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog"></i> Settings
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
                                <p class="mb-0">Selamat datang di dashboard administrator Vibes Kost</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <i class="fas fa-cogs fa-4x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stat-card" style="border-left-color: #3498db;">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="text-primary">Total Penyewa</h5>
                                        <h3><?php echo $stats['total_penyewa']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x text-primary"></i>
                                    </div>
                                </div>
                                <small class="text-muted">Penyewa aktif</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card" style="border-left-color: #27ae60;">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="text-success">Total Pemilik</h5>
                                        <h3><?php echo $stats['total_pemilik']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-user-tie fa-2x text-success"></i>
                                    </div>
                                </div>
                                <small class="text-muted">Pemilik kost</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card" style="border-left-color: #f39c12;">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="text-warning">Total Kost</h5>
                                        <h3><?php echo $stats['total_kost']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-building fa-2x text-warning"></i>
                                    </div>
                                </div>
                                <small class="text-muted">Kost tersedia</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card" style="border-left-color: #9b59b6;">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="text-info">Kamar Tersedia</h5>
                                        <h3><?php echo $stats['total_kamar_tersedia']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-bed fa-2x text-info"></i>
                                    </div>
                                </div>
                                <small class="text-muted">Kamar kosong</small>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stat-card" style="border-left-color: #e74c3c;">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="text-danger">Pemesanan Menunggu</h5>
                                        <h3><?php echo $stats['pemesanan_menunggu']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clock fa-2x text-danger"></i>
                                    </div>
                                </div>
                                <small class="text-muted">Perlu verifikasi</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card" style="border-left-color: #1abc9c;">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="text-teal">Pembayaran Menunggu</h5>
                                        <h3><?php echo $stats['pembayaran_menunggu']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-credit-card fa-2x text-teal"></i>
                                    </div>
                                </div>
                                <small class="text-muted">Perlu konfirmasi</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card" style="border-left-color: #34495e;">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="text-dark">Notifikasi Baru</h5>
                                        <h3><?php echo $stats['notifikasi_baru']; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-bell fa-2x text-dark"></i>
                                    </div>
                                </div>
                                <small class="text-muted">Belum dibaca</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card" style="border-left-color: #e67e22;">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="text-orange">Pendapatan Hari Ini</h5>
                                        <h3>Rp <?php echo number_format($stats['pendapatan_hari_ini'] ?? 0, 0, ',', '.'); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-money-bill-wave fa-2x text-orange"></i>
                                    </div>
                                </div>
                                <small class="text-muted">Total pendapatan</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activities & Users -->
                    <div class="row">
                        <!-- Recent Activities -->
                        <div class="col-md-8 mb-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-history"></i> Aktivitas Terbaru</h5>
                                    <a href="manage_pemesanan.php" class="btn btn-light btn-sm">Lihat Semua</a>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Kode Booking</th>
                                                    <th>Penyewa</th>
                                                    <th>Kost</th>
                                                    <th>Status</th>
                                                    <th>Tanggal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($activity = $activities->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo $activity['kode_booking']; ?></td>
                                                    <td><?php echo $activity['penyewa_nama']; ?></td>
                                                    <td><?php echo $activity['nama_kost']; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            switch($activity['status']) {
                                                                case 'menunggu': echo 'warning'; break;
                                                                case 'dikonfirmasi': echo 'success'; break;
                                                                case 'ditolak': echo 'danger'; break;
                                                                default: echo 'secondary';
                                                            }
                                                        ?>"><?php echo ucfirst($activity['status']); ?></span>
                                                    </td>
                                                    <td><?php echo date('d M Y', strtotime($activity['created_at'])); ?></td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Users -->
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-user-plus"></i> Pengguna Baru</h5>
                                    <a href="manage_users.php" class="btn btn-light btn-sm">Lihat Semua</a>
                                </div>
                                <div class="card-body">
                                    <?php while($user = $recent_users->fetch_assoc()): ?>
                                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                        <div class="flex-shrink-0">
                                            <div class="bg-<?php 
                                                switch($user['role']) {
                                                    case 'admin': echo 'danger'; break;
                                                    case 'pemilik': echo 'success'; break;
                                                    case 'penyewa': echo 'primary'; break;
                                                }
                                            ?> text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                 style="width: 40px; height: 40px;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0"><?php echo $user['nama']; ?></h6>
                                            <small class="text-muted"><?php echo ucfirst($user['role']); ?></small>
                                            <br>
                                            <small class="text-muted"><?php echo date('d M Y', strtotime($user['created_at'])); ?></small>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
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
                                        <div class="col-md-2 mb-3">
                                            <a href="manage_users.php" class="btn btn-outline-primary btn-lg w-100 py-3">
                                                <i class="fas fa-users fa-2x mb-2"></i><br>
                                                Users
                                            </a>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <a href="manage_kost.php" class="btn btn-outline-success btn-lg w-100 py-3">
                                                <i class="fas fa-building fa-2x mb-2"></i><br>
                                                Kost
                                            </a>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <a href="manage_pemesanan.php" class="btn btn-outline-warning btn-lg w-100 py-3">
                                                <i class="fas fa-bed fa-2x mb-2"></i><br>
                                                Pemesanan
                                            </a>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <a href="manage_pembayaran.php" class="btn btn-outline-info btn-lg w-100 py-3">
                                                <i class="fas fa-credit-card fa-2x mb-2"></i><br>
                                                Pembayaran
                                            </a>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <a href="backup.php" class="btn btn-outline-dark btn-lg w-100 py-3">
                                                <i class="fas fa-database fa-2x mb-2"></i><br>
                                                Backup
                                            </a>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <a href="settings.php" class="btn btn-outline-secondary btn-lg w-100 py-3">
                                                <i class="fas fa-cog fa-2x mb-2"></i><br>
                                                Settings
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
    <script>
        // Auto refresh stats every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>