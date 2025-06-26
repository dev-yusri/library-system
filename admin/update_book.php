<?php
include '../db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid book ID.");
}

// Fetch book details
$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();

if (!$book) {
    die("Book not found.");
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $author = $_POST['author'];
    $pages = $_POST['pages'];
    $days = $_POST['days_available'];
    $description = $_POST['description'];

    $image = $book['image_url'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "../uploads/book-image/";
        $imageName = time() . "_" . basename($_FILES['image']['name']);
        $targetFilePath = $targetDir . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
            $image = $imageName;
        } else {
            $error = "Failed to upload new image.";
        }
    }

    $update = $conn->prepare("UPDATE books SET name=?, author=?, pages=?, image_url=?, days_available=?, description=? WHERE id=?");
    $update->bind_param("ssisssi", $name, $author, $pages, $image, $days, $description, $id);

    if ($update->execute()) {
        header("Location: book_list.php");
        exit();
    } else {
        $error = "Failed to update book.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function () {
                const output = document.getElementById('preview');
                output.src = reader.result;
                output.classList.remove("hidden");
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200">
<div class="min-h-screen flex flex-col md:flex-row">
<?php $current_page = 'book'; include '../menu/sidebar_admin.php'; ?>
    <div class="flex-1 p-4 md:p-6 w-full bg-white rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Edit Book</h2>
            <a href="book_list.php" class="text-indigo-600 hover:underline">← Back to Book List</a>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-800 p-3 rounded mb-4"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <label class="block font-medium mb-1">Book Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($book['name']) ?>" required class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-1">Author</label>
                <input type="text" name="author" value="<?= htmlspecialchars($book['author']) ?>" required class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-1">Number of Pages</label>
                <input type="number" name="pages" value="<?= htmlspecialchars($book['pages']) ?>" required class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-1">Days Available</label>
                <input type="number" name="days_available" value="<?= htmlspecialchars($book['days_available']) ?>" required class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-1">Book Status</label>
                <input type="text" value="<?= $book['available'] == 1 ? 'Available' : 'Borrowed' ?>" readonly class="w-full bg-gray-100 border border-gray-300 rounded px-3 py-2 text-gray-600">
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-1">Created At</label>
                <input type="text" value="<?= date("Y-m-d H:i:s", strtotime($book['created_at'])) ?>" readonly class="w-full bg-gray-100 border border-gray-300 rounded px-3 py-2 text-gray-600">
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-1">Description</label>
                <textarea name="description" class="w-full border border-gray-300 rounded px-3 py-2"><?= htmlspecialchars($book['description']) ?></textarea>
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-1">Current Book Image</label>
                <img src="../uploads/book-image/<?= htmlspecialchars($book['image_url']) ?>" alt="<?= htmlspecialchars($book['name']) ?>" class="h-40 w-auto object-contain border rounded mb-3">
                <input type="file" name="image" accept="image/*" onchange="previewImage(event)" class="w-full p-2 border border-gray-300 rounded">
                <img id="preview" class="hidden h-40 w-auto mt-3 object-contain border rounded" />
            </div>

            <div class="mb-6">
                <label class="block font-medium mb-1">Current QR Code</label>
                <img src="../uploads/qrcode/<?= htmlspecialchars($book['qrcode']) ?>" alt="QR Code" class="h-32 w-32 object-contain">
            </div>

            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Update Book
            </button>
        </form>
    </div>
</body>
</html>
