<?php
/**
 * lookup_account.php
 * AJAX endpoint — returns JSON: { found: bool, name: string }
 * Called by the Send / Request modals as the user types an account number.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['found' => false]);
    exit();
}

include 'db.php';

$acc = mysqli_real_escape_string($conn, trim($_GET['acc'] ?? ''));

if (strlen($acc) < 6) {
    echo json_encode(['found' => false]);
    exit();
}

$result = mysqli_query($conn, "SELECT first_name, last_name FROM users WHERE account_number = '$acc' LIMIT 1");

if ($result && mysqli_num_rows($result) > 0) {
    $row  = mysqli_fetch_assoc($result);
    $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    echo json_encode(['found' => true, 'name' => htmlspecialchars($name)]);
} else {
    echo json_encode(['found' => false]);
}
