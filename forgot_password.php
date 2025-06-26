<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'includes/PHPMailer/src/PHPMailer.php';
require 'includes/PHPMailer/src/SMTP.php';
require 'includes/PHPMailer/src/Exception.php';
require 'config.php'; 
require 'db.php';

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // Generate secure reset token
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

            // Save token in DB
            $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
            $update->bind_param("sss", $token, $expires, $email);
            $update->execute();

            // Prepare reset link
            $resetLink = "http://localhost/LIBOS/reset_password.php?token=$token";

            // Send email
            $mail = new PHPMailer(true);
            try {
                // SMTP config (Gmail)
                $mail->isSMTP();
                $mail->Host       = $_ENV['SMTP_HOST'];     // smtp.gmail.com
                $mail->SMTPAuth   = true;
                $mail->Username   = $_ENV['SMTP_USER'];     // your Gmail address
                $mail->Password   = $_ENV['SMTP_PASS'];     // App password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = $_ENV['SMTP_PORT'];     // 587

                // Email content
                $mail->setFrom($_ENV['SMTP_USER'], $_ENV['SMTP_NAME']);
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Reset Your Password - LIBOS';
                $mail->Body    = "Hi, <br><br>Click the link below to reset your password. This link will expire in 1 hour.<br><br><a href='$resetLink'>$resetLink</a><br><br>Regards,<br>LIBOS Support";

                $mail->send();
                $success = "A password reset link has been sent to your email.";
            } catch (Exception $e) {
                $error = "Email could not be sent. Error: " . $mail->ErrorInfo;
            }
        } else {
            $error = "No account found with that email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | LIBOS</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #eef1f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        form {
            background: white;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            width: 400px;
            box-sizing: border-box;
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }
        input[type="email"],
        button {
            width: 100%;
            margin: 12px 0;
            padding: 12px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
        }
        button {
            background-color: rgb(67 56 202 / var(--tw-text-opacity, 1));
            color: white;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background-color: #357acc;
        }
        .popup {
            display: none;
            padding: 12px;
            margin-top: 15px;
            text-align: center;
            border-radius: 5px;
            font-size: 14px;
        }
        .popup.error {
            background: #f8d7da;
            color: #721c24;
        }
        .popup.success {
            background: #d4edda;
            color: #155724;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 10px;
            font-size: 13px;
        }
        .back-link a {
            color: #4a90e2;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<form method="POST">
    <h2>Forgot Password</h2>
    <input type="email" name="email" placeholder="Enter your email" required />
    <button type="submit">Send Reset Link</button>

    <?php if ($error): ?>
        <div class="popup error" id="errorPopup"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="popup success" id="successPopup"><?= $success ?></div>
    <?php endif; ?>

    <div class="back-link">
        Remember your password? <a href="index.php">Login here</a>
    </div>
</form>

<script>
window.onload = () => {
    const errorPopup = document.getElementById('errorPopup');
    const successPopup = document.getElementById('successPopup');
    if (errorPopup) errorPopup.style.display = 'block';
    if (successPopup) successPopup.style.display = 'block';
};
</script>

</body>
</html>
