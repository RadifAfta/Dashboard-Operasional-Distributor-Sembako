<script setup>
import { ref, computed } from 'vue';
import { CheckCircle2, AlertCircle, AlertTriangle, Info, X } from 'lucide-vue-next';

const props = defineProps({
    type: {
        type: String,
        default: 'info', // 'success' | 'error' | 'warning' | 'info'
    },
    title: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: '',
    },
    dismissible: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['dismiss']);

const dismissed = ref(false);

const close = () => {
    dismissed.value = true;
    emit('dismiss');
};

const styles = computed(() => {
    switch (props.type) {
        case 'success':
            return {
                container: 'bg-emerald-50 border-emerald-200/80 text-emerald-950 dark:bg-emerald-950/30 dark:border-emerald-900/60 dark:text-emerald-200',
                icon: CheckCircle2,
                iconColor: 'text-emerald-600 dark:text-emerald-400',
                titleColor: 'text-emerald-900 dark:text-emerald-200',
            };
        case 'error':
            return {
                container: 'bg-rose-50 border-rose-200/80 text-rose-950 dark:bg-rose-950/30 dark:border-rose-900/60 dark:text-rose-200',
                icon: AlertCircle,
                iconColor: 'text-rose-600 dark:text-rose-400',
                titleColor: 'text-rose-900 dark:text-rose-200',
            };
        case 'warning':
            return {
                container: 'bg-amber-50 border-amber-200/80 text-amber-950 dark:bg-amber-950/30 dark:border-amber-900/60 dark:text-amber-200',
                icon: AlertTriangle,
                iconColor: 'text-amber-600 dark:text-amber-400',
                titleColor: 'text-amber-900 dark:text-amber-200',
            };
        case 'info':
        default:
            return {
                container: 'bg-indigo-50 border-indigo-200/80 text-indigo-950 dark:bg-indigo-950/30 dark:border-indigo-900/60 dark:text-indigo-200',
                icon: Info,
                iconColor: 'text-indigo-600 dark:text-indigo-400',
                titleColor: 'text-indigo-900 dark:text-indigo-200',
            };
    }
});
</script>

<template>
    <div
        v-if="!dismissed"
        :class="[
            'flex items-start gap-3.5 p-4 rounded-xl border transition-all duration-200',
            styles.container
        ]"
        role="alert"
    >
        <component :is="styles.icon" :class="['w-5 h-5 shrink-0 mt-0.5', styles.iconColor]" />

        <div class="flex-1 min-w-0 text-xs">
            <h4 v-if="title" :class="['font-bold tracking-tight text-sm', styles.titleColor]">
                {{ title }}
            </h4>
            <div :class="['leading-relaxed', title ? 'mt-1 opacity-90' : '']">
                <slot>
                    {{ description }}
                </slot>
            </div>
            <div v-if="$slots.actions" class="mt-3 flex items-center gap-2">
                <slot name="actions" />
            </div>
        </div>

        <button
            v-if="dismissible"
            type="button"
            @click="close"
            class="p-1 -mr-1 -mt-1 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 rounded-lg hover:bg-black/5 dark:hover:bg-white/5 transition"
            title="Tutup Peringatan"
        >
            <X class="w-4 h-4" />
        </button>
    </div>
</template>
