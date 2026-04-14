<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
// Handle deletion
$delete_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['material_id'])) {
    $del_id = (int)$_POST['material_id'];
    
    // First figure out the subject_id of this material
    $subStmt = $conn->prepare("SELECT subject_id FROM materials WHERE id = ? AND uploader_id = ?");
    $subStmt->bind_param("ii", $del_id, $user_id);
    $subStmt->execute();
    $subRes = $subStmt->get_result();
    
    if ($subRes->num_rows > 0) {
        $subject_id = $subRes->fetch_assoc()['subject_id'];
        
        // Delete the material
        $delStmt = $conn->prepare("DELETE FROM materials WHERE id = ?");
        $delStmt->bind_param("i", $del_id);
        if ($delStmt->execute()) {
            
            // Cleanup empty subjects -> If no materials left, delete the subject (and effectively the Category instance)
            $checkStmt = $conn->prepare("SELECT count(*) as total FROM materials WHERE subject_id = ?");
            $checkStmt->bind_param("i", $subject_id);
            $checkStmt->execute();
            if ($checkStmt->get_result()->fetch_assoc()['total'] == 0) {
                $delSubStmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
                $delSubStmt->bind_param("i", $subject_id);
                $delSubStmt->execute();
            }
            
            $delete_msg = "Content and any empty related courses were successfully deleted.";
        } else {
            $delete_msg = "Error deleting content.";
        }
    } else {
        $delete_msg = "Error deleting content or permission denied.";
    }
}

// Fetch contributions for the user
$sql = "SELECT m.id, m.subject_id, m.video_url, m.notes_url, m.exam_url, m.description, m.unit_name, m.created_at, s.title AS subject_title, s.category 
        FROM materials m 
        JOIN subjects s ON m.subject_id = s.id 
        WHERE m.uploader_id = ? 
        ORDER BY m.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$contributions = $stmt->get_result();

$videos_count = 0;
$notes_count = 0;
$exams_count = 0;

$contributions_data = [];
while ($row = $contributions->fetch_assoc()) {
    $contributions_data[] = $row;
    if (!empty($row['video_url'])) $videos_count++;
    if (!empty($row['notes_url'])) $notes_count++;
    if (!empty($row['exam_url'])) $exams_count++;
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>My Uploads | BCA Learning Hub</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
                    }
                }
            }
        }
    </script>
    <style>
                body { font-family: 'Inter', sans-serif; }
        h1, h2, h3, h4 { font-family: 'Poppins', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        
        /* Premium Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #E2E8F0;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #CBD5E1;
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #1E293B;
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #334155;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen antialiased">

<?php include '../includes/header.php'; ?>

<main class="pt-6 sm:pt-10 px-3.5 sm:px-6 lg:px-8 pb-20 min-h-screen max-w-7xl mx-auto">
    <!-- Header Section -->
    <header class="mb-6 sm:mb-10 text-center sm:text-left mt-4 sm:mt-8">
        <h1 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">My Uploads</h1>
        <p class="text-xs sm:text-base text-slate-500 dark:text-slate-400 mt-1.5 font-medium">Management of your shared academic materials.</p>
    </header>

    <!-- My Contributions / Uploads Section -->
    <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-8 shadow-sm mb-8">
        <?php if ($delete_msg): ?>
            <div class="mb-6 p-4 bg-slate-50 border border-slate-200 text-slate-700 rounded-xl font-medium text-sm">
                <?php echo htmlspecialchars($delete_msg); ?>
            </div>
        <?php endif; ?>
        
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <div>
                <h3 class="text-lg sm:text-xl font-bold text-slate-900">Contribution History</h3>
            </div>
            <!-- Quick Stats in header for desktop -->
            <div class="hidden md:flex gap-4">
                <span class="text-sm text-slate-500">Total: <b class="text-indigo-600"><?php echo count($contributions_data); ?></b></span>
            </div>
        </div>

        <!-- Advance Search Bar -->
        <div class="mb-6 flex flex-col md:flex-row gap-3">
            <div class="relative flex-grow">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
                <input type="text" id="uploadSearch" placeholder="Search by title, category or description..." 
                       class="w-full pl-10 pr-4 py-2 bg-slate-50 border-none rounded-lg text-xs focus:ring-1 focus:ring-primary transition-all">
            </div>
            <select id="typeFilter" class="bg-slate-50 border-none rounded-lg text-xs py-2 px-3 focus:ring-1 focus:ring-primary cursor-pointer w-full md:w-32">
                <option value="all">All Types</option>
                <option value="video">Videos</option>
                <option value="notes">Notes</option>
                <option value="exam">Exams</option>
            </select>
        </div>

        <div class="max-h-[750px] overflow-y-auto pr-2 custom-scrollbar" id="uploadList">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="uploadGrid">
                <?php if(count($contributions_data) === 0): ?>
                    <div class="col-span-full py-10 text-center bg-slate-50 border-2 border-dashed border-slate-200 rounded-xl">
                        <span class="material-symbols-outlined text-4xl text-slate-300 mb-2">inbox</span>
                        <p class="text-slate-500 font-medium">You haven't uploaded anything yet.</p>
                        <a href="upload.php" class="mt-4 inline-block text-primary font-bold hover:underline">Start sharing now!</a>
                    </div>
                <?php else: ?>
                    <?php foreach($contributions_data as $item): 
                        // Determine primary type for icon
                        $type = 'video';
                        $icon = 'video_library';
                        $color_bg = 'bg-blue-50';
                        $color_text = 'text-blue-500';
                        
                        if(!empty($item['exam_url']) && empty($item['video_url'])) {
                            $type = 'exam';
                            $icon = 'assignment';
                            $color_bg = 'bg-orange-50';
                            $color_text = 'text-orange-500';
                        } elseif(!empty($item['notes_url']) && empty($item['video_url'])) {
                            $type = 'notes';
                            $icon = 'description';
                            $color_bg = 'bg-emerald-50';
                            $color_text = 'text-emerald-500';
                        }
                        
                        $time_diff = time() - strtotime($item['created_at']);
                        $days = round($time_diff / (60 * 60 * 24));
                        $time_str = $days <= 0 ? "Today" : ($days == 1 ? "1 day ago" : "$days days ago");
                    ?>
                    <!-- Contribution Item -->
                    <div class="upload-card group border border-slate-100 dark:border-slate-800 rounded-2xl p-4 sm:p-6 hover:shadow-xl hover:shadow-primary/10 transition-all duration-300 bg-white dark:bg-slate-900"
                         data-title="<?php echo strtolower(htmlspecialchars($item['subject_title'])); ?>"
                         data-category="<?php echo strtolower(htmlspecialchars($item['category'])); ?>"
                         data-type="<?php echo $type; ?>"
                         data-desc="<?php echo strtolower(htmlspecialchars($item['description'])); ?>">
                        <div class="flex items-start justify-between mb-4 sm:mb-6">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl <?php echo $color_bg; ?> dark:bg-slate-800 flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-all duration-300">
                                <span class="material-symbols-outlined <?php echo $color_text; ?> group-hover:text-white transition-colors text-2xl sm:text-3xl"><?php echo $icon; ?></span>
                            </div>
                            <div class="flex gap-2 opacity-100 group-hover:opacity-100 sm:opacity-0 transition-opacity">
                                <a href="../pages/subject_details.php?id=<?php echo htmlspecialchars($item['subject_id']); ?>&material_id=<?php echo htmlspecialchars($item['id']); ?>" class="p-2 bg-slate-50 dark:bg-slate-800 rounded-xl text-slate-500 hover:text-primary transition-colors" title="View">
                                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                                </a>
                                <a href="../pages/edit_material.php?id=<?php echo htmlspecialchars($item['id']); ?>" class="p-2 bg-slate-50 dark:bg-slate-800 rounded-xl text-slate-500 hover:text-indigo-600 transition-colors" title="Edit">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </a>
                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this content?');" class="inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="material_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="p-2 bg-red-50 dark:bg-red-900/10 rounded-xl text-slate-500 hover:text-red-500 transition-colors" title="Delete">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <h4 class="font-bold text-slate-900 dark:text-white mb-2 line-clamp-1 group-hover:text-primary transition-colors"><?php echo htmlspecialchars($item['subject_title']); ?></h4>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mb-4 sm:mb-6 line-clamp-2 leading-relaxed font-medium"><?php echo htmlspecialchars($item['description']); ?></p>
                        
                        <!-- Chips -->
                        <div class="flex flex-wrap gap-2 mb-6">
                            <?php if(!empty($item['video_url'])): ?><span class="px-3 py-1 text-[10px] uppercase font-black tracking-widest bg-blue-50 text-blue-600 border border-blue-100 rounded-full">Video</span><?php endif; ?>
                            <?php if(!empty($item['notes_url'])): ?><span class="px-3 py-1 text-[10px] uppercase font-black tracking-widest bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-full">Notes</span><?php endif; ?>
                            <?php if(!empty($item['exam_url'])): ?><span class="px-3 py-1 text-[10px] uppercase font-black tracking-widest bg-orange-50 text-orange-600 border border-orange-100 rounded-full">Exam</span><?php endif; ?>
                        </div>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-slate-50 dark:border-slate-800">
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400"><?php echo $time_str; ?></span>
                            <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-100 dark:border-slate-700 text-slate-500 truncate max-w-[120px]"><?php echo htmlspecialchars($item['category']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <!-- Enhanced Empty Results -->
            <div id="noUploads" class="hidden text-center py-24 bg-white dark:bg-slate-900/50 rounded-3xl border-2 border-dashed border-slate-100 dark:border-slate-800">
                <span class="material-symbols-outlined text-5xl text-slate-300 mb-4 block">search_off</span>
                <h3 class="text-xl font-black text-slate-900 dark:text-white">No matching uploads</h3>
                <p class="text-slate-500 dark:text-slate-400 mt-1 max-w-xs mx-auto font-medium">We couldn't find any of your uploads that match your search query.</p>
            </div>
        </div>
    </div>
    
    <!-- Contribution Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm flex items-center justify-between group hover:border-primary/30 transition-all">
            <div>
                <p class="text-[10px] uppercase font-black text-slate-400 tracking-widest mb-1">Video Lessons</p>
                <p class="text-2xl font-black text-slate-900 dark:text-white"><?php echo $videos_count; ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/20 rounded-xl flex items-center justify-center text-blue-500 group-hover:bg-primary group-hover:text-white transition-all">
                <span class="material-symbols-outlined">video_library</span>
            </div>
        </div>
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm flex items-center justify-between group hover:border-primary/30 transition-all">
            <div>
                <p class="text-[10px] uppercase font-black text-slate-400 tracking-widest mb-1">Study Notes</p>
                <p class="text-2xl font-black text-slate-900 dark:text-white"><?php echo $notes_count; ?></p>
            </div>
            <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl flex items-center justify-center text-emerald-500 group-hover:bg-primary group-hover:text-white transition-all">
                <span class="material-symbols-outlined">menu_book</span>
            </div>
        </div>
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm flex items-center justify-between group hover:border-primary/30 transition-all">
            <div>
                <p class="text-[10px] uppercase font-black text-slate-400 tracking-widest mb-1">Exam Papers</p>
                <p class="text-2xl font-black text-slate-900 dark:text-white"><?php echo $exams_count; ?></p>
            </div>
            <div class="w-12 h-12 bg-orange-50 dark:bg-orange-900/20 rounded-xl flex items-center justify-center text-orange-500 group-hover:bg-primary group-hover:text-white transition-all">
                <span class="material-symbols-outlined">assignment</span>
            </div>
        </div>
    </div>
</main>

<script>
    const uploadSearch = document.getElementById('uploadSearch');
    const typeFilter = document.getElementById('typeFilter');
    const uploadCards = document.querySelectorAll('.upload-card');
    const uploadGrid = document.getElementById('uploadGrid');
    const noUploads = document.getElementById('noUploads');

    function filterUploads() {
        const searchTerm = uploadSearch.value.toLowerCase();
        const type = typeFilter.value;
        let visibleCount = 0;

        uploadCards.forEach(card => {
            const title = card.getAttribute('data-title');
            const cat = card.getAttribute('data-category');
            const desc = card.getAttribute('data-desc');
            const cardType = card.getAttribute('data-type');
            
            const matchesSearch = title.includes(searchTerm) || cat.includes(searchTerm) || desc.includes(searchTerm);
            const matchesType = type === 'all' || cardType === type;

            if (matchesSearch && matchesType) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        if (visibleCount === 0) {
            uploadGrid.classList.add('hidden');
            noUploads.classList.remove('hidden');
        } else {
            uploadGrid.classList.remove('hidden');
            noUploads.classList.add('hidden');
        }
    }

    uploadSearch.addEventListener('input', filterUploads);
    typeFilter.addEventListener('change', filterUploads);
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
