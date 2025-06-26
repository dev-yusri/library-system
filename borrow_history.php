<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require 'menu/sidebar.php';
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'db.php';

$user_id = $_SESSION['user_id'];

// Fetch user email and name
$stmt = $conn->prepare("SELECT name, email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

function sendReturnNotification($to, $username, $book, $borrowDate, $dueDate) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER'];
        $mail->Password = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = 'tls';
        $mail->Port = $_ENV['SMTP_PORT'];

        $mail->setFrom($_ENV['SMTP_USER'], 'Library System');
        $mail->addAddress($to, $username);

        $mail->isHTML(true);
        $mail->Subject = 'Book Returned Confirmation';
        $mail->Body = "
            <h3>Hello $username,</h3>
            <p>You returned a book:</p>
            <ul>
                <li><strong>Name:</strong> {$book['name']}</li>
                <li><strong>Author:</strong> {$book['author']}</li>
                <li><strong>Description:</strong> {$book['description']}</li>
                <li><strong>Borrowed:</strong> $borrowDate</li>
                <li><strong>Due:</strong> $dueDate</li>
            </ul>
            <p>Thank you for using the Library System!</p>
        ";
        $mail->send();
    } catch (Exception $e) {
        error_log("Mail error: " . $mail->ErrorInfo);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['return_book_id'])) {
        $book_id = $_POST['return_book_id'];

        // Get book info
        $stmt = $conn->prepare("SELECT name, author, description FROM books WHERE id = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $book = $stmt->get_result()->fetch_assoc();

        // Get borrow date (and optionally due date)
        $stmt = $conn->prepare("SELECT borrowed_at, DATE_ADD(borrowed_at, INTERVAL 7 DAY) as due_date FROM borrow_log WHERE user_id = ? AND book_id = ? AND returned_at IS NULL");
        $stmt->bind_param("ii", $user_id, $book_id);
        $stmt->execute();
        $borrow_info = $stmt->get_result()->fetch_assoc();

        if ($borrow_info) {
            $borrowed_at = $borrow_info['borrowed_at'];
            $due_date = $borrow_info['due_date'];

            // Update return time
            $stmt = $conn->prepare("UPDATE borrow_log SET returned_at = NOW() WHERE user_id = ? AND book_id = ? AND returned_at IS NULL");
            $stmt->bind_param("ii", $user_id, $book_id);
            $stmt->execute();

            // Set book as available
            $stmt = $conn->prepare("UPDATE books SET available = 1 WHERE id = ?");
            $stmt->bind_param("i", $book_id);
            $stmt->execute();

            // Send email notification
            sendReturnNotification($user['email'], $user['name'], $book, $borrowed_at, $due_date);
        }
    }

    // Handle review
    if (isset($_POST['review_book_id'], $_POST['rating'], $_POST['review'])) {
        $book_id = $_POST['review_book_id'];
        $rating = $_POST['rating'];
        $review = $_POST['review'];

        $stmt = $conn->prepare("SELECT * FROM book_rating WHERE user_id = ? AND book_id = ?");
        $stmt->bind_param("ii", $user_id, $book_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO book_rating (user_id, book_id, rating, review) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $user_id, $book_id, $rating, $review);
        } else {
            $stmt = $conn->prepare("UPDATE book_rating SET rating = ?, review = ? WHERE user_id = ? AND book_id = ?");
            $stmt->bind_param("ssii", $rating, $review, $user_id, $book_id);
        }
        $stmt->execute();
    }
}


// Fetch borrow history
$sql = "SELECT bl.book_id, b.name AS book_name, b.author, b.image_url, bl.borrowed_at, bl.returned_at,
       (SELECT rating FROM book_rating WHERE user_id = bl.user_id AND book_id = bl.book_id) AS rating,
       (SELECT review FROM book_rating WHERE user_id = bl.user_id AND book_id = bl.book_id) AS review
       FROM borrow_log bl
       JOIN books b ON bl.book_id = b.id
       WHERE bl.user_id = ?
       ORDER BY bl.borrowed_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$borrow_history = [];
while ($row = $result->fetch_assoc()) {
    $borrow_history[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Borrow History</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="flex justify-between items-center bg-white p-4 shadow-md">
    <h2 class="text-2xl font-bold text-indigo-700">My Dashboard</h2>
    <div class="relative inline-block text-left">
        <button onclick="toggleDropdown()" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
            My Account <i class="fas fa-chevron-down ml-2"></i>
        </button>
        <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded shadow-lg">
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
    <div class="flex-1 p-4 m-5">
        <div class="flex justify-between items-center bg-white p-4 shadow-md">
            <h2 class="text-2xl font-bold text-indigo-700">My Borrow History</h2>
        </div>

        <div class="mt-6 bg-white p-4 rounded-lg shadow-md overflow-x-auto">
  <?php if (empty($borrow_history)): ?>
    <p class="text-center text-lg text-gray-500">You haven't borrowed any books yet.</p>
  <?php else: ?>
    <table class="w-full table-auto min-w-[600px]">
      <thead>
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700">Book</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700">Author</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700">Borrowed On</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700">Returned At</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700">Status</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($borrow_history as $borrow): ?>
                            <tr class="border-t">
                                <td class="px-4 py-2"><?= htmlspecialchars($borrow['book_name']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($borrow['author']) ?></td>
                                <td class="px-4 py-2"><?= date('F j, Y', strtotime($borrow['borrowed_at'])) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($borrow['returned_at']) ?></td>
                                <td class="px-4 py-2">
                                    <?php if ($borrow['returned_at']): ?>
                                        <span class="text-green-600 font-semibold">Returned</span>
                                    <?php else: ?>
                                        <span class="text-yellow-600 font-semibold">Borrowed</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="px-4 py-2 space-x-2">
                                    <?php if (!$borrow['returned_at']): ?>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to return this book?');" class="inline">
                                            <input type="hidden" name="return_book_id" value="<?= $borrow['book_id'] ?>">
                                            <button type="submit" class="text-sm bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Return</button>
                                        </form>
                                    <?php else: ?>
                                        <?php if ($borrow['rating']): ?>
                                            <span class="text-sm text-yellow-500">
  You rated:
  <?php for ($i = 1; $i <= 5; $i++): ?>
    <i class="fas fa-star<?= $i <= $borrow['rating'] ? '' : '-o' ?>"></i>
  <?php endfor; ?>
</span>
                                            <p class="text-sm text-gray-600"><?= htmlspecialchars($borrow['review']) ?></p>
                                        <?php else: ?>
                                            <form method="POST" class="inline-flex space-x-2">
                                                <input type="hidden" name="review_book_id" value="<?= $borrow['book_id'] ?>">
                                                <select name="rating" class="border rounded px-2 py-1 text-sm">
                                                    <option value="">Rate</option>
                                                    <option value="1">⭐</option>
                                                    <option value="2">⭐⭐</option>
                                                    <option value="3">⭐⭐⭐</option>
                                                    <option value="4">⭐⭐⭐⭐</option>
                                                    <option value="5">⭐⭐⭐⭐⭐</option>
                                                </select>
                                                <textarea name="review" class="border rounded px-2 py-1 text-sm" placeholder="Leave a comment"></textarea>
                                                <button type="submit" class="bg-indigo-500 text-white px-2 py-1 rounded hover:bg-indigo-600 text-sm">Submit</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleDropdown() {
    const menu = document.getElementById("dropdownMenu");
    menu.classList.toggle("hidden");
}
document.addEventListener('click', function (e) {
    if (!e.target.closest('.relative')) {
        document.getElementById("dropdownMenu").classList.add('hidden');
    }
});
</script>
</body>
</html>
