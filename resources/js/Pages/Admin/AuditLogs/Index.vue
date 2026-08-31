<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import {
    Activity,
    Eye,
    X,
    Calendar,
    User as UserIcon,
    Globe,
    Monitor,
    Clock,
    FileCode,
    ArrowRight,
} from 'lucide-vue-next';

const props = defineProps({
    logs: {
        type: Object,
        required: true,
    },
    availableEvents: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const columns = [
    { key: 'created_at', label: 'Waktu Aktivitas', sortable: true },
    { key: 'user', label: 'Pelaku', sortable: false },
    { key: 'event', label: 'Tipe Aksi', sortable: true },
    { key: 'description', label: 'Deskripsi Aktivitas', sortable: false },
    { key: 'ip_address', label: 'IP Address', sortable: false },
];

// Detail Modal State
const selectedLog = ref(null);
const showDetailModal = ref(false);
const activeTab = ref('diff'); // 'diff' | 'raw'

const openDetailModal = (log) => {
    selectedLog.value = log;
    activeTab.value = 'diff';
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedLog.value = null;
};

const handleEventFilter = (e) => {
    const event = e.target.value;
    const currentParams = { ...props.filters, event: event || undefined, page: 1 };
    router.get(route('admin.audit-logs.index'), currentParams, { preserveState: true, replace: true });
};

const formatTimestamp = (dateString) => {
    if (!dateString) return '—';
    const d = new Date(dateString);
    return d.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};

const getEventColor = (event) => {
    switch (event) {
        case 'created':
            return 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200/60 dark:border-emerald-800/40';
        case 'updated':
            return 'text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/30 border-sky-200/60 dark:border-sky-800/40';
        case 'deleted':
            return 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/30 border-rose-200/60 dark:border-rose-800/40';
        case 'auth':
            return 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/30 border-amber-200/60 dark:border-amber-800/40';
        default:
            return 'text-slate-600 dark:text-zinc-400 bg-slate-100 dark:bg-zinc-800 border-slate-200/60 dark:border-zinc-700/60';
    }
};

const hasChangesDiff = computed(() => {
    if (!selectedLog.value?.properties) return false;
    return !!(selectedLog.value.properties.old || selectedLog.value.properties.new);
});

const changedKeys = computed(() => {
    if (!selectedLog.value?.properties) return [];
    const oldKeys = Object.keys(selectedLog.value.properties.old || {});
    const newKeys = Object.keys(selectedLog.value.properties.new || {});
    return Array.from(new Set([...oldKeys, ...newKeys]));
});
</script>

<template>
    <AdminLayout title="Audit Logs">
        <Head title="Audit Logs - Riwayat Aktivitas Sistem" />

        <div class="space-y-6">
            <!-- Header Title -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Audit Logs & Riwayat Aktivitas
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">
                        Pencatatan transparan seluruh mutasi data, autentikasi, dan operasional sistem secara real-time.
                    </p>
                </div>
            </div>

            <!-- Reusable DataTable -->
            <DataTable
                :columns="columns"
                :pagination="logs"
                :filters="filters"
                searchPlaceholder="Cari deskripsi, IP, atau pengguna..."
                export-file-name="riwayat_audit_log_sistem"
            >
                <!-- Filters Slot: Event Dropdown -->
                <template #filters>
                    <div class="flex items-center gap-2">
                        <select
                            :value="filters.event || ''"
                            @change="handleEventFilter"
                            class="py-1.5 px-2.5 text-xs font-medium bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand dark:bg-zinc-900 dark:border-zinc-800 dark:text-zinc-200"
                        >
                            <option value="">Semua Tipe Aksi (Event)</option>
                            <option v-for="ev in availableEvents" :key="ev" :value="ev">
                                {{ ev.toUpperCase() }}
                            </option>
                        </select>
                    </div>
                </template>

                <!-- Custom Cell for Timestamp -->
                <template #cell(created_at)="{ value }">
                    <span class="font-mono text-xs text-slate-600 dark:text-zinc-400">
                        {{ formatTimestamp(value) }}
                    </span>
                </template>

                <!-- Custom Cell for User -->
                <template #cell(user)="{ row }">
                    <div v-if="row.user" class="min-w-0">
                        <p class="font-medium text-xs text-slate-900 dark:text-zinc-100 truncate">
                            {{ row.user.name }}
                        </p>
                        <p class="text-[10px] text-slate-400 dark:text-zinc-500 truncate">
                            {{ row.user.email }}
                        </p>
                    </div>
                    <span v-else class="text-xs font-mono text-slate-400 dark:text-zinc-500 italic">
                        Sistem / Tamu
                    </span>
                </template>

                <!-- Custom Cell for Event -->
                <template #cell(event)="{ value }">
                    <span
                        :class="[
                            'inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-mono font-bold uppercase tracking-wider border',
                            getEventColor(value)
                        ]"
                    >
                        {{ value }}
                    </span>
                </template>

                <!-- Custom Cell for Description -->
                <template #cell(description)="{ value }">
                    <span class="text-xs text-slate-800 dark:text-zinc-200 font-medium">
                        {{ value }}
                    </span>
                </template>

                <!-- Custom Cell for IP Address -->
                <template #cell(ip_address)="{ value }">
                    <span class="font-mono text-xs text-slate-500 dark:text-zinc-400">
                        {{ value || '127.0.0.1' }}
                    </span>
                </template>

                <!-- Row Actions: Inspect Details -->
                <template #rowActions="{ row }">
                    <div class="flex items-center justify-end gap-1">
                        <button
                            type="button"
                            @click="openDetailModal(row)"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 transition"
                            title="Inspeksi Detail & Perubahan Data"
                        >
                            <Eye class="w-3.5 h-3.5" />
                            <span>Detail</span>
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>

        <!-- Modal Detail / Diff Inspector -->
        <Teleport to="body">
            <div
                v-if="showDetailModal && selectedLog"
                class="fixed inset-0 z-50 overflow-y-auto bg-black/75 backdrop-blur-xs flex items-center justify-center p-4"
                @click.self="closeDetailModal"
            >
                <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-2xl border border-slate-200 dark:bg-[#141417] dark:border-zinc-800 overflow-hidden animate-in fade-in zoom-in-95 duration-150 flex flex-col max-h-[90vh]">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-zinc-800">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-xl bg-brand/10 text-brand dark:bg-brand/20 dark:text-brand">
                                <Activity class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                    Inspeksi Log Aktivitas
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-zinc-500 mt-0.5">
                                    ID Log: #{{ selectedLog.id }} &bull; {{ formatTimestamp(selectedLog.created_at) }}
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closeDetailModal"
                            class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-zinc-200 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-800 transition"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Metadata Grid -->
                    <div class="px-6 py-4 bg-slate-50/70 dark:bg-[#0E0E11]/60 border-b border-slate-100 dark:border-zinc-800/80 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                        <div>
                            <span class="text-slate-400 dark:text-zinc-500 text-[10px] uppercase font-mono tracking-wider block">Pelaku</span>
                            <span class="font-bold text-slate-900 dark:text-zinc-200 truncate block mt-0.5">
                                {{ selectedLog.user?.name || 'Sistem' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400 dark:text-zinc-500 text-[10px] uppercase font-mono tracking-wider block">Event</span>
                            <span
                                :class="[
                                    'inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-bold uppercase tracking-wider border mt-0.5',
                                    getEventColor(selectedLog.event)
                                ]"
                            >
                                {{ selectedLog.event }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400 dark:text-zinc-500 text-[10px] uppercase font-mono tracking-wider block">IP Address</span>
                            <span class="font-mono text-slate-700 dark:text-zinc-300 block mt-0.5">
                                {{ selectedLog.ip_address || '—' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400 dark:text-zinc-500 text-[10px] uppercase font-mono tracking-wider block">Model Subjek</span>
                            <span class="font-mono text-slate-700 dark:text-zinc-300 truncate block mt-0.5" :title="selectedLog.subject_type">
                                {{ selectedLog.subject_type ? selectedLog.subject_type.split('\\').pop() : '—' }} #{{ selectedLog.subject_id || '' }}
                            </span>
                        </div>
                    </div>

                    <!-- Modal Body / Content -->
                    <div class="p-6 overflow-y-auto flex-1 custom-scrollbar space-y-4">
                        <!-- Description Note -->
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 dark:bg-zinc-900/60 dark:border-zinc-800">
                            <span class="text-[10px] font-mono font-bold text-slate-400 dark:text-zinc-500 uppercase tracking-wider block mb-1">
                                Keterangan Aktivitas
                            </span>
                            <p class="text-xs text-slate-800 dark:text-zinc-200 font-medium">
                                {{ selectedLog.description }}
                            </p>
                        </div>

                        <!-- User Agent Information -->
                        <div v-if="selectedLog.user_agent" class="text-xs text-slate-500 dark:text-zinc-400 font-mono bg-slate-50 dark:bg-zinc-900/40 p-2.5 rounded-lg border border-slate-200/60 dark:border-zinc-800/60 break-all">
                            <span class="text-slate-400 dark:text-zinc-500 text-[10px] uppercase block mb-0.5">Perangkat & Browser:</span>
                            {{ selectedLog.user_agent }}
                        </div>

                        <!-- Tab Header (Diff vs Raw JSON) -->
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-zinc-800 pt-2">
                            <div class="flex items-center gap-4">
                                <button
                                    type="button"
                                    @click="activeTab = 'diff'"
                                    :class="[
                                        'pb-2 text-xs font-bold transition border-b-2 -mb-px',
                                        activeTab === 'diff'
                                            ? 'text-brand border-brand'
                                            : 'text-slate-400 hover:text-slate-600 dark:hover:text-zinc-300 border-transparent'
                                    ]"
                                >
                                    Perubahan Nilai (Visual Diff)
                                </button>
                                <button
                                    type="button"
                                    @click="activeTab = 'raw'"
                                    :class="[
                                        'pb-2 text-xs font-bold transition border-b-2 -mb-px',
                                        activeTab === 'raw'
                                            ? 'text-brand border-brand'
                                            : 'text-slate-400 hover:text-slate-600 dark:hover:text-zinc-300 border-transparent'
                                    ]"
                                >
                                    Raw JSON
                                </button>
                            </div>
                        </div>

                        <!-- Tab 1: Visual Diff -->
                        <div v-if="activeTab === 'diff'">
                            <!-- Case: Update Diff (Old vs New) -->
                            <div v-if="hasChangesDiff" class="overflow-x-auto border border-slate-200 dark:border-zinc-800 rounded-xl">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-100 dark:bg-zinc-900 border-b border-slate-200 dark:border-zinc-800 text-[11px] font-bold text-slate-600 dark:text-zinc-400 uppercase font-mono">
                                        <tr>
                                            <th class="py-2 px-3 w-1/4">Atribut</th>
                                            <th class="py-2 px-3 w-3/8 text-rose-600 dark:text-rose-400">Sebelum (Nilai Lama)</th>
                                            <th class="py-2 px-3 w-3/8 text-emerald-600 dark:text-emerald-400">Sesudah (Nilai Baru)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/80 font-mono text-xs">
                                        <tr
                                            v-for="key in changedKeys"
                                            :key="key"
                                            class="hover:bg-slate-50 dark:hover:bg-zinc-900/40 transition"
                                        >
                                            <td class="py-2.5 px-3 font-semibold text-slate-800 dark:text-zinc-200">
                                                {{ key }}
                                            </td>
                                            <td class="py-2.5 px-3 bg-rose-50/40 dark:bg-rose-950/20 text-rose-700 dark:text-rose-300 break-all">
                                                {{ selectedLog.properties.old?.[key] !== undefined ? JSON.stringify(selectedLog.properties.old[key]) : '—' }}
                                            </td>
                                            <td class="py-2.5 px-3 bg-emerald-50/40 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-300 break-all">
                                                {{ selectedLog.properties.new?.[key] !== undefined ? JSON.stringify(selectedLog.properties.new[key]) : '—' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Case: Initial Attributes (Created / Deleted) -->
                            <div v-else-if="selectedLog.properties?.attributes" class="border border-slate-200 dark:border-zinc-800 rounded-xl overflow-hidden">
                                <table class="w-full text-left text-xs font-mono">
                                    <thead class="bg-slate-100 dark:bg-zinc-900 border-b border-slate-200 dark:border-zinc-800 text-[11px] font-bold text-slate-600 dark:text-zinc-400 uppercase">
                                        <tr>
                                            <th class="py-2 px-3 w-1/3">Kolom</th>
                                            <th class="py-2 px-3">Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/80">
                                        <tr
                                            v-for="(val, k) in selectedLog.properties.attributes"
                                            :key="k"
                                            class="hover:bg-slate-50 dark:hover:bg-zinc-900/40 transition"
                                        >
                                            <td class="py-2 px-3 font-semibold text-slate-700 dark:text-zinc-300">
                                                {{ k }}
                                            </td>
                                            <td class="py-2 px-3 text-slate-900 dark:text-zinc-100 break-all">
                                                {{ typeof val === 'object' ? JSON.stringify(val) : (val ?? 'null') }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Case: No Detailed Properties -->
                            <div v-else class="py-8 text-center text-slate-400 dark:text-zinc-500 text-xs">
                                Tidak ada rincian atribut data yang tersimpan untuk aktivitas ini.
                            </div>
                        </div>

                        <!-- Tab 2: Raw JSON -->
                        <div v-else-if="activeTab === 'raw'">
                            <pre class="p-4 rounded-xl bg-slate-950 text-slate-200 font-mono text-xs overflow-x-auto border border-slate-800">{{ JSON.stringify(selectedLog.properties, null, 2) || '{}' }}</pre>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-3 border-t border-slate-100 dark:border-zinc-800 flex justify-end">
                        <button
                            type="button"
                            @click="closeDetailModal"
                            class="px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 transition"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
