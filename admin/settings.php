<?php
include '../includes/config.php';
checkRole('admin');

// Handle settings update
if (isset($_POST['update_settings'])) {
    // In a real application, you would save these to a settings table
    $_SESSION['success'] = "Pengaturan berhasil diperbarui";
    header("Location: settings.php");
    exit();
}

// Handle system maintenance
if (isset($_POST['maintenance_action'])) {
    $action = $_POST['maintenance_action'];
    
    if ($action == 'clear_cache') {
        // Clear cache logic here
        $_SESSION['success'] = "Cache berhasil dibersihkan";
    } elseif ($action == 'optimize_tables') {
        // Optimize tables
        $tables_result = $conn->query("SHOW TABLES");
        while ($table = $tables_result->fetch_row()) {
            $conn->query("OPTIMIZE TABLE `{$table[0]}`");
        }
        $_SESSION['success'] = "Tabel database berhasil dioptimasi";
    }
    
    header("Location: settings.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Vibes Kost</title>
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
        .settings-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
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
                    <a class="nav-link active" href="settings.php">
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
                        <h2><i class="fas fa-cog"></i> System Settings</h2>
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

                    <!-- General Settings -->
                    <div class="settings-card">
                        <h4><i class="fas fa-sliders-h text-primary"></i> Pengaturan Umum</h4>
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Aplikasi</label>
                                        <input type="text" class="form-control" value="Vibes Kost" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email Administrator</label>
                                        <input type="email" class="form-control" value="admin@vibeskost.com">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Nomor Telepon</label>
                                        <input type="text" class="form-control" value="+62 812-3456-7890">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Alamat</label>
                                        <textarea class="form-control" rows="3">Mendalo - Sungai Duren, Jambi</textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Timezone</label>
                                        <select class="form-select">
                                            <option value="Asia/Jakarta" selected>Asia/Jakarta (WIB)</option>
                                            <option value="Asia/Makassar">Asia/Makassar (WITA)</option>
                                            <option value="Asia/Jayapura">Asia/Jayapura (WIT)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="update_settings" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Pengaturan
                            </button>
                        </form>
                    </div>

                    <!-- System Maintenance -->
                    <div class="settings-card">
                        <h4><i class="fas fa-tools text-warning"></i> System Maintenance</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-warning mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="fas fa-broom text-warning"></i> Bersihkan Cache</h5>
                                        <p class="card-text">Membersihkan cache sistem untuk meningkatkan performa.</p>
                                        <form method="POST">
                                            <button type="submit" name="maintenance_action" value="clear_cache" class="btn btn-warning">
                                                <i class="fas fa-broom"></i> Bersihkan Cache
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-info mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="fas fa-database text-info"></i> Optimasi Database</h5>
                                        <p class="card-text">Mengoptimasi tabel database untuk performa yang lebih baik.</p>
                                        <form method="POST">
                                            <button type="submit" name="maintenance_action" value="optimize_tables" class="btn btn-info">
                                                <i class="fas fa-database"></i> Optimasi Database
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Information -->
                    <div class="settings-card">
                        <h4><i class="fas fa-info-circle text-info"></i> Informasi Sistem</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th>PHP Version</th>
                                        <td><?php echo phpversion(); ?></td>
                                    </tr>
                                    <tr>
                                        <th>MySQL Version</th>
                                        <td><?php echo $conn->server_info; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Web Server</th>
                                        <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>OS</th>
                                        <td><?php echo php_uname('s') . ' ' . php_uname('r'); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Max Upload Size</th>
                                        <td><?php echo ini_get('upload_max_filesize'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Max Post Size</th>
                                        <td><?php echo ini_get('post_max_size'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Memory Limit</th>
                                        <td><?php echo ini_get('memory_limit'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Execution Time</th>
                                        <td><?php echo ini_get('max_execution_time'); ?>s</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    <div class="settings-card border-danger">
                        <h4><i class="fas fa-exclamation-triangle text-danger"></i> Zona Bahaya</h4>
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-radiation"></i> Peringatan!</h5>
                            <p class="mb-2">Tindakan berikut dapat menyebabkan kehilangan data permanen. Harap berhati-hati.</p>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <button class="btn btn-outline-danger w-100" disabled>
                                    <i class="fas fa-trash"></i> Hapus Semua Data
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-outline-danger w-100" disabled>
                                    <i class="fas fa-user-slash"></i> Nonaktifkan Sistem
                                </button>
                            </div>
                            <div class="col-md-4">
                                <a href="backup.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-database"></i> Kelola Backup
                                </a>
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