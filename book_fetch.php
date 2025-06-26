<?php
require 'db.php'; 
$book_id = $_GET['book_id'];

$stmt = $conn->prepare("SELECT id, name, author, available FROM books WHERE id = ?");
$stmt->bind_param("s", $book_id);
$stmt->execute();
$result = $stmt->get_result();

if ($book = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'id' => $book['id'],
        'title' => $book['name'],
        'author' => $book['author'],
        'available' => $book['available']
    ]);
} else {
    echo json_encode(['success' => false]);
}
