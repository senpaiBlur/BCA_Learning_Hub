<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$subject_id = $_GET['id'] ?? 0;
if (!is_numeric($subject_id)) {
    header("Location: subjects.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$subject_result = $stmt->get_result();

if ($subject_result->num_rows == 0) {
    header("Location: subjects.php");
    exit();
}
$subject = $subject_result->fetch_assoc();

// Fetch materials
$matStmt = $conn->prepare("SELECT m.*, u.name as uploader_name FROM materials m LEFT JOIN users u ON m.uploader_id = u.id WHERE m.subject_id = ? ORDER BY m.id ASC");
$matStmt->bind_param("i", $subject_id);
$matStmt->execute();
$materials_result = $matStmt->get_result();

$materials = [];
while ($row = $materials_result->fetch_assoc()) {
    $materials[] = $row;
}

$active_material_id = $_GET['material_id'] ?? ($materials[0]['id'] ?? 0);
$active_material = null;
$active_index = 0;

foreach ($materials as $index => $mat) {
    if ($mat['id'] == $active_material_id) {
        $active_material = $mat;
        $active_index = $index;
        break;
    }
}

// Track progress: Mark current lesson as completed
if ($active_material_id > 0) {
    $progStmt = $conn->prepare("INSERT IGNORE INTO user_progress (user_id, material_id) VALUES (?, ?)");
    $progStmt->bind_param("ii", $_SESSION['user_id'], $active_material_id);
    $progStmt->execute();
}

// Fetch all completed material IDs for this user and subject
$user_id_ref = (int)$_SESSION['user_id'];
$subject_id_ref = (int)$subject_id;
$compStmt = $conn->prepare("
    SELECT material_id FROM user_progress 
    WHERE user_id = ? AND material_id IN (SELECT id FROM materials WHERE subject_id = ?)
");
$compStmt->bind_param("ii", $user_id_ref, $subject_id_ref);
$compStmt->execute();
$comp_result = $compStmt->get_result();
$completed_ids = [];
while($row = $comp_result->fetch_assoc()) {
    $completed_ids[] = $row['material_id'];
}

function getYoutubeEmbedUrl($url) {
    if (empty($url)) return "";
    
    // If it's already an embed URL, return it
    if (strpos($url, 'youtube.com/embed/') !== false) {
        return $url;
    }
    
    $video_id = "";
    $playlist_id = "";
    
    // Check for standard video ID
    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
        $video_id = $match[1];
    }
    
    // Check for playlist ID
    if (preg_match('/[?&]list=([a-zA-Z0-9_-]+)/i', $url, $match)) {
        $playlist_id = $match[1];
    }
    
    // Construct the embed URL securely
    if (!empty($video_id) && !empty($playlist_id)) {
        return "https://www.youtube.com/embed/" . $video_id . "?list=" . $playlist_id;
    } elseif (!empty($video_id)) {
        return "https://www.youtube.com/embed/" . $video_id;
    } elseif (!empty($playlist_id)) {
        return "https://www.youtube.com/embed/videoseries?list=" . $playlist_id;
    }
    
    return $url; 
}

include '../includes/header.php';
?>
<style>
    .sticky-sidebar {
        position: sticky;
        top: 100px;
        height: calc(100vh - 120px);
    }
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 10px;
    }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #334155;
    }
    .video-overlay {
        background: linear-gradient(to bottom, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0) 20%, rgba(0,0,0,0) 80%, rgba(0,0,0,0.7) 100%);
    }
</style>
<main class="flex-1 flex flex-col lg:flex-row max-w-[1440px] mx-auto w-full p-3.5 sm:p-6 lg:p-8 gap-6 lg:gap-10">
<!-- Main Content Area: Video Player & Description -->
<div class="flex-1 flex flex-col gap-8">
<!-- Breadcrumbs -->
<nav class="flex items-center flex-wrap gap-2 text-[11px] sm:text-sm font-medium">
    <a href="home.php" class="text-slate-400 hover:text-primary transition-colors flex items-center gap-1 shrink-0">
        <span class="material-symbols-outlined text-base sm:text-lg">home</span>
    </a>
    <span class="material-symbols-outlined text-slate-300 text-[12px] sm:text-sm">chevron_right</span>
    <a class="text-slate-400 hover:text-primary transition-colors shrink-0" href="subjects.php?category=<?php echo urlencode($subject['category'] ?? ''); ?>"><?php echo htmlspecialchars($subject['category'] ?? 'Category'); ?></a>
    <span class="material-symbols-outlined text-slate-300 text-[12px] sm:text-sm">chevron_right</span>
    <a class="text-slate-400 hover:text-primary transition-colors truncate max-w-[80px] sm:max-w-none" href="#"><?php echo htmlspecialchars($subject['title']); ?></a>
    <span class="material-symbols-outlined text-slate-300 text-[12px] sm:text-sm">chevron_right</span>
    <span class="text-primary font-bold whitespace-nowrap">Current Lesson</span>
</nav>

<!-- Video Player Container -->
<div class="relative w-full aspect-video bg-black rounded-2xl overflow-hidden shadow-2xl ring-1 ring-slate-200 dark:ring-slate-800 group">
    <?php if($active_material && !empty($active_material['video_url'])): ?>
        <iframe class="w-full h-full" src="<?php echo htmlspecialchars(getYoutubeEmbedUrl($active_material['video_url'])); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        <!-- Premium Video Overlay Title -->
        <div class="absolute inset-x-0 top-0 p-6 video-overlay opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none">
            <h3 class="text-white font-bold text-lg lg:text-xl drop-shadow-lg"><?php echo htmlspecialchars($active_material['unit_name']); ?></h3>
        </div>
    <?php else: ?>
        <div class="w-full h-full flex flex-col items-center justify-center gap-4 bg-slate-900">
            <div class="bg-slate-800 p-4 rounded-full">
                <span class="material-symbols-outlined text-5xl text-slate-600">video_library</span>
            </div>
            <p class="text-slate-400 font-bold">No video available for this lesson.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Lesson Information Card -->
<div class="bg-white dark:bg-slate-900 p-5 sm:p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none relative overflow-hidden">
<div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-6 sm:pb-8 mb-6 sm:mb-8 border-b border-slate-100 dark:border-slate-800">
<div class="flex-1 min-w-0">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold mb-3">
        <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-primary"></span>
        </span>
        NOW LEARNING
    </div>
    <h1 class="text-xl sm:text-3xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight transition-colors">
        <?php echo htmlspecialchars($active_material ? $active_material['unit_name'] : 'Select a lesson'); ?>
    </h1>
    <div class="flex flex-wrap items-center gap-2 sm:gap-4 mt-3 text-slate-500 dark:text-slate-400 font-medium text-[11px] sm:text-sm">
        <span class="flex items-center gap-1 hover:text-slate-900 dark:hover:text-slate-300 transition-colors">
            <span class="material-symbols-outlined text-base sm:text-lg">school</span>
            <?php echo htmlspecialchars($subject['category'] ?? 'Unknown'); ?>
        </span>
        <span class="h-1 w-1 rounded-full bg-slate-300"></span>
        <span class="flex items-center gap-1 hover:text-slate-900 dark:hover:text-slate-300 transition-colors">
            <span class="material-symbols-outlined text-base sm:text-lg">menu_book</span>
            <?php echo htmlspecialchars($subject['title']); ?>
        </span>
    </div>
</div>
<div class="flex shrink-0">
<div class="flex flex-col gap-2 shrink-0">
<?php if($active_material && !empty($active_material['notes_url'])): ?>
    <a href="<?php echo htmlspecialchars($active_material['notes_url']); ?>" target="_blank" class="group relative inline-flex items-center justify-center gap-3 bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-emerald-600/30 transition-all hover:-translate-y-0.5 active:scale-95 active:translate-y-0 overflow-hidden text-sm">
        <span class="absolute inset-0 bg-white/10 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
        <span class="material-symbols-outlined text-lg transition-transform group-hover:scale-110">description</span>
        <span class="relative">View Notes</span>
    </a>
<?php endif; ?>
<?php if($active_material && !empty($active_material['exam_url'])): ?>
    <a href="<?php echo htmlspecialchars($active_material['exam_url']); ?>" target="_blank" class="group relative inline-flex items-center justify-center gap-3 bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-orange-600/30 transition-all hover:-translate-y-0.5 active:scale-95 active:translate-y-0 overflow-hidden text-sm">
        <span class="absolute inset-0 bg-white/10 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
        <span class="material-symbols-outlined text-lg transition-transform group-hover:scale-110">assignment</span>
        <span class="relative">View Exam Paper</span>
    </a>
<?php endif; ?>
</div>
</div>
</div>

<?php if($active_material && !empty($active_material['description'])): ?>
<div class="prose dark:prose-invert max-w-none prose-p:leading-relaxed prose-headings:font-bold">
    <div class="flex items-center gap-3 mb-6 text-slate-900 dark:text-slate-100">
        <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-lg">
            <span class="material-symbols-outlined block">description</span>
        </div>
        <h3 class="text-xl font-bold m-0">Lesson Overview</h3>
    </div>
    <div class="text-slate-600 dark:text-slate-400">
        <?php echo nl2br(htmlspecialchars($active_material['description'])); ?>
    </div>
</div>
<?php endif; ?>

<?php if($active_material && !empty($active_material['uploader_name'])): ?>
<div class="mt-8 flex flex-wrap items-center gap-3">
    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold border border-slate-200 dark:border-slate-700">
        <span class="material-symbols-outlined text-sm">account_circle</span>
        Uploaded by: <?php echo htmlspecialchars($active_material['uploader_name']); ?>
    </span>
    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold border border-slate-200 dark:border-slate-700">
        <span class="material-symbols-outlined text-sm">calendar_today</span>
        Added: <?php echo date('M d, Y', strtotime($active_material['created_at'] ?? 'now')); ?>
    </span>
    <div class="flex items-center gap-1 ml-2">
        <?php if(!empty($active_material['video_url'])): ?>
            <span class="w-7 h-7 flex items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30 text-red-600" title="Video Content"><span class="material-symbols-outlined text-sm">play_circle</span></span>
        <?php endif; ?>
        <?php if(!empty($active_material['notes_url'])): ?>
            <span class="w-7 h-7 flex items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600" title="Study Notes"><span class="material-symbols-outlined text-sm">description</span></span>
        <?php endif; ?>
        <?php if(!empty($active_material['exam_url'])): ?>
            <span class="w-7 h-7 flex items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-600" title="Exam Papers"><span class="material-symbols-outlined text-sm">assignment</span></span>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
</div>
<!-- Navigation Controls -->
<div class="flex items-center justify-between gap-4 pb-12 mt-4 sm:mt-6">
<?php
$prevMaterialId = ($active_index > 0) ? $materials[$active_index - 1]['id'] : null;
$nextMaterialId = ($active_index < count($materials) - 1) ? $materials[$active_index + 1]['id'] : null;
?>
<?php if($prevMaterialId): ?>
<a href="subject_details.php?id=<?php echo $subject['id']; ?>&material_id=<?php echo $prevMaterialId; ?>" class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 sm:px-8 py-3 sm:py-4 rounded-xl border-2 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition-all active:scale-95 text-xs sm:text-sm">
<span class="material-symbols-outlined text-lg">arrow_back</span>
<span class="hidden xs:inline">Previous</span><span class="inline xs:hidden">Prev</span>
</a>
<?php else: ?>
<div class="flex-1 sm:flex-none"></div>
<?php endif; ?>

<?php if($nextMaterialId): ?>
<a href="subject_details.php?id=<?php echo $subject['id']; ?>&material_id=<?php echo $nextMaterialId; ?>" class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 sm:px-8 py-3 sm:py-4 rounded-xl bg-primary text-white font-bold shadow-xl shadow-primary/20 hover:bg-primary/90 transition-all active:scale-95 text-xs sm:text-sm">
<span class="hidden xs:inline">Next Lesson</span><span class="inline xs:hidden">Next</span>
<span class="material-symbols-outlined text-lg">arrow_forward</span>
</a>
<?php else: ?>
<div class="flex-1 sm:flex-none"></div>
<?php endif; ?>
</div>
</div>
<!-- Sidebar: Course Content & AI Tutor -->
<aside class="w-full lg:w-[420px] flex flex-col gap-6 sticky-sidebar overflow-hidden">
<div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none flex flex-col h-full overflow-hidden relative">

<!-- Sidebar Persistent Header (Always Visible) -->
<div class="p-4 border-b border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 sticky top-0 shrink-0 z-30 relative shadow-sm">
    <div class="flex bg-slate-100 dark:bg-slate-800/80 p-1.5 rounded-xl w-full border border-slate-200 dark:border-slate-700">
        <button id="tab-playlist" onclick="switchSidebarTab('playlist')" class="flex-1 py-2 text-sm font-bold bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg shadow-sm transition-all focus:outline-none flex items-center justify-center gap-2 group">
            <span class="material-symbols-outlined text-[18px] transition-transform group-hover:scale-110">format_list_bulleted</span> Playlist
        </button>
        <button id="tab-ai" onclick="switchSidebarTab('ai')" class="flex-1 py-2 text-sm font-bold text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-all focus:outline-none flex items-center justify-center gap-2 group">
            <span class="material-symbols-outlined text-[18px] transition-transform group-hover:scale-110">smart_toy</span> AI Tutor
        </button>
    </div>
</div>

<!-- CONTENT AREAS AREA -->
<!-- 1. Playlists Content -->
<div id="content-playlist" class="flex-1 flex flex-col overflow-hidden transition-all duration-200">
    <div class="px-5 sm:px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 shrink-0">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-black text-slate-900 dark:text-slate-100 text-lg sm:text-xl tracking-tight">Experience Flow</h2>
        <span class="bg-primary/10 text-primary text-[9px] uppercase tracking-widest font-black px-2 py-1 rounded">Subject Hub</span>
    </div>
<?php if(count($materials)>0): ?>
    <?php 
    $completed_count = count($completed_ids);
    $total_lessons = count($materials);
    $progress = min(100, round(($completed_count / $total_lessons) * 100)); 
    ?>
    <div class="flex items-center justify-between mb-2">
        <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Overall Progress</p>
        <p class="text-xs text-primary font-black"><?php echo $progress; ?>%</p>
    </div>
    <div class="w-full bg-slate-200 dark:bg-slate-700 h-2.5 rounded-full overflow-hidden mb-2">
        <div class="bg-primary h-full rounded-full transition-all duration-1000 ease-out shadow-[0_0_8px_rgba(79,70,229,0.5)]" style="width: <?php echo $progress; ?>%"></div>
    </div>
    <p class="text-[11px] text-slate-400 font-medium italic"><?php echo $completed_count; ?> of <?php echo $total_lessons; ?> topics completed</p>
<?php endif; ?>
</div>

<div class="flex flex-col overflow-y-auto custom-scrollbar flex-1 pb-4">
<?php foreach($materials as $idx => $mat): ?>
    <?php 
    $isActive = ($mat['id'] == $active_material_id); 
    $isRecordCompleted = in_array($mat['id'], $completed_ids);
    ?>
    <a href="subject_details.php?id=<?php echo $subject['id']; ?>&material_id=<?php echo $mat['id']; ?>" class="group flex items-center gap-3 sm:gap-4 px-4 sm:px-6 py-4 sm:py-5 border-l-4 transition-all duration-300 <?php echo $isActive ? "bg-primary/5 border-primary" : "border-transparent hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:border-slate-200 dark:hover:border-slate-700"; ?> relative">
        <!-- Number Circle / Status Icon -->
        <div class="shrink-0 relative">
            <?php if($isActive): ?>
                <div class="size-10 flex items-center justify-center rounded-xl bg-primary text-white text-xs font-black shadow-lg shadow-primary/30 z-10 relative">
                    <span class="material-symbols-outlined text-sm animate-pulse">play_arrow</span>
                </div>
                <div class="absolute inset-0 bg-primary opacity-20 rounded-xl blur animate-pulse"></div>
            <?php elseif($isRecordCompleted): ?>
                <div class="size-10 flex items-center justify-center rounded-xl bg-emerald-500 text-white text-xs font-bold transition-all shadow-md shadow-emerald-500/20">
                    <span class="material-symbols-outlined text-lg">check</span>
                </div>
            <?php else: ?>
                <div class="size-10 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-xs font-black border border-slate-200 dark:border-slate-700 transition-all group-hover:border-primary group-hover:text-primary">
                    <?php echo str_pad($idx + 1, 2, '0', STR_PAD_LEFT); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Text Content -->
        <div class="flex-1 min-w-0">
            <p class="text-sm font-bold truncate transition-colors <?php echo $isActive ? "text-primary" : "text-slate-700 dark:text-slate-200 group-hover:text-primary"; ?>">
                <?php echo htmlspecialchars($mat['unit_name']); ?>
            </p>
            <div class="flex items-center gap-2 mt-1">
                <span class="flex items-center gap-1 text-[10px] font-bold uppercase tracking-tighter text-slate-400 group-hover:text-slate-500">
                    <span class="material-symbols-outlined text-xs">play_circle</span>
                    Video Lecture
                </span>
                <?php if($isActive): ?>
                    <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                    <span class="text-[10px] font-black text-primary uppercase animate-pulse">Playing</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Arrow (Hover only) -->
        <span class="material-symbols-outlined text-slate-300 opacity-0 group-hover:opacity-100 transition-all translate-x-[-10px] group-hover:translate-x-0">chevron_right</span>
    </a>
<?php endforeach; ?>
<?php if(count($materials) == 0): ?>
    <p class="p-6 text-sm text-slate-400 italic">No modules available yet.</p>
<?php endif; ?>
    </div> <!-- End Playlist Scrollable Area -->
</div> <!-- End content-playlist -->

<!-- 2. AI Tutor Content -->
<div id="content-ai" class="flex-1 flex flex-col overflow-hidden transition-all duration-200 hidden">
    <!-- Chat Messages -->
    <div id="chat-messages" class="flex-1 overflow-y-auto custom-scrollbar p-5 space-y-6 text-sm flex flex-col bg-slate-50/50 dark:bg-slate-900/50">
        <!-- Messages will be injected here -->
    </div>
    
    <!-- Quick Actions Tray (Grid/Wrap) -->
    <div class="px-4 py-3 bg-slate-50/80 dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 shrink-0 flex flex-wrap items-center gap-2">
        <button onclick="sendQuickAction('Generate quiz questions from this topic')" class="flex-1 min-w-[120px] px-3 py-2 bg-white dark:bg-slate-800 hover:bg-primary/5 text-slate-600 dark:text-slate-300 hover:text-primary text-xs font-bold rounded-xl transition-all border border-slate-200 dark:border-slate-700 hover:border-primary/30 flex items-center justify-center gap-1.5 shadow-sm">
             <span class="material-symbols-outlined text-[16px]">help_outline</span> Ask Quiz
        </button>
        <button onclick="sendQuickAction('Explain and solve doubts in simple way')" class="flex-1 min-w-[120px] px-3 py-2 bg-white dark:bg-slate-800 hover:bg-primary/5 text-slate-600 dark:text-slate-300 hover:text-primary text-xs font-bold rounded-xl transition-all border border-slate-200 dark:border-slate-700 hover:border-primary/30 flex items-center justify-center gap-1.5 shadow-sm">
             <span class="material-symbols-outlined text-[16px]">quiz</span> Ask Doubt
        </button>
        <button onclick="sendQuickAction('Explain this topic step by step with examples')" class="flex-1 min-w-[120px] px-3 py-2 bg-white dark:bg-slate-800 hover:bg-primary/5 text-slate-600 dark:text-slate-300 hover:text-primary text-xs font-bold rounded-xl transition-all border border-slate-200 dark:border-slate-700 hover:border-primary/30 flex items-center justify-center gap-1.5 shadow-sm">
             <span class="material-symbols-outlined text-[16px]">description</span> Explain Topic
        </button>
        <button onclick="sendQuickAction('Give important exam questions')" class="flex-1 min-w-[120px] px-3 py-2 bg-white dark:bg-slate-800 hover:bg-primary/5 text-slate-600 dark:text-slate-300 hover:text-primary text-xs font-bold rounded-xl transition-all border border-slate-200 dark:border-slate-700 hover:border-primary/30 flex items-center justify-center gap-1.5 shadow-sm">
             <span class="material-symbols-outlined text-[16px]">assignment_turned_in</span> Important Qs
        </button>
        <button onclick="clearChat()" class="p-2 text-slate-400 hover:text-red-500 transition-all rounded-lg hover:bg-red-50 dark:hover:bg-red-900/10 ml-auto" title="Clear chat history">
             <span class="material-symbols-outlined text-[20px]">delete_sweep</span>
        </button>
    </div>

    <!-- Chat Input Form -->
    <div class="p-4 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 shrink-0">
        <form id="chat-form" class="relative flex items-center gap-2">
            <input type="text" id="chat-input" placeholder="Ask your tutor anything..." required autocomplete="off" class="w-full bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100 rounded-full pl-5 pr-12 py-3.5 focus:outline-none focus:ring-2 focus:ring-primary/40 text-sm border-0 shadow-inner">
            <button type="submit" id="chat-submit" class="absolute right-1.5 text-white bg-primary hover:bg-primary/90 rounded-full w-9 h-9 flex items-center justify-center transition-all disabled:opacity-50 active:scale-95 shadow-md shadow-primary/30 group">
                <span class="material-symbols-outlined text-[18px] transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5">send</span>
            </button>
        </form>
    </div>
    </div>
</div>
</aside>
</main>
<?php include '../includes/footer.php'; ?>

<script>
// Context Variables for AI
const videoContext = {
    id: <?php echo json_encode($active_material_id); ?>,
    title: <?php echo json_encode($active_material ? $active_material['unit_name'] : ''); ?>,
    description: <?php echo json_encode($active_material ? $active_material['description'] : ''); ?>,
    subject: <?php echo json_encode($subject['title'] . ' (' . ($subject['category'] ?? '') . ')'); ?>
};

// Storage Utils
const HISTORY_KEY = `chat_history_v2_${videoContext.id}`;
let chatHistory = JSON.parse(localStorage.getItem(HISTORY_KEY) || "[]");

function saveHistory() {
    localStorage.setItem(HISTORY_KEY, JSON.stringify(chatHistory));
}

function clearChat() {
    if(confirm('Are you sure you want to clear your chat history for this lesson?')) {
        chatHistory = [];
        saveHistory();
        renderChat();
    }
}

function switchSidebarTab(tab) {
    const btnPlaylist = document.getElementById('tab-playlist');
    const btnAi = document.getElementById('tab-ai');
    const contentPlaylist = document.getElementById('content-playlist');
    const contentAi = document.getElementById('content-ai');

    if (tab === 'playlist') {
        btnPlaylist.className = "flex-1 py-2 text-sm font-bold bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg shadow-sm transition-all focus:outline-none flex items-center justify-center gap-2 group";
        btnAi.className = "flex-1 py-2 text-sm font-bold text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-all focus:outline-none flex items-center justify-center gap-2 group";
        
        contentPlaylist.classList.remove('hidden');
        contentAi.classList.add('hidden');
    } else {
        btnAi.className = "flex-1 py-2 text-sm font-bold bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg shadow-sm transition-all focus:outline-none flex items-center justify-center gap-2 group";
        btnPlaylist.className = "flex-1 py-2 text-sm font-bold text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-all focus:outline-none flex items-center justify-center gap-2 group";
        
        contentPlaylist.classList.add('hidden');
        contentAi.classList.remove('hidden');
        document.getElementById('chat-input').focus();
    }
}

// Chat UI Logic
const chatForm = document.getElementById('chat-form');
const chatInput = document.getElementById('chat-input');
const chatMessages = document.getElementById('chat-messages');
const chatSubmit = document.getElementById('chat-submit');

function sendQuickAction(query) {
    chatInput.value = query;
    chatForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
}

function copyToClipboard(btn) {
    const text = btn.getAttribute('data-copy');
    if (!text || text === "") {
        console.warn("Nothing to copy");
        return;
    }
    
    // Modern API
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showCopySuccess(btn);
        }).catch(err => {
            fallbackCopy(text, btn);
        });
    } else {
        fallbackCopy(text, btn);
    }
}

function fallbackCopy(text, btn) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.select();
    try {
        document.execCommand('copy');
        showCopySuccess(btn);
    } catch (err) {
        console.error('Fallback copy failed', err);
    }
    document.body.removeChild(textArea);
}

function showCopySuccess(btn) {
    const icon = btn.querySelector('.material-symbols-outlined');
    const oldIcon = icon.innerText;
    icon.innerText = 'done_all';
    btn.classList.add('text-emerald-500', 'border-emerald-200', 'bg-emerald-50');
    setTimeout(() => {
        icon.innerText = oldIcon;
        btn.classList.remove('text-emerald-500', 'border-emerald-200', 'bg-emerald-50');
    }, 2000);
}

function appendUserMessage(text, time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })) {
    const msgDiv = document.createElement('div');
    msgDiv.className = "flex flex-col gap-1 max-w-[85%] self-end ms-auto items-end";
    msgDiv.innerHTML = `
        <div class="bg-primary text-white p-4 rounded-2xl rounded-tr-sm shadow-md shadow-primary/20 leading-relaxed text-sm">
            ${text.replace(/</g, "&lt;").replace(/>/g, "&gt;")}
        </div>
        <span class="text-[10px] text-slate-400 font-medium px-1">${time}</span>
    `;
    chatMessages.appendChild(msgDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function appendAIMessage(htmlContent, rawText = "", time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })) {
    const msgDiv = document.createElement('div');
    msgDiv.className = "flex flex-col gap-1 max-w-[90%] items-start group";
    msgDiv.innerHTML = `
        <div class="flex gap-2.5 items-end">
            <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0 relative mb-1">
                <span class="material-symbols-outlined text-[18px]">smart_toy</span>
            </div>
            <div class="relative">
                <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-4 rounded-2xl rounded-tl-sm text-slate-700 dark:text-slate-300 shadow-sm leading-relaxed ai-markdown-content text-sm relative">
                    ${htmlContent}
                </div>
                <!-- Mini Copy Button -->
                <button onclick="copyToClipboard(this)" data-copy="${rawText.replace(/"/g, '&quot;')}" class="absolute -bottom-2 -right-2 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 p-2 rounded-lg text-slate-400 hover:text-primary shadow-md transition-all active:scale-90 flex items-center justify-center z-10" title="Copy response">
                    <span class="material-symbols-outlined text-[16px]">content_copy</span>
                </button>
            </div>
        </div>
        <span class="text-[10px] text-slate-400 font-medium ml-10">${time}</span>
    `;
    chatMessages.appendChild(msgDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    return msgDiv;
}

function appendTypingIndicator() {
    const idx = 'typing-' + Date.now();
    const msgDiv = document.createElement('div');
    msgDiv.id = idx;
    msgDiv.className = "flex gap-2.5 max-w-[90%] items-start animate-fade-in";
    msgDiv.innerHTML = `
        <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0 relative mt-1">
            <span class="material-symbols-outlined text-[18px]">smart_toy</span>
        </div>
        <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 p-4 rounded-2xl rounded-tl-sm text-slate-400 shadow-sm flex items-center gap-1.5 h-11">
            <div class="w-1.5 h-1.5 rounded-full bg-primary/40 animate-bounce" style="animation-delay: 0ms"></div>
            <div class="w-1.5 h-1.5 rounded-full bg-primary/40 animate-bounce" style="animation-delay: 150ms"></div>
            <div class="w-1.5 h-1.5 rounded-full bg-primary/40 animate-bounce" style="animation-delay: 300ms"></div>
        </div>
    `;
    chatMessages.appendChild(msgDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    return idx;
}

function parseMarkdown(md) {
    let html = md.replace(/\n\n/g, '</p><p class="mt-2.5">');
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
    html = html.replace(/`(.*?)`/g, '<code class="bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded text-primary font-mono text-[13px]">$1</code>');
    return `<p>${html}</p>`;
}

function renderChat() {
    chatMessages.innerHTML = '';
    if (chatHistory.length === 0) {
        const welcome = `Hi 👋 I’m your AI Tutor. Ask me anything about ${videoContext.title}, or use the quick buttons above to get started!`;
        appendAIMessage(`Hi 👋 I’m your AI Tutor. Ask me anything about <strong>${videoContext.title}</strong>, or use the quick buttons above to get started!`, welcome, new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
    } else {
        chatHistory.forEach(msg => {
            const time = msg.timestamp || "";
            if (msg.role === 'user') {
                appendUserMessage(msg.content, time);
            } else {
                appendAIMessage(parseMarkdown(msg.content), msg.content, time);
            }
        });
    }
}

chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const message = chatInput.value.trim();
    if (!message) return;

    const timeStamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    // UI Updates
    chatInput.value = '';
    chatInput.disabled = true;
    chatSubmit.disabled = true;
    appendUserMessage(message, timeStamp);
    const typingId = appendTypingIndicator();

    // Push to history
    chatHistory.push({ role: "user", content: message, timestamp: timeStamp });
    saveHistory();

    try {
        // Migration: Calling OpenRouter directly from client to bypass InfinityFree server-side blocks
        const response = await fetch('https://openrouter.ai/api/v1/chat/completions', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Authorization': 'Bearer YOUR_API_KEY_HERE' 
            },
            body: JSON.stringify({
                model: "meta-llama/llama-3.2-3b-instruct",
                messages: [
                    {
                        role: "system",
                        content: `You are a helpful AI tutor for students. Explain everything in simple language with examples. Keep answers short and clear. Current Topic: ${videoContext.title}. Description: ${videoContext.description}. Subject: ${videoContext.subject}`
                    },
                    ...chatHistory.map(h => ({role: h.role, content: h.content})).slice(-10)
                ]
            })
        });

        const data = await response.json();
        const indicator = document.getElementById(typingId);
        if(indicator) indicator.remove();
        
        if (data.choices && data.choices.length > 0) {
            const aiMessage = data.choices[0].message.content;
            const aiTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            // Push to history and save
            chatHistory.push({ role: "assistant", content: aiMessage, timestamp: aiTime });
            saveHistory();
            
            appendAIMessage(parseMarkdown(aiMessage), aiMessage, aiTime);
        } else if (data.error) {
            const errorMsg = data.error.message || JSON.stringify(data.error);
            appendAIMessage(`<i class="text-red-500">AI Error: ${errorMsg}</i>`);
            console.error('OpenRouter Error:', data.error);
        } else {
            appendAIMessage("<i>Sorry, I ran into an error generating a response. Please try again or check your API key.</i>");
        }
    } catch (err) {
        const indicator = document.getElementById(typingId);
        if(indicator) indicator.remove();
        appendAIMessage("<i class='text-red-500 text-xs'>Connection error. Please check your network.</i>");
    } finally {
        chatInput.disabled = false;
        chatSubmit.disabled = false;
        chatInput.focus();
    }
});

// Initial Render
renderChat();
</script>
