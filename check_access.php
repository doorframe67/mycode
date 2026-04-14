<?php
session_start();

// Get the page the user wanted to visit
$target = $_GET['page'];

// Check if the session 'user_id' exists (set in login.php)
if (!isset($_SESSION['user_id'])) {
    // If NOT logged in, send them to login with a "trigger" for the pop-up
    header("Location: login-page.html?error=must_login");
    exit();
} else {
    // If logged in, send them to the actual page
    if ($target == "dashboard") {
        header("Location: dashboard.php");
    } else {
        header("Location: history.php");
    }
    exit();
}
?>