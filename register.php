<?php
session_start();
include 'db.php';

$success = false;
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $role = $_POST['role'];

    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword) || empty($role)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!preg_match("/^(?=.*[A-Z])(?=.*\d).{8,}$/", $password)) {
        $error = "Password must be at least 8 characters, include one uppercase letter and one number.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email is already registered.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

            if ($stmt->execute()) {
                $success = true;
            } else {
                $error = "Error creating account.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register | Library System</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f6f8fa;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    form {
      background: #fff;
      padding: 40px 30px;
      border-radius: 12px;
      box-shadow: 0 0 20px rgba(0,0,0,0.08);
      width: 380px;
      position: relative;
    }
    form h2 {
      text-align: center;
      margin-bottom: 25px;
      color: #333;
    }
    .input-group {
  position: relative;
  width: 100%;
  margin-bottom: 10px;
}

.input-group input {
  width: 100%;
  padding: 12px;
  padding-right: 40px; /* make room for the eye icon */
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  box-sizing: border-box;
}
    select {
  width: 100%;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  box-sizing: border-box;
}
    .toggle-password {
  position: absolute;
  top: 50%;
  right: 12px;
  transform: translateY(-50%);
  cursor: pointer;
  color: #888;
  z-index: 10;
}
    input:focus, select:focus {
      border-color: #4a90e2;
      outline: none;
    }
    button {
  width: 100%;
  padding: 12px;
  background-color: rgb(67 56 202);
  border: none;
  color: white;
  border-radius: 6px;
  font-size: 15px;
  cursor: pointer;
  transition: background-color 0.3s;
  box-sizing: border-box;
}
    button:hover {
      background-color: #357ABD;
    }
    button.loading {
      background: #bbb;
      cursor: not-allowed;
    }
    a {
      display: block;
      text-align: center;
      margin-top: 10px;
      font-size: 13px;
      color: #4a90e2;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }
    .popup {
      display: none;
      padding: 12px;
      margin-top: 15px;
      text-align: center;
      border-radius: 6px;
    }
    .popup.error {
      background: #fdecea;
      color: #d93025;
    }
    .popup.success {
      background: #e6f4ea;
      color: #188038;
    }
  </style>
</head>
<body>

<form method="POST" onsubmit="return handleRegister(event)">
  <h2 style="color:rgb(67 56 202);">Create Account</h2>

  <div class="input-group">
    <input type="text" name="name" placeholder="Full Name" required />
  </div>

  <div class="input-group">
    <input type="email" name="email" placeholder="Email" required />
  </div>

  <div class="input-group">
    <input type="password" name="password" placeholder="Password" id="password" required />
    <i class="fas fa-eye toggle-password" onclick="toggleVisibility('password', this)"></i>
  </div>

  <div class="input-group">
    <input type="password" name="confirm_password" placeholder="Confirm Password" id="confirm_password" required />
    <i class="fas fa-eye toggle-password" onclick="toggleVisibility('confirm_password', this)"></i>
  </div>

  <div class="input-group">
    <select name="role" required>
      <option value="">Select Role</option>
      <option value="student">Student</option>
      <option value="librarian">Librarian</option>
    </select>
  </div>

  <button type="submit" id="registerBtn">Register</button>
  <p style="text-align:center; font-size:13px;">Already have an account? <a href="index.php">Login here</a></p>

  <?php if ($error): ?>
    <div class="popup error" id="errorPopup"><?= $error ?></div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="popup success text-indigo-600" id="successPopup">Account created! <a href="index.php">Login here</a>.</div>
  <?php endif; ?>
</form>

<script>
function toggleVisibility(fieldId, icon) {
  const field = document.getElementById(fieldId);
  if (field.type === "password") {
    field.type = "text";
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    field.type = "password";
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}

function handleRegister(event) {
  const btn = document.getElementById('registerBtn');
  btn.classList.add('loading');
  btn.innerText = 'Registering...';
  return true;
}

window.onload = () => {
  const errorPopup = document.getElementById('errorPopup');
  const successPopup = document.getElementById('successPopup');
  if (errorPopup) errorPopup.style.display = 'block';
  if (successPopup) successPopup.style.display = 'block';
};
</script>

</body>
</html>
