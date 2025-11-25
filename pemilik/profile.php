<?php
include '../includes/config.php';
checkRole('pemilik');

$user_id = $_SESSION['user_id'];
$message = '';

// Get user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $nama = $conn->real_escape_string($_POST['nama']);
    $email = $conn->real_escape_string($_POST['email']);
    $no_telepon = $conn->real_escape_string($_POST['no_telepon']);
    $alamat = $conn->real_escape_string($_POST['alamat']);
    
    // Handle file upload
    $foto_profil = $user['foto_profil'];
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0) {
        $upload_dir = '../uploads/profil/';
        $file_extension = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
        $foto_profil = uniqid() . '.' . $file_extension;
        move_uploaded_file($_FILES['foto_profil']['tmp_name'], $upload_dir . $foto_profil);
        
        // Delete old photo if not default
        if ($user['foto_profil'] != 'default.jpg') {
            unlink($upload_dir . $user['foto_profil']);
        }
    }
    
    $query = "UPDATE users SET nama=?, email=?, no_telepon=?, alamat=?, foto_profil=? WHERE id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssi", $nama, $email, $no_telepon, $alamat, $foto_profil, $user_id);
    
    if ($stmt->execute()) {
        // Update session
        $_SESSION['nama'] = $nama;
        $_SESSION['email'] = $email;
        $_SESSION['foto_profil'] = $foto_profil;
        
        $message = '<div class="alert alert-success">Profile berhasil diupdate!</div>';
        
        // Refresh user data
        $user['nama'] = $nama;
        $user['email'] = $email;
        $user['no_telepon'] = $no_telepon;
        $user['alamat'] = $alamat;
        $user['foto_profil'] = $foto_profil;
    } else {
        $message = '<div class="alert alert-danger">Gagal mengupdate profile: ' . $stmt->error . '</div>';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password=? WHERE id=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Password berhasil diubah!</div>';
            } else {
                $message = '<div class="alert alert-danger">Gagal mengubah password: ' . $stmt->error . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Password baru tidak cocok!</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Password saat ini salah!</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Vibes Kost</title>
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
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
                    <a class="nav-link" href="konfirmasi_pembayaran.php">
                        <i class="fas fa-credit-card"></i> Konfirmasi Pembayaran
                    </a>
                    <a class="nav-link" href="laporan.php">
                        <i class="fas fa-chart-bar"></i> Laporan
                    </a>
                    <a class="nav-link" href="notifikasi.php">
                        <i class="fas fa-bell"></i> Notifikasi
                    </a>
                    <a class="nav-link active" href="profile.php">
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
                    <h2 class="mb-4"><i class="fas fa-user"></i> Profile Saya</h2>
                    
                    <?php echo $message; ?>
                    
                    <div class="row">
                        <!-- Profile Information -->
                        <div class="col-md-8">
                            <div class="profile-card p-4 mb-4">
                                <h4 class="mb-4">Informasi Profile</h4>
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nama Lengkap</label>
                                            <input type="text" name="nama" class="form-control" value="<?php echo $user['nama']; ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nomor Telepon</label>
                                            <input type="text" name="no_telepon" class="form-control" value="<?php echo $user['no_telepon']; ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Foto Profil</label>
                                            <input type="file" name="foto_profil" class="form-control" accept="image/*">
                                        </div>
                                        <div class="col-12 mb-3">
                                            <label class="form-label">Alamat</label>
                                            <textarea name="alamat" class="form-control" rows="3"><?php echo $user['alamat']; ?></textarea>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" name="update_profile" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Update Profile
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Profile Picture and Stats -->
                        <div class="col-md-4">
                            <div class="profile-card p-4 text-center mb-4">
                                <img src="../uploads/profil/<?php echo $user['foto_profil']; ?>" 
                                     class="rounded-circle mb-3" 
                                     width="150" 
                                     height="150"
                                     alt="Profile"
                                     onerror="this.src='https://via.placeholder.com/150'">
                                <h5><?php echo $user['nama']; ?></h5>
                                <p class="text-muted">Pemilik Kost</p>
                                <p class="text-muted">Member sejak: <?php echo date('d F Y', strtotime($user['created_at'])); ?></p>
                            </div>
                            
                            <!-- Change Password -->
                            <div class="profile-card p-4">
                                <h5 class="mb-3">Ubah Password</h5>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Password Saat Ini</label>
                                        <input type="password" name="current_password" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password Baru</label>
                                        <input type="password" name="new_password" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Konfirmasi Password Baru</label>
                                        <input type="password" name="confirm_password" class="form-control" required>
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-warning w-100">
                                        <i class="fas fa-key"></i> Ubah Password
                                    </button>
                                </form>
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