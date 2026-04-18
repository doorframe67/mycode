<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';

$user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
$result  = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
$user    = mysqli_fetch_assoc($result);

// FIX: This prevents the "Undefined array key" error. 
// It uses 'balance' if available, otherwise it falls back to 'opening_deposit'.
$balance = isset($user['balance']) && $user['balance'] > 0 
           ? (float)$user['balance'] 
           : (float)($user['opening_deposit'] ?? 0);

$first_name = isset($user['first_name']) ? htmlspecialchars($user['first_name']) : 'User';
$acc_no     = isset($user['account_no']) ? htmlspecialchars($user['account_no']) : 'N/A'; 
$send_msg = '';

// --- SEND MONEY LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    $to_acc = mysqli_real_escape_string($conn, trim($_POST['to_account'] ?? ''));
    $amount = (float)($_POST['amount'] ?? 0);
    
    if ($amount <= 0) {
        $send_msg = ['type' => 'error', 'text' => 'Enter a valid amount.'];
    } elseif ($amount > $balance) {
        $send_msg = ['type' => 'error', 'text' => 'Insufficient balance.'];
    } else {
        // Search using 'account_no' to match signup.php
        $rec = mysqli_query($conn, "SELECT * FROM users WHERE account_no = '$to_acc'");
        if (!$rec || mysqli_num_rows($rec) === 0) {
            $send_msg = ['type' => 'error', 'text' => 'Recipient account not found.'];
        } else {
            $recipient = mysqli_fetch_assoc($rec);
            if ($recipient['id'] == $user_id) {
                $send_msg = ['type' => 'error', 'text' => 'You cannot send money to yourself.'];
            } else {
                // Update both users (Sender and Receiver)
                mysqli_query($conn, "UPDATE users SET balance = balance - $amount WHERE id = '$user_id'");
                mysqli_query($conn, "UPDATE users SET balance = balance + $amount WHERE id = '{$recipient['id']}'");
                
                // Record for the Live Activity section
                mysqli_query($conn, "INSERT INTO transactions (user_id, type, amount, description) 
                                     VALUES ('$user_id', 'debit', $amount, 'Sent to {$recipient['first_name']}')");
                
                $send_msg = ['type' => 'success', 'text' => "₹" . number_format($amount, 2) . " sent successfully!"];
                $balance -= $amount;
            }
        }
    }
}

// --- REQUEST MONEY LOGIC ---
$req_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request') {
    $from_acc = mysqli_real_escape_string($conn, trim($_POST['from_account'] ?? ''));
    $amount   = (float)($_POST['amount'] ?? 0);
    
    if ($amount <= 0) {
        $req_msg = ['type' => 'error', 'text' => 'Enter a valid amount.'];
    } else {
        $rec = mysqli_query($conn, "SELECT * FROM users WHERE account_no = '$from_acc'");
        
        if (!$rec || mysqli_num_rows($rec) === 0) {
            $req_msg = ['type' => 'error', 'text' => 'Account not found.'];
        } else {
            $from_user = mysqli_fetch_assoc($rec);
            if ($from_user['id'] == $user_id) {
                $req_msg = ['type' => 'error', 'text' => 'You cannot request from yourself.'];
            } else {
                // 1. Record for YOU (The Requester)
                mysqli_query($conn, "INSERT INTO transactions (user_id, type, amount, description) 
                                     VALUES ('$user_id', 'credit', 0, 'Requested ₹$amount from {$from_user['first_name']}')");

                // 2. Record for YOUR FRIEND (The Person being requested)
                // This is the line that was likely missing or had the wrong ID
                $friend_id = $from_user['id'];
                mysqli_query($conn, "INSERT INTO transactions (user_id, type, amount, description) 
                                     VALUES ('$friend_id', 'debit', 0, '{$user['first_name']} requested ₹$amount from you')");
                
                $req_msg = ['type' => 'success', 'text' => "Request for ₹" . number_format($amount, 2) . " sent to {$from_user['first_name']}!"];
            }
        }
    }
}

    // --- DEPOSIT MONEY LOGIC ---
$dep_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deposit') {
    $amount = (float)($_POST['amount'] ?? 0);
    
    if ($amount <= 0) {
        $dep_msg = ['type' => 'error', 'text' => 'Please enter a valid amount.'];
    } else {
        // Update the user's balance
        $update_query = "UPDATE users SET balance = balance + $amount WHERE id = '$user_id'";
        
        if (mysqli_query($conn, $update_query)) {
            // Record the transaction so it shows in Live Activity
            mysqli_query($conn, "INSERT INTO transactions (user_id, type, amount, description) 
                                 VALUES ('$user_id', 'credit', $amount, 'Self Deposit')");
            
            $dep_msg = ['type' => 'success', 'text' => "₹" . number_format($amount, 2) . " deposited successfully!"];
            $balance += $amount; // Update the variable so the animated counter shows the new total
        } else {
            $dep_msg = ['type' => 'error', 'text' => 'Database error. Please try again.'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NeoBank | Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --gold:     #C9A84C;
    --navy:     #0C1F3F;
    --green:    #2ECC8A;
    --bg:       #f0f2f5;
    --white:    #ffffff;
    --txt:      #1a1a2e;
    --muted:    #6b7280;
    --sidebar-w: 270px;
}
html, body { height: 100%; overflow: hidden; }
body { font-family: 'DM Sans', sans-serif; background: var(--bg); display: flex; color: var(--txt); }
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: rgba(12,31,63,.18); border-radius: 3px; }

/* THE CHAKRA BACKGROUND */
.chakra-bg {
    position: fixed; top: 10%; right: -5%; font-size: 500px;
    color: rgba(52, 33, 112, 0.08); /* NeoBank Gold tinted */
    animation: rotateChakra 35s linear infinite; pointer-events: none; z-index: 0; user-select: none;
}
@keyframes rotateChakra { to { transform: rotate(360deg); } }

.sidebar {
    width: var(--sidebar-w); background: linear-gradient(180deg, var(--navy) 0%, #060e1f 100%); color: #fff;
    display: flex; flex-direction: column; padding: 28px 20px; position: relative; z-index: 10; flex-shrink: 0; box-shadow: 6px 0 30px rgba(0,0,0,0.18);
}
.sb-logo { font-family: 'Bebas Neue', sans-serif; font-size: 26px; letter-spacing: 3px; border-bottom: 4px solid var(--gold); padding-bottom: 14px; margin-bottom: 36px; text-align: center; }
.sb-nav { flex: 1; display: flex; flex-direction: column; gap: 8px; }
.sb-link { display: flex; align-items: center; gap: 12px; padding: 13px 16px; border-radius: 16px; color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; font-weight: 500; transition: all .25s; border: 1px solid transparent; cursor: pointer; background: none; width: 100%; text-align: left; }
.sb-link:hover { background: rgba(255,255,255,0.12); color: #fff; }
.sb-link.active { background: rgba(255,255,255,0.18); color: #fff; border-color: rgba(255,255,255,0.2); }
.sb-link i { width: 18px; text-align: center; }
.sb-footer { margin-top: auto; }
.sb-logout { width: 100%; background: #dc2626; color: #fff; border: none; padding: 14px; border-radius: 16px; font-size: 14px; font-weight: 700; cursor: pointer; transition: all .25s; letter-spacing: .5px; }
.sb-logout:hover { background: #b91c1c; transform: scale(1.02); }

.main { flex: 1; padding: 32px 36px; overflow-y: auto; position: relative; z-index: 1; min-height: 100vh; }
.header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 36px; animation: fadeUp .5s ease both; }
.header h2 { font-family: 'Bebas Neue', sans-serif; font-size: 52px; letter-spacing: 1px; color: var(--txt); line-height: 1; }
.header p { font-size: 12px; color: var(--muted); font-weight: 600; letter-spacing: 3px; text-transform: uppercase; margin-top: 6px; }

/* GLASSMORPHISM CLASSES */
.balance-pill {
    background: rgba(255,255,255,0.65); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(255,255,255,0.8); border-radius: 28px; padding: 20px 28px; display: flex; align-items: center; gap: 18px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.08); transition: all .3s;
}
.balance-pill:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,0.12); }
.bal-icon { width: 52px; height: 52px; background: rgba(201,168,76,0.15); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: var(--gold); }
.bal-label { font-size: 10px; font-weight: 700; color: var(--muted); letter-spacing: 2px; text-transform: uppercase; }
.bal-amount { font-family: 'Bebas Neue', sans-serif; font-size: 38px; color: var(--txt); letter-spacing: 1px; }

.card {
    background: rgba(255,255,255,0.65); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(255,255,255,0.8); border-radius: 28px; transition: all .45s cubic-bezier(.23,1,.32,1);
}
.card:hover { transform: translateY(-10px) scale(1.015); box-shadow: 0 24px 48px rgba(0,0,0,0.12); background: rgba(255,255,255,0.85); }

.action-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 28px; animation: fadeUp .5s .08s ease both; }
.action-card { padding: 36px 20px; text-align: center; cursor: pointer; }
.action-card.gold:hover { border-top: 6px solid var(--gold); box-shadow: 0 20px 40px rgba(201,168,76,.15); }
.action-card.navy:hover { border-top: 6px solid var(--navy); box-shadow: 0 20px 40px rgba(12,31,63,.12); }
.action-card.green:hover { border-top: 6px solid var(--green); box-shadow: 0 20px 40px rgba(46,204,138,.12); }
.ac-icon { width: 72px; height: 72px; border-radius: 22px; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; font-size: 28px; transition: transform .3s; }
.action-card:hover .ac-icon { transform: scale(1.12); }
.ac-icon.gold { background: rgba(201,168,76,0.15); color: var(--gold); }
.ac-icon.navy { background: rgba(12,31,63,0.1); color: var(--navy); }
.ac-icon.green { background: rgba(46,204,138,0.1); color: var(--green); }
.ac-title { font-family: 'Bebas Neue', sans-serif; font-size: 22px; letter-spacing: 2px; color: var(--txt); }
.ac-sub { font-size: 12px; color: var(--muted); margin-top: 4px; font-weight: 500; }

.bottom-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; animation: fadeUp .5s .16s ease both; }
.activity-card { padding: 28px; }
.sec-title { font-size: 13px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--muted); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
.sec-title i { color: var(--navy); }
.tx-row { display: flex; align-items: center; gap: 14px; padding: 12px 0; border-bottom: 1px solid rgba(0,0,0,0.05); }
.tx-row:last-child { border-bottom: none; }
.tx-icon { width: 40px; height: 40px; border-radius: 12px; background: rgba(12,31,63,0.08); display: flex; align-items: center; justify-content: center; font-size: 16px; color: var(--navy); flex-shrink: 0; }
.tx-name { font-size: 14px; font-weight: 600; }
.tx-sub { font-size: 11px; color: var(--muted); margin-top: 2px; }
.tx-amt { margin-left: auto; font-family: 'Bebas Neue', sans-serif; font-size: 18px; letter-spacing: .5px; color: var(--green); }
.tx-amt.debit { color: #dc2626; }
.empty-state { text-align: center; padding: 40px 0; color: var(--muted); font-size: 13px; }
.empty-state i { font-size: 40px; opacity: 0.1; display: block; margin-bottom: 12px; }

/* DARK GLASSMORPHISM FOR RECENT TX CARD */
.recent-tx-card {
    background: rgba(12, 31, 63, 0.85); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
    border-radius: 28px; padding: 28px; color: #fff; position: relative; overflow: hidden; border: 1px solid rgba(255,255,255,0.15);
    cursor: pointer; transition: all .4s cubic-bezier(.23,1,.32,1); box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}
.recent-tx-card:hover { transform: translateY(-10px) scale(1.015); box-shadow: 0 24px 48px rgba(12,31,63,0.4); background: rgba(17, 40, 81, 0.95); }
.recent-tx-card::before { content: ''; position: absolute; top: -60px; right: -60px; width: 200px; height: 200px; background: radial-gradient(circle, rgba(201,168,76,0.2) 0%, transparent 70%); border-radius: 50%; }
.sec-h { font-family: 'Bebas Neue', sans-serif; font-size: 26px; letter-spacing: 1px; margin-bottom: 5px; display: flex; align-items: center; justify-content: space-between; }
.sec-p { font-size: 12px; color: rgba(255,255,255,0.55); margin-bottom: 20px; }

/* Modals */
.modal-overlay { position: fixed; inset: 0; background: rgba(10, 10, 30, 0.55); backdrop-filter: blur(8px); z-index: 9000; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity .3s; }
.modal-overlay.open { opacity: 1; pointer-events: all; }
.modal { background: rgba(255,255,255,0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.5); border-radius: 32px; padding: 40px; width: 480px; max-width: 95vw; position: relative; box-shadow: 0 32px 80px rgba(0,0,0,0.2); transform: translateY(30px) scale(0.97); transition: transform .35s cubic-bezier(.23,1,.32,1); }
.modal-overlay.open .modal { transform: translateY(0) scale(1); }
.modal-close { position: absolute; top: 20px; right: 24px; background: none; border: none; font-size: 20px; color: var(--muted); cursor: pointer; transition: color .2s; }
.modal-close:hover { color: var(--txt); }
.modal-stripe { height: 6px; border-radius: 3px; margin-bottom: 24px; }
.modal-title { font-family: 'Bebas Neue', sans-serif; font-size: 32px; letter-spacing: 1.5px; margin-bottom: 4px; }
.modal-sub { font-size: 13px; color: var(--muted); margin-bottom: 28px; font-weight: 500; }
.form-group { margin-bottom: 18px; }
.form-label { display: block; font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: var(--muted); margin-bottom: 8px; }
.form-input { width: 100%; padding: 14px 18px; border: 2px solid #e5e7eb; border-radius: 16px; font-size: 15px; font-family: 'DM Sans', sans-serif; color: var(--txt); outline: none; transition: border-color .2s, box-shadow .2s; background: rgba(248, 249, 251, 0.8); }
.form-input:focus { border-color: var(--navy); box-shadow: 0 0 0 4px rgba(12,31,63,.06); background: #fff; }
.form-hint { font-size: 11px; color: var(--muted); margin-top: 6px; }
.form-btn { width: 100%; padding: 16px; border-radius: 16px; border: none; font-family: 'Bebas Neue', sans-serif; font-size: 18px; letter-spacing: 2px; color: #fff; cursor: pointer; transition: all .25s; margin-top: 8px; }
.form-btn:hover { transform: scale(1.02); filter: brightness(1.08); }
.alert { padding: 14px 18px; border-radius: 14px; font-size: 13px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
.alert.success { background: rgba(46,204,138,.08); color: var(--green); border: 1px solid rgba(46,204,138,.2); }
.alert.error { background: rgba(220,38,38,.07); color: #dc2626; border: 1px solid rgba(220,38,38,.2); }
.chat-fab { position: fixed; bottom: 28px; right: 28px; width: 64px; height: 64px; background: var(--gold); border-radius: 50%; border: none; color: #fff; font-size: 26px; cursor: pointer; box-shadow: 0 8px 30px rgba(201,168,76,0.45); transition: all .25s; z-index: 100; animation: pulse 2.5s infinite; }
.chat-fab:hover { transform: scale(1.1); }
@keyframes pulse { 0%,100% { box-shadow: 0 8px 30px rgba(201,168,76,.45); } 50% { box-shadow: 0 8px 40px rgba(201,168,76,.7); } }
.chat-window { position: fixed; bottom: 104px; right: 28px; width: 340px; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,.18); z-index: 99; display: none; animation: slideUp .3s ease; }
.chat-window.open { display: block; }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: none; } }
.chat-head { background: var(--navy); color: #fff; padding: 18px 20px; display: flex; justify-content: space-between; align-items: center; }
.chat-body { height: 280px; background: #f8f9fb; padding: 18px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; font-size: 13px; }
.msg-bot { background: #fff; border: 1px solid #e5e7eb; padding: 12px 16px; border-radius: 16px 16px 16px 4px; max-width: 85%; border-left: 3px solid var(--gold); }
.chat-foot { background: #fff; padding: 14px; display: flex; gap: 10px; border-top: 1px solid #f0f2f5; }
.chat-input { flex: 1; background: #f0f2f5; border: none; border-radius: 50px; padding: 10px 16px; font-size: 13px; outline: none; }
.chat-send { width: 40px; height: 40px; background: var(--green); border: none; border-radius: 50%; color: #fff; font-size: 14px; cursor: pointer; transition: .2s; flex-shrink: 0; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: none; } }
@media (max-width: 900px) { .action-grid { grid-template-columns: 1fr; } .bottom-grid { grid-template-columns: 1fr; } .header { flex-direction: column; gap: 18px; } }
</style>
</head>
<body>

<div class="chakra-bg"><i class="fas fa-dharmachakra"></i></div>

<aside class="sidebar">
    <div class="sb-logo">NEO BANK</div>
    <nav class="sb-nav">
        <button class="sb-link active" onclick="location.reload()"><i class="fas fa-th-large"></i> Dashboard</button>
         <a href="home-page.php" class="sb-link"><i class="fas fa-home"></i> Home</a>
        <a href="history.php" class="sb-link"><i class="fas fa-history"></i> History</a>
        <a href="#" class="sb-link"><i class="fas fa-user-circle"></i> Profile</a>
        <a href="#" class="sb-link"><i class="fas fa-cog"></i> Settings</a>
    </nav>
    <div class="sb-footer">
        <button class="sb-logout" onclick="window.location.href='logout.php'"><i class="fas fa-power-off"></i> &nbsp;Logout</button>
    </div>
</aside>

<main class="main">
    <header class="header">
        <div>
            <h2>Namaste, <?php echo $first_name; ?>!</h2>
            <small style="color: gray;">YOUR ACCOUNT NUMBER: <?php echo $acc_no; ?></small>
            <p>Banking Reimagined for the Digital Age</p>
        </div>
        <div class="balance-pill">
            <div class="bal-icon"><i class="fas fa-wallet"></i></div>
            <div>
                <div class="bal-label">Available Balance</div>
                <div class="bal-amount">₹ <span id="balDisplay">0.00</span></div>
            </div>
        </div>
    </header>

    <div class="action-grid">
        <div class="card action-card gold" onclick="openModal('deposit')">
            <div class="ac-icon gold"><i class="fas fa-plus"></i></div>
            <div class="ac-title">Deposit</div>
            <div class="ac-sub">Add secure funds instantly.</div>
        </div>
        <div class="card action-card navy" onclick="openModal('send')">
            <div class="ac-icon navy"><i class="fas fa-paper-plane"></i></div>
            <div class="ac-title">Send Money</div>
            <div class="ac-sub">Transfer to any account.</div>
        </div>
        <div class="card action-card green" onclick="openModal('request')">
            <div class="ac-icon green"><i class="fas fa-hand-holding-usd"></i></div>
            <div class="ac-title">Request</div>
            <div class="ac-sub">Ask someone to pay you.</div>
        </div>
    </div>

    <div class="bottom-grid">
        <div class="card activity-card">
            <div class="sec-title"><i class="fas fa-chart-line"></i> Live Activity</div>
            <?php
            $tx_result = @mysqli_query($conn, "SELECT * FROM transactions WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT 5");
            if ($tx_result && mysqli_num_rows($tx_result) > 0):
                while ($tx = mysqli_fetch_assoc($tx_result)):
                    $is_credit = $tx['type'] === 'credit';
            ?>
            <div class="tx-row">
                <div class="tx-icon" style="<?php echo $is_credit ? '' : 'background:rgba(220,38,38,0.08);color:#dc2626'; ?>">
                    <i class="fas fa-<?php echo $is_credit ? 'arrow-down' : 'arrow-up'; ?>"></i>
                </div>
                <div>
                    <div class="tx-name"><?php echo htmlspecialchars($tx['description'] ?? 'Transaction'); ?></div>
                    <div class="tx-sub"><?php echo date('d M Y', strtotime($tx['created_at'])); ?></div>
                </div>
                <div class="tx-amt <?php echo $is_credit ? '' : 'debit'; ?>">
                    <?php echo $is_credit ? '+' : '-'; ?>₹<?php echo number_format($tx['amount'], 2); ?>
                </div>
            </div>
            <?php endwhile; else: ?>
            <div class="empty-state">
                <i class="fas fa-database"></i> No transactions recorded yet.
            </div>
            <?php endif; ?>
        </div>
        
        <div class="recent-tx-card" onclick="window.location.href='history.php'">
            <div class="sec-h">Recent Transactions <i class="fas fa-arrow-right" style="font-size: 16px;"></i></div>
            <p class="sec-p">Click anywhere to view your full history.</p>
            
            <div style="margin-top: 20px;">
                <?php
                $mini_tx = @mysqli_query($conn, "SELECT * FROM transactions WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT 3");
                if ($mini_tx && mysqli_num_rows($mini_tx) > 0):
                    while ($mtx = mysqli_fetch_assoc($mini_tx)):
                        $is_cr = $mtx['type'] === 'credit';
                ?>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.1); padding: 12px 0; font-size: 14px;">
                    <span style="font-weight: 500;"><?php echo htmlspecialchars($mtx['description'] ?? 'Transaction'); ?></span>
                    <span style="color: <?php echo $is_cr ? 'var(--green)' : '#ff5c5c'; ?>; font-family: 'Bebas Neue', sans-serif; font-size: 16px; letter-spacing: 1px;">
                        <?php echo $is_cr ? '+' : '-'; ?>₹<?php echo number_format($mtx['amount'], 2); ?>
                    </span>
                </div>
                <?php endwhile; else: ?>
                <div style="font-size: 13px; color: rgba(255,255,255,0.6);">No recent activity.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<div class="modal-overlay" id="depositModal">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('depositModal')"><i class="fas fa-times-circle"></i></button>
        <div class="modal-stripe" style="background:var(--gold)"></div>
        <div class="modal-title" style="color:var(--gold)">Deposit Funds</div>
        <div class="modal-sub">Add money to your wallet instantly.</div>
        
        <?php if (!empty($dep_msg)): ?>
            <div class="alert <?php echo $dep_msg['type']; ?>">
                <i class="fas fa-<?php echo $dep_msg['type']==='success'?'check-circle':'exclamation-circle'; ?>"></i>
                <?php echo $dep_msg['text']; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="action" value="deposit">
            <div class="form-group">
                <label class="form-label">Amount to Add (₹)</label>
                <input type="number" name="amount" class="form-input" placeholder="0.00" min="1" step="0.01" required>
            </div>
            <button type="submit" class="form-btn" style="background:var(--gold)">Confirm Deposit</button>
        </form>
    </div>
</div>
<div class="modal-overlay" id="sendModal">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('sendModal')"><i class="fas fa-times-circle"></i></button>
        <div class="modal-stripe" style="background:var(--navy)"></div>
        <div class="modal-title" style="color:var(--navy)">Send Money</div>
        <div class="modal-sub">Transfer funds.</div>
        <?php if (!empty($send_msg)): ?>
        <div class="alert <?php echo $send_msg['type']; ?>">
            <i class="fas fa-<?php echo $send_msg['type']==='success'?'check-circle':'exclamation-circle'; ?>"></i>
            <?php echo $send_msg['text']; ?>
        </div>
        <?php if (!empty($dep_msg)): ?>
    <div class="alert alert-<?php echo $dep_msg['type']; ?>">
        <?php echo $dep_msg['text']; ?>
    </div>
<?php endif; ?>
        <?php endif; ?>
        <form method="POST" action="#sendModal">
            <input type="hidden" name="action" value="send">
            <div class="form-group">
                <label class="form-label">Recipient Account Number</label>
                <input type="text" name="to_account" class="form-input" placeholder="Enter account number" required>
            </div>
            <div class="form-group">
                <label class="form-label">Amount (₹)</label>
                <input type="number" name="amount" class="form-input" placeholder="0.00" min="1" step="0.01" required>
                <div class="form-hint">Available balance: ₹<?php echo number_format($balance, 2); ?></div>
            </div>
            <button type="submit" class="form-btn" style="background:var(--navy)">Send Money</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="requestModal">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('requestModal')"><i class="fas fa-times-circle"></i></button>
        <div class="modal-stripe" style="background:var(--green)"></div>
        <div class="modal-title" style="color:var(--green)">Request Money</div>
        <div class="modal-sub">Send a payment request.</div>
        <?php if (!empty($req_msg)): ?>
        <div class="alert <?php echo $req_msg['type']; ?>">
            <i class="fas fa-<?php echo $req_msg['type']==='success'?'check-circle':'exclamation-circle'; ?>"></i>
            <?php echo $req_msg['text']; ?>
        </div>
        <?php endif; ?>
        <form method="POST" action="#requestModal">
            <input type="hidden" name="action" value="request">
            <div class="form-group">
                <label class="form-label">Request From</label>
                <input type="text" name="from_account" class="form-input" placeholder="Their account number" required>
            </div>
            <div class="form-group">
                <label class="form-label">Amount (₹)</label>
                <input type="number" name="amount" class="form-input" placeholder="0.00" min="1" step="0.01" required>
            </div>
            <button type="submit" class="form-btn" style="background:var(--green)">Send Request</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="depositModal">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('depositModal')"><i class="fas fa-times-circle"></i></button>
        <div class="modal-stripe" style="background:var(--gold)"></div>
        <div class="modal-title" style="color:var(--gold)">Deposit</div>
        <div class="modal-sub">Add money.</div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="deposit">
            <div class="form-group">
                <label class="form-label">Deposit Amount (₹)</label>
                <input type="number" name="amount" class="form-input" placeholder="0.00" min="1" step="0.01" required>
            </div>
            <button type="submit" class="form-btn" style="background:var(--gold)">Deposit Now</button>
        </form>
    </div>
</div>

<div class="chat-window" id="chatWindow">
    <div class="chat-head">
        <span><i class="fas fa-robot" style="color:var(--gold);margin-right:8px"></i>NeoBank Assistant</span>
        <button onclick="toggleChat()" style="background:none;border:none;color:#fff;cursor:pointer;font-size:16px"><i class="fas fa-times"></i></button>
    </div>
    <div class="chat-body" id="chatBody">
        <div class="msg-bot">Hello! I'm your NeoBank assistant. How can I help you today?</div>
    </div>
    <div class="chat-foot">
        <input class="chat-input" id="chatInput" placeholder="Type your message…">
        <button class="chat-send"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>
<button class="chat-fab" onclick="toggleChat()"><i class="fas fa-comment-dots"></i></button>

<script>
const TARGET = <?php echo $balance; ?>;
const el = document.getElementById('balDisplay');
const s = performance.now();
(function count(now) {
    const t = Math.min((now - s) / 1600, 1);
    const e = 1 - Math.pow(1 - t, 4);
    el.textContent = (TARGET * e).toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
    if (t < 1) requestAnimationFrame(count);
})(performance.now());

function openModal(type) {
    const map = { 
        send: 'sendModal', 
        request: 'requestModal', 
        deposit: 'depositModal' // Make sure this line is exactly like this
    };
    const modalId = map[type];
    if(modalId) {
        document.getElementById(modalId).classList.add('open');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
}
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) closeModal(o.id); });
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(o => closeModal(o.id));
});
<?php if (!empty($send_msg)): ?>document.getElementById('sendModal').classList.add('open');<?php endif; ?>
<?php if (!empty($req_msg)):  ?>document.getElementById('requestModal').classList.add('open');<?php endif; ?>
<?php if (!empty($dep_msg)): ?>document.getElementById('depositModal').classList.add('open');<?php endif; ?>

function toggleChat() {
    const w = document.getElementById('chatWindow');
    w.classList.toggle('open');
}
</script>
</body>
</html>