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
        $mail = setupMailer();
        $mail->addAddress($row['user_email']);
        $mail->isHTML(true);
        $mail->Subject = "Reminder: Please return \"{$row['book_name']}\".";
        $mail->Body = "
            Hi {$row['user_name']},<br><br>
            This is a final warning to return <strong>\"{$row['book_name']}\"</strong> which is already pass the due date on <strong>{$row['due_date']}</strong>.<br>
            Please return it ASAP.<br><br>
            Thank you,<br>Library Team
        ";

        try {
            $mail->send();
            $update = $conn->prepare("UPDATE borrow_log SET due_notified_again = 1 WHERE id = ?");
            $update->bind_param("i", $logId);
            $update->execute();
            header("Location: book_status.php?msg=Reminder sent");
        } catch (Exception $e) {
            echo "Error sending email: " . $mail->ErrorInfo;
        }
    } else {
        echo "Book already returned or invalid ID.";
    }
}
?>
