<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get total unique subjects user has progress in
$prog_sql = "
    SELECT 
        s.id as subject_id,
        s.title,
        s.category,
        s.thumbnail,
        (SELECT COUNT(*) FROM materials WHERE subject_id = s.id) as total_materials,
        (SELECT COUNT(*) FROM user_progress up 
         JOIN materials m2 ON up.material_id = m2.id 
         WHERE m2.subject_id = s.id AND up.user_id = ?) as completed_materials
    FROM subjects s
    WHERE EXISTS (
        SELECT 1 FROM user_progress up3 
        JOIN materials m3 ON up3.material_id = m3.id 
        WHERE m3.subject_id = s.id AND up3.user_id = ?
    )
";
$stmt = $conn->prepare($prog_sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$progress_res = $stmt->get_result();

$my_courses = [];
while ($row = $progress_res->fetch_assoc()) {
    $my_courses[] = $row;
}
$completed_courses = 0;
$in_progress_courses = 0;
foreach($my_courses as $c) {
    if($c['total_materials'] > 0 && $c['completed_materials'] == $c['total_materials']) {
        $completed_courses++;
    } else {
        $in_progress_courses++;
    }
}
$total_courses = count($my_courses);
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>My Learning | BCA Learning Hub</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet"/>
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
<body class="bg-slate-50 text-slate-900 border-on-surface antialiased min-h-screen">

<?php include '../includes/header.php'; ?>

<main class="pt-10 pb-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <!-- Welcome Header -->
    <header class="mb-6 sm:mb-10 text-center sm:text-left mt-4 sm:mt-8">
        <p class="text-[9px] font-black uppercase tracking-widest text-primary mb-1">Academic Portal</p>
        <h1 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">My Learning</h1>
</header>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Left Column: Primary Content -->
        <div class="lg:col-span-8 space-y-10">
            
            <!-- Section 1: Recently Viewed -->
            <?php
            // Get last 3 subjects the user interacted with
            $recent_sql = "
                SELECT DISTINCT s.id, s.title, s.category, s.thumbnail
                FROM subjects s
                JOIN materials m ON s.id = m.subject_id
                JOIN user_progress up ON m.id = up.material_id
                WHERE up.user_id = ?
                ORDER BY up.id DESC
                LIMIT 3
            ";
            $rStmt = $conn->prepare($recent_sql);
            $rStmt->bind_param("i", $user_id);
            $rStmt->execute();
            $recent_res = $rStmt->get_result();
            ?>
            <?php if ($recent_res->num_rows > 0): ?>
            <section>
                <div class="flex items-center gap-3 mb-4 sm:mb-6">
                    <div class="h-6 w-1 bg-primary rounded-full"></div>
                    <h2 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">Recently Viewed</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
                    <?php while($rs = $recent_res->fetch_assoc()): ?>
                    <a href="subject_details.php?id=<?php echo $rs['id']; ?>" class="group relative overflow-hidden rounded-2xl aspect-[4/3] shadow-lg hover:shadow-2xl transition-all duration-500">
                        <img src="<?php echo htmlspecialchars($rs['thumbnail'] ? $rs['thumbnail'] : 'https://placehold.co/600x400/5048e5/ffffff?text='.urlencode($rs['title'])); ?>" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" />
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent"></div>
                        <div class="absolute bottom-4 left-4 right-4">
                            <span class="text-[10px] font-black text-primary bg-white/90 px-2 py-0.5 rounded uppercase tracking-widest mb-2 inline-block"><?php echo htmlspecialchars($rs['category']); ?></span>
                            <h3 class="text-white font-bold text-sm leading-tight line-clamp-2"><?php echo htmlspecialchars($rs['title']); ?></h3>
                        </div>
                        <div class="absolute inset-x-0 bottom-0 h-1 bg-white/20">
                            <div class="h-full bg-primary transition-all duration-300" style="width: 0%"></div> <!-- Could track actual % here -->
                        </div>
                    </a>
                    <?php endwhile; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Section 2: Progress Overview -->
            <section class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                <!-- Stats cards... -->
                <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-xl p-4 sm:p-6 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <span class="material-symbols-outlined text-primary bg-primary/10 p-1.5 sm:p-2 rounded-xl text-lg sm:text-xl">collections_bookmark</span>
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Active</span>
                    </div>
                    <p class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider">Total Subjects</p>
                    <p class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white mt-0.5 sm:mt-1"><?php echo $total_courses; ?></p>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-xl p-4 sm:p-6 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <span class="material-symbols-outlined text-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 p-1.5 sm:p-2 rounded-xl text-lg sm:text-xl">check_circle</span>
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Done</span>
                    </div>
                    <p class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider">Completed</p>
                    <p class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white mt-0.5 sm:mt-1"><?php echo $completed_courses; ?></p>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-xl p-4 sm:p-6 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <span class="material-symbols-outlined text-blue-500 bg-blue-50 dark:bg-blue-900/20 p-1.5 sm:p-2 rounded-xl text-lg sm:text-xl">pending</span>
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Ongoing</span>
                    </div>
                    <p class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider">In Progress</p>
                    <p class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white mt-0.5 sm:mt-1"><?php echo $in_progress_courses; ?></p>
                </div>
            </section>

            <!-- Section 3: My Courses Grid -->
            <section>
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-1.5 bg-primary rounded-full"></div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Full Catalog</h2>
                    </div>
                    <div class="relative w-full sm:w-72">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                        <input type="text" id="learningSearch" placeholder="Filter your subjects..." 
                               class="w-full pl-12 pr-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 transition-all shadow-sm">
                    </div>
                </div>

                <?php if ($total_courses === 0): ?>
                    <div class="text-center bg-white dark:bg-slate-900 p-16 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm">
                        <div class="w-20 h-20 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6">
                            <span class="material-symbols-outlined text-4xl text-slate-400">school</span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No active subjects</h3>
                        <p class="text-slate-500 mb-8 max-w-sm mx-auto">Start your learning journey by exploring our comprehensive course catalog.</p>
                        <a href="subjects.php" class="inline-flex items-center gap-2 bg-primary text-white px-8 py-3 rounded-xl font-bold hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 active:scale-95">
                            Browse Catalog
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="max-h-[620px] overflow-y-auto pr-2 custom-scrollbar" id="learningList">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8" id="learningGrid">
                            <?php foreach($my_courses as $c): 
                                $percent = $c['total_materials'] > 0 ? floor(($c['completed_materials'] / $c['total_materials']) * 100) : 0;
                            ?>
                            <!-- Course Card -->
                            <div class="learning-card bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl overflow-hidden group shadow-sm hover:shadow-xl hover:shadow-primary/10 transition-all duration-300"
                                 data-title="<?php echo strtolower(htmlspecialchars($c['title'])); ?>"
                                 data-category="<?php echo strtolower(htmlspecialchars($c['category'])); ?>">
                                <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <h4 class="font-bold text-slate-900 dark:text-white line-clamp-1 group-hover:text-primary transition-colors"><?php echo htmlspecialchars($c['title']); ?></h4>
                                            <span class="inline-block mt-2 px-2.5 py-1 rounded-lg bg-slate-50 dark:bg-slate-800 text-[10px] font-black text-slate-400 uppercase tracking-widest border border-slate-100 dark:border-slate-700 truncate max-w-full"><?php echo htmlspecialchars($c['category']); ?></span>
                                        </div>
                                        <div class="shrink-0 w-12 h-12 bg-primary/5 rounded-xl flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-all duration-300">
                                            <span class="material-symbols-outlined"><?php echo $percent == 100 ? 'verified' : 'auto_stories'; ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-end">
                                            <p class="text-[10px] font-black uppercase tracking-widest transition-all <?php echo $percent == 100 ? 'text-emerald-500' : 'text-slate-400'; ?>">
                                                <?php echo $percent == 100 ? 'Complete' : $percent . '% Through'; ?>
                                            </p>
                                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest"><?php echo $c['completed_materials']; ?> / <?php echo $c['total_materials']; ?> Lessons</p>
                                        </div>
                                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden shadow-inner">
                                            <div class="h-full rounded-full transition-all duration-1000 ease-out <?php echo $percent == 100 ? 'bg-emerald-500' : 'bg-primary'; ?>" style="width: <?php echo $percent; ?>%"></div>
                                        </div>
                                    </div>

                                    <div class="pt-2">
                                        <a href="subject_details.php?id=<?php echo htmlspecialchars($c['subject_id']); ?>" class="w-full inline-flex items-center justify-center gap-2 py-3 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-sm font-bold rounded-xl hover:bg-primary hover:text-white transition-all active:scale-[0.98] shadow-sm hover:shadow-primary/20 group/btn">
                                            <?php echo $percent == 100 ? 'Review Course' : 'Continue Learning'; ?>
                                            <span class="material-symbols-outlined text-[18px] transition-transform group-hover/btn:translate-x-1"><?php echo $percent == 100 ? 'history' : 'play_circle'; ?></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Enhanced Empty Search -->
                        <div id="noLearning" class="hidden text-center py-24 bg-slate-50 dark:bg-slate-800/30 rounded-3xl border-2 border-dashed border-slate-100 dark:border-slate-800">
                            <span class="material-symbols-outlined text-5xl text-slate-300 mb-4 block">search_off</span>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">No courses match</h3>
                            <p class="text-slate-500 dark:text-slate-400 mt-1 max-w-xs mx-auto font-medium">We couldn't find any courses in your library that match your search.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <!-- Right Column: Secondary Sections -->
        <div class="lg:col-span-4 space-y-8">
            <!-- Recently Viewed Idea (Static Placeholder in context) -->
            <section class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                <h2 class="text-sm font-bold text-slate-900 mb-5">Quick Actions</h2>
                <div class="space-y-4">
                    <a href="upload.php" class="flex items-center gap-3 group cursor-pointer p-3 hover:bg-slate-50 rounded-lg transition-colors border border-transparent hover:border-slate-100">
                        <div class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-sm">cloud_upload</span>
                        </div>
                        <div class="flex-grow">
                            <p class="text-xs font-bold text-slate-900">Upload Content</p>
                            <p class="text-[10px] text-slate-500">Share your videos or notes</p>
                        </div>
                        <span class="material-symbols-outlined text-slate-300 text-sm">chevron_right</span>
                    </a>
                    
                    <a href="my_uploads.php" class="flex items-center gap-3 group cursor-pointer p-3 hover:bg-slate-50 rounded-lg transition-colors border border-transparent hover:border-slate-100">
                        <div class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-sm">folder_shared</span>
                        </div>
                        <div class="flex-grow">
                            <p class="text-xs font-bold text-slate-900">Manage Uploads</p>
                            <p class="text-[10px] text-slate-500">View what you've shared</p>
                        </div>
                        <span class="material-symbols-outlined text-slate-300 text-sm">chevron_right</span>
                    </a>
                </div>
            </section>
        </div>
    </div>
</main>

<script>
    const learningSearch = document.getElementById('learningSearch');
    const learningCards = document.querySelectorAll('.learning-card');
    const learningGrid = document.getElementById('learningGrid');
    const noLearning = document.getElementById('noLearning');

    function filterLearning() {
        const searchTerm = learningSearch.value.toLowerCase();
        let visibleCount = 0;

        learningCards.forEach(card => {
            const title = card.getAttribute('data-title');
            const cat = card.getAttribute('data-category');
            
            if (title.includes(searchTerm) || cat.includes(searchTerm)) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        if (visibleCount === 0) {
            learningGrid.classList.add('hidden');
            noLearning.classList.remove('hidden');
        } else {
            learningGrid.classList.remove('hidden');
            noLearning.classList.add('hidden');
        }
    }

    learningSearch.addEventListener('input', filterLearning);
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
