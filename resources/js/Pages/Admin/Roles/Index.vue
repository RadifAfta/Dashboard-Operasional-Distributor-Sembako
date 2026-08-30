<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/Admin/Badge.vue';
import Alert from '@/Components/Admin/Alert.vue';
import ConfirmationModal from '@/Components/Admin/ConfirmationModal.vue';
import { useToast } from '@/Composables/useToast';
import {
    Shield,
    Plus,
    Edit2,
    Trash2,
    Users,
    Key,
    X,
    Check,
} from 'lucide-vue-next';

const props = defineProps({
    roles: {
        type: Array,
        required: true,
    },
    permissionsGrouped: {
        type: Object,
        required: true,
    },
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const roleToDelete = ref(null);
const toast = useToast();

const createForm = useForm({
    name: '',
    permissions: [],
});

const editForm = useForm({
    id: null,
    name: '',
    permissions: [],
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    showCreateModal.value = true;
};

const submitCreate = () => {
    createForm.post(route('admin.roles.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
        onError: () => {
            toast.error('Gagal Menyimpan Role', 'Mohon lengkapi nama role dan pilih izin yang sesuai.');
        },
    });
};

const openEditModal = (role) => {
    editForm.clearErrors();
    editForm.id = role.id;
    editForm.name = role.name;
    editForm.permissions = role.permissions.map((p) => p.name);
    showEditModal.value = true;
};

const submitEdit = () => {
    editForm.put(route('admin.roles.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
        },
        onError: () => {
            toast.error('Perubahan Belum Disimpan', 'Terdapat kendala pada data nama role atau daftar izin.');
        },
    });
};

const confirmDelete = (role) => {
    roleToDelete.value = role;
    showDeleteModal.value = true;
};

const deleteRole = () => {
    if (!roleToDelete.value) return;

    router.delete(route('admin.roles.destroy', roleToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            roleToDelete.value = null;
        },
    });
};

// Check if all permissions in group are selected
const isGroupAllSelected = (form, groupPermissions) => {
    return groupPermissions.every((p) => form.permissions.includes(p.name));
};

const toggleGroup = (form, groupPermissions) => {
    if (isGroupAllSelected(form, groupPermissions)) {
        // Uncheck all
        form.permissions = form.permissions.filter((p) => !groupPermissions.some((gp) => gp.name === p));
    } else {
        // Check all
        const toAdd = groupPermissions.map((p) => p.name).filter((name) => !form.permissions.includes(name));
        form.permissions.push(...toAdd);
    }
};
</script>

<template>
    <AdminLayout title="Role & Hak Akses">
        <Head title="Role & Hak Akses" />

        <div class="space-y-6">
            <!-- Header Title -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Role & Permission Matrix
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Atur tingkatan hak akses dan batasan wewenang untuk setiap role dalam sistem.
                    </p>
                </div>
                <button
                    type="button"
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-sm transition shadow-indigo-600/20 self-start sm:self-auto"
                >
                    <Plus class="w-4 h-4" />
                    <span>Buat Role Baru</span>
                </button>
            </div>

            <!-- Role Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="role in roles"
                    :key="role.id"
                    class="flex flex-col justify-between p-6 bg-white border border-slate-200/80 rounded-2xl shadow-sm dark:bg-slate-900 dark:border-slate-800/80 hover:shadow-md transition"
                >
                    <div>
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                                    <Shield class="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                        {{ role.name }}
                                    </h3>
                                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mt-0.5">
                                        <Users class="w-3.5 h-3.5" />
                                        <span>{{ role.users_count }} Pengguna</span>
                                    </div>
                                </div>
                            </div>

                            <Badge :variant="role.name === 'Super Admin' ? 'danger' : 'primary'" size="sm">
                                {{ role.permissions.length }} Izin
                            </Badge>
                        </div>

                        <!-- Sample Permission Pills -->
                        <div class="mt-5">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">
                                Hak Akses Terpasang:
                            </p>
                            <div class="flex flex-wrap gap-1.5 max-h-36 overflow-y-auto pr-1">
                                <span
                                    v-for="p in role.permissions.slice(0, 6)"
                                    :key="p.id"
                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                                >
                                    {{ p.name }}
                                </span>
                                <span
                                    v-if="role.permissions.length > 6"
                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400"
                                >
                                    +{{ role.permissions.length - 6 }} lainnya
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Bottom Actions -->
                    <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400">
                            {{ role.name === 'Super Admin' ? 'System Protected' : 'Custom Role' }}
                        </span>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="openEditModal(role)"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:text-indigo-600 bg-slate-50 hover:bg-slate-100 rounded-lg dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition"
                            >
                                <Edit2 class="w-3.5 h-3.5" />
                                <span>Edit Matrix</span>
                            </button>
                            <button
                                v-if="!['Super Admin', 'Admin', 'User'].includes(role.name)"
                                type="button"
                                @click="confirmDelete(role)"
                                class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/30 transition"
                                title="Hapus Role"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah Role -->
        <Teleport to="body">
            <div
                v-if="showCreateModal"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="showCreateModal = false"
            >
                <div class="relative w-full max-w-2xl p-6 bg-white rounded-2xl shadow-2xl border border-slate-200 dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Buat Role Baru</h3>
                        <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitCreate" class="mt-4 space-y-4 text-xs">
                        <Alert
                            v-if="createForm.hasErrors"
                            type="error"
                            title="Isian Belum Lengkap"
                            description="Mohon lengkapi nama role dan pastikan tidak duplikat dengan role yang ada."
                            class="mb-2"
                        />
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Role</label>
                            <input
                                v-model="createForm.name"
                                type="text"
                                required
                                placeholder="Contoh: Editor, Staff Keuangan"
                                class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:bg-slate-800 dark:border-slate-700 dark:text-white"
                            />
                            <p v-if="createForm.errors.name" class="mt-1 text-rose-500 font-medium">{{ createForm.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-2">Pilih Hak Akses (Permissions)</label>
                            <div class="space-y-4 max-h-72 overflow-y-auto pr-2">
                                <div
                                    v-for="(perms, groupName) in permissionsGrouped"
                                    :key="groupName"
                                    class="p-3 bg-slate-50 border border-slate-200/60 rounded-xl dark:bg-slate-800/40 dark:border-slate-700/60"
                                >
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-bold uppercase tracking-wider text-[11px] text-slate-700 dark:text-slate-300">
                                            Modul {{ groupName }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="toggleGroup(createForm, perms)"
                                            class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
                                        >
                                            {{ isGroupAllSelected(createForm, perms) ? 'Batalkan Semua' : 'Pilih Semua' }}
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label
                                            v-for="p in perms"
                                            :key="p.id"
                                            class="flex items-center gap-2 cursor-pointer select-none text-slate-600 dark:text-slate-300"
                                        >
                                            <input
                                                type="checkbox"
                                                :value="p.name"
                                                v-model="createForm.permissions"
                                                class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500"
                                            />
                                            <span>{{ p.name }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
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
                                Simpan Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Modal Edit Role -->
        <Teleport to="body">
            <div
                v-if="showEditModal"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="showEditModal = false"
            >
                <div class="relative w-full max-w-2xl p-6 bg-white rounded-2xl shadow-2xl border border-slate-200 dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Hak Akses Role: {{ editForm.name }}</h3>
                        <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitEdit" class="mt-4 space-y-4 text-xs">
                        <Alert
                            v-if="editForm.hasErrors"
                            type="error"
                            title="Periksa Isian Role"
                            description="Terdapat kendala pada data role. Mohon periksa kembali kolom bertanda merah."
                            class="mb-2"
                        />
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Role</label>
                            <input
                                v-model="editForm.name"
                                type="text"
                                required
                                :disabled="editForm.name === 'Super Admin'"
                                class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:bg-slate-800 dark:border-slate-700 dark:text-white disabled:opacity-60 disabled:bg-slate-100"
                            />
                            <p v-if="editForm.errors.name" class="mt-1 text-rose-500 font-medium">{{ editForm.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-2">Pilih Hak Akses (Permissions)</label>
                            <div class="space-y-4 max-h-72 overflow-y-auto pr-2">
                                <div
                                    v-for="(perms, groupName) in permissionsGrouped"
                                    :key="groupName"
                                    class="p-3 bg-slate-50 border border-slate-200/60 rounded-xl dark:bg-slate-800/40 dark:border-slate-700/60"
                                >
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-bold uppercase tracking-wider text-[11px] text-slate-700 dark:text-slate-300">
                                            Modul {{ groupName }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="toggleGroup(editForm, perms)"
                                            class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
                                        >
                                            {{ isGroupAllSelected(editForm, perms) ? 'Batalkan Semua' : 'Pilih Semua' }}
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label
                                            v-for="p in perms"
                                            :key="p.id"
                                            class="flex items-center gap-2 cursor-pointer select-none text-slate-600 dark:text-slate-300"
                                        >
                                            <input
                                                type="checkbox"
                                                :value="p.name"
                                                v-model="editForm.permissions"
                                                class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500"
                                            />
                                            <span>{{ p.name }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
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
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Confirmation Delete Modal -->
        <ConfirmationModal
            :show="showDeleteModal"
            title="Hapus Role"
            :message="`Apakah Anda yakin ingin menghapus role '${roleToDelete?.name}'? Role ini tidak dapat dihapus jika masih ada pengguna yang ditugaskan.`"
            confirmText="Hapus Role"
            @confirm="deleteRole"
            @close="showDeleteModal = false"
        />
    </AdminLayout>
</template>
