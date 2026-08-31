<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    LayoutDashboard,
    ShoppingCart,
    Package,
    Clock,
    AlertTriangle,
    TrendingUp,
    ArrowUpRight,
    ArrowDownRight,
    CreditCard,
    DollarSign,
    Wallet,
    FileText,
    Boxes,
    ChevronRight,
    RotateCcw,
    Plus,
    CheckCircle2,
    Calendar,
    ArrowRight,
    BarChart3,
} from 'lucide-vue-next';

const props = defineProps({
    metrics: {
        type: Object,
        required: true,
    },
    salesChart: {
        type: Object,
        required: true,
    },
    quickAlertProducts: {
        type: Array,
        required: true,
    },
    recentTransactions: {
        type: Array,
        default: () => [],
    },
});

// Format Currency
const formatRupiah = (val) => {
    return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
};

// Interactive Chart State
const hoveredBar = ref(null);
const chartMax = computed(() => {
    const maxVal = Math.max(...props.salesChart.total_daily_series, 1000000);
    return maxVal * 1.15;
});

// Quick shortcut restock modal
const isRestockModalOpen = ref(false);
const restockProduct = ref(null);
const restockQty = ref(10);

const openRestockModal = (product) => {
    restockProduct.value = product;
    restockQty.value = 10;
    isRestockModalOpen.value = true;
};

const submitRestock = () => {
    if (!restockProduct.value || restockQty.value <= 0) return;
    router.put(
        route('admin.products.update', restockProduct.value.id),
        {
            sku: restockProduct.value.sku,
            name: restockProduct.value.name,
            category: restockProduct.value.category,
            cost_price: restockProduct.value.cost_price,
            selling_price: restockProduct.value.selling_price,
            base_unit: restockProduct.value.base_unit,
            min_stock: restockProduct.value.min_stock,
            current_stock: restockProduct.value.current_stock + Number(restockQty.value),
            status: 'active',
        },
        {
            onSuccess: () => {
                isRestockModalOpen.value = false;
                restockProduct.value = null;
            },
        }
    );
};
</script>

<template>
    <AdminLayout title="Dashboard Operasional">
        <Head title="Dashboard Operasional - Distributor Sembako" />

        <div class="space-y-6">
            <!-- Header Banner Operasional & Quick Action Bar -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-zinc-950 via-[#141417] to-zinc-950 text-white p-6 shadow-xl border border-zinc-800">
                <div class="absolute -right-12 -top-12 w-64 h-64 bg-brand/15 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute right-32 -bottom-16 w-48 h-48 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand/10 border border-brand/30 text-brand text-xs font-semibold uppercase tracking-wider mb-2">
                            <span class="w-2 h-2 rounded-full bg-brand animate-pulse"></span>
                            Pusat Distribusi & Grosir Sembako Aktif
                        </div>
                        <h1 class="text-2xl lg:text-3xl font-bold tracking-tight text-white">
                            Monitor Operasional Toko Hari Ini
                        </h1>
                        <p class="text-zinc-400 text-sm mt-1 max-w-xl">
                            Pantau perputaran omzet kasir, margin laba kotor, status nota jatuh tempo, dan peringatan stok kritis secara real-time.
                        </p>
                    </div>

                    <!-- Quick Action Shortcuts -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <Link
                            :href="route('admin.pos.index')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-brand text-white font-bold text-sm shadow-sm shadow-brand/30 hover:opacity-90 transition-all transform active:scale-95"
                        >
                            <ShoppingCart class="w-4 h-4" />
                            <span>Buka Kasir POS</span>
                        </Link>
                        <Link
                            :href="route('admin.closure.index')"
                            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-zinc-200 border border-zinc-800 text-sm font-semibold transition-all"
                        >
                            <FileText class="w-4 h-4 text-brand" />
                            <span>Rekap Kas Laci</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- 1. Empat Metric Cards Utama (Pure Neutral Dark #141417) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 lg:gap-5">
                <!-- Card 1: Omzet Hari Ini -->
                <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 p-5 shadow-2xs hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400">
                            Omzet Hari Ini
                        </span>
                        <div class="w-10 h-10 rounded-xl bg-brand/10 text-brand flex items-center justify-center border border-brand/20">
                            <DollarSign class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl lg:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            {{ formatRupiah(metrics.omzet_today) }}
                        </div>
                        <div class="flex items-center gap-1.5 mt-2 text-xs">
                            <span
                                v-if="metrics.omzet_growth_pct >= 0"
                                class="inline-flex items-center gap-0.5 font-semibold text-emerald-600 dark:text-emerald-400"
                            >
                                <ArrowUpRight class="w-3.5 h-3.5" />
                                +{{ metrics.omzet_growth_pct }}%
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-0.5 font-semibold text-rose-600 dark:text-rose-400"
                            >
                                <ArrowDownRight class="w-3.5 h-3.5" />
                                {{ metrics.omzet_growth_pct }}%
                            </span>
                            <span class="text-slate-400 dark:text-zinc-500">vs kemarin</span>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 h-1 bg-brand"></div>
                </div>

                <!-- Card 2: Estimasi Laba Kotor -->
                <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 p-5 shadow-2xs hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400">
                            Estimasi Laba Kotor
                        </span>
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center border border-emerald-500/20">
                            <TrendingUp class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl lg:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            {{ formatRupiah(metrics.profit_today) }}
                        </div>
                        <div class="flex items-center gap-1.5 mt-2 text-xs">
                            <span class="px-1.5 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 font-bold border border-emerald-200 dark:border-emerald-900/50">
                                Margin {{ metrics.profit_margin_pct }}%
                            </span>
                            <span class="text-slate-400 dark:text-zinc-500">bersih dari HPP</span>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 h-1 bg-emerald-500"></div>
                </div>

                <!-- Card 3: Total Piutang Jatuh Tempo -->
                <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 p-5 shadow-2xs hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400">
                            Piutang Jatuh Tempo
                        </span>
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-500 dark:bg-amber-500/20 flex items-center justify-center border border-amber-500/20">
                            <Clock class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl lg:text-3xl font-extrabold text-amber-600 dark:text-amber-400 tracking-tight">
                            {{ formatRupiah(metrics.overdue_debt_total) }}
                        </div>
                        <div class="flex items-center gap-1.5 mt-2 text-xs">
                            <span class="px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 font-bold text-[11px] border border-rose-200 dark:border-rose-900/50">
                                {{ metrics.overdue_debt_count }} Nota Overdue
                            </span>
                            <Link :href="route('admin.debts.index', { status: 'overdue' })" class="text-brand hover:underline font-semibold ml-auto flex items-center gap-0.5">
                                Tagih <ChevronRight class="w-3 h-3" />
                            </Link>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 h-1 bg-amber-500"></div>
                </div>

                <!-- Card 4: Jumlah Barang Kritis (Badge Merah) -->
                <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 p-5 shadow-2xs hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400">
                            Barang Kritis (Restock)
                        </span>
                        <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 flex items-center justify-center border border-rose-500/20">
                            <AlertTriangle class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl lg:text-3xl font-extrabold text-rose-600 dark:text-rose-400 tracking-tight">
                                {{ metrics.critical_stock_count }}
                            </span>
                            <span class="px-2.5 py-1 rounded-full bg-rose-600 text-white font-extrabold text-xs shadow-sm shadow-rose-600/30">
                                Stok Menipis
                            </span>
                        </div>
                        <div class="flex items-center justify-between mt-2 text-xs">
                            <span class="text-slate-400 dark:text-zinc-500">Di bawah batas minimum</span>
                            <Link :href="route('admin.products.index', { is_critical: 1 })" class="text-brand hover:underline font-semibold flex items-center gap-0.5">
                                Lihat Semua <ChevronRight class="w-3 h-3" />
                            </Link>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 h-1 bg-rose-500"></div>
                </div>
            </div>

            <!-- 2. Grafik Penjualan 7 Hari & Ringkasan Penjualan -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Chart Batang Komparasi Tunai vs Tempo -->
                <div class="lg:col-span-2 rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 p-6 shadow-2xs flex flex-col justify-between">
                    <div>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-4 border-b border-slate-200/80 dark:border-zinc-800/80">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <BarChart3 class="w-4 h-4 text-brand" />
                                    Tren Penjualan 7 Hari Terakhir
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">
                                    Komparasi penerimaan Tunai/Transfer vs Transaksi Tempo (Kredit)
                                </p>
                            </div>

                            <!-- Legend Chart -->
                            <div class="flex items-center gap-4 text-xs font-semibold">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-3 h-3 rounded-sm bg-brand"></span>
                                    <span class="text-slate-600 dark:text-zinc-300">Tunai & Transfer</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-3 h-3 rounded-sm bg-amber-500"></span>
                                    <span class="text-slate-600 dark:text-zinc-300">Tempo (Kredit)</span>
                                </div>
                            </div>
                        </div>

                        <!-- Bar Chart Container -->
                        <div class="mt-6 relative h-64 flex items-end justify-between gap-2 sm:gap-4 px-2 pt-8">
                            <div
                                v-for="(label, idx) in salesChart.labels"
                                :key="idx"
                                class="flex-1 flex flex-col items-center h-full justify-end group cursor-pointer relative"
                                @mouseenter="hoveredBar = idx"
                                @mouseleave="hoveredBar = null"
                            >
                                <!-- Interactive Hover Tooltip -->
                                <div
                                    v-if="hoveredBar === idx"
                                    class="absolute -top-16 z-30 bg-zinc-950 text-white text-[11px] rounded-xl py-1.5 px-3 shadow-xl whitespace-nowrap border border-zinc-800 pointer-events-none transform -translate-x-1/2 left-1/2"
                                >
                                    <div class="font-bold border-b border-zinc-800 pb-1 mb-1 text-zinc-200">
                                        {{ label }} • Total: {{ formatRupiah(salesChart.total_daily_series[idx]) }}
                                    </div>
                                    <div class="text-emerald-400">Tunai: {{ formatRupiah(salesChart.cash_series[idx]) }}</div>
                                    <div class="text-amber-400">Tempo: {{ formatRupiah(salesChart.credit_series[idx]) }}</div>
                                </div>

                                <!-- Dual Bars: Cash vs Credit -->
                                <div class="w-full flex items-end justify-center gap-1 sm:gap-1.5 h-full">
                                    <!-- Cash Bar -->
                                    <div
                                        class="w-1/2 max-w-[24px] rounded-t-md bg-brand hover:opacity-90 transition-all duration-300 shadow-sm"
                                        :style="{ height: `${Math.max(4, (salesChart.cash_series[idx] / chartMax) * 100)}%` }"
                                    ></div>

                                    <!-- Credit Bar -->
                                    <div
                                        class="w-1/2 max-w-[24px] rounded-t-md bg-amber-500 hover:opacity-90 transition-all duration-300 shadow-sm"
                                        :style="{ height: `${Math.max(4, (salesChart.credit_series[idx] / chartMax) * 100)}%` }"
                                    ></div>
                                </div>

                                <!-- Label Hari -->
                                <span class="text-[11px] font-semibold text-slate-500 dark:text-zinc-400 mt-2.5 truncate w-full text-center">
                                    {{ label }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Ringkasan 7 Hari -->
                    <div class="mt-4 pt-4 border-t border-slate-200/80 dark:border-zinc-800/80 grid grid-cols-3 gap-2 text-center text-xs">
                        <div class="p-2 rounded-xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/60 dark:border-zinc-800/60">
                            <span class="text-slate-500 dark:text-zinc-400 block text-[11px]">Total 7 Hari</span>
                            <span class="font-bold text-slate-900 dark:text-white text-sm">
                                {{ formatRupiah(salesChart.grand_total) }}
                            </span>
                        </div>
                        <div class="p-2 rounded-xl bg-brand/5 border border-brand/20">
                            <span class="text-brand block text-[11px]">Porsi Tunai</span>
                            <span class="font-bold text-slate-900 dark:text-white text-sm">
                                {{ formatRupiah(salesChart.cash_total) }}
                            </span>
                        </div>
                        <div class="p-2 rounded-xl bg-amber-500/5 border border-amber-500/20">
                            <span class="text-amber-600 dark:text-amber-400 block text-[11px]">Porsi Tempo</span>
                            <span class="font-bold text-slate-900 dark:text-white text-sm">
                                {{ formatRupiah(salesChart.credit_total) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 3. Tabel Quick Alert: 5 Barang Stok Mendekati 0 -->
                <div class="rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 p-5 shadow-2xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200/80 dark:border-zinc-800/80">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <AlertTriangle class="w-4 h-4 text-rose-500" />
                                    Quick Alert Stok Kritis
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">5 barang paling mendekati 0</p>
                            </div>
                            <span class="px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 text-xs font-bold border border-rose-200 dark:border-rose-900/40">
                                Kritis
                            </span>
                        </div>

                        <!-- Item List -->
                        <div class="divide-y divide-slate-200/60 dark:divide-zinc-800/60 mt-2">
                            <div
                                v-for="item in quickAlertProducts"
                                :key="item.id"
                                class="py-3 flex items-center justify-between gap-3 group"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-bold text-slate-900 dark:text-zinc-100 truncate block">
                                            {{ item.name }}
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-zinc-400 mt-0.5 flex items-center gap-2">
                                        <span>{{ item.category }}</span>
                                        <span>•</span>
                                        <span class="text-rose-600 dark:text-rose-400 font-semibold">
                                            Sisa: {{ item.current_stock }} {{ item.base_unit }}
                                        </span>
                                        <span class="text-slate-400 dark:text-zinc-500">(Min: {{ item.min_stock }})</span>
                                    </div>

                                    <!-- Visual Stock Progress Bar -->
                                    <div class="w-full bg-slate-100 dark:bg-zinc-800 h-1.5 rounded-full mt-1.5 overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all duration-500"
                                            :class="item.current_stock <= item.min_stock ? 'bg-rose-500' : 'bg-amber-500'"
                                            :style="{ width: `${Math.min(100, (item.current_stock / (item.min_stock * 1.5)) * 100)}%` }"
                                        ></div>
                                    </div>
                                </div>

                                <!-- Shortcut Restock Button -->
                                <button
                                    @click="openRestockModal(item)"
                                    class="shrink-0 p-1.5 rounded-lg bg-slate-100 dark:bg-zinc-800 hover:bg-brand hover:text-white text-slate-600 dark:text-zinc-300 transition-colors text-xs font-semibold flex items-center gap-1 cursor-pointer"
                                    title="Tambah Stok Cepat"
                                >
                                    <Plus class="w-3.5 h-3.5" />
                                    <span class="hidden sm:inline">Restock</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-200/80 dark:border-zinc-800/80">
                        <Link
                            :href="route('admin.products.index')"
                            class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-zinc-900 hover:bg-slate-100 dark:hover:bg-zinc-800 text-xs font-semibold text-brand text-center block transition-colors"
                        >
                            Buka Master Data Barang & Multi-Satuan →
                        </Link>
                    </div>
                </div>
            </div>

            <!-- 4. Transaksi Terbaru & Shortcut Cepat -->
            <div class="rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 p-6 shadow-2xs">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200/80 dark:border-zinc-800/80">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <Clock class="w-4 h-4 text-brand" />
                            Aktivitas Transaksi Kasir Terbaru
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">Pantauan nota penjualan langsung & tempo hari ini</p>
                    </div>
                    <Link
                        :href="route('admin.pos.index')"
                        class="text-xs font-bold text-brand hover:underline flex items-center gap-1"
                    >
                        Transaksi Baru <ArrowRight class="w-3 h-3" />
                    </Link>
                </div>

                <div class="overflow-x-auto mt-3">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-500 dark:text-zinc-400 border-b border-slate-200/80 dark:border-zinc-800/80 font-semibold">
                                <th class="py-2.5 px-3">No. Nota</th>
                                <th class="py-2.5 px-3">Pelanggan</th>
                                <th class="py-2.5 px-3">Metode Bayar</th>
                                <th class="py-2.5 px-3">Status</th>
                                <th class="py-2.5 px-3 text-right">Total Transaksi</th>
                                <th class="py-2.5 px-3 text-right">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60 dark:divide-zinc-800/60">
                            <tr
                                v-for="trx in recentTransactions"
                                :key="trx.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-[#1c1c21] transition-colors"
                            >
                                <td class="py-3 px-3 font-mono font-bold text-slate-900 dark:text-white">
                                    {{ trx.invoice_number }}
                                </td>
                                <td class="py-3 px-3 font-medium text-slate-800 dark:text-zinc-200">
                                    {{ trx.customer_name }}
                                </td>
                                <td class="py-3 px-3">
                                    <span
                                        v-if="trx.payment_method === 'cash'"
                                        class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-semibold border border-emerald-200 dark:border-emerald-900/40"
                                    >
                                        Tunai
                                    </span>
                                    <span
                                        v-else-if="trx.payment_method === 'transfer'"
                                        class="px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 font-semibold border border-blue-200 dark:border-blue-900/40"
                                    >
                                        QRIS / Transfer
                                    </span>
                                    <span
                                        v-else
                                        class="px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 font-semibold border border-amber-200 dark:border-amber-900/40"
                                    >
                                        Tempo
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    <span
                                        v-if="trx.payment_status === 'paid'"
                                        class="px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-semibold border border-emerald-200 dark:border-emerald-900/40"
                                    >
                                        Lunas
                                    </span>
                                    <span
                                        v-else-if="trx.is_overdue"
                                        class="px-2 py-0.5 rounded-full bg-rose-600 text-white font-bold animate-pulse"
                                    >
                                        JATUH TEMPO
                                    </span>
                                    <span
                                        v-else
                                        class="px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 font-semibold border border-amber-200 dark:border-amber-900/40"
                                    >
                                        Belum Lunas
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right font-extrabold text-slate-900 dark:text-white">
                                    {{ formatRupiah(trx.total_amount) }}
                                </td>
                                <td class="py-3 px-3 text-right text-slate-400 dark:text-zinc-500">
                                    {{ trx.created_at }} ({{ trx.time_ago }})
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Restock Modal -->
        <div
            v-if="isRestockModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
        >
            <div class="bg-white dark:bg-[#141417] rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-zinc-800">
                <h4 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <Plus class="w-5 h-5 text-brand" />
                    Tambah Stok Cepat (Restock)
                </h4>
                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">
                    {{ restockProduct?.name }} (Satuan dasar: {{ restockProduct?.base_unit }})
                </p>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1">
                            Jumlah Tambahan Masuk Gudang ({{ restockProduct?.base_unit }})
                        </label>
                        <input
                            v-model.number="restockQty"
                            type="number"
                            min="1"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white font-bold text-base focus:ring-2 focus:ring-brand/20 focus:border-brand"
                        />
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-900/60 text-xs text-slate-500 dark:text-zinc-400 border border-slate-200/60 dark:border-zinc-800">
                        Stok saat ini: <strong class="text-rose-600 dark:text-rose-400">{{ restockProduct?.current_stock }}</strong> →
                        Setelah restock: <strong class="text-emerald-600 dark:text-emerald-400">{{ (restockProduct?.current_stock || 0) + Number(restockQty || 0) }}</strong> {{ restockProduct?.base_unit }}
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2">
                    <button
                        @click="isRestockModalOpen = false"
                        type="button"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 hover:bg-slate-200 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-300 font-semibold text-xs transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitRestock"
                        type="button"
                        class="px-4 py-2 rounded-xl bg-brand hover:opacity-90 text-white font-bold text-xs shadow-sm shadow-brand/20 transition-all cursor-pointer"
                    >
                        Simpan Stok Masuk
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
