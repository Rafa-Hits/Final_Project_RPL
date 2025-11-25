<?php
include '../includes/config.php';
checkRole('pemilik');

$user_id = $_SESSION['user_id'];

// Get report data
$report_query = "SELECT 
    COUNT(DISTINCT k.id) as total_kost,
    COUNT(DISTINCT km.id) as total_kamar,
    COUNT(DISTINCT CASE WHEN km.status = 'tersedia' THEN km.id END) as kamar_tersedia,
    COUNT(DISTINCT CASE WHEN km.status = 'dipesan' THEN km.id END) as kamar_dipesan,
    COUNT(DISTINCT CASE WHEN km.status = 'ditempati' THEN km.id END) as kamar_ditempati,
    COUNT(DISTINCT p.id) as total_pemesanan,
    COUNT(DISTINCT CASE WHEN p.status = 'menunggu' THEN p.id END) as pemesanan_menunggu,
    COUNT(DISTINCT CASE WHEN p.status = 'dikonfirmasi' THEN p.id END) as pemesanan_dikonfirmasi,
    COUNT(DISTINCT CASE WHEN p.status = 'selesai' THEN p.id END) as pemesanan_selesai,
    COUNT(DISTINCT pb.id) as total_pembayaran,
    COUNT(DISTINCT CASE WHEN pb.status = 'lunas' THEN pb.id END) as pembayaran_lunas,
    COUNT(DISTINCT CASE WHEN pb.status = 'ditolak' THEN pb.id END) as pembayaran_ditolak,
    COALESCE(SUM(CASE WHEN pb.status = 'lunas' THEN pb.nominal ELSE 0 END), 0) as total_pendapatan
FROM users u
LEFT JOIN kost k ON u.id = k.pemilik_id
LEFT JOIN kamars km ON k.id = km.kost_id
LEFT JOIN pemesanans p ON km.id = p.kamar_id
LEFT JOIN pembayarans pb ON p.id = pb.pemesanan_id
WHERE u.id = ?";

$stmt = $conn->prepare($report_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

// Get monthly income
$monthly_query = "SELECT 
    YEAR(pb.updated_at) as tahun,
    MONTH(pb.updated_at) as bulan,
    SUM(pb.nominal) as pendapatan
FROM pembayarans pb
JOIN pemesanans p ON pb.pemesanan_id = p.id
JOIN kamars km ON p.kamar_id = km.id
JOIN kost k ON km.kost_id = k.id
WHERE k.pemilik_id = ? AND pb.status = 'lunas'
GROUP BY YEAR(pb.updated_at), MONTH(pb.updated_at)
ORDER BY tahun DESC, bulan DESC
LIMIT 6";

$stmt = $conn->prepare($monthly_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$monthly_income = $stmt->get_result();

// Get recent transactions
$transactions_query = "SELECT 
    pb.kode_pembayaran,
    u.nama as penyewa_nama,
    k.nama_kost,
    km.nomor_kamar,
    pb.nominal,
    pb.status,
    p.tanggal_masuk,
    p.tanggal_keluar,
    pb.updated_at
FROM pembayarans pb
JOIN pemesanans p ON pb.pemesanan_id = p.id
JOIN users u ON p.penyewa_id = u.id
JOIN kamars km ON p.kamar_id = km.id
JOIN kost k ON km.kost_id = k.id
WHERE k.pemilik_id = ? AND pb.status = 'lunas'
ORDER BY pb.updated_at DESC
LIMIT 10";

$stmt = $conn->prepare($transactions_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$transactions = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Vibes Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
                    <a class="nav-link active" href="laporan.php">
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
                    <h2 class="mb-4"><i class="fas fa-chart-bar"></i> Laporan & Statistik</h2>
                    
                    <!-- Summary Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stat-card text-center">
                                <i class="fas fa-home fa-2x text-primary mb-2"></i>
                                <h4><?php echo $report['total_kost']; ?></h4>
                                <p class="text-muted">Total Kost</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card text-center">
                                <i class="fas fa-bed fa-2x text-success mb-2"></i>
                                <h4><?php echo $report['total_kamar']; ?></h4>
                                <p class="text-muted">Total Kamar</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card text-center">
                                <i class="fas fa-credit-card fa-2x text-warning mb-2"></i>
                                <h4><?php echo $report['total_pembayaran']; ?></h4>
                                <p class="text-muted">Total Transaksi</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card text-center">
                                <i class="fas fa-money-bill-wave fa-2x text-info mb-2"></i>
                                <h4>Rp <?php echo number_format($report['total_pendapatan'], 0, ',', '.'); ?></h4>
                                <p class="text-muted">Total Pendapatan</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Kamar Status Chart -->
                        <div class="col-md-6 mb-4">
                            <div class="chart-container">
                                <h5 class="mb-3">Status Kamar</h5>
                                <canvas id="kamarChart" width="400" height="300"></canvas>
                            </div>
                        </div>
                        
                        <!-- Pemesanan Status Chart -->
                        <div class="col-md-6 mb-4">
                            <div class="chart-container">
                                <h5 class="mb-3">Status Pemesanan</h5>
                                <canvas id="pemesananChart" width="400" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Monthly Income -->
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="chart-container">
                                <h5 class="mb-3">Pendapatan 6 Bulan Terakhir</h5>
                                <canvas id="incomeChart" width="400" height="150"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Transactions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="chart-container">
                                <h5 class="mb-3">Transaksi Terbaru</h5>
                                <?php if ($transactions->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Kode Bayar</th>
                                                    <th>Penyewa</th>
                                                    <th>Kost & Kamar</th>
                                                    <th>Tanggal Masuk</th>
                                                    <th>Tanggal Keluar</th>
                                                    <th>Nominal</th>
                                                    <th>Tanggal Bayar</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($transaction = $transactions->fetch_assoc()): 
                                                    // Calculate checkout date if not set
                                                    $checkout_date = $transaction['tanggal_keluar'];
                                                    if (!$checkout_date && $transaction['tanggal_masuk']) {
                                                        // Assuming duration is calculated based on total_biaya / harga_per_bulan
                                                        $duration_query = "SELECT durasi_sewa FROM pemesanans WHERE id = (
                                                            SELECT pemesanan_id FROM pembayarans WHERE kode_pembayaran = ?
                                                        )";
                                                        $stmt_duration = $conn->prepare($duration_query);
                                                        $stmt_duration->bind_param("s", $transaction['kode_pembayaran']);
                                                        $stmt_duration->execute();
                                                        $duration_result = $stmt_duration->get_result();
                                                        
                                                        if ($duration_result->num_rows > 0) {
                                                            $duration_data = $duration_result->fetch_assoc();
                                                            $checkout_date = date('Y-m-d', strtotime($transaction['tanggal_masuk'] . ' + ' . $duration_data['durasi_sewa'] . ' months'));
                                                        }
                                                    }
                                                ?>
                                                    <tr>
                                                        <td><?php echo $transaction['kode_pembayaran']; ?></td>
                                                        <td><?php echo $transaction['penyewa_nama']; ?></td>
                                                        <td><?php echo $transaction['nama_kost']; ?> - Kamar <?php echo $transaction['nomor_kamar']; ?></td>
                                                        <td>
                                                            <?php if ($transaction['tanggal_masuk']): ?>
                                                                <?php echo date('d/m/Y', strtotime($transaction['tanggal_masuk'])); ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($checkout_date): ?>
                                                                <?php echo date('d/m/Y', strtotime($checkout_date)); ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>Rp <?php echo number_format($transaction['nominal'], 0, ',', '.'); ?></td>
                                                        <td><?php echo date('d/m/Y H:i', strtotime($transaction['updated_at'])); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center">Belum ada transaksi</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Kamar Status Chart
        const kamarCtx = document.getElementById('kamarChart').getContext('2d');
        const kamarChart = new Chart(kamarCtx, {
            type: 'doughnut',
            data: {
                labels: ['Tersedia', 'Dipesan', 'Ditempati'],
                datasets: [{
                    data: [
                        <?php echo $report['kamar_tersedia']; ?>,
                        <?php echo $report['kamar_dipesan']; ?>,
                        <?php echo $report['kamar_ditempati']; ?>
                    ],
                    backgroundColor: [
                        '#27ae60',
                        '#f39c12',
                        '#e74c3c'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Pemesanan Status Chart
        const pemesananCtx = document.getElementById('pemesananChart').getContext('2d');
        const pemesananChart = new Chart(pemesananCtx, {
            type: 'pie',
            data: {
                labels: ['Menunggu', 'Dikonfirmasi', 'Selesai'],
                datasets: [{
                    data: [
                        <?php echo $report['pemesanan_menunggu']; ?>,
                        <?php echo $report['pemesanan_dikonfirmasi']; ?>,
                        <?php echo $report['pemesanan_selesai']; ?>
                    ],
                    backgroundColor: [
                        '#f39c12',
                        '#3498db',
                        '#27ae60'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Monthly Income Chart
        const incomeCtx = document.getElementById('incomeChart').getContext('2d');
        const incomeChart = new Chart(incomeCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php
                    $months = [];
                    $incomes = [];
                    while ($month = $monthly_income->fetch_assoc()) {
                        $monthName = date('M Y', mktime(0, 0, 0, $month['bulan'], 1, $month['tahun']));
                        echo "'" . $monthName . "',";
                        $incomes[] = $month['pendapatan'];
                    }
                    ?>
                ],
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: [<?php echo implode(',', $incomes); ?>],
                    backgroundColor: '#3498db',
                    borderColor: '#2980b9',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>