<?php
include '../includes/config.php';
checkRole('penyewa');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pemesanan_id = $_POST['pemesanan_id'];
    $rating = $_POST['rating'];
    $komentar = $conn->real_escape_string($_POST['komentar']);
    $user_id = $_SESSION['user_id'];
    
    // Check if pemesanan exists and belongs to user
    $check_query = "SELECT p.*, k.id as kost_id 
                   FROM pemesanans p
                   JOIN kamars km ON p.kamar_id = km.id
                   JOIN kost k ON km.kost_id = k.id
                   WHERE p.id = ? AND p.penyewa_id = ? AND p.status = 'selesai'";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $pemesanan_id, $user_id);
    $stmt->execute();
    $pemesanan = $stmt->get_result()->fetch_assoc();
    
    if ($pemesanan) {
        // Check if review already exists
        $existing_query = "SELECT * FROM reviews WHERE pemesanan_id = ?";
        $stmt = $conn->prepare($existing_query);
        $stmt->bind_param("i", $pemesanan_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows == 0) {
            // Insert new review
            $insert_query = "INSERT INTO reviews (penyewa_id, kost_id, pemesanan_id, rating, komentar, is_approved) 
                            VALUES (?, ?, ?, ?, ?, 1)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iiiis", $user_id, $pemesanan['kost_id'], $pemesanan_id, $rating, $komentar);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Review berhasil dikirim!";
            } else {
                $_SESSION['error'] = "Gagal mengirim review!";
            }
        } else {
            $_SESSION['error'] = "Anda sudah memberikan review untuk pemesanan ini!";
        }
    } else {
        $_SESSION['error'] = "Pemesanan tidak valid!";
    }
    
    header("Location: riwayat.php");
    exit();
}
?>