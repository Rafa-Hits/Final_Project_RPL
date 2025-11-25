<?php
include '../includes/config.php';
checkRole('penyewa');

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Get pending bookings for this user
$bookings_query = "
    SELECT p.*, k.nama_kost, km.nomor_kamar, km.harga_per_bulan,
           pb.id as pembayaran_id, pb.status as status_pembayaran
    FROM pemesanans p
    JOIN kamars km ON p.kamar_id = km.id
    JOIN kost k ON km.kost_id = k.id
    LEFT JOIN pembayarans pb ON p.id = pb.pemesanan_id
    WHERE p.penyewa_id = ? AND p.status IN ('menunggu', 'dikonfirmasi')
    ORDER BY p.created_at DESC
";
$stmt = $conn->prepare($bookings_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_pembayaran'])) {
    $pemesanan_id = intval($_POST['pemesanan_id']);
    $nominal = floatval(str_replace(['.', ','], '', $_POST['nominal']));
    $metode_pembayaran = $conn->real_escape_string($_POST['metode_pembayaran']);
    $nama_bank = $conn->real_escape_string($_POST['nama_bank']);
    $nomor_rekening = $conn->real_escape_string($_POST['nomor_rekening']);
    $atas_nama = $conn->real_escape_string($_POST['atas_nama']);
    $tanggal_transfer = $conn->real_escape_string($_POST['tanggal_transfer']);

    // Validation
    if (empty($pemesanan_id)) {
        $errors[] = "Pilih pemesanan yang akan dibayar!";
    }

    if ($nominal <= 0) {
        $errors[] = "Nominal pembayaran tidak valid!";
    }

    if (empty($metode_pembayaran)) {
        $errors[] = "Pilih metode pembayaran!";
    }

    if ($metode_pembayaran == 'transfer_bank') {
        if (empty($nama_bank) || empty($nomor_rekening) || empty($atas_nama)) {
            $errors[] = "Data bank harus diisi untuk transfer!";
        }
    }

    if (empty($tanggal_transfer)) {
        $errors[] = "Tanggal transfer harus diisi!";
    }

    // Check file upload
    if (!isset($_FILES['bukti_transfer']) || $_FILES['bukti_transfer']['error'] != 0) {
        $errors[] = "Bukti transfer harus diupload!";
    } else {
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        $file_ext = strtolower(pathinfo($_FILES['bukti_transfer']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            $errors[] = "Format file tidak didukung! Hanya JPG, JPEG, PNG, GIF, PDF.";
        }
        
        if ($_FILES['bukti_transfer']['size'] > 2 * 1024 * 1024) { // 2MB
            $errors[] = "Ukuran file terlalu besar! Maksimal 2MB.";
        }
    }

    if (empty($errors)) {
        // Check if payment already exists
        $check_payment = $conn->prepare("SELECT id FROM pembayarans WHERE pemesanan_id = ?");
        $check_payment->bind_param("i", $pemesanan_id);
        $check_payment->execute();
        
        if ($check_payment->get_result()->num_rows > 0) {
            $errors[] = "Pembayaran untuk pemesanan ini sudah ada!";
        } else {
            // Handle file upload
            $upload_dir = '../uploads/bukti_bayar/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = 'payment_' . $pemesanan_id . '_' . time() . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $file_path)) {
                // Generate payment code
                $kode_pembayaran = 'PAY' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

                // Insert payment record
                $insert_query = "
                    INSERT INTO pembayarans (
                        pemesanan_id, kode_pembayaran, bukti_transfer, nominal, 
                        metode_pembayaran, nama_bank, nomor_rekening, atas_nama, tanggal_transfer
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";
                
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param(
                    "issdsssss", 
                    $pemesanan_id, $kode_pembayaran, $file_name, $nominal,
                    $metode_pembayaran, $nama_bank, $nomor_rekening, $atas_nama, $tanggal_transfer
                );

                if ($stmt->execute()) {
                    $success = "Bukti pembayaran berhasil diupload! Kode Pembayaran: <strong>$kode_pembayaran</strong>. Menunggu verifikasi pemilik kost.";
                    
                    // Refresh pending bookings
                    $stmt = $conn->prepare($bookings_query);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $pending_bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                } else {
                    $errors[] = "Terjadi kesalahan saat menyimpan data pembayaran.";
                }
            } else {
                $errors[] = "Gagal mengupload bukti transfer!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Vibes Kost</title>
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
            border-left-color: #3498db;
        }
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .booking-card {
            border-left: 4px solid #f39c12;
            margin-bottom: 15px;
        }
        .payment-method {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .payment-method:hover {
            border-color: #3498db;
        }
        .payment-method.selected {
            border-color: #3498db;
            background: #f8f9ff;
        }
        .bank-info {
            display: none;
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
                        <small class="text-muted">Penyewa</small>
                    </div>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a class="nav-link" href="pemesanan.php">
                        <i class="fas fa-bed"></i> Pemesanan
                    </a>
                    <a class="nav-link active" href="pembayaran.php">
                        <i class="fas fa-credit-card"></i> Pembayaran
                    </a>
                    <a class="nav-link" href="riwayat.php">
                        <i class="fas fa-history"></i> Riwayat
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
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-credit-card"></i> Pembayaran</h2>
                        <a href="dashboard.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                        </a>
                    </div>

                    <!-- Messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Pending Bookings -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0"><i class="fas fa-clock"></i> Pemesanan Menunggu Pembayaran</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($pending_bookings)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Tidak ada pemesanan yang menunggu pembayaran</p>
                                            <a href="pemesanan.php" class="btn btn-primary">Pesan Kost</a>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($pending_bookings as $booking): 
                                            $total_biaya = number_format($booking['total_biaya'], 0, ',', '.');
                                        ?>
                                        <div class="card booking-card">
                                            <div class="card-body">
                                                <h6><?php echo $booking['nama_kost']; ?></h6>
                                                <p class="mb-1">Kamar: <?php echo $booking['nomor_kamar']; ?></p>
                                                <p class="mb-1">Total: Rp <?php echo $total_biaya; ?></p>
                                                <p class="mb-1">
                                                    Status: 
                                                    <span class="badge bg-<?php 
                                                        switch($booking['status_pembayaran']) {
                                                            case 'lunas': echo 'success'; break;
                                                            case 'menunggu': echo 'warning'; break;
                                                            case 'ditolak': echo 'danger'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo $booking['status_pembayaran'] ? ucfirst($booking['status_pembayaran']) : 'Belum Bayar'; ?>
                                                    </span>
                                                </p>
                                                <small class="text-muted">
                                                    Kode: <?php echo $booking['kode_booking']; ?>
                                                </small>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Form -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-upload"></i> Upload Bukti Pembayaran</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($pending_bookings)): ?>
                                    <form method="POST" enctype="multipart/form-data" id="paymentForm">
                                        <!-- Booking Selection -->
                                        <div class="mb-3">
                                            <label class="form-label">Pilih Pemesanan *</label>
                                            <select name="pemesanan_id" class="form-select" required>
                                                <option value="">Pilih Pemesanan</option>
                                                <?php foreach ($pending_bookings as $booking): 
                                                    if (!$booking['pembayaran_id'] || $booking['status_pembayaran'] == 'ditolak'): 
                                                ?>
                                                <option value="<?php echo $booking['id']; ?>" 
                                                        data-total="<?php echo $booking['total_biaya']; ?>">
                                                    <?php echo $booking['kode_booking']; ?> - 
                                                    <?php echo $booking['nama_kost']; ?> - 
                                                    Rp <?php echo number_format($booking['total_biaya'], 0, ',', '.'); ?>
                                                </option>
                                                <?php endif; endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Payment Amount -->
                                        <div class="mb-3">
                                            <label class="form-label">Nominal Pembayaran *</label>
                                            <input type="text" name="nominal" class="form-control" 
                                                   placeholder="Contoh: 1500000" required
                                                   oninput="formatCurrency(this)">
                                            <small class="text-muted">Isi sesuai total yang harus dibayar</small>
                                        </div>

                                        <!-- Payment Method -->
                                        <div class="mb-3">
                                            <label class="form-label">Metode Pembayaran *</label>
                                            <div>
                                                <div class="payment-method" data-method="transfer_bank">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="metode_pembayaran" 
                                                               value="transfer_bank" required>
                                                        <label class="form-check-label">
                                                            <i class="fas fa-university"></i> Transfer Bank
                                                        </label>
                                                    </div>
                                                </div>
                                                
                                                <div class="payment-method" data-method="qris">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="metode_pembayaran" 
                                                               value="qris" required>
                                                        <label class="form-check-label">
                                                            <i class="fas fa-qrcode"></i> QRIS
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Bank Information (shown only for bank transfer) -->
                                        <div id="bankInfo" class="bank-info">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nama Bank *</label>
                                                        <input type="text" name="nama_bank" class="form-control" 
                                                               placeholder="Contoh: BCA, Mandiri, BNI">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nomor Rekening *</label>
                                                        <input type="text" name="nomor_rekening" class="form-control" 
                                                               placeholder="Nomor rekening pengirim">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Atas Nama *</label>
                                                <input type="text" name="atas_nama" class="form-control" 
                                                       placeholder="Nama pemilik rekening">
                                            </div>
                                        </div>

                                        <!-- Transfer Date -->
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal Transfer *</label>
                                            <input type="date" name="tanggal_transfer" class="form-control" 
                                                   max="<?php echo date('Y-m-d'); ?>" required>
                                        </div>

                                        <!-- Proof Upload -->
                                        <div class="mb-4">
                                            <label class="form-label">Bukti Transfer *</label>
                                            <input type="file" name="bukti_transfer" class="form-control" 
                                                   accept=".jpg,.jpeg,.png,.gif,.pdf" required>
                                            <small class="text-muted">Format: JPG, PNG, GIF, PDF (maks. 2MB)</small>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" name="submit_pembayaran" class="btn btn-success btn-lg">
                                                <i class="fas fa-upload"></i> Upload Bukti Pembayaran
                                            </button>
                                        </div>
                                    </form>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Tidak ada pemesanan yang perlu dibayar</p>
                                            <a href="pemesanan.php" class="btn btn-primary">Pesan Kost</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Payment Instructions -->
                            <div class="card mt-4">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Cara Pembayaran</h6>
                                </div>
                                <div class="card-body">
                                    <ol class="small">
                                        <li>Pilih pemesanan yang akan dibayar</li>
                                        <li>Transfer sesuai nominal ke rekening pemilik kost</li>
                                        <li>Upload bukti transfer pada form di atas</li>
                                        <li>Tunggu verifikasi dari pemilik kost (1-2 hari kerja)</li>
                                        <li>Status pembayaran akan diperbarui di dashboard Anda</li>
                                    </ol>
                                    <div class="alert alert-warning small">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <strong>Perhatian:</strong> Pastikan bukti transfer jelas terbaca. 
                                        Pembayaran palsu akan dikenakan sanksi.
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
    <script>
        // Payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                
                // Update UI
                document.querySelectorAll('.payment-method').forEach(m => {
                    m.classList.remove('selected');
                });
                this.classList.add('selected');
                
                // Show/hide bank info
                const bankInfo = document.getElementById('bankInfo');
                if (this.dataset.method === 'transfer_bank') {
                    bankInfo.style.display = 'block';
                    // Make bank fields required
                    bankInfo.querySelectorAll('input').forEach(input => {
                        input.required = true;
                    });
                } else {
                    bankInfo.style.display = 'none';
                    // Remove required from bank fields
                    bankInfo.querySelectorAll('input').forEach(input => {
                        input.required = false;
                    });
                }
            });
        });

        // Auto-fill nominal when booking is selected
        document.querySelector('select[name="pemesanan_id"]').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const total = selectedOption.dataset.total;
                document.querySelector('input[name="nominal"]').value = formatNumber(total);
            }
        });

        // Currency formatting
        function formatCurrency(input) {
            let value = input.value.replace(/\./g, '');
            if (!isNaN(value)) {
                input.value = formatNumber(value);
            }
        }

        function formatNumber(num) {
            return parseInt(num).toLocaleString('id-ID');
        }

        // Set maximum date to today
        document.querySelector('input[name="tanggal_transfer"]').max = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>