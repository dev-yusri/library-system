<?php
include 'db.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$showForm = false;

// Check token
if ($token) {
    $stmt = $conn->prepare("SELECT user_id, reset_token_expires FROM users WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $expires = strtotime($user['reset_token_expires']);
        if (time() <= $expires) {
            $showForm = true;
        } else {
            $error = "Reset token has expired. Please request a new one.";
        }
    } else {
        $error = "Invalid reset token.";
    }
} else {
    $error = "No token provided.";
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_password'])) {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        // Hash password
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE reset_token = ?");
        $update->bind_param("ss", $hashed, $token);
        if ($update->execute()) {
            $success = "Password has been reset. You can now <a href='index.php'>login</a>.";
            $showForm = false;
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | LIBOS</title>
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
            background: #fff;
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
        input[type="password"],
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
            color: #fff;
            border: none;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background-color: #357acc;
        }
        .popup {
            padding: 12px;
            margin-top: 20px;
            text-align: center;
            border-radius: 5px;
            font-size: 14px;
            width: 400px;
            box-sizing: border-box;
        }
        .popup.error {
            background: #f8d7da;
            color: #721c24;
        }
        .popup.success {
            background: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>

<?php if ($showForm): ?>
    <form method="POST">
        <h2>Reset Password</h2>
        <input type="password" name="new_password" placeholder="New Password" required />
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required />
        <button type="submit">Reset Password</button>
    </form>
<?php endif; ?>

<?php if ($error): ?>
    <div class="popup error"><?= $error ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="popup success"><?= $success ?></div>
<?php endif; ?>

</body>
</html>
