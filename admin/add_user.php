<?php
include '../includes/config.php';
checkRole('admin');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $conn->real_escape_string($_POST['nama']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $conn->real_escape_string($_POST['role']);
    $no_telepon = $conn->real_escape_string($_POST['no_telepon']);
    $alamat = $conn->real_escape_string($_POST['alamat']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    $errors = [];

    // Check if email already exists
    $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $check_email->store_result();

    if ($check_email->num_rows > 0) {
        $errors[] = "Email sudah terdaftar!";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter!";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Konfirmasi password tidak cocok!";
    }

    if (!in_array($role, ['penyewa', 'pemilik', 'admin'])) {
        $errors[] = "Role tidak valid!";
    }

    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Handle photo upload
        $foto_profil = 'default.jpg';
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0) {
            $upload_dir = '../uploads/profil/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $file_ext = strtolower(pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION));
            
            if (in_array($file_ext, $allowed_types)) {
                if ($_FILES['foto_profil']['size'] <= 2 * 1024 * 1024) { // 2MB max
                    $file_name = 'user_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $file_path = $upload_dir . $file_name;

                    if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $file_path)) {
                        $foto_profil = $file_name;
                    }
                } else {
                    $errors[] = "Ukuran file terlalu besar! Maksimal 2MB.";
                }
            } else {
                $errors[] = "Format file tidak didukung! Hanya JPG, JPEG, PNG, GIF.";
            }
        }

        if (empty($errors)) {
            // Insert user
            $insert_query = "INSERT INTO users (nama, email, password, role, no_telepon, alamat, foto_profil, is_active) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("sssssssi", $nama, $email, $hashed_password, $role, $no_telepon, $alamat, $foto_profil, $is_active);

            if ($stmt->execute()) {
                $_SESSION['success'] = "User berhasil ditambahkan!";
                header("Location: manage_users.php");
                exit();
            } else {
                $errors[] = "Terjadi kesalahan saat menambahkan user. Silakan coba lagi.";
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
    <title>Tambah User - Admin Vibes Kost</title>
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
        .photo-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #667eea;
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
                        <h2><i class="fas fa-user-plus"></i> Tambah User Baru</h2>
                        <a href="manage_users.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left"></i> Kembali ke Manage Users
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

                    <!-- Add User Form -->
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-user-plus"></i> Form Tambah User</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" id="addUserForm">
                                <div class="row">
                                    <!-- Left Column -->
                                    <div class="col-md-6">
                                        <!-- Photo Upload -->
                                        <div class="mb-4 text-center">
                                            <div class="d-flex flex-column align-items-center">
                                                <img id="photoPreview" class="photo-preview mb-3" 
                                                     src="https://via.placeholder.com/120" 
                                                     alt="Preview Foto">
                                                <div>
                                                    <input type="file" name="foto_profil" id="foto_profil" 
                                                           class="form-control d-none" accept="image/*">
                                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                                            onclick="document.getElementById('foto_profil').click()">
                                                        <i class="fas fa-camera"></i> Upload Foto Profil
                                                    </button>
                                                </div>
                                                <small class="text-muted mt-1">Opsional, maksimal 2MB</small>
                                            </div>
                                        </div>

                                        <!-- Role Selection -->
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Role *</label>
                                            <select name="role" class="form-select" required>
                                                <option value="">Pilih Role</option>
                                                <option value="penyewa" <?php echo isset($_POST['role']) && $_POST['role'] == 'penyewa' ? 'selected' : ''; ?>>Penyewa</option>
                                                <option value="pemilik" <?php echo isset($_POST['role']) && $_POST['role'] == 'pemilik' ? 'selected' : ''; ?>>Pemilik Kost</option>
                                                <option value="admin" <?php echo isset($_POST['role']) && $_POST['role'] == 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                            </select>
                                        </div>

                                        <!-- Status -->
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                                                <label class="form-check-label" for="is_active">
                                                    Akun Aktif
                                                </label>
                                            </div>
                                            <small class="text-muted">Nonaktifkan jika user tidak boleh login</small>
                                        </div>
                                    </div>

                                    <!-- Right Column -->
                                    <div class="col-md-6">
                                        <!-- Personal Information -->
                                        <div class="mb-3">
                                            <label class="form-label">Nama Lengkap *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                <input type="text" class="form-control" name="nama" 
                                                       value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>" 
                                                       required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Email *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                                <input type="email" class="form-control" name="email" 
                                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                                       required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Nomor Telepon *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                                <input type="tel" class="form-control" name="no_telepon" 
                                                       value="<?php echo isset($_POST['no_telepon']) ? htmlspecialchars($_POST['no_telepon']) : ''; ?>" 
                                                       required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Password *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                <input type="password" class="form-control" name="password" 
                                                       id="password" required>
                                                <button type="button" class="btn btn-outline-secondary" 
                                                        onclick="togglePassword('password')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Minimal 6 karakter</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Konfirmasi Password *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                <input type="password" class="form-control" name="confirm_password" 
                                                       id="confirm_password" required>
                                                <button type="button" class="btn btn-outline-secondary" 
                                                        onclick="togglePassword('confirm_password')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Address -->
                                <div class="mb-4">
                                    <label class="form-label">Alamat Lengkap *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                        <textarea class="form-control" name="alamat" rows="3" 
                                                  required><?php echo isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : ''; ?></textarea>
                                    </div>
                                </div>

                                <!-- Buttons -->
                                <div class="d-flex justify-content-between">
                                    <a href="manage_users.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Batal
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save"></i> Simpan User
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Photo Preview
        document.getElementById('foto_profil').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('photoPreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        // Toggle Password Visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.parentNode.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Form Validation
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter!');
                return;
            }

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Konfirmasi password tidak cocok!');
                return;
            }
        });
    </script>
</body>
</html>