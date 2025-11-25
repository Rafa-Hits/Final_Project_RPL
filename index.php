<?php
include 'includes/config.php';
include 'includes/header.php';

// Handle search
$search_query = "";
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search_query = $conn->real_escape_string($_GET['q']);
}
?>

<!-- Hero Section -->
<section class="hero" style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/images/hero-bg.jpg'); background-size: cover; background-position: center; height: 60vh; display: flex; align-items: center;">
    <div class="container">
        <div class="row">
            <div class="col-md-8 mx-auto text-center text-white">
                <h1 class="display-4 mb-4">Temukan Kost Nyaman di Mendalo - Sungai Duren</h1>
                <p class="lead mb-4">Cari dan pesan kost impian Anda dengan mudah dan cepat</p>
                
                <!-- Search Form -->
                <form action="index.php" method="GET" class="search-form">
                    <div class="input-group input-group-lg">
                        <input type="text" name="q" class="form-control" 
                               placeholder="Cari kost berdasarkan lokasi, harga, atau fasilitas..." 
                               value="<?php echo htmlspecialchars($search_query); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i> Cari
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Featured Kost Section -->
<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="text-center mb-3">
                    <?php echo $search_query ? 'Hasil Pencarian: "' . htmlspecialchars($search_query) . '"' : 'Kost Populer'; ?>
                </h2>
                <p class="text-center text-muted">
                    <?php echo $search_query ? 'Berikut hasil pencarian kost' : 'Temukan kost terbaik di sekitar Mendalo - Sungai Duren'; ?>
                </p>
            </div>
        </div>

        <div class="row" id="kost-list">
            <?php
            // Build query based on search
            $query = "SELECT k.*, u.nama as pemilik_nama, 
                             COUNT(km.id) as total_kamar,
                             COUNT(CASE WHEN km.status = 'tersedia' THEN km.id END) as kamar_tersedia
                      FROM kost k 
                      JOIN users u ON k.pemilik_id = u.id 
                      LEFT JOIN kamars km ON k.id = km.kost_id 
                      WHERE k.status = 'tersedia'";
            
            if ($search_query) {
                $query .= " AND (k.nama_kost LIKE '%$search_query%' 
                          OR k.alamat LIKE '%$search_query%' 
                          OR k.fasilitas LIKE '%$search_query%'
                          OR k.deskripsi LIKE '%$search_query%')";
            }
            
            $query .= " GROUP BY k.id 
                       ORDER BY k.created_at DESC 
                       LIMIT 12";
            
            $result = $conn->query($query);

            if ($result->num_rows > 0) {
                while ($kost = $result->fetch_assoc()) {
                    $harga = number_format($kost['harga_per_bulan'], 0, ',', '.');
                    $deskripsi_singkat = strlen($kost['deskripsi']) > 100 ? 
                        substr($kost['deskripsi'], 0, 100) . '...' : $kost['deskripsi'];
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="card kost-card h-100">
                            <img src="uploads/kost/<?php echo $kost['foto_kost']; ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo $kost['nama_kost']; ?>"
                                 style="height: 200px; object-fit: cover;"
                                 onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $kost['nama_kost']; ?></h5>
                                <p class="card-text text-muted">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?php echo substr($kost['alamat'], 0, 80); ?>...
                                </p>
                                <p class="card-text">
                                    <strong>Rp <?php echo $harga; ?></strong> / bulan
                                </p>
                                <p class="card-text small text-muted">
                                    <?php echo $deskripsi_singkat; ?>
                                </p>
                                <div class="facilities mb-3">
                                    <?php
                                    $fasilitas = explode(',', $kost['fasilitas']);
                                    $count = 0;
                                    foreach ($fasilitas as $fasilitas_item) {
                                        if ($count < 3) {
                                            echo '<span class="badge bg-light text-dark me-1 mb-1">' . trim($fasilitas_item) . '</span>';
                                            $count++;
                                        }
                                    }
                                    if (count($fasilitas) > 3) {
                                        echo '<span class="badge bg-light text-dark">+' . (count($fasilitas) - 3) . ' more</span>';
                                    }
                                    ?>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-bed"></i> 
                                        <?php echo $kost['kamar_tersedia']; ?> kamar tersedia
                                    </small>
                                    <button type="button" class="btn btn-primary btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#detailModal<?php echo $kost['id']; ?>">
                                        Lihat Detail
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detail Modal -->
                    <div class="modal fade" id="detailModal<?php echo $kost['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?php echo $kost['nama_kost']; ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <img src="uploads/kost/<?php echo $kost['foto_kost']; ?>" 
                                                 class="img-fluid rounded" 
                                                 alt="<?php echo $kost['nama_kost']; ?>"
                                                 onerror="this.src='https://via.placeholder.com/500x300?text=No+Image'">
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-primary">Rp <?php echo $harga; ?> / bulan</h6>
                                            <p class="text-muted">
                                                <i class="fas fa-map-marker-alt"></i> 
                                                <?php echo $kost['alamat']; ?>
                                            </p>
                                            <p><strong>Pemilik:</strong> <?php echo $kost['pemilik_nama']; ?></p>
                                            <p><strong>Kamar Tersedia:</strong> <?php echo $kost['kamar_tersedia']; ?> dari <?php echo $kost['total_kamar']; ?> kamar</p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <h6>Deskripsi</h6>
                                        <p><?php echo $kost['deskripsi'] ?: 'Tidak ada deskripsi tersedia.'; ?></p>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <h6>Fasilitas</h6>
                                        <div class="facilities">
                                            <?php
                                            $fasilitas = explode(',', $kost['fasilitas']);
                                            foreach ($fasilitas as $fasilitas_item) {
                                                echo '<span class="badge bg-primary me-1 mb-1">' . trim($fasilitas_item) . '</span>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($kost['peraturan']): ?>
                                    <div class="mt-3">
                                        <h6>Peraturan</h6>
                                        <p><?php echo $kost['peraturan']; ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                    <?php if (isLoggedIn() && $_SESSION['role'] == 'penyewa'): ?>
                                        <a href="penyewa/pemesanan.php?kost_id=<?php echo $kost['id']; ?>" 
                                           class="btn btn-primary">Pesan Sekarang</a>
                                    <?php else: ?>
                                        <a href="auth/login.php" class="btn btn-primary">Login untuk Memesan</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                if ($search_query) {
                    echo '<div class="col-12 text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">Tidak ada hasil pencarian</h4>
                            <p class="text-muted">Tidak ada kost yang sesuai dengan pencarian "' . htmlspecialchars($search_query) . '"</p>
                            <a href="index.php" class="btn btn-primary">Lihat Semua Kost</a>
                          </div>';
                } else {
                    echo '<div class="col-12 text-center py-5">
                            <i class="fas fa-building fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">Belum ada kost tersedia</h4>
                            <p class="text-muted">Silakan coba lagi nanti atau hubungi administrator.</p>
                          </div>';
                }
            }
            ?>
        </div>

        <?php if (!$search_query && $result->num_rows > 0): ?>
        <div class="row mt-4">
            <div class="col-12 text-center">
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo $_SESSION['role']; ?>/dashboard.php" class="btn btn-outline-primary">Lihat Semua Kost</a>
                <?php else: ?>
                    <a href="auth/login.php" class="btn btn-outline-primary">Login untuk Melihat Semua Kost</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Features Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="feature-icon">
                    <i class="fas fa-search fa-3x text-primary mb-3"></i>
                </div>
                <h4>Cari dengan Mudah</h4>
                <p class="text-muted">Temukan kost berdasarkan lokasi, harga, dan fasilitas yang diinginkan</p>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-icon">
                    <i class="fas fa-credit-card fa-3x text-primary mb-3"></i>
                </div>
                <h4>Bayar Online</h4>
                <p class="text-muted">Lakukan pembayaran secara online dengan mudah dan aman</p>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                </div>
                <h4>Aman dan Terpercaya</h4>
                <p class="text-muted">Sistem terverifikasi dengan notifikasi real-time</p>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="py-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 col-6 mb-4">
                <h3 class="text-primary" id="totalKost">
                    <?php 
                    $count_kost = $conn->query("SELECT COUNT(*) as total FROM kost WHERE status = 'tersedia'")->fetch_assoc();
                    echo $count_kost['total'] . '+';
                    ?>
                </h3>
                <p class="text-muted">Kost Tersedia</p>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <h3 class="text-primary" id="totalKamar">
                    <?php 
                    $count_kamar = $conn->query("SELECT COUNT(*) as total FROM kamars WHERE status = 'ditempati'")->fetch_assoc();
                    echo $count_kamar['total'] . '+';
                    ?>
                </h3>
                <p class="text-muted">Kamar Terjual</p>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <h3 class="text-primary" id="totalPenyewa">
                    <?php 
                    $count_penyewa = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'penyewa' AND is_active = 1")->fetch_assoc();
                    echo $count_penyewa['total'] . '+';
                    ?>
                </h3>
                <p class="text-muted">Penyewa Aktif</p>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <h3 class="text-primary">99%</h3>
                <p class="text-muted">Kepuasan Pengguna</p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>