<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$sql_ratings = "SELECT u.name AS user_name, b.name AS book_name, r.rating, r.review
                FROM book_rating r
                JOIN users u ON r.user_id = u.user_id
                JOIN books b ON r.book_id = b.id
                ORDER BY r.book_id DESC";
$result_ratings = $conn->query($sql_ratings);

if (!$result_ratings) {
    die("Error in SQL query: " . $conn->error);
}

$ratings = $result_ratings->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Book Ratings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a2e0f1f93b.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200">
<div class="min-h-screen flex flex-col md:flex-row">
<?php $current_page = 'feedback'; include '../menu/sidebar_admin.php'; ?>
    <div class="flex-1 p-4 md:p-6 w-full">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="text-center mb-10">
            <h1 class="text-4xl font-bold text-gray-800">Book Ratings & Reviews</h1>
            <p class="text-gray-600 mt-2">Insights from our readers</p>
        </div>

        <div class="bg-white shadow-xl rounded-xl p-6 overflow-x-auto">
            <table class="min-w-full table-auto text-sm text-left text-gray-700">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase tracking-wider text-xs border-b">
                        <th class="py-3 px-6">👤 User</th>
                        <th class="py-3 px-6">📖 Book</th>
                        <th class="py-3 px-6">⭐ Rating</th>
                        <th class="py-3 px-6">📝 Review</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ratings as $rating): ?>
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="py-4 px-6 font-medium"><?= htmlspecialchars($rating['user_name']) ?></td>
                            <td class="py-4 px-6"><?= htmlspecialchars($rating['book_name']) ?></td>
                            <td class="py-2 px-4 border-b text-yellow-500">  <?php for ($i = 1; $i <= 5; $i++): ?>
    <i class="fas fa-star<?= $i <= $rating['rating'] ? '' : '-o' ?>"></i>
  <?php endfor; ?> </td>
                            <td class="py-4 px-6 text-gray-700"><?= nl2br(htmlspecialchars($rating['review'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>


    </div>


</body>
</html>
