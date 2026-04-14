<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['subject_title']);
    
    if (isset($_POST['category']) && $_POST['category'] === 'Other' && !empty(trim($_POST['category_other']))) {
        $category = trim($_POST['category_other']);
    } else {
        $category = trim($_POST['category'] ?? '');
    }

    $units = $_POST['units'] ?? [];
    $uploader_id = $_SESSION['user_id'];

    if (empty($title) || empty($category)) {
        $error = "Subject Title and Category are required.";
    } elseif (empty($units)) {
        $error = "At least one course part is required.";
    } else {
        // Check if subject exists
        $stmt = $conn->prepare("SELECT id FROM subjects WHERE title = ? AND category = ?");
        $stmt->bind_param("ss", $title, $category);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $subject = $result->fetch_assoc();
            $subject_id = $subject['id'];
        } else {
            $stmt = $conn->prepare("INSERT INTO subjects (title, description, category) VALUES (?, '', ?)");
            $stmt->bind_param("ss", $title, $category);
            $stmt->execute();
            $subject_id = $stmt->insert_id;
        }

        $success_count = 0;
        foreach ($units as $unit) {
            $u_title = trim($unit['title'] ?? '');
            $u_desc = trim($unit['description'] ?? '');
            $v_url = trim($unit['video_url'] ?? '');
            $n_url = trim($unit['notes_url'] ?? '');
            $e_url = trim($unit['exam_url'] ?? '');

            if (empty($u_title) && empty($u_desc) && empty($v_url) && empty($n_url) && empty($e_url)) {
                continue;
            }

            if (!empty($v_url)) {
                if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $v_url, $matches)) {
                    $v_url = 'https://www.youtube.com/embed/' . $matches[1];
                }
            }

            $stmt = $conn->prepare("INSERT INTO materials (subject_id, unit_name, video_url, notes_url, exam_url, description, uploader_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssi", $subject_id, $u_title, $v_url, $n_url, $e_url, $u_desc, $uploader_id);
            if ($stmt->execute()) {
                $success_count++;
            }
        }
        
        if ($success_count > 0) {
            $message = "Successfully uploaded $success_count part(s)! (Redirecting...)";
            echo "<script>setTimeout(() => window.location.href='my_uploads.php', 2000);</script>";
        } else {
            $error = "Failed to upload contents or fields were left entirely empty.";
        }
    }
}

// Fetch categories for suggestion
$categories_query = $conn->query("SELECT DISTINCT category FROM subjects");
$existing_categories = [];
while ($cat_row = $categories_query->fetch_assoc()) {
    if (!empty($cat_row['category'])) {
        $existing_categories[] = $cat_row['category'];
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Upload Content | BCA Learning Hub</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries,typography"></script>
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
        .preview-placeholder { background-image: linear-gradient(135deg, #f1f5f9 25%, #f8fafc 100%); }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen antialiased">

<?php include '../includes/header.php'; ?>

<main class="pt-4 sm:pt-10 pb-12 px-3.5 sm:px-6 lg:px-8 max-w-4xl mx-auto">
    <!-- Header Section -->
    <header class="mb-6 mt-4 sm:mb-10 sm:mt-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mb-1 sm:mb-2">Upload Course Content</h1>
            <p class="text-xs sm:text-sm text-slate-500 font-medium">Share your learning materials with the BCA Hub community.</p>
        </div>
        <a href="my_uploads.php" class="flex items-center gap-2 text-xs sm:text-sm font-bold text-slate-500 hover:text-primary transition-colors shrink-0">
            <span class="material-symbols-outlined text-lg">arrow_back</span> Back to list
        </a>
    </header>

    <?php if ($message): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl font-medium flex items-center gap-3">
            <span class="material-symbols-outlined">check_circle</span>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl font-medium flex items-center gap-3">
            <span class="material-symbols-outlined">error</span>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8">
        <!-- Main Form Section -->
        <div class="lg:col-span-8 space-y-5 sm:space-y-6">
            <div class="bg-white p-5 sm:p-10 rounded-2xl border border-slate-200 shadow-xl">
                <form class="space-y-6" method="POST" action="">
                    <div class="space-y-4 border-b border-slate-200 pb-6 mb-6">
                        <!-- Subject Title -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500" for="subject-title">Subject Title</label>
                            <input name="subject_title" class="w-full bg-slate-100 border-0 rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary/20 text-sm placeholder:text-slate-400" id="subject-title" placeholder="e.g. Advanced Data Structures & Algorithms" type="text" required />
                        </div>

                        <!-- Category -->
                        <div class="space-y-2 relative">
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500" for="category">Category</label>
                            <input name="category" class="w-full bg-slate-100 border-0 rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary/20 text-sm text-slate-700 placeholder:text-slate-400" id="category" placeholder="Search or Type new category..." required autocomplete="off" />
                            <div id="category-autocomplete-container" class="absolute z-50 w-full left-0 top-full mt-1 bg-white border border-slate-200 rounded-xl shadow-2xl overflow-hidden hidden transform transition-all origin-top scale-95 opacity-0">
                                <ul id="category-autocomplete-list" class="max-h-60 overflow-y-auto divide-y divide-slate-100">
                                </ul>
                            </div>
                        </div>
                        
                        <div class="pt-6 border-t border-slate-200 mt-6 space-y-5">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <h3 class="text-sm font-black text-slate-700 flex items-center gap-2 tracking-tight">
                                    <span class="material-symbols-outlined text-indigo-500 text-lg">format_list_numbered</span>
                                    Course Modules / Parts
                                </h3>
                                <button type="button" id="add-chapter-btn" class="shrink-0 w-full sm:w-auto bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white px-4 py-2.5 rounded-lg font-bold text-xs tracking-wide transition-colors flex items-center justify-center gap-2 border border-indigo-200 hover:border-indigo-600 shadow-sm">
                                    <span class="material-symbols-outlined text-[18px]">add_circle</span> Add New Part
                                </button>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap bg-slate-50 p-2 rounded-xl" id="chapter-tabs-container">
                                <!-- Tabs will inject here -->
                            </div>
                        </div>
                    </div>

                    <!-- Chapters Container -->
                    <div id="chapters-container" class="space-y-6">
                        <!-- Default First Chapter Block -->
                    </div>

                    <!-- Submit Action -->
                    <div class="pt-6 border-t border-slate-200">
                        <button class="w-full bg-primary text-white font-black py-4 rounded-xl hover:bg-indigo-700 transition-all shadow-xl shadow-primary/20 flex items-center justify-center gap-3 text-sm uppercase tracking-widest" type="submit">
                            <span class="material-symbols-outlined">publish</span>
                            Submit Content
                        </button>
                        <p class="text-center text-[10px] text-slate-400 mt-4 uppercase font-bold tracking-widest">
                            Uploaded by: <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Info Sidebar -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-gradient-to-br from-[#4F46E5] to-[#3730A3] text-white p-8 rounded-2xl relative overflow-hidden shadow-xl">
                <div class="relative z-10">
                    <h3 class="text-xl font-black mb-6 tracking-tight flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-300">lightbulb</span>
                        Submission Rules
                    </h3>
                    <ul class="space-y-6">
                        <li class="flex gap-4">
                            <div class="w-8 h-8 rounded-lg bg-indigo-800/50 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-indigo-300 text-sm">verified</span>
                            </div>
                            <p class="text-xs leading-relaxed font-medium opacity-90">Verify your YouTube link is set to "Public" or "Unlisted" so others can view it.</p>
                        </li>
                        <li class="flex gap-4">
                            <div class="w-8 h-8 rounded-lg bg-indigo-800/50 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-indigo-300 text-sm">lock_open</span>
                            </div>
                            <p class="text-xs leading-relaxed font-medium opacity-90">Ensure your Google Drive links have access changed to "Anyone with the link can view".</p>
                        </li>
                        <li class="flex gap-4">
                            <div class="w-8 h-8 rounded-lg bg-indigo-800/50 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-indigo-300 text-sm">workspace_premium</span>
                            </div>
                            <p class="text-xs leading-relaxed font-medium opacity-90">A combination of video, notes, and exam papers makes the best study material.</p>
                        </li>
                    </ul>
                </div>
                <!-- Decorative Background -->
                <div class="absolute -right-12 -bottom-12 w-48 h-48 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -left-8 top-0 w-32 h-32 bg-white/5 rounded-full blur-2xl"></div>
            </div>
        </div>
    </div>
</main>

<template id="chapter-template">
    <div class="chapter-block p-6 bg-white border-2 border-indigo-50 rounded-2xl relative shadow-sm hidden" data-index="">
        <button type="button" class="remove-chapter absolute top-5 right-5 w-8 h-8 flex items-center justify-center rounded-full bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-colors" title="Remove Part">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
        <h4 class="font-black text-slate-800 text-lg mb-5 flex items-center gap-2"><span class="material-symbols-outlined text-indigo-500">list_alt</span> <span class="chapter-number-text">Part</span></h4>
        
        <div class="space-y-5">
            <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Part / Video Title</label>
                <input class="part-title w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary/20 text-sm placeholder:text-slate-400" placeholder="e.g. Chapter 1: Introduction" type="text" required />
            </div>

            <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Content Description (Optional)</label>
                <textarea class="part-desc w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary/20 text-sm placeholder:text-slate-400 resize-none" placeholder="Notes or description about this specific part..." rows="2"></textarea>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">YouTube Video Link</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 group-focus-within:text-primary"><span class="material-symbols-outlined text-lg">ondemand_video</span></div>
                    <input class="part-youtube-link youtube-input-trigger w-full bg-slate-50 border border-slate-200 rounded-lg pl-12 pr-4 py-3 focus:ring-2 focus:ring-primary/20 text-sm placeholder:text-slate-400" placeholder="https://www.youtube.com/watch?v=..." type="url"/>
                </div>
            </div>

            <!-- Previews -->
            <div class="preview-area hidden space-y-2">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px]">visibility</span> Preview</label>
                <div class="relative aspect-video rounded-xl overflow-hidden bg-slate-900 shadow-inner">
                    <iframe allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full preview-iframe" frameborder="0" src=""></iframe>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 p-4 sm:p-6 bg-slate-50 rounded-2xl border border-slate-100">
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Drive Notes Link</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-emerald-400 group-focus-within:text-emerald-600"><span class="material-symbols-outlined text-[16px]">description</span></div>
                        <input class="part-notes-link w-full bg-emerald-50 border border-emerald-100 rounded-lg pl-9 pr-3 py-2.5 focus:ring-2 focus:ring-emerald-500/20 text-xs placeholder:text-slate-400" placeholder="Optional" type="url"/>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Drive Exam Paper Link</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-orange-400 group-focus-within:text-orange-600"><span class="material-symbols-outlined text-[16px]">assignment</span></div>
                        <input class="part-exam-link w-full bg-orange-50 border border-orange-100 rounded-lg pl-9 pr-3 py-2.5 focus:ring-2 focus:ring-orange-500/20 text-xs placeholder:text-slate-400" placeholder="Optional" type="url"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    // Custom Autocomplete for Categories
    const categoryOptions = [
        "Academic", "Programming", "IT & CS", "Data & AI", "Notes", 
        "Exam Papers", "Competitive Exams", "Placement", "Skills", 
        "Design", "Tools", "Cyber Security", "General Knowledge", 
        "Projects", "Self Improvement"
    ];

    const catInput = document.getElementById('category');
    const catContainer = document.getElementById('category-autocomplete-container');
    const catList = document.getElementById('category-autocomplete-list');

    function renderCategoryDropdown(filterText = '') {
        catList.innerHTML = '';
        const lowercaseFilter = filterText.toLowerCase();
        
        let matches = categoryOptions.filter(cat => cat.toLowerCase().includes(lowercaseFilter));
        let exactMatch = categoryOptions.some(cat => cat.toLowerCase() === lowercaseFilter);
        
        if (matches.length === 0 && filterText.trim() === '') {
            matches = categoryOptions;
        }

        matches.forEach(item => {
            const li = document.createElement('li');
            li.className = 'px-4 py-3 hover:bg-indigo-50 hover:text-indigo-700 cursor-pointer text-sm font-semibold text-slate-700 transition-colors flex items-center justify-between group';
            li.innerHTML = `<span>${item}</span> <span class="material-symbols-outlined text-[16px] text-transparent group-hover:text-indigo-400 transition-colors">check_circle</span>`;
            li.onmousedown = function(e) {
                e.preventDefault();
                catInput.value = item;
                hideCategoryDropdown();
            }
            catList.appendChild(li);
        });

        if (filterText.trim() !== '' && !exactMatch) {
            const liNew = document.createElement('li');
            liNew.className = 'px-4 py-3 bg-slate-50 hover:bg-indigo-50 text-indigo-600 cursor-pointer text-sm border-t border-slate-200 transition-colors flex items-center gap-2';
            liNew.innerHTML = `<span class="material-symbols-outlined text-[18px]">add_circle</span> <span>Create <strong>"${filterText}"</strong></span>`;
            liNew.onmousedown = function(e) {
                e.preventDefault();
                catInput.value = filterText;
                hideCategoryDropdown();
            }
            catList.appendChild(liNew);
        }

        if (catList.childElementCount > 0) {
            catContainer.classList.remove('hidden');
            // Small delay for transition to apply
            setTimeout(() => {
                catContainer.classList.remove('scale-95', 'opacity-0');
                catContainer.classList.add('scale-100', 'opacity-100');
            }, 10);
        } else {
            hideCategoryDropdown();
        }
    }

    function hideCategoryDropdown() {
        catContainer.classList.remove('scale-100', 'opacity-100');
        catContainer.classList.add('scale-95', 'opacity-0');
        setTimeout(() => catContainer.classList.add('hidden'), 150);
    }

    catInput.addEventListener('focus', () => renderCategoryDropdown(catInput.value));
    catInput.addEventListener('input', () => renderCategoryDropdown(catInput.value));
    catInput.addEventListener('blur', hideCategoryDropdown);

    // Dynamic Chapters Logic
    let chapterIndex = 0;
    const chaptersContainer = document.getElementById('chapters-container');
    const tabsContainer = document.getElementById('chapter-tabs-container');
    const template = document.getElementById('chapter-template');

    function switchTab(index) {
        // Toggle blocks
        document.querySelectorAll('.chapter-block').forEach(block => {
            if (parseInt(block.dataset.index) === index) block.classList.remove('hidden');
            else block.classList.add('hidden');
        });
        
        // Toggle tabs
        document.querySelectorAll('.tab-btn').forEach(tab => {
            if (parseInt(tab.dataset.index) === index) {
                tab.classList.add('bg-primary', 'text-white', 'shadow-md');
                tab.classList.remove('bg-slate-100', 'text-slate-600', 'hover:bg-slate-200');
            } else {
                tab.classList.remove('bg-primary', 'text-white', 'shadow-md');
                tab.classList.add('bg-slate-100', 'text-slate-600', 'hover:bg-slate-200');
            }
        });
    }

    function updateVisualNumbers() {
        let visualCounter = 1;
        tabsContainer.querySelectorAll('.tab-btn').forEach(btn => {
            btn.innerText = visualCounter++;
        });
        visualCounter = 1;
        chaptersContainer.querySelectorAll('.chapter-block').forEach(b => {
            b.querySelector('.chapter-number-text').innerText = 'Part ' + visualCounter++;
        });
    }

    function addChapter() {
        if (chaptersContainer.querySelectorAll('.chapter-block').length >= 50) {
            alert('Maximum limit of 50 parts reached.');
            return;
        }

        const clone = template.content.cloneNode(true);
        const block = clone.querySelector('.chapter-block');
        const currentIndex = chapterIndex;
        
        block.dataset.index = currentIndex;
        
        // Set array names
        block.querySelector('.part-title').name = `units[${currentIndex}][title]`;
        block.querySelector('.part-desc').name = `units[${currentIndex}][description]`;
        block.querySelector('.part-youtube-link').name = `units[${currentIndex}][video_url]`;
        block.querySelector('.part-notes-link').name = `units[${currentIndex}][notes_url]`;
        block.querySelector('.part-exam-link').name = `units[${currentIndex}][exam_url]`;

        
        // Create Tab Button
        const tabBtn = document.createElement('button');
        tabBtn.type = 'button';
        tabBtn.className = 'tab-btn w-10 h-10 rounded-lg font-bold transition-all';
        tabBtn.dataset.index = currentIndex;
        tabBtn.onclick = () => switchTab(currentIndex);
        
        tabsContainer.appendChild(tabBtn);
        
        chaptersContainer.appendChild(clone);
        
        updateVisualNumbers();
        
        chapterIndex++;
        switchTab(currentIndex);
    }

    // Initialize with one chapter
    addChapter();

    document.getElementById('add-chapter-btn').addEventListener('click', addChapter);

    // Event delegation for dynamically added chapters (Removing and Previewing)
    chaptersContainer.addEventListener('click', (e) => {
        if (e.target.closest('.remove-chapter')) {
            const blocks = chaptersContainer.querySelectorAll('.chapter-block');
            if (blocks.length > 1) {
                const blockToRemove = e.target.closest('.chapter-block');
                const idxToRemove = parseInt(blockToRemove.dataset.index);
                
                // Find tab to select after removing
                const tabsList = Array.from(tabsContainer.querySelectorAll('.tab-btn'));
                const removedTabIdx = tabsList.findIndex(t => parseInt(t.dataset.index) === idxToRemove);
                
                let nextSelectIdx = null;
                if (removedTabIdx > 0) {
                    nextSelectIdx = parseInt(tabsList[removedTabIdx - 1].dataset.index);
                } else if (tabsList.length > 1) {
                    nextSelectIdx = parseInt(tabsList[1].dataset.index);
                }

                blockToRemove.remove();
                document.querySelector(`.tab-btn[data-index="${idxToRemove}"]`).remove();
                
                updateVisualNumbers();
                
                if (nextSelectIdx !== null) {
                    switchTab(nextSelectIdx);
                }
                
            } else {
                alert("You must have at least one part to upload.");
            }
        }
    });

    chaptersContainer.addEventListener('input', (e) => {
        if (e.target.classList.contains('youtube-input-trigger')) {
            const block = e.target.closest('.chapter-block');
            const previewArea = block.querySelector('.preview-area');
            const iframe = block.querySelector('.preview-iframe');
            const url = e.target.value;

            if (url.includes('youtube.com/') || url.includes('youtu.be/')) {
                let videoId = '';
                if(url.includes('v=')) videoId = url.split('v=')[1].split('&')[0];
                else if(url.includes('youtu.be/')) videoId = url.split('youtu.be/')[1].split('?')[0];

                if(videoId) {
                    iframe.src = `https://www.youtube.com/embed/${videoId}`;
                    previewArea.classList.remove('hidden');
                }
            } else if (url === '') {
                previewArea.classList.add('hidden');
                iframe.src = '';
            }
        }
    });

</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
