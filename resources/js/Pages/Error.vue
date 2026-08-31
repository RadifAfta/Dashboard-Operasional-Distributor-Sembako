<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ShieldAlert, FileQuestion, ServerCrash, Clock, ArrowLeft, RefreshCw, Home } from 'lucide-vue-next';

const props = defineProps({
    status: {
        type: Number,
        default: 404,
    },
});

const details = computed(() => {
    switch (props.status) {
        case 403:
            return {
                title: 'Akses Dibatasi',
                subtitle: '403 Forbidden',
                description: 'Akun Anda saat ini tidak memiliki izin atau wewenang yang memadai untuk mengakses halaman ini. Jika Anda memerlukan akses, silakan hubungi Administrator sistem.',
                icon: ShieldAlert,
                color: 'text-amber-500 bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-900',
            };
        case 419:
            return {
                title: 'Sesi Halaman Kedaluwarsa',
                subtitle: '419 Page Expired',
                description: 'Demi keamanan data Anda, sesi formulir telah berakhir karena tidak ada aktivitas dalam beberapa waktu. Silakan muat ulang halaman atau login kembali.',
                icon: Clock,
                color: 'text-brand bg-brand/10 dark:bg-brand/20 border-brand/20 dark:border-brand/40',
            };
        case 500:
            return {
                title: 'Kendala Layanan Server',
                subtitle: '500 Internal Server Error',
                description: 'Terjadi gangguan teknis pada server kami saat memproses permintaan Anda. Tim teknis telah menerima log insiden ini dan sedang menanganinya.',
                icon: ServerCrash,
                color: 'text-rose-500 bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-900',
            };
        case 503:
            return {
                title: 'Sistem Dalam Pemeliharaan',
                subtitle: '503 Maintenance Mode',
                description: 'Platform sedang menjalani pemeliharaan rutin untuk peningkatan stabilitas dan performa. Kami akan segera kembali online sesaat lagi.',
                icon: ServerCrash,
                color: 'text-sky-500 bg-sky-50 dark:bg-sky-950/40 border-sky-200 dark:border-sky-900',
            };
        case 404:
        default:
            return {
                title: 'Halaman Tidak Ditemukan',
                subtitle: '404 Not Found',
                description: 'Halaman yang Anda tuju tidak dapat ditemukan. Alamat tautan mungkin salah ketik, telah dihapus, atau dipindahkan ke lokasi lain.',
                icon: FileQuestion,
                color: 'text-slate-600 bg-slate-100 dark:bg-zinc-800 border-slate-200 dark:border-zinc-700',
            };
    }
});

const reload = () => {
    window.location.reload();
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-[#09090B] text-slate-900 dark:text-slate-100 font-sans flex items-center justify-center p-4 sm:p-6 selection:bg-slate-900 selection:text-white">
        <Head :title="details.title" />

        <div class="w-full max-w-lg p-8 bg-white border border-slate-200 rounded-3xl shadow-xl dark:bg-[#18181B] dark:border-zinc-800 text-center space-y-6">
            <!-- Centered Status Icon -->
            <div
                :class="[
                    'inline-flex items-center justify-center w-16 h-16 rounded-2xl border mx-auto',
                    details.color
                ]"
            >
                <component :is="details.icon" class="w-8 h-8" />
            </div>

            <div class="space-y-2">
                <span class="px-2.5 py-1 text-xs font-mono font-bold uppercase rounded-full bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400">
                    {{ details.subtitle }}
                </span>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                    {{ details.title }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto leading-relaxed">
                    {{ details.description }}
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
                <button
                    type="button"
                    @click="reload"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700 transition"
                >
                    <RefreshCw class="w-3.5 h-3.5" />
                    <span>Muat Ulang Halaman</span>
                </button>

                <Link
                    :href="route('admin.dashboard')"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-bold text-white bg-brand hover:opacity-90 rounded-xl shadow-sm transition"
                >
                    <Home class="w-3.5 h-3.5" />
                    <span>Kembali ke Dashboard</span>
                </Link>
            </div>
        </div>
    </div>
</template>
