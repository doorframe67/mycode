<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize inputs
    $fname = mysqli_real_escape_string($conn, $_POST['s-fname']);
    $lname = mysqli_real_escape_string($conn, $_POST['s-lname']);
    $email = mysqli_real_escape_string($conn, $_POST['s-email']);
    $pass  = password_hash($_POST['s-pass'], PASSWORD_DEFAULT);
    $dep   = (float)$_POST['s-dep'];
    
    // Generate the unique account number
    $acc_no = "NEO-" . rand(1000, 9999) . "-" . rand(10, 99);

    // Insert into database
    $sql = "INSERT INTO users (first_name, last_name, email, password, opening_deposit, account_no, wallet_balance) 
            VALUES ('$fname', '$lname', '$email', '$pass', '$dep', '$acc_no', 0.00)";

    if (mysqli_query($conn, $sql)) {
        // SUCCESS: Redirect to login or home-page with the account number in the URL
        header("Location: login.php?new_acc=" . $acc_no); 
        exit(); 
    } else {
        // ERROR: If email exists or query fails
        echo "Error: " . mysqli_error($conn);
    }
}
?>