<script setup>
import { AlertTriangle, X } from 'lucide-vue-next';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Konfirmasi Aksi',
    },
    message: {
        type: String,
        default: 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
    },
    confirmText: {
        type: String,
        default: 'Hapus',
    },
    cancelText: {
        type: String,
        default: 'Batal',
    },
    danger: {
        type: Boolean,
        default: true,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['confirm', 'close']);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="$emit('close')"
            >
                <div class="relative w-full max-w-md p-6 bg-white rounded-2xl shadow-2xl border border-slate-200 dark:bg-slate-900 dark:border-slate-800 transform transition-all">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="absolute top-4 right-4 p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    >
                        <X class="w-4 h-4" />
                    </button>

                    <div class="flex items-center gap-4">
                        <div
                            :class="[
                                'flex items-center justify-center w-12 h-12 rounded-full shrink-0',
                                danger ? 'bg-rose-100 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400' : 'bg-amber-100 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400'
                            ]"
                        >
                            <AlertTriangle class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                {{ title }}
                            </h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                {{ message }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="$emit('close')"
                            :disabled="loading"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700/60 transition disabled:opacity-50"
                        >
                            {{ cancelText }}
                        </button>
                        <button
                            type="button"
                            @click="$emit('confirm')"
                            :disabled="loading"
                            :class="[
                                'inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white rounded-xl shadow-sm transition disabled:opacity-50',
                                danger ? 'bg-rose-600 hover:bg-rose-500 focus:ring-2 focus:ring-rose-500' : 'bg-indigo-600 hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500'
                            ]"
                        >
                            <span v-if="loading" class="w-4 h-4 mr-2 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            {{ confirmText }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
