<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import { applyBrandTheme } from '@/Utils/brandTheme';
import { Sun, Moon } from 'lucide-vue-next';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const page = usePage();
const appSettings = computed(() => page.props.appSettings || { name: 'AdminHub', brand_color: 'indigo' });

// Dark / Light Theme Management
const isDark = ref(false);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

onMounted(() => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    } else {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
    }

    // Apply company branding theme dynamically
    applyBrandTheme(appSettings.value?.brand_color || 'indigo');
});

watch(
    () => appSettings.value?.brand_color,
    (newColor) => {
        applyBrandTheme(newColor || 'indigo');
    }
);
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-[#09090B] text-slate-900 dark:text-zinc-100 flex flex-col justify-between selection:bg-brand selection:text-white transition-colors duration-200">
        <!-- Floating Top Bar for Theme Toggle -->
        <header class="w-full flex items-center justify-end p-4 sm:p-6 max-w-7xl mx-auto">
            <!-- Light / Dark Mode Toggle Button -->
            <button
                type="button"
                @click="toggleTheme"
                class="p-2 rounded-xl bg-white dark:bg-[#18181B] border border-slate-200 dark:border-zinc-800 text-slate-600 dark:text-zinc-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800 shadow-2xs transition flex items-center gap-1.5 text-xs font-medium cursor-pointer"
                :title="isDark ? 'Ganti ke Mode Terang' : 'Ganti ke Mode Gelap'"
            >
                <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
                <Moon v-else class="w-4 h-4 text-slate-600" />
                <span class="hidden sm:inline text-[11px]">{{ isDark ? 'Terang' : 'Gelap' }}</span>
            </button>
        </header>

        <!-- Main Auth Content Area -->
        <main class="flex-1 flex items-center justify-center p-4 sm:p-6">
            <slot />
        </main>

        <!-- Minimal Trust & Security Footer -->
        <footer class="py-4 text-center text-xs font-mono text-slate-400 dark:text-zinc-500 border-t border-slate-200/60 dark:border-zinc-800/60">
            <span>&copy; {{ new Date().getFullYear() }} {{ appSettings.name }} &bull; Protected by enterprise session encryption</span>
        </footer>
    </div>
</template>
