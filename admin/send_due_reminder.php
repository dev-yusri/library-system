<?php
require '../vendor/autoload.php';
require '../db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$env = parse_ini_file('../.env');

function setupMailer() {
    global $env;
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $env['SMTP_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $env['SMTP_USER'];
    $mail->Password = $env['SMTP_PASS'];
    $mail->SMTPSecure = $env['SMTP_SECURE'];
    $mail->Port = $env['SMTP_PORT'];
    $mail->setFrom($env['FROM_EMAIL'], $env['FROM_NAME']);
    return $mail;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_id'])) {
    $logId = intval($_POST['log_id']);

    $stmt = $conn->prepare("SELECT bl.*, u.name AS user_name, u.email AS user_email, b.name AS book_name
                            FROM borrow_log bl
                            JOIN users u ON u.user_id = bl.user_id
                            JOIN books b ON b.id = bl.book_id
                            WHERE bl.id = ?");
    $stmt->bind_param("i", $logId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row && !$row['returned_at']) {
        $dueDate = new DateTime($row['due_date']);
        $now = new DateTime();
        $diffDays = $now->diff($dueDate)->days;

        if ($now <= $dueDate && $diffDays <= 1 && !$row['due_soon_notified']) {
            $mail = setupMailer();
            $mail->addAddress($row['user_email']);
            $mail->isHTML(true);
            $mail->Subject = "Reminder: Return \"{$row['book_name']}\" Soon";
            $mail->Body = "
                Hi {$row['user_name']},<br><br>
                Just a friendly reminder: your borrowed book <strong>\"{$row['book_name']}\"</strong> is due on <strong>{$row['due_date']}</strong>.<br>
                Please return it on or before the due date to avoid any penalties.<br><br>
                Thank you,<br>Library Team
            ";

            try {
                $mail->send();
                $update = $conn->prepare("UPDATE borrow_log SET due_soon_notified = 1 WHERE id = ?");
                $update->bind_param("i", $logId);
                $update->execute();
                header("Location: book_status.php?msg=Due reminder sent");
            } catch (Exception $e) {
                echo "Error sending email: " . $mail->ErrorInfo;
            }
        } else {
            echo "Book is not due soon or already notified.";
        }
    } else {
        echo "Book already returned or invalid ID.";
    }
}
?>
