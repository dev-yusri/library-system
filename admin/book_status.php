<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page
    exit;
}

require '../db.php';

$query = "SELECT b.*, bl.id AS log_id, u.name AS borrower_name, u.email AS borrower_email,
          bl.borrowed_at, bl.returned_at, bl.due_date, bl.overdue_notified, bl.due_soon_notified, bl.due_notified_again
          FROM books b
          LEFT JOIN borrow_log bl ON b.id = bl.book_id
          LEFT JOIN users u ON bl.user_id = u.user_id
          WHERE bl.borrowed_at IS NOT NULL
          ORDER BY b.created_at DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Book Status</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 14px 16px;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #3949ab;
            color: white;
            text-align: left;
        }
        td {
            vertical-align: top;
        }
        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: bold;
            text-align: center;
            font-size: 14px;
        }
        .available {
            background-color: #4caf50;
            color: white;
        }
        .unavailable {
            background-color: red;
            color: white;
        }
        .borrow {
            background-color: #ffeb3b;
            color: #333;
        }
        .late {
            background-color: #f44336;
            color: white;
        }
        .returned {
            background-color: #4caf50;
            color: white;
        }
        .returned-time {
            color: #757575;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200">
<div class="min-h-screen flex flex-col md:flex-row">
<?php $current_page = 'status'; include '../menu/sidebar_admin.php'; ?>
<div class="flex-1 p-4 md:p-6 w-full bg-white rounded-lg shadow-md m-5">
<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold">Book Status Overview</h1>
</div>

<table>
    <thead>
        <tr>
            <th>Book</th>
            <th>Availability</th>
            <th>Borrower Info</th>
            <th>Borrow Status</th>
            <th>Status</th>
            <th>Notification</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td>
                <strong><?= htmlspecialchars($row['name']) ?></strong><br>
                <small>by <?= htmlspecialchars($row['author']) ?></small>
            </td>
            <td>
                <?= $row['available'] ? '<span class="status available">Available</span>' : '<span class="status unavailable">Borrowed</span>' ?>
            </td>
            <td>
                <?php if ($row['borrower_name']): ?>
                    <?= htmlspecialchars($row['borrower_name']) ?><br>
                    <small><?= htmlspecialchars($row['borrower_email']) ?></small>
                <?php else: ?>
                    <em>-</em>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($row['borrowed_at'] && !$row['returned_at']): ?>
                    <?php
                        $now = new DateTime();
                        $due = new DateTime($row['due_date']);
                        if ($now > $due) {
                            $lateDays = $now->diff($due)->days;
                            echo "<span class='status late'>Overdue ({$lateDays} day(s))</span>";
                        } else {
                            $remaining = $now->diff($due)->format("%a day(s)");
                            echo "<span class='status borrow'>Borrowed - $remaining left</span>";
                        }
                    ?>
                <?php elseif ($row['returned_at']): ?>
                    <span class="status returned">Returned</span><br>
                    <small><?= date("Y-m-d H:i", strtotime($row['returned_at'])) ?></small>
                <?php else: ?>
                    <em>-</em>
                <?php endif; ?>
            </td>
            <td>
                <?php
                    if ($row['returned_at']) {
                        if (strtotime($row['returned_at']) > strtotime($row['due_date'])) {
                            echo '<span style="color: #E02424;">Returned Late</span>';
                        } else {
                            echo '<span style="color: #10B981;">Returned on Time</span>';
                        }
                    } else {
                        echo '<span style="color: #6B7280;">Borrowing</span>';
                    }
                ?>
            </td>
            <td>
            <?php 
$now = new DateTime();
$dueDate = new DateTime($row['due_date']);
$isDueSoon = ($now <= $dueDate && $now->diff($dueDate)->days <= 1);
$isOverdue = ($now > $dueDate);
$oneDayAfterDue = (clone $dueDate)->modify('+1 day');
$isOneDayPastOverdue = ($now > $oneDayAfterDue);

if ($row['returned_at']) {
    echo '<i class="fas fa-check text-green-500" title="Returned on time"></i>';

// 1. Due Soon Reminder
} elseif ($isDueSoon && !$row['due_soon_notified']) {
    echo '<form method="post" action="send_due_reminder.php" onsubmit="return confirm(\'Send reminder email?\');">';
    echo '<input type="hidden" name="log_id" value="' . $row['log_id'] . '">';
    echo '<button type="submit" class="text-yellow-500" title="Send due soon reminder"><i class="fas fa-bell"></i> Send Reminder</button>';
    echo '</form>';
} elseif ($isDueSoon && $row['due_soon_notified']) {
    echo '<span class="text-blue-500">Already notified</span>';

// 2. Overdue + 1 Day => Send Final Warning
} elseif ($isOverdue && $row['overdue_notified'] && !$row['due_notified_again'] && $isOneDayPastOverdue) {
    echo '<form method="post" action="send_reminder_again.php" onsubmit="return confirm(\'Send final overdue email?\');">';
    echo '<input type="hidden" name="log_id" value="' . $row['log_id'] . '">';
    echo '<button type="submit" class="text-red-600" title="Send overdue again reminder"><i class="fas fa-bell"></i> Send Final Warning</button>';
    echo '</form>';

// 3. First Overdue Reminder
} elseif ($isOverdue && !$row['overdue_notified']) {
    echo '<form method="post" action="send_reminder.php" onsubmit="return confirm(\'Send overdue email?\');">';
    echo '<input type="hidden" name="log_id" value="' . $row['log_id'] . '">';
    echo '<button type="submit" class="text-red-500" title="Send overdue reminder"><i class="fas fa-bell"></i> Send Warning</button>';
    echo '</form>';

// 4. Show after first overdue notification is sent
} elseif ($isOverdue && $row['overdue_notified'] && !$row['due_notified_again']) {
    echo '<span class="text-red-400">Overdue and already notified</span>';

// 5. Final state after second notification
} elseif ($isOverdue && $row['overdue_notified'] && $row['due_notified_again']) {
    echo '<span class="text-red-400">Overdue and already notified again</span>';

} else {
    echo '<span class="text-gray-400">Still borrowing.</span>';
}
?>

</td>


        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
