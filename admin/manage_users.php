<?php
include '../includes/config.php';
checkRole('admin');

// Handle user actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    switch ($_GET['action']) {
        case 'activate':
            $conn->query("UPDATE users SET is_active = 1 WHERE id = $user_id");
            $_SESSION['success'] = "User berhasil diaktifkan";
            break;
        case 'deactivate':
            $conn->query("UPDATE users SET is_active = 0 WHERE id = $user_id");
            $_SESSION['success'] = "User berhasil dinonaktifkan";
            break;
        case 'delete':
            $conn->query("DELETE FROM users WHERE id = $user_id");
            $_SESSION['success'] = "User berhasil dihapus";
            break;
    }
    header("Location: manage_users.php");
    exit();
}

// Get all users
$users_query = "SELECT * FROM users ORDER BY created_at DESC";
$users = $conn->query($users_query);

// Get user statistics
$user_stats_query = "
    SELECT 
        role,
        COUNT(*) as total,
        COUNT(CASE WHEN is_active = 1 THEN 1 END) as active
    FROM users 
    GROUP BY role
";
$user_stats = $conn->query($user_stats_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Vibes Kost</title>
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
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(231, 76, 60, 0.1);
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
                    <a class="nav-link active" href="manage_users.php">
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
                        <h2><i class="fas fa-users"></i> Manage Users</h2>
                        <div>
                            <a href="add_user.php" class="btn btn-success me-2">
                                <i class="fas fa-user-plus"></i> Tambah User
                            </a>
                            <a href="dashboard.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                            </a>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="row mb-4">
                        <?php while($stat = $user_stats->fetch_assoc()): ?>
                        <div class="col-md-4 mb-3">
                            <div class="stat-card" style="border-left: 4px solid <?php 
                                switch($stat['role']) {
                                    case 'admin': echo '#e74c3c'; break;
                                    case 'pemilik': echo '#27ae60'; break;
                                    case 'penyewa': echo '#3498db'; break;
                                }
                            ?>;">
                                <h5 class="text-<?php 
                                    switch($stat['role']) {
                                        case 'admin': echo 'danger'; break;
                                        case 'pemilik': echo 'success'; break;
                                        case 'penyewa': echo 'primary'; break;
                                    }
                                ?>"><?php echo ucfirst($stat['role']); ?></h5>
                                <h3><?php echo $stat['total']; ?></h3>
                                <small class="text-muted"><?php echo $stat['active']; ?> aktif</small>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- Messages -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Users Table -->
                    <div class="card">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-list"></i> Daftar Pengguna</h5>
                            <span class="badge bg-light text-dark">Total: <?php echo $users->num_rows; ?> users</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Nama</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Telepon</th>
                                            <th>Status</th>
                                            <th>Tanggal Daftar</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        while($user = $users->fetch_assoc()): 
                                        ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="../uploads/profil/<?php echo $user['foto_profil']; ?>" 
                                                         class="rounded-circle me-2" 
                                                         width="40" 
                                                         height="40"
                                                         alt="Profile"
                                                         onerror="this.src='https://via.placeholder.com/40'">
                                                    <?php echo $user['nama']; ?>
                                                </div>
                                            </td>
                                            <td><?php echo $user['email']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    switch($user['role']) {
                                                        case 'admin': echo 'danger'; break;
                                                        case 'pemilik': echo 'success'; break;
                                                        case 'penyewa': echo 'primary'; break;
                                                    }
                                                ?>"><?php echo ucfirst($user['role']); ?></span>
                                            </td>
                                            <td><?php echo $user['no_telepon'] ?: '-'; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $user['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <?php if ($user['is_active']): ?>
                                                        <a href="manage_users.php?action=deactivate&id=<?php echo $user['id']; ?>" 
                                                           class="btn btn-warning btn-sm" 
                                                           title="Nonaktifkan">
                                                            <i class="fas fa-pause"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="manage_users.php?action=activate&id=<?php echo $user['id']; ?>" 
                                                           class="btn btn-success btn-sm" 
                                                           title="Aktifkan">
                                                            <i class="fas fa-play"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="manage_users.php?action=delete&id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-danger btn-sm" 
                                                       title="Hapus"
                                                       onclick="return confirm('Yakin ingin menghapus user ini?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
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