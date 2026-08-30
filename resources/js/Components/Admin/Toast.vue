<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, AlertCircle, AlertTriangle, Info, X } from 'lucide-vue-next';

const page = usePage();
const visible = ref(false);
const message = ref('');
const type = ref('success');
let timer = null;

const showToast = (msg, toastType) => {
    if (!msg) return;
    message.value = msg;
    type.value = toastType;
    visible.value = true;

    if (timer) clearTimeout(timer);
    timer = setTimeout(() => {
        visible.value = false;
    }, 4000);
};

const close = () => {
    visible.value = false;
    if (timer) clearTimeout(timer);
};

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) showToast(flash.success, 'success');
        else if (flash?.error) showToast(flash.error, 'error');
        else if (flash?.warning) showToast(flash.warning, 'warning');
        else if (flash?.info) showToast(flash.info, 'info');
    },
    { deep: true, immediate: true }
);
</script>

<template>
    <Transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="visible"
            class="fixed top-5 right-5 z-50 flex items-center w-full max-w-sm p-4 space-x-3 text-slate-800 bg-white border rounded-xl shadow-xl dark:bg-slate-900 dark:text-slate-100 dark:border-slate-800"
            :class="{
                'border-l-4 border-l-emerald-500': type === 'success',
                'border-l-4 border-l-rose-500': type === 'error',
                'border-l-4 border-l-amber-500': type === 'warning',
                'border-l-4 border-l-sky-500': type === 'info',
            }"
            role="alert"
        >
            <div class="shrink-0">
                <CheckCircle2 v-if="type === 'success'" class="w-5 h-5 text-emerald-500" />
                <AlertCircle v-else-if="type === 'error'" class="w-5 h-5 text-rose-500" />
                <AlertTriangle v-else-if="type === 'warning'" class="w-5 h-5 text-amber-500" />
                <Info v-else class="w-5 h-5 text-sky-500" />
            </div>
            <div class="flex-1 text-sm font-medium leading-snug">
                {{ message }}
            </div>
            <button
                type="button"
                @click="close"
                class="shrink-0 inline-flex p-1 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
            >
                <X class="w-4 h-4" />
            </button>
        </div>
    </Transition>
</template>
