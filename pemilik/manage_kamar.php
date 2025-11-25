<?php
include '../includes/config.php';
checkRole('pemilik');

$user_id = $_SESSION['user_id'];
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_kamar'])) {
        $kost_id = $_POST['kost_id'];
        $nomor_kamar = $conn->real_escape_string($_POST['nomor_kamar']);
        $ukuran_kamar = $conn->real_escape_string($_POST['ukuran_kamar']);
        $harga_per_bulan = $_POST['harga_per_bulan'];
        $fasilitas_kamar = $conn->real_escape_string($_POST['fasilitas_kamar']);
        $deskripsi_kamar = $conn->real_escape_string($_POST['deskripsi_kamar']);
        
        // Check if room number already exists in this kost
        $check_query = "SELECT id FROM kamars WHERE kost_id = ? AND nomor_kamar = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("is", $kost_id, $nomor_kamar);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $message = '<div class="alert alert-danger">Nomor kamar sudah ada di kost ini!</div>';
        } else {
            // Handle file upload
            $foto_kamar = null;
            if (isset($_FILES['foto_kamar']) && $_FILES['foto_kamar']['error'] == 0) {
                $upload_dir = '../uploads/kamar/';
                $file_extension = pathinfo($_FILES['foto_kamar']['name'], PATHINFO_EXTENSION);
                $foto_kamar = uniqid() . '.' . $file_extension;
                move_uploaded_file($_FILES['foto_kamar']['tmp_name'], $upload_dir . $foto_kamar);
            }
            
            $query = "INSERT INTO kamars (kost_id, nomor_kamar, ukuran_kamar, harga_per_bulan, fasilitas_kamar, deskripsi_kamar, foto_kamar) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("issdsss", $kost_id, $nomor_kamar, $ukuran_kamar, $harga_per_bulan, $fasilitas_kamar, $deskripsi_kamar, $foto_kamar);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Kamar berhasil ditambahkan!</div>';
            } else {
                $message = '<div class="alert alert-danger">Gagal menambahkan kamar: ' . $stmt->error . '</div>';
            }
        }
    }
    
    if (isset($_POST['update_kamar'])) {
        $kamar_id = $_POST['kamar_id'];
        $nomor_kamar = $conn->real_escape_string($_POST['nomor_kamar']);
        $ukuran_kamar = $conn->real_escape_string($_POST['ukuran_kamar']);
        $harga_per_bulan = $_POST['harga_per_bulan'];
        $fasilitas_kamar = $conn->real_escape_string($_POST['fasilitas_kamar']);
        $deskripsi_kamar = $conn->real_escape_string($_POST['deskripsi_kamar']);
        $status = $_POST['status'];
        
        // Handle file upload
        $foto_kamar = $_POST['current_foto'];
        if (isset($_FILES['foto_kamar']) && $_FILES['foto_kamar']['error'] == 0) {
            $upload_dir = '../uploads/kamar/';
            $file_extension = pathinfo($_FILES['foto_kamar']['name'], PATHINFO_EXTENSION);
            $foto_kamar = uniqid() . '.' . $file_extension;
            move_uploaded_file($_FILES['foto_kamar']['tmp_name'], $upload_dir . $foto_kamar);
            
            // Delete old photo if exists
            if (!empty($_POST['current_foto'])) {
                unlink($upload_dir . $_POST['current_foto']);
            }
        }
        
        $query = "UPDATE kamars SET nomor_kamar=?, ukuran_kamar=?, harga_per_bulan=?, fasilitas_kamar=?, deskripsi_kamar=?, status=?, foto_kamar=? WHERE id=? AND kost_id IN (SELECT id FROM kost WHERE pemilik_id=?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssdssssii", $nomor_kamar, $ukuran_kamar, $harga_per_bulan, $fasilitas_kamar, $deskripsi_kamar, $status, $foto_kamar, $kamar_id, $user_id);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Kamar berhasil diupdate!</div>';
        } else {
            $message = '<div class="alert alert-danger">Gagal mengupdate kamar: ' . $stmt->error . '</div>';
        }
    }
    
    if (isset($_POST['delete_kamar'])) {
        $kamar_id = $_POST['kamar_id'];
        
        $query = "DELETE FROM kamars WHERE id=? AND kost_id IN (SELECT id FROM kost WHERE pemilik_id=?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $kamar_id, $user_id);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Kamar berhasil dihapus!</div>';
        } else {
            $message = '<div class="alert alert-danger">Gagal menghapus kamar: ' . $stmt->error . '</div>';
        }
    }
}

// Get all kost owned by this user for dropdown
$kost_query = "SELECT * FROM kost WHERE pemilik_id = ? ORDER BY nama_kost";
$stmt = $conn->prepare($kost_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$kost_options = $stmt->get_result();

// Get all kamar with kost information
$kamar_query = "SELECT km.*, k.nama_kost, k.alamat 
                FROM kamars km 
                JOIN kost k ON km.kost_id = k.id 
                WHERE k.pemilik_id = ? 
                ORDER BY k.nama_kost, km.nomor_kamar";
$stmt = $conn->prepare($kamar_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$kamar_list = $stmt->get_result();

// Get kamar details for editing
$edit_kamar = null;
if (isset($_GET['edit'])) {
    $kamar_id = $_GET['edit'];
    $query = "SELECT km.*, k.nama_kost 
              FROM kamars km 
              JOIN kost k ON km.kost_id = k.id 
              WHERE km.id = ? AND k.pemilik_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $kamar_id, $user_id);
    $stmt->execute();
    $edit_kamar = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kamar - Vibes Kost</title>
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
        .kamar-card {
            transition: transform 0.3s ease;
        }
        .kamar-card:hover {
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
                    <a class="nav-link" href="manage_kost.php">
                        <i class="fas fa-home"></i> Kelola Kost
                    </a>
                    <a class="nav-link active" href="manage_kamar.php">
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
                        <h2><i class="fas fa-bed"></i> Kelola Kamar</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addKamarModal">
                            <i class="fas fa-plus"></i> Tambah Kamar
                        </button>
                    </div>
                    
                    <?php echo $message; ?>
                    
                    <!-- Kamar List -->
                    <div class="row">
                        <?php if ($kamar_list->num_rows > 0): ?>
                            <?php while ($kamar = $kamar_list->fetch_assoc()): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card kamar-card h-100">
                                        <img src="../uploads/kamar/<?php echo $kamar['foto_kamar'] ?: 'default.jpg'; ?>" 
                                             class="card-img-top" 
                                             alt="Kamar <?php echo $kamar['nomor_kamar']; ?>"
                                             style="height: 200px; object-fit: cover;"
                                             onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                                        <div class="card-body">
                                            <h5 class="card-title">Kamar <?php echo $kamar['nomor_kamar']; ?></h5>
                                            <p class="card-text text-muted">
                                                <i class="fas fa-home"></i> 
                                                <?php echo $kamar['nama_kost']; ?>
                                            </p>
                                            <p class="card-text">
                                                <strong>Rp <?php echo number_format($kamar['harga_per_bulan'], 0, ',', '.'); ?></strong> / bulan
                                            </p>
                                            <p class="card-text">
                                                <small>Ukuran: <?php echo $kamar['ukuran_kamar'] ?: '-'; ?></small>
                                            </p>
                                            <div class="facilities mb-3">
                                                <?php
                                                if (!empty($kamar['fasilitas_kamar'])) {
                                                    $fasilitas = explode(',', $kamar['fasilitas_kamar']);
                                                    $count = 0;
                                                    foreach ($fasilitas as $fasilitas_item) {
                                                        if ($count < 3) {
                                                            echo '<span class="badge bg-light text-dark me-1 mb-1">' . trim($fasilitas_item) . '</span>';
                                                            $count++;
                                                        }
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-<?php 
                                                    switch($kamar['status']) {
                                                        case 'tersedia': echo 'success'; break;
                                                        case 'dipesan': echo 'warning'; break;
                                                        case 'ditempati': echo 'danger'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>"><?php echo ucfirst($kamar['status']); ?></span>
                                                <div>
                                                    <a href="?edit=<?php echo $kamar['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-danger" 
                                                            onclick="confirmDelete(<?php echo $kamar['id']; ?>, 'Kamar <?php echo $kamar['nomor_kamar']; ?>')">
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
                                    <i class="fas fa-bed fa-2x mb-3"></i>
                                    <h4>Belum ada kamar</h4>
                                    <p>Tambahkan kamar pertama Anda untuk mulai menerima pemesanan.</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addKamarModal">
                                        <i class="fas fa-plus"></i> Tambah Kamar Pertama
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Kamar Modal -->
    <div class="modal fade" id="addKamarModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kamar Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Pilih Kost</label>
                                <select name="kost_id" class="form-control" required>
                                    <option value="">-- Pilih Kost --</option>
                                    <?php while ($kost = $kost_options->fetch_assoc()): ?>
                                        <option value="<?php echo $kost['id']; ?>"><?php echo $kost['nama_kost']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nomor Kamar</label>
                                <input type="text" name="nomor_kamar" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ukuran Kamar</label>
                                <input type="text" name="ukuran_kamar" class="form-control" placeholder="Contoh: 3x4 meter">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Harga per Bulan</label>
                                <input type="number" name="harga_per_bulan" class="form-control" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Fasilitas Kamar (pisahkan dengan koma)</label>
                                <input type="text" name="fasilitas_kamar" class="form-control" placeholder="Contoh: AC, Lemari, Kasur, Meja Belajar">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Deskripsi Kamar</label>
                                <textarea name="deskripsi_kamar" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Foto Kamar</label>
                                <input type="file" name="foto_kamar" class="form-control" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_kamar" class="btn btn-primary">Simpan Kamar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Kamar Modal -->
    <?php if ($edit_kamar): ?>
    <div class="modal fade show" id="editKamarModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Kamar: <?php echo $edit_kamar['nomor_kamar']; ?></h5>
                    <a href="manage_kamar.php" class="btn-close"></a>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="kamar_id" value="<?php echo $edit_kamar['id']; ?>">
                        <input type="hidden" name="current_foto" value="<?php echo $edit_kamar['foto_kamar']; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kost</label>
                                <input type="text" class="form-control" value="<?php echo $edit_kamar['nama_kost']; ?>" readonly>
                                <small class="text-muted">Tidak dapat mengubah kost</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nomor Kamar</label>
                                <input type="text" name="nomor_kamar" class="form-control" value="<?php echo $edit_kamar['nomor_kamar']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ukuran Kamar</label>
                                <input type="text" name="ukuran_kamar" class="form-control" value="<?php echo $edit_kamar['ukuran_kamar']; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Harga per Bulan</label>
                                <input type="number" name="harga_per_bulan" class="form-control" value="<?php echo $edit_kamar['harga_per_bulan']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="tersedia" <?php echo $edit_kamar['status'] == 'tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                                    <option value="dipesan" <?php echo $edit_kamar['status'] == 'dipesan' ? 'selected' : ''; ?>>Dipesan</option>
                                    <option value="ditempati" <?php echo $edit_kamar['status'] == 'ditempati' ? 'selected' : ''; ?>>Ditempati</option>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Fasilitas Kamar</label>
                                <input type="text" name="fasilitas_kamar" class="form-control" value="<?php echo $edit_kamar['fasilitas_kamar']; ?>">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Deskripsi Kamar</label>
                                <textarea name="deskripsi_kamar" class="form-control" rows="3"><?php echo $edit_kamar['deskripsi_kamar']; ?></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Foto Kamar</label>
                                <input type="file" name="foto_kamar" class="form-control" accept="image/*">
                                <small class="text-muted">Biarkan kosong jika tidak ingin mengubah foto</small>
                            </div>
                            <div class="col-12 mb-3">
                                <?php if (!empty($edit_kamar['foto_kamar'])): ?>
                                    <img src="../uploads/kamar/<?php echo $edit_kamar['foto_kamar']; ?>" 
                                         class="img-thumbnail" 
                                         width="200"
                                         onerror="this.src='https://via.placeholder.com/200x150?text=No+Image'">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="manage_kamar.php" class="btn btn-secondary">Batal</a>
                        <button type="submit" name="update_kamar" class="btn btn-primary">Update Kamar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Delete Confirmation Form -->
    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="kamar_id" id="deleteKamarId">
        <input type="hidden" name="delete_kamar">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(kamarId, kamarName) {
            if (confirm(`Apakah Anda yakin ingin menghapus ${kamarName}?`)) {
                document.getElementById('deleteKamarId').value = kamarId;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Show edit modal if editing
        <?php if ($edit_kamar): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var editModal = new bootstrap.Modal(document.getElementById('editKamarModal'));
                editModal.show();
            });
        <?php endif; ?>
    </script>
</body>
</html>