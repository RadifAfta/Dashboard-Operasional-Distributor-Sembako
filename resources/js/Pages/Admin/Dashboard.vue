<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatCard from '@/Components/Admin/StatCard.vue';
import Badge from '@/Components/Admin/Badge.vue';
import {
    Users,
    Shield,
    CheckCircle2,
    Server,
    UserPlus,
    Settings,
    ArrowRight,
    TrendingUp,
    Activity,
} from 'lucide-vue-next';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    chartData: {
        type: Object,
        required: true,
    },
    recentUsers: {
        type: Array,
        required: true,
    },
    systemStats: {
        type: Object,
        required: true,
    },
});

// Calculate SVG Chart points
const chartPoints = computed(() => {
    const data = props.chartData.series[0].data;
    const max = Math.max(...data) * 1.15;
    const width = 600;
    const height = 180;

    const points = data.map((val, idx) => {
        const x = (idx / (data.length - 1)) * width;
        const y = height - (val / max) * height;
        return `${x},${y}`;
    });

    return {
        line: points.join(' '),
        area: `0,${height} ${points.join(' ')} ${width},${height}`,
    };
});
</script>

<template>
    <AdminLayout title="Dashboard Overview">
        <Head title="Admin Dashboard" />

        <div class="space-y-6">
            <!-- Welcome Hero Banner -->
            <div class="relative overflow-hidden p-6 sm:p-8 bg-gradient-to-r from-indigo-600 via-indigo-700 to-violet-800 rounded-3xl text-white shadow-xl shadow-indigo-600/10">
                <div class="relative z-10 max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 text-xs font-semibold rounded-full bg-white/10 backdrop-blur-sm border border-white/10 mb-3">
                        <Activity class="w-3.5 h-3.5 text-indigo-200" />
                        <span>Laravel 13 Starter Template Siap Digunakan</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                        Selamat Datang di Template Dashboard Admin!
                    </h2>
                    <p class="mt-2 text-sm text-indigo-100/90 leading-relaxed">
                        Template ini sudah dilengkapi dengan sistem Role & Permission (Spatie), manajemen user, komponen DataTable reusable, dark mode, dan pengaturan dinamis.
                    </p>
                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        <Link
                            :href="route('admin.users.index')"
                            class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-indigo-900 bg-white rounded-xl shadow hover:bg-indigo-50 transition"
                        >
                            <UserPlus class="w-4 h-4" />
                            <span>Kelola Pengguna</span>
                        </Link>
                        <Link
                            :href="route('admin.settings.index')"
                            class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white bg-white/15 hover:bg-white/25 rounded-xl border border-white/20 transition"
                        >
                            <Settings class="w-4 h-4" />
                            <span>Pengaturan Aplikasi</span>
                        </Link>
                    </div>
                </div>

                <!-- Abstract decorative circles -->
                <div class="absolute -right-10 -bottom-10 w-72 h-72 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>
                <div class="absolute right-32 -top-10 w-48 h-48 rounded-full bg-violet-400/10 blur-xl pointer-events-none"></div>
            </div>

            <!-- KPI Metric Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard
                    title="Total Pengguna"
                    :value="stats.total_users"
                    trend="+14% bln ini"
                    :trendUp="true"
                    description="Akun terdaftar di database"
                    iconBg="bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400"
                >
                    <template #icon>
                        <Users class="w-5 h-5" />
                    </template>
                </StatCard>

                <StatCard
                    title="Role & Hak Akses"
                    :value="stats.total_roles"
                    description="Tingkatan role aktif"
                    iconBg="bg-violet-50 text-violet-600 dark:bg-violet-950/50 dark:text-violet-400"
                >
                    <template #icon>
                        <Shield class="w-5 h-5" />
                    </template>
                </StatCard>

                <StatCard
                    title="User Terverifikasi"
                    :value="`${stats.active_percentage}%`"
                    :trend="`${stats.verified_users} user`"
                    :trendUp="true"
                    description="Email terverifikasi"
                    iconBg="bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400"
                >
                    <template #icon>
                        <CheckCircle2 class="w-5 h-5" />
                    </template>
                </StatCard>

                <StatCard
                    title="Sistem & Database"
                    :value="systemStats.db_driver.toUpperCase()"
                    :description="`Laravel v${systemStats.laravel_version}`"
                    iconBg="bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400"
                >
                    <template #icon>
                        <Server class="w-5 h-5" />
                    </template>
                </StatCard>
            </div>

            <!-- Analytics Chart & System Status Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- User Growth Chart (2 Cols) -->
                <div class="lg:col-span-2 p-6 bg-white border border-slate-200/80 rounded-2xl shadow-sm dark:bg-slate-900 dark:border-slate-800/80">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                Tren Pertumbuhan Pengguna
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Simulasi data analitik bulanan
                            </p>
                        </div>
                        <div class="flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2.5 py-1 rounded-lg">
                            <TrendingUp class="w-3.5 h-3.5" />
                            <span>+24.8% YoY</span>
                        </div>
                    </div>

                    <!-- Clean SVG Area Chart -->
                    <div class="relative w-full h-52 mt-4">
                        <svg viewBox="0 0 600 180" class="w-full h-full overflow-visible" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="areaGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="#6366f1" stop-opacity="0.35" />
                                    <stop offset="100%" stop-color="#6366f1" stop-opacity="0.0" />
                                </linearGradient>
                            </defs>
                            <!-- Grid lines -->
                            <line x1="0" y1="45" x2="600" y2="45" stroke="currentColor" class="text-slate-100 dark:text-slate-800/80" stroke-dasharray="3 3" />
                            <line x1="0" y1="90" x2="600" y2="90" stroke="currentColor" class="text-slate-100 dark:text-slate-800/80" stroke-dasharray="3 3" />
                            <line x1="0" y1="135" x2="600" y2="135" stroke="currentColor" class="text-slate-100 dark:text-slate-800/80" stroke-dasharray="3 3" />

                            <!-- Gradient Area -->
                            <polygon :points="chartPoints.area" fill="url(#areaGradient)" />

                            <!-- Smooth Curve Line -->
                            <polyline
                                :points="chartPoints.line"
                                fill="none"
                                stroke="#6366f1"
                                stroke-width="3"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>

                    <!-- Month Labels -->
                    <div class="flex justify-between mt-3 text-[11px] font-medium text-slate-400 dark:text-slate-500">
                        <span v-for="month in chartData.labels" :key="month">{{ month }}</span>
                    </div>
                </div>

                <!-- System Info Card (1 Col) -->
                <div class="p-6 bg-white border border-slate-200/80 rounded-2xl shadow-sm dark:bg-slate-900 dark:border-slate-800/80 flex flex-col justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">
                            Informasi Lingkungan
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-5">
                            Status runtime stack teknologi
                        </p>

                        <div class="space-y-3.5 text-xs">
                            <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 dark:border-slate-800">
                                <span class="text-slate-500 dark:text-slate-400">Framework</span>
                                <Badge variant="primary">Laravel v{{ systemStats.laravel_version }}</Badge>
                            </div>
                            <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 dark:border-slate-800">
                                <span class="text-slate-500 dark:text-slate-400">PHP Version</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ systemStats.php_version }}</span>
                            </div>
                            <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 dark:border-slate-800">
                                <span class="text-slate-500 dark:text-slate-400">Database Driver</span>
                                <Badge variant="success">{{ systemStats.db_driver.toUpperCase() }}</Badge>
                            </div>
                            <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 dark:border-slate-800">
                                <span class="text-slate-500 dark:text-slate-400">App Environment</span>
                                <span class="font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">{{ systemStats.environment }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Frontend Adapter</span>
                                <span class="font-semibold text-indigo-600 dark:text-indigo-400">Inertia.js + Vue 3</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <Link
                            :href="route('admin.settings.index')"
                            class="flex items-center justify-center gap-2 w-full py-2 px-3 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition"
                        >
                            <span>Buka Konfigurasi Lengkap</span>
                            <ArrowRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Recent Users Section -->
            <div class="p-6 bg-white border border-slate-200/80 rounded-2xl shadow-sm dark:bg-slate-900 dark:border-slate-800/80">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            Pengguna Terbaru
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            5 user terakhir yang terdaftar pada sistem
                        </p>
                    </div>
                    <Link
                        :href="route('admin.users.index')"
                        class="text-xs font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 flex items-center gap-1"
                    >
                        <span>Lihat Semua</span>
                        <ArrowRight class="w-3.5 h-3.5" />
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs font-semibold uppercase text-slate-400 border-b border-slate-100 dark:border-slate-800">
                            <tr>
                                <th class="pb-3">Pengguna</th>
                                <th class="pb-3">Role</th>
                                <th class="pb-3">Tanggal Dibuat</th>
                                <th class="pb-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <tr v-for="u in recentUsers" :key="u.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                                <td class="py-3 flex items-center gap-3">
                                    <img
                                        :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(u.name)}&color=7F9CF5&background=EBF4FF`"
                                        class="w-8 h-8 rounded-lg"
                                    />
                                    <div>
                                        <p class="font-bold text-xs text-slate-900 dark:text-white">{{ u.name }}</p>
                                        <p class="text-[11px] text-slate-400">{{ u.email }}</p>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <Badge :variant="u.roles?.[0]?.name === 'Super Admin' ? 'danger' : (u.roles?.[0]?.name === 'Admin' ? 'primary' : 'neutral')" size="sm">
                                        {{ u.roles?.[0]?.name || 'User' }}
                                    </Badge>
                                </td>
                                <td class="py-3 text-xs text-slate-500 dark:text-slate-400">
                                    {{ new Date(u.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                </td>
                                <td class="py-3 text-right">
                                    <Link
                                        :href="route('admin.users.index')"
                                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                                    >
                                        Detail
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
