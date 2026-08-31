<script setup>
import { ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast';
import {
    Clock,
    AlertTriangle,
    CheckCircle2,
    Search,
    CreditCard,
    DollarSign,
    Calendar,
    ChevronLeft,
    ChevronRight,
    ArrowUpRight,
    Receipt,
    User,
    Phone,
    X,
    Filter,
} from 'lucide-vue-next';

const props = defineProps({
    debts: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const toast = useToast();

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');

let searchTimeout = null;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
};

const applyFilters = () => {
    router.get(
        route('admin.debts.index'),
        {
            search: search.value || undefined,
            status: selectedStatus.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const filterByTab = (status) => {
    selectedStatus.value = status;
    applyFilters();
};

// Format Currency
const formatRupiah = (val) => {
    return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
};

// Settlement Modal State
const isSettleModalOpen = ref(false);
const activeTransaction = ref(null);
const settlementType = ref('full'); // 'full' or 'partial'
const paymentAmount = ref(0);
const paymentMethod = ref('cash');
const referenceNumber = ref('');
const settlementNotes = ref('');
const isSubmitting = ref(false);

const openSettleModal = (trx) => {
    activeTransaction.value = trx;
    settlementType.value = 'full';
    paymentAmount.value = Number(trx.remaining_debt);
    paymentMethod.value = 'cash';
    referenceNumber.value = '';
    settlementNotes.value = '';
    isSettleModalOpen.value = true;
};

const onSettlementTypeChange = (type) => {
    settlementType.value = type;
    if (type === 'full') {
        paymentAmount.value = Number(activeTransaction.value.remaining_debt);
    } else {
        paymentAmount.value = Math.min(1000000, Number(activeTransaction.value.remaining_debt));
    }
};

const submitSettlement = () => {
    if (!activeTransaction.value) return;
    if (paymentAmount.value <= 0) {
        toast.error('Nominal Tidak Valid', 'Masukkan nominal pembayaran lebih dari 0.');
        return;
    }

    isSubmitting.value = true;

    router.post(
        route('admin.debts.settle', activeTransaction.value.id),
        {
            amount: paymentAmount.value,
            payment_method: paymentMethod.value,
            reference_number: referenceNumber.value,
            notes: settlementNotes.value,
        },
        {
            onSuccess: () => {
                isSettleModalOpen.value = false;
                isSubmitting.value = false;
                activeTransaction.value = null;
            },
            onError: () => {
                isSubmitting.value = false;
            },
        }
    );
};
</script>

<template>
    <AdminLayout title="Piutang & Jatuh Tempo">
        <Head title="Monitoring Piutang & Jatuh Tempo - Toko Sembako" />

        <div class="space-y-6">
            <!-- 3 Stat Cards Piutang (Menggunakan warna neutral dark #141417 sesuai template) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Card 1: Total Piutang Aktif -->
                <div class="p-5 rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Total Piutang Berjalan</span>
                        <div class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">
                            {{ formatRupiah(stats.total_active_debt) }}
                        </div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-500 dark:bg-amber-500/20 flex items-center justify-center border border-amber-500/20">
                        <Clock class="w-5 h-5" />
                    </div>
                </div>

                <!-- Card 2: Total Piutang Jatuh Tempo (Overdue) -->
                <div class="p-5 rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Piutang Telah Jatuh Tempo</span>
                        <div class="text-2xl font-extrabold text-rose-600 dark:text-rose-400 mt-1">
                            {{ formatRupiah(stats.total_overdue_debt) }}
                        </div>
                        <span class="inline-block mt-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-900/50">
                            {{ stats.overdue_count }} Nota Wajib Ditagih
                        </span>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 flex items-center justify-center border border-rose-500/20">
                        <AlertTriangle class="w-5 h-5" />
                    </div>
                </div>

                <!-- Card 3: Pelunasan Masuk Bulan Ini -->
                <div class="p-5 rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Pelunasan Diterima Bulan Ini</span>
                        <div class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">
                            {{ formatRupiah(stats.paid_this_month) }}
                        </div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 flex items-center justify-center border border-emerald-500/20">
                        <CheckCircle2 class="w-5 h-5" />
                    </div>
                </div>
            </div>

            <!-- Monitoring Table Container -->
            <div class="rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs overflow-hidden">
                <!-- Header Tabs and Search -->
                <div class="p-4 sm:p-5 border-b border-slate-200/80 dark:border-zinc-800/80 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Status Filter Tabs -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
                        <button
                            @click="filterByTab('')"
                            type="button"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
                            :class="selectedStatus === '' ? 'bg-brand text-white shadow-sm' : 'bg-slate-100 dark:bg-zinc-800/80 text-slate-600 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700'"
                        >
                            Semua Nota
                        </button>
                        <button
                            @click="filterByTab('overdue')"
                            type="button"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
                            :class="selectedStatus === 'overdue' ? 'bg-rose-600 text-white shadow-sm' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-900/40 hover:bg-rose-100'"
                        >
                            <AlertTriangle class="w-3.5 h-3.5" />
                            <span>Jatuh Tempo ({{ stats.overdue_count }})</span>
                        </button>
                        <button
                            @click="filterByTab('unpaid')"
                            type="button"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
                            :class="selectedStatus === 'unpaid' ? 'bg-brand text-white shadow-sm' : 'bg-slate-100 dark:bg-zinc-800/80 text-slate-600 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700'"
                        >
                            Belum Lunas
                        </button>
                        <button
                            @click="filterByTab('partial')"
                            type="button"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
                            :class="selectedStatus === 'partial' ? 'bg-brand text-white shadow-sm' : 'bg-slate-100 dark:bg-zinc-800/80 text-slate-600 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700'"
                        >
                            Cicilan Parsial
                        </button>
                        <button
                            @click="filterByTab('paid')"
                            type="button"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
                            :class="selectedStatus === 'paid' ? 'bg-brand text-white shadow-sm' : 'bg-slate-100 dark:bg-zinc-800/80 text-slate-600 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700'"
                        >
                            Lunas
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative min-w-[240px] max-w-sm">
                        <Search class="w-3.5 h-3.5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="search"
                            @input="handleSearch"
                            type="text"
                            placeholder="Cari no. nota / nama pelanggan..."
                            class="w-full pl-9 pr-4 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900 text-slate-900 dark:text-zinc-100 placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand"
                        />
                    </div>
                </div>

                <!-- Table Monitoring Nota Gantung -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 dark:bg-[#18181C] text-slate-600 dark:text-zinc-400 font-semibold border-b border-slate-200/80 dark:border-zinc-800/80">
                            <tr>
                                <th class="py-3 px-4">No. Nota</th>
                                <th class="py-3 px-4">Nama Pelanggan & Kontak</th>
                                <th class="py-3 px-4">Tgl Transaksi</th>
                                <th class="py-3 px-4">Jatuh Tempo</th>
                                <th class="py-3 px-4 text-right">Total Hutang</th>
                                <th class="py-3 px-4 text-right">Sisa Piutang</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60 dark:divide-zinc-800/60">
                            <!-- HIGHLIGHT OTOMATIS: Baris berubah warna merah muda jika tanggal saat ini melampaui jatuh tempo! -->
                            <tr
                                v-for="debt in debts.data"
                                :key="debt.id"
                                class="transition-colors"
                                :class="[
                                    debt.is_overdue
                                        ? 'bg-rose-50/60 dark:bg-rose-950/20 border-l-4 border-rose-500 hover:bg-rose-100/50 dark:hover:bg-rose-950/30'
                                        : 'hover:bg-slate-50/80 dark:hover:bg-[#1c1c21]',
                                ]"
                            >
                                <!-- No. Nota -->
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900 dark:text-zinc-100">
                                    {{ debt.invoice_number }}
                                </td>

                                <!-- Pelanggan & Kontak -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 dark:text-zinc-100">
                                        {{ debt.customer?.name || 'Pelanggan Toko' }}
                                    </div>
                                    <div v-if="debt.customer?.phone" class="text-[11px] text-slate-500 dark:text-zinc-400 flex items-center gap-1 mt-0.5">
                                        <Phone class="w-3 h-3 text-slate-400" />
                                        <span>{{ debt.customer.phone }}</span>
                                    </div>
                                </td>

                                <!-- Tanggal Transaksi -->
                                <td class="py-3.5 px-4 text-slate-600 dark:text-zinc-400">
                                    {{ new Date(debt.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                </td>

                                <!-- Jatuh Tempo dengan Alert Warning -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="font-semibold"
                                            :class="debt.is_overdue ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-slate-700 dark:text-zinc-300'"
                                        >
                                            {{ debt.due_date }}
                                        </span>
                                    </div>
                                    <div v-if="debt.is_overdue" class="mt-0.5 inline-flex items-center gap-1 text-[10px] font-bold text-rose-600 dark:text-rose-400">
                                        <AlertTriangle class="w-3 h-3" />
                                        <span>Lewat {{ debt.days_overdue }} Hari</span>
                                    </div>
                                </td>

                                <!-- Total Hutang Awal -->
                                <td class="py-3.5 px-4 text-right font-medium text-slate-600 dark:text-zinc-400">
                                    {{ formatRupiah(debt.total_amount) }}
                                </td>

                                <!-- Sisa Piutang Saat Ini -->
                                <td class="py-3.5 px-4 text-right">
                                    <span
                                        class="font-extrabold text-sm"
                                        :class="debt.remaining_debt > 0 ? (debt.is_overdue ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400') : 'text-emerald-600 dark:text-emerald-400'"
                                    >
                                        {{ formatRupiah(debt.remaining_debt) }}
                                    </span>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        v-if="debt.payment_status === 'paid'"
                                        class="px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-bold text-[11px] border border-emerald-200 dark:border-emerald-900/50"
                                    >
                                        Lunas
                                    </span>
                                    <span
                                        v-else-if="debt.is_overdue"
                                        class="px-2.5 py-1 rounded-full bg-rose-600 text-white font-extrabold text-[11px] shadow-sm shadow-rose-600/30 animate-pulse"
                                    >
                                        Jatuh Tempo
                                    </span>
                                    <span
                                        v-else-if="debt.payment_status === 'partial'"
                                        class="px-2.5 py-1 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 font-bold text-[11px] border border-amber-200 dark:border-amber-900/50"
                                    >
                                        Cicilan
                                    </span>
                                    <span
                                        v-else
                                        class="px-2.5 py-1 rounded-full bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold text-[11px] border border-slate-200 dark:border-zinc-700"
                                    >
                                        Belum Lunas
                                    </span>
                                </td>

                                <!-- Action: Tombol Pelunasan -->
                                <td class="py-3.5 px-4 text-right">
                                    <button
                                        v-if="debt.remaining_debt > 0"
                                        @click="openSettleModal(debt)"
                                        type="button"
                                        class="px-3 py-1.5 rounded-lg bg-brand hover:opacity-90 text-white font-bold text-xs shadow-sm shadow-brand/20 transition-all flex items-center gap-1 ml-auto cursor-pointer"
                                    >
                                        <DollarSign class="w-3.5 h-3.5" />
                                        <span>Pelunasan</span>
                                    </button>
                                    <span v-else class="text-emerald-600 dark:text-emerald-400 font-bold text-xs flex items-center justify-end gap-1">
                                        <CheckCircle2 class="w-4 h-4" /> Selesai
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="debts.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400 dark:text-zinc-500">
                                    <Receipt class="w-10 h-10 mx-auto text-slate-300 dark:text-zinc-700 mb-2" />
                                    <p class="font-bold">Tidak ada nota piutang yang sesuai filter.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="debts.total > debts.per_page" class="p-4 border-t border-slate-200/80 dark:border-zinc-800/80 flex items-center justify-between text-xs text-slate-500 dark:text-zinc-400">
                    <div>
                        Menampilkan {{ debts.from }} - {{ debts.to }} dari {{ debts.total }} nota
                    </div>
                    <div class="flex items-center gap-1">
                        <Link
                            v-if="debts.prev_page_url"
                            :href="debts.prev_page_url"
                            class="p-1.5 rounded-lg border border-slate-200 dark:border-zinc-800 hover:bg-slate-100 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-200"
                        >
                            <ChevronLeft class="w-4 h-4" />
                        </Link>
                        <span class="px-3 py-1 font-bold text-slate-900 dark:text-white">
                            Halaman {{ debts.current_page }} dari {{ debts.last_page }}
                        </span>
                        <Link
                            v-if="debts.next_page_url"
                            :href="debts.next_page_url"
                            class="p-1.5 rounded-lg border border-slate-200 dark:border-zinc-800 hover:bg-slate-100 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-200"
                        >
                            <ChevronRight class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL PELUNASAN PIUTANG (Parsial atau Lunas Seketika) -->
        <div
            v-if="isSettleModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
        >
            <div class="bg-white dark:bg-[#141417] rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-zinc-800">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-zinc-800">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <DollarSign class="w-5 h-5 text-brand" />
                            Pelunasan Nota Piutang
                        </h3>
                        <span class="text-xs text-slate-400 dark:text-zinc-500 font-mono">{{ activeTransaction?.invoice_number }}</span>
                    </div>
                    <button @click="isSettleModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-zinc-200">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Info Pelanggan & Hutang -->
                <div class="mt-4 p-4 rounded-xl bg-slate-50 dark:bg-zinc-900/70 border border-slate-200 dark:border-zinc-800 grid grid-cols-2 gap-3 text-xs">
                    <div>
                        <span class="text-slate-500 dark:text-zinc-400 block">Pelanggan:</span>
                        <span class="font-bold text-slate-900 dark:text-white text-sm">
                            {{ activeTransaction?.customer?.name }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-500 dark:text-zinc-400 block">Sisa Hutang Saat Ini:</span>
                        <span class="font-black text-rose-600 dark:text-rose-400 text-base">
                            {{ formatRupiah(activeTransaction?.remaining_debt) }}
                        </span>
                    </div>
                </div>

                <div class="mt-4 space-y-3.5 text-xs">
                    <!-- Opsi: Lunas Seketika vs Parsial -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-zinc-300 mb-1.5">Pilihan Jenis Pelunasan</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                @click="onSettlementTypeChange('full')"
                                type="button"
                                class="py-2.5 px-3 rounded-xl border font-bold text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer"
                                :class="settlementType === 'full' ? 'bg-brand/10 border-brand text-brand shadow-sm' : 'border-slate-200 dark:border-zinc-800 text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800'"
                            >
                                <CheckCircle2 class="w-4 h-4" />
                                <span>Lunas Seketika (Full)</span>
                            </button>
                            <button
                                @click="onSettlementTypeChange('partial')"
                                type="button"
                                class="py-2.5 px-3 rounded-xl border font-bold text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer"
                                :class="settlementType === 'partial' ? 'bg-amber-500/10 border-amber-500 text-amber-600 dark:text-amber-400 shadow-sm' : 'border-slate-200 dark:border-zinc-800 text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800'"
                            >
                                <CreditCard class="w-4 h-4" />
                                <span>Bayar Cicilan (Parsial)</span>
                            </button>
                        </div>
                    </div>

                    <!-- Input Nominal Pembayaran -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-zinc-300 mb-1">
                            Nominal yang Dibayarkan Hari Ini (Rp) *
                        </label>
                        <input
                            v-model.number="paymentAmount"
                            type="number"
                            min="1"
                            :max="Number(activeTransaction?.remaining_debt)"
                            class="w-full px-3 py-2 text-lg rounded-xl border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white font-black text-emerald-600 focus:ring-2 focus:ring-brand"
                        />
                        <span class="text-[11px] text-slate-500 dark:text-zinc-400 mt-1 block">
                            Sisa hutang setelah pembayaran ini:
                            <strong>{{ formatRupiah(Math.max(0, Number(activeTransaction?.remaining_debt) - paymentAmount)) }}</strong>
                        </span>
                    </div>

                    <!-- Metode Pembayaran Pelunasan -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-zinc-300 mb-1">Metode Penerimaan Uang</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                @click="paymentMethod = 'cash'"
                                type="button"
                                class="py-2 px-3 rounded-xl border font-bold text-xs transition-all cursor-pointer"
                                :class="paymentMethod === 'cash' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'border-slate-200 dark:border-zinc-800 text-slate-700 dark:text-zinc-300'"
                            >
                                Tunai / Kasir
                            </button>
                            <button
                                @click="paymentMethod = 'transfer'"
                                type="button"
                                class="py-2 px-3 rounded-xl border font-bold text-xs transition-all cursor-pointer"
                                :class="paymentMethod === 'transfer' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'border-slate-200 dark:border-zinc-800 text-slate-700 dark:text-zinc-300'"
                            >
                                Transfer Bank
                            </button>
                        </div>
                    </div>

                    <div v-if="paymentMethod === 'transfer'">
                        <label class="block font-bold text-slate-700 dark:text-zinc-300 mb-1">No. Bukti / Referensi Transfer</label>
                        <input
                            v-model="referenceNumber"
                            type="text"
                            placeholder="Contoh: TRF-BCA-98124"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-zinc-300 mb-1">Catatan Pembayaran (Opsional)</label>
                        <input
                            v-model="settlementNotes"
                            type="text"
                            placeholder="Contoh: Titip lewat sopir / lunas di toko"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white"
                        />
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2">
                    <button
                        @click="isSettleModalOpen = false"
                        type="button"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-semibold text-xs hover:bg-slate-200 dark:hover:bg-zinc-700 cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitSettlement"
                        :disabled="isSubmitting"
                        type="button"
                        class="px-5 py-2.5 rounded-xl bg-brand hover:opacity-90 disabled:opacity-50 text-white font-bold text-xs shadow-sm shadow-brand/20 cursor-pointer"
                    >
                        Konfirmasi Pembayaran
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
