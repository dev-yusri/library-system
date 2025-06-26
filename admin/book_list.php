<?php
include '../db.php'; // Make sure your DB connection is here

// Handle search
$search = $_GET['search'] ?? '';
$query = "SELECT b.*, 
            IF(bl.book_id IS NULL, 'Available', 'Borrowed') AS status 
          FROM books b 
          LEFT JOIN borrow_log bl ON b.id = bl.book_id AND bl.returned_at IS NULL 
          WHERE b.name LIKE ? OR b.author LIKE ? 
          ORDER BY b.created_at DESC";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("SQL error: " . $conn->error);
}
$searchParam = "%$search%";
$stmt->bind_param("ss", $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();
$books = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
       function confirmDelete(url) {
            const confirmAction = confirm("Are you sure you want to delete this book?");
            if (confirmAction) {
                window.location.href = url; // Redirect to the delete book page if confirmed
            }
        }

        function showQRCode(src) {
            const popup = document.getElementById('qrPopup');
            document.getElementById('qrImage').src = src;
            popup.classList.remove('hidden');
        }

        function closePopup() {
            document.getElementById('qrPopup').classList.add('hidden');
        }
    </script>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200">
<div class="min-h-screen flex flex-col md:flex-row">
<?php $current_page = 'book'; include '../menu/sidebar_admin.php'; ?>
    <div class="flex-1 p-4 md:p-6 w-full bg-white rounded-lg shadow-md m-5">

    <div class="flex justify-between items-center mb-4">
        <h1 class="text-3xl font-bold">Book List</h1>
        <a href="add_book.php" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-blue-700">+ Create New Book</a>
    </div>

    <form method="GET" class="mb-6">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or author" class="border p-2 rounded w-1/2">
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded ml-2 hover:bg-gray-900">Search</button>
    </form>

    <div class="overflow-auto bg-white p-4 rounded shadow">
        <table class="min-w-full table-auto text-left">
            <thead>
                <tr class="bg-gray-200">
                    <th class="px-4 py-2">Book Name</th>
                    <th class="px-4 py-2">Author</th>
                    <th class="px-4 py-2">Pages</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">QR Code</th>
                    <th class="px-4 py-2">Created Date</th>
                    <th class="px-4 py-2">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book): ?>
                <tr class="border-b">
                    <td class="px-4 py-2"><?= htmlspecialchars($book['name']) ?></td>
                    <td class="px-4 py-2"><?= htmlspecialchars($book['author']) ?></td>
                    <td class="px-4 py-2"><?= htmlspecialchars($book['pages']) ?></td>
                    <td class="px-4 py-2">
                        <?= $book['available'] === 0 ? '<span class="text-red-600 font-semibold bg-red-100 rounded-3xl p-1">Borrowed</span>' : '<span class="text-green-600 font-semibold bg-green-100 rounded-3xl p-1">Available</span>' ?>
                    </td>
                    <td class="px-4 py-2">
                        <img src="../uploads/qrcode/<?= $book['qrcode'] ?>" alt="QR Code" class="w-12 h-12 cursor-pointer" onclick="showQRCode('../uploads/qrcode/<?= $book['qrcode'] ?>')">
                    </td>
                    <td class="px-4 py-2"><?= date("Y-m-d", strtotime($book['created_at'])) ?></td>
                    <td class="px-4 py-2 space-x-2">
                        <a href="update_book.php?id=<?= $book['id'] ?>" class="text-blue-600 hover:underline">✏️</a>
                        <a href="javascript:void(0);" onclick="confirmDelete('delete_book.php?id=<?= $book['id'] ?>')" class="text-red-600 hover:underline">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($books)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-gray-500">No books found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- QR Code Popup -->
    <div id="qrPopup" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded shadow-md relative">
            <button onclick="closePopup()" class="absolute top-2 right-2 text-gray-600 text-xl">&times;</button>
            <img id="qrImage" src="" alt="QR Code" class="w-64 h-64">
        </div>
    </div>

</body>

</html>
