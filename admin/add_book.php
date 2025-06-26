<?php
include '../db.php';
require '../phpqrcode/qrlib.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $author = $_POST['author'];
    $pages = $_POST['pages'];
    $days = $_POST['days_available'] ?? 0;
    $available = "1";
    $description = $_POST['description'] ?? '';
    $created_at = date('Y-m-d H:i:s');

    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "../uploads/book-image/"; 
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true); 
        }

        $imageName = time() . "_" . basename($_FILES['image']['name']);
        $targetFilePath = $targetDir . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
            $image = $imageName;
        } else {
            $error = "Error uploading image.";
        }
    }

    $stmt = $conn->prepare("INSERT INTO books (name, author, pages, available, image_url, days_available, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisssss", $name, $author, $pages, $available, $image, $days, $description, $created_at);

    if ($stmt->execute()) {
        $book_id = $stmt->insert_id;

        // Generate QR code with redirect link to book detail page
        $base_url = "http://localhost/LIBOS/book_detail.php"; 
        $qr_data = $base_url . "?id=" . $book_id;

        $qr_filename = 'qr_' . $book_id . '.png';
        $qr_path = '../uploads/qrcode/' . $qr_filename;

        if (!file_exists('../uploads/qrcode')) {
            mkdir('../uploads/qrcode', 0777, true);
        }

        // Generate QR code with larger size
        $qr_error_correction = 'H'; // High error correction
        $qr_pixel_size = 10;        // Larger size
        $qr_margin = 2;             // Margin around the QR

        QRcode::png($qr_data, $qr_path, $qr_error_correction, $qr_pixel_size, $qr_margin);

        // Save QR code filename in DB
        $update = $conn->prepare("UPDATE books SET qrcode = ? WHERE id = ?");
        $update->bind_param("si", $qr_filename, $book_id);
        $update->execute();

        header("Location: book_list.php?success=1");
        exit();
    } else {
        $error = "Error adding book: " . $stmt->error;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Book</title>
    <title>Edit Book</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200">
<div class="min-h-screen flex flex-col md:flex-row">
<?php $current_page = 'book'; include '../menu/sidebar_admin.php'; ?>
    <div class="flex-1 p-4 md:p-6 w-full bg-white rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-6">Add New Book</h2>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-800 p-3 rounded mb-4"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data"> <!-- ✅ Add enctype -->
            <div class="mb-4">
                <label class="block font-medium mb-1">Book Name</label>
                <input type="text" name="name" required class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-1">Author</label>
                <input type="text" name="author" required class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-1">Number of Pages</label>
                <input type="number" name="pages" required class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-1">Days Available</label>
                <input type="number" name="days_available" required class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded px-3 py-2"></textarea>
            </div>

            <div class="mb-4">
                <label for="image" class="block font-medium mb-1">Book Image</label>
                <input type="file" name="image" id="image" accept="image/*" class="w-full p-2 border border-gray-300 rounded">
            </div>

            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                Add Book
            </button>
        </form>
    </div>
</body>
</html>
