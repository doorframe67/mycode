<?php 
// 1. THIS IS THE MOST IMPORTANT LINE. 
// It must be at the VERY top of the file before any HTML.
session_start(); 
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="mycode.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Plus+Jakarta+Sans:wght@700;800&family=Space+Grotesk:wght@500&display=swap" rel="stylesheet">
    
    <title>NeoBank</title>
</head>

<body class="home-theme">

    <div class="bg-layer"></div>

    <nav class="navbar navbar-expand-lg navbar-dark my-own-red"></nav>

    <nav class="navbar navbar-expand-lg navbar-light my-own-red1">
      <div class="container">
        <a class="navbar-brand" style="font-family: 'Space Grotesk', sans-serif; font-weight: 500; letter-spacing: -1px;">NEO BANK</a>
        
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item"><a class="nav-link" href="home-page.php">home</a></li>
                <li class="nav-item"><a class="nav-link" href="check_access.php?page=dashboard">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="check_access.php?page=history">Transaction History</a></li>
                
                <?php 
                // ISSUE #2 & #3 FIXED: Logic now works because session_start() is at the top
                if (isset($_SESSION['user_id'])): 
                ?>
                    <li class="nav-item">
                        <a href="logout.php" class="btn btn-outline-danger ml-2" style="border-radius: 8px;">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="login-page.html" class="nav-link btn btn-outline-dark text-dark px-4 mr-2" style="border-radius: 8px;">Login</a>
                    </li>
                    <li class="nav-item">
                        <a href="login-page.html#pane-signup" class="nav-link btn btn-primary text-white px-4" style="border-radius: 8px; background: #0C1F3F;">Join NeoBank</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
      </div>
    </nav>

    <div class="container py-5 mt-5"> 
        <h1 class="display-4 mb-5 mt-4" style="color: #0a0c0d; font-family: 'Segoe UI'; text-transform: uppercase;"> 
            Banking Reimagined for <br> the Digital Age.
        </h1>
        
        <ul class="list-unstyled mt-4" style="font-family: 'Segoe UI'; color: #160938;">
            <li class="mb-3 mt-5 h5" style="text-transform:uppercase">🔹 <strong>Secure:</strong> Advanced biometric encryption.</li>
            <li class="mb-3 h5" style="text-transform:uppercase">🔹 <strong>Lightning-Fast:</strong> Instant global transfers.</li>
            <li class="mb-3 h5" style="text-transform:uppercase">🔹 <strong>Lifestyle Design:</strong> Built for the modern user.</li>
        </ul>
    </div>

    <div class="container position-relative py-5">
        <div class="row align-items-center">
            <div class="col-md-7">
                <h1 class="mb-4" style="color: #004643; font-family: 'Segoe UI'; font-weight: 800; text-shadow: none;">Why NeoBank?</h1>
                
                <div class="pr-md-5">
                    <h5 class="mb-4" style="color: #002147; line-height: 1.6;"> 
                        <strong>Security First:</strong> Every transaction is protected by 256-bit encryption and multi-factor biometric authentication.
                    </h5>
                    <h5 class="mb-4" style="color: #002147; line-height: 1.6;">
                        <strong>Smart Insights:</strong> Get weekly reports on your spending habits to help you save for what matters.
                    </h5>
                    <h5 class="mb-4" style="color: #002147; line-height: 1.6;">
                        <strong>Hidden Fees:</strong> We believe in transparency. No monthly maintenance fees, ever.
                    </h5>
                </div>
            </div>

            <div class="col-md-5 text-center">
                <dotlottie-player 
                    src="animations/Isometric data analysis.lottie" 
                    background="transparent" 
                    speed="1" 
                    style="width: 100%; height: 450px;" 
                    loop 
                    autoplay>
                </dotlottie-player>
            </div>
        </div>
    </div>

    <footer class="text-white mt-auto">
        <div class="container py-5">
            <div class="row">
                <div class="col-md-6">
                    <h4 class="font-weight-bold">NEO BANK</h4>
                    <p class="opacity-75">Building cool things with Bootstrap and custom CSS.</p>
                </div>
                <div class="col-md-6 text-md-right">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="home-page.php" class="text-white">Home</a></li>
                        <li><a href="check_access.php?page=dashboard2" class="text-white">Dashboard</a></li>
                        <li><a href="#" class="text-white">Contact Us</a></li>
                    </ul>
                </div>
            </div>
            <hr class="bg-white my-4">
            <div class="text-center">
                <small>&copy; 2026 NEO BANK. All rights reserved.</small>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/@dotlottie/player-component@2.7.12/dist/dotlottie-player.mjs" type="module"></script>
</body>
</html>