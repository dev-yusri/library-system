<?php
include '../db.php'; // Your DB connection file

// Fetch all users
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'name';

$sql = "SELECT * FROM users WHERE $filter LIKE ?";
$stmt = $conn->prepare($sql);
$likeSearch = "%" . $search . "%";
$stmt->bind_param("s", $likeSearch);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200">
<div class="min-h-screen flex flex-col md:flex-row">
<?php $current_page = 'user'; include '../menu/sidebar_admin.php'; ?>
    <div class="flex-1 p-4 md:p-6 w-full bg-white rounded-lg shadow-md m-5">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">User List</h1>
            <button onclick="document.getElementById('addUserModal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add New User</button>
        </div>

        <!-- Search & Filter -->
        <form method="GET" class="flex gap-4 mb-4">
            <input type="text" name="search" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>" class="px-3 py-2 border rounded w-full">
            <select name="filter" class="px-3 py-2 border rounded">
                <option value="name" <?= $filter === 'name' ? 'selected' : '' ?>>Name</option>
                <option value="role" <?= $filter === 'role' ? 'selected' : '' ?>>Role</option>
            </select>
            <button class="bg-gray-800 text-white px-4 py-2 rounded">Search</button>
        </form>

        <!-- User Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border rounded shadow">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border">ID</th>
                        <th class="px-4 py-2 border">Name</th>
                        <th class="px-4 py-2 border">Email</th>
                        <th class="px-4 py-2 border">Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr class="text-center">
                            <td class="px-4 py-2 border"><?= $user['user_id'] ?></td>
                            <td class="px-4 py-2 border"><?= $user['name'] ?></td>
                            <td class="px-4 py-2 border"><?= $user['email'] ?></td>
                            <td class="px-4 py-2 border capitalize"><?= $user['role'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Add New User -->
    <div id="addUserModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded shadow-lg w-full max-w-md">
            <h2 class="text-xl font-semibold mb-4">Add New User</h2>
            <form action="add_user.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium">Name</label>
                    <input name="name" required class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium">Email</label>
                    <input type="email" name="email" required class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium">Password</label>
                    <input type="password" name="password" required class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium">Role</label>
                    <select name="role" required class="w-full px-3 py-2 border rounded">
                        <option value="">Select Role</option>
                        <option value="student">Student</option>
                        <option value="librarian">Librarian</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="bg-gray-300 px-4 py-2 rounded">Cancel</button>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add User</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
