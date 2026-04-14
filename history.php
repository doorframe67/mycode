<?php session_start(); if(!isset($_SESSION['user_id'])) { header("Location: login-page.html"); } ?>
<h1>Transaction History for <?php echo $_SESSION['user_name']; ?></h1>