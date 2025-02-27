<?php
session_start();
require_once '../includes/db.php';

// Cek apakah user sudah login atau mengakses sebagai guest
// Jika tidak memiliki session user_id, buat sebagai guest
if (!isset($_SESSION['user_id'])) {
    // Set sebagai guest mode
    $_SESSION['is_guest'] = true;
    
    // Cari ID user Guest dari database
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = 'Guest' LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $guest_user = $result->fetch_assoc();
        $_SESSION['user_id'] = $guest_user['id'];
    } else {
        // Jika user Guest belum ada di database, redirect ke login
        header("Location: login.php?error=Guest access not configured");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $is_guest = isset($_SESSION['is_guest']) ? 1 : 0;
    $content = trim($_POST['post_content']);
    
    // Generate guest identifier jika diperlukan (bisa menggunakan IP atau session ID)
    $guest_identifier = null;
    if ($is_guest) {
        // Gunakan hash dari IP dan session ID sebagai identifier
        $guest_identifier = md5($_SERVER['REMOTE_ADDR'] . session_id());
    }
    
    // Validasi konten
    if (empty($content)) {
        header("Location: home.php?error=Konten post tidak boleh kosong");
        exit();
    }

    // Pastikan direktori uploads ada
    $uploadDirectory = "../assets/uploads/";
    if (!file_exists($uploadDirectory)) {
        mkdir($uploadDirectory, 0777, true);
    }
    
    // -------------- Upload Gambar --------------
    $image_name = null;
    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['post_image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            // Generate nama file unik
            $newname = uniqid() . '.' . $filetype;
            $upload_path = $uploadDirectory . $newname;
            
            if (move_uploaded_file($_FILES['post_image']['tmp_name'], $upload_path)) {
                $image_name = $newname;
            } else {
                header("Location: home.php?error=Gagal mengupload gambar");
                exit();
            }
        } else {
            header("Location: home.php?error=Format file gambar tidak didukung");
            exit();
        }
    }

    // -------------- Upload Video --------------
    $video_name = null;
    if (isset($_FILES['post_video']) && $_FILES['post_video']['error'] == 0) {
        $allowed_video = ['mp4', 'webm', 'ogg'];
        $video_filename = $_FILES['post_video']['name'];
        $video_filetype = pathinfo($video_filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($video_filetype), $allowed_video)) {
            // Batas maksimum ukuran video adalah 20MB
            if ($_FILES['post_video']['size'] > 20 * 1024 * 1024) {
                header("Location: home.php?error=Ukuran video terlalu besar. Maksimum 20MB");
                exit();
            }
            
            // Generate nama file unik untuk video
            $new_video_name = uniqid() . '.' . $video_filetype;
            $upload_path_video = $uploadDirectory . $new_video_name;
            
            if (move_uploaded_file($_FILES['post_video']['tmp_name'], $upload_path_video)) {
                $video_name = $new_video_name;
            } else {
                header("Location: home.php?error=Gagal mengupload video");
                exit();
            }
        } else {
            header("Location: home.php?error=Format video tidak didukung");
            exit();
        }
    }

    // -------------- Insert ke Database --------------
    try {
        // Insert data post ke database dengan field is_guest dan guest_identifier
        $stmt = $conn->prepare("
            INSERT INTO posts (user_id, content, image, video, created_at, is_guest, guest_identifier) 
            VALUES (?, ?, ?, ?, NOW(), ?, ?)
        ");
        $stmt->bind_param("isssss", $user_id, $content, $image_name, $video_name, $is_guest, $guest_identifier);
        
        if ($stmt->execute()) {
            header("Location: home.php?success=Post berhasil dibuat");
            exit();
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }
    } catch (Exception $e) {
        // Jika terjadi error, hapus file yang sudah diupload (jika ada)
        if ($image_name && isset($upload_path) && file_exists($upload_path)) {
            unlink($upload_path);
        }
        if ($video_name && isset($upload_path_video) && file_exists($upload_path_video)) {
            unlink($upload_path_video);
        }
        header("Location: home.php?error=Gagal membuat post: " . $e->getMessage());
        exit();
    }
}

// Jika bukan POST request, redirect ke home
header("Location: home.php");
exit();