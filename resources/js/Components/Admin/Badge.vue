<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
    },
    size: {
        type: String,
        default: 'md',
    },
});

const variantClasses = computed(() => {
    switch (props.variant) {
        case 'success':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800';
        case 'danger':
            return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800';
        case 'warning':
            return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800';
        case 'info':
            return 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-400 dark:border-sky-800';
        case 'neutral':
            return 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
        case 'primary':
        default:
            return 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-800';
    }
});

const sizeClasses = computed(() => {
    return props.size === 'sm'
        ? 'px-2 py-0.5 text-xs font-medium'
        : 'px-2.5 py-1 text-xs font-semibold';
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 rounded-full border transition-colors',
            variantClasses,
            sizeClasses
        ]"
    >
        <slot />
    </span>
</template>
