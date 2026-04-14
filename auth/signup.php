<?php
session_start();
require_once '../includes/db.php';

$error = '';
$success = '';

if (isset($_POST['signup'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $captcha = $_POST['captcha'] ?? '';
    $security_question = $_POST['security_question'] ?? '';
    $sec_answer = strtolower(trim($_POST['sec_answer'] ?? ''));

    // Verify Captcha
    if (!isset($_SESSION['captcha_ans']) || $captcha != $_SESSION['captcha_ans']) {
        $error = "Incorrect CAPTCHA answer. Please try again.";
    } else {
        // Check if email exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "Email already exists.";
        } else {
            if (!empty($name) && !empty($email) && !empty($password) && !empty($security_question) && !empty($sec_answer)) {
                // Enforce Strong Password Policy
                if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password) || !preg_match("/[\W_]/", $password)) {
                    $error = "Weak password! It must be at least 8 characters long, contain 1 uppercase letter, 1 number, and 1 special character.";
                } else {
                    $password_hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, security_question, sec_answer) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("sssss", $name, $email, $password_hashed, $security_question, $sec_answer);
                        if ($stmt->execute()) {
                            $_SESSION['user_id'] = $stmt->insert_id;
                            $_SESSION['user_name'] = $name;
                            $_SESSION['role'] = 'student';
                            unset($_SESSION['captcha_ans']);
                            header("Location: ../pages/home.php");
                            exit();
                        } else {
                            $error = "Registration failed.";
                        }
                    }
                }
            } else {
                $error = "Please fill all fields.";
            }
        }
    }
}

// Generate new Captcha
$num1 = rand(1, 9);
$num2 = rand(1, 9);
$_SESSION['captcha_ans'] = $num1 + $num2;
$captcha_text = "$num1 + $num2 = ?";
?>
<!DOCTYPE html>
<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Signup - BCA Learning Hub</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700,0..1&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#5048e5",
                        "background-light": "#f6f6f8",
                        "background-dark": "#121121",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100 min-h-screen flex flex-col">
<header class="w-full px-6 py-4 flex items-center justify-between border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-background-dark/80 backdrop-blur-md sticky top-0 z-50">
<div class="flex items-center gap-3">
<div class="bg-primary p-2 rounded-lg text-white">
<span class="material-symbols-outlined block text-2xl">school</span>
</div>
<h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">BCA Hub</h1>
</div>
<div class="flex items-center gap-4">
<a class="text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-primary transition-colors" href="../index.php">Back to Home</a>
</div>
</header>
<main class="flex-1 flex flex-col items-center justify-center p-4 sm:p-12">
<div class="w-full max-w-[440px]">
<!-- Signup Card -->
<div class="bg-white dark:bg-slate-900 shadow-2xl rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
<div class="p-5 sm:p-10">
<div class="mb-8 sm:mb-10">
<h2 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Create Account</h2>
<p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-2">Join the BCA Learning Community</p>
<div class="mt-4 flex items-start gap-2 px-3 py-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800 text-indigo-700 dark:text-indigo-400 text-[10px] font-bold leading-tight uppercase tracking-wider">
<span class="material-symbols-outlined text-[16px] shrink-0">assured_workload</span>
<span>Affiliated with Dr. BR Ambedkar University Agra</span>
</div>
</div>
<?php if($error): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>
<form class="space-y-6" method="POST" action="">
<div class="space-y-2.5">
<label class="text-sm font-semibold text-slate-700 dark:text-slate-300" for="name">Name</label>
<div class="relative group">
<span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">badge</span>
<input class="w-full pl-11 pr-4 py-3.5 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white placeholder:text-slate-400" id="name" name="name" placeholder="Enter your full name" type="text" value="<?php echo htmlspecialchars($name ?? ''); ?>" required/>
</div>
</div>
<div class="space-y-2.5">
<label class="text-sm font-semibold text-slate-700 dark:text-slate-300" for="email">Email</label>
<div class="relative group">
<span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">email</span>
<input class="w-full pl-11 pr-4 py-3.5 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white placeholder:text-slate-400" id="email" name="email" placeholder="Enter your email" type="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required/>
</div>
</div>
<div class="space-y-2.5">
<label class="text-sm font-semibold text-slate-700 dark:text-slate-300" for="password">Password</label>
<div class="relative group">
<span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">lock</span>
<input class="w-full pl-11 pr-12 py-3.5 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white placeholder:text-slate-400" id="password" name="password" placeholder="Create a password" type="password" required/>
</div>
<p class="text-[11px] text-slate-500 font-medium ml-1">Suggestion: Use a strong password like <b class="text-slate-700 dark:text-slate-300">BCAhub@2026</b></p>
</div>
<div class="space-y-2.5">
<label class="text-sm font-semibold text-slate-700 dark:text-slate-300" for="security_question">Security Question (For Password Reset)</label>
<div class="relative group">
<span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">help</span>
<select class="w-full pl-11 pr-4 py-3.5 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white" id="security_question" name="security_question" required>
    <option value="What is your childhood nickname?">What is your childhood nickname?</option>
    <option value="What is the name of your first school?">What is the name of your first school?</option>
    <option value="What is your favorite book?">What is your favorite book?</option>
</select>
</div>
</div>
<div class="space-y-2.5">
<label class="text-sm font-semibold text-slate-700 dark:text-slate-300" for="sec_answer">Your Secret Answer</label>
<div class="relative group">
<span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">key</span>
<input class="w-full pl-11 pr-4 py-3.5 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white placeholder:text-slate-400" id="sec_answer" name="sec_answer" placeholder="Enter answer (keep it safe)" type="text" required autocomplete="off"/>
</div>
</div>
<div class="space-y-2.5">
<label class="text-sm font-semibold text-slate-700 dark:text-slate-300" for="captcha">Solve: <?php echo $captcha_text; ?></label>
<div class="relative group">
<span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">calculate</span>
<input class="w-full pl-11 pr-4 py-3.5 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white placeholder:text-slate-400" id="captcha" name="captcha" placeholder="Enter answer" type="number" required autocomplete="off"/>
</div>
</div>

<button name="signup" class="w-full bg-primary hover:bg-primary/95 active:scale-[0.985] text-white font-bold py-4 rounded-xl shadow-xl shadow-primary/20 transition-all flex items-center justify-center gap-2.5 group" type="submit">
<span>Sign Up</span>
<span class="material-symbols-outlined text-xl group-hover:translate-x-1 transition-transform">arrow_forward</span>
</button>
</form>

</div>
<div class="bg-slate-50 dark:bg-slate-800/50 p-8 text-center border-t border-slate-200 dark:border-slate-800">
<p class="text-sm text-slate-600 dark:text-slate-400">
                        Already have an account? 
                        <a class="text-primary font-bold hover:underline ml-1" href="login.php">Sign in</a>
</p>
</div>
</div>
<!-- Decorative background elements -->
<div class="mt-10 flex justify-center gap-10">
<div class="flex items-center gap-2 text-slate-400 text-[10px] uppercase tracking-[0.2em] font-bold">
<span class="material-symbols-outlined text-base">verified_user</span>
<span>Secure Access</span>
</div>
<div class="flex items-center gap-2 text-slate-400 text-[10px] uppercase tracking-[0.2em] font-bold">
<span class="material-symbols-outlined text-base">language</span>
<span>Global Campus</span>
</div>
</div>
</div>
</main>
<footer class="p-8 text-center text-slate-500 dark:text-slate-600 text-xs mt-auto">
<p>© 2024 BCA Learning Hub. All rights reserved.</p>
</footer>
<!-- Background Decoration -->
<div class="fixed top-0 left-0 -z-10 w-full h-full overflow-hidden opacity-20 pointer-events-none">
<div class="absolute -top-24 -left-24 w-96 h-96 bg-primary/20 rounded-full blur-3xl"></div>
<div class="absolute top-1/2 -right-24 w-64 h-64 bg-cyan-400/20 rounded-full blur-3xl"></div>
<div class="absolute -bottom-24 left-1/4 w-80 h-80 bg-primary/10 rounded-full blur-3xl"></div>
</div>
</body></html>
