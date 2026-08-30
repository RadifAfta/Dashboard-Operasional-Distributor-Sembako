<script setup>
import { ref, onMounted, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Toast from '@/Components/Admin/Toast.vue';
import {
    LayoutDashboard,
    Users,
    Shield,
    Settings,
    Menu,
    X,
    Sun,
    Moon,
    Bell,
    ChevronDown,
    ChevronRight,
    LogOut,
    User as UserIcon,
    Search,
    Sliders,
    Sparkles,
} from 'lucide-vue-next';

const props = defineProps({
    title: {
        type: String,
        default: 'Dashboard',
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const menuItems = computed(() => page.props.adminMenu || []);
const appSettings = computed(() => page.props.appSettings || { name: 'AdminHub' });

// Sidebar state
const sidebarOpen = ref(false); // mobile
const sidebarCollapsed = ref(false); // desktop
const openSubmenus = ref({});

// Theme State (Dark / Light)
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
    if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    } else {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
    }

    // Auto expand active submenu
    menuItems.value.forEach((item, index) => {
        if (item.children && isParentActive(item)) {
            openSubmenus.value[index] = true;
        }
    });
});

// Icon Resolver map
const iconMap = {
    LayoutDashboard,
    Users,
    Shield,
    Settings,
    Sliders,
};

const resolveIcon = (iconName) => {
    return iconMap[iconName] || LayoutDashboard;
};

// Check if route is active
const isRouteActive = (routePattern) => {
    if (!routePattern) return false;
    // Basic route matching
    const currentRoute = route().current();
    if (!currentRoute) return false;

    if (routePattern.endsWith('*')) {
        const prefix = routePattern.replace('*', '');
        return currentRoute.startsWith(prefix);
    }
    return currentRoute === routePattern;
};

const isParentActive = (item) => {
    if (item.active && isRouteActive(item.active)) return true;
    if (item.children) {
        return item.children.some((child) => child.active && isRouteActive(child.active));
    }
    return false;
};

const toggleSubmenu = (index) => {
    openSubmenus.value[index] = !openSubmenus.value[index];
};

const userDropdownOpen = ref(false);

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 font-sans antialiased flex flex-col">
        <!-- Toast Notification Container -->
        <Toast />

        <!-- Mobile Sidebar Backdrop -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden transition-opacity"
        ></div>

        <!-- Sidebar (Desktop & Mobile) -->
        <aside
            :class="[
                'fixed top-0 bottom-0 left-0 z-50 flex flex-col bg-white border-r border-slate-200 dark:bg-slate-900 dark:border-slate-800 transition-all duration-300 ease-in-out',
                sidebarCollapsed ? 'lg:w-20' : 'lg:w-64',
                sidebarOpen ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0'
            ]"
        >
            <!-- Sidebar Header / Logo -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-slate-200 dark:border-slate-800">
                <Link :href="route('admin.dashboard')" class="flex items-center gap-3 overflow-hidden">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white shadow-md shadow-indigo-500/20 shrink-0">
                        <Sparkles class="w-5 h-5" />
                    </div>
                    <div v-show="!sidebarCollapsed" class="flex flex-col truncate">
                        <span class="font-bold text-base tracking-tight text-slate-900 dark:text-white leading-tight">
                            {{ appSettings.name }}
                        </span>
                        <span class="text-[10px] uppercase font-bold tracking-widest text-indigo-600 dark:text-indigo-400">
                            Admin Panel
                        </span>
                    </div>
                </Link>

                <!-- Mobile Close Button -->
                <button
                    @click="sidebarOpen = false"
                    class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 lg:hidden rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Navigation Links -->
            <div class="flex-1 px-3 py-4 space-y-1.5 overflow-y-auto custom-scrollbar">
                <div v-for="(item, index) in menuItems" :key="index">
                    <!-- Standard Menu Item (Single Link) -->
                    <Link
                        v-if="!item.children"
                        :href="route(item.route)"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group',
                            isRouteActive(item.active)
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25 dark:bg-indigo-600 dark:text-white'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/60'
                        ]"
                    >
                        <component :is="resolveIcon(item.icon)" class="w-5 h-5 shrink-0" />
                        <span v-show="!sidebarCollapsed" class="truncate">{{ item.title }}</span>
                        <span
                            v-if="item.badge && !sidebarCollapsed"
                            class="ml-auto px-2 py-0.5 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                        >
                            {{ item.badge }}
                        </span>
                    </Link>

                    <!-- Accordion Submenu Item -->
                    <div v-else class="space-y-1">
                        <button
                            type="button"
                            @click="toggleSubmenu(index)"
                            :class="[
                                'w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group',
                                isParentActive(item)
                                    ? 'text-indigo-600 bg-indigo-50/70 font-semibold dark:bg-indigo-950/30 dark:text-indigo-400'
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/60'
                            ]"
                        >
                            <div class="flex items-center gap-3 truncate">
                                <component :is="resolveIcon(item.icon)" class="w-5 h-5 shrink-0" />
                                <span v-show="!sidebarCollapsed" class="truncate">{{ item.title }}</span>
                            </div>
                            <ChevronDown
                                v-show="!sidebarCollapsed"
                                :class="[
                                    'w-4 h-4 transition-transform duration-200 shrink-0',
                                    openSubmenus[index] ? 'rotate-180 text-indigo-600 dark:text-indigo-400' : 'text-slate-400'
                                ]"
                            />
                        </button>

                        <!-- Children Links -->
                        <div
                            v-show="openSubmenus[index] && !sidebarCollapsed"
                            class="pl-9 pr-1 py-1 space-y-1 transition-all"
                        >
                            <Link
                                v-for="(child, childIdx) in item.children"
                                :key="childIdx"
                                :href="route(child.route)"
                                :class="[
                                    'flex items-center px-3 py-2 rounded-lg text-xs font-medium transition-all',
                                    isRouteActive(child.active)
                                        ? 'bg-indigo-600 text-white font-semibold shadow-sm'
                                        : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/60 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/40'
                                ]"
                            >
                                <span class="truncate">{{ child.title }}</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Footer User Profile Card -->
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                <div
                    class="flex items-center gap-3 p-2 rounded-xl bg-slate-50 border border-slate-100 dark:bg-slate-800/40 dark:border-slate-800/60"
                >
                    <img
                        :src="user?.avatar_url || 'https://ui-avatars.com/api/?name=Admin'"
                        alt="Avatar"
                        class="w-9 h-9 rounded-lg object-cover ring-2 ring-indigo-500/20 shrink-0"
                    />
                    <div v-show="!sidebarCollapsed" class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
                            {{ user?.name }}
                        </p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                            {{ user?.roles?.[0] || 'User' }}
                        </p>
                    </div>
                    <button
                        v-show="!sidebarCollapsed"
                        type="button"
                        @click="logout"
                        title="Keluar"
                        class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-white dark:hover:bg-slate-800 transition"
                    >
                        <LogOut class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div
            :class="[
                'flex-1 flex flex-col transition-all duration-300 ease-in-out',
                sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64'
            ]"
        >
            <!-- Top Navigation Header -->
            <header class="sticky top-0 z-30 flex items-center justify-between h-16 px-4 sm:px-6 bg-white/80 backdrop-blur-md border-b border-slate-200 dark:bg-slate-900/80 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <!-- Mobile Hamburger -->
                    <button
                        type="button"
                        @click="sidebarOpen = true"
                        class="p-2 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white lg:hidden rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800"
                    >
                        <Menu class="w-5 h-5" />
                    </button>

                    <!-- Desktop Sidebar Collapse Toggle -->
                    <button
                        type="button"
                        @click="sidebarCollapsed = !sidebarCollapsed"
                        class="hidden lg:flex p-2 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        title="Toggle Sidebar"
                    >
                        <Menu class="w-5 h-5" />
                    </button>

                    <!-- Page Title / Breadcrumb -->
                    <div class="flex items-center gap-2">
                        <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight">
                            {{ title }}
                        </h1>
                    </div>
                </div>

                <!-- Right Header Actions -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Dark/Light Mode Switch -->
                    <button
                        type="button"
                        @click="toggleTheme"
                        class="p-2 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        :title="isDark ? 'Mode Terang' : 'Mode Gelap'"
                    >
                        <Sun v-if="isDark" class="w-5 h-5 text-amber-400" />
                        <Moon v-else class="w-5 h-5" />
                    </button>

                    <!-- User Dropdown Menu -->
                    <div class="relative">
                        <button
                            type="button"
                            @click="userDropdownOpen = !userDropdownOpen"
                            class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        >
                            <img
                                :src="user?.avatar_url || 'https://ui-avatars.com/api/?name=Admin'"
                                alt="Avatar"
                                class="w-8 h-8 rounded-lg object-cover ring-2 ring-indigo-500/20"
                            />
                            <span class="hidden md:inline text-xs font-semibold text-slate-700 dark:text-slate-300">
                                {{ user?.name }}
                            </span>
                            <ChevronDown class="w-3.5 h-3.5 text-slate-400 hidden md:inline" />
                        </button>

                        <!-- Dropdown Menu Box -->
                        <div
                            v-if="userDropdownOpen"
                            @click="userDropdownOpen = false"
                            class="fixed inset-0 z-40"
                        ></div>

                        <div
                            v-if="userDropdownOpen"
                            class="absolute right-0 mt-2 w-52 py-1.5 bg-white border border-slate-200 rounded-2xl shadow-xl dark:bg-slate-900 dark:border-slate-800 z-50 text-xs text-slate-700 dark:text-slate-300 animate-in fade-in zoom-in-95 duration-100"
                        >
                            <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-800">
                                <p class="font-bold text-slate-900 dark:text-white truncate">{{ user?.name }}</p>
                                <p class="text-slate-400 truncate">{{ user?.email }}</p>
                            </div>

                            <Link
                                :href="route('profile.edit')"
                                class="flex items-center gap-2.5 px-4 py-2.5 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                            >
                                <UserIcon class="w-4 h-4 text-slate-400" />
                                <span>Profil Saya</span>
                            </Link>

                            <Link
                                :href="route('admin.settings.index')"
                                class="flex items-center gap-2.5 px-4 py-2.5 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                            >
                                <Settings class="w-4 h-4 text-slate-400" />
                                <span>Pengaturan Sistem</span>
                            </Link>

                            <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>

                            <button
                                type="button"
                                @click="logout"
                                class="w-full flex items-center gap-2.5 px-4 py-2.5 text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30 transition text-left"
                            >
                                <LogOut class="w-4 h-4" />
                                <span>Keluar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                <slot />
            </main>

            <!-- Footer -->
            <footer class="mt-auto py-4 px-6 border-t border-slate-200/80 text-center text-xs text-slate-400 dark:border-slate-800/80 dark:text-slate-500">
                &copy; {{ new Date().getFullYear() }} {{ appSettings.name }} &bull; Next-Gen Laravel Starter Kit
            </footer>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.3);
    border-radius: 4px;
}
</style>
