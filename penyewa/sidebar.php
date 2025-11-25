<?php
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get unread notifications count
$unread_query = "SELECT COUNT(*) as unread FROM notifikasis WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($unread_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unread_count = $stmt->get_result()->fetch_assoc()['unread'];
?>

<!-- Sidebar -->
<div class="col-md-3 col-lg-2 sidebar p-0">
    <div class="p-4">
        <h4 class="text-center mb-4">Vibes Kost</h4>
        <div class="text-center mb-4">
            <img src="../uploads/profil/<?php echo $user['foto_profil']; ?>" 
                 class="rounded-circle" 
                 width="80" 
                 height="80"
                 alt="Profile"
                 onerror="this.src='https://via.placeholder.com/80'">
            <h6 class="mt-2"><?php echo $user['nama']; ?></h6>
            <small class="text-muted">Penyewa</small>
        </div>
    </div>
    
    <nav class="nav flex-column">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
           href="dashboard.php">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pemesanan.php' ? 'active' : ''; ?>" 
           href="pemesanan.php">
            <i class="fas fa-bed"></i> Pemesanan
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pembayaran.php' ? 'active' : ''; ?>" 
           href="pembayaran.php">
            <i class="fas fa-credit-card"></i> Pembayaran
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'riwayat.php' ? 'active' : ''; ?>" 
           href="riwayat.php">
            <i class="fas fa-history"></i> Riwayat
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifikasi.php' ? 'active' : ''; ?>" 
           href="notifikasi.php">
            <i class="fas fa-bell"></i> Notifikasi
            <?php if ($unread_count > 0): ?>
                <span class="badge bg-danger float-end"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" 
           href="profile.php">
            <i class="fas fa-user"></i> Profile
        </a>
        <a class="nav-link" href="../auth/logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</div>