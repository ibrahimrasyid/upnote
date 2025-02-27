<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/guest_functions.php';

// Simpan ID user sebelum menghapus session
$user_id = $_SESSION['user_id'] ?? null;
$was_guest = isset($_SESSION['is_guest']) && $_SESSION['is_guest'] === true;

// Hapus semua session
session_unset();
session_destroy();

// Jika user adalah guest dan kita memiliki user_id
if ($was_guest && $user_id) {
    // Lakukan pembersihan data guest jika diperlukan
    cleanupGuestDataIfNeeded($conn, $user_id);
}

// Redirect ke halaman login setelah logout
header("Location: login.php");
exit();
?>