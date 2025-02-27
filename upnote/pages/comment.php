<?php
session_start();
include '../includes/db.php';
include '../includes/guest_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Silakan login terlebih dahulu']);
    exit();
}

$response = ['success' => false];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $post_id = $_POST['post_id'];
    $comment = trim($_POST['comment']);
    $is_guest = isGuestUser(); // Cek apakah user adalah guest

    if (!empty($comment)) {
        // Tambahkan komentar dengan kolom is_guest
        $stmt = $conn->prepare("INSERT INTO comments (user_id, post_id, comment, created_at, is_guest) VALUES (?, ?, ?, NOW(), ?)");
        $stmt->bind_param("iisi", $user_id, $post_id, $comment, $is_guest);
        
        if ($stmt->execute()) {
            // Get the inserted comment data
            $commentQuery = "SELECT c.*, u.username, u.profile_pic 
                           FROM comments c 
                           JOIN users u ON c.user_id = u.id 
                           WHERE c.id = LAST_INSERT_ID()";
            $result = $conn->query($commentQuery);
            $commentData = $result->fetch_assoc();
            
            // Tambahkan informasi is_guest ke data komentar
            $commentData['is_guest'] = $is_guest;
            
            $response = [
                'success' => true,
                'comment' => $commentData
            ];
        } else {
            $response['error'] = "Gagal menambahkan komentar";
        }
    } else {
        $response['error'] = "Komentar tidak boleh kosong";
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit();