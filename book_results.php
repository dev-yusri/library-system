<?php
require 'db.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';

$query = "SELECT * FROM books WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $query .= " AND (name LIKE ? OR author LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

// Sorting
switch ($sort) {
    case 'created_asc': $query .= " ORDER BY created_at ASC"; break;
    case 'created_desc': $query .= " ORDER BY created_at DESC"; break;
    case 'name_asc': $query .= " ORDER BY name ASC"; break;
    case 'name_desc': $query .= " ORDER BY name DESC"; break;
    case 'author_asc': $query .= " ORDER BY author ASC"; break;
    case 'author_desc': $query .= " ORDER BY author DESC"; break;
    default: $query .= " ORDER BY created_at DESC"; break;
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<?php if ($result->num_rows > 0): ?>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <?php while ($row = $result->fetch_assoc()): ?>
      <a href="book_detail.php?id=<?= $row['id'] ?>" class="block h-full">
        <div class="bg-white p-5 rounded-xl shadow hover:shadow-lg transition duration-300 flex flex-col h-full">
          
          <div class="h-44 w-full mb-4 flex items-center justify-center bg-gray-100 rounded overflow-hidden">
            <img src="uploads/book-image/<?= htmlspecialchars($row['image_url']) ?>"
                alt="<?= htmlspecialchars($row['name']) ?>"
                class="h-full w-full object-contain">
          </div>

          <h3 class="text-lg font-semibold mb-1"><?= htmlspecialchars($row['name']) ?></h3>
          <p class="text-sm text-gray-600 mb-1"><?= htmlspecialchars($row['author']) ?></p>
          <div class="mt-auto">
            <div class="flex items-center justify-between mb-1">
              <span class="<?= $row['available'] ? 'text-green-600 bg-green-100' : 'text-red-600 bg-red-100' ?> rounded p-1 text-xs font-semibold">
                <?= $row['available'] ? 'Available' : 'Borrowed' ?>
              </span>
              <span class="text-xs text-indigo-600 hover:text-indigo-700 transition">View Details →</span>
            </div>
          </div>
        </div>
      </a>
    <?php endwhile; ?>
  </div>
<?php else: ?>
  <div class="text-center text-gray-500 py-12 text-lg">📚 No books found</div>
<?php endif; ?>

