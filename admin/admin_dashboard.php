<?php
session_start();
include '../db.php';

// Fetch statistics
$sql_users = "SELECT role, COUNT(*) AS count FROM users GROUP BY role";
$result_users = $conn->query($sql_users);

if (!$result_users) {
    die("Error in SQL query: " . $conn->error); 
}

$users = [];
while ($row = $result_users->fetch_assoc()) {
    $users[$row['role']] = $row['count'];
}

$total_students = isset($users['student']) ? $users['student'] : 0;
$total_librarian = isset($users['librarian']) ? $users['librarian'] : 0;

$sql_books = "SELECT COUNT(*) AS count FROM books";
$result_books = $conn->query($sql_books);

if (!$result_books) {
    die("Error in SQL query: " . $conn->error);
}

$total_books = $result_books->fetch_assoc()['count'];

$sql_borrowed = "SELECT COUNT(*) AS count FROM borrow_log";
$result_borrowed = $conn->query($sql_borrowed);

if (!$result_borrowed) {
    die("Error in SQL query: " . $conn->error); 
}

$total_borrowed = $result_borrowed->fetch_assoc()['count'];

$today = date('Y-m-d');
$sql_overdue = "SELECT COUNT(*) as total FROM borrow_log WHERE returned_at IS NULL AND due_date < '$today'";
$result_overdue = $conn->query($sql_overdue);
$total_overdue = $result_overdue->fetch_assoc()['total'];

// Fetch total ratings for books
$sql_total_ratings = "SELECT book_id, COUNT(*) AS total_ratings FROM book_rating GROUP BY book_id";
$result_total_ratings = $conn->query($sql_total_ratings);

if (!$result_total_ratings) {
    die("Error in SQL query: " . $conn->error); // Displays any SQL errors
}

$total_ratings = [];
while ($row = $result_total_ratings->fetch_assoc()) {
    $total_ratings[$row['book_id']] = $row['total_ratings'];
}

// Fetch user and book details
$sql_users_details = "SELECT user_id, name, role FROM users";  
$result_users_details = $conn->query($sql_users_details);

if (!$result_users_details) {
    die("Error in SQL query: " . $conn->error); // Displays any SQL errors
}

$users_details = $result_users_details->fetch_all(MYSQLI_ASSOC);

$sql_books_details = "SELECT id, name, author FROM books LIMIT 5";  // 'name' is correct here
$result_books_details = $conn->query($sql_books_details);

if (!$result_books_details) {
    die("Error in SQL query: " . $conn->error); // Displays any SQL errors
}

$books_details = $result_books_details->fetch_all(MYSQLI_ASSOC);

$sql_user_ratings = "
    SELECT DISTINCT u.name AS user_name, b.name AS book_name, r.rating, r.review
    FROM book_rating r
    JOIN users u ON r.user_id = u.user_id
    JOIN books b ON r.book_id = b.id
    WHERE EXISTS (
        SELECT 1 FROM borrow_log bl
        WHERE bl.user_id = r.user_id AND bl.book_id = r.book_id AND bl.returned_at IS NOT NULL
    )
    LIMIT 5
";

$result_user_ratings = $conn->query($sql_user_ratings);

if (!$result_user_ratings) {
    die("Error in SQL query: " . $conn->error); 
}

$user_ratings = $result_user_ratings->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200">
<div class="min-h-screen flex flex-col md:flex-row">
<?php $current_page = 'home'; include '../menu/sidebar_admin.php'; ?>
    <div class="flex-1 p-4 md:p-6 w-full">
        <h1 class="text-3xl font-semibold text-gray-800 mb-6"></h1>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <!-- Total Students -->
    <div class="bg-gradient-to-br from-blue-100 to-blue-300 p-6 rounded-2xl shadow-lg transition-transform hover:scale-105">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-lg font-medium text-blue-900">Total Students</h3>
            <svg class="w-6 h-6 text-blue-800" fill="none" stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422A12.083 12.083 0 0112 21a12.083 12.083 0 01-6.16-10.422L12 14z"/></svg>
        </div>
        <p class="text-3xl font-bold text-blue-900"><?= $total_students ?></p>
    </div>

    <!-- Total Librarians -->
    <div class="bg-gradient-to-br from-purple-100 to-purple-300 p-6 rounded-2xl shadow-lg transition-transform hover:scale-105">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-lg font-medium text-purple-900">Total Librarians</h3>
            <svg class="w-6 h-6 text-purple-800" fill="none" stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422A12.083 12.083 0 0112 21a12.083 12.083 0 01-6.16-10.422L12 14z"/></svg>
        </div>
        <p class="text-3xl font-bold text-purple-900"><?= $total_librarian ?></p>
    </div>

    <!-- Total Books -->
    <div class="bg-gradient-to-br from-green-100 to-green-300 p-6 rounded-2xl shadow-lg transition-transform hover:scale-105">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-lg font-medium text-green-900">Total Books</h3>
            <svg class="w-6 h-6 text-green-800" fill="none" stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422A12.083 12.083 0 0112 21a12.083 12.083 0 01-6.16-10.422L12 14z"/></svg>
        </div>
        <p class="text-3xl font-bold text-green-900"><?= $total_books ?></p>
    </div>
</div>

<!-- Second Row -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <!-- Borrowed Books -->
    <div class="bg-gradient-to-br from-yellow-100 to-yellow-300 p-6 rounded-2xl shadow-lg transition-transform hover:scale-105">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-lg font-medium text-yellow-900">Total Borrowed Books</h3>
            <svg class="w-6 h-6 text-yellow-800" fill="none" stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422A12.083 12.083 0 0112 21a12.083 12.083 0 01-6.16-10.422L12 14z"/></svg>
        </div>
        <p class="text-3xl font-bold text-yellow-900"><?= $total_borrowed ?></p>
    </div>
    <div class="bg-gradient-to-br from-red-100 to-red-300 p-6 rounded-2xl shadow-lg transition-transform hover:scale-105">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-lg font-medium text-yellow-900">Total Overdue Books</h3>
            <svg class="w-6 h-6 text-yellow-800" fill="none" stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422A12.083 12.083 0 0112 21a12.083 12.083 0 01-6.16-10.422L12 14z"/></svg>
        </div>
        <p class="text-3xl font-bold text-yellow-900"><?= $total_overdue ?></p>
    </div>
    <!-- Book Ratings -->
    <div class="bg-gradient-to-br from-black-100 to-black-300 p-6 rounded-2xl shadow-lg transition-transform hover:scale-105">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-lg font-medium text-pink-900">Total Book Ratings</h3>
            <svg class="w-6 h-6 text-pink-800" fill="none" stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422A12.083 12.083 0 0112 21a12.083 12.083 0 01-6.16-10.422L12 14z"/></svg>
        </div>
        <p class="text-3xl font-bold text-pink-900"><?= array_sum($total_ratings) ?></p>
    </div>
</div>




        <!-- User Details -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <h3 class="text-xl font-semibold mb-4">User Details</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-2 px-4 border-b text-left">ID</th>
                    <th class="py-2 px-4 border-b text-left">Name</th>
                    <th class="py-2 px-4 border-b text-left">Role</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users_details)): ?>
                    <?php foreach ($users_details as $user): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($user['user_id']) ?></td>
                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($user['name']) ?></td>
                            <td class="py-2 px-4 border-b capitalize"><?= htmlspecialchars($user['role']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="py-4 px-4 text-center text-gray-500">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


        <!-- Book Details -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <h3 class="text-xl font-semibold mb-4">Book Details</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-2 px-4 border-b text-left">ID</th>
                    <th class="py-2 px-4 border-b text-left">Title</th>
                    <th class="py-2 px-4 border-b text-left">Author</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($books_details)): ?>
                    <?php foreach ($books_details as $book): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($book['id']) ?></td>
                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($book['name']) ?></td>
                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($book['author']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="py-4 px-4 text-center text-gray-500">No books found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


        <!-- User Ratings for Borrowed Books -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <h3 class="text-xl font-semibold mb-4">User Ratings on Borrowed Books</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-2 px-4 border-b text-left">User</th>
                    <th class="py-2 px-4 border-b text-left">Book</th>
                    <th class="py-2 px-4 border-b text-left">Rating</th>
                    <th class="py-2 px-4 border-b text-left">Review</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($user_ratings)): ?>
                    <?php foreach ($user_ratings as $rating): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($rating['user_name']) ?></td>
                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($rating['book_name']) ?></td>
                            <td class="py-2 px-4 border-b text-yellow-500">  <?php for ($i = 1; $i <= 5; $i++): ?>
    <i class="fas fa-star<?= $i <= $rating['rating'] ? '' : '-o' ?>"></i>
  <?php endfor; ?></td>
                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($rating['review']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="py-4 px-4 text-center text-gray-500">No ratings found yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
