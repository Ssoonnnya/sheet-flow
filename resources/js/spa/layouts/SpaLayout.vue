<script setup lang="ts">
import { RouterLink, useRouter } from 'vue-router';
import { useAuth } from '@/spa/composables/useAuth';

const { user, logout } = useAuth();
const router = useRouter();

async function onLogout(): Promise<void> {
    await logout();
    await router.push({ name: 'login' });
}
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <header
            class="border-b border-slate-200 bg-white"
        >
            <div
                class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4"
            >
                <div class="flex items-center gap-6">
                    <RouterLink
                        :to="{ name: 'sources' }"
                        class="text-lg font-semibold tracking-tight"
                    >
                        Sheet Flow
                    </RouterLink>
                    <nav class="flex gap-4 text-sm">
                        <RouterLink
                            :to="{ name: 'sources' }"
                            class="text-slate-600 hover:text-slate-900"
                            active-class="!text-slate-900 font-medium"
                        >
                            Sources
                        </RouterLink>
                        <RouterLink
                            :to="{ name: 'runs' }"
                            class="text-slate-600 hover:text-slate-900"
                            active-class="!text-slate-900 font-medium"
                        >
                            Runs
                        </RouterLink>
                    </nav>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <span class="text-slate-500">{{ user?.email }}</span>
                    <button
                        type="button"
                        class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-100"
                        @click="onLogout"
                    >
                        Log out
                    </button>
                </div>
            </div>
        </header>
        <main class="mx-auto max-w-6xl px-4 py-8">
            <slot />
        </main>
    </div>
</template>
