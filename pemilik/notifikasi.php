<?php
include '../includes/config.php';
checkRole('pemilik');

$user_id = $_SESSION['user_id'];

// Mark as read if requested
if (isset($_GET['mark_read'])) {
    $notif_id = $_GET['mark_read'];
    $query = "UPDATE notifikasis SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $notif_id, $user_id);
    $stmt->execute();
}

// Mark all as read
if (isset($_POST['mark_all_read'])) {
    $query = "UPDATE notifikasis SET is_read = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    header("Location: notifikasi.php");
    exit();
}

// Get notifications
$query = "SELECT * FROM notifikasis WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();

// Count unread
$unread_query = "SELECT COUNT(*) as unread_count FROM notifikasis WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($unread_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unread_count = $stmt->get_result()->fetch_assoc()['unread_count'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - Vibes Kost</title>
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
        .notification-item {
            border-left: 4px solid #3498db;
            background: white;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .notification-item.unread {
            border-left-color: #e74c3c;
            background: #fff5f5;
        }
        .notification-item.read {
            border-left-color: #95a5a6;
            background: #f8f9fa;
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
                    <a class="nav-link active" href="notifikasi.php">
                        <i class="fas fa-bell"></i> Notifikasi
                        <?php if ($unread_count > 0): ?>
                            <span class="badge bg-danger float-end"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
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
                        <h2><i class="fas fa-bell"></i> Notifikasi</h2>
                        <?php if ($unread_count > 0): ?>
                            <form method="POST" class="d-inline">
                                <button type="submit" name="mark_all_read" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-check-double"></i> Tandai Semua Sudah Dibaca
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Notifications List -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Daftar Notifikasi</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($notifications->num_rows > 0): ?>
                                <div class="list-group">
                                    <?php while ($notif = $notifications->fetch_assoc()): ?>
                                        <div class="list-group-item notification-item <?php echo $notif['is_read'] ? 'read' : 'unread'; ?>">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo $notif['judul']; ?></h6>
                                                <small><?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?></small>
                                            </div>
                                            <p class="mb-1"><?php echo $notif['pesan']; ?></p>
                                            <?php if (!$notif['is_read']): ?>
                                                <div class="mt-2">
                                                    <a href="?mark_read=<?php echo $notif['id']; ?>" class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-check"></i> Tandai Sudah Dibaca
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center">Tidak ada notifikasi</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>