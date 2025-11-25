<?php
include '../includes/config.php';
checkRole('admin');

// Handle pemesanan actions
if (isset($_POST['action']) && isset($_POST['pemesanan_id'])) {
    $pemesanan_id = intval($_POST['pemesanan_id']);
    $alasan_penolakan = $conn->real_escape_string($_POST['alasan_penolakan'] ?? '');
    
    switch ($_POST['action']) {
        case 'confirm':
            $conn->query("UPDATE pemesanans SET status = 'dikonfirmasi' WHERE id = $pemesanan_id");
            $_SESSION['success'] = "Pemesanan berhasil dikonfirmasi";
            break;
        case 'reject':
            $conn->query("UPDATE pemesanans SET status = 'ditolak', alasan_penolakan = '$alasan_penolakan' WHERE id = $pemesanan_id");
            // Reset kamar status
            $conn->query("UPDATE kamars km 
                         JOIN pemesanans p ON km.id = p.kamar_id 
                         SET km.status = 'tersedia' 
                         WHERE p.id = $pemesanan_id");
            $_SESSION['success'] = "Pemesanan berhasil ditolak";
            break;
        case 'complete':
            $conn->query("UPDATE pemesanans SET status = 'selesai' WHERE id = $pemesanan_id");
            // Reset kamar status
            $conn->query("UPDATE kamars km 
                         JOIN pemesanans p ON km.id = p.kamar_id 
                         SET km.status = 'tersedia' 
                         WHERE p.id = $pemesanan_id");
            $_SESSION['success'] = "Pemesanan berhasil diselesaikan";
            break;
    }
    header("Location: manage_pemesanan.php");
    exit();
}

// Get all pemesanan with details
$pemesanan_query = "
    SELECT 
        p.*,
        u.nama as penyewa_nama,
        u.email as penyewa_email,
        u.no_telepon as penyewa_telepon,
        k.nama_kost,
        km.nomor_kamar,
        km.harga_per_bulan,
        pb.status as status_pembayaran,
        pb.nominal as nominal_bayar
    FROM pemesanans p
    JOIN users u ON p.penyewa_id = u.id
    JOIN kamars km ON p.kamar_id = km.id
    JOIN kost k ON km.kost_id = k.id
    LEFT JOIN pembayarans pb ON p.id = pb.pemesanan_id
    ORDER BY p.created_at DESC
";
$pemesanan_list = $conn->query($pemesanan_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pemesanan - Admin Vibes Kost</title>
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
        .status-badge {
            font-size: 0.8em;
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
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link" href="manage_users.php">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                    <a class="nav-link" href="manage_kost.php">
                        <i class="fas fa-building"></i> Manage Kost
                    </a>
                    <a class="nav-link active" href="manage_pemesanan.php">
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
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-bed"></i> Manage Pemesanan</h2>
                        <a href="dashboard.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                        </a>
                    </div>

                    <!-- Messages -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Pemesanan Table -->
                    <div class="card">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-list"></i> Daftar Pemesanan</h5>
                            <span class="badge bg-light text-dark">Total: <?php echo $pemesanan_list->num_rows; ?> pemesanan</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>Kode Booking</th>
                                            <th>Penyewa</th>
                                            <th>Kost & Kamar</th>
                                            <th>Tanggal Masuk</th>
                                            <th>Durasi</th>
                                            <th>Total Biaya</th>
                                            <th>Status Pemesanan</th>
                                            <th>Status Pembayaran</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($pemesanan = $pemesanan_list->fetch_assoc()): 
                                            $total_biaya = number_format($pemesanan['total_biaya'], 0, ',', '.');
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $pemesanan['kode_booking']; ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo date('d M Y', strtotime($pemesanan['created_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php echo $pemesanan['penyewa_nama']; ?>
                                                <br>
                                                <small class="text-muted"><?php echo $pemesanan['penyewa_telepon']; ?></small>
                                            </td>
                                            <td>
                                                <?php echo $pemesanan['nama_kost']; ?>
                                                <br>
                                                <small class="text-muted">Kamar <?php echo $pemesanan['nomor_kamar']; ?></small>
                                            </td>
                                            <td><?php echo date('d M Y', strtotime($pemesanan['tanggal_masuk'])); ?></td>
                                            <td><?php echo $pemesanan['durasi_sewa']; ?> bulan</td>
                                            <td>Rp <?php echo $total_biaya; ?></td>
                                            <td>
                                                <span class="badge status-badge bg-<?php 
                                                    switch($pemesanan['status']) {
                                                        case 'menunggu': echo 'warning'; break;
                                                        case 'dikonfirmasi': echo 'success'; break;
                                                        case 'ditolak': echo 'danger'; break;
                                                        case 'selesai': echo 'info'; break;
                                                        case 'dibatalkan': echo 'secondary'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>"><?php echo ucfirst($pemesanan['status']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge status-badge bg-<?php 
                                                    switch($pemesanan['status_pembayaran']) {
                                                        case 'lunas': echo 'success'; break;
                                                        case 'menunggu': echo 'warning'; break;
                                                        case 'ditolak': echo 'danger'; break;
                                                        case 'kadaluarsa': echo 'secondary'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>"><?php echo $pemesanan['status_pembayaran'] ? ucfirst($pemesanan['status_pembayaran']) : '-'; ?></span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <?php if ($pemesanan['status'] == 'menunggu'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="pemesanan_id" value="<?php echo $pemesanan['id']; ?>">
                                                            <button type="submit" name="action" value="confirm" class="btn btn-success btn-sm">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $pemesanan['id']; ?>">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php elseif ($pemesanan['status'] == 'dikonfirmasi'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="pemesanan_id" value="<?php echo $pemesanan['id']; ?>">
                                                            <button type="submit" name="action" value="complete" class="btn btn-info btn-sm">
                                                                <i class="fas fa-flag-checkered"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Reject Modal -->
                                                <div class="modal fade" id="rejectModal<?php echo $pemesanan['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Tolak Pemesanan</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="POST">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="pemesanan_id" value="<?php echo $pemesanan['id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label for="alasan_penolakan" class="form-label">Alasan Penolakan</label>
                                                                        <textarea class="form-control" id="alasan_penolakan" name="alasan_penolakan" rows="3" required></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                                    <button type="submit" name="action" value="reject" class="btn btn-danger">Tolak Pemesanan</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <?php if ($pemesanan_list->num_rows == 0): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bed fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Belum ada pemesanan</h4>
                        <p class="text-muted">Tidak ada data pemesanan yang ditemukan dalam sistem.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>