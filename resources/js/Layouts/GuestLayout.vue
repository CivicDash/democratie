<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gradient-to-br from-slate-50 via-white to-indigo-50 pt-6 sm:justify-center sm:pt-0 dark:from-gray-900 dark:via-gray-900 dark:to-slate-900"
    >
        <div class="mb-2">
            <Link href="/" class="flex flex-col items-center gap-2 group">
                <img 
                    src="/images/logo.png" 
                    alt="CivicDash" 
                    class="h-16 w-auto transition-transform group-hover:scale-105" 
                />
                <span class="text-xl font-bold text-gray-800 dark:text-white">
                    CivicDash
                </span>
            </Link>
        </div>

        <div
            class="mt-4 w-full overflow-hidden bg-white px-6 py-4 shadow-lg sm:max-w-md sm:rounded-xl dark:bg-gray-800 border border-gray-100 dark:border-gray-700"
        >
            <slot />
        </div>
        
        <!-- Footer léger -->
        <div class="mt-6 text-center text-xs text-gray-500 dark:text-gray-400">
            <p>
                © {{ new Date().getFullYear() }} 
                <a href="https://civis-consilium.eu" target="_blank" class="hover:text-indigo-600 transition">
                    Association Civis-Consilium
                </a>
            </p>
        </div>
    </div>
</template>
