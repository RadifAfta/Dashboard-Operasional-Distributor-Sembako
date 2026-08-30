<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Search, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-vue-next';

const props = defineProps({
    columns: {
        type: Array,
        required: true,
        // Example: [{ key: 'name', label: 'Name', sortable: true }, ...]
    },
    pagination: {
        type: Object,
        required: true,
        // Laravel paginator object: data, current_page, last_page, from, to, total, links, etc.
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
});

const emit = defineEmits(['update:selectedItems', 'sort', 'search']);

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || 10);
let searchDebounceTimer = null;

// Search debounce
watch(search, (newVal) => {
    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        applyFilters({ search: newVal, page: 1 });
    }, 350);
});

// Per Page change
const handlePerPageChange = (e) => {
    applyFilters({ per_page: e.target.value, page: 1 });
};

// Sort column
const handleSort = (columnKey) => {
    const isCurrent = props.filters.sort_field === columnKey;
    const direction = isCurrent && props.filters.sort_direction === 'asc' ? 'desc' : 'asc';
    applyFilters({ sort_field: columnKey, sort_direction: direction });
};

// Apply query params to current Inertia route
const applyFilters = (newParams) => {
    const currentParams = {
        ...props.filters,
        ...newParams,
    };

    // Remove empty parameters
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

// Select all toggle
const toggleSelectAll = (e) => {
    if (e.target.checked) {
        const allIds = props.pagination.data.map((item) => item.id);
        emit('update:selectedItems', allIds);
    } else {
        emit('update:selectedItems', []);
    }
};

// Toggle individual item
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
</script>

<template>
    <div class="space-y-4">
        <!-- Top Toolbar: Search, Filters, and Actions -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 flex-wrap items-center gap-3">
                <!-- Search Input -->
                <div class="relative w-full max-w-xs">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="searchPlaceholder"
                        class="w-full pl-9 pr-4 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:bg-slate-900 dark:border-slate-800 dark:text-white dark:placeholder-slate-500"
                    />
                </div>

                <!-- Custom Filter Slot -->
                <slot name="filters" />

                <!-- Per Page Select -->
                <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                    <span>Tampilkan</span>
                    <select
                        :value="perPage"
                        @change="handlePerPageChange"
                        class="py-1.5 px-2.5 text-xs bg-white border border-slate-200 rounded-lg focus:ring-1 focus:ring-indigo-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-300"
                    >
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                </div>
            </div>

            <!-- Action Buttons Slot (e.g., Create, Export) -->
            <div class="flex items-center gap-2 shrink-0">
                <slot name="actions" />
            </div>
        </div>

        <!-- Bulk Action Banner -->
        <div
            v-if="selectable && selectedItems.length > 0"
            class="flex items-center justify-between px-4 py-2.5 bg-indigo-50 border border-indigo-200 rounded-xl dark:bg-indigo-950/40 dark:border-indigo-900/60"
        >
            <div class="text-sm font-medium text-indigo-700 dark:text-indigo-300">
                <span class="font-bold">{{ selectedItems.length }}</span> item terpilih
            </div>
            <div class="flex items-center gap-2">
                <slot name="bulk-actions" />
            </div>
        </div>

        <!-- Data Table Container -->
        <div class="overflow-hidden bg-white border border-slate-200 rounded-2xl shadow-sm dark:bg-slate-900 dark:border-slate-800">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200 dark:bg-slate-800/60 dark:text-slate-400 dark:border-slate-800">
                        <tr>
                            <!-- Checkbox Select All -->
                            <th v-if="selectable" class="w-10 px-4 py-3.5">
                                <input
                                    type="checkbox"
                                    :checked="isAllSelected()"
                                    @change="toggleSelectAll"
                                    class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800"
                                />
                            </th>

                            <!-- Columns Header -->
                            <th
                                v-for="col in columns"
                                :key="col.key"
                                :class="[
                                    'px-4 py-3.5',
                                    col.sortable ? 'cursor-pointer select-none hover:text-slate-700 dark:hover:text-slate-200' : '',
                                    col.class || ''
                                ]"
                                @click="col.sortable && handleSort(col.key)"
                            >
                                <div class="flex items-center gap-1.5">
                                    <span>{{ col.label }}</span>
                                    <span v-if="col.sortable" class="text-slate-400">
                                        <ArrowUp v-if="filters.sort_field === col.key && filters.sort_direction === 'asc'" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" />
                                        <ArrowDown v-else-if="filters.sort_field === col.key && filters.sort_direction === 'desc'" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" />
                                        <ArrowUpDown v-else class="w-3.5 h-3.5 opacity-40 hover:opacity-100" />
                                    </span>
                                </div>
                            </th>

                            <!-- Actions Column -->
                            <th v-if="$slots.rowActions" class="px-4 py-3.5 text-right">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <!-- Table Rows -->
                        <tr
                            v-for="(row, index) in pagination.data"
                            :key="row.id || index"
                            class="transition-colors hover:bg-slate-50/70 dark:hover:bg-slate-800/40"
                            :class="{ 'bg-indigo-50/30 dark:bg-indigo-950/20': selectable && selectedItems.includes(row.id) }"
                        >
                            <!-- Row Select Checkbox -->
                            <td v-if="selectable" class="w-10 px-4 py-3.5">
                                <input
                                    type="checkbox"
                                    :checked="selectedItems.includes(row.id)"
                                    @change="toggleItem(row.id)"
                                    class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800"
                                />
                            </td>

                            <!-- Dynamic Slots for Columns -->
                            <td
                                v-for="col in columns"
                                :key="col.key"
                                :class="['px-4 py-3.5', col.cellClass || '']"
                            >
                                <slot :name="`cell(${col.key})`" :row="row" :value="row[col.key]">
                                    {{ row[col.key] }}
                                </slot>
                            </td>

                            <!-- Row Actions Slot -->
                            <td v-if="$slots.rowActions" class="px-4 py-3.5 text-right whitespace-nowrap">
                                <slot name="rowActions" :row="row" />
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="!pagination.data || pagination.data.length === 0">
                            <td :colspan="columns.length + (selectable ? 1 : 0) + ($slots.rowActions ? 1 : 0)" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <slot name="empty">
                                        <Search class="w-8 h-8 opacity-30" />
                                        <p class="text-sm font-medium">Tidak ada data yang ditemukan.</p>
                                    </slot>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div
                v-if="pagination.total > 0"
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between px-4 py-3 border-t border-slate-200 bg-slate-50/50 text-xs text-slate-500 dark:bg-slate-800/30 dark:border-slate-800 dark:text-slate-400 gap-3"
            >
                <div>
                    Menampilkan <span class="font-semibold text-slate-700 dark:text-slate-200">{{ pagination.from || 0 }}</span> sampai
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ pagination.to || 0 }}</span> dari
                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ pagination.total }}</span> hasil
                </div>

                <!-- Page Navigation Buttons -->
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        :disabled="!pagination.prev_page_url"
                        @click="pagination.prev_page_url && applyFilters({ page: pagination.current_page - 1 })"
                        class="p-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-700 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                    >
                        <ChevronLeft class="w-4 h-4" />
                    </button>

                    <div class="px-2 py-1 font-medium text-slate-700 dark:text-slate-200">
                        Hal {{ pagination.current_page }} dari {{ pagination.last_page }}
                    </div>

                    <button
                        type="button"
                        :disabled="!pagination.next_page_url"
                        @click="pagination.next_page_url && applyFilters({ page: pagination.current_page + 1 })"
                        class="p-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-700 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                    >
                        <ChevronRight class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
