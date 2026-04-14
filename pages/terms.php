<?php
session_start();
require_once '../includes/db.php';
include '../includes/header.php';
?>
<main class="max-w-4xl mx-auto px-4 py-8 sm:py-16 sm:px-6 lg:px-8">
    <div class="mb-8 sm:mb-12">
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight mb-2">Terms of Service</h1>
        <p class="text-xs sm:text-sm text-slate-500 font-medium">Effective Date: April 14, 2026</p>
    </div>

    <div class="space-y-12">
        <section>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">1. Acceptance of Terms</h2>
            <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                By accessing BCA Hub, you agree to follow these terms. This platform is provided "as is" for educational purposes only.
            </p>
        </section>

        <section>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">2. Educational Use Only</h2>
            <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                The content shared on this platform is intended for learning. Users are prohibited from using the platform for any commercial purpose or sharing illegal, copyrighted, or inappropriate material.
            </p>
        </section>

        <section>
            <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white mb-4">3. User Responsibilities</h2>
            <div class="p-5 sm:p-6 rounded-2xl border border-amber-200 bg-amber-50 dark:bg-amber-900/10 dark:border-amber-900/30">
                <p class="text-amber-800 dark:text-amber-400 leading-relaxed text-sm sm:text-base font-medium">
                    As a contributor, you are solely responsible for the links you share. Ensure that your Google Drive or YouTube content does not violate any copyright laws. We reserve the right to remove any content reported or found to be violating these terms.
                </p>
            </div>
        </section>

        <section>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">4. Limitation of Liability</h2>
            <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                BCA Hub and its creators are not liable for any technical failures, data loss, or inaccuracies in the educational content provided by users.
            </p>
        </section>

        <section>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">5. Modifications</h2>
            <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                We reserve the right to modify these terms at any time. Continued use of the platform after changes implies agreement to the updated terms.
            </p>
        </section>
    </div>
</main>
<?php include '../includes/footer.php'; ?>
