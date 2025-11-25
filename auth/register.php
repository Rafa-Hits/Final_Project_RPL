<?php
include '../includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectBasedOnRole();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $conn->real_escape_string($_POST['nama']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $conn->real_escape_string($_POST['role']);
    $no_telepon = $conn->real_escape_string($_POST['no_telepon']);
    $alamat = $conn->real_escape_string($_POST['alamat']);

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

    if (!in_array($role, ['penyewa', 'pemilik'])) {
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
            $insert_query = "INSERT INTO users (nama, email, password, role, no_telepon, alamat, foto_profil) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("sssssss", $nama, $email, $hashed_password, $role, $no_telepon, $alamat, $foto_profil);

            if ($stmt->execute()) {
                $_SESSION['success'] = "Registrasi berhasil! Silakan login.";
                header("Location: login.php");
                exit();
            } else {
                $errors[] = "Terjadi kesalahan saat registrasi. Silakan coba lagi.";
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
    <title>Register - Vibes Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .register-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            padding: 20px 0;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .brand-text {
            color: #667eea;
            font-weight: bold;
        }
        .form-section {
            padding: 40px;
        }
        .role-option {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 10px;
        }
        .role-option:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        .role-option.selected {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }
        .role-icon {
            font-size: 2rem;
            margin-bottom: 10px;
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
    <div class="register-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-10 col-lg-8">
                    <div class="register-card">
                        <div class="row g-0">
                            <!-- Left Side - Form -->
                            <div class="col-md-7">
                                <div class="form-section">
                                    <div class="text-center mb-4">
                                        <h2 class="brand-text">Daftar Akun Baru</h2>
                                        <p class="text-muted">Bergabung dengan Vibes Kost</p>
                                    </div>
                                    
                                    <?php if (!empty($errors)): ?>
                                        <div class="alert alert-danger">
                                            <ul class="mb-0">
                                                <?php foreach ($errors as $error): ?>
                                                    <li><?php echo $error; ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" enctype="multipart/form-data" id="registerForm">
                                        <!-- Role Selection -->
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">Daftar Sebagai:</label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="role-option" data-role="penyewa">
                                                        <div class="role-icon">
                                                            <i class="fas fa-user"></i>
                                                        </div>
                                                        <h6>Penyewa</h6>
                                                        <small class="text-muted">Mencari dan memesan kost</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="role-option" data-role="pemilik">
                                                        <div class="role-icon">
                                                            <i class="fas fa-user-tie"></i>
                                                        </div>
                                                        <h6>Pemilik Kost</h6>
                                                        <small class="text-muted">Mengelola kost dan kamar</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="role" id="selectedRole" required>
                                        </div>

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

                                        <!-- Personal Information -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Nama Lengkap *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                        <input type="text" class="form-control" name="nama" 
                                                               value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>" 
                                                               required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Email *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                                        <input type="email" class="form-control" name="email" 
                                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                                               required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Nomor Telepon *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                                        <input type="tel" class="form-control" name="no_telepon" 
                                                               value="<?php echo isset($_POST['no_telepon']) ? htmlspecialchars($_POST['no_telepon']) : ''; ?>" 
                                                               required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
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
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Alamat Lengkap *</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                                <textarea class="form-control" name="alamat" rows="3" 
                                                          required><?php echo isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : ''; ?></textarea>
                                            </div>
                                        </div>

                                        <div class="mb-4">
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

                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="agreeTerms" required>
                                            <label class="form-check-label" for="agreeTerms">
                                                Saya menyetujui <a href="../auth/terms.php" class="text-primary">Syarat dan Ketentuan</a> 
                                                serta <a href="../auth/privacy.php" class="text-primary">Kebijakan Privasi</a>
                                            </label>
                                        </div>

                                        <button type="submit" class="btn btn-primary w-100 mb-3 py-2">
                                            <i class="fas fa-user-plus"></i> Daftar Sekarang
                                        </button>

                                        <div class="text-center">
                                            <p class="text-muted mb-0">
                                                Sudah punya akun? 
                                                <a href="login.php" class="text-primary">Login di sini</a>
                                            </p>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Right Side - Info -->
                            <div class="col-md-5 bg-primary text-white">
                                <div class="h-100 d-flex flex-column justify-content-center p-5">
                                    <div class="text-center">
                                        <i class="fas fa-home fa-4x mb-4 opacity-75"></i>
                                        <h3>Vibes Kost</h3>
                                        <p class="mb-4">Sistem Informasi Kost Terpercaya di Mendalo - Sungai Duren</p>
                                    </div>

                                    <div class="features-list">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="fas fa-check-circle me-3 opacity-75"></i>
                                            <span>Cari kost dengan mudah</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="fas fa-check-circle me-3 opacity-75"></i>
                                            <span>Bayar online aman</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="fas fa-check-circle me-3 opacity-75"></i>
                                            <span>Notifikasi real-time</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="fas fa-check-circle me-3 opacity-75"></i>
                                            <span>Verifikasi terpercaya</span>
                                        </div>
                                    </div>

                                    <div class="mt-4 text-center">
                                        <small class="opacity-75">
                                            Bergabung dengan <strong>500+</strong> pengguna terdaftar
                                        </small>
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
        // Role Selection
        document.querySelectorAll('.role-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                document.querySelectorAll('.role-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Set hidden input value
                document.getElementById('selectedRole').value = this.dataset.role;
            });
        });

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
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const role = document.getElementById('selectedRole').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const agreeTerms = document.getElementById('agreeTerms').checked;

            if (!role) {
                e.preventDefault();
                alert('Pilih role terlebih dahulu!');
                return;
            }

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

            if (!agreeTerms) {
                e.preventDefault();
                alert('Anda harus menyetujui syarat dan ketentuan!');
                return;
            }
        });

        // Auto-select first role option
        document.addEventListener('DOMContentLoaded', function() {
            const firstRoleOption = document.querySelector('.role-option');
            if (firstRoleOption) {
                firstRoleOption.click();
            }
        });
    </script>
</body>
</html>