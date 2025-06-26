<?php
require 'vendor/autoload.php';
require 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


$env = parse_ini_file('.env');


function setupMailer() {
    global $env;
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $env['SMTP_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $env['SMTP_USER'];
    $mail->Password   = $env['SMTP_PASS'];
    $mail->SMTPSecure = $env['SMTP_SECURE'];
    $mail->Port       = $env['SMTP_PORT'];
    $mail->setFrom($env['FROM_EMAIL'], $env['FROM_NAME']);
    return $mail;
}

$sql = "SELECT bl.*, u.name AS user_name, u.email AS user_email, b.name AS book_name
        FROM borrow_log bl
        JOIN users u ON u.user_id = bl.user_id
        JOIN books b ON b.id = bl.book_id
        WHERE bl.returned_at IS NULL 
          AND bl.due_date < CURDATE()
          AND bl.overdue_notified = 0";
$result = $conn->query($sql);

$librarians = [];
$librarianQuery = $conn->query("SELECT email FROM users WHERE role = 'librarian'");
while ($row = $librarianQuery->fetch_assoc()) {
    $librarians[] = $row['email'];
}

while ($row = $result->fetch_assoc()) {
    $mail = setupMailer();

    $user_email = $row['user_email'];
    $user_name = $row['user_name'];
    $book_name = $row['book_name'];
    $due_date = date("d M Y", strtotime($row['due_date']));

    $subject = "Overdue Book Reminder: $book_name";
    $body = "
        Hi $user_name,<br><br>
        Our records show that <strong>\"$book_name\"</strong> was due on <strong>$due_date</strong> and has not been returned yet.<br>
        Please return it as soon as possible to avoid any penalties.<br><br>
        Thank you,<br>Library Team
    ";

    try {
        $mail->addAddress($user_email);
        foreach ($librarians as $librarian) {
            $mail->addBCC($librarian);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();

        // Mark as notified
        $update = $conn->prepare("UPDATE borrow_log SET overdue_notified = 1 WHERE id = ?");
        $update->bind_param("i", $row['id']);
        $update->execute();

        echo "Email sent to $user_email<br>";
    } catch (Exception $e) {
        echo "Mailer Error to $user_email: {$mail->ErrorInfo}<br>";
    }
}

?>
