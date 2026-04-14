<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/permissions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Fetch dynamic role
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$roleObj = $stmt->get_result()->fetch_assoc();
$current_role = $roleObj ? $roleObj['role'] : ROLE_STUDENT;
$_SESSION['role'] = $current_role;

// Hierarchy Check: Owner, Co-partner, and Admin are allowed
if (get_role_level($current_role) > 3) {
    echo "<!DOCTYPE html><html class='light'><head><script src='https://cdn.tailwindcss.com'></script></head>
          <body class='flex items-center justify-center min-h-screen bg-slate-50'>
            <div class='text-center space-y-4'>
                <h1 class='text-4xl font-bold text-red-500'>Access Denied</h1>
                <p class='text-slate-500'>You do not have Administrator privileges.</p>
                <a href='home.php' class='inline-block px-4 py-2 bg-primary text-white rounded-lg font-bold'>Back to Home</a>
            </div>
          </body></html>";
    exit();
}

// Fetch stats
$u_res = $conn->query("SELECT COUNT(*) as c FROM users");
$total_users = $u_res->fetch_assoc()['c'];

$s_res = $conn->query("SELECT COUNT(*) as c FROM subjects");
$total_subjects = $s_res->fetch_assoc()['c'];

$m_res = $conn->query("SELECT COUNT(*) as c FROM materials");
$total_materials = $m_res->fetch_assoc()['c'];

// Fetch users with upload counts and search
$search = $_GET['search'] ?? '';
$search_query = "";
if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $search_query = " WHERE name LIKE '%$search_safe%' OR email LIKE '%$search_safe%' ";
}

$users_sql = "SELECT u.*, (SELECT COUNT(*) FROM materials m WHERE m.uploader_id = u.id) as materials_count 
              FROM users u 
              $search_query
              ORDER BY created_at DESC LIMIT 100";
$users = $conn->query($users_sql);

// Additional stats for the new grid layout
$admins_count_res = $conn->query("SELECT COUNT(*) as c FROM users WHERE role IN ('admin', 'owner', 'co-partner')");
$admins_count = $admins_count_res->fetch_assoc()['c'];
$students_count_res = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'student'");
$students_count = $students_count_res->fetch_assoc()['c'];

include '../includes/header.php';
?>
<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    
    .role-dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        width: 170px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
        z-index: 50;
        border: 1px solid #f1f5f9;
        padding: 8px;
    }
    .role-dropdown.show { display: block; animation: slideUp 0.2s ease-out; }
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<!-- Hero Admin Section -->
<div class="bg-slate-900 text-white py-12 sm:py-20 mb-8 sm:mb-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="text-center md:text-left">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/20 text-primary-200 text-[10px] font-black uppercase tracking-widest mb-4">
                    <span class="material-symbols-outlined text-[14px]">admin_panel_settings</span>
                    System Administration
                </div>
                <h1 class="text-3xl sm:text-5xl font-black tracking-tight mb-4">Console Dashboard</h1>
                <p class="text-slate-400 font-medium max-w-xl">Global management interface for users and academic resources.</p>
            </div>
            <button onclick="document.getElementById('createUserModal').classList.remove('hidden')" class="w-full sm:w-auto bg-primary hover:bg-primary/90 text-white px-8 py-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2 shadow-2xl shadow-primary/40 transition-all active:scale-95 group">
                <span class="material-symbols-outlined text-xl group-hover:rotate-12 transition-transform">person_add</span>
                Onboard New User
            </button>
        </div>
    </div>
</div>

<main class="max-w-7xl mx-auto px-3.5 sm:px-6 lg:px-8 -mt-16 sm:-mt-20 relative z-10 pb-20">
    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-10">
        <!-- Stat Card 1 -->
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-4 sm:p-8 rounded-[2rem] shadow-sm hover:shadow-xl transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center text-indigo-600">
                    <span class="material-symbols-outlined text-xl sm:text-2xl">group</span>
                </div>
                <span class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">Total</span>
            </div>
            <p class="text-xs sm:text-sm font-bold text-slate-500 uppercase tracking-widest">Active Users</p>
            <p class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white mt-1"><?php echo $total_users; ?></p>
        </div>
        <!-- Stat Card 2 -->
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-4 sm:p-8 rounded-[2rem] shadow-sm hover:shadow-xl transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-emerald-50 dark:bg-emerald-900/30 rounded-2xl flex items-center justify-center text-emerald-600">
                    <span class="material-symbols-outlined text-xl sm:text-2xl">verified_user</span>
                </div>
                <span class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">Security</span>
            </div>
            <p class="text-xs sm:text-sm font-bold text-slate-500 uppercase tracking-widest">Admins</p>
            <p class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white mt-1"><?php echo $admins_count; ?></p>
        </div>
        <!-- Stat Card 3 -->
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-4 sm:p-8 rounded-[2rem] shadow-sm hover:shadow-xl transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-amber-50 dark:bg-amber-900/30 rounded-2xl flex items-center justify-center text-amber-600">
                    <span class="material-symbols-outlined text-xl sm:text-2xl">school</span>
                </div>
                <span class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">Growth</span>
            </div>
            <p class="text-xs sm:text-sm font-bold text-slate-500 uppercase tracking-widest">Students</p>
            <p class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white mt-1"><?php echo $students_count; ?></p>
        </div>
        <!-- Stat Card 4 -->
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-4 sm:p-8 rounded-[2rem] shadow-sm hover:shadow-xl transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-purple-50 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center text-purple-600">
                    <span class="material-symbols-outlined text-xl sm:text-2xl">auto_stories</span>
                </div>
                <span class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">Content</span>
            </div>
            <p class="text-xs sm:text-sm font-bold text-slate-500 uppercase tracking-widest">Subjects</p>
            <p class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white mt-1"><?php echo $total_subjects; ?></p>
        </div>
    </div>

    <!-- User Management Section -->
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-[2rem] overflow-hidden shadow-sm">
        <div class="p-6 border-b border-slate-50 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/20 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-xl">badge</span>
                </div>
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Identity Manager</h2>
                    <p class="text-xs text-slate-500 font-medium">Review and manage platform stakeholders.</p>
                </div>
            </div>
            
            <form method="GET" action="" class="relative group max-w-sm w-full">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">search</span>
                <input type="text" name="search" placeholder="Search accounts..." value="<?php echo htmlspecialchars($search); ?>" 
                       class="w-full pl-12 pr-4 py-3 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl text-sm font-bold focus:ring-4 focus:ring-primary/10 transition-all outline-none" />
                <?php if($search): ?>
                    <a href="admin.php" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 hover:text-red-500 transition-colors"><span class="material-symbols-outlined text-lg">cancel</span></a>
                <?php endif; ?>
            </form>
        </div>

        <div class="overflow-x-auto min-h-[400px] custom-scrollbar" style="max-height: 520px; overflow-y: auto;">
            <table class="w-full text-left">
                <thead class="sticky top-0 z-20 bg-slate-50/80 dark:bg-slate-800/80 backdrop-blur-md">
                    <tr>
                        <th class="px-4 sm:px-8 py-4 sm:py-6 text-left text-[9px] sm:text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] whitespace-nowrap">Identity Details</th>
                        <th class="px-4 sm:px-8 py-4 sm:py-6 text-left text-[9px] sm:text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] whitespace-nowrap">Email Address</th>
                        <th class="px-4 sm:px-8 py-4 sm:py-6 text-left text-[9px] sm:text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] whitespace-nowrap">System Role</th>
                        <th class="px-4 sm:px-8 py-4 sm:py-6 text-right text-[9px] sm:text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] whitespace-nowrap">Operations</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                    <?php while($row = $users->fetch_assoc()): ?>
                    <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                        <td class="px-4 sm:px-8 py-4 sm:py-6">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 flex items-center justify-center text-slate-400 font-bold shrink-0">
                                    <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($row['name']); ?></p>
                                    <p class="text-[9px] text-slate-400 font-black uppercase tracking-widest mt-0.5">ID: #<?php echo $row['id']; ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 sm:px-8 py-4 sm:py-6">
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate"><?php echo htmlspecialchars($row['email']); ?></p>
                        </td>
                        <td class="px-4 sm:px-8 py-4 sm:py-6">
                            <div class="flex items-center gap-2 relative">
                                <?php 
                                    $role_class = 'bg-slate-50 text-slate-500 border-slate-100';
                                    $role_icon = 'person';
                                    if($row['role'] === ROLE_OWNER) { $role_class = 'bg-purple-50 text-purple-600 border-purple-100'; $role_icon = 'workspace_premium'; }
                                    if($row['role'] === ROLE_CO_PARTNER) { $role_class = 'bg-amber-50 text-amber-600 border-amber-100'; $role_icon = 'shield_person'; }
                                    if($row['role'] === ROLE_ADMIN) { $role_class = 'bg-blue-50 text-blue-600 border-blue-100'; $role_icon = 'admin_panel_settings'; }
                                    if($row['role'] === ROLE_STUDENT) { $role_class = 'bg-emerald-50 text-emerald-600 border-emerald-100'; $role_icon = 'school'; }
                                ?>
                                <span class="px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border <?php echo $role_class; ?> flex items-center gap-2 shadow-sm whitespace-nowrap">
                                    <span class="material-symbols-outlined text-[14px]"><?php echo $role_icon; ?></span>
                                    <?php echo htmlspecialchars($row['role']); ?>
                                </span>

                                <?php if(($current_role === ROLE_OWNER || $current_role === ROLE_CO_PARTNER) && $row['id'] != $_SESSION['user_id']): ?>
                                    <button onclick="toggleRoleMenu(this)" class="w-8 h-8 flex items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all text-slate-400 hover:text-primary">
                                        <span class="material-symbols-outlined text-[18px]">expand_circle_down</span>
                                    </button>
                                    <div class="role-dropdown">
                                        <div class="px-3 py-2 text-[9px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-50 mb-1">Set Permissions</div>
                                        <?php 
                                            $assignable = get_assignable_roles($current_role);
                                            foreach($assignable as $role): 
                                                if($role === $row['role']) continue;
                                                
                                                $displayText = ucfirst($role);
                                                $opt_icon = 'person';
                                                
                                                if($role === ROLE_OWNER) {
                                                    if($current_role === ROLE_OWNER && $row['role'] === ROLE_CO_PARTNER) $displayText = "Full Transfer";
                                                    else continue;
                                                }

                                                if($role === ROLE_OWNER) $opt_icon = 'workspace_premium';
                                                if($role === ROLE_CO_PARTNER) $opt_icon = 'shield_person';
                                                if($role === ROLE_ADMIN) $opt_icon = 'admin_panel_settings';
                                                if($role === ROLE_STUDENT) $opt_icon = 'school';
                                        ?>
                                            <button onclick="updateRole(<?php echo $row['id']; ?>, '<?php echo $role; ?>')" class="w-full flex items-center gap-3 px-3 py-2.5 text-[11px] font-black text-slate-600 dark:text-slate-300 hover:bg-primary/5 hover:text-primary rounded-xl transition-all">
                                                <span class="material-symbols-outlined text-[16px]"><?php echo $opt_icon; ?></span>
                                                <?php echo $displayText; ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 sm:px-8 py-4 sm:py-6 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="admin_user_view.php?user_id=<?php echo $row['id']; ?>" class="w-9 h-9 flex items-center justify-center bg-slate-50 dark:bg-slate-800 hover:bg-primary hover:text-white transition-all rounded-xl text-slate-400 group/btn" title="Audit Contributions">
                                    <span class="material-symbols-outlined text-[18px]">query_stats</span>
                                </a>
                                <?php if(can_manage_user($current_role, $row['role']) && $row['id'] != $_SESSION['user_id']): ?>
                                    <button onclick="deleteUser(<?php echo $row['id']; ?>)" class="w-9 h-9 flex items-center justify-center bg-red-50 dark:bg-red-900/10 text-red-500 hover:bg-red-500 hover:text-white transition-all rounded-xl" title="Revoke Access">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    
                    <?php if($users->num_rows == 0): ?>
                    <tr>
                        <td colspan="4" class="px-8 py-20 text-center">
                            <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                                <span class="material-symbols-outlined text-4xl text-slate-300">search_off</span>
                            </div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white">No identities found</h3>
                            <p class="text-slate-400 text-xs font-medium italic">Adjust your search parameters and try again.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Create User Modal -->
<div id="createUserModal" class="hidden fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[100] flex items-center justify-center p-4 animate-in fade-in duration-300">
    <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden animate-in zoom-in-95 duration-200">
        <div class="p-5 sm:p-8 border-b border-slate-50 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-slate-900">
            <div>
                <p class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-primary mb-1">Onboarding</p>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">Provision Account</h3>
            </div>
            <button onclick="document.getElementById('createUserModal').classList.add('hidden')" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-400 transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form onsubmit="createUser(event)" class="p-5 sm:p-8 space-y-4 sm:space-y-6">
            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Legal Identity</label>
                <input name="name" type="text" required placeholder="Full Name" 
                       class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl outline-none focus:ring-4 focus:ring-primary/10 font-bold transition-all text-slate-900 dark:text-white" />
            </div>
            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Electronic Mail</label>
                <input name="email" type="email" required placeholder="name@learninghub.com" 
                       class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl outline-none focus:ring-4 focus:ring-primary/10 font-bold transition-all text-slate-900 dark:text-white" />
            </div>
            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Access Credential</label>
                <input name="password" type="password" required placeholder="••••••••" 
                       class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl outline-none focus:ring-4 focus:ring-primary/10 font-bold transition-all text-slate-900 dark:text-white" />
            </div>
            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Authority Level</label>
                <select name="role" class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl outline-none focus:ring-4 focus:ring-primary/10 font-black cursor-pointer appearance-none text-slate-900 dark:text-white">
                    <?php foreach(get_assignable_roles($current_role) as $role): ?>
                        <option value="<?php echo $role; ?>"><?php echo ucfirst($role); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="w-full bg-primary py-4 rounded-2xl text-white font-black shadow-xl shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all mt-4">Generate Token & Create</button>
        </form>
    </div>
</div>

<script>
function toggleRoleMenu(btn) {
    const menu = btn.nextElementSibling;
    const allMenus = document.querySelectorAll('.role-dropdown');
    
    // Close others
    allMenus.forEach(m => { if(m !== menu) m.classList.remove('show'); });
    
    // Toggle current
    menu.classList.toggle('show');
    
    // Close on click outside
    const closeMenu = (e) => {
        if (!menu.contains(e.target) && !btn.contains(e.target)) {
            menu.classList.remove('show');
            document.removeEventListener('click', closeMenu);
        }
    };
    if(menu.classList.contains('show')) document.addEventListener('click', closeMenu);
}

async function createUser(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Initializing...';

    const formData = new FormData(e.target);
    formData.append('action', 'create');
    
    try {
        const res = await fetch('../api/manage_user.php', { method: 'POST', body: formData });
        const data = await res.json();
        alert(data.message);
        if(data.success) location.reload();
    } catch(err) {
        alert('Internal server error during provisioning.');
    } finally {
        btn.disabled = false;
        btn.innerText = originalText;
    }
}

async function deleteUser(id) {
    if(!confirm('DANGER: This will permanently revoke all access and wipe this identity from the HUB. Are you certain?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('user_id', id);
    
    try {
        const res = await fetch('../api/manage_user.php', { method: 'POST', body: formData });
        const data = await res.json();
        alert(data.message);
        if(data.success) location.reload();
    } catch(err) {
        alert('Network error during deletion.');
    }
}

async function updateRole(id, newRole) {
    if(!newRole) return;
    if(!confirm(`CAUTION: You are about to re-assign this identity to the "${newRole.toUpperCase()}" authority level. Proceed?`)) { 
        return; 
    }
    
    const formData = new FormData();
    formData.append('action', 'update_role');
    formData.append('user_id', id);
    formData.append('new_role', newRole);
    
    try {
        const res = await fetch('../api/manage_user.php', { method: 'POST', body: formData });
        const data = await res.json();
        alert(data.message);
        if(data.success) location.reload();
    } catch(err) {
        alert('Network error during role update.');
    }
}
</script>

<?php include '../includes/footer.php'; ?>
