<?php
include '../includes/config.php';
checkRole('admin');

// Handle kost actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $kost_id = intval($_GET['id']);
    
    switch ($_GET['action']) {
        case 'activate':
            $conn->query("UPDATE kost SET status = 'tersedia' WHERE id = $kost_id");
            $_SESSION['success'] = "Kost berhasil diaktifkan";
            break;
        case 'deactivate':
            $conn->query("UPDATE kost SET status = 'tidak_tersedia' WHERE id = $kost_id");
            $_SESSION['success'] = "Kost berhasil dinonaktifkan";
            break;
        case 'delete':
            $conn->query("DELETE FROM kost WHERE id = $kost_id");
            $_SESSION['success'] = "Kost berhasil dihapus";
            break;
    }
    header("Location: manage_kost.php");
    exit();
}

// Get all kost with owner info
$kost_query = "
    SELECT k.*, u.nama as pemilik_nama, u.email as pemilik_email,
           COUNT(km.id) as total_kamar,
           COUNT(CASE WHEN km.status = 'tersedia' THEN km.id END) as kamar_tersedia
    FROM kost k
    JOIN users u ON k.pemilik_id = u.id
    LEFT JOIN kamars km ON k.id = km.kost_id
    GROUP BY k.id
    ORDER BY k.created_at DESC
";
$kost_list = $conn->query($kost_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Kost - Admin Vibes Kost</title>
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
        .kost-card {
            transition: transform 0.3s;
        }
        .kost-card:hover {
            transform: translateY(-5px);
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
                    <a class="nav-link active" href="manage_kost.php">
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
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-building"></i> Manage Kost</h2>
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

                    <!-- Kost List -->
                    <div class="row">
                        <?php while($kost = $kost_list->fetch_assoc()): 
                            $harga = number_format($kost['harga_per_bulan'], 0, ',', '.');
                        ?>
                        <div class="col-md-6 mb-4">
                            <div class="card kost-card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?php echo $kost['nama_kost']; ?></h5>
                                    <span class="badge bg-<?php echo $kost['status'] == 'tersedia' ? 'success' : 'danger'; ?>">
                                        <?php echo $kost['status'] == 'tersedia' ? 'Tersedia' : 'Tidak Tersedia'; ?>
                                    </span>
                                </div>
                                <img src="../uploads/kost/<?php echo $kost['foto_kost']; ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo $kost['nama_kost']; ?>"
                                     style="height: 200px; object-fit: cover;"
                                     onerror="this.src='https://via.placeholder.com/300x200'">
                                <div class="card-body">
                                    <p class="card-text">
                                        <i class="fas fa-map-marker-alt text-danger"></i> 
                                        <?php echo substr($kost['alamat'], 0, 100); ?>...
                                    </p>
                                    <p class="card-text">
                                        <strong>Rp <?php echo $harga; ?></strong> / bulan
                                    </p>
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-user-tie"></i> Pemilik: <?php echo $kost['pemilik_nama']; ?>
                                        </small><br>
                                        <small class="text-muted">
                                            <i class="fas fa-bed"></i> Kamar: <?php echo $kost['kamar_tersedia']; ?> tersedia dari <?php echo $kost['total_kamar']; ?> total
                                        </small>
                                    </div>
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
                                </div>
                                <div class="card-footer">
                                    <div class="btn-group w-100">
                                        <?php if ($kost['status'] == 'tersedia'): ?>
                                            <a href="manage_kost.php?action=deactivate&id=<?php echo $kost['id']; ?>" 
                                               class="btn btn-warning btn-sm">
                                                <i class="fas fa-pause"></i> Nonaktifkan
                                            </a>
                                        <?php else: ?>
                                            <a href="manage_kost.php?action=activate&id=<?php echo $kost['id']; ?>" 
                                               class="btn btn-success btn-sm">
                                                <i class="fas fa-play"></i> Aktifkan
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="manage_kost.php?action=delete&id=<?php echo $kost['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Yakin ingin menghapus kost ini?')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>

                    <?php if ($kost_list->num_rows == 0): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-building fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Belum ada kost terdaftar</h4>
                        <p class="text-muted">Tidak ada data kost yang ditemukan dalam sistem.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
