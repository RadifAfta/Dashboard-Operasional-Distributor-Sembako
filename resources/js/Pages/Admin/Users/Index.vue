<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import Badge from '@/Components/Admin/Badge.vue';
import ConfirmationModal from '@/Components/Admin/ConfirmationModal.vue';
import {
    UserPlus,
    Trash2,
    Edit2,
    Shield,
    X,
    Check,
} from 'lucide-vue-next';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    roles: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const columns = [
    { key: 'user', label: 'Pengguna', sortable: true },
    { key: 'role', label: 'Role / Hak Akses', sortable: false },
    { key: 'created_at', label: 'Tanggal Terdaftar', sortable: true },
];

const selectedUsers = ref([]);

// Modals State
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showBulkDeleteModal = ref(false);
const userToDelete = ref(null);

// Forms
const createForm = useForm({
    name: '',
    email: '',
    password: '',
    role: 'User',
});

const editForm = useForm({
    id: null,
    name: '',
    email: '',
    password: '',
    role: 'User',
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    showCreateModal.value = true;
};

const submitCreate = () => {
    createForm.post(route('admin.users.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

const openEditModal = (user) => {
    editForm.clearErrors();
    editForm.id = user.id;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.password = '';
    editForm.role = user.roles?.[0]?.name || 'User';
    showEditModal.value = true;
};

const submitEdit = () => {
    editForm.put(route('admin.users.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
        },
    });
};

const confirmDelete = (user) => {
    userToDelete.value = user;
    showDeleteModal.value = true;
};

const deleteUser = () => {
    if (!userToDelete.value) return;

    router.delete(route('admin.users.destroy', userToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            userToDelete.value = null;
        },
    });
};

const bulkDelete = () => {
    router.post(route('admin.users.bulk-delete'), { ids: selectedUsers.value }, {
        preserveScroll: true,
        onSuccess: () => {
            showBulkDeleteModal.value = false;
            selectedUsers.value = [];
        },
    });
};

const handleRoleFilter = (e) => {
    const role = e.target.value;
    const currentParams = { ...props.filters, role: role || undefined, page: 1 };
    router.get(route('admin.users.index'), currentParams, { preserveState: true, replace: true });
};
</script>

<template>
    <AdminLayout title="Manajemen Pengguna">
        <Head title="Manajemen Pengguna" />

        <div class="space-y-6">
            <!-- Header Title and Action -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Daftar Pengguna
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Kelola data akun admin dan pengguna terdaftar dalam sistem.
                    </p>
                </div>
            </div>

            <!-- Reusable DataTable -->
            <DataTable
                :columns="columns"
                :pagination="users"
                :filters="filters"
                :selectable="true"
                v-model:selectedItems="selectedUsers"
                searchPlaceholder="Cari nama atau email pengguna..."
            >
                <!-- Filters Slot: Role Dropdown -->
                <template #filters>
                    <div class="flex items-center gap-2">
                        <select
                            :value="filters.role || ''"
                            @change="handleRoleFilter"
                            class="py-2 px-3 text-xs bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-300"
                        >
                            <option value="">Semua Role</option>
                            <option v-for="r in roles" :key="r.id" :value="r.name">
                                Role: {{ r.name }}
                            </option>
                        </select>
                    </div>
                </template>

                <!-- Actions Slot: Add User Button -->
                <template #actions>
                    <button
                        type="button"
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-sm transition shadow-indigo-600/20"
                    >
                        <UserPlus class="w-4 h-4" />
                        <span>Tambah Pengguna</span>
                    </button>
                </template>

                <!-- Bulk Actions Toolbar -->
                <template #bulk-actions>
                    <button
                        type="button"
                        @click="showBulkDeleteModal = true"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 dark:bg-rose-950/40 dark:border-rose-900 dark:text-rose-400 transition"
                    >
                        <Trash2 class="w-3.5 h-3.5" />
                        <span>Hapus Terpilih</span>
                    </button>
                </template>

                <!-- Custom Cell for User Info -->
                <template #cell(user)="{ row }">
                    <div class="flex items-center gap-3">
                        <img
                            :src="row.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(row.name)}`"
                            alt="Avatar"
                            class="w-9 h-9 rounded-xl object-cover ring-1 ring-slate-200 dark:ring-slate-800"
                        />
                        <div class="min-w-0">
                            <p class="font-bold text-xs text-slate-900 dark:text-white truncate">
                                {{ row.name }}
                            </p>
                            <p class="text-[11px] text-slate-400 truncate">
                                {{ row.email }}
                            </p>
                        </div>
                    </div>
                </template>

                <!-- Custom Cell for Role -->
                <template #cell(role)="{ row }">
                    <Badge
                        :variant="row.roles?.[0]?.name === 'Super Admin' ? 'danger' : (row.roles?.[0]?.name === 'Admin' ? 'primary' : 'neutral')"
                        size="sm"
                    >
                        <Shield class="w-3 h-3" />
                        <span>{{ row.roles?.[0]?.name || 'Tanpa Role' }}</span>
                    </Badge>
                </template>

                <!-- Custom Cell for Date -->
                <template #cell(created_at)="{ value }">
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        {{ new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                    </span>
                </template>

                <!-- Row Actions -->
                <template #rowActions="{ row }">
                    <div class="flex items-center justify-end gap-1">
                        <button
                            type="button"
                            @click="openEditModal(row)"
                            class="p-1.5 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                            title="Edit"
                        >
                            <Edit2 class="w-4 h-4" />
                        </button>
                        <button
                            type="button"
                            @click="confirmDelete(row)"
                            class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                            title="Hapus"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>

        <!-- Modal Tambah Pengguna -->
        <Teleport to="body">
            <div
                v-if="showCreateModal"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="showCreateModal = false"
            >
                <div class="relative w-full max-w-lg p-6 bg-white rounded-2xl shadow-2xl border border-slate-200 dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Tambah Pengguna Baru</h3>
                        <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitCreate" class="mt-4 space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap</label>
                            <input
                                v-model="createForm.name"
                                type="text"
                                required
                                placeholder="Contoh: Radif Alamsyah"
                                class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                            />
                            <p v-if="createForm.errors.name" class="mt-1 text-rose-500 font-medium">{{ createForm.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Email</label>
                            <input
                                v-model="createForm.email"
                                type="email"
                                required
                                placeholder="nama@perusahaan.com"
                                class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                            />
                            <p v-if="createForm.errors.email" class="mt-1 text-rose-500 font-medium">{{ createForm.errors.email }}</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Password</label>
                            <input
                                v-model="createForm.password"
                                type="password"
                                required
                                placeholder="Minimal 8 karakter"
                                class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                            />
                            <p v-if="createForm.errors.password" class="mt-1 text-rose-500 font-medium">{{ createForm.errors.password }}</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Role / Hak Akses</label>
                            <select
                                v-model="createForm.role"
                                class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                            >
                                <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.name }}</option>
                            </select>
                            <p v-if="createForm.errors.role" class="mt-1 text-rose-500 font-medium">{{ createForm.errors.role }}</p>
                        </div>

                        <div class="pt-4 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-800">
                            <button
                                type="button"
                                @click="showCreateModal = false"
                                class="px-4 py-2 text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl dark:bg-slate-800 dark:text-slate-300 font-semibold"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="createForm.processing"
                                class="px-4 py-2 text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl font-semibold disabled:opacity-50"
                            >
                                Simpan Pengguna
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Modal Edit Pengguna -->
        <Teleport to="body">
            <div
                v-if="showEditModal"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="showEditModal = false"
            >
                <div class="relative w-full max-w-lg p-6 bg-white rounded-2xl shadow-2xl border border-slate-200 dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Data Pengguna</h3>
                        <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitEdit" class="mt-4 space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap</label>
                            <input
                                v-model="editForm.name"
                                type="text"
                                required
                                class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                            />
                            <p v-if="editForm.errors.name" class="mt-1 text-rose-500 font-medium">{{ editForm.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Email</label>
                            <input
                                v-model="editForm.email"
                                type="email"
                                required
                                class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                            />
                            <p v-if="editForm.errors.email" class="mt-1 text-rose-500 font-medium">{{ editForm.errors.email }}</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Password Baru <span class="text-slate-400 font-normal">(Kosongkan jika tidak diubah)</span>
                            </label>
                            <input
                                v-model="editForm.password"
                                type="password"
                                placeholder="Minimal 8 karakter"
                                class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                            />
                            <p v-if="editForm.errors.password" class="mt-1 text-rose-500 font-medium">{{ editForm.errors.password }}</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Role / Hak Akses</label>
                            <select
                                v-model="editForm.role"
                                class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                            >
                                <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.name }}</option>
                            </select>
                            <p v-if="editForm.errors.role" class="mt-1 text-rose-500 font-medium">{{ editForm.errors.role }}</p>
                        </div>

                        <div class="pt-4 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-800">
                            <button
                                type="button"
                                @click="showEditModal = false"
                                class="px-4 py-2 text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl dark:bg-slate-800 dark:text-slate-300 font-semibold"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="px-4 py-2 text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl font-semibold disabled:opacity-50"
                            >
                                Perbarui Pengguna
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Confirmation Delete Modal -->
        <ConfirmationModal
            :show="showDeleteModal"
            title="Hapus Pengguna"
            :message="`Apakah Anda yakin ingin menghapus akun '${userToDelete?.name}'? Tindakan ini tidak dapat dibatalkan.`"
            confirmText="Hapus Pengguna"
            @confirm="deleteUser"
            @close="showDeleteModal = false"
        />

        <!-- Confirmation Bulk Delete Modal -->
        <ConfirmationModal
            :show="showBulkDeleteModal"
            title="Hapus Pengguna Massal"
            :message="`Apakah Anda yakin ingin menghapus ${selectedUsers.length} pengguna yang dipilih? Tindakan ini tidak dapat dibatalkan.`"
            confirmText="Hapus Semua Terpilih"
            @confirm="bulkDelete"
            @close="showBulkDeleteModal = false"
        />
    </AdminLayout>
</template>
