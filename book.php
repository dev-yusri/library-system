<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}


include 'db.php';

$search = "";
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $stmt = $conn->prepare("SELECT * FROM books WHERE name LIKE ? OR author LIKE ?");
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
} else {
    $stmt = $conn->prepare("SELECT * FROM books");
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Library Books</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  
</head>
<body class="bg-gray-100 text-gray-800">

  <div class="flex flex-col md:flex-row min-h-screen">
    <!-- Sidebar -->
    <?php $current_page = 'book'; include 'menu/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 p-6">
      <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
        <h1 class="text-3xl font-bold text-center sm:text-left">Book Collection</h1>
    


      </div>
      <div class="flex flex-wrap justify-end items-center gap-4 mb-6">
  <input type="text" id="searchBar" placeholder="Search books..." class="border p-2 rounded w-full sm:w-64">
  
  <select id="sortFilter" class="border p-2 rounded w-full sm:w-48">
    <option value="">Sort by</option>
    <option value="created_asc">Created (Oldest First)</option>
    <option value="created_desc">Created (Newest First)</option>
    <option value="name_asc">Name (A → Z)</option>
    <option value="name_desc">Name (Z → A)</option>
    <option value="author_asc">Author (A → Z)</option>
    <option value="author_desc">Author (Z → A)</option>
  </select>
</div>
<div id="bookResults">
  <?php
    require 'book_results.php'; // Initially load the results server-side
  ?>
</div>
    


    </main>
  </div>

</body>
<script>
  const searchBar = document.getElementById('searchBar');
  const sortFilter = document.getElementById('sortFilter');
  const bookResults = document.getElementById('bookResults');
  let debounceTimer;

  function fetchBooks() {
    const searchQuery = encodeURIComponent(searchBar.value.trim());
    const sort = encodeURIComponent(sortFilter.value);

    fetch(`book_results.php?search=${searchQuery}&sort=${sort}`)
      .then(res => res.text())
      .then(data => {
        bookResults.innerHTML = data;
      });
  }

  searchBar.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchBooks, 300); // debounce typing
  });

  sortFilter.addEventListener('change', fetchBooks);
</script>

</html>
