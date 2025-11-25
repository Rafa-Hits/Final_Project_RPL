<?php
include '../includes/config.php';
checkRole('admin');

$user_id = $_SESSION['user_id'];

// Get user data
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $nama = $conn->real_escape_string($_POST['nama']);
    $email = $conn->real_escape_string($_POST['email']);
    $no_telepon = $conn->real_escape_string($_POST['no_telepon']);
    $alamat = $conn->real_escape_string($_POST['alamat']);
    
    // Handle photo upload
    $foto_profil = $user['foto_profil'];
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0) {
        $upload_dir = '../uploads/profil/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
        $file_name = 'admin_' . $user_id . '_' . time() . '.' . $file_ext;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $file_path)) {
            // Delete old photo if exists and not default
            if ($foto_profil != 'default.jpg' && file_exists($upload_dir . $foto_profil)) {
                unlink($upload_dir . $foto_profil);
            }
            $foto_profil = $file_name;
        }
    }
    
    $update_query = "UPDATE users SET nama = ?, email = ?, no_telepon = ?, alamat = ?, foto_profil = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssssi", $nama, $email, $no_telepon, $alamat, $foto_profil, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['nama'] = $nama;
        $_SESSION['email'] = $email;
        $_SESSION['foto_profil'] = $foto_profil;
        $_SESSION['success'] = "Profile berhasil diperbarui";
    } else {
        $_SESSION['error'] = "Gagal memperbarui profile";
    }
    
    header("Location: profile.php");
    exit();
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Password berhasil diubah";
            } else {
                $_SESSION['error'] = "Gagal mengubah password";
            }
        } else {
            $_SESSION['error'] = "Password baru tidak cocok";
        }
    } else {
        $_SESSION['error'] = "Password saat ini salah";
    }
    
    header("Location: profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Admin Vibes Kost</title>
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
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            margin: 0 auto 20px;
            overflow: hidden;
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
                    <a class="nav-link" href="manage_pembayaran.php">
                        <i class="fas fa-credit-card"></i> Manage Pembayaran
                    </a>
                    <a class="nav-link" href="backup.php">
                        <i class="fas fa-database"></i> Backup Data
                    </a>
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog"></i> Settings
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
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-user"></i> My Profile</h2>
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

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Profile Card -->
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="profile-picture">
                                <img src="../uploads/profil/<?php echo $user['foto_profil']; ?>" 
                                     alt="Profile Picture" 
                                     class="w-100 h-100"
                                     style="object-fit: cover;"
                                     onerror="this.src='https://via.placeholder.com/150'">
                            </div>
                            <h3><?php echo $user['nama']; ?></h3>
                            <p class="mb-0">Administrator - Vibes Kost</p>
                        </div>
                        
                        <div class="p-4">
                            <!-- Profile Update Form -->
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Lengkap</label>
                                            <input type="text" name="nama" class="form-control" value="<?php echo $user['nama']; ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Nomor Telepon</label>
                                            <input type="text" name="no_telepon" class="form-control" value="<?php echo $user['no_telepon']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Foto Profil</label>
                                            <input type="file" name="foto_profil" class="form-control" accept="image/*">
                                            <small class="text-muted">Ukuran maksimal 2MB. Format: JPG, PNG, GIF</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Alamat</label>
                                            <textarea name="alamat" class="form-control" rows="3"><?php echo $user['alamat']; ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Role</label>
                                            <input type="text" class="form-control" value="Administrator" readonly>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </form>

                            <hr class="my-4">

                            <!-- Password Change Form -->
                            <h5 class="mb-3"><i class="fas fa-lock"></i> Ubah Password</h5>
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Password Saat Ini</label>
                                            <input type="password" name="current_password" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Password Baru</label>
                                            <input type="password" name="new_password" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Konfirmasi Password Baru</label>
                                            <input type="password" name="confirm_password" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-warning">
                                    <i class="fas fa-key"></i> Ubah Password
                                </button>
                            </form>

                            <hr class="my-4">

                            <!-- Account Information -->
                            <h5 class="mb-3"><i class="fas fa-info-circle"></i> Informasi Akun</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>User ID</th>
                                            <td><?php echo $user['id']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Daftar</th>
                                            <td><?php echo date('d M Y H:i', strtotime($user['created_at'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Terakhir Update</th>
                                            <td><?php echo date('d M Y H:i', strtotime($user['updated_at'])); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                <span class="badge bg-success">Aktif</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Role</th>
                                            <td>
                                                <span class="badge bg-danger">Administrator</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Login Terakhir</th>
                                            <td><?php echo date('d M Y H:i'); ?></td>
                                        </tr>
                                    </table>
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
        // Preview image before upload
        document.querySelector('input[name="foto_profil"]').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-picture img').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>