<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    ShoppingCart,
    Package,
    Clock,
    AlertTriangle,
    TrendingUp,
    ArrowUpRight,
    ArrowDownRight,
    DollarSign,
    FileText,
    Boxes,
    ChevronRight,
    Plus,
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

const formattedCurrentDate = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date());
});

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
            <!-- Professional Clean Header (No bloated AI-slop hero banner) -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-zinc-100 tracking-tight">
                        Dashboard Operasional
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">
                        Ikhtisar penjualan harian, margin laba kotor, piutang tempo, dan monitoring persediaan gudang.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <div class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#141417] text-xs text-slate-600 dark:text-zinc-400 font-medium shadow-2xs">
                        <Calendar class="w-3.5 h-3.5 text-slate-400" />
                        <span>{{ formattedCurrentDate }}</span>
                    </div>

                    <Link
                        :href="route('admin.pos.index')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold text-white bg-brand hover:opacity-90 rounded-lg shadow-2xs transition"
                    >
                        <ShoppingCart class="w-3.5 h-3.5" />
                        <span>Buka Kasir POS</span>
                    </Link>

                    <Link
                        :href="route('admin.closure.index')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-slate-700 dark:text-zinc-300 bg-white dark:bg-[#141417] border border-slate-200 dark:border-zinc-800 hover:bg-slate-50 dark:hover:bg-zinc-800 rounded-lg shadow-2xs transition"
                    >
                        <FileText class="w-3.5 h-3.5 text-slate-400" />
                        <span>Tutup Buku</span>
                    </Link>
                </div>
            </div>

            <!-- 1. Empat Metric Cards Utama (Minimalist, Clean, High-Craft) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <!-- Card 1: Omzet Hari Ini -->
                <div class="p-4 sm:p-5 rounded-xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500 dark:text-zinc-400">
                            Omzet Hari Ini
                        </span>
                        <DollarSign class="w-4 h-4 text-slate-400 dark:text-zinc-500" />
                    </div>
                    <div class="mt-2">
                        <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-zinc-100">
                            {{ formatRupiah(metrics.omzet_today) }}
                        </div>
                        <div class="flex items-center gap-1.5 mt-1.5 text-xs text-slate-500 dark:text-zinc-400">
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
                </div>

                <!-- Card 2: Estimasi Laba Kotor -->
                <div class="p-4 sm:p-5 rounded-xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500 dark:text-zinc-400">
                            Estimasi Laba Kotor
                        </span>
                        <TrendingUp class="w-4 h-4 text-slate-400 dark:text-zinc-500" />
                    </div>
                    <div class="mt-2">
                        <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-zinc-100">
                            {{ formatRupiah(metrics.profit_today) }}
                        </div>
                        <div class="flex items-center gap-1.5 mt-1.5 text-xs">
                            <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                                Margin {{ metrics.profit_margin_pct }}%
                            </span>
                            <span class="text-slate-400 dark:text-zinc-500">dari omzet</span>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Total Piutang Jatuh Tempo -->
                <div class="p-4 sm:p-5 rounded-xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500 dark:text-zinc-400">
                            Piutang Jatuh Tempo
                        </span>
                        <Clock class="w-4 h-4 text-slate-400 dark:text-zinc-500" />
                    </div>
                    <div class="mt-2">
                        <div class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400">
                            {{ formatRupiah(metrics.overdue_debt_total) }}
                        </div>
                        <div class="flex items-center justify-between mt-1.5 text-xs">
                            <span class="font-medium text-rose-600 dark:text-rose-400">
                                {{ metrics.overdue_debt_count }} nota lewat tempo
                            </span>
                            <Link :href="route('admin.debts.index', { status: 'overdue' })" class="text-brand hover:underline font-semibold flex items-center gap-0.5">
                                Periksa <ChevronRight class="w-3 h-3" />
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Jumlah Barang Kritis -->
                <div class="p-4 sm:p-5 rounded-xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500 dark:text-zinc-400">
                            Stok Kritis Gudang
                        </span>
                        <AlertTriangle class="w-4 h-4 text-rose-500" />
                    </div>
                    <div class="mt-2">
                        <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-zinc-100">
                            {{ metrics.critical_stock_count }} <span class="text-xs font-normal text-slate-500 dark:text-zinc-400">Produk</span>
                        </div>
                        <div class="flex items-center justify-between mt-1.5 text-xs">
                            <span class="text-rose-600 dark:text-rose-400 font-medium">
                                Di bawah batas minimum
                            </span>
                            <Link :href="route('admin.products.index', { is_critical: 1 })" class="text-brand hover:underline font-semibold flex items-center gap-0.5">
                                Detail <ChevronRight class="w-3 h-3" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Grafik Penjualan 7 Hari & Quick Alert Stok Kritis -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Chart Batang Komparasi Tunai vs Tempo -->
                <div class="lg:col-span-2 rounded-xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 p-5 shadow-2xs flex flex-col justify-between">
                    <div>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3.5 border-b border-slate-200/80 dark:border-zinc-800/80">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-zinc-100">
                                    Tren Penjualan 7 Hari Terakhir
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">
                                    Komparasi transaksi Tunai/Transfer dengan Transaksi Tempo (Kredit)
                                </p>
                            </div>

                            <!-- Legend Chart -->
                            <div class="flex items-center gap-3.5 text-xs">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-xs bg-brand"></span>
                                    <span class="text-slate-600 dark:text-zinc-400">Tunai & Transfer</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-xs bg-amber-500"></span>
                                    <span class="text-slate-600 dark:text-zinc-400">Tempo</span>
                                </div>
                            </div>
                        </div>

                        <!-- Bar Chart Container -->
                        <div class="mt-6 relative h-60 flex items-end justify-between gap-2 sm:gap-4 px-2 pt-6">
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
                                    class="absolute -top-14 z-30 bg-zinc-950 text-white text-[11px] rounded-lg py-1.5 px-3 shadow-xl whitespace-nowrap border border-zinc-800 pointer-events-none transform -translate-x-1/2 left-1/2"
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
                                        class="w-1/2 max-w-[20px] rounded-t-sm bg-brand hover:opacity-90 transition-all duration-200"
                                        :style="{ height: `${Math.max(4, (salesChart.cash_series[idx] / chartMax) * 100)}%` }"
                                    ></div>

                                    <!-- Credit Bar -->
                                    <div
                                        class="w-1/2 max-w-[20px] rounded-t-sm bg-amber-500 hover:opacity-90 transition-all duration-200"
                                        :style="{ height: `${Math.max(4, (salesChart.credit_series[idx] / chartMax) * 100)}%` }"
                                    ></div>
                                </div>

                                <!-- Label Hari -->
                                <span class="text-[11px] font-medium text-slate-500 dark:text-zinc-400 mt-2 truncate w-full text-center">
                                    {{ label }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Ringkasan 7 Hari -->
                    <div class="mt-4 pt-3.5 border-t border-slate-200/80 dark:border-zinc-800/80 grid grid-cols-3 gap-2 text-center text-xs">
                        <div class="p-2 rounded-lg bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/60 dark:border-zinc-800/60">
                            <span class="text-slate-500 dark:text-zinc-400 block text-[11px]">Total 7 Hari</span>
                            <span class="font-bold text-slate-900 dark:text-zinc-100 text-sm">
                                {{ formatRupiah(salesChart.grand_total) }}
                            </span>
                        </div>
                        <div class="p-2 rounded-lg bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/60 dark:border-zinc-800/60">
                            <span class="text-slate-500 dark:text-zinc-400 block text-[11px]">Porsi Tunai</span>
                            <span class="font-bold text-brand text-sm">
                                {{ formatRupiah(salesChart.cash_total) }}
                            </span>
                        </div>
                        <div class="p-2 rounded-lg bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/60 dark:border-zinc-800/60">
                            <span class="text-slate-500 dark:text-zinc-400 block text-[11px]">Porsi Tempo</span>
                            <span class="font-bold text-amber-600 dark:text-amber-400 text-sm">
                                {{ formatRupiah(salesChart.credit_total) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 3. Tabel Quick Alert: 5 Barang Stok Mendekati 0 -->
                <div class="rounded-xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 p-5 shadow-2xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-3.5 border-b border-slate-200/80 dark:border-zinc-800/80">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-zinc-100">
                                    Peringatan Stok Kritis
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">5 barang dengan stok paling sedikit</p>
                            </div>
                            <span class="text-xs font-semibold text-rose-600 dark:text-rose-400">
                                Segera Restock
                            </span>
                        </div>

                        <!-- Item List -->
                        <div class="divide-y divide-slate-200/60 dark:divide-zinc-800/60 mt-1">
                            <div
                                v-for="item in quickAlertProducts"
                                :key="item.id"
                                class="py-2.5 flex items-center justify-between gap-3 group"
                            >
                                <div class="min-w-0 flex-1">
                                    <span class="text-xs font-bold text-slate-900 dark:text-zinc-100 truncate block">
                                        {{ item.name }}
                                    </span>
                                    <div class="text-[11px] text-slate-500 dark:text-zinc-400 mt-0.5 flex items-center gap-1.5">
                                        <span class="text-rose-600 dark:text-rose-400 font-semibold">
                                            Sisa {{ item.current_stock }} {{ item.base_unit }}
                                        </span>
                                        <span>•</span>
                                        <span>Min {{ item.min_stock }}</span>
                                    </div>
                                </div>

                                <!-- Shortcut Restock Button -->
                                <button
                                    @click="openRestockModal(item)"
                                    class="shrink-0 px-2 py-1 rounded-md border border-slate-200 dark:border-zinc-800 hover:bg-slate-100 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-300 transition-colors text-[11px] font-semibold flex items-center gap-1 cursor-pointer"
                                >
                                    <Plus class="w-3 h-3" />
                                    <span>Restock</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-200/80 dark:border-zinc-800/80">
                        <Link
                            :href="route('admin.products.index')"
                            class="w-full py-1.5 text-xs font-semibold text-brand hover:underline text-center block transition-colors"
                        >
                            Buka Seluruh Master Data Barang →
                        </Link>
                    </div>
                </div>
            </div>

            <!-- 4. Transaksi Kasir Terbaru -->
            <div class="rounded-xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-200/80 dark:border-zinc-800/80 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-zinc-100">
                            Transaksi Kasir Terbaru
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">Riwayat nota transaksi langsung & tempo hari ini</p>
                    </div>
                    <Link
                        :href="route('admin.pos.index')"
                        class="text-xs font-semibold text-brand hover:underline flex items-center gap-1"
                    >
                        Ke Kasir POS <ArrowRight class="w-3 h-3" />
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 dark:bg-[#18181C] text-slate-600 dark:text-zinc-400 font-semibold border-b border-slate-200/80 dark:border-zinc-800/80">
                            <tr>
                                <th class="py-2.5 px-4">No. Nota</th>
                                <th class="py-2.5 px-4">Pelanggan</th>
                                <th class="py-2.5 px-4">Metode Bayar</th>
                                <th class="py-2.5 px-4">Status</th>
                                <th class="py-2.5 px-4 text-right">Total Transaksi</th>
                                <th class="py-2.5 px-4 text-right">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60 dark:divide-zinc-800/60">
                            <tr
                                v-for="trx in recentTransactions"
                                :key="trx.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-[#1c1c21] transition-colors"
                            >
                                <td class="py-3 px-4 font-mono font-bold text-slate-900 dark:text-zinc-100">
                                    {{ trx.invoice_number }}
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-800 dark:text-zinc-200">
                                    {{ trx.customer_name }}
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        v-if="trx.payment_method === 'cash'"
                                        class="px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 font-medium text-[11px] border border-emerald-200 dark:border-emerald-900/50"
                                    >
                                        Tunai
                                    </span>
                                    <span
                                        v-else-if="trx.payment_method === 'transfer'"
                                        class="px-2 py-0.5 rounded-md bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-medium text-[11px] border border-blue-200 dark:border-blue-900/50"
                                    >
                                        QRIS / Transfer
                                    </span>
                                    <span
                                        v-else
                                        class="px-2 py-0.5 rounded-md bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 font-medium text-[11px] border border-amber-200 dark:border-amber-900/50"
                                    >
                                        Tempo
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        v-if="trx.payment_status === 'paid'"
                                        class="text-emerald-600 dark:text-emerald-400 font-semibold"
                                    >
                                        Lunas
                                    </span>
                                    <span
                                        v-else-if="trx.is_overdue"
                                        class="text-rose-600 dark:text-rose-400 font-bold"
                                    >
                                        Jatuh Tempo
                                    </span>
                                    <span
                                        v-else
                                        class="text-amber-600 dark:text-amber-400 font-semibold"
                                    >
                                        Belum Lunas
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-slate-900 dark:text-zinc-100">
                                    {{ formatRupiah(trx.total_amount) }}
                                </td>
                                <td class="py-3 px-4 text-right text-slate-400 dark:text-zinc-500">
                                    {{ trx.time_ago }}
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
            <div class="bg-white dark:bg-[#141417] rounded-xl max-w-md w-full p-5 shadow-xl border border-slate-200 dark:border-zinc-800">
                <h4 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <Plus class="w-4 h-4 text-brand" />
                    Tambah Stok Cepat (Restock)
                </h4>
                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">
                    {{ restockProduct?.name }} (Satuan: {{ restockProduct?.base_unit }})
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
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white font-bold text-base focus:ring-2 focus:ring-brand/20 focus:border-brand"
                        />
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-zinc-900/60 text-xs text-slate-500 dark:text-zinc-400 border border-slate-200/60 dark:border-zinc-800">
                        Stok saat ini: <strong class="text-rose-600 dark:text-rose-400">{{ restockProduct?.current_stock }}</strong> →
                        Setelah restock: <strong class="text-emerald-600 dark:text-emerald-400">{{ (restockProduct?.current_stock || 0) + Number(restockQty || 0) }}</strong> {{ restockProduct?.base_unit }}
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button
                        @click="isRestockModalOpen = false"
                        type="button"
                        class="px-3.5 py-1.5 rounded-lg bg-slate-100 dark:bg-zinc-800 hover:bg-slate-200 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-300 font-semibold text-xs transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitRestock"
                        type="button"
                        class="px-4 py-1.5 rounded-lg bg-brand hover:opacity-90 text-white font-bold text-xs shadow-2xs transition-all cursor-pointer"
                    >
                        Simpan Stok Masuk
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
