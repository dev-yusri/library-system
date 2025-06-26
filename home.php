<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Home</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
  <div class="container">
  <?php $current_page = 'home'; include 'menu/sidebar.php'; ?>


    <main class="main-content">
      <h1 class="font-bold text-2xl pb-2">Welcome to the Library</h1>
      <p class="subtext">
        Your gateway to knowledge and discovery. Browse our collection, manage your books, and more.
      </p>

      <div class="card-grid">
      <a href="book.php" class="block">
        <div class="card">
        <div class="icon "><i class="fa fa-book-open "></i>
</i></div>
          <h3>Browse Books</h3>
          <p>Explore our vast collection of books across various genres.</p>
        </div></a>
        <a href="scanner.php" class="block">
        <div class="card">
          <div class="icon"><i class="fa fa-qrcode"></i></div>
          <h3>Quick Scan</h3>
          <p>Scan QR codes to quickly check-in or check-out books.</p>
        </div></a>
        <a href="dashboard.php" class="block">
        <div class="card">
          <div class="icon"><i class="fas fa-user-cog"></i></div>
          <h3>Your Dashboard</h3>
          <p>View your borrowed books and manage your account.</p>
        </div></a>
      </div>
    </main>
  </div>
</body>
</html>

