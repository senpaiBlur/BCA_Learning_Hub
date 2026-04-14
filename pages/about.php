<?php
session_start();
require_once '../includes/db.php';
include '../includes/header.php';
?>
<main class="max-w-4xl mx-auto px-3.5 py-10 sm:py-16 sm:px-6 lg:px-8">
    <div class="text-center mb-10 sm:mb-16">
        <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight mb-4">About BCA Hub</h1>
        <p class="text-base sm:text-lg text-slate-500 dark:text-slate-400">Empowering BCA students through community-driven education.</p>
    </div>

    <div class="prose prose-slate dark:prose-invert max-w-none space-y-6 sm:space-y-8">
        <section class="bg-white dark:bg-slate-900 p-5 sm:p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm transition-all hover:shadow-md">
            <h2 class="text-2xl font-bold flex items-center gap-3 text-primary">
                <span class="material-symbols-outlined">favorite</span> Our Mission
            </h2>
            <p class="text-slate-600 dark:text-slate-400 leading-relaxed mt-4">
                BCA Hub was born out of a simple idea: that high-quality education should be free and accessible to every student. Our mission is to provide a comprehensive, organized, and interactive learning platform specifically tailored for BCA students.
            </p>
        </section>

        <section class="bg-indigo-50 dark:bg-slate-800/20 p-5 sm:p-8 rounded-2xl border border-indigo-100 dark:border-slate-700 shadow-sm">
            <h2 class="text-2xl font-bold flex items-center gap-3 text-indigo-700 dark:text-indigo-400">
                <span class="material-symbols-outlined">code</span> Student Project & Open Source
            </h2>
            <p class="text-slate-600 dark:text-slate-400 leading-relaxed mt-4">
                This platform is a student-driven initiative. We believe in the power of community and collaboration. That's why BCA Hub is completely free to use and our code is open-source.
            </p>
            <div class="mt-8 flex items-center gap-4">
                <a href="https://github.com/senpaiBlur/BCA_Learning_Hub" target="_blank" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-slate-900 text-white px-6 py-3.5 rounded-xl font-bold text-sm hover:bg-slate-800 transition-all shadow-xl shadow-slate-900/20">
                    <span class="material-symbols-outlined text-lg">terminal</span> View on GitHub
                </a>
            </div>
        </section>

        <section class="bg-white dark:bg-slate-900 p-5 sm:p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm transition-all hover:shadow-md">
            <h2 class="text-2xl font-bold flex items-center gap-3 text-emerald-600">
                <span class="material-symbols-outlined">verified</span> Platform Features
            </h2>
            <ul class="grid md:grid-cols-2 gap-4 mt-6 list-none p-0">
                <li class="flex items-center gap-3 text-slate-600 dark:text-slate-400">
                    <span class="material-symbols-outlined text-emerald-500">check_circle</span>
                    Curated Video Lectures
                </li>
                <li class="flex items-center gap-3 text-slate-600 dark:text-slate-400">
                    <span class="material-symbols-outlined text-emerald-500">check_circle</span>
                    Free Study Notes
                </li>
                <li class="flex items-center gap-3 text-slate-600 dark:text-slate-400">
                    <span class="material-symbols-outlined text-emerald-500">check_circle</span>
                    Previous Year Papers
                </li>
                <li class="flex items-center gap-3 text-slate-600 dark:text-slate-400">
                    <span class="material-symbols-outlined text-emerald-500">check_circle</span>
                    AI-Powered Tutor
                </li>
            </ul>
        </section>
    </div>
</main>
<?php include '../includes/footer.php'; ?>
