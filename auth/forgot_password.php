<?php
session_start();
require_once '../includes/db.php';

$step = 1;
$error = '';
$success = '';
$question_text = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['verify_email'])) {
        $email = $_POST['email'] ?? '';
        $stmt = $conn->prepare("SELECT security_question FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $_SESSION['reset_email'] = $email;
                $question_text = $res->fetch_assoc()['security_question'];
                $step = 2;
            } else {
                $error = "No account found with that email.";
            }
        }
    } elseif (isset($_POST['verify_answer']) && isset($_SESSION['reset_email'])) {
        $answer_input = strtolower(trim($_POST['sec_answer'] ?? ''));
        $stmt = $conn->prepare("SELECT sec_answer, security_question FROM users WHERE email = ?");
        $stmt->bind_param("s", $_SESSION['reset_email']);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        
        if (strtolower(trim($res['sec_answer'])) === $answer_input) {
            $step = 3;
        } else {
            $error = "Incorrect secret answer.";
            $question_text = $res['security_question'];
            $step = 2;
        }
    } elseif (isset($_POST['reset_password']) && isset($_SESSION['reset_email'])) {
        $new_password = $_POST['new_password'] ?? '';
        if (strlen($new_password) < 8 || !preg_match("/[A-Z]/", $new_password) || !preg_match("/[a-z]/", $new_password) || !preg_match("/[0-9]/", $new_password) || !preg_match("/[\W_]/", $new_password)) {
            $error = "Weak password! Keep it strong (8+ chars, 1 uppercase, 1 symbol).";
            $step = 3;
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed, $_SESSION['reset_email']);
            if ($stmt->execute()) {
                $success = "Password reset successfully! Redirecting to login...";
                unset($_SESSION['reset_email']);
                header("refresh:3;url=login.php");
                $step = 4;
            } else {
                $error = "Failed to update password.";
                $step = 3;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Forgot Password - BCA Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: { colors: { primary: "#5048e5" }, fontFamily: { display: ["Inter", "sans-serif"] } },
            },
        }
    </script>
</head>
<body class="bg-[#f6f6f8] font-display min-h-screen flex flex-col">
<header class="w-full px-6 py-4 flex items-center justify-between border-b border-slate-200 bg-white/80 backdrop-blur-md sticky top-0 z-50">
    <div class="flex items-center gap-3">
        <div class="bg-primary p-2 rounded-lg text-white">
            <span class="material-symbols-outlined block text-2xl">school</span>
        </div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900">BCA Hub</h1>
    </div>
    <div class="flex items-center gap-4">
        <a class="text-sm font-medium text-slate-600 hover:text-primary transition-colors" href="login.php">Back to Login</a>
    </div>
</header>
<main class="flex-1 flex flex-col items-center justify-center p-6 sm:p-12">
    <div class="w-full max-w-[480px]">
        <div class="bg-white shadow-2xl rounded-2xl border border-slate-200 overflow-hidden">
            <div class="p-8 sm:p-10">
                
                <div class="mb-10 text-center">
                    <div class="w-16 h-16 bg-indigo-50 text-primary rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="material-symbols-outlined text-3xl">lock_reset</span>
                    </div>
                    <h2 class="text-3xl font-bold text-slate-900">Forgot Password</h2>
                    <p class="text-slate-500 mt-2.5">
                        <?php 
                        if ($step == 1) echo "Enter your registered email address";
                        if ($step == 2) echo "Answer your security question";
                        if ($step == 3) echo "Create a strong new password";
                        if ($step == 4) echo "Password Recovered";
                        ?>
                    </p>
                </div>
                
                <?php if($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6">
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>

                <?php if($step == 1): ?>
                <form class="space-y-6" method="POST" action="">
                    <div class="space-y-2.5">
                        <label class="text-sm font-semibold text-slate-700">Email Address</label>
                        <div class="relative group">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">email</span>
                            <input class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900" name="email" placeholder="Enter your email" type="email" required/>
                        </div>
                    </div>
                    <button name="verify_email" class="w-full bg-primary hover:bg-primary/95 text-white font-bold py-4 rounded-xl shadow-xl shadow-primary/20 transition-all flex items-center justify-center gap-2.5 group" type="submit">
                        <span>Continue</span>
                        <span class="material-symbols-outlined text-xl group-hover:translate-x-1 transition-transform">arrow_forward</span>
                    </button>
                </form>
                <?php endif; ?>

                <?php if($step == 2): ?>
                <form class="space-y-6" method="POST" action="">
                    <div class="p-4 bg-indigo-50 border border-indigo-100 rounded-xl mb-4 text-center">
                        <p class="text-sm font-bold text-indigo-900 leading-relaxed">
                            Q: <?php echo htmlspecialchars($question_text); ?>
                        </p>
                    </div>
                    <div class="space-y-2.5">
                        <label class="text-sm font-semibold text-slate-700">Your Secret Answer</label>
                        <div class="relative group">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">key</span>
                            <input class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900" name="sec_answer" placeholder="Enter your answer" type="text" required autocomplete="off"/>
                        </div>
                    </div>
                    <button name="verify_answer" class="w-full bg-primary hover:bg-primary/95 text-white font-bold py-4 rounded-xl shadow-xl shadow-primary/20 transition-all flex items-center justify-center gap-2.5" type="submit">
                        <span>Verify Answer</span>
                    </button>
                </form>
                <?php endif; ?>

                <?php if($step == 3): ?>
                <form class="space-y-6" method="POST" action="">
                    <div class="space-y-2.5">
                        <label class="text-sm font-semibold text-slate-700">New Password</label>
                        <div class="relative group">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">lock</span>
                            <input class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900" name="new_password" placeholder="Create new password" type="password" required/>
                        </div>
                        <p class="text-[11px] text-slate-500 font-medium">Suggestion: Use a strong password like <b>BCAhub@2026</b></p>
                    </div>
                    <button name="reset_password" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-4 rounded-xl shadow-xl shadow-emerald-500/20 transition-all flex items-center justify-center gap-2.5" type="submit">
                        <span class="material-symbols-outlined">check_circle</span>
                        <span>Reset Password</span>
                    </button>
                </form>
                <?php endif; ?>

                <?php if($step == 4): ?>
                <div class="text-center pt-4">
                    <a href="login.php" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-xl font-bold hover:shadow-lg transition-all">Go to Login</a>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</main>
</body>
</html>
