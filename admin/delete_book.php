<?php
include '../db.php'; // Make sure your DB connection is here

// Get the book ID from URL parameter
$book_id = $_GET['id'] ?? null;

if ($book_id) {
    // Delete query to remove the book from the books table
    $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    
    if ($stmt->execute()) {
        echo "Book deleted successfully!";
        header("Location: book_list.php");
        exit;
    } else {
        echo "Error deleting book: " . $conn->error;
    }
} else {
    echo "Invalid book ID!";
}
?>
