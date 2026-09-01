<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Mail, ArrowLeft, Send, CheckCircle2 } from 'lucide-vue-next';

defineProps({
    status: {
        type: String,
        default: null,
    },
});

const page = usePage();
const appSettings = computed(() => page.props.appSettings || { name: 'AdminHub', brand_color: 'indigo' });

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Pemulihan Kata Sandi" />

        <div class="w-full max-w-md p-6 sm:p-8 bg-white dark:bg-[#141417] border border-slate-200 dark:border-zinc-800 rounded-3xl shadow-xl space-y-5">
            <!-- Header -->
            <div class="space-y-1 text-center sm:text-left">
                <h1 class="text-xl font-black tracking-tight text-slate-900 dark:text-white">
                    Lupa Kata Sandi?
                </h1>
                <p class="text-xs text-slate-500 dark:text-zinc-400 leading-relaxed">
                    Masukkan email terdaftar Anda. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi akun Anda.
                </p>
            </div>

            <!-- Status Notice -->
            <div
                v-if="status"
                class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300 text-xs font-medium flex items-center gap-2"
            >
                <CheckCircle2 class="w-4 h-4 shrink-0" />
                <span>{{ status }}</span>
            </div>

            <form @submit.prevent="submit" class="space-y-4 text-xs">
                <div>
                    <label for="email" class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1.5 text-xs">
                        Alamat Email
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-zinc-500">
                            <Mail class="w-4 h-4" />
                        </div>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autofocus
                            placeholder="nama@perusahaan.com"
                            class="w-full pl-10 pr-3.5 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand/20 focus:border-brand dark:bg-[#0E0E11] dark:border-zinc-800 dark:text-zinc-100 transition shadow-2xs"
                            :class="{ 'border-rose-500 focus:border-rose-500 focus:ring-rose-200': form.errors.email }"
                        />
                    </div>
                    <p v-if="form.errors.email" class="mt-1.5 text-rose-500 font-medium text-[11px]">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-2.5 px-4 rounded-xl font-bold text-xs sm:text-sm text-white bg-brand hover:opacity-90 active:scale-[0.99] transition shadow-md shadow-brand/25 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                    >
                        <Send class="w-4 h-4" />
                        <span>{{ form.processing ? 'Mengirim Tautan...' : 'Kirim Tautan Pemulihan' }}</span>
                    </button>
                </div>

                <div class="pt-2 text-center">
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-zinc-200 transition"
                    >
                        <ArrowLeft class="w-3.5 h-3.5" />
                        <span>Kembali ke Halaman Masuk</span>
                    </Link>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
