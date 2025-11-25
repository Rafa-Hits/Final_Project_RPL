<?php
include '../includes/config.php';
checkRole('admin');

// Handle backup action
if (isset($_POST['backup'])) {
    $backup_file = 'backup/vibeskost_backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    // Get all table names
    $tables = array();
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    $sql_script = "";
    
    foreach ($tables as $table) {
        // Drop table if exists
        $sql_script .= "DROP TABLE IF EXISTS `$table`;\n";
        
        // Create table structure
        $create_table = $conn->query("SHOW CREATE TABLE `$table`");
        $row = $create_table->fetch_row();
        $sql_script .= $row[1] . ";\n\n";
        
        // Insert data
        $result = $conn->query("SELECT * FROM `$table`");
        if ($result->num_rows > 0) {
            $sql_script .= "INSERT INTO `$table` VALUES\n";
            $rows = array();
            while ($row = $result->fetch_row()) {
                $values = array_map(array($conn, 'real_escape_string'), $row);
                $rows[] = "('" . implode("','", $values) . "')";
            }
            $sql_script .= implode(",\n", $rows) . ";\n\n";
        }
    }
    
    // Save to file
    if (!is_dir('backup')) {
        mkdir('backup', 0777, true);
    }
    
    if (file_put_contents($backup_file, $sql_script)) {
        $_SESSION['success'] = "Backup berhasil dibuat: " . basename($backup_file);
    } else {
        $_SESSION['error'] = "Gagal membuat backup";
    }
    
    header("Location: backup.php");
    exit();
}

// Handle restore action
if (isset($_POST['restore']) && isset($_FILES['backup_file'])) {
    $backup_file = $_FILES['backup_file']['tmp_name'];
    
    if ($backup_file) {
        $sql_script = file_get_contents($backup_file);
        
        // Execute SQL script
        $conn->multi_query($sql_script);
        
        // Clear any remaining results
        while ($conn->more_results()) {
            $conn->next_result();
        }
        
        $_SESSION['success'] = "Restore berhasil dilakukan";
    } else {
        $_SESSION['error'] = "File backup tidak valid";
    }
    
    header("Location: backup.php");
    exit();
}

// Get list of backup files
$backup_files = array();
if (is_dir('backup')) {
    $files = scandir('backup');
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backup_files[] = $file;
        }
    }
    rsort($backup_files); // Sort by newest first
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Data - Admin Vibes Kost</title>
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
        .backup-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
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
                    <a class="nav-link active" href="backup.php">
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
                        <h2><i class="fas fa-database"></i> Backup & Restore Data</h2>
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

                    <!-- Backup Section -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="backup-card">
                                <h4><i class="fas fa-download text-success"></i> Backup Database</h4>
                                <p class="text-muted">Buat cadangan seluruh data sistem ke file SQL.</p>
                                <form method="POST">
                                    <button type="submit" name="backup" class="btn btn-success">
                                        <i class="fas fa-download"></i> Buat Backup Sekarang
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="backup-card">
                                <h4><i class="fas fa-upload text-primary"></i> Restore Database</h4>
                                <p class="text-muted">Pulihkan data dari file backup SQL.</p>
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <input type="file" name="backup_file" class="form-control" accept=".sql" required>
                                    </div>
                                    <button type="submit" name="restore" class="btn btn-primary" onclick="return confirm('PERINGATAN: Restore akan mengganti semua data saat ini. Yakin ingin melanjutkan?')">
                                        <i class="fas fa-upload"></i> Restore dari File
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Backup Files List -->
                    <div class="card mt-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-history"></i> File Backup Tersedia</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($backup_files)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nama File</th>
                                                <th>Ukuran</th>
                                                <th>Tanggal Dibuat</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($backup_files as $file): 
                                                $file_path = 'backup/' . $file;
                                                $file_size = filesize($file_path);
                                                $file_date = date('d M Y H:i:s', filemtime($file_path));
                                            ?>
                                            <tr>
                                                <td><?php echo $file; ?></td>
                                                <td><?php echo round($file_size / 1024, 2); ?> KB</td>
                                                <td><?php echo $file_date; ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="<?php echo $file_path; ?>" class="btn btn-success btn-sm" download>
                                                            <i class="fas fa-download"></i> Download
                                                        </a>
                                                        <a href="<?php echo $file_path; ?>" class="btn btn-info btn-sm" target="_blank">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        <a href="backup.php?delete=<?php echo urlencode($file); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus file backup ini?')">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-database fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Belum ada file backup</h5>
                                    <p class="text-muted">Buat backup pertama Anda dengan menekan tombol "Buat Backup Sekarang"</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Database Info -->
                    <div class="card mt-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informasi Database</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php
                                // Get database size
                                $db_size_query = "
                                    SELECT 
                                        table_schema as db_name,
                                        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as db_size_mb,
                                        COUNT(*) as table_count
                                    FROM information_schema.tables 
                                    WHERE table_schema = '" . DB_NAME . "'
                                    GROUP BY table_schema
                                ";
                                $db_info = $conn->query($db_size_query)->fetch_assoc();
                                
                                // Get table counts
                                $table_counts = array();
                                $tables_result = $conn->query("SHOW TABLES");
                                while ($table = $tables_result->fetch_row()) {
                                    $table_name = $table[0];
                                    $count_result = $conn->query("SELECT COUNT(*) as count FROM `$table_name`");
                                    $table_counts[$table_name] = $count_result->fetch_assoc()['count'];
                                }
                                ?>
                                
                                <div class="col-md-6">
                                    <h6>Statistik Database</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>Nama Database:</strong> <?php echo DB_NAME; ?></li>
                                        <li><strong>Ukuran Database:</strong> <?php echo $db_info['db_size_mb'] ?? '0'; ?> MB</li>
                                        <li><strong>Jumlah Tabel:</strong> <?php echo $db_info['table_count'] ?? '0'; ?></li>
                                        <li><strong>Server:</strong> <?php echo DB_HOST; ?></li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Jumlah Data per Tabel</h6>
                                    <ul class="list-unstyled">
                                        <?php foreach ($table_counts as $table => $count): ?>
                                            <li><strong><?php echo $table; ?>:</strong> <?php echo $count; ?> records</li>
                                        <?php endforeach; ?>
                                    </ul>
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