<?php
include '../includes/config.php';
checkRole('penyewa');

$user_id = $_SESSION['user_id'];

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $update_query = "UPDATE notifikasis SET is_read = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

// Mark single as read
if (isset($_GET['mark_read'])) {
    $notif_id = $_GET['mark_read'];
    $update_query = "UPDATE notifikasis SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $notif_id, $user_id);
    $stmt->execute();
}

// Delete notification
if (isset($_GET['delete'])) {
    $notif_id = $_GET['delete'];
    $delete_query = "DELETE FROM notifikasis WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("ii", $notif_id, $user_id);
    $stmt->execute();
}

// Get notifications
$query = "SELECT * FROM notifikasis WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();

// Count unread
$unread_query = "SELECT COUNT(*) as unread FROM notifikasis WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($unread_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unread_count = $stmt->get_result()->fetch_assoc()['unread'];
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
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }
        .notification-item.unread {
            background: #f8f9fa;
            border-left-color: #dc3545;
        }
        .notification-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
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
                        <h2>
                            <i class="fas fa-bell"></i> Notifikasi
                            <?php if ($unread_count > 0): ?>
                                <span class="badge bg-danger ms-2"><?php echo $unread_count; ?> baru</span>
                            <?php endif; ?>
                        </h2>
                        <div class="btn-group">
                            <a href="notifikasi.php?mark_all_read=1" class="btn btn-outline-success">
                                <i class="fas fa-check-double"></i> Tandai Semua Dibaca
                            </a>
                            <a href="notifikasi.php?delete_all=1" class="btn btn-outline-danger" 
                               onclick="return confirm('Yakin ingin menghapus semua notifikasi?')">
                                <i class="fas fa-trash"></i> Hapus Semua
                            </a>
                        </div>
                    </div>

                    <!-- Notifications List -->
                    <div class="row">
                        <div class="col-12">
                            <?php if ($notifications->num_rows > 0): ?>
                                <?php while ($notif = $notifications->fetch_assoc()): ?>
                                    <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                                        <div class="d-flex align-items-start">
                                            <div class="notification-icon bg-<?php 
                                                switch($notif['jenis']) {
                                                    case 'pemesanan': echo 'primary'; break;
                                                    case 'pembayaran': echo 'success'; break;
                                                    case 'sistem': echo 'warning'; break;
                                                    case 'promosi': echo 'info'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?> text-white">
                                                <i class="fas fa-<?php 
                                                    switch($notif['jenis']) {
                                                        case 'pemesanan': echo 'bed'; break;
                                                        case 'pembayaran': echo 'credit-card'; break;
                                                        case 'sistem': echo 'cog'; break;
                                                        case 'promosi': echo 'bullhorn'; break;
                                                        default: echo 'bell';
                                                    }
                                                ?>"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <h6 class="mb-1"><?php echo $notif['judul']; ?></h6>
                                                    <small class="text-muted">
                                                        <?php echo date('d M Y H:i', strtotime($notif['created_at'])); ?>
                                                    </small>
                                                </div>
                                                <p class="mb-2"><?php echo $notif['pesan']; ?></p>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if (!$notif['is_read']): ?>
                                                        <a href="notifikasi.php?mark_read=<?php echo $notif['id']; ?>" 
                                                           class="btn btn-outline-success btn-sm">
                                                            <i class="fas fa-check"></i> Tandai Dibaca
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="notifikasi.php?delete=<?php echo $notif['id']; ?>" 
                                                       class="btn btn-outline-danger btn-sm"
                                                       onclick="return confirm('Yakin ingin menghapus notifikasi ini?')">
                                                        <i class="fas fa-trash"></i> Hapus
                                                    </a>
                                                    <?php if ($notif['link']): ?>
                                                        <a href="<?php echo $notif['link']; ?>" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-external-link-alt"></i> Buka
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php if (!$notif['is_read']): ?>
                                                <span class="badge bg-danger notification-badge">Baru</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">Tidak Ada Notifikasi</h4>
                                    <p class="text-muted">Anda belum memiliki notifikasi.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Notification Statistics -->
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h4><?php echo $notifications->num_rows; ?></h4>
                                    <p>Total Notifikasi</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h4><?php echo $unread_count; ?></h4>
                                    <p>Belum Dibaca</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4><?php echo $notifications->num_rows - $unread_count; ?></h4>
                                    <p>Sudah Dibaca</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <?php
                                    $today_count = 0;
                                    $today = date('Y-m-d');
                                    $notifications->data_seek(0); // Reset pointer
                                    while ($notif = $notifications->fetch_assoc()) {
                                        if (date('Y-m-d', strtotime($notif['created_at'])) === $today) {
                                            $today_count++;
                                        }
                                    }
                                    ?>
                                    <h4><?php echo $today_count; ?></h4>
                                    <p>Hari Ini</p>
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
        // Auto refresh notifications every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
        
        // Mark as read when notification is clicked
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const markReadBtn = this.querySelector('a[href*="mark_read"]');
                if (markReadBtn) {
                    window.location.href = markReadBtn.href;
                }
            });
        });
    </script>
</body>
</html>