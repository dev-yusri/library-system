<?php
session_start();
include 'db.php';

$loginError = '';
$loginSuccess = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $sql = "SELECT * FROM users WHERE email = ? AND role = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role'] = $row['role'];
            $loginSuccess = true;

            if ($row['role'] === 'student') {
                echo "<script>
                        setTimeout(() => {
                            window.location.href = 'home.php';
                        }, 2000);
                      </script>";
            } elseif ($row['role'] === 'librarian') {
                echo "<script>
                        setTimeout(() => {
                            window.location.href = 'admin/admin_dashboard.php';
                        }, 2000);
                      </script>";
            }
        } else {
            $loginError = 'Incorrect password.';
        }
    } else {
        $loginError = 'No account found for this email and role.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | Library System</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 ">
    <div class="flex items-center justify-center min-h-screen">

  <form method="POST" onsubmit="return handleLogin(event)" class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
    <h2 class="text-2xl font-bold text-center mb-6 text-indigo-700">Library Login</h2>

    <input type="email" name="email" placeholder="Email"
      class="w-full px-4 py-2 mb-4 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-400"
      required />

      <div class="relative mb-4">
  <input type="password" name="password" placeholder="Password" id="passwordInput"
    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-400 pr-10"
    required />
  <button type="button" onclick="togglePassword()" 
    class="absolute inset-y-0 right-3 flex items-center text-gray-500 focus:outline-none">
    <i id="eyeIcon" class="fas fa-eye"></i>
  </button>
</div>

    <select name="role"
      class="w-full px-4 py-2 mb-4 border border-gray-300 rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-indigo-400"
      required>
      <option value="">Select Role</option>
      <option value="student">Student</option>
      <option value="librarian">Librarian</option>
    </select>

    <div class="text-sm text-right mb-4">
      <a href="forgot_password.php" class="text-indigo-600 hover:underline">Forgot password?</a>
    </div>

    <button type="submit" id="loginBtn"
      class="w-full bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 transition disabled:opacity-50">
      Login
    </button>

    <div class="text-center mt-4 text-sm">
      Don’t have an account?
      <a href="register.php" class="text-indigo-600 font-medium hover:underline">Register here</a>
    </div>

    <?php if ($loginError): ?>
      <div id="errorPopup" class="mt-4 px-4 py-2 bg-red-100 text-red-700 rounded-md text-center">
        <?= $loginError ?>
      </div>
    <?php endif; ?>

    <?php if ($loginSuccess): ?>
      <div id="successPopup" class="mt-4 px-4 py-2 bg-green-100 text-green-700 rounded-md text-center">
        ✅ Login successful! Redirecting...
      </div>
    <?php endif; ?>
  </form>

  <script>
    function handleLogin(event) {
      const btn = document.getElementById('loginBtn');
      btn.disabled = true;
      btn.textContent = 'Logging in...';
      return true;
    }

    function togglePassword() {
    const input = document.getElementById('passwordInput');
    const icon = document.getElementById('eyeIcon');

    if (input.type === "password") {
      input.type = "text";
      icon.classList.remove("fa-eye");
      icon.classList.add("fa-eye-slash");
    } else {
      input.type = "password";
      icon.classList.remove("fa-eye-slash");
      icon.classList.add("fa-eye");
    }
  }

  function handleLogin(event) {
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.textContent = 'Logging in...';
    return true;
  }
  </script>

</body>
</html>
