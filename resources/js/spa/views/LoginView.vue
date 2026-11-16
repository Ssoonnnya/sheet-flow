<script setup lang="ts">
import { ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useAuth } from '@/spa/composables/useAuth';
import { ApiError } from '@/spa/api/types';

const { login } = useAuth();
const router = useRouter();
const route = useRoute();

const email = ref('');
const password = ref('');
const remember = ref(false);
const loading = ref(false);
const formError = ref<string | null>(null);
const fieldErrors = ref<Record<string, string[]>>({});

async function onSubmit(): Promise<void> {
    loading.value = true;
    formError.value = null;
    fieldErrors.value = {};

    try {
        await login(email.value, password.value, remember.value);
        const redirect =
            typeof route.query.redirect === 'string'
                ? route.query.redirect
                : '/sources';
        await router.push(redirect);
    } catch (error) {
        if (error instanceof ApiError) {
            fieldErrors.value = error.errors;
            formError.value = error.message;
        } else {
            formError.value = 'Login failed.';
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div
        class="flex min-h-screen items-center justify-center bg-slate-50 px-4"
    >
        <form
            class="w-full max-w-md space-y-4 rounded-xl border border-slate-200 bg-white p-8 shadow-sm"
            @submit.prevent="onSubmit"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Sheet Flow
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Sign in to manage sheet imports.
                </p>
            </div>

            <div v-if="formError" class="text-sm text-red-600">
                {{ formError }}
            </div>

            <label class="block space-y-1 text-sm">
                <span class="font-medium">Email</span>
                <input
                    v-model="email"
                    type="email"
                    required
                    autocomplete="username"
                    class="w-full rounded-md border border-slate-300 px-3 py-2"
                />
                <span
                    v-if="fieldErrors.email?.[0]"
                    class="text-red-600"
                >{{ fieldErrors.email[0] }}</span>
            </label>

            <label class="block space-y-1 text-sm">
                <span class="font-medium">Password</span>
                <input
                    v-model="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    class="w-full rounded-md border border-slate-300 px-3 py-2"
                />
                <span
                    v-if="fieldErrors.password?.[0]"
                    class="text-red-600"
                >{{ fieldErrors.password[0] }}</span>
            </label>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input v-model="remember" type="checkbox" />
                Remember me
            </label>

            <button
                type="submit"
                class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-60"
                :disabled="loading"
            >
                {{ loading ? 'Signing in…' : 'Sign in' }}
            </button>

            <p class="text-center text-sm text-slate-500">
                No account yet?
                <RouterLink
                    :to="{ name: 'register' }"
                    class="font-medium text-slate-900 hover:underline"
                >
                    Register
                </RouterLink>
            </p>
        </form>
    </div>
</template>
