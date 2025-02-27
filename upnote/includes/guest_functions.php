<?php
// SIMPAN FILE INI SEBAGAI: includes/guest_functions.php

/**
 * Generate unique guest user
 * @return array User data for the guest
 */
function generateUniqueGuest($conn) {
    // Generate unique suffix for guest name
    $uniqueId = substr(md5(uniqid(mt_rand(), true)), 0, 8);
    $guestUsername = "Guest_" . $uniqueId;
    
    // Generate random profile picture color (untuk avatar)
    $colors = ['red', 'blue', 'green', 'purple', 'orange', 'teal', 'pink', 'brown'];
    $color = $colors[array_rand($colors)];
    $profile_pic = null; // Guest tidak memiliki profile pic
    
    // Enkripsi password acak (tidak akan digunakan untuk login, tapi diperlukan untuk database)
    $randomPass = bin2hex(random_bytes(8));
    $hashedPassword = password_hash($randomPass, PASSWORD_BCRYPT);
    
    // Simpan guest user ke database
    $stmt = $conn->prepare("INSERT INTO users (username, email, bio, password, created_at) 
                           VALUES (?, ?, ?, ?, NOW())");
    
    $guestEmail = "guest" . $uniqueId . "@temporary.com";
    $guestBio = "Saya adalah pengguna tamu";
    
    $stmt->bind_param("ssss", $guestUsername, $guestEmail, $guestBio, $hashedPassword);
    
    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        
        // Return user data
        return [
            'id' => $userId,
            'username' => $guestUsername,
            'is_guest' => true,
            'guest_id' => $uniqueId
        ];
    }
    
    // Fallback jika insert gagal
    return false;
}

/**
 * Set user sebagai guest di session
 */
function setGuestSession($userData) {
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['username'] = $userData['username'];
    $_SESSION['is_guest'] = true;
    $_SESSION['guest_id'] = $userData['guest_id'];
}

/**
 * Cek apakah user saat ini adalah guest
 */
function isGuestUser() {
    return isset($_SESSION['is_guest']) && $_SESSION['is_guest'] === true;
}

/**
 * Handle pembersihan data guest saat logout
 * Dipanggil dari logout.php untuk membersihkan data guest yang tidak diperlukan
 */
function cleanupGuestDataIfNeeded($conn, $userId) {
    // Jika user adalah guest, hapus data mereka saat logout
    // Kita simpan post dan aktivitas mereka, tapi user account dihapus
    if (isset($_SESSION['is_guest']) && $_SESSION['is_guest'] === true) {
        // Optional: Delete guest user (gunakan dengan hati-hati, mungkin Anda ingin menyimpan data)
        // Jika Anda menghapus user, pastikan constraint cascade delete di database
        // Jika tidak, Anda perlu menghapus semua data terkait terlebih dahulu
        
        // Contoh kode untuk menghapus user (hanya dilakukan jika Anda ingin menghapus akun guest):
        /*
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND username LIKE 'Guest_%'");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        */
    }
}