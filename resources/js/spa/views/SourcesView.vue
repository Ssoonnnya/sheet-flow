<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { api } from '@/spa/api/client';
import {
    ApiError,
    type PageSize,
    type PaginationMeta,
    type SheetSource,
} from '@/spa/api/types';
import PaginationBar from '@/spa/components/PaginationBar.vue';
import SpaLayout from '@/spa/layouts/SpaLayout.vue';

const sources = ref<SheetSource[]>([]);
const meta = ref<PaginationMeta>({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: null,
    to: null,
});
const page = ref(1);
const perPage = ref<PageSize>(15);
const loading = ref(true);
const error = ref<string | null>(null);
const creating = ref(false);
const spreadsheetId = ref('');
const fieldErrors = ref<Record<string, string[]>>({});
const formError = ref<string | null>(null);
const syncing = ref(false);
const syncMessage = ref<string | null>(null);
const editingId = ref<number | null>(null);
const editName = ref('');
const editActive = ref(true);
const editSpreadsheetId = ref('');
const editErrors = ref<Record<string, string[]>>({});
const savingId = ref<number | null>(null);
const deletingId = ref<number | null>(null);

async function loadSources(): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
        const payload = await api.getPage<SheetSource>(
            `/sources?page=${page.value}&per_page=${perPage.value}`,
        );
        sources.value = payload.data;
        meta.value = payload.meta;
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
        spreadsheetId.value = '';
        syncMessage.value = `Connected “${source.name}” and queued the first sync.`;
        page.value = 1;
        await loadSources();
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

function startEdit(source: SheetSource): void {
    editingId.value = source.id;
    editName.value = source.name;
    editActive.value = source.is_active;
    editSpreadsheetId.value = source.spreadsheet_id;
    editErrors.value = {};
}

function cancelEdit(): void {
    editingId.value = null;
    editErrors.value = {};
}

async function saveEdit(sourceId: number): Promise<void> {
    savingId.value = sourceId;
    editErrors.value = {};
    syncMessage.value = null;

    try {
        await api.put<SheetSource>(`/sources/${sourceId}`, {
            name: editName.value,
            is_active: editActive.value,
            spreadsheet_id: editSpreadsheetId.value,
        });
        editingId.value = null;
        await loadSources();
    } catch (err) {
        if (err instanceof ApiError) {
            editErrors.value = err.errors;
            syncMessage.value = err.message;
        } else {
            syncMessage.value = 'Failed to update source.';
        }
    } finally {
        savingId.value = null;
    }
}

async function deleteSource(source: SheetSource): Promise<void> {
    if (!window.confirm(`Delete source “${source.name}”? This cannot be undone.`)) {
        return;
    }

    deletingId.value = source.id;
    syncMessage.value = null;

    try {
        await api.delete(`/sources/${source.id}`);
        if (sources.value.length === 1 && page.value > 1) {
            page.value -= 1;
        }
        await loadSources();
    } catch (err) {
        syncMessage.value =
            err instanceof ApiError ? err.message : 'Failed to delete source.';
    } finally {
        deletingId.value = null;
    }
}

function onPageChange(nextPage: number): void {
    page.value = nextPage;
    void loadSources();
}

function onPerPageChange(nextPerPage: PageSize): void {
    perPage.value = nextPerPage;
    page.value = 1;
    void loadSources();
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
        <div v-else class="space-y-4">
            <ul class="space-y-3">
                <li
                    v-for="source in sources"
                    :key="source.id"
                    class="rounded-xl border border-slate-200 bg-white p-4"
                >
                    <div
                        v-if="editingId === source.id"
                        class="space-y-3"
                    >
                        <div class="grid gap-3 md:grid-cols-2">
                            <label class="block space-y-1 text-sm">
                                <span class="font-medium">Name</span>
                                <input
                                    v-model="editName"
                                    required
                                    class="w-full rounded-md border border-slate-300 px-3 py-2"
                                />
                                <span
                                    v-if="editErrors.name?.[0]"
                                    class="text-red-600"
                                >{{ editErrors.name[0] }}</span>
                            </label>
                            <label class="flex items-end gap-2 pb-2 text-sm">
                                <input
                                    v-model="editActive"
                                    type="checkbox"
                                    class="rounded border-slate-300"
                                />
                                <span>Active</span>
                            </label>
                            <label class="block space-y-1 text-sm md:col-span-2">
                                <span class="font-medium">Spreadsheet URL or id</span>
                                <input
                                    v-model="editSpreadsheetId"
                                    required
                                    class="w-full rounded-md border border-slate-300 px-3 py-2 font-mono text-xs"
                                />
                                <span
                                    v-if="editErrors.spreadsheet_id?.[0]"
                                    class="text-red-600"
                                >{{ editErrors.spreadsheet_id[0] }}</span>
                            </label>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="rounded-md bg-slate-900 px-3 py-1.5 text-sm text-white hover:bg-slate-800 disabled:opacity-60"
                                :disabled="savingId === source.id"
                                @click="saveEdit(source.id)"
                            >
                                {{ savingId === source.id ? 'Saving…' : 'Save' }}
                            </button>
                            <button
                                type="button"
                                class="rounded-md border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50"
                                @click="cancelEdit"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                    <div
                        v-else
                        class="flex flex-wrap items-center justify-between gap-4"
                    >
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
                        <div class="flex flex-wrap items-center gap-2">
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
                            <button
                                type="button"
                                class="rounded-md border border-slate-300 px-2.5 py-1 text-sm hover:bg-slate-50"
                                @click="startEdit(source)"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                class="rounded-md border border-red-200 px-2.5 py-1 text-sm text-red-700 hover:bg-red-50 disabled:opacity-60"
                                :disabled="deletingId === source.id"
                                @click="deleteSource(source)"
                            >
                                {{ deletingId === source.id ? 'Deleting…' : 'Delete' }}
                            </button>
                        </div>
                    </div>
                </li>
            </ul>

            <PaginationBar
                :meta="meta"
                @page="onPageChange"
                @per-page="onPerPageChange"
            />
        </div>
    </SpaLayout>
</template>
