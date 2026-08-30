<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    Search,
    ChevronLeft,
    ChevronRight,
    ArrowUpDown,
    ArrowUp,
    ArrowDown,
    SlidersHorizontal,
    Columns,
    Download,
    RotateCcw,
    Check,
    X,
    Filter,
    Layers,
    AlignJustify,
    Menu,
} from 'lucide-vue-next';

const props = defineProps({
    columns: {
        type: Array,
        required: true,
        // Example: [{ key: 'name', label: 'Nama', sortable: true, defaultHidden: false }, ...]
    },
    pagination: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    searchPlaceholder: {
        type: String,
        default: 'Cari data...',
    },
    selectedItems: {
        type: Array,
        default: () => [],
    },
    selectable: {
        type: Boolean,
        default: false,
    },
    exportFileName: {
        type: String,
        default: 'export_data',
    },
});

const emit = defineEmits(['update:selectedItems', 'sort', 'search']);

// Search & Debounce
const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || 10);
let searchDebounceTimer = null;

watch(search, (newVal) => {
    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        applyFilters({ search: newVal, page: 1 });
    }, 350);
});

// View Density: 'comfortable' | 'compact'
const density = ref('comfortable');

onMounted(() => {
    const savedDensity = localStorage.getItem('datatable_density');
    if (savedDensity) {
        density.value = savedDensity;
    }
});

const toggleDensity = () => {
    density.value = density.value === 'comfortable' ? 'compact' : 'comfortable';
    localStorage.setItem('datatable_density', density.value);
};

// Column Visibility Manager
const showColumnDropdown = ref(false);
const visibleColumnKeys = ref(
    props.columns
        .filter((col) => !col.defaultHidden)
        .map((col) => col.key)
);

const toggleColumnVisibility = (key) => {
    const idx = visibleColumnKeys.value.indexOf(key);
    if (idx > -1) {
        // Keep at least 1 column visible
        if (visibleColumnKeys.value.length > 1) {
            visibleColumnKeys.value.splice(idx, 1);
        }
    } else {
        visibleColumnKeys.value.push(key);
    }
};

const visibleColumns = computed(() => {
    return props.columns.filter((col) => visibleColumnKeys.value.includes(col.key));
});

// Sorting
const handleSort = (columnKey) => {
    const isCurrent = props.filters.sort_field === columnKey;
    const direction = isCurrent && props.filters.sort_direction === 'asc' ? 'desc' : 'asc';
    applyFilters({ sort_field: columnKey, sort_direction: direction });
};

// Per Page
const handlePerPageChange = (e) => {
    applyFilters({ per_page: e.target.value, page: 1 });
};

// Apply query params to current Inertia route
const applyFilters = (newParams) => {
    const currentParams = {
        ...props.filters,
        ...newParams,
    };

    Object.keys(currentParams).forEach((key) => {
        if (currentParams[key] === '' || currentParams[key] === null || currentParams[key] === undefined) {
            delete currentParams[key];
        }
    });

    router.get(window.location.pathname, currentParams, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Reset Search & Filters
const resetFilters = () => {
    search.value = '';
    applyFilters({ search: '', page: 1, sort_field: null, sort_direction: null });
};

// Selection logic
const toggleSelectAll = (e) => {
    if (e.target.checked) {
        const allIds = (props.pagination.data || []).map((item) => item.id);
        emit('update:selectedItems', allIds);
    } else {
        emit('update:selectedItems', []);
    }
};

const toggleItem = (id) => {
    const current = [...props.selectedItems];
    const index = current.indexOf(id);
    if (index > -1) {
        current.splice(index, 1);
    } else {
        current.push(id);
    }
    emit('update:selectedItems', current);
};

const isAllSelected = () => {
    if (!props.pagination.data || props.pagination.data.length === 0) return false;
    return props.pagination.data.every((item) => props.selectedItems.includes(item.id));
};

const clearSelection = () => {
    emit('update:selectedItems', []);
};

// Built-in CSV Export
const exportTableCSV = () => {
    const cols = visibleColumns.value;
    const headers = cols.map((c) => c.label);
    const data = props.pagination.data || [];

    const rows = data.map((row) => {
        return cols.map((c) => {
            const val = row[c.key];
            if (val === null || val === undefined) return '""';
            const strVal = String(val).replace(/"/g, '""');
            return `"${strVal}"`;
        });
    });

    const csvContent = 'data:text/csv;charset=utf-8,\uFEFF' + [headers.join(','), ...rows.map((r) => r.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `${props.exportFileName}_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <div class="space-y-3 relative">
        <!-- Floating Bulk Action Pill (When Rows Selected) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div
                v-if="selectable && selectedItems.length > 0"
                class="flex items-center justify-between px-4 py-2.5 bg-slate-900 text-white rounded-xl shadow-lg border border-slate-800 dark:bg-slate-900 dark:border-slate-700 text-xs font-medium"
            >
                <div class="flex items-center gap-2.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>
                        <strong class="font-mono font-bold">{{ selectedItems.length }}</strong> data terpilih
                    </span>
                    <button
                        type="button"
                        @click="clearSelection"
                        class="text-[11px] text-slate-300 hover:text-white underline ml-1 cursor-pointer"
                    >
                        Batal Pilih
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <slot name="bulk-actions" />
                </div>
            </div>
        </Transition>

        <!-- Master Unified Enterprise Table Card -->
        <div class="bg-white border border-slate-200/90 rounded-2xl dark:bg-[#0D121F] dark:border-slate-800/90 shadow-2xs overflow-hidden">
            <!-- 1. Integrated Precision Toolbar Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-3.5 sm:p-4 border-b border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-[#0D121F]">
                <!-- Left: Search and Custom Filters Slot -->
                <div class="flex flex-1 flex-wrap items-center gap-2.5">
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-72">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="searchPlaceholder"
                            class="w-full pl-8 pr-7 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand dark:bg-slate-900 dark:border-slate-800 dark:text-white dark:placeholder-slate-500 transition font-medium"
                        />
                        <button
                            v-if="search"
                            type="button"
                            @click="search = ''"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5 rounded"
                        >
                            <X class="w-3 h-3" />
                        </button>
                    </div>

                    <!-- Custom Filters Slot (e.g. Role filter, Status dropdown) -->
                    <slot name="filters" />

                    <!-- Reset Filter Quick Button (Shows if search active) -->
                    <button
                        v-if="search || filters.role || filters.status"
                        type="button"
                        @click="resetFilters"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-[11px] font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-white bg-slate-100 hover:bg-slate-200 rounded-lg dark:bg-slate-800 dark:text-slate-300 transition"
                        title="Reset Filter"
                    >
                        <RotateCcw class="w-3 h-3" />
                        <span>Reset</span>
                    </button>
                </div>

                <!-- Right Toolbar: Table Controls & Actions -->
                <div class="flex items-center gap-2 shrink-0">
                    <!-- View Density Toggle -->
                    <button
                        type="button"
                        @click="toggleDensity"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-800 dark:hover:bg-slate-800 transition"
                        :title="density === 'comfortable' ? 'Ganti ke Mode Padat (Compact)' : 'Ganti ke Mode Nyaman (Comfortable)'"
                    >
                        <AlignJustify v-if="density === 'comfortable'" class="w-3.5 h-3.5 text-slate-400" />
                        <Menu v-else class="w-3.5 h-3.5 text-brand" />
                        <span class="text-[11px] hidden md:inline">{{ density === 'comfortable' ? 'Nyaman' : 'Padat' }}</span>
                    </button>

                    <!-- Column Visibility Dropdown -->
                    <div class="relative">
                        <button
                            type="button"
                            @click="showColumnDropdown = !showColumnDropdown"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-800 dark:hover:bg-slate-800 transition"
                            title="Atur Kolom"
                        >
                            <Columns class="w-3.5 h-3.5 text-slate-400" />
                            <span class="text-[11px] hidden md:inline">Kolom</span>
                        </button>

                        <div
                            v-if="showColumnDropdown"
                            @click="showColumnDropdown = false"
                            class="fixed inset-0 z-40"
                        ></div>

                        <div
                            v-if="showColumnDropdown"
                            class="absolute right-0 mt-1.5 w-48 p-2 bg-white border border-slate-200 rounded-xl shadow-xl dark:bg-[#0D121F] dark:border-slate-800 z-50 text-xs text-slate-700 dark:text-slate-300 animate-in fade-in zoom-in-95 duration-100"
                        >
                            <div class="px-2 py-1 text-[10px] font-mono font-bold uppercase tracking-wider text-slate-400 mb-1 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                                Visibilitas Kolom
                            </div>
                            <div class="space-y-1 max-h-48 overflow-y-auto custom-scrollbar">
                                <label
                                    v-for="col in columns"
                                    :key="col.key"
                                    class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer text-xs select-none"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="visibleColumnKeys.includes(col.key)"
                                        @change="toggleColumnVisibility(col.key)"
                                        class="w-3.5 h-3.5 text-slate-900 border-slate-300 rounded focus:ring-0 dark:border-slate-700 dark:bg-slate-800"
                                    />
                                    <span class="truncate font-medium">{{ col.label }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Export CSV Button -->
                    <button
                        type="button"
                        @click="exportTableCSV"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-800 dark:hover:bg-slate-800 transition"
                        title="Ekspor CSV"
                    >
                        <Download class="w-3.5 h-3.5 text-slate-400" />
                        <span class="text-[11px] hidden md:inline">Ekspor</span>
                    </button>

                    <div class="h-4 w-px bg-slate-200 dark:bg-slate-800 hidden sm:block"></div>

                    <!-- Action Buttons Slot (e.g. Tambah Pengguna Baru) -->
                    <slot name="actions" />
                </div>
            </div>

            <!-- 2. Razor-Sharp Data Table Body -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                    <!-- Thead: Crisp Modern Headers -->
                    <thead class="bg-slate-50/90 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 select-none">
                        <tr>
                            <!-- Select All Checkbox -->
                            <th v-if="selectable" class="w-9 px-3.5 py-3 text-center">
                                <input
                                    type="checkbox"
                                    :checked="isAllSelected()"
                                    @change="toggleSelectAll"
                                    class="w-3.5 h-3.5 text-brand border-slate-300 rounded focus:ring-brand dark:border-slate-700 dark:bg-slate-800 cursor-pointer"
                                />
                            </th>

                            <!-- Columns Header -->
                            <th
                                v-for="col in visibleColumns"
                                :key="col.key"
                                :class="[
                                    'px-4 py-3 whitespace-nowrap',
                                    col.sortable ? 'cursor-pointer hover:text-slate-900 dark:hover:text-white' : '',
                                    col.headerClass || ''
                                ]"
                                @click="col.sortable && handleSort(col.key)"
                            >
                                <div class="flex items-center gap-1.5">
                                    <span>{{ col.label }}</span>
                                    <span v-if="col.sortable" class="text-slate-400">
                                        <ArrowUp v-if="filters.sort_field === col.key && filters.sort_direction === 'asc'" class="w-3 h-3 text-brand" />
                                        <ArrowDown v-else-if="filters.sort_field === col.key && filters.sort_direction === 'desc'" class="w-3 h-3 text-brand" />
                                        <ArrowUpDown v-else class="w-3 h-3 opacity-30 hover:opacity-100" />
                                    </span>
                                </div>
                            </th>

                            <!-- Row Actions Column -->
                            <th v-if="$slots.rowActions" class="px-4 py-3 text-right whitespace-nowrap">
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <!-- Tbody: Clean Micro-Border Rows -->
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70 font-sans">
                        <tr
                            v-for="(row, index) in pagination.data"
                            :key="row.id || index"
                            :class="[
                                'transition-colors hover:bg-slate-50/70 dark:hover:bg-slate-800/30',
                                selectable && selectedItems.includes(row.id)
                                    ? 'bg-brand/5 dark:bg-brand/10 border-l-2 border-l-brand'
                                    : ''
                            ]"
                        >
                            <!-- Row Select Checkbox -->
                            <td
                                v-if="selectable"
                                :class="[
                                    'w-9 px-3.5 text-center',
                                    density === 'compact' ? 'py-2' : 'py-3.5'
                                ]"
                            >
                                <input
                                    type="checkbox"
                                    :checked="selectedItems.includes(row.id)"
                                    @change="toggleItem(row.id)"
                                    class="w-3.5 h-3.5 text-brand border-slate-300 rounded focus:ring-brand dark:border-slate-700 dark:bg-slate-800 cursor-pointer"
                                />
                            </td>

                            <!-- Dynamic Slots for Columns -->
                            <td
                                v-for="col in visibleColumns"
                                :key="col.key"
                                :class="[
                                    'px-4 align-middle',
                                    density === 'compact' ? 'py-2' : 'py-3.5',
                                    col.cellClass || ''
                                ]"
                            >
                                <slot :name="`cell(${col.key})`" :row="row" :value="row[col.key]">
                                    {{ row[col.key] }}
                                </slot>
                            </td>

                            <!-- Row Actions Slot -->
                            <td
                                v-if="$slots.rowActions"
                                :class="[
                                    'px-4 text-right whitespace-nowrap align-middle',
                                    density === 'compact' ? 'py-2' : 'py-3.5'
                                ]"
                            >
                                <slot name="rowActions" :row="row" />
                            </td>
                        </tr>

                        <!-- Empty State (Humanized & Dignified) -->
                        <tr v-if="!pagination.data || pagination.data.length === 0">
                            <td
                                :colspan="visibleColumns.length + (selectable ? 1 : 0) + ($slots.rowActions ? 1 : 0)"
                                class="py-16 text-center text-slate-400 dark:text-slate-500"
                            >
                                <div class="flex flex-col items-center justify-center gap-3 max-w-sm mx-auto">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                        <Search class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Tidak Ada Data Ditemukan
                                        </h4>
                                        <p class="text-[11px] text-slate-400 mt-0.5">
                                            {{ search ? `Tidak ada data yang cocok dengan kriteria "${search}".` : 'Belum ada rekaman data yang tersedia pada tabel ini.' }}
                                        </p>
                                    </div>
                                    <button
                                        v-if="search"
                                        type="button"
                                        @click="resetFilters"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg dark:bg-indigo-950/50 dark:text-indigo-400 dark:hover:bg-indigo-900/50 transition"
                                    >
                                        <RotateCcw class="w-3.5 h-3.5" />
                                        <span>Reset Pencarian</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 4. Executive Pagination Footer -->
            <div
                v-if="pagination.total > 0"
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between px-4 py-2.5 border-t border-slate-200/80 bg-slate-50/50 text-[11px] text-slate-500 dark:bg-slate-900/50 dark:border-slate-800 dark:text-slate-400 gap-3 font-sans"
            >
                <div class="flex items-center gap-2">
                    <span>
                        Menampilkan <strong class="text-slate-800 dark:text-slate-200">{{ pagination.from || 0 }}</strong> - <strong class="text-slate-800 dark:text-slate-200">{{ pagination.to || 0 }}</strong> dari <strong class="text-slate-800 dark:text-slate-200">{{ pagination.total }}</strong> data
                    </span>

                    <span class="text-slate-300 dark:text-slate-700 hidden sm:inline">&bull;</span>

                    <!-- Per Page Dropdown -->
                    <div class="hidden sm:flex items-center gap-1.5 text-slate-400">
                        <span>Baris:</span>
                        <select
                            :value="perPage"
                            @change="handlePerPageChange"
                            class="py-0.5 pl-2 pr-6 text-xs font-medium bg-white border border-slate-200 rounded-md focus:ring-0 focus:border-brand dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300"
                        >
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>
                </div>

                <!-- Page Navigation Navigation Pills -->
                <div class="flex items-center gap-1 font-sans">
                    <button
                        type="button"
                        :disabled="!pagination.prev_page_url"
                        @click="pagination.prev_page_url && applyFilters({ page: pagination.current_page - 1 })"
                        class="p-1 rounded-md border border-slate-200 bg-white hover:bg-slate-100 dark:bg-slate-800 dark:border-slate-700 dark:hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed transition"
                        title="Halaman Sebelumnya"
                    >
                        <ChevronLeft class="w-3.5 h-3.5" />
                    </button>

                    <div class="px-2 py-0.5 text-xs font-mono font-medium text-slate-700 dark:text-slate-200">
                        {{ pagination.current_page }} / {{ pagination.last_page }}
                    </div>

                    <button
                        type="button"
                        :disabled="!pagination.next_page_url"
                        @click="pagination.next_page_url && applyFilters({ page: pagination.current_page + 1 })"
                        class="p-1 rounded-md border border-slate-200 bg-white hover:bg-slate-100 dark:bg-slate-800 dark:border-slate-700 dark:hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed transition"
                        title="Halaman Selanjutnya"
                    >
                        <ChevronRight class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
