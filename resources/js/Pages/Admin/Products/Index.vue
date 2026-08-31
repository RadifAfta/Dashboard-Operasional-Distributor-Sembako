<script setup>
import { ref, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast';
import {
    Package,
    Plus,
    Search,
    Download,
    Upload,
    FileSpreadsheet,
    Edit,
    Trash2,
    Layers,
    AlertTriangle,
    CheckCircle2,
    X,
    Filter,
    ArrowUpDown,
    Boxes,
    ChevronLeft,
    ChevronRight,
    ExternalLink,
    Eye,
} from 'lucide-vue-next';

const props = defineProps({
    products: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
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
const selectedCategory = ref(props.filters.category || '');
const isCriticalFilter = ref(props.filters.is_critical || false);

// Search and filter handler
let searchTimeout = null;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
};

const applyFilters = () => {
    router.get(
        route('admin.products.index'),
        {
            search: search.value || undefined,
            category: selectedCategory.value || undefined,
            is_critical: isCriticalFilter.value ? 1 : undefined,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilters = () => {
    search.value = '';
    selectedCategory.value = '';
    isCriticalFilter.value = false;
    applyFilters();
};

// Format Currency
const formatRupiah = (val) => {
    return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
};

// Detail Modal
const isDetailModalOpen = ref(false);
const selectedProduct = ref(null);

const openDetailModal = (product) => {
    selectedProduct.value = product;
    isDetailModalOpen.value = true;
};

// Product Modal (Create & Edit)
const isFormModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = ref({
    sku: '',
    barcode: '',
    name: '',
    category: 'Minyak Goreng',
    cost_price: 0,
    selling_price: 0,
    base_unit: 'Pouch',
    min_stock: 10,
    current_stock: 0,
    status: 'active',
    description: '',
    units: [],
});

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.value = {
        sku: 'SKU-' + Math.floor(1000 + Math.random() * 9000),
        barcode: '',
        name: '',
        category: props.categories[0] || 'Minyak Goreng',
        cost_price: 0,
        selling_price: 0,
        base_unit: 'Pouch',
        min_stock: 10,
        current_stock: 0,
        status: 'active',
        description: '',
        units: [],
    };
    isFormModalOpen.value = true;
};

const openEditModal = (product) => {
    isEditing.value = true;
    editingId.value = product.id;
    form.value = {
        sku: product.sku,
        barcode: product.barcode || '',
        name: product.name,
        category: product.category,
        cost_price: Number(product.cost_price),
        selling_price: Number(product.selling_price),
        base_unit: product.base_unit,
        min_stock: Number(product.min_stock),
        current_stock: Number(product.current_stock),
        status: product.status,
        description: product.description || '',
        units: (product.units || []).map((u) => ({
            id: u.id,
            unit_name: u.unit_name,
            conversion_ratio: Number(u.conversion_ratio),
            selling_price: Number(u.selling_price),
            barcode: u.barcode || '',
        })),
    };
    isFormModalOpen.value = true;
};

// Multi-satuan dynamic rows
const addUnitRow = () => {
    form.value.units.push({
        unit_name: 'Dus',
        conversion_ratio: 12,
        selling_price: form.value.selling_price * 12,
        barcode: '',
    });
};

const removeUnitRow = (index) => {
    form.value.units.splice(index, 1);
};

const submitForm = () => {
    if (isEditing.value) {
        router.put(route('admin.products.update', editingId.value), form.value, {
            onSuccess: () => {
                isFormModalOpen.value = false;
                toast.success('Berhasil', 'Data produk dan satuan berhasil diperbarui.');
            },
            onError: (errors) => {
                toast.error('Gagal', Object.values(errors)[0] || 'Periksa kembali formulir.');
            },
        });
    } else {
        router.post(route('admin.products.store'), form.value, {
            onSuccess: () => {
                isFormModalOpen.value = false;
                toast.success('Berhasil', 'Produk baru dan multi-satuan berhasil disimpan.');
            },
            onError: (errors) => {
                toast.error('Gagal', Object.values(errors)[0] || 'Periksa kembali formulir.');
            },
        });
    }
};

// Delete Confirmation
const deleteModalOpen = ref(false);
const itemToDelete = ref(null);

const confirmDelete = (product) => {
    itemToDelete.value = product;
    deleteModalOpen.value = true;
};

const executeDelete = () => {
    if (!itemToDelete.value) return;
    router.delete(route('admin.products.destroy', itemToDelete.value.id), {
        onSuccess: () => {
            deleteModalOpen.value = false;
            itemToDelete.value = null;
            toast.success('Dihapus', 'Produk berhasil dihapus dari sistem.');
        },
    });
};

// Batch Import Modal
const isImportModalOpen = ref(false);
const importRows = ref([]);
const isParsingFile = ref(false);
const fileInputRef = ref(null);

const openImportModal = () => {
    importRows.value = [];
    isImportModalOpen.value = true;
};

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    isParsingFile.value = true;
    const reader = new FileReader();

    reader.onload = (e) => {
        try {
            const text = e.target.result;
            const lines = text.split(/\r\n|\n/).filter((line) => line.trim().length > 0);
            if (lines.length <= 1) {
                toast.error('Gagal', 'File kosong atau tidak memiliki data.');
                isParsingFile.value = false;
                return;
            }

            const headers = lines[0].split(',').map((h) => h.trim().replace(/^["']|["']$/g, '').toLowerCase());
            const parsed = [];

            for (let i = 1; i < lines.length; i++) {
                const cols = lines[i].split(',').map((c) => c.trim().replace(/^["']|["']$/g, ''));
                if (cols.length >= 3) {
                    const rowObj = {};
                    headers.forEach((h, idx) => {
                        rowObj[h] = cols[idx] || '';
                    });

                    parsed.push({
                        sku: rowObj.sku || `SKU-${Date.now()}-${i}`,
                        barcode: rowObj.barcode || '',
                        name: rowObj.name || rowObj['nama barang'] || '',
                        category: rowObj.category || rowObj['kategori'] || 'Sembako Umum',
                        cost_price: Number(rowObj.cost_price || rowObj['harga beli'] || 0),
                        selling_price: Number(rowObj.selling_price || rowObj['harga jual'] || 0),
                        base_unit: rowObj.base_unit || rowObj['satuan'] || 'Pcs',
                        min_stock: Number(rowObj.min_stock || 10),
                        current_stock: Number(rowObj.current_stock || 0),
                        wholesale_unit_name: rowObj.wholesale_unit_name || '',
                        wholesale_ratio: Number(rowObj.wholesale_ratio || 0),
                        wholesale_price: Number(rowObj.wholesale_price || 0),
                    });
                }
            }

            importRows.value = parsed;
            toast.info('File Terbaca', `${parsed.length} baris produk terdeteksi dan siap diimpor.`);
        } catch (err) {
            toast.error('Gagal Parse File', 'Pastikan format file sesuai template CSV/Excel.');
        } finally {
            isParsingFile.value = false;
        }
    };

    reader.readAsText(file);
};

const submitBatchImport = () => {
    if (importRows.value.length === 0) return;
    router.post(
        route('admin.products.batch-import'),
        { items: importRows.value },
        {
            onSuccess: () => {
                isImportModalOpen.value = false;
                importRows.value = [];
                toast.success('Berhasil', 'Seluruh data barang massal telah tersimpan di gudang.');
            },
            onError: () => {
                toast.error('Gagal Impor', 'Terjadi kesalahan saat memproses data ke database.');
            },
        }
    );
};
</script>

<template>
    <AdminLayout title="Master Data Barang & Multi-Satuan">
        <Head title="Master Data Barang - Distributor Sembako" />

        <div class="space-y-6">
            <!-- Top Summary Cards (Neutral Dark #141417) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-5 rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Total Jenis Barang</span>
                        <div class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ stats.total_products }} Produk</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-brand/10 text-brand flex items-center justify-center border border-brand/20">
                        <Package class="w-5 h-5" />
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Stok Kritis (&lt; Min)</span>
                        <div class="text-2xl font-extrabold text-rose-600 dark:text-rose-400 mt-1">{{ stats.critical_stock }} Produk</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 flex items-center justify-center border border-rose-500/20">
                        <AlertTriangle class="w-5 h-5" />
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Estimasi Aset Gudang (HPP)</span>
                        <div class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">{{ formatRupiah(stats.total_stock_value) }}</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 flex items-center justify-center border border-emerald-500/20">
                        <Boxes class="w-5 h-5" />
                    </div>
                </div>
            </div>

            <!-- Main Data Table Container -->
            <div class="rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs overflow-hidden">
                <!-- Table Header Actions & Filter -->
                <div class="p-4 sm:p-5 border-b border-slate-200/80 dark:border-zinc-800/80 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Search & Filter Controls -->
                    <div class="flex flex-wrap items-center gap-3 flex-1">
                        <div class="relative min-w-[240px] max-w-sm flex-1">
                            <Search class="w-3.5 h-3.5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                v-model="search"
                                @input="handleSearch"
                                type="text"
                                placeholder="Cari nama, SKU, barcode..."
                                class="w-full pl-9 pr-4 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900 text-slate-900 dark:text-zinc-100 placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all"
                            />
                        </div>

                        <!-- Filter Kategori -->
                        <select
                            v-model="selectedCategory"
                            @change="applyFilters"
                            class="px-3 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900 text-slate-700 dark:text-zinc-300 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand"
                        >
                            <option value="">Semua Kategori</option>
                            <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                        </select>

                        <!-- Toggle Stok Kritis -->
                        <button
                            @click="isCriticalFilter = !isCriticalFilter; applyFilters()"
                            type="button"
                            class="px-3 py-1.5 text-xs rounded-lg font-bold flex items-center gap-1.5 transition-all cursor-pointer"
                            :class="isCriticalFilter ? 'bg-rose-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700'"
                        >
                            <AlertTriangle class="w-3.5 h-3.5" />
                            <span>Stok Kritis Saja</span>
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2">
                        <!-- Batch Import Button -->
                        <button
                            @click="openImportModal"
                            type="button"
                            class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-zinc-800 hover:bg-slate-200 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-200 text-xs font-bold flex items-center gap-1.5 transition-colors cursor-pointer border border-slate-200/80 dark:border-zinc-700"
                        >
                            <Upload class="w-3.5 h-3.5 text-brand" />
                            <span>Batch Import .xlsx</span>
                        </button>

                        <!-- Export Button -->
                        <a
                            :href="route('admin.products.export')"
                            class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-zinc-800 hover:bg-slate-200 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-200 text-xs font-bold flex items-center gap-1.5 transition-colors border border-slate-200/80 dark:border-zinc-700"
                        >
                            <Download class="w-3.5 h-3.5" />
                            <span>Export CSV</span>
                        </a>

                        <!-- Create Product Button -->
                        <button
                            @click="openCreateModal"
                            type="button"
                            class="px-3.5 py-1.5 rounded-lg bg-brand hover:opacity-90 text-white text-xs font-bold flex items-center gap-1.5 shadow-sm shadow-brand/20 transition-all cursor-pointer"
                        >
                            <Plus class="w-4 h-4" />
                            <span>+ Produk Baru</span>
                        </button>
                    </div>
                </div>

                <!-- Products Table -->
                <!-- Products Table (1 Kolom = 1 Informasi Jelas & Rapi) -->
                <!-- Products Table (Hanya Kolom Penting, Pas di Layar Tanpa Scroll Horizontal) -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 dark:bg-[#18181C] text-slate-600 dark:text-zinc-400 font-semibold border-b border-slate-200/80 dark:border-zinc-800/80">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">No.</th>
                                <th class="py-3 px-4 w-36">Kode SKU</th>
                                <th class="py-3 px-4">Nama Barang</th>
                                <th class="py-3 px-4 w-36">Kategori</th>
                                <th class="py-3 px-4 text-right w-36">Harga Jual</th>
                                <th class="py-3 px-4 text-right w-36">Stok Gudang</th>
                                <th class="py-3 px-4 text-right w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60 dark:divide-zinc-800/60">
                            <tr
                                v-for="(product, index) in products.data"
                                :key="product.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-[#1c1c21] transition-colors"
                                :class="product.current_stock <= product.min_stock ? 'bg-rose-50/30 dark:bg-rose-950/15' : ''"
                            >
                                <!-- No. -->
                                <td class="py-3 px-4 text-center text-slate-400 dark:text-zinc-500 font-mono text-xs">
                                    {{ (products.current_page - 1) * products.per_page + index + 1 }}
                                </td>

                                <!-- 1. Kode SKU -->
                                <td class="py-3 px-4 font-mono font-bold text-slate-900 dark:text-zinc-100">
                                    {{ product.sku }}
                                </td>

                                <!-- 2. Nama Barang -->
                                <td class="py-3 px-4">
                                    <button
                                        type="button"
                                        @click="openDetailModal(product)"
                                        class="font-bold text-slate-900 dark:text-zinc-100 hover:text-brand transition-colors text-left cursor-pointer"
                                        title="Klik untuk melihat detail lengkap"
                                    >
                                        {{ product.name }}
                                    </button>
                                </td>

                                <!-- 3. Kategori -->
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-medium text-[11px] border border-slate-200/60 dark:border-zinc-700/60">
                                        {{ product.category }}
                                    </span>
                                </td>

                                <!-- 4. Harga Jual Satuan -->
                                <td class="py-3 px-4 text-right font-bold text-slate-900 dark:text-zinc-100">
                                    {{ formatRupiah(product.selling_price) }}
                                    <span class="text-[11px] font-normal text-slate-400 dark:text-zinc-500">/ {{ product.base_unit }}</span>
                                </td>

                                <!-- 5. Stok Gudang -->
                                <td class="py-3 px-4 text-right">
                                    <span
                                        class="font-bold text-sm"
                                        :class="product.current_stock <= product.min_stock ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-zinc-100'"
                                    >
                                        {{ product.current_stock }}
                                    </span>
                                    <span class="text-[11px] text-slate-400 dark:text-zinc-500 ml-1">{{ product.base_unit }}</span>
                                </td>

                                <!-- 6. Aksi (Detail, Edit, Hapus) -->
                                <td class="py-3 px-4 text-right space-x-1 whitespace-nowrap">
                                    <button
                                        @click="openDetailModal(product)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-brand hover:bg-brand/10 transition-colors cursor-pointer"
                                        title="Lihat Detail Produk"
                                    >
                                        <Eye class="w-4 h-4" />
                                    </button>
                                    <button
                                        @click="openEditModal(product)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-brand hover:bg-brand/10 transition-colors cursor-pointer"
                                        title="Edit Produk & Satuan"
                                    >
                                        <Edit class="w-4 h-4" />
                                    </button>
                                    <button
                                        @click="confirmDelete(product)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                                        title="Hapus Produk"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="products.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400 dark:text-zinc-500">
                                    <Package class="w-10 h-10 mx-auto text-slate-300 dark:text-zinc-700 mb-2" />
                                    <p class="font-medium">Tidak ada data barang yang sesuai filter.</p>
                                    <button @click="resetFilters" class="mt-2 text-xs text-brand hover:underline font-bold cursor-pointer">
                                        Reset Filter Pencarian
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="products.total > products.per_page" class="p-4 border-t border-slate-200/80 dark:border-zinc-800/80 flex items-center justify-between text-xs text-slate-500 dark:text-zinc-400">
                    <div>
                        Menampilkan {{ products.from }} - {{ products.to }} dari {{ products.total }} produk
                    </div>
                    <div class="flex items-center gap-1">
                        <Link
                            v-if="products.prev_page_url"
                            :href="products.prev_page_url"
                            class="p-1.5 rounded-lg border border-slate-200 dark:border-zinc-800 hover:bg-slate-100 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-200"
                        >
                            <ChevronLeft class="w-4 h-4" />
                        </Link>
                        <span class="px-3 py-1 font-bold text-slate-900 dark:text-white">
                            Halaman {{ products.current_page }} dari {{ products.last_page }}
                        </span>
                        <Link
                            v-if="products.next_page_url"
                            :href="products.next_page_url"
                            class="p-1.5 rounded-lg border border-slate-200 dark:border-zinc-800 hover:bg-slate-100 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-200"
                        >
                            <ChevronRight class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Create / Edit Modal (Dark: #141417) -->
        <div
            v-if="isFormModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs overflow-y-auto"
        >
            <div class="bg-white dark:bg-[#141417] rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 dark:border-zinc-800 my-8 max-h-[90vh] flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-zinc-800">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <Package class="w-5 h-5 text-brand" />
                            {{ isEditing ? 'Edit Data Barang & Satuan' : 'Tambah Barang Baru & Multi-Satuan' }}
                        </h3>
                        <button @click="isFormModalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-zinc-200">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="mt-4 space-y-4 text-xs overflow-y-auto pr-1">
                        <!-- SKU, Barcode, Nama -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-zinc-300 mb-1">Kode SKU *</label>
                                <input
                                    v-model="form.sku"
                                    type="text"
                                    required
                                    class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white font-mono"
                                />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block font-bold text-slate-700 dark:text-zinc-300 mb-1">Barcode Dasar (Opsional)</label>
                                <input
                                    v-model="form.barcode"
                                    type="text"
                                    placeholder="Scan / ketik barcode"
                                    class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white font-mono"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block font-bold text-slate-700 dark:text-zinc-300 mb-1">Nama Barang *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Contoh: Minyak Goreng Bimoli Klasik 2L"
                                    class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white font-semibold"
                                />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-zinc-300 mb-1">Kategori *</label>
                                <input
                                    v-model="form.category"
                                    type="text"
                                    required
                                    placeholder="Minyak, Beras, dll"
                                    class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white"
                                />
                            </div>
                        </div>

                        <!-- Harga & Satuan Dasar -->
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200 dark:border-zinc-800 space-y-3">
                            <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-brand"></span>
                                1. Konfigurasi Satuan Dasar (Eceran)
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-600 dark:text-zinc-400 mb-1">Nama Satuan Dasar *</label>
                                    <input
                                        v-model="form.base_unit"
                                        type="text"
                                        required
                                        placeholder="Pouch, Kg, Pcs, Bungkus"
                                        class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-600 dark:text-zinc-400 mb-1">Harga Beli (HPP) *</label>
                                    <input
                                        v-model.number="form.cost_price"
                                        type="number"
                                        required
                                        min="0"
                                        class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-600 dark:text-zinc-400 mb-1">Harga Jual Eceran *</label>
                                    <input
                                        v-model.number="form.selling_price"
                                        type="number"
                                        required
                                        min="0"
                                        class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white font-bold text-emerald-600 dark:text-emerald-400"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                                <div>
                                    <label class="block font-bold text-slate-600 dark:text-zinc-400 mb-1">Stok Sekarang (Satuan Dasar)</label>
                                    <input
                                        v-model.number="form.current_stock"
                                        type="number"
                                        required
                                        min="0"
                                        class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white font-extrabold"
                                    />
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-600 dark:text-zinc-400 mb-1">Batas Minimum Stok Alert</label>
                                    <input
                                        v-model.number="form.min_stock"
                                        type="number"
                                        required
                                        min="0"
                                        class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- 2. Multi-Satuan Grosir (Dus / Sak / Bal) -->
                        <div class="p-4 rounded-xl bg-brand/5 dark:bg-brand/10 border border-brand/20 space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                        <Layers class="w-4 h-4 text-brand" />
                                        2. Satuan Grosir / Multi-Satuan
                                    </span>
                                    <p class="text-[11px] text-slate-500 dark:text-zinc-400">
                                        Penjualan satuan grosir otomatis memotong stok satuan dasar sesuai rasio konversi.
                                    </p>
                                </div>
                                <button
                                    @click="addUnitRow"
                                    type="button"
                                    class="px-2.5 py-1.5 rounded-lg bg-brand hover:opacity-90 text-white font-bold text-xs flex items-center gap-1 shadow-sm cursor-pointer"
                                >
                                    <Plus class="w-3.5 h-3.5" />
                                    <span>+ Tambah Satuan</span>
                                </button>
                            </div>

                            <!-- List Satuan Grosir -->
                            <div v-if="form.units.length > 0" class="space-y-2.5 pt-1">
                                <div
                                    v-for="(unit, idx) in form.units"
                                    :key="idx"
                                    class="p-3 rounded-xl bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 grid grid-cols-1 sm:grid-cols-4 gap-2 items-center"
                                >
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-600 dark:text-zinc-400 mb-0.5">Nama Grosir</label>
                                        <input
                                            v-model="unit.unit_name"
                                            type="text"
                                            placeholder="Dus / Sak"
                                            class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800 text-xs font-semibold"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-600 dark:text-zinc-400 mb-0.5">
                                            Rasio (1 {{ unit.unit_name || 'Satuan' }} = X {{ form.base_unit }})
                                        </label>
                                        <input
                                            v-model.number="unit.conversion_ratio"
                                            type="number"
                                            min="1"
                                            class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800 text-xs font-bold text-brand"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-600 dark:text-zinc-400 mb-0.5">Harga Jual Grosir (Rp)</label>
                                        <input
                                            v-model.number="unit.selling_price"
                                            type="number"
                                            min="0"
                                            class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800 text-xs font-bold text-emerald-600 dark:text-emerald-400"
                                        />
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <div class="flex-1">
                                            <label class="block text-[10px] font-bold text-slate-600 dark:text-zinc-400 mb-0.5">Barcode Grosir</label>
                                            <input
                                                v-model="unit.barcode"
                                                type="text"
                                                placeholder="Barcode dus"
                                                class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800 text-xs font-mono"
                                            />
                                        </div>
                                        <button
                                            @click="removeUnitRow(idx)"
                                            type="button"
                                            class="mt-4 p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 cursor-pointer"
                                            title="Hapus Satuan"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-2 text-slate-400 dark:text-zinc-500 italic text-[11px]">
                                Belum ada satuan grosir tambahan. Klik tombol "+ Tambah Satuan" di atas untuk memasukkan kemasan Dus, Sak, Bal, dsb.
                            </div>
                        </div>
                    </form>
                </div>

                <div class="mt-6 pt-3 border-t border-slate-200 dark:border-zinc-800 flex items-center justify-end gap-2">
                    <button
                        @click="isFormModalOpen = false"
                        type="button"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-semibold hover:bg-slate-200 dark:hover:bg-zinc-700 cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitForm"
                        type="button"
                        class="px-5 py-2 rounded-xl bg-brand hover:opacity-90 text-white font-bold shadow-sm shadow-brand/20 cursor-pointer"
                    >
                        {{ isEditing ? 'Simpan Perubahan' : 'Simpan Produk' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Batch Import Modal -->
        <div
            v-if="isImportModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
        >
            <div class="bg-white dark:bg-[#141417] rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-200 dark:border-zinc-800">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-zinc-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <FileSpreadsheet class="w-5 h-5 text-brand" />
                        Batch Import Data Barang (.xlsx / .csv)
                    </h3>
                    <button @click="isImportModalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-zinc-200">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="mt-4 space-y-4 text-xs">
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-zinc-900/60 border border-dashed border-slate-300 dark:border-zinc-700 text-center">
                        <Upload class="w-8 h-8 mx-auto text-brand mb-2" />
                        <p class="font-bold text-slate-800 dark:text-zinc-200">
                            Pilih File Spreadsheet Data Barang
                        </p>
                        <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-0.5">
                            Mendukung file .csv atau .xlsx yang diekspor dari Excel
                        </p>
                        <input
                            ref="fileInputRef"
                            type="file"
                            accept=".csv, .xlsx, .xls"
                            @change="handleFileUpload"
                            class="mt-3 block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand/10 file:text-brand hover:file:bg-brand/20 cursor-pointer"
                        />
                    </div>

                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-slate-500 dark:text-zinc-400">Belum punya formatnya?</span>
                        <a
                            :href="route('admin.products.template')"
                            class="text-brand hover:underline font-bold flex items-center gap-1"
                        >
                            <Download class="w-3.5 h-3.5" />
                            Unduh Template Contoh .CSV
                        </a>
                    </div>

                    <!-- Preview Parsed Rows -->
                    <div v-if="importRows.length > 0" class="border border-slate-200 dark:border-zinc-800 rounded-xl overflow-hidden">
                        <div class="p-2.5 bg-slate-100 dark:bg-[#18181C] font-bold text-slate-800 dark:text-zinc-200 flex items-center justify-between">
                            <span>Preview ({{ importRows.length }} Baris Terdeteksi)</span>
                            <span class="text-emerald-600 dark:text-emerald-400">Siap Diimpor</span>
                        </div>
                        <div class="max-h-40 overflow-y-auto divide-y divide-slate-100 dark:divide-zinc-800">
                            <div
                                v-for="(row, idx) in importRows.slice(0, 5)"
                                :key="idx"
                                class="p-2 flex items-center justify-between text-[11px]"
                            >
                                <span class="font-bold text-slate-900 dark:text-white truncate max-w-[200px]">{{ row.name }}</span>
                                <span class="text-slate-500 dark:text-zinc-400 font-mono">{{ row.sku }}</span>
                                <span class="text-emerald-600 font-semibold">{{ formatRupiah(row.selling_price) }}</span>
                            </div>
                        </div>
                        <div v-if="importRows.length > 5" class="p-2 text-center text-[10px] text-slate-400 dark:text-zinc-500 bg-slate-50 dark:bg-zinc-900">
                            ... dan {{ importRows.length - 5 }} produk lainnya
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2">
                    <button
                        @click="isImportModalOpen = false"
                        type="button"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-semibold text-xs cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitBatchImport"
                        :disabled="importRows.length === 0"
                        type="button"
                        class="px-5 py-2 rounded-xl bg-brand hover:opacity-90 disabled:opacity-50 text-white font-bold text-xs shadow-sm shadow-brand/20 cursor-pointer"
                    >
                        Impor {{ importRows.length }} Data Sekarang
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="deleteModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
        >
            <div class="bg-white dark:bg-[#141417] rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 dark:border-zinc-800 text-center">
                <div class="w-12 h-12 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-600 flex items-center justify-center mx-auto mb-3">
                    <Trash2 class="w-6 h-6" />
                </div>
                <h4 class="text-base font-bold text-slate-900 dark:text-white">Hapus Data Produk?</h4>
                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">
                    Anda yakin ingin menghapus [<strong>{{ itemToDelete?.name }}</strong>]? Tindakan ini tidak dapat dibatalkan.
                </p>

                <div class="mt-6 flex items-center justify-center gap-2">
                    <button
                        @click="deleteModalOpen = false"
                        type="button"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-semibold text-xs cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        @click="executeDelete"
                        type="button"
                        class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-sm cursor-pointer"
                    >
                        Ya, Hapus Produk
                    </button>
                </div>
            </div>
        </div>

        <!-- Detail Product Modal -->
        <div
            v-if="isDetailModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
        >
            <div class="bg-white dark:bg-[#141417] rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-200 dark:border-zinc-800 space-y-5">
                <!-- Modal Header -->
                <div class="flex items-start justify-between border-b border-slate-200/80 dark:border-zinc-800/80 pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-md bg-brand/10 text-brand text-xs font-bold border border-brand/20">
                                {{ selectedProduct?.sku }}
                            </span>
                            <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 text-xs font-semibold">
                                {{ selectedProduct?.category }}
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mt-1.5">
                            {{ selectedProduct?.name }}
                        </h3>
                    </div>
                    <button
                        @click="isDetailModalOpen = false"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-zinc-200 transition-colors cursor-pointer"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Informasi Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/60 dark:border-zinc-800/60">
                        <span class="text-slate-400 dark:text-zinc-500 block text-[11px]">Barcode</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-zinc-100 mt-0.5 block">
                            {{ selectedProduct?.barcode || '-' }}
                        </span>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/60 dark:border-zinc-800/60">
                        <span class="text-slate-400 dark:text-zinc-500 block text-[11px]">Satuan Dasar</span>
                        <span class="font-bold text-slate-900 dark:text-zinc-100 mt-0.5 block">
                            {{ selectedProduct?.base_unit }}
                        </span>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/60 dark:border-zinc-800/60">
                        <span class="text-slate-400 dark:text-zinc-500 block text-[11px]">Status</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400 mt-0.5 block capitalize">
                            {{ selectedProduct?.status === 'active' ? 'Aktif Dijual' : 'Non-Aktif' }}
                        </span>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/60 dark:border-zinc-800/60">
                        <span class="text-slate-400 dark:text-zinc-500 block text-[11px]">Harga Beli (HPP)</span>
                        <span class="font-bold text-slate-900 dark:text-zinc-100 mt-0.5 block">
                            {{ formatRupiah(selectedProduct?.cost_price) }}
                        </span>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/60 dark:border-zinc-800/60">
                        <span class="text-slate-400 dark:text-zinc-500 block text-[11px]">Harga Jual Satuan</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400 mt-0.5 block">
                            {{ formatRupiah(selectedProduct?.selling_price) }}
                        </span>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-900/60 border border-slate-200/60 dark:border-zinc-800/60">
                        <span class="text-slate-400 dark:text-zinc-500 block text-[11px]">Estimasi Margin Laba</span>
                        <span class="font-bold text-brand mt-0.5 block">
                            {{ formatRupiah((selectedProduct?.selling_price || 0) - (selectedProduct?.cost_price || 0)) }}
                        </span>
                    </div>
                </div>

                <!-- Posisi Stok Gudang -->
                <div class="p-3.5 rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900/40 flex items-center justify-between text-xs">
                    <div>
                        <span class="text-slate-500 dark:text-zinc-400 block text-[11px]">Posisi Stok Gudang</span>
                        <span
                            class="text-base font-extrabold mt-0.5 block"
                            :class="selectedProduct?.current_stock <= selectedProduct?.min_stock ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-zinc-100'"
                        >
                            {{ selectedProduct?.current_stock }} {{ selectedProduct?.base_unit }}
                        </span>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-500 dark:text-zinc-400 block text-[11px]">Batas Stok Minimal</span>
                        <span class="font-bold text-slate-800 dark:text-zinc-200 mt-0.5 block">
                            {{ selectedProduct?.min_stock }} {{ selectedProduct?.base_unit }}
                        </span>
                    </div>
                </div>

                <!-- Daftar Satuan Grosir Multi-Satuan -->
                <div>
                    <h4 class="text-xs font-bold text-slate-800 dark:text-zinc-200 mb-2">
                        Konversi Multi-Satuan Grosir
                    </h4>
                    <div v-if="selectedProduct?.units && selectedProduct.units.length > 0" class="border border-slate-200 dark:border-zinc-800 rounded-xl overflow-hidden text-xs">
                        <table class="w-full text-left">
                            <thead class="bg-slate-100 dark:bg-zinc-800/60 text-slate-600 dark:text-zinc-400 font-semibold">
                                <tr>
                                    <th class="py-2 px-3">Nama Satuan</th>
                                    <th class="py-2 px-3">Rasio Konversi</th>
                                    <th class="py-2 px-3 text-right">Harga Jual Grosir</th>
                                    <th class="py-2 px-3">Barcode Satuan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/60 dark:divide-zinc-800/60">
                                <tr v-for="unit in selectedProduct.units" :key="unit.id">
                                    <td class="py-2 px-3 font-bold text-slate-900 dark:text-zinc-100">{{ unit.unit_name }}</td>
                                    <td class="py-2 px-3 text-slate-600 dark:text-zinc-400">1 {{ unit.unit_name }} = {{ unit.conversion_ratio }} {{ selectedProduct.base_unit }}</td>
                                    <td class="py-2 px-3 text-right font-bold text-emerald-600 dark:text-emerald-400">{{ formatRupiah(unit.selling_price) }}</td>
                                    <td class="py-2 px-3 font-mono text-slate-500 dark:text-zinc-400">{{ unit.barcode || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-900/40 border border-slate-200 dark:border-zinc-800 text-xs text-slate-500 dark:text-zinc-400 italic">
                        Produk ini hanya dijual dalam satuan dasar ({{ selectedProduct?.base_unit }}), belum ada satuan grosir tambahan.
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-200/80 dark:border-zinc-800/80">
                    <button
                        @click="isDetailModalOpen = false"
                        type="button"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 hover:bg-slate-200 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-300 font-semibold text-xs transition-colors cursor-pointer"
                    >
                        Tutup
                    </button>
                    <button
                        @click="isDetailModalOpen = false; openEditModal(selectedProduct)"
                        type="button"
                        class="px-4 py-2 rounded-xl bg-brand hover:opacity-90 text-white font-bold text-xs shadow-sm shadow-brand/20 transition-all cursor-pointer flex items-center gap-1.5"
                    >
                        <Edit class="w-3.5 h-3.5" />
                        <span>Edit Data Produk</span>
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
