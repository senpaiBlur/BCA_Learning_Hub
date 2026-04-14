<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$material_id = $_GET['id'] ?? 0;
$message = '';
$error = '';

if (!$material_id) {
    header("Location: my_uploads.php");
    exit();
}

// Fetch current data
$stmt = $conn->prepare("SELECT m.*, s.title as subject_title, s.category 
                       FROM materials m 
                       JOIN subjects s ON m.subject_id = s.id 
                       WHERE m.id = ? AND m.uploader_id = ?");
$stmt->bind_param("ii", $material_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: my_uploads.php");
    exit();
}

$data = $result->fetch_assoc();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_title = trim($_POST['subject_title']);
    $category = trim($_POST['category']);
    $unit_name = trim($_POST['unit_name']);
    $description = trim($_POST['description']);
    $video_url = trim($_POST['video_url']);
    $notes_url = trim($_POST['notes_url']);
    $exam_url = trim($_POST['exam_url']);

    if (empty($subject_title) || empty($category) || empty($unit_name)) {
        $error = "Subject, Category, and Part Title are required.";
    } else {
        // 1. Update Subject (might affect other materials under same subject)
        $updateSubject = $conn->prepare("UPDATE subjects SET title = ?, category = ? WHERE id = ?");
        $updateSubject->bind_param("ssi", $subject_title, $category, $data['subject_id']);
        $updateSubject->execute();

        // Process Youtube URL
        if (!empty($video_url)) {
            if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $video_url, $matches)) {
                $video_url = 'https://www.youtube.com/embed/' . $matches[1];
            }
        }

        // 2. Update Material
        $updateMat = $conn->prepare("UPDATE materials SET unit_name = ?, description = ?, video_url = ?, notes_url = ?, exam_url = ? WHERE id = ? AND uploader_id = ?");
        $updateMat->bind_param("sssssii", $unit_name, $description, $video_url, $notes_url, $exam_url, $material_id, $user_id);
        
        if ($updateMat->execute()) {
            $message = "Changes saved successfully! Redirecting...";
            echo "<script>setTimeout(() => window.location.href='my_uploads.php', 1500);</script>";
        } else {
            $error = "Failed to update record.";
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
    <title>Edit Content | BCA Learning Hub</title>
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
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen antialiased">

<?php include '../includes/header.php'; ?>

<main class="pt-4 sm:pt-10 pb-12 px-3.5 sm:px-6 lg:px-8 max-w-4xl mx-auto">
    <!-- Header Section -->
    <header class="mb-6 mt-4 sm:mb-10 sm:mt-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mb-1 sm:mb-2">Edit Upload</h1>
            <p class="text-xs sm:text-sm text-slate-500 font-medium">Update your shared learning materials.</p>
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

    <div class="bg-white p-5 sm:p-10 rounded-2xl border border-slate-200 shadow-xl">
        <form class="space-y-8" method="POST" action="">
            <!-- Subject Context -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 p-4 sm:p-6 bg-slate-50 rounded-2xl border border-slate-100">
                <div class="space-y-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500" for="subject_title">Subject Title</label>
                    <input name="subject_title" class="w-full bg-white border-slate-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-primary/20 text-sm font-medium" id="subject_title" type="text" value="<?php echo htmlspecialchars($data['subject_title']); ?>" required />
                </div>
                <div class="space-y-2 relative">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500" for="category">Category</label>
                    <input name="category" class="w-full bg-white border-slate-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-primary/20 text-sm font-medium" id="category" type="text" value="<?php echo htmlspecialchars($data['category']); ?>" required autocomplete="off" />
                    <div id="category-autocomplete-container" class="absolute z-50 w-full left-0 top-full mt-1 bg-white border border-slate-200 rounded-xl shadow-2xl overflow-hidden hidden transform transition-all origin-top scale-95 opacity-0">
                        <ul id="category-autocomplete-list" class="max-h-60 overflow-y-auto divide-y divide-slate-100"></ul>
                    </div>
                </div>
            </div>

            <!-- Material Details -->
            <div class="space-y-6">
                <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">edit_note</span>
                    Content Details
                </h3>

                <div class="space-y-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500" for="unit_name">Part / Lesson Title</label>
                    <input name="unit_name" class="w-full bg-white border-slate-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-primary/20 text-sm" id="unit_name" type="text" value="<?php echo htmlspecialchars($data['unit_name']); ?>" required />
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500" for="description">Description</label>
                    <textarea name="description" class="w-full bg-white border-slate-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-primary/20 text-sm resize-none" id="description" rows="3"><?php echo htmlspecialchars($data['description']); ?></textarea>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500" for="video_url">YouTube Video Link</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 group-focus-within:text-primary">
                            <span class="material-symbols-outlined text-lg">ondemand_video</span>
                        </div>
                        <input name="video_url" class="youtube-input-trigger w-full bg-white border-slate-200 rounded-xl pl-12 pr-4 py-3 focus:ring-2 focus:ring-primary/20 text-sm" id="video_url" type="url" value="<?php echo htmlspecialchars($data['video_url']); ?>" />
                    </div>
                </div>

                <!-- Preview Area -->
                <div id="preview-area" class="<?php echo empty($data['video_url']) ? 'hidden' : ''; ?> space-y-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px]">visibility</span> Preview</label>
                    <div class="relative aspect-video rounded-2xl overflow-hidden bg-slate-900 shadow-inner max-w-md">
                        <iframe id="preview-iframe" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full" frameborder="0" src="<?php echo htmlspecialchars($data['video_url']); ?>"></iframe>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500" for="notes_url">Drive Notes Link</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-emerald-400">
                                <span class="material-symbols-outlined text-[18px]">description</span>
                            </div>
                            <input name="notes_url" class="w-full bg-emerald-50/30 border-emerald-100 rounded-xl pl-10 pr-4 py-3 focus:ring-2 focus:ring-emerald-500/20 text-sm" id="notes_url" type="url" value="<?php echo htmlspecialchars($data['notes_url']); ?>" />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500" for="exam_url">Drive Exam Link</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-orange-400">
                                <span class="material-symbols-outlined text-[18px]">assignment</span>
                            </div>
                            <input name="exam_url" class="w-full bg-orange-50/30 border-orange-100 rounded-xl pl-10 pr-4 py-3 focus:ring-2 focus:ring-orange-500/20 text-sm" id="exam_url" type="url" value="<?php echo htmlspecialchars($data['exam_url']); ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Action -->
            <div class="pt-8 border-t border-slate-100 flex gap-4">
                <button class="flex-1 bg-primary text-white font-black py-4 rounded-xl hover:bg-indigo-700 transition-all shadow-xl shadow-primary/20 flex items-center justify-center gap-3 text-sm uppercase tracking-widest" type="submit">
                    <span class="material-symbols-outlined">save</span>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    // Category Autocomplete Logic
    const categoryOptions = <?php echo json_encode($existing_categories); ?>;
    const catInput = document.getElementById('category');
    const catContainer = document.getElementById('category-autocomplete-container');
    const catList = document.getElementById('category-autocomplete-list');

    function renderCategoryDropdown(filterText = '') {
        catList.innerHTML = '';
        const lowercaseFilter = filterText.toLowerCase();
        let matches = categoryOptions.filter(cat => cat.toLowerCase().includes(lowercaseFilter));
        
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

        if (catList.childElementCount > 0) {
            catContainer.classList.remove('hidden');
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

    // YouTube Preview Logic
    const youtubeInput = document.querySelector('.youtube-input-trigger');
    const previewArea = document.getElementById('preview-area');
    const previewIframe = document.getElementById('preview-iframe');

    youtubeInput.addEventListener('input', (e) => {
        const url = e.target.value;
        if (url.includes('youtube.com/') || url.includes('youtu.be/')) {
            let videoId = '';
            if(url.includes('v=')) videoId = url.split('v=')[1].split('&')[0];
            else if(url.includes('youtu.be/')) videoId = url.split('youtu.be/')[1].split('?')[0];
            else if(url.includes('embed/')) videoId = url.split('embed/')[1].split('?')[0];

            if(videoId) {
                previewIframe.src = `https://www.youtube.com/embed/${videoId}`;
                previewArea.classList.remove('hidden');
            }
        } else if (url === '') {
            previewArea.classList.add('hidden');
            previewIframe.src = '';
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
