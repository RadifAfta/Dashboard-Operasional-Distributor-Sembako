<script setup>
import { ref, onMounted, computed } from 'vue';
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
    Package,
    Folder,
    Tag,
    ShoppingBag,
    Layers,
    Briefcase,
    FileText,
    Database,
    Calendar,
    Boxes,
} from 'lucide-vue-next';

import { applyBrandTheme } from '@/Utils/brandTheme';
import { watch } from 'vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Operations Hub',
    },
    description: {
        type: String,
        default: '',
    },
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const menuItems = computed(() => page.props.adminMenu || []);
const appSettings = computed(() => page.props.appSettings || { name: 'AdminHub Enterprise', brand_color: 'indigo' });

// Watch brand color setting and apply theme
watch(
    () => appSettings.value?.brand_color,
    (newColor) => {
        applyBrandTheme(newColor || 'indigo');
    },
    { immediate: true }
);

// Dynamic Breadcrumb Resolver based on adminMenu groups and active routes
const resolvedBreadcrumbs = computed(() => {
    if (props.breadcrumbs && props.breadcrumbs.length > 0) {
        return props.breadcrumbs;
    }

    const crumbs = [];

    for (const item of menuItems.value) {
        if (!item.children) {
            if (isRouteActive(item.active)) {
                crumbs.push({
                    title: item.title,
                    url: item.route ? route(item.route) : null,
                    isCurrent: true,
                });
                return crumbs;
            }
        } else {
            const activeChild = item.children.find((child) => child.active && isRouteActive(child.active));
            if (activeChild) {
                crumbs.push({
                    title: item.title,
                    url: null,
                    isCurrent: false,
                });
                crumbs.push({
                    title: activeChild.title,
                    url: activeChild.route ? route(activeChild.route) : null,
                    isCurrent: true,
                });
                return crumbs;
            }
        }
    }

    if (props.title) {
        crumbs.push({
            title: props.title,
            url: null,
            isCurrent: true,
        });
    }

    return crumbs;
});

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
    Package,
    Folder,
    Tag,
    ShoppingBag,
    Layers,
    Briefcase,
    FileText,
    Database,
    Calendar,
    Boxes,
    Building2,
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
    <!-- Outer Viewport Ambient Background Canvas with Studio Frame Inset -->
    <div class="h-screen w-screen overflow-hidden ambient-canvas p-2 sm:p-3 lg:p-3.5 font-sans antialiased text-slate-900 dark:text-slate-100 selection:bg-indigo-600 selection:text-white flex flex-col">
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
            class="fixed inset-0 z-40 bg-black/75 backdrop-blur-xs lg:hidden transition-opacity"
        ></div>

        <!-- 🖥️ KONSEP 3: The Unified Studio App Window Shell -->
        <div class="flex-1 w-full h-full rounded-2xl border border-slate-300 dark:border-zinc-800 shadow-2xl overflow-hidden flex flex-row bg-white dark:bg-[#0C0C0E] relative">
            
            <!-- Left Studio Pane: Sidebar (Seamless Light & Dark Adaptation) -->
            <aside
                :class="[
                    'flex flex-col bg-slate-50 text-slate-800 border-r border-slate-200/90 dark:bg-[#121215] dark:text-white dark:border-zinc-800/80 h-full transition-all duration-200 ease-in-out shrink-0 z-30',
                    sidebarCollapsed ? 'w-16' : 'w-60',
                    sidebarOpen ? 'fixed inset-y-0 left-0 z-50 w-64 shadow-2xl' : 'hidden lg:flex'
                ]"
            >
                <!-- Workspace Header -->
                <div class="flex items-center justify-between h-14 px-3.5 border-b border-slate-200 dark:border-slate-800/80">
                    <div class="flex items-center gap-2.5 overflow-hidden">
                        <div class="flex items-center justify-center w-7 h-7 rounded-lg bg-brand text-white font-black text-xs tracking-wider shrink-0 shadow-sm">
                            {{ appSettings.name.charAt(0) }}
                        </div>
                        <div v-show="!sidebarCollapsed" class="flex flex-col truncate">
                            <span class="font-bold text-xs tracking-tight text-slate-900 dark:text-white truncate">
                                {{ appSettings.name }}
                            </span>
                            <span class="text-[10px] font-mono text-slate-400 dark:text-slate-400 truncate">
                                Enterprise Console
                            </span>
                        </div>
                    </div>

                    <button
                        @click="sidebarOpen = false"
                        class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-white lg:hidden rounded-md"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Navigation Links -->
                <div class="flex-1 px-2.5 py-3 space-y-1 overflow-y-auto custom-scrollbar">
                    <div v-if="!sidebarCollapsed" class="px-2 pb-1 text-[10px] font-mono uppercase tracking-widest text-slate-400 dark:text-slate-500 font-semibold">
                        Workspace
                    </div>

                    <div v-for="(item, index) in menuItems" :key="index">
                        <!-- Standard Menu Item -->
                        <Link
                            v-if="!item.children"
                            :href="route(item.route)"
                            :class="[
                                'flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-semibold transition-colors duration-150',
                                isRouteActive(item.active)
                                    ? 'bg-brand text-white shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/60'
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
                                class="ml-auto px-1.5 py-0.5 text-[10px] font-mono font-bold rounded bg-slate-200 text-slate-700 dark:bg-zinc-800 dark:text-zinc-300"
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
                                    'w-full flex items-center justify-between gap-2.5 px-2.5 py-2 rounded-lg text-xs font-semibold transition-colors duration-150',
                                    isParentActive(item)
                                        ? 'text-slate-900 dark:text-white font-bold bg-slate-200/40 dark:bg-transparent'
                                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/60'
                                ]"
                            >
                                <div class="flex items-center gap-2.5 truncate">
                                    <component
                                        :is="resolveIcon(item.icon)"
                                        :class="[
                                            'w-4 h-4 shrink-0',
                                            isParentActive(item) ? 'text-brand' : 'text-slate-400'
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
                                class="pl-6 pr-1 py-0.5 space-y-0.5 border-l border-slate-200 dark:border-slate-800 ml-3.5"
                            >
                                <Link
                                    v-for="(child, childIdx) in item.children"
                                    :key="childIdx"
                                    :href="route(child.route)"
                                    :class="[
                                        'flex items-center px-2 py-1.5 rounded-md text-[11px] font-medium transition-colors',
                                        isRouteActive(child.active)
                                            ? 'bg-brand/10 text-brand font-bold border border-brand/20 dark:bg-brand/20 dark:text-white dark:border-brand/40'
                                            : 'text-slate-500 hover:text-slate-900 hover:bg-slate-200/50 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/40'
                                    ]"
                                >
                                    <span class="truncate">{{ child.title }}</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Footer User Capsule -->
                <div class="p-2.5 border-t border-slate-200 dark:border-zinc-800/80">
                    <div class="flex items-center justify-between p-1.5 rounded-lg border border-slate-200/90 bg-white dark:border-zinc-800 dark:bg-[#18181B] shadow-2xs">
                        <div class="flex items-center gap-2 overflow-hidden">
                            <img
                                :src="user?.avatar_url || 'https://ui-avatars.com/api/?name=Admin'"
                                alt="Avatar"
                                class="w-6 h-6 rounded-md object-cover shrink-0"
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
                            title="Keluar"
                            class="p-1 text-slate-400 hover:text-rose-600 hover:bg-slate-100 dark:hover:text-rose-400 dark:hover:bg-zinc-800 rounded-md transition"
                        >
                            <LogOut class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </aside>

            <!-- Right Studio Pane: The Work Canvas with Independent Scroll -->
            <div class="flex-1 flex flex-col h-full min-w-0 overflow-hidden bg-[#F8FAFC] dark:bg-[#0E0E11]">
                
                <!-- Internal Studio Topbar Console -->
                <header class="h-14 px-4 sm:px-6 border-b border-slate-200 dark:border-zinc-800/80 bg-white/95 dark:bg-[#121215]/90 backdrop-blur-md flex items-center justify-between shrink-0 z-20">
                    <div class="flex items-center gap-3 min-w-0">
                        <!-- Mobile Hamburger -->
                        <button
                            type="button"
                            @click="sidebarOpen = true"
                            class="p-1.5 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white lg:hidden rounded-lg"
                        >
                            <Menu class="w-5 h-5" />
                        </button>

                        <!-- Desktop Sidebar Collapse Toggle -->
                        <button
                            type="button"
                            @click="sidebarCollapsed = !sidebarCollapsed"
                            class="hidden lg:flex p-1.5 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800/60 transition"
                            title="Toggle Sidebar"
                        >
                            <Menu class="w-4 h-4" />
                        </button>

                        <div class="h-4 w-px bg-slate-200 dark:bg-zinc-800 hidden sm:block"></div>

                        <!-- Dynamic Breadcrumb Navigation -->
                        <nav class="flex items-center gap-1.5 text-xs truncate" aria-label="Breadcrumb">
                            <template v-for="(crumb, idx) in resolvedBreadcrumbs" :key="idx">
                                <span v-if="idx > 0" class="text-slate-300 dark:text-slate-700 select-none">/</span>
                                <Link
                                    v-if="crumb.url && !crumb.isCurrent"
                                    :href="crumb.url"
                                    class="font-medium text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition truncate max-w-[130px]"
                                >
                                    {{ crumb.title }}
                                </Link>
                                <span
                                    v-else-if="!crumb.isCurrent"
                                    class="font-mono text-slate-500 dark:text-slate-400 uppercase tracking-wider text-[11px] truncate max-w-[130px] hidden sm:inline"
                                >
                                    {{ crumb.title }}
                                </span>
                                <span
                                    v-else
                                    class="font-bold text-slate-900 dark:text-white truncate max-w-[180px]"
                                >
                                    {{ crumb.title }}
                                </span>
                            </template>
                        </nav>
                    </div>

                    <!-- Right Header Actions -->
                    <div class="flex items-center gap-2.5 shrink-0">
                        <!-- Search Command Palette Indicator -->
                        <button
                            type="button"
                            @click="showCommandPalette = true"
                            class="hidden sm:flex items-center gap-2 px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 dark:border-zinc-800 dark:bg-[#18181B] text-xs text-slate-500 dark:text-zinc-400 cursor-pointer hover:border-slate-300 dark:hover:border-zinc-700 hover:text-slate-700 dark:hover:text-zinc-300 transition group"
                            title="Buka Command Palette (Ctrl + K)"
                        >
                            <Search class="w-3.5 h-3.5 group-hover:text-brand transition" />
                            <span class="text-[11px]">Cari menu...</span>
                            <kbd class="px-1.5 py-0.5 text-[10px] font-mono bg-white border border-slate-200 rounded text-slate-500 dark:bg-zinc-800 dark:border-zinc-700 group-hover:border-brand/40 transition">Ctrl K</kbd>
                        </button>

                        <!-- Telemetry Pill -->
                        <div class="hidden lg:flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-50 border border-slate-200 dark:bg-[#18181B] dark:border-zinc-800 text-[11px] font-mono font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-emerald-600 dark:text-emerald-400">24ms</span>
                            <span class="text-slate-300 dark:text-zinc-700">&bull;</span>
                            <span class="text-slate-500 dark:text-zinc-400">SLA 99.98%</span>
                        </div>

                        <!-- Dark / Light Toggle -->
                        <button
                            type="button"
                            @click="toggleTheme"
                            class="p-1.5 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                            :title="isDark ? 'Mode Terang' : 'Mode Gelap'"
                        >
                            <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
                            <Moon v-else class="w-4 h-4 text-slate-500" />
                        </button>

                        <div class="h-4 w-px bg-slate-200 dark:bg-zinc-800"></div>

                        <!-- Profile Dropdown -->
                        <div class="relative">
                            <button
                                type="button"
                                @click="userDropdownOpen = !userDropdownOpen"
                                class="flex items-center gap-1.5 p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-800 transition"
                            >
                                <img
                                    :src="user?.avatar_url || 'https://ui-avatars.com/api/?name=Admin'"
                                    alt="Avatar"
                                    class="w-7 h-7 rounded-md object-cover"
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
                                class="absolute right-0 mt-2 w-52 p-1 bg-white border border-slate-200 rounded-xl shadow-xl dark:bg-[#18181B] dark:border-zinc-800 z-50 text-xs text-slate-700 dark:text-zinc-300 animate-in fade-in zoom-in-95 duration-100"
                            >
                                <div class="px-3 py-2 border-b border-slate-100 dark:border-zinc-800/80 mb-1">
                                    <p class="font-bold text-slate-900 dark:text-white truncate">{{ user?.name }}</p>
                                    <p class="text-[11px] font-mono text-slate-400 dark:text-zinc-500 truncate">{{ user?.email }}</p>
                                </div>

                                <Link
                                    :href="route('profile.edit')"
                                    class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-800 transition"
                                >
                                    <UserIcon class="w-3.5 h-3.5 text-slate-400" />
                                    <span>Profil Pengguna</span>
                                </Link>

                                <Link
                                    :href="route('admin.settings.index')"
                                    class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-800 transition"
                                >
                                    <Settings class="w-3.5 h-3.5 text-slate-400" />
                                    <span>Konfigurasi Sistem</span>
                                </Link>

                                <div class="my-1 border-t border-slate-100 dark:border-zinc-800/80"></div>

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

                <!-- Dedicated Work Canvas (Independent Scroll Container) -->
                <main class="flex-1 overflow-y-auto custom-scrollbar p-5 sm:p-7 space-y-6">
                    <div class="max-w-7xl mx-auto space-y-6">
                        <slot />
                    </div>

                    <!-- Minimal Enterprise Footer -->
                    <footer class="pt-8 pb-3 border-t border-slate-200/80 dark:border-zinc-800/70 text-xs text-slate-400 dark:text-zinc-500 font-mono flex flex-col sm:flex-row items-center justify-between gap-2 max-w-7xl mx-auto">
                        <span>&copy; {{ new Date().getFullYear() }} {{ appSettings.name }} &bull; Enterprise Admin Console</span>
                        <span class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>All services operational</span>
                        </span>
                    </footer>
                </main>
            </div>
        </div>
    </div>
</template>
