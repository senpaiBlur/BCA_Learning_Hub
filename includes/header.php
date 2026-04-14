<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$base_url = ''; // You may need to adjust this depending on the server root
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>BCA Learning Hub</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries,typography"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#4F46E5",
                        "background-light": "#FFFFFF",
                        "background-dark": "#0F172A",
                    },
                    fontFamily: {
                        "display": ["Poppins", "Inter", "sans-serif"],
                        "sans": ["Inter", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "12px",
                        "lg": "12px",
                        "xl": "16px",
                        "2xl": "24px",
                        "full": "9999px"
                    },
                    animation: {
                        'bounce-slow': 'bounce 3s infinite',
                    }
                },
            },
        }
    </script>
<style>
        body {
            font-family: 'Inter', sans-serif;
            scroll-behavior: smooth;
        }
        h1, h2, h3, h4 {
            font-family: 'Poppins', sans-serif;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
        }
        .dark .glass-effect {
            background: rgba(15, 23, 42, 0.8);
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 transition-colors duration-200">
<!-- Navigation -->
<header class="sticky top-0 z-50 w-full glass-effect border-b border-slate-100/80 dark:border-slate-800">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
<div class="flex items-center justify-between h-16 sm:h-20">
<div class="flex items-center gap-10">
<a class="flex items-center gap-2 group transition-transform active:scale-95" href="../pages/home.php">
<div class="bg-primary p-1.5 sm:p-2 rounded-xl text-white shadow-lg shadow-primary/20 group-hover:rotate-6 transition-transform">
<span class="material-symbols-outlined block text-xl sm:text-2xl">school</span>
</div>
<h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">BCA Hub</h1>
</a>
<nav class="hidden md:flex items-center gap-6">
<a class="text-sm font-bold text-slate-500 hover:text-primary transition-colors" href="../pages/home.php">Dashboard</a>
<a class="text-sm font-bold text-slate-500 hover:text-primary transition-colors" href="../pages/my_learning.php">My Learning</a>
<a class="text-sm font-bold text-slate-500 hover:text-primary transition-colors" href="../pages/my_uploads.php">My Uploads</a>
</nav>
</div>
<div class="flex items-center gap-4 flex-1 justify-end">
<?php 
if(isset($_SESSION['user_id']) && !isset($_SESSION['role']) && isset($conn)) {
    $rStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $rStmt->bind_param("i", $_SESSION['user_id']);
    $rStmt->execute();
    $r_res = $rStmt->get_result()->fetch_assoc();
    $_SESSION['role'] = $r_res ? $r_res['role'] : 'student';
}
?>
<div class="hidden md:flex items-center gap-4">
<?php if(isset($_SESSION['user_id'])): ?>
    <?php 
        require_once __DIR__ . '/permissions.php';
        $role_level = get_role_level($_SESSION['role'] ?? ROLE_STUDENT);
    ?>
    <?php if($role_level <= 3): ?>
        <a href="../pages/admin.php" class="flex items-center gap-1.5 text-sm font-bold text-amber-500 hover:text-amber-600 transition-colors">
            <span class="material-symbols-outlined text-[18px]">admin_panel_settings</span>
            <?php echo ucfirst($_SESSION['role']); ?>
        </a>
    <?php endif; ?>
    <a href="../pages/profile.php" class="flex items-center gap-1.5 text-sm font-bold text-slate-600 dark:text-slate-300 hover:text-primary transition-colors pr-2 border-r border-slate-200 dark:border-slate-700">
        <span class="material-symbols-outlined text-[18px]">account_circle</span>
        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
    </a>
    <a href="../auth/logout.php" class="flex items-center gap-2 bg-red-500 text-white px-6 py-2.5 rounded-xl font-bold text-sm hover:bg-red-600 hover:shadow-lg transition-all active:scale-95">
        <span>Logout</span>
    </a>
<?php else: ?>
    <a href="../auth/login.php" class="flex items-center gap-2 bg-primary text-white px-6 py-2.5 rounded-xl font-bold text-sm hover:bg-primary/90 hover:shadow-lg hover:shadow-primary/30 transition-all active:scale-95">
        <span>Login</span>
    </a>
<?php endif; ?>
</div>

<!-- Mobile Menu Button -->
<button id="mobile-menu-toggle" class="md:hidden p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-primary hover:text-white transition-all">
    <span class="material-symbols-outlined text-xl" id="menu-icon">menu</span>
</button>
</div>
</div>

<!-- Mobile Menu Container -->
<div id="mobile-menu" class="hidden md:hidden overflow-hidden transition-all duration-300 border-t border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 pb-6 rounded-b-2xl shadow-2xl">
    <nav class="flex flex-col p-4 gap-2">
        <a class="flex items-center gap-3 p-3.5 text-base font-bold text-slate-600 dark:text-slate-300 hover:bg-primary/5 hover:text-primary rounded-xl transition-all" href="../pages/home.php">
            <span class="material-symbols-outlined">dashboard</span> Dashboard
        </a>
        <a class="flex items-center gap-3 p-3.5 text-base font-bold text-slate-600 dark:text-slate-300 hover:bg-primary/5 hover:text-primary rounded-xl transition-all" href="../pages/my_learning.php">
            <span class="material-symbols-outlined">school</span> My Learning
        </a>
        <a class="flex items-center gap-3 p-3.5 text-base font-bold text-slate-600 dark:text-slate-300 hover:bg-primary/5 hover:text-primary rounded-xl transition-all" href="../pages/my_uploads.php">
            <span class="material-symbols-outlined">cloud_upload</span> My Uploads
        </a>
        <hr class="my-2 border-slate-100 dark:border-slate-800">
        <?php if(isset($_SESSION['user_id'])): ?>
            <?php if($role_level <= 3): ?>
                <a class="flex items-center gap-3 p-3.5 text-base font-bold text-amber-500 hover:bg-amber-50 rounded-xl transition-all" href="../pages/admin.php">
                    <span class="material-symbols-outlined">admin_panel_settings</span> Admin Panel
                </a>
            <?php endif; ?>
            <a class="flex items-center gap-3 p-3.5 text-base font-bold text-slate-600 dark:text-slate-300 hover:bg-primary/5 hover:text-primary rounded-xl transition-all" href="../pages/profile.php">
                <span class="material-symbols-outlined">account_circle</span> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </a>
            <a class="flex items-center gap-3 p-3.5 text-base font-bold text-red-500 hover:bg-red-50 rounded-xl transition-all" href="../auth/logout.php">
                <span class="material-symbols-outlined">logout</span> Logout
            </a>
        <?php else: ?>
            <a class="flex items-center justify-center gap-2 m-2 bg-primary text-white p-3.5 rounded-xl font-bold text-base shadow-lg shadow-primary/30" href="../auth/login.php">
                <span>Login to BCA Hub</span>
            </a>
        <?php endif; ?>
    </nav>
</div>
</div>
</header>

<script>
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const menuIcon = document.getElementById('menu-icon');

    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', () => {
            const isHidden = mobileMenu.classList.contains('hidden');
            if (isHidden) {
                mobileMenu.classList.remove('hidden');
                menuIcon.innerText = 'close';
            } else {
                mobileMenu.classList.add('hidden');
                menuIcon.innerText = 'menu';
            }
        });
    }
</script>
