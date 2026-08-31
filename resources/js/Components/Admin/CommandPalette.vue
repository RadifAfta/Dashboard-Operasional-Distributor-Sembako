<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    Search,
    LayoutDashboard,
    Users,
    Shield,
    Settings,
    Sun,
    Moon,
    User,
    Bell,
    ArrowRight,
    CornerDownLeft,
    X,
} from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    isDark: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'toggle-theme']);

const searchQuery = ref('');
const activeIndex = ref(0);
const searchInput = ref(null);

const close = () => {
    emit('update:modelValue', false);
    searchQuery.value = '';
    activeIndex.value = 0;
};

// Base Actions and Navigation List
const commands = computed(() => [
    {
        group: 'Navigasi Halaman',
        items: [
            {
                id: 'nav-dashboard',
                title: 'Pusat Operasional & Dashboard',
                subtitle: 'Telemetri sistem, status kluster, dan log audit',
                icon: LayoutDashboard,
                action: () => router.visit(route('admin.dashboard')),
                badge: 'G D',
            },
            {
                id: 'nav-users',
                title: 'Manajemen Pengguna & Akun',
                subtitle: 'Kelola data pengguna, status verifikasi, dan peran',
                icon: Users,
                action: () => router.visit(route('admin.users.index')),
                badge: 'G U',
            },
            {
                id: 'nav-roles',
                title: 'Matriks Hak Akses & Role (RBAC)',
                subtitle: 'Atur izin wewenang Super Admin, Admin, dan User',
                icon: Shield,
                action: () => router.visit(route('admin.roles.index')),
                badge: 'G R',
            },
            {
                id: 'nav-settings',
                title: 'Pengaturan Sistem & Branding',
                subtitle: 'Konfigurasi nama website, registrasi, dan zona waktu',
                icon: Settings,
                action: () => router.visit(route('admin.settings.index')),
                badge: 'G S',
            },
        ],
    },
    {
        group: 'Aksi Cepat & Preferensi',
        items: [
            {
                id: 'action-theme',
                title: props.isDark ? 'Ganti ke Mode Terang (Light Mode)' : 'Ganti ke Mode Gelap (Dark Mode)',
                subtitle: 'Beralih tampilan kontras antarmuka',
                icon: props.isDark ? Sun : Moon,
                action: () => {
                    emit('toggle-theme');
                    close();
                },
                badge: 'T',
            },
            {
                id: 'action-profile',
                title: 'Profil Pengguna Saya',
                subtitle: 'Ubah informasi akun dan sandi',
                icon: User,
                action: () => router.visit(route('profile.edit')),
                badge: 'P',
            },
            {
                id: 'action-notifications',
                title: 'Uji Notifikasi & Alert Playground',
                subtitle: 'Test toast sukses, error ramah, dan banner alert',
                icon: Bell,
                action: () => router.visit(route('admin.settings.index')),
                badge: 'N',
            },
        ],
    },
]);

// Filtered commands based on query
const filteredGroups = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return commands.value;

    return commands.value
        .map((group) => ({
            ...group,
            items: group.items.filter(
                (item) =>
                    item.title.toLowerCase().includes(q) ||
                    item.subtitle.toLowerCase().includes(q) ||
                    group.group.toLowerCase().includes(q)
            ),
        }))
        .filter((group) => group.items.length > 0);
});

// Flat list for keyboard indexing
const flatItems = computed(() => {
    return filteredGroups.value.flatMap((g) => g.items);
});

watch(
    () => props.modelValue,
    (val) => {
        if (val) {
            nextTick(() => {
                searchInput.value?.focus();
            });
        }
    }
);

watch(searchQuery, () => {
    activeIndex.value = 0;
});

const onKeydown = (e) => {
    // Open on Ctrl + K or Cmd + K
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        emit('update:modelValue', !props.modelValue);
        return;
    }

    if (!props.modelValue) return;

    if (e.key === 'Escape') {
        e.preventDefault();
        close();
    } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        activeIndex.value = (activeIndex.value + 1) % flatItems.value.length;
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        activeIndex.value = (activeIndex.value - 1 + flatItems.value.length) % flatItems.value.length;
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const selected = flatItems.value[activeIndex.value];
        if (selected) {
            selected.action();
            close();
        }
    }
};

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 scale-98"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-98"
        >
            <div
                v-if="modelValue"
                class="fixed inset-0 z-50 overflow-y-auto bg-black/75 backdrop-blur-xs flex items-start justify-center pt-16 sm:pt-24 p-4"
                @click.self="close"
            >
                <div class="relative w-full max-w-xl bg-white border border-slate-200 rounded-2xl shadow-2xl overflow-hidden dark:bg-[#18181B] dark:border-zinc-800 animate-in fade-in zoom-in-95 duration-150">
                    <!-- Search Input Bar -->
                    <div class="flex items-center gap-3 px-4 py-3.5 border-b border-slate-100 dark:border-zinc-800/80">
                        <Search class="w-4 h-4 text-slate-400 shrink-0" />
                        <input
                            ref="searchInput"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Ketik nama halaman, fitur, atau aksi cepat..."
                            class="w-full bg-transparent border-0 p-0 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:ring-0 dark:text-white"
                        />
                        <kbd class="px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 border border-slate-200 rounded text-slate-500 dark:bg-zinc-800 dark:border-zinc-700">
                            ESC
                        </kbd>
                    </div>

                    <!-- Command Items List -->
                    <div class="max-h-80 overflow-y-auto p-2 space-y-4 custom-scrollbar">
                        <div v-if="flatItems.length === 0" class="py-8 text-center text-xs text-slate-400">
                            Tidak ada menu atau aksi yang cocok dengan "{{ searchQuery }}"
                        </div>

                        <div v-for="group in filteredGroups" :key="group.group" class="space-y-1">
                            <p class="px-3 py-1 text-[10px] font-mono font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                {{ group.group }}
                            </p>

                            <div
                                v-for="item in group.items"
                                :key="item.id"
                                @click="item.action(); close();"
                                @mouseenter="activeIndex = flatItems.findIndex((i) => i.id === item.id)"
                                :class="[
                                    'flex items-center justify-between gap-3 px-3 py-2 rounded-xl text-xs cursor-pointer transition-colors duration-100',
                                    flatItems[activeIndex]?.id === item.id
                                        ? 'bg-slate-900 text-white dark:bg-indigo-600 dark:text-white'
                                        : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800/60'
                                ]"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <component
                                        :is="item.icon"
                                        :class="[
                                            'w-4 h-4 shrink-0',
                                            flatItems[activeIndex]?.id === item.id
                                                ? 'text-white'
                                                : 'text-slate-400 dark:text-slate-500'
                                        ]"
                                    />
                                    <div class="truncate">
                                        <p class="font-bold tracking-tight truncate">{{ item.title }}</p>
                                        <p
                                            :class="[
                                                'text-[10px] truncate',
                                                flatItems[activeIndex]?.id === item.id
                                                    ? 'text-slate-300 dark:text-indigo-200'
                                                    : 'text-slate-400'
                                            ]"
                                        >
                                            {{ item.subtitle }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1 shrink-0">
                                    <span
                                        v-if="item.badge"
                                        :class="[
                                            'px-1.5 py-0.5 text-[9px] font-mono font-bold rounded',
                                            flatItems[activeIndex]?.id === item.id
                                                ? 'bg-white/20 text-white'
                                                : 'bg-slate-100 text-slate-500 dark:bg-zinc-800 dark:text-zinc-400'
                                        ]"
                                    >
                                        {{ item.badge }}
                                    </span>
                                    <CornerDownLeft
                                        v-if="flatItems[activeIndex]?.id === item.id"
                                        class="w-3 h-3 text-white ml-1 animate-pulse"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Keyboard Hints -->
                    <div class="px-4 py-2 bg-slate-50 border-t border-slate-100 dark:bg-[#121215] dark:border-zinc-800/80 flex items-center justify-between text-[10px] font-mono text-slate-400">
                        <div class="flex items-center gap-3">
                            <span class="flex items-center gap-1">
                                <kbd class="px-1 py-0.2 bg-white border border-slate-200 rounded dark:bg-zinc-800 dark:border-zinc-700">&uarr;&darr;</kbd> Navigasi
                            </span>
                            <span class="flex items-center gap-1">
                                <kbd class="px-1 py-0.2 bg-white border border-slate-200 rounded dark:bg-zinc-800 dark:border-zinc-700">&crarr;</kbd> Buka
                            </span>
                        </div>
                        <span>Raycast-Speed Engine</span>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
