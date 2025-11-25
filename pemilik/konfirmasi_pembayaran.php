<?php
include '../includes/config.php';
checkRole('pemilik');

$user_id = $_SESSION['user_id'];
$message = '';

// Handle payment verification
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['verify_payment'])) {
        $pembayaran_id = $_POST['pembayaran_id'];
        $status = $_POST['status'];
        $alasan_penolakan = $conn->real_escape_string($_POST['alasan_penolakan'] ?? '');
        
        // Use stored procedure for verification
        $query = "CALL VerifyPembayaran(?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isss", $pembayaran_id, $user_id, $status, $alasan_penolakan);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Pembayaran berhasil diverifikasi!</div>';
        } else {
            $message = '<div class="alert alert-danger">Gagal memverifikasi pembayaran: ' . $stmt->error . '</div>';
        }
    }
}

// Get pending payments
$pending_query = "SELECT pb.*, p.kode_booking, p.tanggal_masuk, p.durasi_sewa, p.total_biaya,
                         u.nama as penyewa_nama, u.no_telepon as penyewa_telepon,
                         k.nama_kost, km.nomor_kamar
                  FROM pembayarans pb
                  JOIN pemesanans p ON pb.pemesanan_id = p.id
                  JOIN users u ON p.penyewa_id = u.id
                  JOIN kamars km ON p.kamar_id = km.id
                  JOIN kost k ON km.kost_id = k.id
                  WHERE k.pemilik_id = ? AND pb.status = 'menunggu'
                  ORDER BY pb.created_at DESC";
$stmt = $conn->prepare($pending_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_payments = $stmt->get_result();

// Get payment history
$history_query = "SELECT pb.*, p.kode_booking, u.nama as penyewa_nama,
                         k.nama_kost, km.nomor_kamar
                  FROM pembayarans pb
                  JOIN pemesanans p ON pb.pemesanan_id = p.id
                  JOIN users u ON p.penyewa_id = u.id
                  JOIN kamars km ON p.kamar_id = km.id
                  JOIN kost k ON km.kost_id = k.id
                  WHERE k.pemilik_id = ? AND pb.status != 'menunggu'
                  ORDER BY pb.updated_at DESC 
                  LIMIT 20";
$stmt = $conn->prepare($history_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payment_history = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pembayaran - Vibes Kost</title>
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
        .payment-card {
            border-left: 4px solid #f39c12;
        }
        .verified-card {
            border-left: 4px solid #27ae60;
        }
        .rejected-card {
            border-left: 4px solid #e74c3c;
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
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link" href="manage_kost.php">
                        <i class="fas fa-home"></i> Kelola Kost
                    </a>
                    <a class="nav-link" href="manage_kamar.php">
                        <i class="fas fa-bed"></i> Kelola Kamar
                    </a>
                    <a class="nav-link active" href="konfirmasi_pembayaran.php">
                        <i class="fas fa-credit-card"></i> Konfirmasi Pembayaran
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
                    <h2 class="mb-4"><i class="fas fa-credit-card"></i> Konfirmasi Pembayaran</h2>
                    
                    <?php echo $message; ?>
                    
                    <!-- Pending Payments -->
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-clock"></i> Pembayaran Menunggu Verifikasi</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($pending_payments->num_rows > 0): ?>
                                <div class="row">
                                    <?php while ($payment = $pending_payments->fetch_assoc()): ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="card payment-card h-100">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <strong><?php echo $payment['penyewa_nama']; ?></strong>
                                                    <span class="badge bg-warning">Menunggu</span>
                                                </div>
                                                <div class="card-body">
                                                    <p><strong>Kost:</strong> <?php echo $payment['nama_kost']; ?></p>
                                                    <p><strong>Kamar:</strong> <?php echo $payment['nomor_kamar']; ?></p>
                                                    <p><strong>Kode Booking:</strong> <?php echo $payment['kode_booking']; ?></p>
                                                    <p><strong>Nominal:</strong> Rp <?php echo number_format($payment['nominal'], 0, ',', '.'); ?></p>
                                                    <p><strong>Metode:</strong> <?php echo ucfirst(str_replace('_', ' ', $payment['metode_pembayaran'])); ?></p>
                                                    
                                                    <?php if ($payment['bukti_transfer']): ?>
                                                        <p><strong>Bukti Transfer:</strong></p>
                                                        <img src="../uploads/bukti_bayar/<?php echo $payment['bukti_transfer']; ?>" 
                                                             class="img-thumbnail" 
                                                             style="max-height: 200px; cursor: pointer;" 
                                                             onclick="openImageModal(this.src)"
                                                             onerror="this.style.display='none'">
                                                    <?php endif; ?>
                                                    
                                                    <div class="mt-3">
                                                        <button class="btn btn-success btn-sm" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#verifyModal"
                                                                data-payment-id="<?php echo $payment['id']; ?>"
                                                                data-payment-nominal="Rp <?php echo number_format($payment['nominal'], 0, ',', '.'); ?>"
                                                                data-penyewa-nama="<?php echo $payment['penyewa_nama']; ?>"
                                                                onclick="setVerifyData(this)">
                                                            <i class="fas fa-check"></i> Terima
                                                        </button>
                                                        <button class="btn btn-danger btn-sm" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#rejectModal"
                                                                data-payment-id="<?php echo $payment['id']; ?>"
                                                                data-payment-nominal="Rp <?php echo number_format($payment['nominal'], 0, ',', '.'); ?>"
                                                                data-penyewa-nama="<?php echo $payment['penyewa_nama']; ?>"
                                                                onclick="setRejectData(this)">
                                                            <i class="fas fa-times"></i> Tolak
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center">Tidak ada pembayaran yang menunggu verifikasi</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Payment History -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-history"></i> Riwayat Verifikasi</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($payment_history->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Kode Bayar</th>
                                                <th>Penyewa</th>
                                                <th>Kost & Kamar</th>
                                                <th>Nominal</th>
                                                <th>Status</th>
                                                <th>Tanggal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($payment = $payment_history->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo $payment['kode_pembayaran']; ?></td>
                                                    <td><?php echo $payment['penyewa_nama']; ?></td>
                                                    <td><?php echo $payment['nama_kost']; ?> - Kamar <?php echo $payment['nomor_kamar']; ?></td>
                                                    <td>Rp <?php echo number_format($payment['nominal'], 0, ',', '.'); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            switch($payment['status']) {
                                                                case 'lunas': echo 'success'; break;
                                                                case 'ditolak': echo 'danger'; break;
                                                                case 'kadaluarsa': echo 'secondary'; break;
                                                                default: echo 'warning';
                                                            }
                                                        ?>"><?php echo ucfirst($payment['status']); ?></span>
                                                    </td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($payment['updated_at'])); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center">Belum ada riwayat verifikasi</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Verify Modal -->
    <div class="modal fade" id="verifyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Penerimaan Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="pembayaran_id" id="verifyPaymentId">
                    <input type="hidden" name="status" value="lunas">
                    
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menerima pembayaran dari <strong id="verifyPenyewaNama"></strong> sebesar <strong id="verifyPaymentNominal"></strong>?</p>
                        <p class="text-success"><i class="fas fa-check-circle"></i> Pembayaran akan diterima dan status kamar akan diupdate.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="verify_payment" class="btn btn-success">Ya, Terima Pembayaran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Penolakan Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="pembayaran_id" id="rejectPaymentId">
                    <input type="hidden" name="status" value="ditolak">
                    
                    <div class="modal-body">
                        <p>Anda akan menolak pembayaran dari <strong id="rejectPenyewaNama"></strong> sebesar <strong id="rejectPaymentNominal"></strong>.</p>
                        
                        <div class="mb-3">
                            <label class="form-label">Alasan Penolakan</label>
                            <textarea name="alasan_penolakan" class="form-control" rows="3" placeholder="Berikan alasan penolakan..." required></textarea>
                        </div>
                        
                        <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> Pembayaran akan ditolak dan status kamar akan dikembalikan ke "Tersedia".</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="verify_payment" class="btn btn-danger">Ya, Tolak Pembayaran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bukti Transfer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid" alt="Bukti Transfer">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setVerifyData(button) {
            document.getElementById('verifyPaymentId').value = button.getAttribute('data-payment-id');
            document.getElementById('verifyPaymentNominal').textContent = button.getAttribute('data-payment-nominal');
            document.getElementById('verifyPenyewaNama').textContent = button.getAttribute('data-penyewa-nama');
        }
        
        function setRejectData(button) {
            document.getElementById('rejectPaymentId').value = button.getAttribute('data-payment-id');
            document.getElementById('rejectPaymentNominal').textContent = button.getAttribute('data-payment-nominal');
            document.getElementById('rejectPenyewaNama').textContent = button.getAttribute('data-penyewa-nama');
        }
        
        function openImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            var imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
            imageModal.show();
        }
    </script>
</body>
</html>