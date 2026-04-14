<?php
session_start();    // Join the current session
session_unset();    // Clear all the variables (like user_id and name)
session_destroy();  // Kill the session completely

// This is the "Refresh" part: it sends the user back to your home page
header("Location: home-page.php"); 
exit();
?>