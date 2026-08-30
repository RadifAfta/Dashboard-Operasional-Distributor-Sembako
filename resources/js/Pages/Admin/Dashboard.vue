<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Activity,
    Shield,
    CheckCircle2,
    Clock,
    Download,
    RefreshCw,
    Server,
    Database,
    Zap,
    AlertCircle,
    ArrowUpRight,
    Search,
    Filter,
    ChevronRight,
    Lock,
    Cpu,
} from 'lucide-vue-next';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    throughput: {
        type: Object,
        required: true,
    },
    services: {
        type: Array,
        required: true,
    },
    auditLogs: {
        type: Array,
        required: true,
    },
    systemStats: {
        type: Object,
        required: true,
    },
});

const selectedRange = ref('today');
const auditFilter = ref('');

// Filter audit logs based on search input
const filteredAuditLogs = computed(() => {
    if (!auditFilter.value) return props.auditLogs;
    const query = auditFilter.value.toLowerCase();
    return props.auditLogs.filter((log) => {
        return (
            log.actor.toLowerCase().includes(query) ||
            log.event.toLowerCase().includes(query) ||
            log.resource.toLowerCase().includes(query) ||
            log.ip_address.toLowerCase().includes(query)
        );
    });
});

// Calculate precision SVG chart line
const chartPoints = computed(() => {
    const data = props.throughput.data;
    const max = props.throughput.peak * 1.15;
    const width = 800;
    const height = 180;

    const points = data.map((val, idx) => {
        const x = (idx / (data.length - 1)) * width;
        const y = height - (val / max) * height;
        return { x, y, val };
    });

    const linePoints = points.map((p) => `${p.x},${p.y}`).join(' ');
    const areaPoints = `0,${height} ${linePoints} ${width},${height}`;

    const baselineY = height - (props.throughput.baseline / max) * height;

    return { points, line: linePoints, area: areaPoints, baselineY };
});

const exportAuditCSV = () => {
    const headers = ['ID', 'Aktor', 'Email', 'Event', 'Resource', 'IP Address', 'Status', 'Timestamp'];
    const rows = props.auditLogs.map((log) => [
        log.id,
        log.actor,
        log.email,
        log.event,
        log.resource,
        log.ip_address,
        log.status,
        log.timestamp,
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map((e) => e.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `audit_log_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <AdminLayout title="Monitoring Operasional & Audit">
        <Head title="Monitoring Operasional & Audit" />

        <div class="space-y-6">
            <!-- 1. Executive Operations Header (Stripe / Linear Grade) -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-5 border-b border-slate-200 dark:border-slate-800">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2 py-0.5 text-[10px] font-mono font-bold uppercase rounded bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            Telemetry Engine v13
                        </span>
                        <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                        <span class="text-xs font-mono text-slate-500 dark:text-slate-400">Node ID: {{ systemStats.host || 'srv-primary-01' }}</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                        Pusat Operasional & Audit Sistem
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Pemantauan telemetri real-time, status integritas layanan, dan jejak audit keamanan perusahaan.
                    </p>
                </div>

                <!-- Action Controls -->
                <div class="flex items-center gap-2.5 shrink-0">
                    <select
                        v-model="selectedRange"
                        class="py-1.5 px-3 text-xs font-semibold bg-white border border-slate-200 rounded-lg focus:ring-1 focus:ring-slate-900 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200"
                    >
                        <option value="today">Hari Ini (24 Jam)</option>
                        <option value="7d">7 Hari Terakhir</option>
                        <option value="q3">Kuartal Berjalan (Q3)</option>
                    </select>

                    <button
                        type="button"
                        @click="exportAuditCSV"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-800 dark:hover:bg-slate-800 transition shadow-2xs"
                    >
                        <Download class="w-3.5 h-3.5" />
                        <span>Ekspor CSV</span>
                    </button>
                </div>
            </div>

            <!-- 2. Precision Operational KPI Cards (Linear / Stripe Style) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Throughput -->
                <div class="spotlight-card p-5 bg-white border border-slate-200 rounded-xl dark:bg-[#0D121F] dark:border-slate-800/90 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-mono font-medium uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Daily Throughput
                        </span>
                        <Activity class="w-4 h-4 text-slate-400" />
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <div class="text-2xl font-mono font-bold tracking-tight text-slate-900 dark:text-white">
                            {{ stats.total_requests_today }}
                        </div>
                        <span class="inline-flex items-center text-xs font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                            <ArrowUpRight class="w-3.5 h-3.5 mr-0.5" />
                            {{ stats.requests_growth }}
                        </span>
                    </div>
                    <div class="mt-2 text-[11px] text-slate-400 font-mono flex items-center justify-between border-t border-slate-100 dark:border-slate-800/80 pt-2">
                        <span>Laju Permintaan API</span>
                        <span class="text-slate-600 dark:text-slate-300 font-semibold">{{ stats.active_sessions }} Sesi Aktif</span>
                    </div>
                </div>

                <!-- Card 2: Pending Approvals -->
                <div class="spotlight-card p-5 bg-white border border-slate-200 rounded-xl dark:bg-[#0D121F] dark:border-slate-800/90 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-mono font-medium uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Pending Review
                        </span>
                        <AlertCircle class="w-4 h-4 text-amber-500" />
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <div class="text-2xl font-mono font-bold tracking-tight text-slate-900 dark:text-white">
                            {{ stats.pending_approvals }} <span class="text-sm font-sans font-normal text-slate-400">Aksi</span>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-mono font-bold rounded bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900">
                            Action Req.
                        </span>
                    </div>
                    <div class="mt-2 text-[11px] text-slate-400 font-mono flex items-center justify-between border-t border-slate-100 dark:border-slate-800/80 pt-2">
                        <span>Otorisasi Hak Akses</span>
                        <Link :href="route('admin.roles.index')" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">
                            Tinjau Sekarang &rarr;
                        </Link>
                    </div>
                </div>

                <!-- Card 3: Security & Compliance -->
                <div class="spotlight-card p-5 bg-white border border-slate-200 rounded-xl dark:bg-[#0D121F] dark:border-slate-800/90 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-mono font-medium uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Compliance Score
                        </span>
                        <Shield class="w-4 h-4 text-emerald-500" />
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <div class="text-2xl font-mono font-bold tracking-tight text-slate-900 dark:text-white">
                            {{ stats.compliance_score }}%
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-mono font-bold rounded bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900">
                            STRICT RBAC
                        </span>
                    </div>
                    <div class="mt-2 text-[11px] text-slate-400 font-mono flex items-center justify-between border-t border-slate-100 dark:border-slate-800/80 pt-2">
                        <span>{{ stats.verified_users }} dari {{ stats.total_users }} Akun</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Audit Lulus</span>
                    </div>
                </div>

                <!-- Card 4: Uptime SLA & Latency -->
                <div class="spotlight-card p-5 bg-white border border-slate-200 rounded-xl dark:bg-[#0D121F] dark:border-slate-800/90 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-mono font-medium uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Uptime & Latency
                        </span>
                        <Server class="w-4 h-4 text-slate-400" />
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <div class="text-2xl font-mono font-bold tracking-tight text-slate-900 dark:text-white">
                            {{ stats.uptime_sla }}
                        </div>
                        <span class="font-mono text-xs font-bold text-slate-500 dark:text-slate-400">
                            {{ stats.avg_latency_ms }} ms avg
                        </span>
                    </div>
                    <div class="mt-2 text-[11px] text-slate-400 font-mono flex items-center justify-between border-t border-slate-100 dark:border-slate-800/80 pt-2">
                        <span>Zero Outage Reported</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold">SLA Tier-1</span>
                    </div>
                </div>
            </div>

            <!-- 3. Real-Time Infrastructure Services Health Cards -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-mono uppercase tracking-wider text-slate-400 dark:text-slate-500 font-bold">
                            Infrastructure Healthcheck
                        </span>
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                    </div>
                    <span class="text-xs font-mono text-slate-400">Pembaruan otomatis per 30 detik</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div
                        v-for="(service, idx) in services"
                        :key="idx"
                        class="p-4 bg-white border border-slate-200 rounded-xl dark:bg-[#0D121F] dark:border-slate-800/90 flex flex-col justify-between"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                    {{ service.name }}
                                </span>
                                <span class="inline-flex items-center gap-1 text-[10px] font-mono font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-1.5 py-0.5 rounded border border-emerald-200 dark:border-emerald-900">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    OK
                                </span>
                            </div>
                            <p class="text-[11px] font-mono text-slate-400">Driver: {{ service.driver }}</p>
                        </div>
                        <div class="mt-3 pt-2 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] font-mono text-slate-500 dark:text-slate-400">
                            <span>{{ service.latency }}</span>
                            <span class="truncate max-w-[110px]">{{ service.pool }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Precision Throughput & Operational Load Chart -->
            <div class="p-5 sm:p-6 bg-white border border-slate-200 rounded-xl dark:bg-[#0D121F] dark:border-slate-800/90 shadow-2xs">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white font-mono uppercase tracking-wider">
                                System Request Telemetry (24-Hour Timeline)
                            </h3>
                            <span class="px-2 py-0.5 text-[10px] font-mono font-bold rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                Peak: {{ throughput.peak.toLocaleString() }} req/hr
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Grafik distribusi beban lalu lintas data per interval jam
                        </p>
                    </div>

                    <div class="flex items-center gap-4 text-xs font-mono text-slate-500 dark:text-slate-400">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-0.5 bg-indigo-600 inline-block"></span>
                            <span>Throughput Aktual</span>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-0.5 bg-slate-300 dark:bg-slate-700 inline-block border-dashed"></span>
                            <span>Baseline Operasional</span>
                        </span>
                    </div>
                </div>

                <!-- High-Precision SVG Grid & Graph -->
                <div class="relative w-full h-48">
                    <svg viewBox="0 0 800 180" class="w-full h-full overflow-visible" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="opGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#4F46E5" stop-opacity="0.25" />
                                <stop offset="100%" stop-color="#4F46E5" stop-opacity="0.0" />
                            </linearGradient>
                        </defs>

                        <!-- Subtle Grid lines -->
                        <line x1="0" y1="45" x2="800" y2="45" stroke="currentColor" class="text-slate-100 dark:text-slate-800/80" stroke-width="1" />
                        <line x1="0" y1="90" x2="800" y2="90" stroke="currentColor" class="text-slate-100 dark:text-slate-800/80" stroke-width="1" />
                        <line x1="0" y1="135" x2="800" y2="135" stroke="currentColor" class="text-slate-100 dark:text-slate-800/80" stroke-width="1" />

                        <!-- Baseline Threshold Line -->
                        <line
                            x1="0"
                            :y1="chartPoints.baselineY"
                            x2="800"
                            :y2="chartPoints.baselineY"
                            stroke="currentColor"
                            class="text-slate-300 dark:text-slate-700"
                            stroke-width="1"
                            stroke-dasharray="3 3"
                        />

                        <!-- Gradient Fill Area -->
                        <polygon :points="chartPoints.area" fill="url(#opGradient)" />

                        <!-- Line Curve -->
                        <polyline
                            :points="chartPoints.line"
                            fill="none"
                            stroke="#4F46E5"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                        <!-- Data Nodes -->
                        <circle
                            v-for="(p, idx) in chartPoints.points"
                            :key="idx"
                            :cx="p.x"
                            :cy="p.y"
                            r="3.5"
                            class="fill-white dark:fill-[#0D121F] stroke-indigo-600 stroke-[2] hover:r-5 transition-all"
                        />
                    </svg>
                </div>

                <div class="flex justify-between mt-3 text-[11px] font-mono text-slate-400 dark:text-slate-500">
                    <span v-for="t in throughput.labels" :key="t">{{ t }}</span>
                </div>
            </div>

            <!-- 5. Enterprise Audit Trail & Security Feed (Siapa Mengakses Apa & Kapan) -->
            <div class="p-5 sm:p-6 bg-white border border-slate-200 rounded-xl dark:bg-[#0D121F] dark:border-slate-800/90 shadow-2xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white font-mono uppercase tracking-wider">
                                Enterprise Audit Trail & Security Log
                            </h3>
                            <span class="px-2 py-0.2 text-[10px] font-mono font-bold rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400">
                                Tamper-Proof
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Catatan audit kronologis seluruh aksi penting admin dan pengguna sistem.
                        </p>
                    </div>

                    <!-- Search Filter in Audit Table -->
                    <div class="relative w-full sm:w-64">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                        <input
                            v-model="auditFilter"
                            type="text"
                            placeholder="Cari aktor, event, IP..."
                            class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:ring-1 focus:ring-slate-900 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200"
                        />
                    </div>
                </div>

                <!-- Audit Log Table -->
                <div class="overflow-x-auto border border-slate-200 dark:border-slate-800/90 rounded-lg">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] font-mono font-bold uppercase text-slate-500 border-b border-slate-200 dark:bg-slate-900/60 dark:text-slate-400 dark:border-slate-800">
                            <tr>
                                <th class="py-2.5 px-4">Log ID</th>
                                <th class="py-2.5 px-4">Aktor / Pengguna</th>
                                <th class="py-2.5 px-4">Kode Event</th>
                                <th class="py-2.5 px-4">Sasaran Objek</th>
                                <th class="py-2.5 px-4">IP Origin</th>
                                <th class="py-2.5 px-4">Status</th>
                                <th class="py-2.5 px-4 text-right">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-mono">
                            <tr
                                v-for="log in filteredAuditLogs"
                                :key="log.id"
                                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors"
                            >
                                <td class="py-3 px-4 font-bold text-slate-500 dark:text-slate-400">
                                    {{ log.id }}
                                </td>
                                <td class="py-3 px-4 font-sans">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center font-bold text-[10px] text-slate-700 dark:text-slate-200">
                                            {{ log.actor.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 dark:text-white leading-none">{{ log.actor }}</p>
                                            <p class="text-[10px] text-slate-400 leading-tight">{{ log.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                                        {{ log.event }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-sans text-slate-600 dark:text-slate-300">
                                    {{ log.resource }}
                                </td>
                                <td class="py-3 px-4 text-slate-400">
                                    {{ log.ip_address }}
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded text-[10px] font-bold',
                                            log.status === 'SUCCESS'
                                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900'
                                                : 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900'
                                        ]"
                                    >
                                        {{ log.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right text-slate-400">
                                    {{ log.timestamp }}
                                </td>
                            </tr>

                            <tr v-if="filteredAuditLogs.length === 0">
                                <td colspan="7" class="py-8 text-center text-slate-400 font-sans">
                                    Tidak ada catatan log yang sesuai dengan filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-400 pt-2 font-mono">
                    <span>Menampilkan {{ filteredAuditLogs.length }} dari {{ auditLogs.length }} riwayat audit terbaru</span>
                    <button
                        type="button"
                        @click="exportAuditCSV"
                        class="text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1 font-sans font-semibold"
                    >
                        <span>Unduh Arsip Audit Lengkap (.CSV)</span>
                        <ChevronRight class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
