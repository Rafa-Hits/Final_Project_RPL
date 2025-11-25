<?php
include '../includes/config.php';
checkRole('pemilik');

$user_id = $_SESSION['user_id'];
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_kost'])) {
        $nama_kost = $conn->real_escape_string($_POST['nama_kost']);
        $alamat = $conn->real_escape_string($_POST['alamat']);
        $deskripsi = $conn->real_escape_string($_POST['deskripsi']);
        $harga_per_bulan = $_POST['harga_per_bulan'];
        $fasilitas = $conn->real_escape_string($_POST['fasilitas']);
        $peraturan = $conn->real_escape_string($_POST['peraturan']);
        
        // Handle file upload
        $foto_kost = 'default.jpg';
        if (isset($_FILES['foto_kost']) && $_FILES['foto_kost']['error'] == 0) {
            $upload_dir = '../uploads/kost/';
            $file_extension = pathinfo($_FILES['foto_kost']['name'], PATHINFO_EXTENSION);
            $foto_kost = uniqid() . '.' . $file_extension;
            move_uploaded_file($_FILES['foto_kost']['tmp_name'], $upload_dir . $foto_kost);
        }
        
        $query = "INSERT INTO kost (pemilik_id, nama_kost, alamat, deskripsi, harga_per_bulan, fasilitas, peraturan, foto_kost) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssdsss", $user_id, $nama_kost, $alamat, $deskripsi, $harga_per_bulan, $fasilitas, $peraturan, $foto_kost);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Kost berhasil ditambahkan!</div>';
        } else {
            $message = '<div class="alert alert-danger">Gagal menambahkan kost: ' . $stmt->error . '</div>';
        }
    }
    
    if (isset($_POST['update_kost'])) {
        $kost_id = $_POST['kost_id'];
        $nama_kost = $conn->real_escape_string($_POST['nama_kost']);
        $alamat = $conn->real_escape_string($_POST['alamat']);
        $deskripsi = $conn->real_escape_string($_POST['deskripsi']);
        $harga_per_bulan = $_POST['harga_per_bulan'];
        $fasilitas = $conn->real_escape_string($_POST['fasilitas']);
        $peraturan = $conn->real_escape_string($_POST['peraturan']);
        $status = $_POST['status'];
        
        // Handle file upload
        $foto_kost = $_POST['current_foto'];
        if (isset($_FILES['foto_kost']) && $_FILES['foto_kost']['error'] == 0) {
            $upload_dir = '../uploads/kost/';
            $file_extension = pathinfo($_FILES['foto_kost']['name'], PATHINFO_EXTENSION);
            $foto_kost = uniqid() . '.' . $file_extension;
            move_uploaded_file($_FILES['foto_kost']['tmp_name'], $upload_dir . $foto_kost);
            
            // Delete old photo if not default
            if ($_POST['current_foto'] != 'default.jpg') {
                unlink($upload_dir . $_POST['current_foto']);
            }
        }
        
        $query = "UPDATE kost SET nama_kost=?, alamat=?, deskripsi=?, harga_per_bulan=?, fasilitas=?, peraturan=?, status=?, foto_kost=? WHERE id=? AND pemilik_id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssdssssii", $nama_kost, $alamat, $deskripsi, $harga_per_bulan, $fasilitas, $peraturan, $status, $foto_kost, $kost_id, $user_id);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Kost berhasil diupdate!</div>';
        } else {
            $message = '<div class="alert alert-danger">Gagal mengupdate kost: ' . $stmt->error . '</div>';
        }
    }
    
    if (isset($_POST['delete_kost'])) {
        $kost_id = $_POST['kost_id'];
        
        $query = "DELETE FROM kost WHERE id=? AND pemilik_id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $kost_id, $user_id);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Kost berhasil dihapus!</div>';
        } else {
            $message = '<div class="alert alert-danger">Gagal menghapus kost: ' . $stmt->error . '</div>';
        }
    }
}

// Get all kost owned by this user
$query = "SELECT * FROM kost WHERE pemilik_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$kost_list = $stmt->get_result();

// Get kost details for editing
$edit_kost = null;
if (isset($_GET['edit'])) {
    $kost_id = $_GET['edit'];
    $query = "SELECT * FROM kost WHERE id = ? AND pemilik_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $kost_id, $user_id);
    $stmt->execute();
    $edit_kost = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kost - Vibes Kost</title>
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
        .kost-card {
            transition: transform 0.3s ease;
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
                        <small class="text-muted">Pemilik Kost</small>
                    </div>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link active" href="manage_kost.php">
                        <i class="fas fa-home"></i> Kelola Kost
                    </a>
                    <a class="nav-link" href="manage_kamar.php">
                        <i class="fas fa-bed"></i> Kelola Kamar
                    </a>
                    <a class="nav-link" href="konfirmasi_pembayaran.php">
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-home"></i> Kelola Kost</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addKostModal">
                            <i class="fas fa-plus"></i> Tambah Kost
                        </button>
                    </div>
                    
                    <?php echo $message; ?>
                    
                    <!-- Kost List -->
                    <div class="row">
                        <?php if ($kost_list->num_rows > 0): ?>
                            <?php while ($kost = $kost_list->fetch_assoc()): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card kost-card h-100">
                                        <img src="../uploads/kost/<?php echo $kost['foto_kost']; ?>" 
                                             class="card-img-top" 
                                             alt="<?php echo $kost['nama_kost']; ?>"
                                             style="height: 200px; object-fit: cover;"
                                             onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo $kost['nama_kost']; ?></h5>
                                            <p class="card-text text-muted">
                                                <i class="fas fa-map-marker-alt"></i> 
                                                <?php echo substr($kost['alamat'], 0, 100); ?>...
                                            </p>
                                            <p class="card-text">
                                                <strong>Rp <?php echo number_format($kost['harga_per_bulan'], 0, ',', '.'); ?></strong> / bulan
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
                                                ?>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-<?php echo $kost['status'] == 'tersedia' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($kost['status']); ?>
                                                </span>
                                                <div>
                                                    <a href="?edit=<?php echo $kost['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-danger" 
                                                            onclick="confirmDelete(<?php echo $kost['id']; ?>, '<?php echo $kost['nama_kost']; ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-info text-center">
                                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                                    <h4>Belum ada kost</h4>
                                    <p>Tambahkan kost pertama Anda untuk mulai berbisnis.</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addKostModal">
                                        <i class="fas fa-plus"></i> Tambah Kost Pertama
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Kost Modal -->
    <div class="modal fade" id="addKostModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kost Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Kost</label>
                                <input type="text" name="nama_kost" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Harga per Bulan</label>
                                <input type="number" name="harga_per_bulan" class="form-control" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Alamat Lengkap</label>
                                <textarea name="alamat" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="deskripsi" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Fasilitas (pisahkan dengan koma)</label>
                                <input type="text" name="fasilitas" class="form-control" placeholder="Contoh: WiFi, AC, Kamar Mandi Dalam, Parkir Motor">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Peraturan</label>
                                <textarea name="peraturan" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Foto Kost</label>
                                <input type="file" name="foto_kost" class="form-control" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_kost" class="btn btn-primary">Simpan Kost</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Kost Modal -->
    <?php if ($edit_kost): ?>
    <div class="modal fade show" id="editKostModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Kost: <?php echo $edit_kost['nama_kost']; ?></h5>
                    <a href="manage_kost.php" class="btn-close"></a>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="kost_id" value="<?php echo $edit_kost['id']; ?>">
                        <input type="hidden" name="current_foto" value="<?php echo $edit_kost['foto_kost']; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Kost</label>
                                <input type="text" name="nama_kost" class="form-control" value="<?php echo $edit_kost['nama_kost']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Harga per Bulan</label>
                                <input type="number" name="harga_per_bulan" class="form-control" value="<?php echo $edit_kost['harga_per_bulan']; ?>" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Alamat Lengkap</label>
                                <textarea name="alamat" class="form-control" rows="3" required><?php echo $edit_kost['alamat']; ?></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="deskripsi" class="form-control" rows="3"><?php echo $edit_kost['deskripsi']; ?></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Fasilitas</label>
                                <input type="text" name="fasilitas" class="form-control" value="<?php echo $edit_kost['fasilitas']; ?>">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Peraturan</label>
                                <textarea name="peraturan" class="form-control" rows="3"><?php echo $edit_kost['peraturan']; ?></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="tersedia" <?php echo $edit_kost['status'] == 'tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                                    <option value="tidak_tersedia" <?php echo $edit_kost['status'] == 'tidak_tersedia' ? 'selected' : ''; ?>>Tidak Tersedia</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Foto Kost</label>
                                <input type="file" name="foto_kost" class="form-control" accept="image/*">
                                <small class="text-muted">Biarkan kosong jika tidak ingin mengubah foto</small>
                            </div>
                            <div class="col-12 mb-3">
                                <img src="../uploads/kost/<?php echo $edit_kost['foto_kost']; ?>" 
                                     class="img-thumbnail" 
                                     width="200"
                                     onerror="this.src='https://via.placeholder.com/200x150?text=No+Image'">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="manage_kost.php" class="btn btn-secondary">Batal</a>
                        <button type="submit" name="update_kost" class="btn btn-primary">Update Kost</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Delete Confirmation Form -->
    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="kost_id" id="deleteKostId">
        <input type="hidden" name="delete_kost">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(kostId, kostName) {
            if (confirm(`Apakah Anda yakin ingin menghapus kost "${kostName}"?`)) {
                document.getElementById('deleteKostId').value = kostId;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Show edit modal if editing
        <?php if ($edit_kost): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var editModal = new bootstrap.Modal(document.getElementById('editKostModal'));
                editModal.show();
            });
        <?php endif; ?>
    </script>
</body>
</html>