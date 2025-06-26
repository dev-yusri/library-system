<?php
session_start();
header('Content-Type: application/json');

require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// DB connection
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$book_id = $data['book_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$book_id || !$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing book ID or user session.']);
    exit;
}

// Fetch book info
$bookStmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$bookStmt->bind_param("i", $book_id);
$bookStmt->execute();
$book = $bookStmt->get_result()->fetch_assoc();

if (!$book) {
    echo json_encode(['success' => false, 'message' => 'Book not found.']);
    exit;
}

// Fetch user info
$userStmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

// Determine action
$isBorrowing = $book['available'] == 1;
$newStatus = $isBorrowing ? 0 : 1;
$borrowedBy = $isBorrowing ? $user_id : null;

// Update book availability
$updateStmt = $conn->prepare("UPDATE books SET available = ?, borrowed_by = ? WHERE id = ?");
$updateStmt->bind_param("iii", $newStatus, $borrowedBy, $book_id);
$updateStmt->execute();

// Send email notification
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['SMTP_USER'];
    $mail->Password = $_ENV['SMTP_PASS'];
    $mail->SMTPSecure = $_ENV['SMTP_SECURE'];
    $mail->Port = $_ENV['SMTP_PORT'];

    $mail->setFrom($_ENV['FROM_EMAIL'], $_ENV['FROM_NAME']);
    $mail->addAddress($user['email'], $user['name']);
    $mail->Subject = "Book " . ($isBorrowing ? "Borrowed" : "Returned") . ": {$book['title']}";

    $mailBody = "
        <p>Hi <strong>{$user['name']}</strong>,</p>
        <p>You have successfully <strong>" . ($isBorrowing ? "borrowed" : "returned") . "</strong> the book:</p>
        <ul>
            <li><strong>Title:</strong> {$book['title']}</li>
            <li><strong>Author:</strong> {$book['author']}</li>
        </ul>
        <p>Thank you for using the Library System.</p>
    ";

    $mail->isHTML(true);
    $mail->Body = $mailBody;
    $mail->AltBody = "Hi {$user['name']},\n\n"
        . "You have successfully " . ($isBorrowing ? "borrowed" : "returned") . " the book:\n"
        . "Title: {$book['title']}\n"
        . "Author: {$book['author']}\n\n"
        . "Thank you for using the Library System.";

    $mail->send();

    echo json_encode([
        'success' => true,
        'message' => "Book successfully " . ($isBorrowing ? "borrowed" : "returned") . ". Email notification sent."
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => "Book updated, but email not sent. Error: " . $mail->ErrorInfo
    ]);
}
?>
