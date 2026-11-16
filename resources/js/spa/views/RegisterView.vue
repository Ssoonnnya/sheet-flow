<script setup lang="ts">
import { ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useAuth } from '@/spa/composables/useAuth';
import { ApiError } from '@/spa/api/types';

const { register } = useAuth();
const router = useRouter();

const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const loading = ref(false);
const formError = ref<string | null>(null);
const fieldErrors = ref<Record<string, string[]>>({});

async function onSubmit(): Promise<void> {
    loading.value = true;
    formError.value = null;
    fieldErrors.value = {};

    try {
        await register(
            name.value,
            email.value,
            password.value,
            passwordConfirmation.value,
        );
        await router.push({ name: 'sources' });
    } catch (error) {
        if (error instanceof ApiError) {
            fieldErrors.value = error.errors;
            formError.value = error.message;
        } else {
            formError.value = 'Registration failed.';
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
                    Create account
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Register to manage sheet imports.
                </p>
            </div>

            <div v-if="formError" class="text-sm text-red-600">
                {{ formError }}
            </div>

            <label class="block space-y-1 text-sm">
                <span class="font-medium">Name</span>
                <input
                    v-model="name"
                    type="text"
                    required
                    autocomplete="name"
                    class="w-full rounded-md border border-slate-300 px-3 py-2"
                />
                <span
                    v-if="fieldErrors.name?.[0]"
                    class="text-red-600"
                >{{ fieldErrors.name[0] }}</span>
            </label>

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
                    autocomplete="new-password"
                    class="w-full rounded-md border border-slate-300 px-3 py-2"
                />
                <span
                    v-if="fieldErrors.password?.[0]"
                    class="text-red-600"
                >{{ fieldErrors.password[0] }}</span>
            </label>

            <label class="block space-y-1 text-sm">
                <span class="font-medium">Confirm password</span>
                <input
                    v-model="passwordConfirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="w-full rounded-md border border-slate-300 px-3 py-2"
                />
            </label>

            <button
                type="submit"
                class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-60"
                :disabled="loading"
            >
                {{ loading ? 'Creating…' : 'Create account' }}
            </button>

            <p class="text-center text-sm text-slate-500">
                Already have an account?
                <RouterLink
                    :to="{ name: 'login' }"
                    class="font-medium text-slate-900 hover:underline"
                >
                    Sign in
                </RouterLink>
            </p>
        </form>
    </div>
</template>
