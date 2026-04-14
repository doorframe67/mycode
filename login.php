<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['l-email']);
    $password = $_POST['l-pass'];

    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'];
            
            // Redirect to dashboard where the banking features are
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NeoBank | Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    </head>
<body>

<?php if (isset($_GET['new_acc'])): ?>
    <div id="regSuccess" style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; z-index: 9999;">
        <div style="background: white; padding: 40px; border-radius: 25px; text-align: center; width: 90%; max-width: 400px; box-shadow: 0 15px 40px rgba(0,0,0,0.5);">
            <div style="font-size: 50px; color: #C9A84C; margin-bottom: 20px;"><i class="fas fa-crown"></i></div>
            <h2 style="font-family: 'Bebas Neue', sans-serif; font-size: 28px; margin-bottom: 10px;">REGISTRATION SUCCESSFUL!</h2>
            <p style="color: #666; font-size: 14px; margin-bottom: 20px;">Welcome to the vault. Your unique account number is:</p>
            
            <div style="background: #0C1F3F; color: #C9A84C; padding: 15px; border-radius: 12px; font-family: monospace; font-size: 22px; font-weight: bold; letter-spacing: 2px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($_GET['new_acc']); ?>
            </div>
            
            <p style="color: #dc2626; font-size: 12px; font-weight: bold; margin-bottom: 25px;">⚠️ COPY THIS NUMBER. YOU NEED IT TO RECEIVE MONEY!</p>
            
            <button onclick="document.getElementById('regSuccess').style.display='none'" 
                    style="width: 100%; padding: 15px; background: #C9A84C; color: #0C1F3F; border: none; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 16px;">
                I'VE SAVED IT, PROCEED
            </button>
        </div>
    </div>
<?php endif; ?>

<div class="login-container">
    <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form action="login.php" method="POST">
        </form>
</div>

</body>
</html>