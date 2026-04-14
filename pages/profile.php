<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    if (empty($name)) {
        $error = "Name cannot be empty.";
    } else {
        if (!empty($new_password)) {
            if (empty($old_password)) {
                $error = "Please enter your Current Password to set a new one.";
            } else {
                $pStmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $pStmt->bind_param("i", $user_id);
                $pStmt->execute();
                $db_pass = $pStmt->get_result()->fetch_assoc()['password'];
                
                if (!password_verify($old_password, $db_pass)) {
                    $error = "Incorrect Current Password.";
                } elseif (strlen($new_password) < 8 || !preg_match("/[A-Z]/", $new_password) || !preg_match("/[a-z]/", $new_password) || !preg_match("/[0-9]/", $new_password) || !preg_match("/[\W_]/", $new_password)) {
                    $error = "Weak New Password! It must be at least 8 chars long with 1 uppercase, 1 lowercase, 1 number, and 1 special char.";
                } else {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET name = ?, password = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $name, $hashed, $user_id);
                }
            }
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $user_id);
        }

        if (empty($error) && isset($stmt) && $stmt->execute()) {
            $_SESSION['user_name'] = $name;
            $success = "Profile updated successfully!";
        } else {
            if (empty($error)) {
                $error = "Failed to update profile.";
            }
        }
    }
}

// Fetch current user info
$stmt = $conn->prepare("SELECT name, email, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();

include '../includes/header.php';
?>
<main class="flex-1 max-w-3xl mx-auto w-full p-4 sm:p-12 my-6 sm:my-10">
    <div class="bg-white dark:bg-slate-900 shadow-2xl rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="p-5 sm:p-10">
            <div class="mb-6 sm:mb-10 flex items-center gap-4 border-b border-slate-100 dark:border-slate-800 pb-5 sm:pb-6">
                <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 flex items-center justify-center text-xl sm:text-2xl font-bold uppercase shrink-0">
                    <?php echo substr(htmlspecialchars($current_user['name']), 0, 1); ?>
                </div>
                <div class="min-w-0">
                    <h2 class="text-xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Profile Settings</h2>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">Manage your digital identity</p>
                </div>
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

            <form class="space-y-6" method="POST" action="">
                <div class="space-y-2.5">
                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Email Address (Read Only)</label>
                    <div class="relative group">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">email</span>
                        <input class="w-full pl-11 pr-4 py-3 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-500 cursor-not-allowed" type="email" value="<?php echo htmlspecialchars($current_user['email']); ?>" disabled/>
                    </div>
                </div>

                <div class="space-y-2.5">
                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300" for="name">Full Name</label>
                    <div class="relative group">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">badge</span>
                        <input class="w-full pl-11 pr-4 py-3.5 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white" id="name" name="name" type="text" value="<?php echo htmlspecialchars($current_user['name']); ?>" required/>
                    </div>
                </div>

                <div class="space-y-4 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200">Change Password</p>
                    <div class="space-y-2.5">
                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-300" for="old_password">Current Password (Required only if changing password)</label>
                        <div class="relative group">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">key</span>
                            <input class="w-full pl-11 pr-12 py-3.5 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white placeholder:text-slate-400" id="old_password" name="old_password" placeholder="Enter current password" type="password"/>
                        </div>
                    </div>

                    <div class="space-y-2.5">
                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-300" for="new_password">New Password</label>
                        <div class="relative group">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">lock</span>
                            <input class="w-full pl-11 pr-12 py-3.5 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-slate-900 dark:text-white placeholder:text-slate-400" id="new_password" name="new_password" placeholder="Create new password" type="password"/>
                        </div>
                        <p class="text-[11px] text-slate-500 font-medium ml-1">Suggestion: Use a strong password like <b class="text-slate-700 dark:text-slate-300">BCAhub@2026</b></p>
                    </div>
                </div>

                <div class="pt-4 flex justify-end">
                    <button name="update_profile" class="w-full sm:w-auto bg-primary hover:bg-primary/95 active:scale-[0.985] text-white font-black py-4 px-8 rounded-2xl shadow-xl shadow-primary/30 transition-all flex items-center justify-center gap-2.5 group" type="submit">
                        <span class="material-symbols-outlined text-xl transition-transform group-hover:scale-110">save</span>
                        <span>Save Profile Changes</span>
                    </button>
                </div>
            </form>
            
            <div class="mt-10 pt-6 border-t border-slate-100 dark:border-slate-800 text-center">
                <p class="text-xs text-slate-400">Account created on: <?php echo date('M d, Y', strtotime($current_user['created_at'])); ?></p>
            </div>
        </div>
    </div>
</main>
<?php include '../includes/footer.php'; ?>
