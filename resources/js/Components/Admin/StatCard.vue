<script setup>
import { ArrowUpRight, ArrowDownRight } from 'lucide-vue-next';

defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    trend: {
        type: [String, Number],
        default: null,
    },
    trendUp: {
        type: Boolean,
        default: true,
    },
    description: {
        type: String,
        default: '',
    },
    iconBg: {
        type: String,
        default: 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400',
    },
});
</script>

<template>
    <div class="relative p-6 bg-white border border-slate-200/80 rounded-2xl shadow-sm dark:bg-slate-900 dark:border-slate-800/80 transition-all hover:shadow-md">
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                {{ title }}
            </p>
            <div :class="['flex items-center justify-center w-11 h-11 rounded-xl', iconBg]">
                <slot name="icon" />
            </div>
        </div>

        <div class="mt-4 flex items-baseline justify-between">
            <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ value }}
            </h3>

            <div v-if="trend !== null" class="flex items-center text-xs font-semibold" :class="trendUp ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                <ArrowUpRight v-if="trendUp" class="w-3.5 h-3.5 mr-0.5" />
                <ArrowDownRight v-else class="w-3.5 h-3.5 mr-0.5" />
                <span>{{ trend }}</span>
            </div>
        </div>

        <p v-if="description" class="mt-2 text-xs text-slate-500 dark:text-slate-400">
            {{ description }}
        </p>
    </div>
</template>
