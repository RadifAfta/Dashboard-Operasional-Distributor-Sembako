<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import {
    Mail,
    Lock,
    Eye,
    EyeOff,
    LogIn,
    CheckCircle2,
    ShieldCheck,
} from 'lucide-vue-next';

defineProps({
    canResetPassword: {
        type: Boolean,
        default: true,
    },
    status: {
        type: String,
        default: null,
    },
});

const page = usePage();
const appSettings = computed(() => page.props.appSettings || { name: 'AdminHub', brand_color: 'indigo', contact_email: 'admin@example.com' });

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk - Portal Administrasi" />

        <!-- Centered Vercel/GitHub Style Auth Card Shell -->
        <div class="w-full max-w-[420px] mx-auto space-y-6 animate-in fade-in zoom-in-95 duration-200">
            
            <!-- Brand & Console Title Header -->
            <div class="flex flex-col items-center text-center space-y-3">
                <ApplicationLogo size="xl" />
                <div class="space-y-1">
                    <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                        {{ appSettings.name }}
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-zinc-400">
                        Masuk ke Enterprise Console
                    </p>
                </div>
            </div>

            <!-- Floating Card Box -->
            <div class="bg-white dark:bg-[#141417] border border-slate-200/90 dark:border-zinc-800 rounded-3xl p-6 sm:p-8 shadow-xl dark:shadow-2xl space-y-5">
                
                <!-- Session Status Notice -->
                <div
                    v-if="status"
                    class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300 text-xs font-medium flex items-center gap-2"
                >
                    <CheckCircle2 class="w-4 h-4 shrink-0" />
                    <span>{{ status }}</span>
                </div>

                <!-- Login Form -->
                <form @submit.prevent="submit" class="space-y-4 text-xs">
                    <!-- Email Input -->
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
                                autocomplete="username"
                                placeholder="nama@perusahaan.com"
                                class="w-full pl-10 pr-3.5 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand/20 focus:border-brand dark:bg-[#0E0E11] dark:border-zinc-800 dark:text-zinc-100 transition shadow-2xs"
                                :class="{ 'border-rose-500 focus:border-rose-500 focus:ring-rose-200': form.errors.email }"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1.5 text-rose-500 font-medium text-[11px]">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password Input -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block font-semibold text-slate-700 dark:text-zinc-300 text-xs">
                                Kata Sandi
                            </label>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-[11px] font-semibold text-brand hover:underline"
                            >
                                Lupa kata sandi?
                            </Link>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-zinc-500">
                                <Lock class="w-4 h-4" />
                            </div>
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="Masukkan kata sandi"
                                class="w-full pl-10 pr-10 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand/20 focus:border-brand dark:bg-[#0E0E11] dark:border-zinc-800 dark:text-zinc-100 transition shadow-2xs"
                                :class="{ 'border-rose-500 focus:border-rose-500 focus:ring-rose-200': form.errors.password }"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-zinc-200 cursor-pointer"
                                :title="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                            >
                                <EyeOff v-if="showPassword" class="w-4 h-4" />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-rose-500 font-medium text-[11px]">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Remember Me Option -->
                    <div class="pt-1 flex items-center">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="w-4 h-4 text-brand border-slate-300 rounded focus:ring-brand"
                            />
                            <span class="text-xs text-slate-600 dark:text-zinc-400 font-medium">
                                Ingat sesi login di perangkat ini
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-2.5 px-4 rounded-xl font-bold text-xs sm:text-sm text-white bg-brand hover:opacity-90 active:scale-[0.99] transition shadow-md shadow-brand/25 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                        >
                            <LogIn class="w-4 h-4" />
                            <span>{{ form.processing ? 'Sedang Memproses...' : 'Masuk' }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sub-Card Support & Helpdesk Note -->
            <div class="text-center space-y-2">
                <p class="text-[11px] text-slate-400 dark:text-zinc-500">
                    Mengalami kendala akun? Hubungi Administrator:
                    <a
                        :href="`mailto:${appSettings.contact_email || 'admin@example.com'}`"
                        class="text-slate-600 dark:text-zinc-300 font-medium hover:text-brand dark:hover:text-brand hover:underline transition"
                    >
                        {{ appSettings.contact_email || 'admin@example.com' }}
                    </a>
                </p>
                <div class="flex items-center justify-center gap-1.5 text-[10px] font-mono text-slate-400 dark:text-zinc-600">
                    <ShieldCheck class="w-3.5 h-3.5 text-emerald-500" />
                    <span>Enkripsi Sesi TLS 1.3 &bull; Diawasi Audit Log</span>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
