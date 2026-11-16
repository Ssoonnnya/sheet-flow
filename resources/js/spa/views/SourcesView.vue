<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { api } from '@/spa/api/client';
import { ApiError, type SheetSource } from '@/spa/api/types';
import SpaLayout from '@/spa/layouts/SpaLayout.vue';

const sources = ref<SheetSource[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const creating = ref(false);
const spreadsheetId = ref('');
const fieldErrors = ref<Record<string, string[]>>({});
const formError = ref<string | null>(null);
const syncing = ref(false);
const syncMessage = ref<string | null>(null);

async function loadSources(): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
        sources.value = await api.get<SheetSource[]>('/sources');
    } catch (err) {
        error.value =
            err instanceof ApiError ? err.message : 'Failed to load sources.';
    } finally {
        loading.value = false;
    }
}

async function createSource(): Promise<void> {
    creating.value = true;
    fieldErrors.value = {};
    formError.value = null;
    syncMessage.value = null;

    try {
        const source = await api.post<SheetSource>('/sources', {
            spreadsheet_id: spreadsheetId.value,
            is_active: true,
        });
        sources.value = [...sources.value, source].sort((a, b) =>
            a.name.localeCompare(b.name),
        );
        spreadsheetId.value = '';
        syncMessage.value = `Connected “${source.name}” and queued the first sync.`;
    } catch (err) {
        if (err instanceof ApiError) {
            fieldErrors.value = err.errors;
            formError.value = err.message;
        } else {
            formError.value = 'Failed to connect spreadsheet.';
        }
    } finally {
        creating.value = false;
    }
}

async function runSync(): Promise<void> {
    syncing.value = true;
    syncMessage.value = null;

    try {
        const run = await api.post<{ id: number; status: string }>('/sync');
        syncMessage.value = `Queued import run #${run.id} (${run.status}).`;
    } catch (err) {
        syncMessage.value =
            err instanceof ApiError ? err.message : 'Failed to queue sync.';
    } finally {
        syncing.value = false;
    }
}

onMounted(() => {
    void loadSources();
});
</script>

<template>
    <SpaLayout>
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Sources</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Paste a Google Sheets link. Tabs, columns, and tables are
                    configured automatically.
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

        <p v-if="syncMessage" class="mb-4 text-sm text-slate-600">
            {{ syncMessage }}
        </p>

        <form
            class="mb-8 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-[1fr_auto]"
            @submit.prevent="createSource"
        >
            <label class="block space-y-1 text-sm">
                <span class="font-medium">Google Sheets URL or id</span>
                <input
                    v-model="spreadsheetId"
                    required
                    placeholder="https://docs.google.com/spreadsheets/d/…"
                    class="w-full rounded-md border border-slate-300 px-3 py-2"
                />
                <span
                    v-if="fieldErrors.spreadsheet_id?.[0]"
                    class="text-red-600"
                >{{ fieldErrors.spreadsheet_id[0] }}</span>
            </label>
            <div class="flex items-end">
                <button
                    type="submit"
                    class="w-full rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50 disabled:opacity-60 md:w-auto"
                    :disabled="creating"
                >
                    {{ creating ? 'Connecting…' : 'Connect sheet' }}
                </button>
            </div>
            <p
                v-if="formError"
                class="text-sm text-red-600 md:col-span-2"
            >
                {{ formError }}
            </p>
        </form>

        <div v-if="loading" class="text-sm text-slate-500">Loading…</div>
        <div v-else-if="error" class="text-sm text-red-600">{{ error }}</div>
        <div
            v-else-if="sources.length === 0"
            class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500"
        >
            No sources yet. Paste a spreadsheet link above.
        </div>
        <ul v-else class="space-y-3">
            <li
                v-for="source in sources"
                :key="source.id"
                class="rounded-xl border border-slate-200 bg-white p-4"
            >
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <RouterLink
                            :to="{
                                name: 'source-detail',
                                params: { id: source.id },
                            }"
                            class="font-medium hover:underline"
                        >
                            {{ source.name }}
                        </RouterLink>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ source.tabs?.length ?? 0 }} tab(s) ·
                            <span class="font-mono">{{
                                source.spreadsheet_id
                            }}</span>
                        </p>
                    </div>
                    <span
                        class="rounded-full px-2 py-0.5 text-xs"
                        :class="
                            source.is_active
                                ? 'bg-emerald-50 text-emerald-700'
                                : 'bg-slate-100 text-slate-500'
                        "
                    >
                        {{ source.is_active ? 'active' : 'inactive' }}
                    </span>
                </div>
            </li>
        </ul>
    </SpaLayout>
</template>
