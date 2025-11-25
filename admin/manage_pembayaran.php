<?php
include '../includes/config.php';
checkRole('admin');

// Handle pembayaran actions
if (isset($_POST['action']) && isset($_POST['pembayaran_id'])) {
    $pembayaran_id = intval($_POST['pembayaran_id']);
    $alasan_penolakan = $conn->real_escape_string($_POST['alasan_penolakan'] ?? '');
    
    // Call stored procedure for verification
    if ($_POST['action'] == 'verify') {
        $stmt = $conn->prepare("CALL VerifyPembayaran(?, ?, 'lunas', NULL)");
        $stmt->bind_param("ii", $pembayaran_id, $_SESSION['user_id']);
        $stmt->execute();
        $_SESSION['success'] = "Pembayaran berhasil diverifikasi";
    } elseif ($_POST['action'] == 'reject') {
        $stmt = $conn->prepare("CALL VerifyPembayaran(?, ?, 'ditolak', ?)");
        $stmt->bind_param("iis", $pembayaran_id, $_SESSION['user_id'], $alasan_penolakan);
        $stmt->execute();
        $_SESSION['success'] = "Pembayaran berhasil ditolak";
    }
    
    header("Location: manage_pembayaran.php");
    exit();
}

// Get all pembayaran with details
$pembayaran_query = "
    SELECT 
        pb.*,
        p.kode_booking,
        p.tanggal_masuk,
        p.durasi_sewa,
        p.total_biaya,
        u.nama as penyewa_nama,
        u.email as penyewa_email,
        k.nama_kost,
        km.nomor_kamar,
        verifier.nama as verifier_nama
    FROM pembayarans pb
    JOIN pemesanans p ON pb.pemesanan_id = p.id
    JOIN users u ON p.penyewa_id = u.id
    JOIN kamars km ON p.kamar_id = km.id
    JOIN kost k ON km.kost_id = k.id
    LEFT JOIN users verifier ON pb.verified_by = verifier.id
    ORDER BY pb.created_at DESC
";
$pembayaran_list = $conn->query($pembayaran_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pembayaran - Admin Vibes Kost</title>
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
        .bukti-transfer {
            max-width: 200px;
            cursor: pointer;
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
                    <a class="nav-link" href="manage_pemesanan.php">
                        <i class="fas fa-bed"></i> Manage Pemesanan
                    </a>
                    <a class="nav-link active" href="manage_pembayaran.php">
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
                        <h2><i class="fas fa-credit-card"></i> Manage Pembayaran</h2>
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

                    <!-- Pembayaran Table -->
                    <div class="card">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-list"></i> Daftar Pembayaran</h5>
                            <span class="badge bg-light text-dark">Total: <?php echo $pembayaran_list->num_rows; ?> pembayaran</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>Kode Pembayaran</th>
                                            <th>Penyewa</th>
                                            <th>Kost & Kamar</th>
                                            <th>Nominal</th>
                                            <th>Bukti Transfer</th>
                                            <th>Status</th>
                                            <th>Tanggal Transfer</th>
                                            <th>Diverifikasi Oleh</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($pembayaran = $pembayaran_list->fetch_assoc()): 
                                            $nominal = number_format($pembayaran['nominal'], 0, ',', '.');
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $pembayaran['kode_pembayaran']; ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    Booking: <?php echo $pembayaran['kode_booking']; ?>
                                                </small>
                                            </td>
                                            <td><?php echo $pembayaran['penyewa_nama']; ?></td>
                                            <td>
                                                <?php echo $pembayaran['nama_kost']; ?>
                                                <br>
                                                <small class="text-muted">Kamar <?php echo $pembayaran['nomor_kamar']; ?></small>
                                            </td>
                                            <td>Rp <?php echo $nominal; ?></td>
                                            <td>
                                                <?php if ($pembayaran['bukti_transfer']): ?>
                                                    <img src="../uploads/bukti_bayar/<?php echo $pembayaran['bukti_transfer']; ?>" 
                                                         class="bukti-transfer img-thumbnail" 
                                                         alt="Bukti Transfer"
                                                         data-bs-toggle="modal" 
                                                         data-bs-target="#imageModal<?php echo $pembayaran['id']; ?>">
                                                    
                                                    <!-- Image Modal -->
                                                    <div class="modal fade" id="imageModal<?php echo $pembayaran['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Bukti Transfer - <?php echo $pembayaran['kode_pembayaran']; ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body text-center">
                                                                    <img src="../uploads/bukti_bayar/<?php echo $pembayaran['bukti_transfer']; ?>" 
                                                                         class="img-fluid" 
                                                                         alt="Bukti Transfer">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    switch($pembayaran['status']) {
                                                        case 'lunas': echo 'success'; break;
                                                        case 'menunggu': echo 'warning'; break;
                                                        case 'ditolak': echo 'danger'; break;
                                                        case 'kadaluarsa': echo 'secondary'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>"><?php echo ucfirst($pembayaran['status']); ?></span>
                                            </td>
                                            <td>
                                                <?php echo $pembayaran['tanggal_transfer'] ? date('d M Y', strtotime($pembayaran['tanggal_transfer'])) : '-'; ?>
                                            </td>
                                            <td>
                                                <?php echo $pembayaran['verifier_nama'] ?: '-'; ?>
                                                <?php if ($pembayaran['verifier_nama']): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo date('d M Y', strtotime($pembayaran['updated_at'])); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($pembayaran['status'] == 'menunggu'): ?>
                                                    <div class="btn-group">
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="pembayaran_id" value="<?php echo $pembayaran['id']; ?>">
                                                            <button type="submit" name="action" value="verify" class="btn btn-success btn-sm">
                                                                <i class="fas fa-check"></i> Verify
                                                            </button>
                                                        </form>
                                                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $pembayaran['id']; ?>">
                                                            <i class="fas fa-times"></i> Reject
                                                        </button>
                                                    </div>

                                                    <!-- Reject Modal -->
                                                    <div class="modal fade" id="rejectModal<?php echo $pembayaran['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Tolak Pembayaran</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form method="POST">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="pembayaran_id" value="<?php echo $pembayaran['id']; ?>">
                                                                        <div class="mb-3">
                                                                            <label for="alasan_penolakan" class="form-label">Alasan Penolakan</label>
                                                                            <textarea class="form-control" id="alasan_penolakan" name="alasan_penolakan" rows="3" required></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                                        <button type="submit" name="action" value="reject" class="btn btn-danger">Tolak Pembayaran</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <?php if ($pembayaran_list->num_rows == 0): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-credit-card fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Belum ada pembayaran</h4>
                        <p class="text-muted">Tidak ada data pembayaran yang ditemukan dalam sistem.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>