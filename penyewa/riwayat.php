<?php
include '../includes/config.php';
checkRole('penyewa');

$user_id = $_SESSION['user_id'];

// Get riwayat pemesanan
$query = "SELECT p.*, k.nama_kost, km.nomor_kamar, km.harga_per_bulan, 
                 pb.status as status_pembayaran, pb.nominal as nominal_bayar,
                 r.rating, r.komentar as review_komentar
          FROM pemesanans p
          JOIN kamars km ON p.kamar_id = km.id
          JOIN kost k ON km.kost_id = k.id
          LEFT JOIN pembayarans pb ON p.id = pb.pemesanan_id
          LEFT JOIN reviews r ON p.id = r.pemesanan_id
          WHERE p.penyewa_id = ?
          ORDER BY p.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$riwayat = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat - Vibes Kost</title>
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
        .riwayat-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .riwayat-card:hover {
            transform: translateY(-2px);
        }
        .status-badge {
            font-size: 0.8em;
            padding: 5px 10px;
        }
        .rating-stars {
            color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="p-4">
                    <!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-history"></i> Riwayat Pemesanan</h2>
                        <div class="btn-group">
                            <button class="btn btn-outline-primary" onclick="filterRiwayat('all')">Semua</button>
                            <button class="btn btn-outline-success" onclick="filterRiwayat('selesai')">Selesai</button>
                            <button class="btn btn-outline-warning" onclick="filterRiwayat('menunggu')">Menunggu</button>
                            <button class="btn btn-outline-danger" onclick="filterRiwayat('ditolak')">Ditolak</button>
                        </div>
                    </div>

                    <!-- Riwayat List -->
                    <div class="row">
                        <div class="col-12">
                            <?php if ($riwayat->num_rows > 0): ?>
                                <?php while ($item = $riwayat->fetch_assoc()): ?>
                                    <div class="riwayat-card" data-status="<?php echo $item['status']; ?>">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <div>
                                                            <h5><?php echo $item['nama_kost']; ?></h5>
                                                            <p class="text-muted mb-1">
                                                                <i class="fas fa-door-open"></i> Kamar <?php echo $item['nomor_kamar']; ?> 
                                                                | Rp <?php echo number_format($item['harga_per_bulan'], 0, ',', '.'); ?>/bln
                                                            </p>
                                                            <p class="text-muted mb-0">
                                                                <i class="fas fa-calendar"></i> 
                                                                <?php echo date('d M Y', strtotime($item['tanggal_masuk'])); ?> 
                                                                - <?php echo $item['durasi_sewa']; ?> Bulan
                                                            </p>
                                                        </div>
                                                        <div class="text-end">
                                                            <span class="badge status-badge bg-<?php 
                                                                switch($item['status']) {
                                                                    case 'menunggu': echo 'warning'; break;
                                                                    case 'dikonfirmasi': echo 'success'; break;
                                                                    case 'ditolak': echo 'danger'; break;
                                                                    case 'selesai': echo 'info'; break;
                                                                    case 'dibatalkan': echo 'secondary'; break;
                                                                    default: echo 'secondary';
                                                                }
                                                            ?>">
                                                                <?php echo strtoupper($item['status']); ?>
                                                            </span>
                                                            <br>
                                                            <span class="badge status-badge bg-<?php 
                                                                switch($item['status_pembayaran']) {
                                                                    case 'lunas': echo 'success'; break;
                                                                    case 'menunggu': echo 'warning'; break;
                                                                    case 'ditolak': echo 'danger'; break;
                                                                    default: echo 'secondary';
                                                                }
                                                            ?> mt-1">
                                                                Pembayaran: <?php echo strtoupper($item['status_pembayaran']); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <strong>Kode Booking:</strong><br>
                                                            <?php echo $item['kode_booking']; ?>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <strong>Total Biaya:</strong><br>
                                                            Rp <?php echo number_format($item['total_biaya'], 0, ',', '.'); ?>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <strong>Tanggal Pemesanan:</strong><br>
                                                            <?php echo date('d M Y H:i', strtotime($item['created_at'])); ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if ($item['rating']): ?>
                                                        <div class="mt-3 p-3 bg-light rounded">
                                                            <h6><i class="fas fa-star rating-stars"></i> Review Anda:</h6>
                                                            <div class="rating-stars mb-2">
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <i class="fas fa-star<?php echo $i > $item['rating'] ? '-half-alt' : ''; ?>"></i>
                                                                <?php endfor; ?>
                                                                <span class="ms-2">(<?php echo $item['rating']; ?>/5)</span>
                                                            </div>
                                                            <p class="mb-0">"<?php echo $item['review_komentar']; ?>"</p>
                                                        </div>
                                                    <?php elseif ($item['status'] == 'selesai'): ?>
                                                        <div class="mt-3">
                                                            <button class="btn btn-sm btn-outline-warning" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#reviewModal"
                                                                    data-pemesanan-id="<?php echo $item['id']; ?>"
                                                                    data-kost-nama="<?php echo $item['nama_kost']; ?>">
                                                                <i class="fas fa-star"></i> Beri Review
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-4 border-start">
                                                    <h6>Detail Pembayaran:</h6>
                                                    <p class="mb-1">
                                                        <strong>Nominal:</strong> 
                                                        Rp <?php echo number_format($item['nominal_bayar'], 0, ',', '.'); ?>
                                                    </p>
                                                    <p class="mb-1">
                                                        <strong>Status:</strong> 
                                                        <span class="badge bg-<?php 
                                                            switch($item['status_pembayaran']) {
                                                                case 'lunas': echo 'success'; break;
                                                                case 'menunggu': echo 'warning'; break;
                                                                case 'ditolak': echo 'danger'; break;
                                                                default: echo 'secondary';
                                                            }
                                                        ?>">
                                                            <?php echo strtoupper($item['status_pembayaran']); ?>
                                                        </span>
                                                    </p>
                                                    <?php if ($item['alasan_penolakan']): ?>
                                                        <p class="mb-0">
                                                            <strong>Alasan Penolakan:</strong><br>
                                                            <small class="text-danger"><?php echo $item['alasan_penolakan']; ?></small>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-history fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">Belum Ada Riwayat</h4>
                                    <p class="text-muted">Anda belum memiliki riwayat pemesanan.</p>
                                    <a href="../index.php" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Cari Kost Sekarang
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="row mt-4">
                        <div class="col-md-2 col-6 mb-3">
                            <div class="card text-white bg-primary">
                                <div class="card-body text-center p-3">
                                    <?php
                                    $total_query = "SELECT COUNT(*) as total FROM pemesanans WHERE penyewa_id = ?";
                                    $stmt = $conn->prepare($total_query);
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    $total = $stmt->get_result()->fetch_assoc()['total'];
                                    ?>
                                    <h4><?php echo $total; ?></h4>
                                    <small>Total</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <div class="card text-white bg-success">
                                <div class="card-body text-center p-3">
                                    <?php
                                    $query = "SELECT COUNT(*) as total FROM pemesanans WHERE penyewa_id = ? AND status = 'selesai'";
                                    $stmt = $conn->prepare($query);
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    $selesai = $stmt->get_result()->fetch_assoc()['total'];
                                    ?>
                                    <h4><?php echo $selesai; ?></h4>
                                    <small>Selesai</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <div class="card text-white bg-warning">
                                <div class="card-body text-center p-3">
                                    <?php
                                    $query = "SELECT COUNT(*) as total FROM pemesanans WHERE penyewa_id = ? AND status = 'menunggu'";
                                    $stmt = $conn->prepare($query);
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    $menunggu = $stmt->get_result()->fetch_assoc()['total'];
                                    ?>
                                    <h4><?php echo $menunggu; ?></h4>
                                    <small>Menunggu</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <div class="card text-white bg-info">
                                <div class="card-body text-center p-3">
                                    <?php
                                    $query = "SELECT COUNT(*) as total FROM pemesanans WHERE penyewa_id = ? AND status = 'dikonfirmasi'";
                                    $stmt = $conn->prepare($query);
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    $dikonfirmasi = $stmt->get_result()->fetch_assoc()['total'];
                                    ?>
                                    <h4><?php echo $dikonfirmasi; ?></h4>
                                    <small>Dikonfirmasi</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <div class="card text-white bg-danger">
                                <div class="card-body text-center p-3">
                                    <?php
                                    $query = "SELECT COUNT(*) as total FROM pemesanans WHERE penyewa_id = ? AND status IN ('ditolak', 'dibatalkan')";
                                    $stmt = $conn->prepare($query);
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    $ditolak = $stmt->get_result()->fetch_assoc()['total'];
                                    ?>
                                    <h4><?php echo $ditolak; ?></h4>
                                    <small>Ditolak</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <div class="card text-white bg-secondary">
                                <div class="card-body text-center p-3">
                                    <?php
                                    $query = "SELECT COUNT(*) as total FROM reviews r 
                                             JOIN pemesanans p ON r.pemesanan_id = p.id 
                                             WHERE p.penyewa_id = ?";
                                    $stmt = $conn->prepare($query);
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    $reviews = $stmt->get_result()->fetch_assoc()['total'];
                                    ?>
                                    <h4><?php echo $reviews; ?></h4>
                                    <small>Reviews</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="reviewModalLabel">
                        <i class="fas fa-star"></i> Beri Review
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="submit_review.php">
                    <div class="modal-body">
                        <input type="hidden" name="pemesanan_id" id="review_pemesanan_id">
                        <div class="mb-3">
                            <label class="form-label">Kost</label>
                            <input type="text" class="form-control" id="review_kost_nama" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rating</label>
                            <div class="rating-input">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star rating-star" data-rating="<?php echo $i; ?>" 
                                       style="cursor: pointer; font-size: 2em; color: #ddd; margin-right: 5px;"></i>
                                <?php endfor; ?>
                                <input type="hidden" name="rating" id="rating_value" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="komentar" class="form-label">Komentar</label>
                            <textarea class="form-control" id="komentar" name="komentar" rows="4" 
                                      placeholder="Bagaimana pengalaman Anda tinggal di kost ini?" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Submit Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter riwayat
        function filterRiwayat(status) {
            const cards = document.querySelectorAll('.riwayat-card');
            cards.forEach(card => {
                if (status === 'all' || card.getAttribute('data-status') === status) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Review modal setup
        const reviewModal = document.getElementById('reviewModal');
        reviewModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const pemesananId = button.getAttribute('data-pemesanan-id');
            const kostNama = button.getAttribute('data-kost-nama');
            
            document.getElementById('review_pemesanan_id').value = pemesananId;
            document.getElementById('review_kost_nama').value = kostNama;
        });
        
        // Star rating
        const stars = document.querySelectorAll('.rating-star');
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                document.getElementById('rating_value').value = rating;
                
                stars.forEach(s => {
                    if (s.getAttribute('data-rating') <= rating) {
                        s.style.color = '#ffc107';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });
    </script>
</body>
</html>