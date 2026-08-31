<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast';
import {
    ShoppingCart,
    Search,
    Barcode,
    Plus,
    Minus,
    Trash2,
    Check,
    CreditCard,
    DollarSign,
    Clock,
    Printer,
    ArrowRight,
    User,
    UserPlus,
    X,
    RotateCcw,
    Layers,
    AlertCircle,
    Store,
    Receipt,
} from 'lucide-vue-next';

const props = defineProps({
    products: {
        type: Array,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    customers: {
        type: Array,
        default: () => [],
    },
    nextInvoiceNumber: {
        type: String,
        default: 'TRX-001',
    },
    storeInfo: {
        type: Object,
        default: () => ({}),
    },
});

const toast = useToast();

// Auto-focus barcode input
const barcodeInput = ref(null);
const searchQuery = ref('');
const selectedCategory = ref('');

onMounted(() => {
    focusBarcode();
});

const focusBarcode = () => {
    nextTick(() => {
        barcodeInput.value?.focus();
    });
};

// Filtered product grid
const filteredProducts = computed(() => {
    let list = props.products;
    if (selectedCategory.value) {
        list = list.filter((p) => p.category === selectedCategory.value);
    }
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(
            (p) =>
                p.name.toLowerCase().includes(q) ||
                p.sku.toLowerCase().includes(q) ||
                (p.barcode && p.barcode.toLowerCase().includes(q)) ||
                p.units.some((u) => u.barcode && u.barcode.toLowerCase().includes(q))
        );
    }
    return list;
});

// Cart State
const cart = ref([]);

// Handle Barcode Scan / Enter Key in Search
const onSearchEnter = () => {
    if (!searchQuery.value) return;
    const q = searchQuery.value.trim().toLowerCase();

    // 1. Exact match barcode on product or unit
    for (const p of props.products) {
        if (p.barcode && p.barcode.toLowerCase() === q) {
            addToCart(p);
            searchQuery.value = '';
            return;
        }
        for (const u of p.units) {
            if (u.barcode && u.barcode.toLowerCase() === q) {
                addToCart(p, u);
                searchQuery.value = '';
                return;
            }
        }
    }

    // 2. Exact match SKU
    const matchedSku = props.products.find((p) => p.sku.toLowerCase() === q);
    if (matchedSku) {
        addToCart(matchedSku);
        searchQuery.value = '';
        return;
    }

    // 3. If only 1 product matches filter, add it
    if (filteredProducts.value.length === 1) {
        addToCart(filteredProducts.value[0]);
        searchQuery.value = '';
    }
};

// Add product to cart with chosen unit (default base unit)
const addToCart = (product, unit = null) => {
    if (product.current_stock <= 0) {
        toast.warning('Stok Habis', `Produk [${product.name}] saat ini stoknya 0 di gudang.`);
        return;
    }

    const unitId = unit ? unit.id : null;
    const unitName = unit ? unit.unit_name : product.base_unit;
    const ratio = unit ? Number(unit.conversion_ratio) : 1;
    const price = unit ? Number(unit.selling_price) : Number(product.selling_price);

    const existingIndex = cart.value.findIndex(
        (item) => item.product.id === product.id && item.selectedUnitId === unitId
    );

    if (existingIndex > -1) {
        const item = cart.value[existingIndex];
        const newQty = item.quantity + 1;
        const totalBaseRequired = newQty * ratio;

        if (totalBaseRequired > product.current_stock) {
            toast.warning('Batas Stok', `Maksimum stok ${product.name}: ${product.current_stock} ${product.base_unit}.`);
            return;
        }

        item.quantity = newQty;
    } else {
        if (ratio > product.current_stock) {
            toast.warning('Batas Stok', `Stok tidak mencukupi untuk kemasan ${unitName} (butuh ${ratio} ${product.base_unit}).`);
            return;
        }

        cart.value.push({
            product,
            selectedUnitId: unitId,
            unitName,
            conversionRatio: ratio,
            sellingPrice: price,
            quantity: 1,
            discount: 0,
        });
    }

    focusBarcode();
};

// Change Unit for an item in cart
const onUnitChange = (item) => {
    if (!item.selectedUnitId) {
        item.unitName = item.product.base_unit;
        item.conversionRatio = 1;
        item.sellingPrice = Number(item.product.selling_price);
    } else {
        const unit = item.product.units.find((u) => u.id === item.selectedUnitId);
        if (unit) {
            item.unitName = unit.unit_name;
            item.conversionRatio = Number(unit.conversion_ratio);
            item.sellingPrice = Number(unit.selling_price);
        }
    }
};

const updateQty = (item, delta) => {
    const newQty = item.quantity + delta;
    if (newQty <= 0) {
        removeFromCart(item);
        return;
    }

    const totalBaseRequired = newQty * item.conversionRatio;
    if (totalBaseRequired > item.product.current_stock) {
        toast.warning('Batas Stok', `Maksimum stok: ${item.product.current_stock} ${item.product.base_unit}.`);
        return;
    }

    item.quantity = newQty;
};

const removeFromCart = (item) => {
    const idx = cart.value.indexOf(item);
    if (idx > -1) cart.value.splice(idx, 1);
};

const clearCart = () => {
    cart.value = [];
    focusBarcode();
};

// Calculations
const subtotalAmount = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.sellingPrice * item.quantity, 0);
});

const discountTotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + Number(item.discount || 0), 0);
});

const grandTotal = computed(() => {
    return Math.max(0, subtotalAmount.value - discountTotal.value);
});

// Format Currency
const formatRupiah = (val) => {
    return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
};

// Payment Modal State
const isPaymentModalOpen = ref(false);
const paymentMethod = ref('cash');
const cashGiven = ref(0);
const bankName = ref('BCA QRIS');
const referenceNumber = ref('');
const selectedCustomerId = ref('');
const creditDays = ref(14);
const orderNotes = ref('');

const cashChange = computed(() => {
    if (paymentMethod.value !== 'cash') return 0;
    return Math.max(0, Number(cashGiven.value || 0) - grandTotal.value);
});

const isCashSufficient = computed(() => {
    return Number(cashGiven.value || 0) >= grandTotal.value;
});

const openPaymentModal = () => {
    if (cart.value.length === 0) {
        toast.warning('Keranjang Kosong', 'Tambahkan barang terlebih dahulu ke keranjang.');
        return;
    }
    cashGiven.value = grandTotal.value;
    isPaymentModalOpen.value = true;
};

const setQuickCash = (amount) => {
    cashGiven.value = amount;
};

// Submit Checkout
const isSubmitting = ref(false);
const completedTransaction = ref(null);
const isReceiptModalOpen = ref(false);
const receiptSize = ref('58mm');

const submitCheckout = () => {
    if (paymentMethod.value === 'credit' && !selectedCustomerId.value) {
        toast.error('Pelanggan Wajib Dipilih', 'Penjualan tempo wajib memilih pelanggan terdaftar.');
        return;
    }

    if (paymentMethod.value === 'cash' && !isCashSufficient.value) {
        toast.error('Uang Kurang', 'Nominal uang yang diterima kurang dari total belanja.');
        return;
    }

    isSubmitting.value = true;

    const payload = {
        items: cart.value.map((item) => ({
            product_id: item.product.id,
            product_unit_id: item.selectedUnitId,
            quantity: item.quantity,
            discount: item.discount,
        })),
        payment_method: paymentMethod.value,
        cash_given: paymentMethod.value === 'cash' ? cashGiven.value : null,
        bank_name: paymentMethod.value === 'transfer' ? bankName.value : null,
        reference_number: paymentMethod.value === 'transfer' ? referenceNumber.value : null,
        customer_id: paymentMethod.value === 'credit' ? selectedCustomerId.value : null,
        credit_days: paymentMethod.value === 'credit' ? creditDays.value : null,
        notes: orderNotes.value,
    };

    router.post(route('admin.pos.checkout'), payload, {
        onSuccess: (page) => {
            isPaymentModalOpen.value = false;
            isSubmitting.value = false;

            completedTransaction.value = page.props.flash?.lastTransaction || {
                invoice_number: props.nextInvoiceNumber,
                created_at: new Date().toISOString(),
                payment_method: paymentMethod.value,
                total_amount: grandTotal.value,
                subtotal: subtotalAmount.value,
                discount_amount: discountTotal.value,
                cash_given: cashGiven.value,
                cash_change: cashChange.value,
                bank_name: bankName.value,
                reference_number: referenceNumber.value,
                customer: props.customers.find((c) => c.id === selectedCustomerId.value),
                due_date: new Date(Date.now() + creditDays.value * 86400000).toISOString().split('T')[0],
                items: cart.value.map((c) => ({
                    product_name: c.product.name,
                    unit_name: c.unitName,
                    quantity: c.quantity,
                    selling_price: c.sellingPrice,
                    subtotal: c.sellingPrice * c.quantity - (c.discount || 0),
                })),
            };

            isReceiptModalOpen.value = true;
            cart.value = [];
            focusBarcode();
            toast.success('Sukses', 'Transaksi kasir berhasil diselesaikan.');
        },
        onError: (errors) => {
            isSubmitting.value = false;
            toast.error('Gagal Transaksi', Object.values(errors)[0] || 'Terjadi kesalahan pemrosesan kasir.');
        },
    });
};

const printReceipt = () => {
    window.print();
};

// Quick Add Customer modal
const isQuickCustomerModalOpen = ref(false);
const newCustomerName = ref('');
const newCustomerPhone = ref('');
const newCustomerAddress = ref('');

const submitQuickCustomer = () => {
    if (!newCustomerName.value) return;
    router.post(
        route('admin.customers.store'),
        {
            name: newCustomerName.value,
            phone: newCustomerPhone.value,
            address: newCustomerAddress.value,
        },
        {
            onSuccess: () => {
                isQuickCustomerModalOpen.value = false;
                newCustomerName.value = '';
                newCustomerPhone.value = '';
                newCustomerAddress.value = '';
                toast.success('Pelanggan Baru', 'Pelanggan berhasil ditambahkan.');
            },
        }
    );
};
</script>

<template>
    <AdminLayout title="Kasir Penjualan POS">
        <Head title="Kasir POS - Distributor Sembako" />

        <div class="h-[calc(100vh-6.5rem)] flex flex-col lg:flex-row gap-4 overflow-hidden">
            <!-- LEFT PANEL: Product Grid & Barcode Scanner (Dark: #141417) -->
            <div class="flex-1 flex flex-col bg-white dark:bg-[#141417] rounded-2xl border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs overflow-hidden">
                <!-- Barcode & Search Top Bar -->
                <div class="p-3.5 border-b border-slate-200/80 dark:border-zinc-800/80 bg-slate-50/60 dark:bg-[#18181C] flex flex-wrap items-center gap-2.5">
                    <div class="relative flex-1 min-w-[260px]">
                        <Barcode class="w-4 h-4 text-brand absolute left-3.5 top-1/2 -translate-y-1/2" />
                        <input
                            ref="barcodeInput"
                            v-model="searchQuery"
                            @keydown.enter.prevent="onSearchEnter"
                            type="text"
                            placeholder="Scan Barcode / Ketik SKU / Nama Barang (Tekan Enter)..."
                            class="w-full pl-9 pr-4 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-slate-900 dark:text-zinc-100 placeholder-slate-400 dark:placeholder-zinc-500 font-medium focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all"
                        />
                    </div>

                    <!-- Category Pills -->
                    <div class="flex items-center gap-1 overflow-x-auto py-1 max-w-full">
                        <button
                            @click="selectedCategory = ''"
                            type="button"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold whitespace-nowrap transition-all cursor-pointer"
                            :class="selectedCategory === '' ? 'bg-brand text-white shadow-sm' : 'bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700'"
                        >
                            Semua ({{ products.length }})
                        </button>
                        <button
                            v-for="cat in categories"
                            :key="cat"
                            @click="selectedCategory = cat"
                            type="button"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold whitespace-nowrap transition-all cursor-pointer"
                            :class="selectedCategory === cat ? 'bg-brand text-white shadow-sm' : 'bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700'"
                        >
                            {{ cat }}
                        </button>
                    </div>
                </div>

                <!-- Product Cards Grid -->
                <div class="flex-1 overflow-y-auto p-4 grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                    <div
                        v-for="product in filteredProducts"
                        :key="product.id"
                        class="p-3 rounded-xl border border-slate-200 dark:border-zinc-800/80 hover:border-brand/40 bg-white dark:bg-zinc-900/60 hover:shadow-md transition-all flex flex-col justify-between group cursor-pointer"
                        @click="addToCart(product)"
                    >
                        <div>
                            <div class="flex items-center justify-between gap-1 text-[10px] text-slate-400 dark:text-zinc-500 font-mono">
                                <span class="truncate">{{ product.sku }}</span>
                                <span
                                    class="px-1.5 py-0.5 rounded font-bold"
                                    :class="product.current_stock <= product.min_stock ? 'bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400' : 'bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-300'"
                                >
                                    {{ product.current_stock }} {{ product.base_unit }}
                                </span>
                            </div>

                            <h4 class="font-bold text-xs text-slate-900 dark:text-zinc-100 mt-1.5 line-clamp-2 group-hover:text-brand transition-colors">
                                {{ product.name }}
                            </h4>
                        </div>

                        <div class="mt-3 pt-2 border-t border-slate-100 dark:border-zinc-800">
                            <!-- Eceran Price -->
                            <div class="flex items-baseline justify-between">
                                <span class="text-[10px] text-slate-400 dark:text-zinc-500">Eceran</span>
                                <span class="font-extrabold text-xs text-slate-900 dark:text-zinc-100">
                                    {{ formatRupiah(product.selling_price) }}
                                </span>
                            </div>

                            <!-- Wholesale Unit Quick Badges -->
                            <div v-if="product.units.length > 0" class="mt-1.5 flex flex-wrap gap-1">
                                <button
                                    v-for="unit in product.units"
                                    :key="unit.id"
                                    @click.stop="addToCart(product, unit)"
                                    type="button"
                                    class="px-1.5 py-0.5 rounded bg-brand/10 hover:bg-brand hover:text-white border border-brand/20 text-brand text-[10px] font-bold transition-all flex items-center gap-1 cursor-pointer"
                                    :title="`Beli Grosir: 1 ${unit.unit_name} = ${unit.conversion_ratio} ${product.base_unit}`"
                                >
                                    <span>+ {{ unit.unit_name }}</span>
                                    <span class="opacity-80">({{ formatRupiah(unit.selling_price) }})</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT PANEL: Shopping Cart & Checkout (Dark: #141417) -->
            <div class="w-full lg:w-96 xl:w-[420px] bg-white dark:bg-[#141417] rounded-2xl border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs flex flex-col justify-between overflow-hidden">
                <!-- Cart Header -->
                <div class="p-3.5 border-b border-slate-200/80 dark:border-zinc-800/80 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-brand/10 text-brand flex items-center justify-center border border-brand/20">
                            <ShoppingCart class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                Keranjang Kasir
                                <span class="px-1.5 py-0.5 rounded-full bg-brand text-white text-[10px] font-extrabold">
                                    {{ cart.length }} Item
                                </span>
                            </h3>
                            <span class="text-[10px] text-slate-400 dark:text-zinc-500 font-mono">{{ nextInvoiceNumber }}</span>
                        </div>
                    </div>

                    <button
                        v-if="cart.length > 0"
                        @click="clearCart"
                        type="button"
                        class="text-[11px] text-rose-500 hover:text-rose-600 font-semibold flex items-center gap-1 cursor-pointer"
                    >
                        <RotateCcw class="w-3 h-3" /> Bersihkan
                    </button>
                </div>

                <!-- Cart Items List -->
                <div class="flex-1 overflow-y-auto p-3 space-y-2.5 divide-y divide-slate-200/60 dark:divide-zinc-800/60">
                    <div
                        v-for="(item, idx) in cart"
                        :key="idx"
                        class="pt-2 first:pt-0 text-xs"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <h5 class="font-bold text-slate-900 dark:text-zinc-100 truncate">
                                    {{ item.product.name }}
                                </h5>

                                <!-- Unit Selector: Satuan Dasar vs Satuan Grosir -->
                                <div class="mt-1 flex items-center gap-2">
                                    <select
                                        v-model="item.selectedUnitId"
                                        @change="onUnitChange(item)"
                                        class="py-0.5 px-2 rounded-lg border border-slate-200 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800 text-[11px] font-bold text-brand focus:ring-1 focus:ring-brand"
                                    >
                                        <option :value="null">Eceran ({{ item.product.base_unit }})</option>
                                        <option
                                            v-for="u in item.product.units"
                                            :key="u.id"
                                            :value="u.id"
                                        >
                                            {{ u.unit_name }} (x{{ u.conversion_ratio }})
                                        </option>
                                    </select>

                                    <span class="text-[11px] text-slate-400 dark:text-zinc-400">
                                        @ {{ formatRupiah(item.sellingPrice) }}
                                    </span>
                                </div>
                            </div>

                            <button
                                @click="removeFromCart(item)"
                                class="text-slate-400 hover:text-rose-500 p-1 cursor-pointer"
                            >
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </div>

                        <!-- Quantity and Subtotal Row -->
                        <div class="mt-2 flex items-center justify-between">
                            <!-- Qty Buttons -->
                            <div class="flex items-center border border-slate-200 dark:border-zinc-700 rounded-lg overflow-hidden bg-slate-50 dark:bg-zinc-800">
                                <button
                                    @click="updateQty(item, -1)"
                                    type="button"
                                    class="px-2 py-1 hover:bg-slate-200 dark:hover:bg-zinc-700 text-slate-600 dark:text-zinc-300 cursor-pointer"
                                >
                                    <Minus class="w-3 h-3" />
                                </button>
                                <span class="px-2.5 font-extrabold text-slate-900 dark:text-white">
                                    {{ item.quantity }}
                                </span>
                                <button
                                    @click="updateQty(item, 1)"
                                    type="button"
                                    class="px-2 py-1 hover:bg-slate-200 dark:hover:bg-zinc-700 text-slate-600 dark:text-zinc-300 cursor-pointer"
                                >
                                    <Plus class="w-3 h-3" />
                                </button>
                            </div>

                            <!-- Item Subtotal -->
                            <span class="font-extrabold text-slate-900 dark:text-white text-xs">
                                {{ formatRupiah(item.sellingPrice * item.quantity - (item.discount || 0)) }}
                            </span>
                        </div>
                    </div>

                    <div v-if="cart.length === 0" class="py-16 text-center text-slate-400 dark:text-zinc-600">
                        <ShoppingCart class="w-10 h-10 mx-auto text-slate-300 dark:text-zinc-700 mb-2" />
                        <p class="font-bold text-xs text-slate-600 dark:text-zinc-400">Keranjang Masih Kosong</p>
                        <p class="text-[11px] text-slate-400 dark:text-zinc-500 mt-0.5">Scan barcode atau klik barang di sebelah kiri</p>
                    </div>
                </div>

                <!-- Cart Bottom Summary & Checkout Button -->
                <div class="p-4 border-t border-slate-200/80 dark:border-zinc-800/80 bg-slate-50/70 dark:bg-[#18181C] space-y-2.5">
                    <div class="flex items-center justify-between text-xs text-slate-500 dark:text-zinc-400">
                        <span>Subtotal Belanja</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ formatRupiah(subtotalAmount) }}</span>
                    </div>
                    <div v-if="discountTotal > 0" class="flex items-center justify-between text-xs text-rose-500">
                        <span>Potongan Diskon</span>
                        <span class="font-bold">- {{ formatRupiah(discountTotal) }}</span>
                    </div>
                    <div class="pt-2 border-t border-slate-200 dark:border-zinc-800 flex items-baseline justify-between">
                        <span class="font-extrabold text-slate-900 dark:text-white text-sm">TOTAL BAYAR</span>
                        <span class="text-xl font-black text-brand tracking-tight">
                            {{ formatRupiah(grandTotal) }}
                        </span>
                    </div>

                    <!-- Checkout Trigger Button -->
                    <button
                        @click="openPaymentModal"
                        :disabled="cart.length === 0"
                        type="button"
                        class="w-full py-3 rounded-xl bg-brand hover:opacity-90 disabled:opacity-40 text-white font-extrabold text-sm shadow-md shadow-brand/20 flex items-center justify-center gap-2 transition-all transform active:scale-98 cursor-pointer"
                    >
                        <span>Pilih Pembayaran (F4)</span>
                        <ArrowRight class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- PAYMENT MODAL -->
        <div
            v-if="isPaymentModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
        >
            <div class="bg-white dark:bg-[#141417] rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-zinc-800">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-zinc-800">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <CreditCard class="w-5 h-5 text-brand" />
                            Pembayaran Kasir
                        </h3>
                        <span class="text-xs text-slate-400 dark:text-zinc-500 font-mono">{{ nextInvoiceNumber }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 dark:text-zinc-500 block">Total Tagihan</span>
                        <span class="text-lg font-black text-brand">{{ formatRupiah(grandTotal) }}</span>
                    </div>
                </div>

                <!-- Tabs: Metode Pembayaran -->
                <div class="grid grid-cols-3 gap-2 mt-4">
                    <button
                        @click="paymentMethod = 'cash'"
                        type="button"
                        class="py-2.5 px-3 rounded-xl text-xs font-bold border flex flex-col items-center gap-1 transition-all cursor-pointer"
                        :class="paymentMethod === 'cash' ? 'bg-brand/10 border-brand text-brand shadow-sm' : 'border-slate-200 dark:border-zinc-800 text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800'"
                    >
                        <DollarSign class="w-4 h-4" />
                        <span>Tunai (Cash)</span>
                    </button>

                    <button
                        @click="paymentMethod = 'transfer'"
                        type="button"
                        class="py-2.5 px-3 rounded-xl text-xs font-bold border flex flex-col items-center gap-1 transition-all cursor-pointer"
                        :class="paymentMethod === 'transfer' ? 'bg-blue-500/10 border-blue-500 text-blue-600 dark:text-blue-400 shadow-sm' : 'border-slate-200 dark:border-zinc-800 text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800'"
                    >
                        <CreditCard class="w-4 h-4" />
                        <span>QRIS / Bank</span>
                    </button>

                    <button
                        @click="paymentMethod = 'credit'"
                        type="button"
                        class="py-2.5 px-3 rounded-xl text-xs font-bold border flex flex-col items-center gap-1 transition-all cursor-pointer"
                        :class="paymentMethod === 'credit' ? 'bg-amber-500/10 border-amber-500 text-amber-600 dark:text-amber-400 shadow-sm' : 'border-slate-200 dark:border-zinc-800 text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800'"
                    >
                        <Clock class="w-4 h-4" />
                        <span>Tempo (Kredit)</span>
                    </button>
                </div>

                <!-- Tab Content: 1. Tunai -->
                <div v-if="paymentMethod === 'cash'" class="mt-4 space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-zinc-300 mb-1">
                            Uang Diterima dari Pelanggan (Rp)
                        </label>
                        <input
                            v-model.number="cashGiven"
                            type="number"
                            min="0"
                            class="w-full px-3 py-2 text-lg rounded-xl border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white font-black focus:ring-2 focus:ring-brand"
                        />
                    </div>

                    <!-- Quick Amount Buttons -->
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            @click="setQuickCash(grandTotal)"
                            type="button"
                            class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-zinc-800 hover:bg-slate-200 text-slate-700 dark:text-zinc-300 font-bold text-[11px] cursor-pointer"
                        >
                            Uang Pas
                        </button>
                        <button
                            v-for="amt in [50000, 100000, 200000, 500000]"
                            :key="amt"
                            @click="setQuickCash(amt)"
                            type="button"
                            class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-zinc-800 hover:bg-slate-200 text-slate-700 dark:text-zinc-300 font-bold text-[11px] cursor-pointer"
                        >
                            {{ formatRupiah(amt) }}
                        </button>
                    </div>

                    <!-- Kembalian Display -->
                    <div
                        class="p-3.5 rounded-xl border flex items-center justify-between"
                        :class="isCashSufficient ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-300 dark:border-emerald-900/50 text-emerald-800 dark:text-emerald-300' : 'bg-rose-50 dark:bg-rose-950/30 border-rose-300 dark:border-rose-900/50 text-rose-800 dark:text-rose-300'"
                    >
                        <span class="font-bold">Uang Kembalian:</span>
                        <span class="text-xl font-black">
                            {{ formatRupiah(cashChange) }}
                        </span>
                    </div>
                </div>

                <!-- Tab Content: 2. Transfer / QRIS -->
                <div v-else-if="paymentMethod === 'transfer'" class="mt-4 space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-zinc-300 mb-1">Nama Bank / QRIS Merchant</label>
                        <select
                            v-model="bankName"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white"
                        >
                            <option value="BCA QRIS">BCA (QRIS Dinamis / Transfer)</option>
                            <option value="Mandiri Livin">Bank Mandiri (QRIS / Transfer)</option>
                            <option value="BRImo">BRI (QRIS / Transfer)</option>
                            <option value="BNI">BNI (QRIS / Transfer)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-zinc-300 mb-1">Nomor Referensi Transaksi</label>
                        <input
                            v-model="referenceNumber"
                            type="text"
                            placeholder="Contoh: REF-889127498"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white font-mono"
                        />
                    </div>
                </div>

                <!-- Tab Content: 3. Tempo -->
                <div v-else class="mt-4 space-y-3 text-xs">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="font-bold text-slate-700 dark:text-zinc-300">Pilih Pelanggan Terdaftar *</label>
                            <button
                                @click="isQuickCustomerModalOpen = true"
                                type="button"
                                class="text-brand hover:underline font-bold flex items-center gap-1 text-[11px] cursor-pointer"
                            >
                                <UserPlus class="w-3 h-3" /> + Pelanggan Baru
                            </button>
                        </div>
                        <select
                            v-model="selectedCustomerId"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white font-semibold"
                        >
                            <option value="">-- Pilih Toko Mitra / Pelanggan --</option>
                            <option
                                v-for="c in customers"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }} (Hutang Berjalan: {{ formatRupiah(c.current_debt) }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-zinc-300 mb-1">Durasi Jatuh Tempo</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                v-for="d in [7, 14, 30]"
                                :key="d"
                                @click="creditDays = d"
                                type="button"
                                class="py-2 rounded-xl border font-bold text-xs transition-all cursor-pointer"
                                :class="creditDays === d ? 'bg-amber-500 text-white border-amber-500 shadow-sm' : 'border-slate-200 dark:border-zinc-800 text-slate-700 dark:text-zinc-300'"
                            >
                                {{ d }} Hari
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Action Footer -->
                <div class="mt-6 flex items-center justify-end gap-2">
                    <button
                        @click="isPaymentModalOpen = false"
                        type="button"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-semibold text-xs cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitCheckout"
                        :disabled="isSubmitting"
                        type="button"
                        class="px-5 py-2.5 rounded-xl bg-brand hover:opacity-90 disabled:opacity-50 text-white font-bold text-xs shadow-md shadow-brand/20 flex items-center gap-2 cursor-pointer"
                    >
                        <Check class="w-4 h-4" />
                        <span>Selesaikan & Cetak Struk</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- THERMAL RECEIPT PREVIEW MODAL (58mm / 80mm) -->
        <div
            v-if="isReceiptModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs print:p-0 print:bg-white"
        >
            <div class="bg-white dark:bg-[#141417] rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-zinc-800 print:shadow-none print:border-none print:p-0">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-zinc-800 print:hidden">
                    <div class="flex items-center gap-2">
                        <Receipt class="w-5 h-5 text-brand" />
                        <h4 class="font-bold text-slate-900 dark:text-white text-sm">Simulasi Cetak Nota</h4>
                    </div>

                    <div class="flex items-center bg-slate-100 dark:bg-zinc-800 p-0.5 rounded-lg text-[10px] font-bold">
                        <button
                            @click="receiptSize = '58mm'"
                            type="button"
                            class="px-2 py-1 rounded cursor-pointer"
                            :class="receiptSize === '58mm' ? 'bg-white dark:bg-zinc-700 shadow-sm text-brand' : 'text-slate-500'"
                        >
                            58mm
                        </button>
                        <button
                            @click="receiptSize = '80mm'"
                            type="button"
                            class="px-2 py-1 rounded cursor-pointer"
                            :class="receiptSize === '80mm' ? 'bg-white dark:bg-zinc-700 shadow-sm text-brand' : 'text-slate-500'"
                        >
                            80mm
                        </button>
                    </div>
                </div>

                <!-- Printable Thermal Receipt Paper -->
                <div
                    class="my-4 p-4 bg-amber-50/20 dark:bg-zinc-900/60 border border-slate-200 dark:border-zinc-800 rounded-xl font-mono text-[11px] text-slate-800 dark:text-zinc-200 leading-tight mx-auto transition-all print:bg-white print:border-none print:p-0"
                    :style="{ width: receiptSize === '58mm' ? '280px' : '360px' }"
                >
                    <div class="text-center pb-2 border-b border-dashed border-slate-300 dark:border-zinc-700">
                        <h2 class="font-bold text-sm text-slate-900 dark:text-white uppercase">{{ storeInfo.name }}</h2>
                        <p class="text-[10px] mt-0.5">{{ storeInfo.address }}</p>
                        <p class="text-[10px]">Telp: {{ storeInfo.phone }}</p>
                    </div>

                    <div class="py-2 border-b border-dashed border-slate-300 dark:border-zinc-700 text-[10px] space-y-0.5">
                        <div class="flex justify-between">
                            <span>No. Nota:</span>
                            <span class="font-bold">{{ completedTransaction?.invoice_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Waktu:</span>
                            <span>{{ new Date().toLocaleString('id-ID') }}</span>
                        </div>
                        <div v-if="completedTransaction?.customer" class="flex justify-between">
                            <span>Pelanggan:</span>
                            <span class="font-bold">{{ completedTransaction?.customer?.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Metode Bayar:</span>
                            <span class="font-bold uppercase">{{ completedTransaction?.payment_method }}</span>
                        </div>
                    </div>

                    <div class="py-2 border-b border-dashed border-slate-300 dark:border-zinc-700 space-y-1.5">
                        <div
                            v-for="(item, idx) in completedTransaction?.items"
                            :key="idx"
                        >
                            <div class="font-bold text-slate-900 dark:text-white truncate">
                                {{ item.product_name }}
                            </div>
                            <div class="flex justify-between text-[10px] text-slate-500 dark:text-zinc-400">
                                <span>{{ item.quantity }} {{ item.unit_name }} x {{ formatRupiah(item.selling_price) }}</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ formatRupiah(item.subtotal) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="py-2 border-b border-dashed border-slate-300 dark:border-zinc-700 text-[10px] space-y-1">
                        <div class="flex justify-between font-bold text-xs text-slate-900 dark:text-white">
                            <span>TOTAL:</span>
                            <span>{{ formatRupiah(completedTransaction?.total_amount) }}</span>
                        </div>

                        <div v-if="completedTransaction?.payment_method === 'cash'" class="space-y-0.5">
                            <div class="flex justify-between">
                                <span>Tunai:</span>
                                <span>{{ formatRupiah(completedTransaction?.cash_given) }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-emerald-600">
                                <span>Kembalian:</span>
                                <span>{{ formatRupiah(completedTransaction?.cash_change) }}</span>
                            </div>
                        </div>

                        <div v-else-if="completedTransaction?.payment_method === 'credit'" class="space-y-0.5 text-amber-500 font-semibold">
                            <div class="flex justify-between">
                                <span>STATUS:</span>
                                <span>TEMPO / BELUM LUNAS</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Jatuh Tempo:</span>
                                <span>{{ completedTransaction?.due_date }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 text-center text-[9px] text-slate-500 dark:text-zinc-500">
                        <p>Terima kasih atas kunjungan Anda!</p>
                        <p>Barang yang sudah dibeli tidak dapat ditukar kecuali perjanjian sebelumnya.</p>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-end gap-2 print:hidden">
                    <button
                        @click="isReceiptModalOpen = false"
                        type="button"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold text-xs cursor-pointer"
                    >
                        Tutup
                    </button>
                    <button
                        @click="printReceipt"
                        type="button"
                        class="px-5 py-2 rounded-xl bg-brand hover:opacity-90 text-white font-bold text-xs shadow-sm shadow-brand/20 flex items-center gap-2 cursor-pointer"
                    >
                        <Printer class="w-4 h-4" />
                        <span>Cetak Struk Thermal</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- QUICK ADD CUSTOMER MODAL -->
        <div
            v-if="isQuickCustomerModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
        >
            <div class="bg-white dark:bg-[#141417] rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 dark:border-zinc-800">
                <h4 class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                    <UserPlus class="w-4 h-4 text-brand" /> Tambah Pelanggan Baru
                </h4>
                <div class="mt-3 space-y-2.5 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Nama Toko / Pelanggan *</label>
                        <input
                            v-model="newCustomerName"
                            type="text"
                            placeholder="Contoh: Toko Barokah Madura"
                            class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white"
                        />
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">No. WhatsApp / HP</label>
                        <input
                            v-model="newCustomerPhone"
                            type="text"
                            placeholder="0812..."
                            class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white"
                        />
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button
                        @click="isQuickCustomerModalOpen = false"
                        type="button"
                        class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-zinc-800 text-xs font-semibold text-slate-700 dark:text-zinc-300 cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitQuickCustomer"
                        type="button"
                        class="px-4 py-1.5 rounded-lg bg-brand text-white text-xs font-bold shadow-sm cursor-pointer"
                    >
                        Simpan Pelanggan
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
