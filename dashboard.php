<?php
session_start();
require 'db.php';
require 'menu/sidebar.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT 
    b.name,
    b.author,
    b.image_url,
    bl.borrowed_at,
    bl.due_date,
    bl.returned_at
FROM borrow_log bl
JOIN books b ON bl.book_id = b.id
WHERE bl.user_id = ? 
ORDER BY bl.borrowed_at DESC");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$borrowed_books = $result->fetch_all(MYSQLI_ASSOC);

// Stats
$total_borrowed = count($borrowed_books);
$current_borrowed = count(array_filter($borrowed_books, fn($b) => !$b['returned_at']));
$late_returns = count(array_filter($borrowed_books, fn($b) => !$b['returned_at'] && strtotime($b['due_date']) < time()));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="flex justify-between items-center bg-white p-4 shadow-md">
    <h2 class="text-2xl font-bold text-indigo-700">My Dashboard</h2>
    <div class="relative inline-block text-left">
      <button class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700" id="dropdownBtn">
        <i class="fas fa-user mr-2"></i> My Account <i class="fas fa-chevron-down ml-2"></i>
      </button>
      <div class="hidden absolute right-0 mt-2 w-48 bg-white border rounded shadow z-10" id="dropdownMenu">
        <a href="my_account.php" class="block px-4 py-2 hover:bg-gray-100">My Account</a>
        <a href="borrow_history.php" class="block px-4 py-2 hover:bg-gray-100">Borrow History</a>
      
        <button onclick="showLogoutModal()" class="flex items-center gap-3 p-3 w-full text-left rounded-lg hover:bg-gray-100 hover:text-black">
  <i class="fa fa-sign-out-alt"></i> Logout
</button>
  
      </div>
    </div>
  </div>
<div class="min-h-screen flex flex-col md:flex-row">
<?php $current_page = 'dashboard'; include 'menu/sidebar.php'; ?>
  <!-- Navbar -->
  

  <!-- Content -->
  <div class="flex-1 p-4 md:p-6 w-full">
    <!-- Quick Actions -->
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-xl font-semibold">Quick Actions</h3>
      <a href="scanner.php" class="bg-indigo-600 text-white px-5 py-2 rounded hover:bg-indigo-700">
        <i class="fas fa-qrcode mr-1"></i> Scan Book
      </a>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
      <div class="bg-white rounded shadow p-4">
        <h4 class="text-gray-700">Total Borrowed</h4>
        <p class="text-3xl font-bold text-indigo-700"><?= $total_borrowed ?></p>
      </div>
      <div class="bg-white rounded shadow p-4">
        <h4 class="text-gray-700">Currently Borrowed</h4>
        <p class="text-3xl font-bold text-yellow-600"><?= $current_borrowed ?></p>
      </div>
      <div class="bg-white rounded shadow p-4">
        <h4 class="text-gray-700">Late Returns</h4>
        <p class="text-3xl font-bold text-red-600"><?= $late_returns ?></p>
      </div>
    </div>

    <!-- Notifications -->
    <?php if ($late_returns > 0): ?>
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
        <p class="font-bold">Overdue Alert</p>
        <p>You have <?= $late_returns ?> overdue book<?= $late_returns > 1 ? 's' : '' ?>! Please return them ASAP.</p>
      </div>
    <?php endif; ?>

    <!-- Borrowed Books -->
    <div>
      <h3 class="mt-5 text-xl font-semibold mb-4">Currently Borrowed Books</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
      <?php
$hasUnreturned = false;
foreach ($borrowed_books as $book) {
  if (!$book['returned_at']) {
    $hasUnreturned = true;
    ?>
    <div class="bg-white rounded shadow p-4 mb-4">
      <h4 class="font-bold text-lg"><?= htmlspecialchars($book['name']) ?></h4>
      <p class="text-gray-600">Author: <?= htmlspecialchars($book['author']) ?></p>
      <p class="text-gray-500 text-sm">Due: <?= date('d M Y', strtotime($book['due_date'])) ?></p>
      <?php if (strtotime($book['due_date']) < time()): ?>
        <span class="text-sm text-red-600">⚠️ Overdue</span>
      <?php else: ?>
        <span class="text-sm text-green-600">On Time</span>
      <?php endif; ?>
    </div>
    <?php
  }
}
?>

<?php if (!$hasUnreturned): ?>
  <div class="text-center py-8 px-4 bg-white rounded shadow text-gray-500 text-lg">
    <i class="fas fa-book-open text-2xl text-gray-400 mb-2"></i><br>
    No borrowed book
  </div>
<?php endif; ?>

      </div>
    </div>
  </div>

  <script>
    const dropdownBtn = document.getElementById("dropdownBtn");
    const dropdownMenu = document.getElementById("dropdownMenu");
    dropdownBtn.addEventListener("click", () => {
      dropdownMenu.classList.toggle("hidden");
    });
    window.onclick = function(event) {
      if (!event.target.matches('#dropdownBtn') && !dropdownBtn.contains(event.target)) {
        dropdownMenu.classList.add("hidden");
      }
    }

    function showLogoutModal() {
    const modal = document.getElementById('logoutModal');
    const content = document.getElementById('modalContent');

    modal.classList.remove('hidden');
    setTimeout(() => {
      content.classList.remove('scale-95', 'opacity-0', 'translate-y-6');
      content.classList.add('scale-100', 'opacity-100', 'translate-y-0');
    }, 10); // triggers CSS animation
  }

  function hideLogoutModal() {
    const modal = document.getElementById('logoutModal');
    const content = document.getElementById('modalContent');

    content.classList.add('scale-95', 'opacity-0', 'translate-y-6');
    content.classList.remove('scale-100', 'opacity-100', 'translate-y-0');

    setTimeout(() => {
      modal.classList.add('hidden');
    }, 200); // matches the transition duration
  }
  </script>
</body>
</html>
