<script setup>
import { ref, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast';
import {
    Calculator,
    Coins,
    DollarSign,
    CheckCircle2,
    AlertTriangle,
    FileSpreadsheet,
    Printer,
    Send,
    MessageSquare,
    Store,
    Clock,
    X,
    ExternalLink,
    TrendingUp,
    Receipt,
} from 'lucide-vue-next';

const props = defineProps({
    todayMetrics: {
        type: Object,
        required: true,
    },
    todayClosure: {
        type: Object,
        default: null,
    },
    closureHistory: {
        type: Array,
        default: () => [],
    },
    whatsappSummary: {
        type: String,
        default: '',
    },
    ownerPhone: {
        type: String,
        default: '628123456789',
    },
});

const toast = useToast();

// Form Rekap Pecahan Uang Fisik Laci
const notes = ref(props.todayClosure?.notes || '');
const targetPhone = ref(props.ownerPhone);

const denominations = ref({
    d100k: props.todayClosure?.denominations?.d100k || 0,
    d50k: props.todayClosure?.denominations?.d50k || 0,
    d20k: props.todayClosure?.denominations?.d20k || 0,
    d10k: props.todayClosure?.denominations?.d10k || 0,
    d5k: props.todayClosure?.denominations?.d5k || 0,
    d2k: props.todayClosure?.denominations?.d2k || 0,
    d1k: props.todayClosure?.denominations?.d1k || 0,
    koin1k: props.todayClosure?.denominations?.koin1k || 0,
    koin500: props.todayClosure?.denominations?.koin500 || 0,
    koin200: props.todayClosure?.denominations?.koin200 || 0,
    koin100: props.todayClosure?.denominations?.koin100 || 0,
});

// Calculate total actual physical cash in drawer
const totalPhysicalCash = computed(() => {
    const d = denominations.value;
    return (
        Number(d.d100k || 0) * 100000 +
        Number(d.d50k || 0) * 50000 +
        Number(d.d20k || 0) * 20000 +
        Number(d.d10k || 0) * 10000 +
        Number(d.d5k || 0) * 5000 +
        Number(d.d2k || 0) * 2000 +
        Number(d.d1k || 0) * 1000 +
        Number(d.koin1k || 0) * 1000 +
        Number(d.koin500 || 0) * 500 +
        Number(d.koin200 || 0) * 200 +
        Number(d.koin100 || 0) * 100
    );
});

// Expected cash according to system
const expectedCash = computed(() => {
    return Number(props.todayMetrics.cash_sales_today || 0);
});

// Difference = Physical - Expected
const difference = computed(() => {
    return totalPhysicalCash.value - expectedCash.value;
});

const isBalance = computed(() => difference.value === 0);
const isSurplus = computed(() => difference.value > 0);
const isDeficit = computed(() => difference.value < 0);

// Format Rupiah
const formatRupiah = (val) => {
    return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
};

// Quick button to balance drawer with system cash
const autoFillExactCash = () => {
    let remaining = expectedCash.value;
    const d = {
        d100k: 0,
        d50k: 0,
        d20k: 0,
        d10k: 0,
        d5k: 0,
        d2k: 0,
        d1k: 0,
        koin1k: 0,
        koin500: 0,
        koin200: 0,
        koin100: 0,
    };

    d.d100k = Math.floor(remaining / 100000);
    remaining %= 100000;

    d.d50k = Math.floor(remaining / 50000);
    remaining %= 50000;

    d.d20k = Math.floor(remaining / 20000);
    remaining %= 20000;

    d.d10k = Math.floor(remaining / 10000);
    remaining %= 10000;

    d.d5k = Math.floor(remaining / 5000);
    remaining %= 5000;

    d.d2k = Math.floor(remaining / 2000);
    remaining %= 2000;

    d.d1k = Math.floor(remaining / 1000);
    remaining %= 1000;

    d.koin500 = Math.floor(remaining / 500);
    remaining %= 500;

    denominations.value = d;
    toast.info('Diisi Otomatis', 'Pecahan telah disesuaikan dengan nilai kas sistem.');
};

// Submit Closure & Trigger WhatsApp
const isSubmitting = ref(false);
const isWaModalOpen = ref(false);
const waModalData = ref(null);

const submitClosure = () => {
    isSubmitting.value = true;

    router.post(
        route('admin.closure.store'),
        {
            actual_cash: totalPhysicalCash.value,
            expected_cash: expectedCash.value,
            total_omzet: props.todayMetrics.omzet_today,
            total_profit: props.todayMetrics.profit_today,
            total_cash_sales: props.todayMetrics.cash_sales_today,
            total_credit_sales: props.todayMetrics.credit_sales_today,
            denominations: denominations.value,
            notes: notes.value,
            wa_phone: targetPhone.value,
        },
        {
            onSuccess: (page) => {
                isSubmitting.value = false;
                toast.success('Tutup Buku Tersimpan', 'Rekap kas fisik laci berhasil diarsipkan.');
                triggerWhatsAppSimulator();
            },
            onError: () => {
                isSubmitting.value = false;
                toast.error('Gagal', 'Terjadi kesalahan saat menyimpan rekap tutup buku.');
            },
        }
    );
};

// WhatsApp Webhook Trigger Simulator
const isTriggeringWa = ref(false);

const triggerWhatsAppSimulator = () => {
    isTriggeringWa.value = true;

    fetch(route('admin.closure.whatsapp'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
            phone: targetPhone.value,
            closure_date: props.todayMetrics.date,
        }),
    })
        .then((res) => res.json())
        .then((data) => {
            isTriggeringWa.value = false;
            waModalData.value = data;
            isWaModalOpen.value = true;
        })
        .catch(() => {
            isTriggeringWa.value = false;
            toast.error('Gagal Gateway', 'Koneksi ke gateway simulasi terputus.');
        });
};

const printClosureReport = () => {
    window.print();
};
</script>

<template>
    <AdminLayout title="Laporan & Tutup Buku Harian">
        <Head title="Rekap Kas & Tutup Buku - Toko Sembako" />

        <div class="space-y-6">
            <!-- 4 Ringkasan Angka Sistem Hari Ini (Dark: #141417) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-5 rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs">
                    <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Total Omzet Hari Ini</span>
                    <div class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ formatRupiah(todayMetrics.omzet_today) }}</div>
                    <span class="text-[11px] text-slate-400 dark:text-zinc-500 mt-1 block">{{ todayMetrics.transaction_count }} Transaksi Selesai</span>
                </div>

                <div class="p-5 rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs">
                    <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Kas Tunai Sistem (Expected)</span>
                    <div class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">{{ formatRupiah(todayMetrics.cash_sales_today) }}</div>
                    <span class="text-[11px] text-slate-400 dark:text-zinc-500 mt-1 block">Wajib ada di laci kasir</span>
                </div>

                <div class="p-5 rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs">
                    <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Penjualan Tempo (Kredit)</span>
                    <div class="text-2xl font-extrabold text-amber-600 dark:text-amber-400 mt-1">{{ formatRupiah(todayMetrics.credit_sales_today) }}</div>
                    <span class="text-[11px] text-slate-400 dark:text-zinc-500 mt-1 block">Tercatat piutang</span>
                </div>

                <div class="p-5 rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs">
                    <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Estimasi Laba Kotor</span>
                    <div class="text-2xl font-extrabold text-brand mt-1">{{ formatRupiah(todayMetrics.profit_today) }}</div>
                    <span class="text-[11px] text-slate-400 dark:text-zinc-500 mt-1 block">Margin kotor bersih dari HPP</span>
                </div>
            </div>

            <!-- Form Rekap Kas Fisik Laci (The Wow Factor) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Kolom Kiri: Input Pecahan Rupiah Laci -->
                <div class="lg:col-span-2 bg-white dark:bg-[#141417] rounded-2xl border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs p-6 space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-slate-200/80 dark:border-zinc-800/80 gap-2">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <Calculator class="w-5 h-5 text-brand" />
                                Formulir Rekap Fisik Uang Laci Kasir
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">
                                Masukkan jumlah lembar / koin hasil penghitungan fisik saat pergantian shift atau tutup toko
                            </p>
                        </div>
                        <button
                            @click="autoFillExactCash"
                            type="button"
                            class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-zinc-800 hover:bg-slate-200 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-300 font-bold text-xs flex items-center gap-1.5 transition-colors cursor-pointer self-start sm:self-auto"
                        >
                            <CheckCircle2 class="w-3.5 h-3.5 text-emerald-500" />
                            <span>Sesuaikan dengan Kas Sistem</span>
                        </button>
                    </div>

                    <!-- Input Grid: Uang Kertas (Banknotes) -->
                    <div class="space-y-3">
                        <span class="text-xs font-bold text-slate-800 dark:text-zinc-200 flex items-center gap-1.5">
                            <DollarSign class="w-4 h-4 text-brand" />
                            Pecahan Uang Kertas (Lembaran)
                        </span>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/80 dark:border-zinc-800">
                                <span class="font-bold text-slate-800 dark:text-zinc-200 block text-xs">Rp 100.000</span>
                                <div class="mt-1 flex items-center gap-1">
                                    <input
                                        v-model.number="denominations.d100k"
                                        type="number"
                                        min="0"
                                        class="w-full px-2 py-1 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white font-bold text-center"
                                    />
                                    <span class="text-[10px] text-slate-400">lbr</span>
                                </div>
                                <span class="text-[10px] text-slate-500 dark:text-zinc-400 mt-1 block font-medium">
                                    = {{ formatRupiah(denominations.d100k * 100000) }}
                                </span>
                            </div>

                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/80 dark:border-zinc-800">
                                <span class="font-bold text-slate-800 dark:text-zinc-200 block text-xs">Rp 50.000</span>
                                <div class="mt-1 flex items-center gap-1">
                                    <input
                                        v-model.number="denominations.d50k"
                                        type="number"
                                        min="0"
                                        class="w-full px-2 py-1 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white font-bold text-center"
                                    />
                                    <span class="text-[10px] text-slate-400">lbr</span>
                                </div>
                                <span class="text-[10px] text-slate-500 dark:text-zinc-400 mt-1 block font-medium">
                                    = {{ formatRupiah(denominations.d50k * 50000) }}
                                </span>
                            </div>

                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/80 dark:border-zinc-800">
                                <span class="font-bold text-slate-800 dark:text-zinc-200 block text-xs">Rp 20.000</span>
                                <div class="mt-1 flex items-center gap-1">
                                    <input
                                        v-model.number="denominations.d20k"
                                        type="number"
                                        min="0"
                                        class="w-full px-2 py-1 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white font-bold text-center"
                                    />
                                    <span class="text-[10px] text-slate-400">lbr</span>
                                </div>
                                <span class="text-[10px] text-slate-500 dark:text-zinc-400 mt-1 block font-medium">
                                    = {{ formatRupiah(denominations.d20k * 20000) }}
                                </span>
                            </div>

                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/80 dark:border-zinc-800">
                                <span class="font-bold text-slate-800 dark:text-zinc-200 block text-xs">Rp 10.000</span>
                                <div class="mt-1 flex items-center gap-1">
                                    <input
                                        v-model.number="denominations.d10k"
                                        type="number"
                                        min="0"
                                        class="w-full px-2 py-1 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white font-bold text-center"
                                    />
                                    <span class="text-[10px] text-slate-400">lbr</span>
                                </div>
                                <span class="text-[10px] text-slate-500 dark:text-zinc-400 mt-1 block font-medium">
                                    = {{ formatRupiah(denominations.d10k * 10000) }}
                                </span>
                            </div>

                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/80 dark:border-zinc-800">
                                <span class="font-bold text-slate-800 dark:text-zinc-200 block text-xs">Rp 5.000</span>
                                <div class="mt-1 flex items-center gap-1">
                                    <input
                                        v-model.number="denominations.d5k"
                                        type="number"
                                        min="0"
                                        class="w-full px-2 py-1 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white font-bold text-center"
                                    />
                                    <span class="text-[10px] text-slate-400">lbr</span>
                                </div>
                                <span class="text-[10px] text-slate-500 dark:text-zinc-400 mt-1 block font-medium">
                                    = {{ formatRupiah(denominations.d5k * 5000) }}
                                </span>
                            </div>

                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/80 dark:border-zinc-800">
                                <span class="font-bold text-slate-800 dark:text-zinc-200 block text-xs">Rp 2.000</span>
                                <div class="mt-1 flex items-center gap-1">
                                    <input
                                        v-model.number="denominations.d2k"
                                        type="number"
                                        min="0"
                                        class="w-full px-2 py-1 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white font-bold text-center"
                                    />
                                    <span class="text-[10px] text-slate-400">lbr</span>
                                </div>
                                <span class="text-[10px] text-slate-500 dark:text-zinc-400 mt-1 block font-medium">
                                    = {{ formatRupiah(denominations.d2k * 2000) }}
                                </span>
                            </div>

                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/80 dark:border-zinc-800">
                                <span class="font-bold text-slate-800 dark:text-zinc-200 block text-xs">Rp 1.000</span>
                                <div class="mt-1 flex items-center gap-1">
                                    <input
                                        v-model.number="denominations.d1k"
                                        type="number"
                                        min="0"
                                        class="w-full px-2 py-1 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white font-bold text-center"
                                    />
                                    <span class="text-[10px] text-slate-400">lbr</span>
                                </div>
                                <span class="text-[10px] text-slate-500 dark:text-zinc-400 mt-1 block font-medium">
                                    = {{ formatRupiah(denominations.d1k * 1000) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Input Grid: Uang Koin / Logam -->
                    <div class="space-y-3 pt-2">
                        <span class="text-xs font-bold text-slate-800 dark:text-zinc-200 flex items-center gap-1.5">
                            <Coins class="w-4 h-4 text-amber-500" />
                            Pecahan Uang Logam (Koin)
                        </span>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/80 dark:border-zinc-800">
                                <span class="font-bold text-slate-800 dark:text-zinc-200 block text-xs">Koin Rp 1.000</span>
                                <div class="mt-1 flex items-center gap-1">
                                    <input
                                        v-model.number="denominations.koin1k"
                                        type="number"
                                        min="0"
                                        class="w-full px-2 py-1 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white font-bold text-center"
                                    />
                                    <span class="text-[10px] text-slate-400">koin</span>
                                </div>
                            </div>

                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/80 dark:border-zinc-800">
                                <span class="font-bold text-slate-800 dark:text-zinc-200 block text-xs">Koin Rp 500</span>
                                <div class="mt-1 flex items-center gap-1">
                                    <input
                                        v-model.number="denominations.koin500"
                                        type="number"
                                        min="0"
                                        class="w-full px-2 py-1 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white font-bold text-center"
                                    />
                                    <span class="text-[10px] text-slate-400">koin</span>
                                </div>
                            </div>

                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/80 dark:border-zinc-800">
                                <span class="font-bold text-slate-800 dark:text-zinc-200 block text-xs">Koin Rp 200</span>
                                <div class="mt-1 flex items-center gap-1">
                                    <input
                                        v-model.number="denominations.koin200"
                                        type="number"
                                        min="0"
                                        class="w-full px-2 py-1 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white font-bold text-center"
                                    />
                                    <span class="text-[10px] text-slate-400">koin</span>
                                </div>
                            </div>

                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/80 dark:border-zinc-800">
                                <span class="font-bold text-slate-800 dark:text-zinc-200 block text-xs">Koin Rp 100</span>
                                <div class="mt-1 flex items-center gap-1">
                                    <input
                                        v-model.number="denominations.koin100"
                                        type="number"
                                        min="0"
                                        class="w-full px-2 py-1 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white font-bold text-center"
                                    />
                                    <span class="text-[10px] text-slate-400">koin</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Catatan Kasir -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-zinc-300 text-xs mb-1">
                            Catatan Tutup Buku / Keterangan Selisih (Opsional)
                        </label>
                        <input
                            v-model="notes"
                            type="text"
                            placeholder="Contoh: Selisih uang tip kasir / kembalian permen / uang modal kasir Rp 200.000 sudah dipisahkan"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white"
                        />
                    </div>
                </div>

                <!-- Kolom Kanan: Rekonsiliasi & Tombol Eksekusi Tutup Buku + WA -->
                <div class="space-y-5">
                    <!-- Kartu Status Rekonsiliasi Kas -->
                    <div class="bg-white dark:bg-[#141417] rounded-2xl border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs p-6 space-y-4">
                        <h4 class="font-bold text-slate-900 dark:text-white text-sm">Status Rekonsiliasi Laci</h4>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between text-slate-500 dark:text-zinc-400">
                                <span>Total Fisik Uang Laci:</span>
                                <span class="font-black text-slate-900 dark:text-white text-sm">{{ formatRupiah(totalPhysicalCash) }}</span>
                            </div>
                            <div class="flex justify-between text-slate-500 dark:text-zinc-400">
                                <span>Target Kas Sistem:</span>
                                <span class="font-bold text-slate-900 dark:text-white text-sm">{{ formatRupiah(expectedCash) }}</span>
                            </div>
                        </div>

                        <!-- Indikator Dinamis Selisih (Surplus / Minus / Seimbang) -->
                        <div
                            class="p-4 rounded-xl border text-center transition-all"
                            :class="[
                                isBalance
                                    ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-300 dark:border-emerald-900/50 text-emerald-800 dark:text-emerald-300'
                                    : isSurplus
                                    ? 'bg-blue-50 dark:bg-blue-950/30 border-blue-300 dark:border-blue-900/50 text-blue-800 dark:text-blue-300'
                                    : 'bg-rose-50 dark:bg-rose-950/30 border-rose-300 dark:border-rose-900/50 text-rose-800 dark:text-rose-300',
                            ]"
                        >
                            <span class="text-xs font-bold uppercase tracking-wider block">
                                {{ isBalance ? 'KAS SEIMBANG (BALANCE)' : isSurplus ? 'SURPLUS KAS FISIK' : 'DEFISIT / MINUS KAS' }}
                            </span>
                            <div class="text-2xl font-black mt-1">
                                {{ isBalance ? 'Rp 0' : (isSurplus ? '+' : '') + formatRupiah(difference) }}
                            </div>
                            <p class="text-[11px] mt-1 opacity-80">
                                {{ isBalance ? 'Fisik uang cocok 100% dengan transaksi kasir.' : isSurplus ? 'Uang fisik di laci lebih banyak dari catatan sistem.' : 'Uang fisik di laci kurang dari catatan kasir!' }}
                            </p>
                        </div>

                        <!-- Input Nomor WhatsApp Owner -->
                        <div class="pt-2">
                            <label class="block font-bold text-slate-700 dark:text-zinc-300 text-xs mb-1">
                                No. WhatsApp Owner / Supervisor
                            </label>
                            <input
                                v-model="targetPhone"
                                type="text"
                                placeholder="628123456789"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white font-mono"
                            />
                        </div>

                        <!-- Tombol Utama: Tutup Buku & Kirim WA -->
                        <button
                            @click="submitClosure"
                            :disabled="isSubmitting"
                            type="button"
                            class="w-full py-3 rounded-xl bg-brand hover:opacity-90 disabled:opacity-50 text-white font-extrabold text-sm shadow-md shadow-brand/20 flex items-center justify-center gap-2 transition-all transform active:scale-98 cursor-pointer"
                        >
                            <Send class="w-4 h-4" />
                            <span>Tutup Buku & Kirim WA</span>
                        </button>
                    </div>

                    <!-- Tombol Ekspor Excel & Print Laporan -->
                    <div class="bg-white dark:bg-[#141417] rounded-2xl border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs p-5 space-y-2.5">
                        <span class="text-xs font-bold text-slate-800 dark:text-zinc-200 block">Ekspor & Arsip Dokumen</span>
                        <div class="grid grid-cols-2 gap-2">
                            <a
                                :href="route('admin.closure.export-excel')"
                                class="p-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 hover:bg-slate-50 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-200 text-xs font-bold flex items-center justify-center gap-1.5 transition-colors"
                            >
                                <FileSpreadsheet class="w-4 h-4 text-emerald-600" />
                                <span>Ekspor Excel</span>
                            </a>
                            <button
                                @click="printClosureReport"
                                type="button"
                                class="p-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 hover:bg-slate-50 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-200 text-xs font-bold flex items-center justify-center gap-1.5 transition-colors cursor-pointer"
                            >
                                <Printer class="w-4 h-4 text-brand" />
                                <span>Cetak PDF</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Riwayat Tutup Buku Terakhir -->
            <div class="bg-white dark:bg-[#141417] rounded-2xl border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs p-6">
                <h4 class="text-base font-bold text-slate-900 dark:text-white mb-4">Riwayat Tutup Buku Harian</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-slate-500 dark:text-zinc-400 border-b border-slate-200/80 dark:border-zinc-800/80 font-semibold bg-slate-50/60 dark:bg-[#18181C]">
                            <tr>
                                <th class="py-2.5 px-3">Tanggal</th>
                                <th class="py-2.5 px-3">Total Omzet</th>
                                <th class="py-2.5 px-3">Target Kas</th>
                                <th class="py-2.5 px-3">Fisik Laci</th>
                                <th class="py-2.5 px-3">Selisih</th>
                                <th class="py-2.5 px-3">Penanggung Jawab</th>
                                <th class="py-2.5 px-3 text-center">Status WA</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60 dark:divide-zinc-800/60">
                            <tr
                                v-for="c in closureHistory"
                                :key="c.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-[#1c1c21] transition-colors"
                            >
                                <td class="py-3 px-3 font-bold text-slate-900 dark:text-white">{{ c.closure_date }}</td>
                                <td class="py-3 px-3 font-extrabold">{{ formatRupiah(c.total_omzet) }}</td>
                                <td class="py-3 px-3">{{ formatRupiah(c.expected_cash) }}</td>
                                <td class="py-3 px-3 font-bold">{{ formatRupiah(c.actual_cash) }}</td>
                                <td class="py-3 px-3">
                                    <span
                                        class="px-2 py-0.5 rounded-full font-bold text-[10px]"
                                        :class="c.difference === 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : c.difference > 0 ? 'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400'"
                                    >
                                        {{ c.difference === 0 ? 'Cocok (Rp 0)' : (c.difference > 0 ? '+' : '') + formatRupiah(c.difference) }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-slate-600 dark:text-zinc-300">{{ c.closed_by_user }}</td>
                                <td class="py-3 px-3 text-center">
                                    <span class="text-emerald-600 font-bold flex items-center justify-center gap-1 text-[11px]">
                                        <CheckCircle2 class="w-3.5 h-3.5" /> Terkirim
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="closureHistory.length === 0">
                                <td colspan="7" class="py-8 text-center text-slate-400 dark:text-zinc-500 font-medium">
                                    Belum ada catatan tutup buku yang disimpan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- WHATSAPP GATEWAY SIMULATOR MODAL (The Wow Factor) -->
        <div
            v-if="isWaModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
        >
            <div class="bg-white dark:bg-[#141417] rounded-3xl max-w-sm w-full p-5 shadow-2xl border border-slate-200 dark:border-zinc-800 text-xs">
                <!-- Phone Header -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold">
                            WA
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900 dark:text-white text-xs">WhatsApp Gateway Live</h4>
                            <span class="text-[10px] text-emerald-500 font-bold flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                HTTP {{ waModalData?.http_status }} Delivered
                            </span>
                        </div>
                    </div>
                    <button @click="isWaModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-zinc-200">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Smartphone Screen Simulator -->
                <div class="my-4 p-3.5 bg-[#0b141a] rounded-2xl border border-emerald-900/40 shadow-inner font-sans text-[11px] leading-relaxed text-zinc-100 space-y-2">
                    <div class="text-[9px] text-center text-zinc-500 py-0.5">
                        Terkirim ke {{ waModalData?.phone }} • Hari ini {{ new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}
                    </div>

                    <!-- Chat Bubble -->
                    <div class="p-3 bg-[#005c4b] text-white rounded-2xl rounded-tr-xs shadow-md space-y-1">
                        <pre class="whitespace-pre-wrap font-sans text-[11px]">{{ waModalData?.raw_text }}</pre>
                        <div class="text-[9px] text-emerald-200 text-right flex items-center justify-end gap-1 pt-1">
                            <span>{{ new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}</span>
                            <span class="text-cyan-300 font-bold">✓✓</span>
                        </div>
                    </div>
                </div>

                <!-- Actions: Real WhatsApp Web Link -->
                <div class="space-y-2">
                    <a
                        :href="waModalData?.wa_me_link"
                        target="_blank"
                        rel="noopener"
                        class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-sm transition-all"
                    >
                        <ExternalLink class="w-3.5 h-3.5" />
                        <span>Buka & Tes di WhatsApp Sungguhan</span>
                    </a>
                    <button
                        @click="isWaModalOpen = false"
                        type="button"
                        class="w-full py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-300 font-semibold text-xs hover:bg-slate-200 dark:hover:bg-zinc-700"
                    >
                        Tutup Simulasi
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
