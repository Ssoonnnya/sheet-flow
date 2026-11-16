<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { api } from '@/spa/api/client';
import { ApiError, type ImportRun, type Paginated } from '@/spa/api/types';
import SpaLayout from '@/spa/layouts/SpaLayout.vue';

const runs = ref<ImportRun[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const syncing = ref(false);
let pollTimer: ReturnType<typeof setInterval> | null = null;

async function loadRuns(): Promise<void> {
    try {
        const payload = await api.get<ImportRun[] | Paginated<ImportRun>>(
            '/import-runs',
        );

        runs.value = Array.isArray(payload)
            ? payload
            : ((payload as Paginated<ImportRun>).data ?? []);
        error.value = null;
    } catch (err) {
        error.value =
            err instanceof ApiError ? err.message : 'Failed to load runs.';
    } finally {
        loading.value = false;
    }
}

async function runSync(): Promise<void> {
    syncing.value = true;

    try {
        const run = await api.post<ImportRun>('/sync');
        runs.value = [run, ...runs.value];
    } catch (err) {
        error.value =
            err instanceof ApiError ? err.message : 'Failed to queue sync.';
    } finally {
        syncing.value = false;
    }
}

onMounted(() => {
    void loadRuns();
    pollTimer = setInterval(() => {
        void loadRuns();
    }, 5000);
});

onUnmounted(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
    }
});
</script>

<template>
    <SpaLayout>
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Import runs
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Daily and manual sync history.
                </p>
            </div>
            <button
                type="button"
                class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-60"
                :disabled="syncing"
                @click="runSync"
            >
                {{ syncing ? 'Queuing…' : 'Run sync now' }}
            </button>
        </div>

        <div v-if="loading" class="text-sm text-slate-500">Loading…</div>
        <div v-else-if="error" class="text-sm text-red-600">{{ error }}</div>
        <div
            v-else-if="runs.length === 0"
            class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500"
        >
            No import runs yet.
        </div>
        <ul v-else class="space-y-4">
            <li
                v-for="run in runs"
                :key="run.id"
                class="rounded-xl border border-slate-200 bg-white p-4"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="font-medium">Run #{{ run.id }}</p>
                        <p class="text-xs text-slate-500">
                            {{ run.trigger }} · {{ run.created_at }}
                        </p>
                    </div>
                    <span
                        class="rounded-full px-2 py-0.5 text-xs capitalize"
                        :class="{
                            'bg-amber-50 text-amber-700':
                                run.status === 'pending' ||
                                run.status === 'running',
                            'bg-emerald-50 text-emerald-700':
                                run.status === 'completed',
                            'bg-red-50 text-red-700': run.status === 'failed',
                        }"
                    >
                        {{ run.status }}
                    </span>
                </div>
                <p
                    v-if="run.message"
                    class="mt-2 text-sm text-slate-600"
                >
                    {{ run.message }}
                </p>
                <ul
                    v-if="run.entries && run.entries.length > 0"
                    class="mt-3 space-y-1 text-sm text-slate-600"
                >
                    <li
                        v-for="entry in run.entries"
                        :key="entry.id"
                    >
                        <span class="font-mono text-xs">{{
                            entry.destination_table
                        }}</span>
                        · read {{ entry.rows_read }}, wrote
                        {{ entry.rows_written }}, skipped
                        {{ entry.rows_skipped }}
                        <span
                            v-if="entry.error"
                            class="text-red-600"
                        >
                            — {{ entry.error }}
                        </span>
                    </li>
                </ul>
            </li>
        </ul>
    </SpaLayout>
</template>
