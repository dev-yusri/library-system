<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendBorrowNotification($to, $username, $book, $borrowDate, $dueDate) {
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
        $mail->Subject = 'Book Borrowed Confirmation';
        $mail->Body = "
            <h3>Hello $username,</h3>
            <p>You borrowed a book:</p>
            <ul>
                <li><strong>Name:</strong> {$book['name']}</li>
                <li><strong>Author:</strong> {$book['author']}</li>
                <li><strong>Description:</strong> {$book['description']}</li>
                <li><strong>Borrowed:</strong> $borrowDate</li>
                <li><strong>Due:</strong> $dueDate</li>
            </ul>
            <p>Please return it on time. Thank you!</p>
        ";
        $mail->send();
    } catch (Exception $e) {
        error_log("Mail error: " . $mail->ErrorInfo);
    }
}

include 'db.php';

if (!isset($_GET['id'])) {
    echo "Book ID not provided."; exit;
}

$book_id = intval($_GET['id']);

// Handle borrow action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_borrow'])) {
    $stmt = $conn->prepare("UPDATE books SET available = 0 WHERE id = ? AND available = 1");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();

    $due_date = date('Y-m-d', strtotime('+7 days'));
    $stmt = $conn->prepare("INSERT INTO borrow_log (user_id, book_id, due_date) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $_SESSION['user_id'], $book_id, $due_date);
    $stmt->execute();

    // Fetch book
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $book_result = $stmt->get_result();
    $book = $book_result->fetch_assoc();

    // Get user
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user = $user_result->fetch_assoc();

    sendBorrowNotification($user['email'], $user['name'], $book, date('Y-m-d'), $due_date);

    header("Location: book_detail.php?id=$book_id&borrowed=success");
    exit;
}


// Check if user borrowed the book and hasn't returned it
$has_borrowed = false;
$stmt = $conn->prepare("SELECT * FROM borrow_log WHERE user_id = ? AND book_id = ? AND returned_at IS NULL");
$stmt->bind_param("ii", $_SESSION['user_id'], $book_id);
$stmt->execute();
$borrow_result = $stmt->get_result();
$borrow_entry = $borrow_result->fetch_assoc();
$has_borrowed = $borrow_entry ? true : false;

// Handle return action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_book'])) {
    // Update book availability
    $stmt = $conn->prepare("UPDATE books SET available = 1 WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();

    // Set return_date
    $stmt = $conn->prepare("UPDATE borrow_log SET returned_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $borrow_entry['id']);
    $stmt->execute();

    // Fetch user info
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Send return confirmation email
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
        $mail->addAddress($user['email'], $user['name']);

        $mail->isHTML(true);
        $mail->Subject = 'Book Return Confirmation';
        $mail->Body = "<h3>Hello {$user['name']},</h3><p>You have successfully returned the book <strong>{$book['name']}</strong>. Thank you!</p>";

        $mail->send();
    } catch (Exception $e) {
        error_log("Return email error: " . $mail->ErrorInfo);
    }

    // Refresh page to show review form
    header("Location: book_detail.php?id=$book_id&returned=1");
    exit;
}

// Fetch book
$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo "Book not found."; exit;
}
$book = $result->fetch_assoc();
// Check if user already submitted review
$stmt = $conn->prepare("SELECT * FROM book_rating WHERE book_id = ? AND user_id = ?");
$stmt->bind_param("ii", $book_id, $_SESSION['user_id']);
$stmt->execute();
$existing_review = $stmt->get_result()->fetch_assoc();
// Ratings
$stmt = $conn->prepare("SELECT r.rating, r.review, u.name FROM book_rating r JOIN users u ON r.user_id = u.user_id WHERE r.book_id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$ratings_result = $stmt->get_result();
$ratings = $ratings_result->fetch_all(MYSQLI_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($book['name']) ?> - Book Details</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    function confirmBorrow() {
      if (confirm("Are you sure you want to borrow this book?")) {
        document.getElementById('borrowForm').submit();
      }
    }
    function showQRCode(src) {
    document.getElementById('qrImage').src = src;
    document.getElementById('qrModal').classList.remove('hidden');
    document.getElementById('qrModal').classList.add('flex');
  }

  function closeQRCode() {
    document.getElementById('qrModal').classList.add('hidden');
    document.getElementById('qrModal').classList.remove('flex');
    document.getElementById('qrImage').src = '';
  }
  </script>
</head>
<body class="bg-gray-100 text-gray-800">

  <div class="flex flex-col md:flex-row min-h-screen">
    <?php $current_page = 'book'; include 'menu/sidebar.php'; ?>
    <main class="flex-1 p-6">
  <div class="max-w-5xl mx-auto bg-white p-6 rounded-xl shadow-md">
    <?php if (isset($_GET['borrowed']) && $_GET['borrowed'] == 'success'): ?>
      <div class="mb-4 px-4 py-3 bg-green-100 text-green-700 rounded-lg">
        ✅ Book borrowed successfully!
      </div>
    <?php endif; ?>

    <div class="grid md:grid-cols-2 gap-6">
      <!-- Clickable image with zoom modal -->
      <div class="relative">
        <img src="uploads/book-image/<?= htmlspecialchars($book['image_url']) ?>"
             alt="<?= htmlspecialchars($book['name']) ?>"
             class="w-full h-80 object-contain rounded-lg cursor-pointer"
             onclick="zoomImage('uploads/book-image/<?= htmlspecialchars($book['image_url']) ?>')">
      </div>

      <div>
        <div class="flex items-center justify-between mb-1">
          <h1 class="text-3xl font-bold"><?= htmlspecialchars($book['name']) ?></h1>
          <p>
            <span class="<?= $book['available'] ? 'text-xs text-green-600 bg-green-100 rounded inline-text p-1' : 'text-xs p-1 inline-text text-red-600 bg-red-100 rounded' ?> font-medium">
              <?= $book['available'] ? 'Available' : 'Borrowed' ?>
            </span>
          </p>
        </div>
        <p class="text-gray-600 mb-2"><strong><i class="fas fa-user"></i> &nbsp;</strong> <?= htmlspecialchars($book['author']) ?></p>

        <button onclick="showQRCode('uploads/qrcode/<?= $book['qrcode'] ?>')" class="bg-indigo-300 text-white text-xs px-3 py-1 rounded hover:bg-indigo-700 hover:text-white transition">
          View QR Code
        </button>

        <div id="qrModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
          <div class="bg-white rounded-lg p-6 shadow-lg relative max-w-sm w-full">
            <button onclick="closeQRCode()" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            <img id="qrImage" src="" alt="QR Code" class="w-64 h-64 mx-auto" />
          </div>
        </div>

        <br><br><hr><br>
        <p class="text-gray-500"><?= htmlspecialchars($book['description']) ?></p>

        <div class="flex gap-3 mt-6 flex-wrap">
          <a href="book.php" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition">
            <i class="fa fa-arrow-left"></i> Back to Books
          </a>

          <?php if ($book['available']): ?>
            <form method="POST" id="borrowForm" class="inline-block">
              <input type="hidden" name="confirm_borrow" value="1">
              <button type="button" onclick="confirmBorrow()" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                <i class="fa fa-book-reader"></i> Borrow Book
              </button>
            </form>
          <?php endif; ?>

          <?php if ($has_borrowed): ?>
            <form method="POST" onsubmit="return confirm('Are you sure you want to return this book?')">
              <input type="hidden" name="return_book" value="1">
              <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                <i class="fas fa-undo"></i> Return Book
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Image Zoom Modal -->
    <div id="imageZoomModal" class="fixed inset-0 bg-black bg-opacity-70 hidden items-center justify-center z-50">
      <div class="relative max-w-4xl w-full mx-auto p-4">
        <button onclick="closeZoom()" class="absolute top-2 right-2 text-white text-3xl">&times;</button>
        <img id="zoomedImage" src="" class="w-full h-auto max-h-[90vh] object-contain rounded shadow-lg" />
      </div>
    </div>
  </div>
             
        <!-- Ratings Section -->
        <div class="max-w-5xl mx-auto bg-white p-6 rounded-xl shadow-md mt-5">
        <div class="mt-12">
          <h3 class="text-2xl font-semibold mb-6 border-b pb-2">📚 Book Reviews</h3>

          <?php if (count($ratings) > 0): ?>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
              <?php foreach ($ratings as $rating): ?>
                <div class="bg-gray-50 p-4 rounded-lg shadow hover:shadow-md transition">
                  <h4 class="font-semibold text-indigo-600"><?= htmlspecialchars($rating['name']) ?></h4>
                  <div class="text-yellow-500 mb-2">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <i class="fas fa-star<?= $i <= $rating['rating'] ? '' : '-o' ?>"></i>
                    <?php endfor; ?>
                  </div>
                  <p class="text-gray-700 text-sm"><?= htmlspecialchars($rating['review']) ?: "<em>No comment provided.</em>" ?></p>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="text-gray-500">No reviews yet. Be the first to leave a rating!</p>
          <?php endif; ?>
          <?php if ((isset($_GET['returned']) && $_GET['returned'] == 1) && !$existing_review): ?>
  <div class="mt-10 border-t pt-6">
    <h3 class="text-xl font-semibold mb-4">⭐ Leave a Review</h3>
    <form method="POST" action="submit_review.php" class="space-y-4">
      <input type="hidden" name="book_id" value="<?= $book_id ?>">
      <div>
        <label for="rating" class="block font-medium">Rating (1-5):</label>
        <select name="rating" class="border rounded px-2 py-1 text-sm" required>
  <option value="">Rate</option>
  <option value="1">⭐</option>
  <option value="2">⭐⭐</option>
  <option value="3">⭐⭐⭐</option>
  <option value="4">⭐⭐⭐⭐</option>
  <option value="5">⭐⭐⭐⭐⭐</option>
</select>
      </div>
      <div>
        <label for="review" class="block font-medium">Review:</label>
        <textarea name="review" id="review" rows="3" class="w-full border rounded p-2" placeholder="Your thoughts..."></textarea>
      </div>
      <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Submit Review</button>
    </form>
  </div>
<?php endif; ?>
        </div>
      </div>
    </main>
  </div>
</body>
<script>
  function showQRCode(src) {
    document.getElementById('qrImage').src = src;
    document.getElementById('qrModal').classList.remove('hidden');
    document.getElementById('qrModal').classList.add('flex');
  }

  function closeQRCode() {
    document.getElementById('qrModal').classList.add('hidden');
    document.getElementById('qrModal').classList.remove('flex');
  }

  function zoomImage(src) {
    document.getElementById('zoomedImage').src = src;
    document.getElementById('imageZoomModal').classList.remove('hidden');
    document.getElementById('imageZoomModal').classList.add('flex');
  }

  function closeZoom() {
    document.getElementById('imageZoomModal').classList.add('hidden');
    document.getElementById('imageZoomModal').classList.remove('flex');
  }
</script>
</html>
