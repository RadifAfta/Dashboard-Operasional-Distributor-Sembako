import { ref } from 'vue';

const toasts = ref([]);
let toastCounter = 0;

export function useToast() {
    const add = ({ title = '', message = '', type = 'success', duration = 5000, action = null }) => {
        const id = ++toastCounter;
        const toast = {
            id,
            title,
            message,
            type, // 'success' | 'error' | 'warning' | 'info'
            duration,
            action,
            progress: 100,
            createdAt: Date.now(),
        };

        toasts.value.push(toast);

        if (duration > 0) {
            const interval = 50;
            const step = (interval / duration) * 100;

            const timer = setInterval(() => {
                const target = toasts.value.find((t) => t.id === id);
                if (target) {
                    target.progress = Math.max(0, target.progress - step);
                    if (target.progress <= 0) {
                        clearInterval(timer);
                        remove(id);
                    }
                } else {
                    clearInterval(timer);
                }
            }, interval);

            toast._timer = timer;
        }

        return id;
    };

    const remove = (id) => {
        const index = toasts.value.findIndex((t) => t.id === id);
        if (index > -1) {
            if (toasts.value[index]._timer) {
                clearInterval(toasts.value[index]._timer);
            }
            toasts.value.splice(index, 1);
        }
    };

    const clear = () => {
        toasts.value.forEach((t) => {
            if (t._timer) clearInterval(t._timer);
        });
        toasts.value = [];
    };

    return {
        toasts,
        add,
        remove,
        clear,
        success: (title, message = '', options = {}) => add({ title, message, type: 'success', ...options }),
        error: (title, message = '', options = {}) => add({ title, message, type: 'error', duration: 7000, ...options }),
        warning: (title, message = '', options = {}) => add({ title, message, type: 'warning', duration: 6000, ...options }),
        info: (title, message = '', options = {}) => add({ title, message, type: 'info', ...options }),
    };
}
