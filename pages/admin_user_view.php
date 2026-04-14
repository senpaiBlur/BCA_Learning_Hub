<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/permissions.php';

// Auth check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$current_role = $stmt->get_result()->fetch_assoc()['role'] ?? 'student';

// Hierarchy Check: Owner, Co-partner, and Admin are allowed (Level <= 3)
if (get_role_level($current_role) > 3) {
    header("Location: home.php");
    exit();
}

$target_user_id = $_GET['user_id'] ?? 0;
if (!$target_user_id) {
    header("Location: admin.php");
    exit();
}

// Handle material deletion
if (isset($_POST['delete_material_id'])) {
    $del_id = (int)$_POST['delete_material_id'];
    $delStmt = $conn->prepare("DELETE FROM materials WHERE id = ?");
    $delStmt->bind_param("i", $del_id);
    $delStmt->execute();
    $msg = "Material deleted successfully.";
}

// Fetch user info
$userStmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->bind_param("i", $target_user_id);
$userStmt->execute();
$user_info = $userStmt->get_result()->fetch_assoc();

if (!$user_info) {
    header("Location: admin.php");
    exit();
}

// Fetch user's materials
$matSql = "SELECT m.*, s.title as subject_title, s.category 
           FROM materials m 
           JOIN subjects s ON m.subject_id = s.id 
           WHERE m.uploader_id = ? 
           ORDER BY m.created_at DESC";
$matStmt = $conn->prepare($matSql);
$matStmt->bind_param("i", $target_user_id);
$matStmt->execute();
$materials = $matStmt->get_result();

include '../includes/header.php';
?>

<main class="max-w-7xl mx-auto w-full p-3.5 sm:p-8 space-y-6 sm:space-y-10">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm">
        <div class="flex items-center gap-4 sm:gap-5">
            <a href="admin.php" class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-primary hover:text-white flex items-center justify-center transition-all text-slate-400 group shrink-0">
                <span class="material-symbols-outlined text-xl group-hover:-translate-x-1 transition-transform">arrow_back</span>
            </a>
            <div class="min-w-0">
                <p class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-primary mb-1">Contributor Profile</p>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight truncate">
                    <?php echo htmlspecialchars($user_info['name']); ?>
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 font-medium truncate"><?php echo htmlspecialchars($user_info['email']); ?></p>
            </div>
        </div>
        <div class="flex items-center gap-4">
             <div class="px-5 sm:px-6 py-2.5 sm:py-3 bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700 rounded-2xl flex flex-col">
                 <p class="text-[9px] sm:text-[10px] uppercase font-black text-slate-400 tracking-widest leading-none mb-1">Total Assets</p>
                 <p class="text-xl sm:text-2xl font-black text-primary leading-none"><?php echo $materials->num_rows; ?></p>
             </div>
        </div>
    </div>

    <?php if(isset($msg)): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-4 rounded-xl flex items-center gap-3 animate-in fade-in slide-in-from-top-2">
        <span class="material-symbols-outlined">check_circle</span>
        <span class="text-sm font-bold"><?php echo $msg; ?></span>
    </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-3xl overflow-hidden shadow-sm">
        <!-- Search & Filter Controls -->
        <!-- Search & Filter Controls -->
        <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/20 flex flex-col sm:flex-row gap-3 sm:gap-4">
            <div class="relative flex-grow">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
                <input type="text" id="adminMatSearch" placeholder="Filter contributions..." 
                       class="w-full pl-12 pr-4 py-3 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl text-sm focus:ring-4 focus:ring-primary/10 transition-all outline-none font-bold">
            </div>
            <select id="adminTypeFilter" class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl text-sm py-3 px-4 focus:ring-4 focus:ring-primary/10 cursor-pointer w-full sm:min-w-[160px] outline-none font-bold text-slate-600 dark:text-slate-300">
                <option value="all">All Content</option>
                <option value="video">Videos</option>
                <option value="notes">Notes</option>
                <option value="exam">Exams</option>
            </select>
        </div>

        <div class="overflow-x-auto min-h-[400px]" id="adminMatList">
            <table class="w-full text-left" id="adminMatTable">
                <thead class="bg-slate-50/50 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-4 sm:px-8 py-4 sm:py-5 text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-slate-400">Content Detail</th>
                        <th class="px-4 sm:px-8 py-4 sm:py-5 text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-slate-400">Classification</th>
                        <th class="px-4 sm:px-8 py-4 sm:py-5 text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-slate-400">Timestamp</th>
                        <th class="px-4 sm:px-8 py-4 sm:py-5 text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php while($row = $materials->fetch_assoc()): 
                        $type = 'video';
                        if($row['exam_url'] && !$row['video_url']) $type = 'exam';
                        if($row['notes_url'] && !$row['video_url']) $type = 'notes';
                    ?>
                    <tr class="mat-row group hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors"
                        data-content="<?php echo strtolower(htmlspecialchars($row['unit_name'])); ?>"
                        data-subject="<?php echo strtolower(htmlspecialchars($row['subject_title'])); ?>"
                        data-category="<?php echo strtolower(htmlspecialchars($row['category'])); ?>"
                        data-type="<?php echo $type; ?>">
                        <td class="px-4 sm:px-8 py-4 sm:py-5">
                            <div class="flex items-center gap-3 sm:gap-4">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center shrink-0 <?php 
                                    echo $type === 'video' ? 'bg-indigo-50 text-indigo-500' : 
                                        ($type === 'notes' ? 'bg-emerald-50 text-emerald-500' : 'bg-orange-50 text-orange-500'); 
                                ?> dark:bg-slate-800">
                                    <span class="material-symbols-outlined text-lg sm:text-xl">
                                        <?php echo $type === 'video' ? 'video_library' : ($type === 'notes' ? 'description' : 'assignment'); ?>
                                    </span>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-sm text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($row['unit_name'] ?: 'Untitled Segment'); ?></div>
                                    <div class="flex gap-2 mt-0.5 sm:mt-1">
                                        <?php if($row['video_url']): ?><span class="text-[8px] sm:text-[9px] font-black uppercase px-1.5 py-0.5 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 rounded">Video</span><?php endif; ?>
                                        <?php if($row['notes_url']): ?><span class="text-[8px] sm:text-[9px] font-black uppercase px-1.5 py-0.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 rounded">Notes</span><?php endif; ?>
                                        <?php if($row['exam_url']): ?><span class="text-[8px] sm:text-[9px] font-black uppercase px-1.5 py-0.5 bg-orange-50 dark:bg-orange-900/20 text-orange-600 rounded">Exam</span><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-5">
                            <div class="text-sm font-bold text-slate-700 dark:text-slate-300"><?php echo htmlspecialchars($row['subject_title']); ?></div>
                            <div class="text-[10px] font-black uppercase text-slate-400 mt-1"><?php echo htmlspecialchars($row['category']); ?></div>
                        </td>
                        <td class="px-8 py-5">
                            <div class="text-xs font-bold text-slate-600 dark:text-slate-400"><?php echo date('d M Y', strtotime($row['created_at'])); ?></div>
                            <div class="text-[10px] text-slate-400 mt-0.5"><?php echo date('h:i A', strtotime($row['created_at'])); ?></div>
                        </td>
                        <td class="px-8 py-5 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="subject_details.php?id=<?php echo $row['subject_id']; ?>&material_id=<?php echo $row['id']; ?>" class="w-9 h-9 flex items-center justify-center bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-primary hover:text-white rounded-lg transition-all" title="Review">
                                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                                </a>
                                <form method="POST" onsubmit="return confirm('Delete this material permanently?');" class="inline">
                                    <input type="hidden" name="delete_material_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="w-9 h-9 flex items-center justify-center bg-red-50 dark:bg-red-900/20 text-red-500 hover:bg-red-500 hover:text-white rounded-lg transition-all" title="Remove">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Empty Search Results -->
            <div id="adminNoMat" class="hidden text-center py-24">
                <div class="w-20 h-20 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-4xl text-slate-300">search_off</span>
                </div>
                <h3 class="text-xl font-black text-slate-900 dark:text-white">No matches found</h3>
                <p class="text-slate-500 dark:text-slate-400 max-w-xs mx-auto text-sm font-medium">We couldn't find any contributions matching your current search criteria.</p>
            </div>

            <?php if($materials->num_rows == 0): ?>
            <div class="text-center py-24">
                <div class="w-20 h-20 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-4xl text-slate-300">cloud_off</span>
                </div>
                <h3 class="text-xl font-black text-slate-900 dark:text-white">No uploads yet</h3>
                <p class="text-slate-500 dark:text-slate-400 max-w-xs mx-auto text-sm font-medium">This user hasn't contributed any learning materials to the hub yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    const adminMatSearch = document.getElementById('adminMatSearch');
    const adminTypeFilter = document.getElementById('adminTypeFilter');
    const matRows = document.querySelectorAll('.mat-row');
    const adminNoMat = document.getElementById('adminNoMat');

    function filterAdminMaterials() {
        const searchTerm = adminMatSearch.value.toLowerCase();
        const type = adminTypeFilter.value;
        let visibleCount = 0;

        matRows.forEach(row => {
            const content = row.getAttribute('data-content');
            const subject = row.getAttribute('data-subject');
            const category = row.getAttribute('data-category');
            const rowType = row.getAttribute('data-type');
            
            const matchesSearch = content.includes(searchTerm) || subject.includes(searchTerm) || category.includes(searchTerm);
            const matchesType = type === 'all' || rowType === type;

            if (matchesSearch && matchesType) {
                row.classList.remove('hidden');
                visibleCount++;
            } else {
                row.classList.add('hidden');
            }
        });

        if (visibleCount === 0 && matRows.length > 0) {
            adminNoMat.classList.remove('hidden');
        } else {
            adminNoMat.classList.add('hidden');
        }
    }

    adminMatSearch.addEventListener('input', filterAdminMaterials);
    adminTypeFilter.addEventListener('change', filterAdminMaterials);
</script>

<?php include '../includes/footer.php'; ?>
