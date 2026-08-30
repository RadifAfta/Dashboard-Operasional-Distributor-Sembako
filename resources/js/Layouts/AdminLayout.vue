<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Toast from '@/Components/Admin/Toast.vue';
import CommandPalette from '@/Components/Admin/CommandPalette.vue';
import {
    LayoutDashboard,
    Users,
    Shield,
    Settings,
    Menu,
    X,
    Sun,
    Moon,
    ChevronDown,
    LogOut,
    User as UserIcon,
    Search,
    Sliders,
    Building2,
    CheckCircle2,
    ExternalLink,
    Terminal,
} from 'lucide-vue-next';

const props = defineProps({
    title: {
        type: String,
        default: 'Operations Hub',
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const menuItems = computed(() => page.props.adminMenu || []);
const appSettings = computed(() => page.props.appSettings || { name: 'AdminHub Enterprise' });

// Sidebar state
const sidebarOpen = ref(false);
const sidebarCollapsed = ref(false);
const openSubmenus = ref({});
const showCommandPalette = ref(false);

// Theme State
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

    menuItems.value.forEach((item, index) => {
        if (item.children && isParentActive(item)) {
            openSubmenus.value[index] = true;
        }
    });
});

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

const isRouteActive = (routePattern) => {
    if (!routePattern) return false;
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
    <div class="min-h-screen bg-slate-100/70 text-slate-900 dark:bg-[#090D16] dark:text-slate-100 bg-dot-matrix font-sans antialiased flex flex-col selection:bg-slate-900 selection:text-white dark:selection:bg-slate-100 dark:selection:text-slate-900">
        <!-- Toast Notification Container -->
        <Toast />

        <!-- Interactive Raycast/Linear Command Palette (Ctrl + K) -->
        <CommandPalette
            v-model="showCommandPalette"
            :is-dark="isDark"
            @toggle-theme="toggleTheme"
        />

        <!-- Mobile Backdrop -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-slate-950/70 backdrop-blur-xs lg:hidden transition-opacity"
        ></div>

        <!-- B2B SaaS Minimalist Sidebar -->
        <aside
            :class="[
                'fixed top-0 bottom-0 left-0 z-50 flex flex-col bg-white border-r border-slate-200 dark:bg-[#0D121F] dark:border-slate-800/80 transition-all duration-200 ease-in-out',
                sidebarCollapsed ? 'lg:w-16' : 'lg:w-64',
                sidebarOpen ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0'
            ]"
        >
            <!-- Organization / Workspace Switcher -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-slate-200 dark:border-slate-800/80">
                <div class="flex items-center gap-3 overflow-hidden">
                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-900 text-white dark:bg-white dark:text-slate-950 font-black text-sm tracking-wider shrink-0 shadow-xs">
                        {{ appSettings.name.charAt(0) }}
                    </div>
                    <div v-show="!sidebarCollapsed" class="flex flex-col truncate">
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-xs tracking-tight text-slate-900 dark:text-white truncate">
                                {{ appSettings.name }}
                            </span>
                            <span class="px-1.5 py-0.2 text-[9px] font-mono font-bold uppercase rounded bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800">
                                PROD
                            </span>
                        </div>
                        <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500 truncate">
                            Enterprise Operations
                        </span>
                    </div>
                </div>

                <button
                    @click="sidebarOpen = false"
                    class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 lg:hidden rounded-md"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>

            <!-- Navigation Links -->
            <div class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar">
                <div v-if="!sidebarCollapsed" class="px-2.5 pb-1 text-[10px] font-mono uppercase tracking-widest text-slate-400 dark:text-slate-500 font-semibold">
                    Navigation
                </div>

                <div v-for="(item, index) in menuItems" :key="index">
                    <!-- Standard Menu Item -->
                    <Link
                        v-if="!item.children"
                        :href="route(item.route)"
                        :class="[
                            'flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold transition-colors duration-150',
                            isRouteActive(item.active)
                                ? 'bg-slate-900 text-white dark:bg-slate-800 dark:text-white'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/60'
                        ]"
                    >
                        <component
                            :is="resolveIcon(item.icon)"
                            :class="[
                                'w-4 h-4 shrink-0',
                                isRouteActive(item.active) ? 'text-white' : 'text-slate-400'
                            ]"
                        />
                        <span v-show="!sidebarCollapsed" class="truncate">{{ item.title }}</span>
                        <span
                            v-if="item.badge && !sidebarCollapsed"
                            class="ml-auto px-1.5 py-0.5 text-[10px] font-mono font-bold rounded bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        >
                            {{ item.badge }}
                        </span>
                    </Link>

                    <!-- Submenu Group -->
                    <div v-else class="space-y-0.5">
                        <button
                            type="button"
                            @click="toggleSubmenu(index)"
                            :class="[
                                'w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg text-xs font-semibold transition-colors duration-150',
                                isParentActive(item)
                                    ? 'text-slate-900 font-bold dark:text-white'
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/60'
                            ]"
                        >
                            <div class="flex items-center gap-3 truncate">
                                <component
                                    :is="resolveIcon(item.icon)"
                                    :class="[
                                        'w-4 h-4 shrink-0',
                                        isParentActive(item) ? 'text-slate-900 dark:text-white' : 'text-slate-400'
                                    ]"
                                />
                                <span v-show="!sidebarCollapsed" class="truncate">{{ item.title }}</span>
                            </div>
                            <ChevronDown
                                v-show="!sidebarCollapsed"
                                :class="[
                                    'w-3.5 h-3.5 transition-transform duration-150 shrink-0 text-slate-400',
                                    openSubmenus[index] ? 'rotate-180' : ''
                                ]"
                            />
                        </button>

                        <!-- Children -->
                        <div
                            v-show="openSubmenus[index] && !sidebarCollapsed"
                            class="pl-7 pr-1 py-0.5 space-y-0.5 border-l border-slate-200 dark:border-slate-800 ml-4"
                        >
                            <Link
                                v-for="(child, childIdx) in item.children"
                                :key="childIdx"
                                :href="route(child.route)"
                                :class="[
                                    'flex items-center px-2.5 py-1.5 rounded-md text-[11px] font-medium transition-colors',
                                    isRouteActive(child.active)
                                        ? 'bg-slate-100 text-slate-900 font-bold dark:bg-slate-800/80 dark:text-white'
                                        : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/40'
                                ]"
                            >
                                <span class="truncate">{{ child.title }}</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Footer: System Status & User Info -->
            <div class="p-3 border-t border-slate-200 dark:border-slate-800/80">
                <div class="flex items-center justify-between p-2 rounded-lg border border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/50">
                    <div class="flex items-center gap-2.5 overflow-hidden">
                        <img
                            :src="user?.avatar_url || 'https://ui-avatars.com/api/?name=Admin'"
                            alt="Avatar"
                            class="w-7 h-7 rounded-md object-cover ring-1 ring-slate-300 dark:ring-slate-700 shrink-0"
                        />
                        <div v-show="!sidebarCollapsed" class="flex flex-col truncate">
                            <span class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ user?.name }}</span>
                            <span class="text-[10px] font-mono text-slate-400 truncate">{{ user?.roles?.[0] || 'User' }}</span>
                        </div>
                    </div>

                    <button
                        v-show="!sidebarCollapsed"
                        type="button"
                        @click="logout"
                        title="Sign Out"
                        class="p-1.5 text-slate-400 hover:text-rose-600 rounded-md hover:bg-white dark:hover:bg-slate-800 transition"
                    >
                        <LogOut class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Body Wrapper -->
        <div
            :class="[
                'flex-1 flex flex-col transition-all duration-200 ease-in-out',
                sidebarCollapsed ? 'lg:pl-16' : 'lg:pl-64'
            ]"
        >
            <!-- Top Navbar (Precision Stripe Style) -->
            <header class="sticky top-0 z-30 flex items-center justify-between h-16 px-4 sm:px-8 bg-white/95 backdrop-blur-md border-b border-slate-200 dark:bg-[#0B0F19]/95 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="sidebarOpen = true"
                        class="p-1.5 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white lg:hidden rounded-lg"
                    >
                        <Menu class="w-5 h-5" />
                    </button>

                    <button
                        type="button"
                        @click="sidebarCollapsed = !sidebarCollapsed"
                        class="hidden lg:flex p-1.5 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        title="Toggle Navigation"
                    >
                        <Menu class="w-4 h-4" />
                    </button>

                    <div class="h-4 w-px bg-slate-200 dark:bg-slate-800 hidden sm:block"></div>

                    <!-- Breadcrumb / Section Header -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-mono font-medium text-slate-400 uppercase tracking-wider hidden md:inline">Operations</span>
                        <span class="text-slate-300 dark:text-slate-700 hidden md:inline">/</span>
                        <span class="text-xs font-bold text-slate-900 dark:text-white">{{ title }}</span>
                    </div>
                </div>

                <!-- Right Header Actions -->
                <div class="flex items-center gap-3">
                    <!-- Search Command Palette Indicator -->
                    <button
                        type="button"
                        @click="showCommandPalette = true"
                        class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50/80 dark:bg-slate-900 dark:border-slate-800 text-xs text-slate-400 cursor-pointer hover:border-slate-300 dark:hover:border-slate-700 hover:text-slate-600 dark:hover:text-slate-300 transition group"
                        title="Buka Command Palette (Ctrl + K)"
                    >
                        <Search class="w-3.5 h-3.5 group-hover:text-indigo-600 transition" />
                        <span class="text-[11px]">Cari menu, audit, aksi...</span>
                        <kbd class="px-1.5 py-0.5 text-[10px] font-mono bg-white border border-slate-200 rounded text-slate-500 dark:bg-slate-800 dark:border-slate-700 group-hover:border-indigo-300 transition">Ctrl K</kbd>
                    </button>

                    <!-- Live Cluster Telemetry Capsule -->
                    <div class="hidden lg:flex items-center gap-2 px-2.5 py-1 rounded-md bg-slate-50 border border-slate-200 dark:bg-slate-900/80 dark:border-slate-800 text-[11px] font-mono font-medium">
                        <span class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>24ms</span>
                        </span>
                        <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                        <span class="text-slate-600 dark:text-slate-400 font-semibold">SLA 99.98%</span>
                    </div>

                    <!-- Dark / Light Toggle -->
                    <button
                        type="button"
                        @click="toggleTheme"
                        class="p-2 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        :title="isDark ? 'Mode Terang' : 'Mode Gelap'"
                    >
                        <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
                        <Moon v-else class="w-4 h-4 text-slate-600" />
                    </button>

                    <div class="h-4 w-px bg-slate-200 dark:bg-slate-800"></div>

                    <!-- Profile Dropdown -->
                    <div class="relative">
                        <button
                            type="button"
                            @click="userDropdownOpen = !userDropdownOpen"
                            class="flex items-center gap-2 p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        >
                            <img
                                :src="user?.avatar_url || 'https://ui-avatars.com/api/?name=Admin'"
                                alt="Avatar"
                                class="w-7 h-7 rounded-md object-cover ring-1 ring-slate-300 dark:ring-slate-700"
                            />
                            <ChevronDown class="w-3 h-3 text-slate-400" />
                        </button>

                        <div
                            v-if="userDropdownOpen"
                            @click="userDropdownOpen = false"
                            class="fixed inset-0 z-40"
                        ></div>

                        <div
                            v-if="userDropdownOpen"
                            class="absolute right-0 mt-2 w-52 p-1 bg-white border border-slate-200 rounded-xl shadow-xl dark:bg-[#0D121F] dark:border-slate-800 z-50 text-xs text-slate-700 dark:text-slate-300 animate-in fade-in zoom-in-95 duration-100"
                        >
                            <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-800/80 mb-1">
                                <p class="font-bold text-slate-900 dark:text-white truncate">{{ user?.name }}</p>
                                <p class="text-[11px] font-mono text-slate-400 truncate">{{ user?.email }}</p>
                            </div>

                            <Link
                                :href="route('profile.edit')"
                                class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                            >
                                <UserIcon class="w-3.5 h-3.5 text-slate-400" />
                                <span>Profil Pengguna</span>
                            </Link>

                            <Link
                                :href="route('admin.settings.index')"
                                class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                            >
                                <Settings class="w-3.5 h-3.5 text-slate-400" />
                                <span>Konfigurasi Sistem</span>
                            </Link>

                            <div class="my-1 border-t border-slate-100 dark:border-slate-800/80"></div>

                            <button
                                type="button"
                                @click="logout"
                                class="w-full flex items-center gap-2 px-3 py-2 text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30 rounded-lg transition text-left font-semibold"
                            >
                                <LogOut class="w-3.5 h-3.5" />
                                <span>Keluar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">
                <slot />
            </main>

            <!-- Footer -->
            <footer class="mt-auto py-4 px-8 border-t border-slate-200 dark:border-slate-800/80 text-xs text-slate-400 dark:text-slate-500 font-mono flex flex-col sm:flex-row items-center justify-between gap-2">
                <span>&copy; {{ new Date().getFullYear() }} {{ appSettings.name }} &bull; Enterprise Operations</span>
                <span class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>All services operational</span>
                </span>
            </footer>
        </div>
    </div>
</template>
