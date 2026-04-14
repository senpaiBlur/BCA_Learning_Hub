<?php
session_start();
require_once '../includes/db.php';
include '../includes/header.php';
?>
<main class="max-w-4xl mx-auto px-4 py-8 sm:py-16 sm:px-6 lg:px-8">
    <div class="mb-8 sm:mb-12">
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight mb-2">Privacy Policy</h1>
        <p class="text-xs sm:text-sm text-slate-500 font-medium">Last updated: April 14, 2026</p>
    </div>

    <div class="space-y-12">
        <section>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">1. Data Collection</h2>
            <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                We only collect basic information necessary for you to use the platform, such as your name, email address, and progress data. We do not use trackers or sell your personal data to third parties.
            </p>
        </section>

        <section>
            <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white mb-4">2. AI Tutor Privacy</h2>
            <div class="bg-indigo-50 dark:bg-slate-800/20 p-5 sm:p-6 rounded-2xl border border-indigo-100 dark:border-slate-700">
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed text-sm sm:text-base">
                    Important: Your conversations with the <strong>AI Tutor</strong> are processed by OpenRouter (Llama model) but are stored <strong>only on your local device</strong> (localStorage). We do not store your chat history on our servers. Clearing your browser cache or clicking "Clear Chat" will permanently delete this history.
                </p>
            </div>
        </section>

        <section>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">3. External Links</h2>
            <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                Our platform contains links to external sites like YouTube and Google Drive. We are not responsible for the privacy practices or content of these third-party platforms.
            </p>
        </section>

        <section>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">4. Content Ownership</h2>
            <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                Users who upload content are responsible for ensuring they have the rights to share that content. All metadata (titles, descriptions) provided is stored in our database to facilitate the learning experience.
            </p>
        </section>
    </div>
</main>
<?php include '../includes/footer.php'; ?>
