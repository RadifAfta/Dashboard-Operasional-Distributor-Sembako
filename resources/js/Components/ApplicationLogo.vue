<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    size: {
        type: String,
        default: 'md', // 'sm' | 'md' | 'lg' | 'xl'
    },
    showText: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const appSettings = computed(() => page.props.appSettings || { name: 'AdminHub', brand_color: 'indigo', logo_url: null });

const sizeConfig = computed(() => {
    switch (props.size) {
        case 'sm':
            return {
                box: 'w-7 h-7',
                rounded: 'rounded-lg',
                text: 'text-xs',
            };
        case 'lg':
            return {
                box: 'w-11 h-11',
                rounded: 'rounded-2xl',
                text: 'text-lg',
            };
        case 'xl':
            return {
                box: 'w-14 h-14',
                rounded: 'rounded-2xl',
                text: 'text-xl',
            };
        case 'md':
        default:
            return {
                box: 'w-8 h-8',
                rounded: 'rounded-xl',
                text: 'text-sm',
            };
    }
});
</script>

<template>
    <div class="flex items-center gap-2.5">
        <!-- Image Logo if Uploaded -->
        <img
            v-if="appSettings.logo_url"
            :src="appSettings.logo_url"
            :alt="appSettings.name"
            :class="[
                sizeConfig.box,
                sizeConfig.rounded,
                'object-contain shrink-0'
            ]"
        />

        <!-- Smart Fallback: Dynamic Initial Lettermark Badge -->
        <div
            v-else
            :class="[
                sizeConfig.box,
                sizeConfig.rounded,
                sizeConfig.text,
                'bg-brand text-white font-black tracking-wider flex items-center justify-center shrink-0 shadow-sm shadow-brand/30 transition-transform select-none'
            ]"
        >
            {{ (appSettings.name || 'A').charAt(0) }}
        </div>

        <!-- Optional Text Beside Logo -->
        <div v-if="showText" class="flex flex-col truncate min-w-0">
            <span class="font-bold text-xs tracking-tight text-slate-900 dark:text-white truncate">
                {{ appSettings.name }}
            </span>
            <span class="text-[10px] font-mono text-slate-400 dark:text-zinc-500 truncate">
                Enterprise Console
            </span>
        </div>
    </div>
</template>
