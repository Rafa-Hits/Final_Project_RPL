<?php
include '../includes/config.php';
checkRole('penyewa');

$user_id = $_SESSION['user_id'];
$success = $error = '';

// Get user data
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $nama = $conn->real_escape_string($_POST['nama']);
    $no_telepon = $conn->real_escape_string($_POST['no_telepon']);
    $alamat = $conn->real_escape_string($_POST['alamat']);
    
    $update_query = "UPDATE users SET nama = ?, no_telepon = ?, alamat = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssi", $nama, $no_telepon, $alamat, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['nama'] = $nama;
        $success = "Profile berhasil diperbarui!";
        // Refresh user data
        $user = $stmt->get_result()->fetch_assoc();
    } else {
        $error = "Gagal memperbarui profile!";
    }
}

// Change password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
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
                $success = "Password berhasil diubah!";
            } else {
                $error = "Gagal mengubah password!";
            }
        } else {
            $error = "Password baru tidak cocok!";
        }
    } else {
        $error = "Password saat ini salah!";
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
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 30px;
            text-align: center;
        }
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            margin-top: -75px;
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
                        <h2><i class="fas fa-user"></i> Profile Saya</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Profile</li>
                            </ol>
                        </nav>
                    </div>

                    <!-- Alerts -->
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Profile Information -->
                        <div class="col-md-8">
                            <div class="profile-card mb-4">
                                <div class="profile-header">
                                    <h4>Informasi Profile</h4>
                                    <p>Kelola informasi profile Anda</p>
                                </div>
                                <div class="card-body p-4">
                                    <form method="POST" action="">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="nama" class="form-label">Nama Lengkap</label>
                                                <input type="text" class="form-control" id="nama" name="nama" 
                                                       value="<?php echo $user['nama']; ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="email" 
                                                       value="<?php echo $user['email']; ?>" readonly>
                                                <small class="text-muted">Email tidak dapat diubah</small>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="no_telepon" class="form-label">No. Telepon</label>
                                                <input type="text" class="form-control" id="no_telepon" name="no_telepon" 
                                                       value="<?php echo $user['no_telepon']; ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="role" class="form-label">Role</label>
                                                <input type="text" class="form-control" 
                                                       value="<?php echo ucfirst($user['role']); ?>" readonly>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="alamat" class="form-label">Alamat</label>
                                            <textarea class="form-control" id="alamat" name="alamat" 
                                                      rows="3"><?php echo $user['alamat']; ?></textarea>
                                        </div>
                                        
                                        <button type="submit" name="update_profile" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Update Profile
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Profile Picture & Change Password -->
                        <div class="col-md-4">
                            <!-- Profile Picture -->
                            <div class="profile-card mb-4">
                                <div class="card-body text-center">
                                    <img src="../uploads/profil/<?php echo $user['foto_profil']; ?>" 
                                         class="profile-picture mb-3"
                                         alt="Profile Picture"
                                         onerror="this.src='https://via.placeholder.com/150'">
                                    <h5><?php echo $user['nama']; ?></h5>
                                    <p class="text-muted"><?php echo ucfirst($user['role']); ?></p>
                                    <button class="btn btn-outline-primary btn-sm" disabled>
                                        <i class="fas fa-camera"></i> Ubah Foto
                                    </button>
                                    <small class="d-block text-muted mt-2">Fitur upload foto coming soon</small>
                                </div>
                            </div>
                            
                            <!-- Change Password -->
                            <div class="profile-card">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0"><i class="fas fa-lock"></i> Ubah Password</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Password Saat Ini</label>
                                            <input type="password" class="form-control" id="current_password" 
                                                   name="current_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">Password Baru</label>
                                            <input type="password" class="form-control" id="new_password" 
                                                   name="new_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                            <input type="password" class="form-control" id="confirm_password" 
                                                   name="confirm_password" required>
                                        </div>
                                        <button type="submit" name="change_password" class="btn btn-warning w-100">
                                            <i class="fas fa-key"></i> Ubah Password
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Account Info -->
                    <div class="row">
                        <div class="col-12">
                            <div class="profile-card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informasi Akun</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>Status Akun:</strong>
                                            <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $user['is_active'] ? 'Aktif' : 'Non-Aktif'; ?>
                                            </span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Terdaftar Sejak:</strong><br>
                                            <?php echo date('d M Y', strtotime($user['created_at'])); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Terakhir Update:</strong><br>
                                            <?php echo date('d M Y H:i', strtotime($user['updated_at'])); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>ID Pengguna:</strong><br>
                                            #<?php echo str_pad($user['id'], 6, '0', STR_PAD_LEFT); ?>
                                        </div>
                                    </div>
                                </div>
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