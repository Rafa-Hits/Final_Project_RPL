-- =============================================
-- CREATE DATABASE AND TABLES
-- =============================================

-- Create database
CREATE DATABASE IF NOT EXISTS Vibekos;
USE Vibekos;

-- Users table with bank account columns
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','pemilik','penyewa') NOT NULL,
    no_telepon VARCHAR(20),
    alamat TEXT,
    -- Bank account fields for pemilik kost
    bank_nama VARCHAR(100),
    bank_nomor VARCHAR(50),
    bank_atas_nama VARCHAR(100),
    foto_profil VARCHAR(255) DEFAULT 'default.jpg',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Kost table
CREATE TABLE kost (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pemilik_id INT NOT NULL,
    nama_kost VARCHAR(100) NOT NULL,
    alamat TEXT NOT NULL,
    deskripsi TEXT,
    harga_per_bulan DECIMAL(12,2) NOT NULL,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    foto_kost VARCHAR(255) DEFAULT 'default.jpg',
    fasilitas TEXT,
    peraturan TEXT,
    status ENUM('tersedia','tidak_tersedia') DEFAULT 'tersedia',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pemilik_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Kamars table
CREATE TABLE kamars (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kost_id INT NOT NULL,
    nomor_kamar VARCHAR(10) NOT NULL,
    ukuran_kamar VARCHAR(20),
    harga_per_bulan DECIMAL(12,2) NOT NULL,
    fasilitas_kamar TEXT,
    deskripsi_kamar TEXT,
    foto_kamar VARCHAR(255),
    status ENUM('tersedia','dipesan','ditempati') DEFAULT 'tersedia',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kost_id) REFERENCES kost(id) ON DELETE CASCADE
);

-- Pemesanan table
CREATE TABLE pemesanans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_booking VARCHAR(20) UNIQUE NOT NULL,
    penyewa_id INT NOT NULL,
    kamar_id INT NOT NULL,
    tanggal_masuk DATE NOT NULL,
    tanggal_keluar DATE,
    durasi_sewa INT NOT NULL COMMENT 'Dalam bulan',
    total_biaya DECIMAL(12,2) NOT NULL,
    catatan_khusus TEXT,
    status ENUM('menunggu','dikonfirmasi','ditolak','selesai','dibatalkan') DEFAULT 'menunggu',
    alasan_penolakan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (penyewa_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kamar_id) REFERENCES kamars(id) ON DELETE CASCADE
);

-- Pembayaran table
CREATE TABLE pembayarans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pemesanan_id INT NOT NULL,
    kode_pembayaran VARCHAR(20) UNIQUE NOT NULL,
    bukti_transfer VARCHAR(255),
    nominal DECIMAL(12,2) NOT NULL,
    metode_pembayaran ENUM('transfer_bank','qris','tunai') DEFAULT 'transfer_bank',
    nama_bank VARCHAR(50),
    nomor_rekening VARCHAR(50),
    atas_nama VARCHAR(100),
    status ENUM('menunggu','lunas','ditolak','kadaluarsa') DEFAULT 'menunggu',
    tanggal_transfer DATE,
    alasan_penolakan TEXT,
    verified_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pemesanan_id) REFERENCES pemesanans(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Notifikasi table
CREATE TABLE notifikasis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    judul VARCHAR(255) NOT NULL,
    pesan TEXT NOT NULL,
    jenis ENUM('pemesanan','pembayaran','sistem','promosi') DEFAULT 'sistem',
    is_read BOOLEAN DEFAULT FALSE,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Reviews table
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    penyewa_id INT NOT NULL,
    kost_id INT NOT NULL,
    pemesanan_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    komentar TEXT,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (penyewa_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kost_id) REFERENCES kost(id) ON DELETE CASCADE,
    FOREIGN KEY (pemesanan_id) REFERENCES pemesanans(id) ON DELETE CASCADE
);

-- Fasilitas table (master data)
CREATE TABLE fasilitas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_fasilitas VARCHAR(100) NOT NULL,
    icon VARCHAR(100),
    kategori ENUM('umum','kamar','kamar_mandi','dapur','lainnya') DEFAULT 'umum',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Kost Fasilitas (many-to-many relationship)
CREATE TABLE kost_fasilitas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kost_id INT NOT NULL,
    fasilitas_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kost_id) REFERENCES kost(id) ON DELETE CASCADE,
    FOREIGN KEY (fasilitas_id) REFERENCES fasilitas(id) ON DELETE CASCADE
);

-- =============================================
-- INSERT DEFAULT DATA
-- =============================================

-- Insert default users with bank account information
INSERT INTO users (nama, email, password, role, no_telepon, alamat, bank_nama, bank_nomor, bank_atas_nama) VALUES 
('Administrator', 'admin@vibeskost.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '081234567890', 'Jl. Admin No. 1, Mendalo', NULL, NULL, NULL),
('Pemilik Kost Sejahtera', 'pemilik@vibeskost.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pemilik', '081234567891', 'Jl. Pemilik No. 2, Sungai Duren', 'Bank BCA', '1234567890', 'Pemilik Kost Sejahtera'),
('Penyewa Contoh', 'penyewa@vibeskost.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'penyewa', '081234567892', 'Jl. Penyewa No. 3, Mendalo', NULL, NULL, NULL),
('Budi Santoso', 'budi@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pemilik', '081234567893', 'Jl. Merdeka No. 45, Sungai Duren', 'Bank Mandiri', '0987654321', 'Budi Santoso'),
('Sari Indah', 'sari@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'penyewa', '081234567894', 'Jl. Flamboyan No. 12, Mendalo', NULL, NULL, NULL);

-- Insert fasilitas master data
INSERT INTO fasilitas (nama_fasilitas, icon, kategori) VALUES 
-- Fasilitas Umum
('WiFi', 'wifi', 'umum'),
('Parkir Motor', 'motorcycle', 'umum'),
('Parkir Mobil', 'car', 'umum'),
('CCTV', 'cctv', 'umum'),
('Security 24 Jam', 'security', 'umum'),
('Laundry', 'laundry', 'umum'),
('Dapur Bersama', 'kitchen', 'umum'),
('Ruang Tamu', 'sofa', 'umum'),
('Taman', 'tree', 'umum'),
('Mesin ATM', 'atm', 'umum'),

-- Fasilitas Kamar
('AC', 'snowflake', 'kamar'),
('Kipas Angin', 'fan', 'kamar'),
('Lemari', 'archive', 'kamar'),
('Kasur', 'bed', 'kamar'),
('Meja Belajar', 'desk', 'kamar'),
('Kursi', 'chair', 'kamar'),
('LED TV', 'tv', 'kamar'),
('Kulkas', 'refrigerator', 'kamar'),

-- Fasilitas Kamar Mandi
('Kamar Mandi Dalam', 'bath', 'kamar_mandi'),
('Kamar Mandi Luar', 'shower', 'kamar_mandi'),
('Water Heater', 'thermometer', 'kamar_mandi'),
('Shower', 'shower-head', 'kamar_mandi'),

-- Fasilitas Dapur
('Kompor', 'stove', 'dapur'),
('Rice Cooker', 'rice', 'dapur'),
('Microwave', 'microwave', 'dapur'),

-- Lainnya
('Listrik Included', 'bolt', 'lainnya'),
('Air Included', 'tint', 'lainnya'),
('Kebersihan Included', 'broom', 'lainnya');

-- Insert sample kost data
INSERT INTO kost (pemilik_id, nama_kost, alamat, deskripsi, harga_per_bulan, latitude, longitude, fasilitas, peraturan) VALUES 
(2, 'Kost Sejahtera 1', 'Jl. Mendalo Raya No. 123, Mendalo', 'Kost nyaman dengan fasilitas lengkap, dekat kampus dan pusat perbelanjaan. Lingkungan aman dan bersih.', 1500000.00, -1.610000, 103.550000, 'WiFi, AC, Kamar Mandi Dalam, Laundry, Parkir Motor', 'Dilarang merokok di dalam kamar, Tamu hanya boleh sampai jam 10 malam, Wajib menjaga kebersihan'),
(2, 'Kost Sejahtera 2', 'Jl. Sungai Duren No. 45, Sungai Duren', 'Kost eksklusif dengan view yang bagus, cocok untuk mahasiswa dan pekerja. Free WiFi dan listrik included.', 2000000.00, -1.611000, 103.551000, 'WiFi, AC, Kulkas, Kamar Mandi Dalam, Water Heater, Parkir Mobil', 'Tamu wajib lapor security, Dilarang bawa hewan peliharaan, Wajib bayar tepat waktu'),
(4, 'Kost Budi Santoso', 'Jl. Flamboyan No. 78, Mendalo', 'Kost sederhana dengan harga terjangkau, cocok untuk mahasiswa. Lingkungan asri dan tenang.', 800000.00, -1.612000, 103.552000, 'Kipas Angin, Kamar Mandi Luar, Parkir Motor', 'Tamu maksimal 2 orang, Wajib menjaga ketenangan, Dilarang menginap tanpa izin');

-- Insert sample kamar data
INSERT INTO kamars (kost_id, nomor_kamar, ukuran_kamar, harga_per_bulan, fasilitas_kamar, deskripsi_kamar, status) VALUES 
(1, 'A1', '3x4 meter', 1500000.00, 'AC, Lemari, Kasur, Meja Belajar', 'Kamar menghadap timur, dapat sinar matahari pagi', 'tersedia'),
(1, 'A2', '3x4 meter', 1500000.00, 'AC, Lemari, Kasur, Meja Belajar', 'Kamar tengah, lebih sejuk', 'tersedia'),
(1, 'A3', '3x4 meter', 1500000.00, 'AC, Lemari, Kasur, Meja Belajar', 'Kamar dekat kamar mandi', 'dipesan'),
(2, 'B1', '4x4 meter', 2000000.00, 'AC, Kulkas, LED TV, Lemari, Kasur Queen Size', 'Kamar corner dengan view bagus', 'tersedia'),
(2, 'B2', '4x4 meter', 2000000.00, 'AC, Kulkas, LED TV, Lemari, Kasur Queen Size', 'Kamar dengan balkon kecil', 'tersedia'),
(3, 'C1', '3x3 meter', 800000.00, 'Kipas Angin, Lemari, Kasur Single', 'Kamar sederhana cocok untuk mahasiswa', 'tersedia'),
(3, 'C2', '3x3 meter', 800000.00, 'Kipas Angin, Lemari, Kasur Single', 'Kamar depan, akses mudah', 'tersedia'),
(3, 'C3', '3x3 meter', 800000.00, 'Kipas Angin, Lemari, Kasur Single', 'Kamar belakang, lebih tenang', 'ditempati');

-- Insert kost_fasilitas relationships
INSERT INTO kost_fasilitas (kost_id, fasilitas_id) VALUES 
(1, 1), (1, 2), (1, 5), (1, 6), (1, 11), (1, 13), (1, 19),
(2, 1), (2, 3), (2, 5), (2, 11), (2, 13), (2, 17), (2, 19), (2, 20),
(3, 2), (3, 12), (3, 14), (3, 19), (3, 20);

-- Insert sample pemesanan
INSERT INTO pemesanans (kode_booking, penyewa_id, kamar_id, tanggal_masuk, durasi_sewa, total_biaya, status) VALUES 
('BOOK001', 3, 3, '2025-02-01', 6, 9000000.00, 'dikonfirmasi'),
('BOOK002', 5, 8, '2025-01-15', 12, 9600000.00, 'selesai');

-- Insert sample pembayaran
INSERT INTO pembayarans (pemesanan_id, kode_pembayaran, nominal, metode_pembayaran, status, tanggal_transfer, verified_by) VALUES 
(1, 'PAY001', 9000000.00, 'transfer_bank', 'lunas', '2025-01-25', 2),
(2, 'PAY002', 9600000.00, 'transfer_bank', 'lunas', '2024-12-20', 2);

-- Insert sample notifikasi
INSERT INTO notifikasis (user_id, judul, pesan, jenis, is_read) VALUES 
(2, 'Pemesanan Baru', 'Ada pemesanan baru untuk Kost Sejahtera 1 Kamar A3', 'pemesanan', TRUE),
(3, 'Pembayaran Diterima', 'Pembayaran Anda untuk pemesanan BOOK001 telah diterima', 'pembayaran', TRUE),
(5, 'Masa Sewa Berakhir', 'Masa sewa Anda untuk kamar C3 akan berakhir dalam 7 hari', 'sistem', FALSE);

-- Insert sample reviews
INSERT INTO reviews (penyewa_id, kost_id, pemesanan_id, rating, komentar, is_approved) VALUES 
(5, 3, 2, 4, 'Kost nyaman dan harga terjangkau. Pemiliknya ramah dan lokasi strategis.', TRUE);

-- =============================================
-- CREATE INDEXES FOR BETTER PERFORMANCE
-- =============================================

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_kost_pemilik ON kost(pemilik_id);
CREATE INDEX idx_kost_status ON kost(status);
CREATE INDEX idx_kamars_kost ON kamars(kost_id);
CREATE INDEX idx_kamars_status ON kamars(status);
CREATE INDEX idx_pemesanans_penyewa ON pemesanans(penyewa_id);
CREATE INDEX idx_pemesanans_kamar ON pemesanans(kamar_id);
CREATE INDEX idx_pemesanans_status ON pemesanans(status);
CREATE INDEX idx_pembayarans_pemesanan ON pembayarans(pemesanan_id);
CREATE INDEX idx_pembayarans_status ON pembayarans(status);
CREATE INDEX idx_notifikasis_user ON notifikasis(user_id);
CREATE INDEX idx_notifikasis_read ON notifikasis(is_read);
CREATE INDEX idx_reviews_kost ON reviews(kost_id);
CREATE INDEX idx_reviews_penyewa ON reviews(penyewa_id);

-- =============================================
-- CREATE VIEWS FOR REPORTING
-- =============================================

-- View untuk dashboard pemilik kost
CREATE VIEW view_dashboard_pemilik AS
SELECT 
    u.id as pemilik_id,
    u.nama as pemilik_nama,
    u.bank_nama,
    u.bank_nomor,
    COUNT(DISTINCT k.id) as total_kost,
    COUNT(DISTINCT km.id) as total_kamar,
    COUNT(DISTINCT CASE WHEN km.status = 'tersedia' THEN km.id END) as kamar_tersedia,
    COUNT(DISTINCT p.id) as total_pemesanan,
    COUNT(DISTINCT CASE WHEN p.status = 'menunggu' THEN p.id END) as pemesanan_menunggu,
    COALESCE(SUM(CASE WHEN pb.status = 'lunas' THEN pb.nominal ELSE 0 END), 0) as total_pendapatan
FROM users u
LEFT JOIN kost k ON u.id = k.pemilik_id
LEFT JOIN kamars km ON k.id = km.kost_id
LEFT JOIN pemesanans p ON km.id = p.kamar_id
LEFT JOIN pembayarans pb ON p.id = pb.pemesanan_id
WHERE u.role = 'pemilik'
GROUP BY u.id, u.nama, u.bank_nama, u.bank_nomor;

-- View untuk laporan pemesanan
CREATE VIEW view_laporan_pemesanan AS
SELECT 
    p.kode_booking,
    pe.nama as penyewa_nama,
    pe.no_telepon as penyewa_telepon,
    ko.nama_kost,
    km.nomor_kamar,
    p.tanggal_masuk,
    p.durasi_sewa,
    p.total_biaya,
    p.status as status_pemesanan,
    pb.status as status_pembayaran,
    pb.nominal as nominal_bayar,
    u.nama as pemilik_nama,
    u.bank_nama,
    u.bank_nomor,
    p.created_at
FROM pemesanans p
JOIN users pe ON p.penyewa_id = pe.id
JOIN kamars km ON p.kamar_id = km.id
JOIN kost ko ON km.kost_id = ko.id
JOIN users u ON ko.pemilik_id = u.id
LEFT JOIN pembayarans pb ON p.id = pb.pemesanan_id;

-- =============================================
-- CREATE STORED PROCEDURES
-- =============================================

DELIMITER //

-- Procedure untuk membuat pemesanan baru
CREATE PROCEDURE CreatePemesanan(
    IN p_penyewa_id INT,
    IN p_kamar_id INT,
    IN p_tanggal_masuk DATE,
    IN p_durasi_sewa INT
)
BEGIN
    DECLARE v_harga_kamar DECIMAL(12,2);
    DECLARE v_total_biaya DECIMAL(12,2);
    DECLARE v_kode_booking VARCHAR(20);
    
    -- Generate kode booking
    SET v_kode_booking = CONCAT('BOOK', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    -- Get harga kamar
    SELECT harga_per_bulan INTO v_harga_kamar 
    FROM kamars WHERE id = p_kamar_id;
    
    -- Calculate total biaya
    SET v_total_biaya = v_harga_kamar * p_durasi_sewa;
    
    -- Insert pemesanan
    INSERT INTO pemesanans (kode_booking, penyewa_id, kamar_id, tanggal_masuk, durasi_sewa, total_biaya)
    VALUES (v_kode_booking, p_penyewa_id, p_kamar_id, p_tanggal_masuk, p_durasi_sewa, v_total_biaya);
    
    -- Update status kamar
    UPDATE kamars SET status = 'dipesan' WHERE id = p_kamar_id;
    
    -- Return kode booking
    SELECT v_kode_booking as kode_booking;
END //

-- Procedure untuk verifikasi pembayaran
CREATE PROCEDURE VerifyPembayaran(
    IN p_pembayaran_id INT,
    IN p_verified_by INT,
    IN p_status ENUM('lunas','ditolak'),
    IN p_alasan_penolakan TEXT
)
BEGIN
    DECLARE v_pemesanan_id INT;
    
    -- Get pemesanan_id
    SELECT pemesanan_id INTO v_pemesanan_id 
    FROM pembayarans WHERE id = p_pembayaran_id;
    
    -- Update pembayaran
    UPDATE pembayarans 
    SET status = p_status,
        verified_by = p_verified_by,
        alasan_penolakan = p_alasan_penolakan,
        updated_at = NOW()
    WHERE id = p_pembayaran_id;
    
    -- Update pemesanan status
    IF p_status = 'lunas' THEN
        UPDATE pemesanans SET status = 'dikonfirmasi' WHERE id = v_pemesanan_id;
        -- Update kamar status to 'ditempati' when payment is verified
        UPDATE kamars km
        JOIN pemesanans p ON km.id = p.kamar_id
        SET km.status = 'ditempati'
        WHERE p.id = v_pemesanan_id;
    ELSE
        UPDATE pemesanans SET status = 'ditolak' WHERE id = v_pemesanan_id;
        -- Reset kamar status to 'tersedia' if payment rejected
        UPDATE kamars km
        JOIN pemesanans p ON km.id = p.kamar_id
        SET km.status = 'tersedia'
        WHERE p.id = v_pemesanan_id;
    END IF;
END //

DELIMITER ;

-- =============================================
-- CREATE TRIGGERS
-- =============================================

DELIMITER //

-- Trigger untuk membuat notifikasi otomatis saat pemesanan baru
CREATE TRIGGER after_insert_pemesanan
    AFTER INSERT ON pemesanans
    FOR EACH ROW
BEGIN
    DECLARE v_pemilik_id INT;
    DECLARE v_kost_nama VARCHAR(100);
    DECLARE v_kamar_nomor VARCHAR(10);
    
    -- Get pemilik_id and kost info
    SELECT ko.pemilik_id, ko.nama_kost, km.nomor_kamar 
    INTO v_pemilik_id, v_kost_nama, v_kamar_nomor
    FROM kamars km
    JOIN kost ko ON km.kost_id = ko.id
    WHERE km.id = NEW.kamar_id;
    
    -- Create notification for pemilik
    INSERT INTO notifikasis (user_id, judul, pesan, jenis)
    VALUES (
        v_pemilik_id,
        'Pemesanan Baru',
        CONCAT('Ada pemesanan baru untuk ', v_kost_nama, ' Kamar ', v_kamar_nomor, ' - Kode: ', NEW.kode_booking),
        'pemesanan'
    );
    
    -- Create notification for penyewa
    INSERT INTO notifikasis (user_id, judul, pesan, jenis)
    VALUES (
        NEW.penyewa_id,
        'Pemesanan Berhasil',
        CONCAT('Pemesanan Anda berhasil dengan kode ', NEW.kode_booking, '. Silakan lakukan pembayaran.'),
        'pemesanan'
    );
END //

-- Trigger untuk membuat notifikasi saat pembayaran diverifikasi
CREATE TRIGGER after_update_pembayaran
    AFTER UPDATE ON pembayarans
    FOR EACH ROW
BEGIN
    DECLARE v_penyewa_id INT;
    
    IF OLD.status != NEW.status THEN
        -- Get penyewa_id
        SELECT p.penyewa_id INTO v_penyewa_id
        FROM pemesanans p
        WHERE p.id = NEW.pemesanan_id;
        
        IF NEW.status = 'lunas' THEN
            -- Notification for penyewa - payment approved
            INSERT INTO notifikasis (user_id, judul, pesan, jenis)
            VALUES (
                v_penyewa_id,
                'Pembayaran Diterima',
                CONCAT('Pembayaran untuk kode booking ', (SELECT kode_booking FROM pemesanans WHERE id = NEW.pemesanan_id), ' telah diterima.'),
                'pembayaran'
            );
        ELSEIF NEW.status = 'ditolak' THEN
            -- Notification for penyewa - payment rejected
            INSERT INTO notifikasis (user_id, judul, pesan, jenis)
            VALUES (
                v_penyewa_id,
                'Pembayaran Ditolak',
                CONCAT('Pembayaran untuk kode booking ', (SELECT kode_booking FROM pemesanans WHERE id = NEW.pemesanan_id), ' ditolak. Alasan: ', NEW.alasan_penolakan),
                'pembayaran'
            );
        END IF;
    END IF;
END //

DELIMITER ;

-- =============================================
-- VERIFY DATA
-- =============================================

-- Verify users with bank accounts
SELECT id, nama, role, bank_nama, bank_nomor, bank_atas_nama 
FROM users 
WHERE role = 'pemilik';

-- Verify the database structure
SHOW TABLES;

-- Check sample data
SELECT 
    k.nama_kost,
    k.alamat,
    k.harga_per_bulan,
    u.nama as pemilik_nama,
    u.bank_nama,
    u.bank_nomor,
    u.bank_atas_nama,
    COUNT(km.id) as total_kamar,
    COUNT(CASE WHEN km.status = 'tersedia' THEN km.id END) as kamar_tersedia
FROM kost k
JOIN users u ON k.pemilik_id = u.id
LEFT JOIN kamars km ON k.id = km.kost_id
WHERE k.status = 'tersedia'
GROUP BY k.id, k.nama_kost, k.alamat, k.harga_per_bulan, u.nama, u.bank_nama, u.bank_nomor, u.bank_atas_nama;vibekosusers