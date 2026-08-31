<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Alert from '@/Components/Admin/Alert.vue';
import { useToast } from '@/Composables/useToast';
import { BRAND_PALETTES, applyBrandTheme } from '@/Utils/brandTheme';
import {
    Sliders,
    Save,
    Globe,
    ShieldCheck,
    Coins,
    Check,
    Bell,
    CheckCircle2,
    AlertCircle,
    AlertTriangle,
    Info,
    Sparkles,
    Palette,
} from 'lucide-vue-next';

const toast = useToast();

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
});

const activeTab = ref('general');

// Flatten settings into a form structure
const formItems = [];
Object.values(props.settings).forEach((group) => {
    group.forEach((item) => {
        formItems.push({
            key: item.key,
            value: item.value,
            group: item.group,
            type: item.type,
            description: item.description,
        });
    });
});

if (!formItems.some((s) => s.key === 'brand_color')) {
    formItems.push({
        key: 'brand_color',
        value: 'indigo',
        group: 'general',
        type: 'text',
        description: 'Warna tema aksen identitas perusahaan klien',
    });
}

const form = useForm({
    settings: formItems,
});

const submit = () => {
    form.post(route('admin.settings.update'), {
        preserveScroll: true,
    });
};

const getSetting = (key) => {
    return form.settings.find((s) => s.key === key);
};

const currentBrandColor = computed(() => {
    return getSetting('brand_color')?.value || 'indigo';
});

const customHexValue = computed(() => {
    const val = currentBrandColor.value;
    if (BRAND_PALETTES[val]) {
        return BRAND_PALETTES[val].hex;
    }
    if (val.startsWith('#')) return val;
    return `#${val}`;
});

const customHexClean = computed(() => {
    return customHexValue.value.replace('#', '');
});

const selectBrandColor = (key) => {
    let setting = getSetting('brand_color');
    if (!setting) {
        setting = {
            key: 'brand_color',
            value: key,
            group: 'general',
            type: 'text',
            description: 'Warna tema aksen identitas perusahaan klien',
        };
        form.settings.push(setting);
    } else {
        setting.value = key;
    }
    applyBrandTheme(key);
    toast.info('Pratinjau Warna Aksen', `Warna ${BRAND_PALETTES[key]?.name || key} aktif.`);
};

const onCustomColorInput = (hex) => {
    let setting = getSetting('brand_color');
    if (!setting) {
        setting = {
            key: 'brand_color',
            value: hex,
            group: 'general',
            type: 'text',
            description: 'Warna tema aksen identitas perusahaan klien',
        };
        form.settings.push(setting);
    } else {
        setting.value = hex;
    }
    applyBrandTheme(hex);
};

const onHexTextInput = (raw) => {
    const clean = raw.replace('#', '').trim();
    if (clean.length === 3 || clean.length === 6) {
        onCustomColorInput(`#${clean}`);
    }
};
</script>

<template>
    <AdminLayout title="Pengaturan Sistem">
        <Head title="Pengaturan Sistem" />

        <div class="space-y-6 max-w-4xl">
            <!-- Page Header -->
            <div>
                <h2 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Pengaturan Umum & Konfigurasi
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Sesuaikan identitas website, kebijakan pendaftaran, dan preferensi aplikasi.
                </p>
            </div>

            <!-- Settings Form Container -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Group Navigation Tabs -->
                <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2 overflow-x-auto">
                    <button
                        type="button"
                        @click="activeTab = 'general'"
                        :class="[
                            'inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl transition shrink-0',
                            activeTab === 'general'
                                ? 'bg-brand text-white shadow-sm'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800'
                        ]"
                    >
                        <Globe class="w-4 h-4" />
                        <span>Umum & Branding</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'security'"
                        :class="[
                            'inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl transition shrink-0',
                            activeTab === 'security'
                                ? 'bg-brand text-white shadow-sm'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800'
                        ]"
                    >
                        <ShieldCheck class="w-4 h-4" />
                        <span>Akses & Keamanan</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'localization'"
                        :class="[
                            'inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl transition shrink-0',
                            activeTab === 'localization'
                                ? 'bg-brand text-white shadow-sm'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800'
                        ]"
                    >
                        <Coins class="w-4 h-4" />
                        <span>Lokalisasi & Regional</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'notifications'"
                        :class="[
                            'inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl transition shrink-0',
                            activeTab === 'notifications'
                                ? 'bg-brand text-white shadow-sm'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800'
                        ]"
                    >
                        <Bell class="w-4 h-4" />
                        <span>Uji Notifikasi & Alert</span>
                    </button>
                </div>

                <!-- Tab 1: General Settings -->
                <div v-show="activeTab === 'general'" class="p-6 bg-white border border-slate-200/80 rounded-2xl shadow-sm dark:bg-[#141417] dark:border-zinc-800/80 space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">
                            Nama Aplikasi / Website
                        </label>
                        <p class="text-[11px] text-slate-400 mb-2">Ditampilkan di header sidebar, title bar, dan email template.</p>
                        <input
                            v-if="getSetting('app_name')"
                            v-model="getSetting('app_name').value"
                            type="text"
                            class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand/20 focus:border-brand dark:bg-zinc-800 dark:border-zinc-700 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">
                            Deskripsi Singkat Aplikasi
                        </label>
                        <p class="text-[11px] text-slate-400 mb-2">Penjelasan profil singkat atau tagline aplikasi.</p>
                        <textarea
                            v-if="getSetting('app_description')"
                            v-model="getSetting('app_description').value"
                            rows="3"
                            class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand/20 focus:border-brand dark:bg-zinc-800 dark:border-zinc-700 dark:text-white"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">
                            Email Kontak Sistem
                        </label>
                        <p class="text-[11px] text-slate-400 mb-2">Email pengirim notifikasi dan bantuan pengguna.</p>
                        <input
                            v-if="getSetting('contact_email')"
                            v-model="getSetting('contact_email').value"
                            type="email"
                            class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand/20 focus:border-brand dark:bg-zinc-800 dark:border-zinc-700 dark:text-white"
                        />
                    </div>

                    <!-- Brand Accent Color Palette Selector -->
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2 mb-1">
                            <Palette class="w-4 h-4 text-brand" />
                            <label class="block text-xs font-bold text-slate-800 dark:text-slate-200">
                                Warna Identitas Perusahaan (Brand Accent Color)
                            </label>
                        </div>
                        <p class="text-[11px] text-slate-400 mb-3">
                            Pilih warna identitas utama yang selaras dengan logo klien. Tombol utama, indikator aktif, dan aksen navigasi akan langsung berubah otomatis secara serasi tanpa merusak fondasi netral dashboard.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                            <button
                                v-for="(palette, key) in BRAND_PALETTES"
                                :key="key"
                                type="button"
                                @click="selectBrandColor(key)"
                                :class="[
                                    'flex items-center gap-3 p-2.5 rounded-xl border text-left transition-all',
                                    currentBrandColor === key
                                        ? 'border-slate-900 bg-slate-100/70 dark:border-white dark:bg-zinc-800 shadow-2xs ring-1 ring-slate-900 dark:ring-white'
                                        : 'border-slate-200 hover:border-slate-300 dark:border-zinc-800 dark:hover:border-zinc-700 bg-white dark:bg-zinc-900'
                                ]"
                            >
                                <span
                                    class="w-7 h-7 rounded-lg shrink-0 shadow-xs flex items-center justify-center text-white text-[10px]"
                                    :style="{ backgroundColor: palette.hex }"
                                >
                                    <Check v-if="currentBrandColor === key" class="w-4 h-4" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                        {{ palette.name }}
                                    </p>
                                    <p class="text-[10px] text-slate-400 truncate">
                                        {{ palette.description }}
                                    </p>
                                </div>
                            </button>
                        </div>

                        <!-- Custom HEX Color Picker & Input Box -->
                        <div class="mt-4 p-3.5 bg-slate-50 dark:bg-zinc-900/60 rounded-xl border border-slate-200 dark:border-zinc-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                    Bebas Masukkan Kode Warna Klien (HEX Custom)
                                </p>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    Ketik kode warna heksadesimal resmi perusahaan klien atau gunakan pemilih warna visual.
                                </p>
                            </div>
                            <div class="flex items-center gap-2.5 shrink-0">
                                <div class="relative flex items-center">
                                    <input
                                        type="color"
                                        :value="customHexValue"
                                        @input="onCustomColorInput($event.target.value)"
                                        class="w-8 h-8 rounded-lg border border-slate-300 dark:border-zinc-700 cursor-pointer bg-transparent p-0 overflow-hidden"
                                        title="Buka pemilih warna (Color Picker)"
                                    />
                                </div>
                                <div class="relative flex items-center">
                                    <span class="absolute left-2.5 text-xs font-mono text-slate-400 font-bold">#</span>
                                    <input
                                        type="text"
                                        :value="customHexClean"
                                        @input="onHexTextInput($event.target.value)"
                                        maxlength="6"
                                        placeholder="4F46E5"
                                        class="w-28 pl-6 pr-2.5 py-1.5 text-xs font-mono font-bold uppercase bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-lg focus:ring-2 focus:ring-brand/20 focus:border-brand"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Security Settings -->
                <div v-show="activeTab === 'security'" class="p-6 bg-white border border-slate-200/80 rounded-2xl shadow-sm dark:bg-[#141417] dark:border-zinc-800/80 space-y-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                Izinkan Pendaftaran Publik (User Registration)
                            </p>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                Jika dinonaktifkan, tombol registrasi di halaman login akan disembunyikan.
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                v-if="getSetting('enable_registration')"
                                type="checkbox"
                                :checked="getSetting('enable_registration').value === '1'"
                                @change="getSetting('enable_registration').value = $event.target.checked ? '1' : '0'"
                                class="sr-only peer"
                            />
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-brand"></div>
                        </label>
                    </div>
                </div>

                <!-- Tab 3: Localization Settings -->
                <div v-show="activeTab === 'localization'" class="p-6 bg-white border border-slate-200/80 rounded-2xl shadow-sm dark:bg-[#141417] dark:border-zinc-800/80 space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">
                            Zona Waktu Default
                        </label>
                        <select
                            v-if="getSetting('timezone')"
                            v-model="getSetting('timezone').value"
                            class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand/20 focus:border-brand dark:bg-zinc-800 dark:border-zinc-700 dark:text-white"
                        >
                            <option value="Asia/Jakarta">Asia/Jakarta (WIB)</option>
                            <option value="Asia/Makassar">Asia/Makassar (WITA)</option>
                            <option value="Asia/Jayapura">Asia/Jayapura (WIT)</option>
                            <option value="UTC">UTC Universal</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">
                            Simbol Mata Uang
                        </label>
                        <input
                            v-if="getSetting('currency_symbol')"
                            v-model="getSetting('currency_symbol').value"
                            type="text"
                            class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand/20 focus:border-brand dark:bg-zinc-800 dark:border-zinc-700 dark:text-white"
                        />
                    </div>
                </div>

                <!-- Tab 4: Interactive Notification & Error Handling Playground -->
                <div v-show="activeTab === 'notifications'" class="p-6 bg-white border border-slate-200/80 rounded-2xl shadow-sm dark:bg-[#141417] dark:border-zinc-800/80 space-y-6">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                            Pusat Pengujian Notifikasi & Penanganan Error
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Uji coba format notifikasi mengambang (Toast) dan pesan kesalahan bahasa manusia yang mudah dipahami oleh pengguna.
                        </p>
                    </div>

                    <!-- Trigger Buttons Grid -->
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            1. Trigger Notifikasi Mengambang (Floating Toast)
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <button
                                type="button"
                                @click="toast.success('Penyimpanan Berhasil', 'Data pengguna baru telah tersimpan ke sistem.')"
                                class="flex items-center justify-center gap-2 p-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900 transition font-bold text-xs"
                            >
                                <CheckCircle2 class="w-4 h-4" />
                                <span>Uji Sukses (CRUD)</span>
                            </button>

                            <button
                                type="button"
                                @click="toast.error('Gagal Menyimpan Data', 'Format email belum sesuai standar. Mohon periksa isian bertanda merah.')"
                                class="flex items-center justify-center gap-2 p-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900 transition font-bold text-xs"
                            >
                                <AlertCircle class="w-4 h-4" />
                                <span>Uji Error Ramah</span>
                            </button>

                            <button
                                type="button"
                                @click="toast.warning('Peringatan Kuota', 'Kapasitas penyimpanan sistem hampir penuh (sisa 10%). Segera lakukan pencadangan.')"
                                class="flex items-center justify-center gap-2 p-3 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900 transition font-bold text-xs"
                            >
                                <AlertTriangle class="w-4 h-4" />
                                <span>Uji Peringatan</span>
                            </button>

                            <button
                                type="button"
                                @click="toast.info('Pembaruan Sistem', 'Pemeliharaan server berkala dijadwalkan malam ini pukul 23:00 WIB.')"
                                class="flex items-center justify-center gap-2 p-3 rounded-xl bg-brand/10 text-brand border border-brand/20 hover:bg-brand/20 dark:bg-brand/20 dark:text-brand dark:border-brand/40 transition font-bold text-xs"
                            >
                                <Info class="w-4 h-4" />
                                <span>Uji Info Sistem</span>
                            </button>
                        </div>
                    </div>

                    <!-- Inline Alert Banners Preview -->
                    <div class="space-y-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            2. Tampilan Banner Peringatan Inline (Alert Callouts)
                        </label>

                        <div class="space-y-3">
                            <Alert
                                type="success"
                                title="Aksi Berhasil Diselesaikan"
                                description="Seluruh konfigurasi hak akses pengguna telah berhasil diterapkan dan aktif."
                                :dismissible="true"
                            />

                            <Alert
                                type="error"
                                title="Isian Belum Lengkap"
                                description="Terdapat kolom formulir yang belum sesuai. Mohon periksa kembali kolom bertanda merah di atas."
                                :dismissible="true"
                            />

                            <Alert
                                type="warning"
                                title="Perhatian Hak Akses"
                                description="Mengubah pengaturan ini akan berdampak pada hak akses seluruh akun staf."
                                :dismissible="true"
                            />

                            <Alert
                                type="info"
                                title="Petunjuk Tambahan"
                                description="Anda dapat memanggil notifikasi ini di file Vue manapun dengan perintah useToast()."
                                :dismissible="true"
                            />
                        </div>
                    </div>
                </div>

                <!-- Submit Button Bar -->
                <div v-show="activeTab !== 'notifications'" class="flex items-center justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-bold text-white bg-brand hover:opacity-90 rounded-xl shadow-md shadow-brand/20 transition disabled:opacity-50"
                    >
                        <Save class="w-4 h-4" />
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
