<?php
include '../includes/config.php';
checkRole('penyewa');

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Get kost details if kost_id is provided
$kost_id = isset($_GET['kost_id']) ? intval($_GET['kost_id']) : 0;
$kost = null;
$available_rooms = [];

if ($kost_id) {
    // Get kost details
    $kost_query = "SELECT k.*, u.nama as pemilik_nama, u.no_telepon as pemilik_telepon 
                   FROM kost k 
                   JOIN users u ON k.pemilik_id = u.id 
                   WHERE k.id = ? AND k.status = 'tersedia'";
    $stmt = $conn->prepare($kost_query);
    $stmt->bind_param("i", $kost_id);
    $stmt->execute();
    $kost = $stmt->get_result()->fetch_assoc();

    if ($kost) {
        // Get available rooms for this kost
        $rooms_query = "SELECT * FROM kamars 
                       WHERE kost_id = ? AND status = 'tersedia' 
                       ORDER BY nomor_kamar";
        $stmt = $conn->prepare($rooms_query);
        $stmt->bind_param("i", $kost_id);
        $stmt->execute();
        $available_rooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_pemesanan'])) {
    $kamar_id = intval($_POST['kamar_id']);
    $tanggal_masuk = $conn->real_escape_string($_POST['tanggal_masuk']);
    $durasi_sewa = intval($_POST['durasi_sewa']);
    $catatan_khusus = $conn->real_escape_string($_POST['catatan_khusus']);

    // Validation
    if (empty($kamar_id)) {
        $errors[] = "Pilih kamar terlebih dahulu!";
    }

    if (empty($tanggal_masuk)) {
        $errors[] = "Tanggal masuk harus diisi!";
    }

    if ($durasi_sewa < 1) {
        $errors[] = "Durasi sewa minimal 1 bulan!";
    }

    // Check if room is still available
    $check_room = $conn->prepare("SELECT status FROM kamars WHERE id = ?");
    $check_room->bind_param("i", $kamar_id);
    $check_room->execute();
    $room_status = $check_room->get_result()->fetch_assoc();

    if (!$room_status || $room_status['status'] != 'tersedia') {
        $errors[] = "Kamar tidak tersedia lagi! Silakan pilih kamar lain.";
    }

    if (empty($errors)) {
        // Get room price
        $room_query = $conn->prepare("SELECT harga_per_bulan FROM kamars WHERE id = ?");
        $room_query->bind_param("i", $kamar_id);
        $room_query->execute();
        $room = $room_query->get_result()->fetch_assoc();
        
        $total_biaya = $room['harga_per_bulan'] * $durasi_sewa;

        // Generate unique booking code
        $kode_booking = 'BOOK' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Insert pemesanan using stored procedure
        try {
            $stmt = $conn->prepare("CALL CreatePemesanan(?, ?, ?, ?)");
            $stmt->bind_param("iisi", $user_id, $kamar_id, $tanggal_masuk, $durasi_sewa);
            $stmt->execute();
            $result = $stmt->get_result();
            $booking_result = $result->fetch_assoc();
            
            $kode_booking = $booking_result['kode_booking'];
            
            $success = "Pemesanan berhasil! Kode Booking: <strong>$kode_booking</strong>. Silakan lakukan pembayaran.";
            
            // Reset form
            $kost_id = 0;
            $kost = null;
            $available_rooms = [];
            
        } catch (Exception $e) {
            $errors[] = "Terjadi kesalahan saat memproses pemesanan: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan Kost - Vibes Kost</title>
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
        .kost-card {
            border-left: 4px solid #3498db;
        }
        .room-option {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .room-option:hover {
            border-color: #3498db;
            background: #f8f9ff;
        }
        .room-option.selected {
            border-color: #3498db;
            background: #3498db;
            color: white;
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
                    <a class="nav-link active" href="pemesanan.php">
                        <i class="fas fa-bed"></i> Pemesanan
                    </a>
                    <a class="nav-link" href="pembayaran.php">
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
                        <h2><i class="fas fa-bed"></i> Pemesanan Kost</h2>
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
                            <div class="mt-3">
                                <a href="pembayaran.php" class="btn btn-primary me-2">
                                    <i class="fas fa-credit-card"></i> Bayar Sekarang
                                </a>
                                <a href="pemesanan.php" class="btn btn-outline-primary">
                                    <i class="fas fa-plus"></i> Pesan Kost Lain
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!$kost && !$success): ?>
                    <!-- Kost Selection -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-search"></i> Pilih Kost</h5>
                        </div>
                        <div class="card-body">
                            <p>Silakan pilih kost dari beranda atau daftar kost tersedia.</p>
                            <div class="text-center">
                                <a href="../index.php" class="btn btn-primary">
                                    <i class="fas fa-home"></i> Lihat Kost Tersedia
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($kost && !$success): ?>
                    <!-- Kost Details -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Detail Kost</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <img src="../uploads/kost/<?php echo $kost['foto_kost']; ?>" 
                                         class="img-fluid rounded" 
                                         alt="<?php echo $kost['nama_kost']; ?>"
                                         onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                                </div>
                                <div class="col-md-9">
                                    <h4><?php echo $kost['nama_kost']; ?></h4>
                                    <p class="text-muted">
                                        <i class="fas fa-map-marker-alt"></i> 
                                        <?php echo $kost['alamat']; ?>
                                    </p>
                                    <p><strong>Harga:</strong> Rp <?php echo number_format($kost['harga_per_bulan'], 0, ',', '.'); ?> / bulan</p>
                                    <p><strong>Pemilik:</strong> <?php echo $kost['pemilik_nama']; ?> (<?php echo $kost['pemilik_telepon']; ?>)</p>
                                    <p><strong>Deskripsi:</strong> <?php echo $kost['deskripsi']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Form -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-edit"></i> Form Pemesanan</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="bookingForm">
                                <input type="hidden" name="kost_id" value="<?php echo $kost_id; ?>">
                                
                                <!-- Room Selection -->
                                <div class="mb-4">
                                    <h6>Pilih Kamar</h6>
                                    <?php if (empty($available_rooms)): ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> 
                                            Tidak ada kamar tersedia untuk kost ini.
                                        </div>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($available_rooms as $room): 
                                                $harga_kamar = number_format($room['harga_per_bulan'], 0, ',', '.');
                                            ?>
                                            <div class="col-md-6">
                                                <div class="room-option" data-room-id="<?php echo $room['id']; ?>">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h6 class="mb-1">Kamar <?php echo $room['nomor_kamar']; ?></h6>
                                                            <p class="mb-1"><strong>Rp <?php echo $harga_kamar; ?></strong> / bulan</p>
                                                            <small class="d-block"><?php echo $room['ukuran_kamar']; ?></small>
                                                            <small class="d-block"><?php echo $room['fasilitas_kamar']; ?></small>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="kamar_id" 
                                                                   value="<?php echo $room['id']; ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($available_rooms)): ?>
                                <!-- Booking Details -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal Masuk *</label>
                                            <input type="date" name="tanggal_masuk" class="form-control" 
                                                   min="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Durasi Sewa (Bulan) *</label>
                                            <select name="durasi_sewa" class="form-select" required>
                                                <option value="">Pilih Durasi</option>
                                                <option value="6">6 Bulan</option>
                                                <option value="12">12 Bulan</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Catatan Khusus (Opsional)</label>
                                    <textarea name="catatan_khusus" class="form-control" rows="3" 
                                              placeholder="Contoh: Minta lantai 1, kamar menghadap timur, dll."></textarea>
                                </div>

                                <!-- Summary -->
                                <div class="card bg-light mb-4">
                                    <div class="card-body">
                                        <h6>Ringkasan Pemesanan</h6>
                                        <div id="bookingSummary">
                                            <p class="text-muted">Pilih kamar dan durasi untuk melihat ringkasan</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" name="submit_pemesanan" class="btn btn-success btn-lg">
                                        <i class="fas fa-check"></i> Konfirmasi Pemesanan
                                    </button>
                                </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Room selection
        document.querySelectorAll('.room-option').forEach(option => {
            option.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                
                // Update UI
                document.querySelectorAll('.room-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                
                updateBookingSummary();
            });
        });

        // Update booking summary
        function updateBookingSummary() {
            const selectedRoom = document.querySelector('input[name="kamar_id"]:checked');
            const durasi = document.querySelector('select[name="durasi_sewa"]').value;
            const tanggalMasuk = document.querySelector('input[name="tanggal_masuk"]').value;
            
            if (selectedRoom && durasi && tanggalMasuk) {
                const roomOption = selectedRoom.closest('.room-option');
                const roomNumber = roomOption.querySelector('h6').textContent;
                const roomPrice = roomOption.querySelector('p strong').textContent;
                
                // Calculate total
                const pricePerMonth = parseInt(roomPrice.replace('Rp ', '').replace(/\./g, ''));
                const total = pricePerMonth * parseInt(durasi);
                
                const summary = `
                    <table class="table table-sm">
                        <tr>
                            <td>Kamar:</td>
                            <td>${roomNumber}</td>
                        </tr>
                        <tr>
                            <td>Tanggal Masuk:</td>
                            <td>${new Date(tanggalMasuk).toLocaleDateString('id-ID')}</td>
                        </tr>
                        <tr>
                            <td>Durasi:</td>
                            <td>${durasi} Bulan</td>
                        </tr>
                        <tr>
                            <td>Harga per Bulan:</td>
                            <td>${roomPrice}</td>
                        </tr>
                        <tr class="table-active">
                            <td><strong>Total Biaya:</strong></td>
                            <td><strong>Rp ${total.toLocaleString('id-ID')}</strong></td>
                        </tr>
                    </table>
                `;
                
                document.getElementById('bookingSummary').innerHTML = summary;
            }
        }

        // Event listeners for form changes
        document.querySelectorAll('input[name="kamar_id"], select[name="durasi_sewa"], input[name="tanggal_masuk"]')
            .forEach(element => {
                element.addEventListener('change', updateBookingSummary);
            });

        // Set minimum date to today
        document.querySelector('input[name="tanggal_masuk"]').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>