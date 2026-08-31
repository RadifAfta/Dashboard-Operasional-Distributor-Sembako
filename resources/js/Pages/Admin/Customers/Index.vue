<script setup>
import { ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast';
import {
    Users,
    UserPlus,
    Search,
    Edit,
    Trash2,
    Clock,
    Phone,
    MapPin,
    CreditCard,
    X,
    ChevronLeft,
    ChevronRight,
} from 'lucide-vue-next';

const props = defineProps({
    customers: {
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

let searchTimeout = null;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('admin.customers.index'),
            { search: search.value || undefined },
            { preserveState: true, replace: true }
        );
    }, 350);
};

const formatRupiah = (val) => {
    return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
};

// Modal Create & Edit
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = ref({
    name: '',
    phone: '',
    address: '',
    credit_limit: 10000000,
    status: 'active',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.value = {
        name: '',
        phone: '',
        address: '',
        credit_limit: 10000000,
        status: 'active',
    };
    isModalOpen.value = true;
};

const openEditModal = (c) => {
    isEditing.value = true;
    editingId.value = c.id;
    form.value = {
        name: c.name,
        phone: c.phone || '',
        address: c.address || '',
        credit_limit: Number(c.credit_limit || 0),
        status: c.status,
    };
    isModalOpen.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        router.put(route('admin.customers.update', editingId.value), form.value, {
            onSuccess: () => {
                isModalOpen.value = false;
                toast.success('Berhasil', 'Data pelanggan berhasil diperbarui.');
            },
        });
    } else {
        router.post(route('admin.customers.store'), form.value, {
            onSuccess: () => {
                isModalOpen.value = false;
                toast.success('Berhasil', 'Pelanggan grosir baru berhasil disimpan.');
            },
        });
    }
};

// Delete Customer
const deleteModalOpen = ref(false);
const customerToDelete = ref(null);

const confirmDelete = (c) => {
    customerToDelete.value = c;
    deleteModalOpen.value = true;
};

const executeDelete = () => {
    if (!customerToDelete.value) return;
    router.delete(route('admin.customers.destroy', customerToDelete.value.id), {
        onSuccess: () => {
            deleteModalOpen.value = false;
            customerToDelete.value = null;
        },
    });
};
</script>

<template>
    <AdminLayout title="Pelanggan Grosir & Mitra Warung">
        <Head title="Pelanggan Grosir - Toko Sembako" />

        <div class="space-y-6">
            <!-- 3 Stat Cards Pelanggan (Dark: #141417) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-5 rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Total Mitra Terdaftar</span>
                        <div class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ stats.total_customers }} Mitra</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-brand/10 text-brand flex items-center justify-center border border-brand/20">
                        <Users class="w-5 h-5" />
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Mitra Memiliki Piutang</span>
                        <div class="text-2xl font-extrabold text-amber-600 dark:text-amber-400 mt-1">{{ stats.active_debtors }} Mitra</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center border border-amber-500/20">
                        <Clock class="w-5 h-5" />
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-500 dark:text-zinc-400 uppercase tracking-wider">Total Piutang Berjalan</span>
                        <div class="text-2xl font-extrabold text-rose-600 dark:text-rose-400 mt-1">{{ formatRupiah(stats.total_outstanding_debt) }}</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center border border-rose-500/20">
                        <CreditCard class="w-5 h-5" />
                    </div>
                </div>
            </div>

            <!-- Customers Table Card -->
            <div class="rounded-2xl bg-white dark:bg-[#141417] border border-slate-200/80 dark:border-zinc-800/80 shadow-2xs overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-200/80 dark:border-zinc-800/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="relative max-w-sm flex-1">
                        <Search class="w-3.5 h-3.5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="search"
                            @input="handleSearch"
                            type="text"
                            placeholder="Cari nama toko, kode, kontak..."
                            class="w-full pl-9 pr-4 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900 text-slate-900 dark:text-zinc-100 placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all"
                        />
                    </div>

                    <button
                        @click="openCreateModal"
                        type="button"
                        class="px-3.5 py-1.5 rounded-lg bg-brand hover:opacity-90 text-white text-xs font-bold flex items-center gap-1.5 shadow-sm shadow-brand/20 transition-all cursor-pointer"
                    >
                        <UserPlus class="w-4 h-4" />
                        <span>+ Tambah Mitra Baru</span>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 dark:bg-[#18181C] text-slate-600 dark:text-zinc-400 font-semibold border-b border-slate-200/80 dark:border-zinc-800/80">
                            <tr>
                                <th class="py-3 px-4">Kode Mitra</th>
                                <th class="py-3 px-4">Nama Toko & Kontak</th>
                                <th class="py-3 px-4">Alamat Usaha</th>
                                <th class="py-3 px-4 text-right">Plafon Kredit</th>
                                <th class="py-3 px-4 text-right">Hutang Berjalan</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60 dark:divide-zinc-800/60">
                            <tr v-for="c in customers.data" :key="c.id" class="hover:bg-slate-50/80 dark:hover:bg-[#1c1c21] transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900 dark:text-zinc-100">
                                    {{ c.code }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900 dark:text-zinc-100">{{ c.name }}</div>
                                    <div v-if="c.phone" class="text-[11px] text-slate-500 dark:text-zinc-400 flex items-center gap-1 mt-0.5">
                                        <Phone class="w-3 h-3 text-slate-400" />
                                        <span>{{ c.phone }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 max-w-xs truncate text-slate-600 dark:text-zinc-400">
                                    {{ c.address || '-' }}
                                </td>
                                <td class="py-3 px-4 text-right font-medium text-slate-600 dark:text-zinc-400">
                                    {{ formatRupiah(c.credit_limit) }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <span
                                        class="font-extrabold text-sm"
                                        :class="c.current_debt > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400 dark:text-zinc-600'"
                                    >
                                        {{ formatRupiah(c.current_debt) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="c.status === 'active' ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900/40' : 'bg-slate-100 dark:bg-zinc-800 text-slate-500 dark:text-zinc-400'"
                                    >
                                        {{ c.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right space-x-1 whitespace-nowrap">
                                    <button
                                        @click="openEditModal(c)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-brand hover:bg-brand/10 transition-colors cursor-pointer"
                                    >
                                        <Edit class="w-4 h-4" />
                                    </button>
                                    <button
                                        @click="confirmDelete(c)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="customers.data.length === 0">
                                <td colspan="7" class="py-10 text-center text-slate-400 dark:text-zinc-500 font-medium">
                                    Belum ada pelanggan grosir terdaftar.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="customers.total > customers.per_page" class="p-4 border-t border-slate-200/80 dark:border-zinc-800/80 flex items-center justify-between text-xs text-slate-500 dark:text-zinc-400">
                    <div>
                        Menampilkan {{ customers.from }} - {{ customers.to }} dari {{ customers.total }} mitra
                    </div>
                    <div class="flex items-center gap-1">
                        <Link
                            v-if="customers.prev_page_url"
                            :href="customers.prev_page_url"
                            class="p-1.5 rounded-lg border border-slate-200 dark:border-zinc-800 hover:bg-slate-100 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-200"
                        >
                            <ChevronLeft class="w-4 h-4" />
                        </Link>
                        <span class="px-3 py-1 font-bold text-slate-900 dark:text-white">
                            Halaman {{ customers.current_page }} dari {{ customers.last_page }}
                        </span>
                        <Link
                            v-if="customers.next_page_url"
                            :href="customers.next_page_url"
                            class="p-1.5 rounded-lg border border-slate-200 dark:border-zinc-800 hover:bg-slate-100 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-200"
                        >
                            <ChevronRight class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create / Edit Customer Modal -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
        >
            <div class="bg-white dark:bg-[#141417] rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-zinc-800">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-zinc-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <Users class="w-5 h-5 text-brand" />
                        {{ isEditing ? 'Edit Mitra Grosir' : 'Daftarkan Mitra Baru' }}
                    </h3>
                    <button @click="isModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-zinc-200">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="mt-4 space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-zinc-300 mb-1">Nama Toko / Mitra *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Contoh: Toko Berkah Jaya (Warung Madura)"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white font-semibold"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-zinc-300 mb-1">No. WhatsApp / Telepon</label>
                        <input
                            v-model="form.phone"
                            type="text"
                            placeholder="081234567890"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-zinc-300 mb-1">Plafon Batas Kredit (Rp)</label>
                        <input
                            v-model.number="form.credit_limit"
                            type="number"
                            min="0"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white font-bold"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-zinc-300 mb-1">Alamat Lengkap Toko / Warung</label>
                        <textarea
                            v-model="form.address"
                            rows="2"
                            placeholder="Jl. Raya Pasar Induk..."
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white"
                        ></textarea>
                    </div>

                    <div v-if="isEditing">
                        <label class="block font-bold text-slate-700 dark:text-zinc-300 mb-1">Status Keanggotaan</label>
                        <select
                            v-model="form.status"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white"
                        >
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </div>

                    <div class="mt-6 flex justify-end gap-2 pt-2">
                        <button
                            @click="isModalOpen = false"
                            type="button"
                            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-semibold cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-5 py-2 rounded-xl bg-brand hover:opacity-90 text-white font-bold shadow-sm shadow-brand/20 cursor-pointer"
                        >
                            {{ isEditing ? 'Simpan Perubahan' : 'Daftarkan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Modal -->
        <div
            v-if="deleteModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
        >
            <div class="bg-white dark:bg-[#141417] rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 dark:border-zinc-800 text-center">
                <div class="w-12 h-12 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-600 flex items-center justify-center mx-auto mb-3">
                    <Trash2 class="w-6 h-6" />
                </div>
                <h4 class="text-base font-bold text-slate-900 dark:text-white">Hapus Mitra?</h4>
                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">
                    Anda yakin ingin menghapus [<strong>{{ customerToDelete?.name }}</strong>]?
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
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
