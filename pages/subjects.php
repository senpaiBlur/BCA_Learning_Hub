<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Always fetch all subjects to allow real-time JS filtering across all categories
// Fetch subjects with first material uploader and count of types
$result = $conn->query("
    SELECT s.*, 
    (SELECT u.name FROM materials m JOIN users u ON m.uploader_id = u.id WHERE m.subject_id = s.id LIMIT 1) as owner_name,
    (SELECT m.created_at FROM materials m WHERE m.subject_id = s.id ORDER BY m.id DESC LIMIT 1) as last_updated,
    (SELECT COUNT(*) FROM materials m WHERE m.subject_id = s.id AND m.video_url != '') as video_count,
    (SELECT COUNT(*) FROM materials m WHERE m.subject_id = s.id AND m.notes_url != '') as notes_count,
    (SELECT COUNT(*) FROM materials m WHERE m.subject_id = s.id AND m.exam_url != '') as exam_count
    FROM subjects s 
    ORDER BY id DESC
");

include '../includes/header.php';
?>
<style>
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
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-6 sm:mb-10 text-center sm:text-left mt-4 sm:mt-8">
        <h1 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">Explore Subjects</h1>
        <p class="text-xs sm:text-base text-slate-500 dark:text-slate-400 mt-1.5 font-medium">Access comprehensive study materials for your BCA journey.</p>
</div>

    <!-- Advance Search & Filter Section -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 sm:p-4 mb-8 sm:mb-12 shadow-sm">
        <div class="flex flex-col md:flex-row items-stretch md:items-center gap-3 sm:gap-4">
            <!-- Search Bar -->
            <div class="relative flex-grow">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
                <input type="text" id="subjectSearch" placeholder="Search by name, semester..." 
                    class="w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-primary/20 transition-all shadow-inner" />
            </div>
            
            <!-- Quick Filters -->
            <div class="flex items-center gap-3 bg-slate-50 dark:bg-slate-800 rounded-xl px-4 py-1.5 border border-transparent focus-within:ring-2 focus-within:ring-primary/20 transition-all h-full">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Sort:</span>
                <select id="sortFilter" class="bg-transparent border-none text-xs font-bold py-2 pr-8 pl-0 focus:ring-0 cursor-pointer text-slate-700 dark:text-slate-300">
                    <option value="newest">Newest First</option>
                    <option value="az">A - Z</option>
                    <option value="za">Z - A</option>
                </select>
            </div>
        </div>

        <!-- Category Scrollable Chips -->
        <?php
        $cat_q = $conn->query("SELECT DISTINCT category FROM subjects WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
        $all_cats = [];
        while($cr = $cat_q->fetch_assoc()) $all_cats[] = $cr['category'];
        ?>
        <div class="mt-4 flex items-center gap-3">
            <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest whitespace-nowrap">Filter:</span>
            <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-none no-scrollbar" id="categoryChips">
                <button onclick="filterByCategory('all', this)" class="category-chip active-chip px-4 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition-all border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50">All Items</button>
                <?php foreach($all_cats as $cat): ?>
                <button onclick="filterByCategory('<?php echo htmlspecialchars($cat); ?>', this)" 
                    class="category-chip px-4 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition-all border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50">
                    <?php echo htmlspecialchars($cat); ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Subjects Grid -->
    <?php if($result && $result->num_rows > 0): ?>
    <div class="max-h-[720px] overflow-y-auto pr-2 sm:pr-4 custom-scrollbar">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 sm:gap-8" id="subjectsGrid">
            <?php while($row = $result->fetch_assoc()): ?>
            <!-- Card -->
            <div class="subject-card group flex flex-col bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 ease-out" 
                 data-title="<?php echo strtolower(htmlspecialchars($row['title'])); ?>" 
                 data-category="<?php echo strtolower(htmlspecialchars($row['category'])); ?>">
                <div class="relative h-40 sm:h-48 w-full overflow-hidden">
                    <img class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110" src="<?php echo htmlspecialchars($row['thumbnail'] ? $row['thumbnail'] : 'https://placehold.co/600x400/5048e5/ffffff?text='.urlencode($row['title'])); ?>" />
                    <div class="absolute top-3 left-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black tracking-widest uppercase bg-white/90 text-slate-900 shadow-sm backdrop-blur-md"><?php echo htmlspecialchars($row['category']); ?></span>
                    </div>
                    <?php if($row['video_count'] > 0 || $row['notes_count'] > 0 || $row['exam_count'] > 0): ?>
                    <div class="absolute bottom-3 right-3 flex gap-1.5">
                        <?php if($row['video_count'] > 0): ?>
                            <span class="w-7 h-7 flex items-center justify-center rounded-lg bg-white/90 text-red-600 shadow-sm backdrop-blur-md" title="Videos Available"><span class="material-symbols-outlined text-[16px]">play_circle</span></span>
                        <?php endif; ?>
                        <?php if($row['notes_count'] > 0): ?>
                            <span class="w-7 h-7 flex items-center justify-center rounded-lg bg-white/90 text-emerald-600 shadow-sm backdrop-blur-md" title="Notes Available"><span class="material-symbols-outlined text-[16px]">description</span></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="p-4 sm:p-6 flex flex-col flex-1">
                    <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white group-hover:text-primary transition-colors line-clamp-1"><?php echo htmlspecialchars($row['title']); ?></h3>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[12px]">person</span>
                            <?php echo htmlspecialchars($row['owner_name'] ?? 'Academic'); ?>
                        </span>
                        <span class="w-0.5 h-0.5 rounded-full bg-slate-200 dark:bg-slate-700"></span>
                        <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">
                            <?php echo date('M Y', strtotime($row['last_updated'] ?? 'now')); ?>
                        </span>
                    </div>
                    <p class="mt-3 text-xs sm:text-sm text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed font-medium"><?php echo htmlspecialchars($row['description']); ?></p>
                    <div class="mt-auto pt-4 sm:pt-6">
                        <a href="subject_details.php?id=<?php echo $row['id']; ?>" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 sm:py-3 text-xs sm:text-sm font-bold text-white hover:bg-primary/95 transition-all active:scale-[0.98] shadow-lg shadow-primary/20 group/btn">
                            View Subject
                            <span class="material-symbols-outlined text-[16px] group-hover/btn:translate-x-1 transition-transform">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    
    <!-- Enhanced Empty Search State -->
    <div id="noResults" class="hidden text-center py-32 bg-white dark:bg-slate-900/50 rounded-3xl border-2 border-dashed border-slate-100 dark:border-slate-800">
        <div class="w-24 h-24 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6">
            <span class="material-symbols-outlined text-5xl text-slate-300">search_off</span>
        </div>
        <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">No matching subjects</h3>
        <p class="text-slate-500 dark:text-slate-400 mt-2 max-w-sm mx-auto font-medium">We couldn't find any courses matching your search. Try adjusting your filters or search terms.</p>
        <button onclick="resetFilters()" class="mt-8 px-8 py-3 bg-primary text-white font-bold rounded-xl shadow-xl shadow-primary/20 hover:bg-primary/95 transition-all active:scale-95">
            Reset All Filters
        </button>
    </div>

    <?php else: ?>
    <div class="text-center py-20">
        <h3 class="text-xl font-medium text-slate-500">No subjects found for this category.</h3>
    </div>
    <?php endif; ?>

</main>

<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .active-chip { background-color: #4F46E5 !important; border-color: #4F46E5 !important; color: white !important; }
</style>

<script>
    const searchInput = document.getElementById('subjectSearch');
    const sortFilter = document.getElementById('sortFilter');
    const cards = document.querySelectorAll('.subject-card');
    const grid = document.getElementById('subjectsGrid');
    const noResults = document.getElementById('noResults');
    let currentCategory = 'all';

    function filterSubjects() {
        const searchTerm = searchInput.value.toLowerCase();
        let visibleCount = 0;

        cards.forEach(card => {
            const title = card.getAttribute('data-title');
            const category = card.getAttribute('data-category');
            
            const matchesSearch = title.includes(searchTerm) || category.includes(searchTerm);
            const matchesCategory = currentCategory === 'all' || category === currentCategory.toLowerCase();

            if (matchesSearch && matchesCategory) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        if (visibleCount === 0) {
            grid.classList.add('hidden');
            noResults.classList.remove('hidden');
        } else {
            grid.classList.remove('hidden');
            noResults.classList.add('hidden');
        }
    }

    function filterByCategory(category, btn) {
        currentCategory = category;
        document.querySelectorAll('.category-chip').forEach(c => c.classList.remove('active-chip'));
        
        // If btn is not provided (e.g. from URL), find the button by category text
        if (!btn) {
            const chips = document.querySelectorAll('.category-chip');
            chips.forEach(chip => {
                if (chip.textContent.trim().toLowerCase() === category.toLowerCase()) {
                    btn = chip;
                }
            });
            // Fallback for 'all' or if not found
            if (!btn && (category === 'all' || category === '')) btn = chips[0];
        }

        if (btn) btn.classList.add('active-chip');
        filterSubjects();
    }

    // Auto-filter based on URL parameter when page loads
    window.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const urlCat = urlParams.get('category');
        if (urlCat) {
            filterByCategory(urlCat, null);
        }
    });

    function resetFilters() {
        searchInput.value = '';
        currentCategory = 'all';
        document.querySelectorAll('.category-chip').forEach(c => c.classList.remove('active-chip'));
        document.querySelector('.category-chip').classList.add('active-chip');
        filterSubjects();
    }

    searchInput.addEventListener('input', filterSubjects);
    
    sortFilter.addEventListener('change', () => {
        const value = sortFilter.value;
        const cardArray = Array.from(cards);
        
        cardArray.sort((a, b) => {
            const titleA = a.querySelector('h3').textContent.toLowerCase();
            const titleB = b.querySelector('h3').textContent.toLowerCase();
            
            if(value === 'az') return titleA.localeCompare(titleB);
            if(value === 'za') return titleB.localeCompare(titleA);
            return 0; // 'newest' is default by initial order
        });
        
        cardArray.forEach(card => grid.appendChild(card));
    });
</script>

<?php include '../includes/footer.php'; ?>

