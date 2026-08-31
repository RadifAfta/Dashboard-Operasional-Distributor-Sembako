<script setup>
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { CheckCircle2, AlertCircle, AlertTriangle, Info, X } from 'lucide-vue-next';

const page = usePage();
const { toasts, remove, add } = useToast();

// Listen to Laravel Backend Flash Messages
watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;

        if (flash.success) {
            const isObj = typeof flash.success === 'object';
            add({
                title: isObj ? flash.success.title || 'Berhasil' : 'Berhasil',
                message: isObj ? flash.success.message : flash.success,
                type: 'success',
            });
        }
        if (flash.error) {
            const isObj = typeof flash.error === 'object';
            add({
                title: isObj ? flash.error.title || 'Gagal Melakukan Aksi' : 'Terjadi Kendala',
                message: isObj ? flash.error.message : flash.error,
                type: 'error',
                duration: 7000,
            });
        }
        if (flash.warning) {
            const isObj = typeof flash.warning === 'object';
            add({
                title: isObj ? flash.warning.title || 'Peringatan' : 'Peringatan Sistem',
                message: isObj ? flash.warning.message : flash.warning,
                type: 'warning',
                duration: 6000,
            });
        }
        if (flash.info) {
            const isObj = typeof flash.info === 'object';
            add({
                title: isObj ? flash.info.title || 'Pemberitahuan' : 'Informasi',
                message: isObj ? flash.info.message : flash.info,
                type: 'info',
            });
        }
    },
    { deep: true, immediate: true }
);

const getIcon = (type) => {
    switch (type) {
        case 'success':
            return CheckCircle2;
        case 'error':
            return AlertCircle;
        case 'warning':
            return AlertTriangle;
        case 'info':
        default:
            return Info;
    }
};

const getBorderColor = (type) => {
    switch (type) {
        case 'success':
            return 'border-l-emerald-500 text-emerald-600 dark:text-emerald-400';
        case 'error':
            return 'border-l-rose-500 text-rose-600 dark:text-rose-400';
        case 'warning':
            return 'border-l-amber-500 text-amber-600 dark:text-amber-400';
        case 'info':
        default:
            return 'border-l-indigo-500 text-indigo-600 dark:text-indigo-400';
    }
};

const getProgressBarColor = (type) => {
    switch (type) {
        case 'success':
            return 'bg-emerald-500';
        case 'error':
            return 'bg-rose-500';
        case 'warning':
            return 'bg-amber-500';
        case 'info':
        default:
            return 'bg-indigo-500';
    }
};
</script>

<template>
    <div
        class="fixed top-5 right-5 z-50 flex flex-col gap-3 w-full max-w-sm pointer-events-none"
        aria-live="assertive"
    >
        <TransitionGroup
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="pointer-events-auto relative overflow-hidden flex flex-col bg-white border border-slate-200 border-l-4 rounded-xl shadow-xl dark:bg-[#18181B] dark:border-zinc-800"
                :class="getBorderColor(toast.type)"
            >
                <div class="p-4 flex items-start gap-3">
                    <component
                        :is="getIcon(toast.type)"
                        class="w-5 h-5 shrink-0 mt-0.5"
                    />

                    <div class="flex-1 min-w-0 pr-1 text-xs">
                        <h5 class="font-bold text-slate-900 dark:text-white text-sm tracking-tight">
                            {{ toast.title }}
                        </h5>
                        <p class="mt-1 text-slate-600 dark:text-slate-300 leading-relaxed font-medium">
                            {{ toast.message }}
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="remove(toast.id)"
                        class="p-1 -mr-1 -mt-1 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        title="Tutup Notifikasi"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Subtle Countdown Progress Bar -->
                <div
                    v-if="toast.duration > 0"
                    class="h-0.5 w-full bg-slate-100 dark:bg-zinc-800 overflow-hidden"
                >
                    <div
                        class="h-full transition-all duration-100 ease-linear"
                        :class="getProgressBarColor(toast.type)"
                        :style="{ width: `${toast.progress}%` }"
                    ></div>
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>
