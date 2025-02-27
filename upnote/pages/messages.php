<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'];
$successMessage = "";
$errorMessage = "";

// Set cookie (contoh)
setcookie("visited", "true", time() + 86400, "/");

// --------------------- DELETE CONVERSATION ---------------------
if (isset($_GET['delete_conversation'])) {
    $partner_id_del = intval($_GET['delete_conversation']);

    // Hapus semua pesan di tabel messages antara user ini dan partner_id_del
    $stmt = $conn->prepare("
        DELETE FROM messages
        WHERE (sender_id = ? AND receiver_id = ?)
           OR (sender_id = ? AND receiver_id = ?)
    ");
    $stmt->bind_param("iiii", $user_id, $partner_id_del, $partner_id_del, $user_id);

    if ($stmt->execute()) {
        $successMessage = "Percakapan berhasil dihapus.";
    } else {
        $errorMessage = "Gagal menghapus percakapan.";
    }
    header("Location: messages.php?success=" . urlencode($successMessage) . "&error=" . urlencode($errorMessage));
    exit();
}

// --------------------- DELETE MESSAGE ---------------------
if (isset($_GET['delete_message'])) {
    $message_id = intval($_GET['delete_message']);
    $stmt = $conn->prepare("SELECT id FROM messages WHERE id = ? AND sender_id = ?");
    $stmt->bind_param("ii", $message_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $delStmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND sender_id = ?");
        $delStmt->bind_param("ii", $message_id, $user_id);
        if ($delStmt->execute()) {
            $successMessage = "Pesan berhasil dihapus.";
        } else {
            $errorMessage = "Gagal menghapus pesan.";
        }
    } else {
        $errorMessage = "Pesan tidak ditemukan atau Anda tidak memiliki hak menghapus pesan ini.";
    }
    header("Location: messages.php?success=" . urlencode($successMessage) . "&error=" . urlencode($errorMessage));
    exit();
}

// --------------------- SEND MESSAGE ---------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $receiver_id = intval($_POST['receiver_id']);
    $content = trim($_POST['content']);

    if (empty($content) || $receiver_id <= 0) {
        $errorMessage = "Pastikan semua field terisi dengan benar.";
    } else {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $user_id, $receiver_id, $content);
        if ($stmt->execute()) {
            $successMessage = "Pesan berhasil dikirim.";
        } else {
            $errorMessage = "Gagal mengirim pesan.";
        }
    }
    header("Location: messages.php?partner_id=" . $receiver_id . "&success=" . urlencode($successMessage) . "&error=" . urlencode($errorMessage));
    exit();
}

// --------------------- EDIT MESSAGE ---------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_message') {
    $message_id = intval($_POST['message_id']);
    $newContent = trim($_POST['new_content']);
    $partner_id_for_edit = isset($_GET['partner_id']) ? intval($_GET['partner_id']) : 0;

    if (empty($newContent)) {
        $errorMessage = "Pesan tidak boleh kosong.";
    } else {
        $stmt = $conn->prepare("UPDATE messages SET content = ? WHERE id = ? AND sender_id = ?");
        $stmt->bind_param("sii", $newContent, $message_id, $user_id);
        if ($stmt->execute()) {
            $successMessage = "Pesan berhasil diperbarui.";
        } else {
            $errorMessage = "Gagal memperbarui pesan.";
        }
    }
    header("Location: messages.php?partner_id=" . $partner_id_for_edit . "&success=" . urlencode($successMessage) . "&error=" . urlencode($errorMessage));
    exit();
}

// --------------------- CONVERSATION LOGIC ---------------------
$partner_id = isset($_GET['partner_id']) ? intval($_GET['partner_id']) : 0;
$conversationQuery = "
    SELECT DISTINCT
        CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS partner_id
    FROM messages
    WHERE sender_id = ? OR receiver_id = ?
    ORDER BY created_at DESC
";
$stmt = $conn->prepare($conversationQuery);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$conversationPartners = [];
while ($row = mysqli_fetch_assoc($result)) {
    $conversationPartners[] = $row['partner_id'];
}
$conversationPartners = array_unique($conversationPartners);

$partnersDetails = [];
if (!empty($conversationPartners)) {
    $ids = implode(',', $conversationPartners);
    $partnerQuery = "SELECT id, username, profile_pic FROM users WHERE id IN ($ids)";
    $res = mysqli_query($conn, $partnerQuery);
    while ($row = mysqli_fetch_assoc($res)) {
        $partnersDetails[$row['id']] = $row;
    }
}

$partnerDetails = null;
$conversationMessages = [];
if ($partner_id > 0) {
    $stmt = $conn->prepare("SELECT id, username, profile_pic FROM users WHERE id = ?");
    $stmt->bind_param("i", $partner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $partnerDetails = mysqli_fetch_assoc($result);

    if ($partnerDetails) {
        $msgQuery = "
            SELECT *
            FROM messages
            WHERE (sender_id = ? AND receiver_id = ?)
               OR (sender_id = ? AND receiver_id = ?)
            ORDER BY created_at ASC
        ";
        $stmt = $conn->prepare($msgQuery);
        $stmt->bind_param("iiii", $user_id, $partner_id, $partner_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $conversationMessages = mysqli_fetch_all($result, MYSQLI_ASSOC);
    } else {
        $partner_id = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Direct Messages - UpNote</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .modal {
      display: none; /* Pastikan modal tidak muncul otomatis */
    }
    .chat-bubble {
      max-width: 70%;
      padding: 0.75rem;
      border-radius: 0.75rem;
      word-wrap: break-word;
    }
    .chat-bubble.sent {
      background-color: #DCF8C6;
      align-self: flex-end;
    }
    .chat-bubble.received {
      background-color: #FFFFFF;
      align-self: flex-start;
    }
    /* Form pengiriman pesan tersembunyi secara default */
    #messageForm {
      display: none;
    }
  </style>
</head>
<body class="bg-gray-100">
  <!-- Navigation Bar -->
  <nav class="bg-white shadow-md py-4">
    <div class="container mx-auto flex justify-between items-center px-4">
      <h1 class="text-2xl font-bold text-blue-600">UpNote</h1>
      <div class="space-x-4">
        <a href="home.php" class="text-gray-700 hover:text-blue-500">Home</a>
        <a href="messages.php" class="text-gray-700 hover:text-blue-500">Messages</a>
        <a href="notifications.php" class="text-gray-700 hover:text-blue-500">Notifications</a>
        <a href="logout.php" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container mx-auto px-4 py-8 flex">
    <!-- Sidebar: Daftar Percakapan -->
    <div class="w-1/3 bg-white rounded-lg shadow p-4 mr-4">
      <h2 class="text-xl font-bold mb-4">Conversations</h2>
      <?php if (!empty($partnersDetails)): ?>
        <ul class="space-y-2">
          <?php foreach ($partnersDetails as $partner): ?>
            <li class="p-2 rounded hover:bg-gray-100 <?php echo ($partner['id'] == $partner_id) ? 'bg-gray-200' : ''; ?>">
              <a href="messages.php?partner_id=<?php echo $partner['id']; ?>" class="flex items-center">
                <img src="../assets/uploads/<?php echo htmlspecialchars($partner['profile_pic'] ?? 'default.png'); ?>" alt="Profile" class="w-10 h-10 rounded-full mr-3">
                <span class="font-semibold"><?php echo htmlspecialchars($partner['username']); ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="text-gray-700">No conversations found.</p>
      <?php endif; ?>
    </div>

    <!-- Chat Area -->
    <div class="w-2/3 bg-white rounded-lg shadow p-4 flex flex-col">
      <?php if ($partner_id > 0 && $partnerDetails): ?>
        <div class="mb-4 border-b pb-2 flex items-center">
          <img src="../assets/uploads/<?php echo htmlspecialchars($partnerDetails['profile_pic'] ?? 'default.png'); ?>" alt="Profile" class="w-12 h-12 rounded-full mr-3">
          <h2 class="text-xl font-bold"><?php echo htmlspecialchars($partnerDetails['username']); ?></h2>

          <!-- Tambahkan tombol Delete Chat -->
          <button onclick="if(confirm('Hapus seluruh chat dengan <?php echo htmlspecialchars($partnerDetails['username']); ?>?')) {
              window.location='messages.php?delete_conversation=<?php echo $partner_id; ?>';
          }"
          class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 ml-4 text-sm">
            Delete Chat
          </button>
        </div>

        <!-- Pesan Sukses / Error -->
        <?php if ($successMessage): ?>
          <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($successMessage); ?>
          </div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
          <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($errorMessage); ?>
          </div>
        <?php endif; ?>

        <!-- Daftar Pesan -->
        <div class="flex-1 overflow-y-auto space-y-4 p-2" style="max-height: 500px;">
          <?php if (!empty($conversationMessages)): ?>
            <?php foreach ($conversationMessages as $msg): ?>
              <div class="flex <?php echo ($msg['sender_id'] == $user_id) ? 'justify-end' : 'justify-start'; ?>">
                <div class="chat-bubble <?php echo ($msg['sender_id'] == $user_id) ? 'sent' : 'received'; ?>">
                  <p><?php echo nl2br(htmlspecialchars($msg['content'])); ?></p>
                  <small class="block text-right text-xs text-gray-500"><?php echo date('H:i', strtotime($msg['created_at'])); ?></small>
                </div>

                <!-- Jika pesan milik user, tampilkan tombol Edit dan Delete -->
                <?php if ($msg['sender_id'] == $user_id): ?>
                  <div class="flex flex-col ml-2">
                    <button onclick="openModal(<?php echo $msg['id']; ?>)" class="mb-1 bg-yellow-500 text-white px-2 py-1 rounded hover:bg-yellow-600 text-sm">
                      Edit
                    </button>
                    <a href="messages.php?delete_message=<?php echo $msg['id']; ?>" onclick="return confirm('Anda yakin ingin menghapus pesan ini?');" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 text-sm">
                      Delete
                    </a>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Modal Edit hanya untuk pesan milik user sendiri -->
              <?php if ($msg['sender_id'] == $user_id): ?>
              <div id="editModal_<?php echo $msg['id']; ?>" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 w-full max-w-md">
                  <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">Edit Message</h3>
                    <button onclick="closeModal(<?php echo $msg['id']; ?>)" class="text-gray-500 hover:text-gray-700">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                  <form action="messages.php?partner_id=<?php echo $partner_id; ?>" method="POST">
                    <input type="hidden" name="action" value="edit_message">
                    <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                    <div class="mb-4">
                      <textarea name="new_content" rows="4" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required><?php echo htmlspecialchars($msg['content']); ?></textarea>
                    </div>
                    <div class="flex justify-end">
                      <button type="button" onclick="closeModal(<?php echo $msg['id']; ?>)" class="mr-2 px-4 py-2 border rounded hover:bg-gray-100">
                        Cancel
                      </button>
                      <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Save Changes
                      </button>
                    </div>
                  </form>
                </div>
              </div>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-gray-700">No messages in this conversation.</p>
          <?php endif; ?>
        </div>

        <!-- Tombol Toggle Form Pengiriman Pesan -->
        <button id="toggleMessageForm" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mt-4">
          Tulis Pesan
        </button>

        <!-- Form Pengiriman Pesan Baru (disembunyikan secara default) -->
        <div id="messageForm" style="display: none;">
          <form action="messages.php?partner_id=<?php echo $partner_id; ?>" method="POST" class="mt-4">
            <input type="hidden" name="action" value="send_message">
            <input type="hidden" name="receiver_id" value="<?php echo $partner_id; ?>">
            <div class="flex">
              <input type="text" name="content" class="flex-1 p-2 border rounded-l focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Type a message..." required>
              <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-r hover:bg-blue-600">
                Send
              </button>
            </div>
          </form>
        </div>
      <?php else: ?>
        <div class="flex-1 flex items-center justify-center">
          <p class="text-gray-700">Select a conversation to start chatting.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Tutup semua modal saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
      var allModals = document.querySelectorAll('.modal');
      allModals.forEach(function(modal) {
        modal.style.display = 'none';
      });
    });

    function openModal(messageId) {
      document.getElementById('editModal_' + messageId).style.display = 'flex';
    }
    function closeModal(messageId) {
      document.getElementById('editModal_' + messageId).style.display = 'none';
    }
    // Toggle form pengiriman pesan
    document.getElementById('toggleMessageForm').addEventListener('click', function(){
      var form = document.getElementById('messageForm');
      if (form.style.display === "none" || form.style.display === "") {
          form.style.display = "block";
      } else {
          form.style.display = "none";
      }
    });
  </script>
</body>
</html>
