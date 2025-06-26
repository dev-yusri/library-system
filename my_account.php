<?php
session_start();
require 'db.php';
require 'menu/sidebar.php'; // Your DB connection file

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");  // Redirect to login if not logged in
    exit();
}

// Get the current user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT name, email, password FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Update user details
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_name = $_POST['name'];
    $new_password = $_POST['password'];

    // Password hash
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    } else {
        $hashed_password = $user['password'];  // Keep the old password if no new password
    }

    // Update query
    $update_sql = "UPDATE users SET name = ?, password = ? WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssi", $new_name, $hashed_password, $user_id);

    if ($update_stmt->execute()) {
        $message = "Account details updated successfully!";
    } else {
        $message = "Error updating details!";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<!-- Navbar or Header -->
<div class="flex justify-between items-center bg-white p-4 shadow-md">
    <h2 class="text-2xl font-bold text-indigo-700">My Dashboard</h2>
    <div class="relative inline-block text-left">
        <button onclick="toggleDropdown()" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
            <i class="fas fa-user mr-2"></i> My Account <i class="fas fa-chevron-down ml-2"></i>
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
<!-- My Account Form -->
<div class="flex-1 p-4 m-5 md:p-6 w-full mt-8 bg-white rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Update Your Account Information</h2>

    <?php if (isset($message)) { echo "<div class='mb-4 text-green-500'>$message</div>"; } ?>

    <form action="my_account.php" method="POST">
        <div class="mb-4">
            <label for="name" class="block text-gray-700 font-bold">Full Name</label>
            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" class="w-full p-3 border border-gray-300 rounded-md mt-2" required>
        </div>

        <div class="mb-4">
            <label for="password" class="block text-gray-700 font-bold">New Password</label>
            <input type="password" name="password" id="password" placeholder="Leave blank if you don't want to change" class="w-full p-3 border border-gray-300 rounded-md mt-2">
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Update Account</button>
        </div>
    </form>
</div>

<!-- Script to toggle dropdown -->
<script>
    function toggleDropdown() {
        const menu = document.getElementById("dropdownMenu");
        menu.classList.toggle("hidden");
    }

    document.addEventListener('click', function (e) {
        const button = document.querySelector('[onclick="toggleDropdown()"]');
        const menu = document.getElementById("dropdownMenu");
        if (!e.target.closest('.relative')) {
            menu.classList.add('hidden');
        }
    });
</script>

</body>
</html>
