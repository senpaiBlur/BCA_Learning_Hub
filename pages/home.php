<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Fetch dynamic stats
$stats_query = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM materials) AS total_materials,
        (SELECT COUNT(*) FROM materials WHERE video_url IS NOT NULL AND video_url != '') AS video_lectures,
        (SELECT COUNT(*) FROM materials WHERE (notes_url IS NOT NULL AND notes_url != '') OR (exam_url IS NOT NULL AND exam_url != '')) AS pdf_notes,
        (SELECT COUNT(*) FROM users) AS happy_students
");
$stats = $stats_query->fetch_assoc();

$video_lectures_count = $stats['video_lectures'] ?: 0;
$total_materials_count = $stats['total_materials'] ?: 0;
$pdf_notes_count = $stats['pdf_notes'] ?: 0;
// Add a base multiplier to students if the database is small to look impressive (e.g. 50 + actual) or just use actual data as requested.
$happy_students_count = $stats['happy_students'] ?: 0;

include '../includes/header.php';
?>
<main>
<!-- Hero Section -->
<section class="relative pt-10 pb-16 sm:pt-20 sm:pb-32 lg:pt-32 lg:pb-40 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid lg:grid-cols-2 gap-10 sm:gap-16 items-center">
            <div class="text-center lg:text-left space-y-6 sm:space-y-10">
                <div class="space-y-4 sm:space-y-6">
                    <span class="inline-flex items-center px-4 py-1.5 bg-primary/10 text-primary text-[10px] sm:text-xs font-bold uppercase tracking-widest rounded-full border border-primary/20">
                        <span class="w-2 h-2 bg-primary rounded-full mr-2 animate-pulse"></span>
                        E-Learning Platform
                    </span>
                    <h1 class="text-4xl sm:text-5xl lg:text-[5.5rem] font-extrabold text-slate-900 dark:text-white leading-[1.1] sm:leading-[1.05] tracking-tight">
                        Master Your <span class="text-primary relative inline-block">BCA<span class="absolute bottom-1 sm:bottom-2 left-0 w-full h-2 sm:h-3 bg-primary/10 -z-10"></span></span> Subjects
                    </h1>
                    <p class="text-base sm:text-xl text-slate-600 dark:text-slate-400 max-w-xl leading-relaxed font-medium mx-auto lg:mx-0">
                        The ultimate destination for BCA students. Get free access to curated video lectures, professional notes, and exam papers.
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row gap-4 sm:gap-5 justify-center lg:justify-start">
                    <a href="#categories" class="px-8 py-4 sm:px-10 sm:py-5 bg-primary text-white text-base sm:text-lg font-bold rounded-xl shadow-2xl shadow-primary/40 hover:-translate-y-1 hover:shadow-primary/50 transition-all active:scale-95 flex items-center justify-center gap-2">
                        Start Learning 
                        <span class="material-symbols-outlined text-xl">arrow_downward</span>
                    </a>
                </div>
            </div>
            <div class="relative lg:ml-10 mt-12 lg:mt-0">
                <div class="relative rounded-2xl lg:rounded-[2rem] overflow-hidden shadow-[0_32px_64px_-16px_rgba(79,70,229,0.2)] border-4 sm:border-8 border-white/50 dark:border-slate-800/50">
                    <img alt="Modern Learning Experience" class="w-full h-auto aspect-[4/5] object-cover scale-105 hover:scale-100 transition-transform duration-1000" src="../assets/images/campus.jpg"/>
                    <div class="absolute inset-0 bg-gradient-to-tr from-primary/30 via-transparent to-transparent"></div>
                </div>
                
                <!-- Floating info cards -->
                <div class="absolute -bottom-6 -left-4 sm:-bottom-8 sm:-left-8 bg-white dark:bg-slate-800 p-4 sm:p-5 rounded-2xl shadow-2xl flex items-center gap-3 sm:gap-4 animate-bounce-slow border border-slate-100 dark:border-slate-700">
                    <div class="bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 p-2 sm:p-3 rounded-xl">
                        <span class="material-symbols-outlined text-xl sm:text-2xl">verified_user</span>
                    </div>
                    <div>
                        <p class="text-[9px] sm:text-[10px] text-slate-400 font-bold uppercase tracking-wider">Latest Update</p>
                        <p class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Exam Papers Added</p>
                    </div>
                </div>

                <div class="absolute -top-6 -right-4 sm:-top-6 sm:-right-6 bg-white dark:bg-slate-800 p-4 sm:p-5 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-700 hidden sm:flex items-center gap-3">
                    <div class="flex -space-x-2">
                        <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center text-primary"><span class="material-symbols-outlined text-sm">play_arrow</span></div>
                    </div>
                    <p class="text-sm font-bold text-slate-900 dark:text-white"><?php echo $video_lectures_count; ?>+ Video Lectures</p>
                </div>
            </div>
        </div>
    </div>
    <!-- Background Decoration -->
    <div class="absolute top-0 right-0 -z-10 opacity-10 pointer-events-none">
        <svg fill="none" height="800" viewbox="0 0 600 600" width="800" xmlns="http://www.w3.org/2000/svg">
            <circle cx="400" cy="200" fill="url(#paint0_linear)" r="300"></circle>
            <defs>
                <lineargradient gradientunits="userSpaceOnUse" id="paint0_linear" x1="200" x2="600" y1="0" y2="400">
                    <stop stop-color="#4F46E5"></stop>
                    <stop offset="1" stop-color="#4F46E5" stop-opacity="0"></stop>
                </lineargradient>
            </defs>
        </svg>
    </div>
</section>

<!-- Categories -->
<section id="categories" class="py-16 sm:py-32 bg-slate-50 dark:bg-slate-900/50">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
<div class="flex flex-col md:flex-row md:items-end justify-between mb-10 sm:mb-16 gap-6">
<div class="max-w-2xl">
<h2 class="text-2xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight text-center md:text-left">Browse by Category</h2>
<p class="mt-2 sm:mt-4 text-sm sm:text-lg text-slate-500 font-medium text-center md:text-left">Navigate through various subjects categorized by their core domains.</p>
</div>
<div class="shrink-0">
    <a href="subjects.php" class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold rounded-xl border border-slate-200 dark:border-slate-700 hover:border-primary dark:hover:border-primary hover:text-primary dark:hover:text-primary transition-all shadow-sm hover:shadow-md active:scale-95">
        View All Subjects
        <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
    </a>
</div>
</div>
<?php
// Reverted to Semester system as per user request
$home_semesters = [
    'BCA Semester 1' => ['icon' => 'looks_one', 'desc' => 'Foundational Subjects'],
    'BCA Semester 2' => ['icon' => 'looks_two', 'desc' => 'Programming Basics'],
    'BCA Semester 3' => ['icon' => 'looks_3', 'desc' => 'Data Structures & DBMS'],
    'BCA Semester 4' => ['icon' => 'looks_4', 'desc' => 'Networking & OS'],
    'BCA Semester 5' => ['icon' => 'looks_5', 'desc' => 'Advanced Algorithms & Web'],
];
?>
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
    <?php foreach($home_semesters as $sem_name => $sem_data): ?>
    <a class="group bg-white dark:bg-slate-800 p-8 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all text-center flex flex-col items-center justify-center" href="subjects.php?category=<?php echo urlencode($sem_name); ?>">
        <div class="w-16 h-16 bg-primary/10 text-primary rounded-2xl flex items-center justify-center mb-6 group-hover:bg-primary group-hover:text-white group-hover:rotate-3 transition-all ring-4 ring-transparent group-hover:ring-primary/20">
            <span class="material-symbols-outlined text-3xl font-bold"><?php echo $sem_data['icon']; ?></span>
        </div>
        <h3 class="font-bold text-base text-slate-900 dark:text-white"><?php echo htmlspecialchars($sem_name); ?></h3>
        <p class="text-[10px] text-slate-400 mt-2 font-black uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-opacity">View Subjects</p>
    </a>
    <?php endforeach; ?>
</div>
</div>
</section>

<!-- Stats Section -->
<section class="py-16 sm:py-24 bg-primary text-white relative overflow-hidden">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 sm:gap-12 text-center">
                            <div class="space-y-1 sm:space-y-2">
                                <p class="text-3xl sm:text-5xl font-extrabold tracking-tight"><?php echo $video_lectures_count; ?>+</p>
                                <p class="text-primary-100 text-[10px] sm:text-sm font-bold uppercase tracking-widest opacity-80">Video Lectures</p>
                            </div>
                            <div class="space-y-1 sm:space-y-2">
                                <p class="text-3xl sm:text-5xl font-extrabold tracking-tight"><?php echo $total_materials_count; ?>+</p>
                                <p class="text-primary-100 text-[10px] sm:text-sm font-bold uppercase tracking-widest opacity-80">Study Materials</p>
                            </div>
                            <div class="space-y-1 sm:space-y-2">
                                <p class="text-3xl sm:text-5xl font-extrabold tracking-tight"><?php echo $pdf_notes_count; ?>+</p>
                                <p class="text-primary-100 text-[10px] sm:text-sm font-bold uppercase tracking-widest opacity-80">PDF Notes</p>
                            </div>
                            <div class="space-y-1 sm:space-y-2">
                                <p class="text-3xl sm:text-5xl font-extrabold tracking-tight"><?php echo $happy_students_count; ?>+</p>
                                <p class="text-primary-100 text-[10px] sm:text-sm font-bold uppercase tracking-widest opacity-80">Happy Students</p>
                            </div>
                        </div>
</div>
<div class="absolute top-0 left-0 w-full h-full opacity-10">
<svg class="w-full h-full" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M0 100Q250 50 500 100T1000 100" stroke="white" stroke-width="2"></path>
<path d="M0 150Q250 100 500 150T1000 150" stroke="white" stroke-width="2"></path>
</svg>
</div>
</section>
</main>
<?php include '../includes/footer.php'; ?>
