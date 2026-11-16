<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { api } from '@/spa/api/client';
import {
    ApiError,
    COLUMN_TYPES,
    type ColumnMapping,
    type SheetSource,
    type SheetTab,
    type TableMapping,
} from '@/spa/api/types';
import SpaLayout from '@/spa/layouts/SpaLayout.vue';

const props = defineProps<{
    id: string;
}>();

const router = useRouter();

const source = ref<SheetSource | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);
const notice = ref<string | null>(null);
const showAdvanced = ref(false);
const syncing = ref(false);
const editing = ref(false);
const editName = ref('');
const editActive = ref(true);
const saving = ref(false);
const deleting = ref(false);
const editErrors = ref<Record<string, string[]>>({});

const tabName = ref('');
const mappingTable = ref('');
const mappingUnique = ref('date,name');
const selectedTabId = ref<number | null>(null);
const selectedMappingId = ref<number | null>(null);

const columnSource = ref('');
const columnDestination = ref('');
const columnType = ref<(typeof COLUMN_TYPES)[number]>('string');

const selectedTab = computed(
    () =>
        source.value?.tabs?.find((tab) => tab.id === selectedTabId.value) ??
        null,
);

const selectedMapping = computed(
    () =>
        selectedTab.value?.mappings?.find(
            (mapping) => mapping.id === selectedMappingId.value,
        ) ?? null,
);

const tabCount = computed(() => source.value?.tabs?.length ?? 0);
const mappingCount = computed(
    () =>
        source.value?.tabs?.reduce(
            (total, tab) => total + (tab.mappings?.length ?? 0),
            0,
        ) ?? 0,
);

async function loadSource(): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
        source.value = await api.get<SheetSource>(`/sources/${props.id}`);
        selectedTabId.value = source.value.tabs?.[0]?.id ?? null;
        selectedMappingId.value =
            source.value.tabs?.[0]?.mappings?.[0]?.id ?? null;
    } catch (err) {
        error.value =
            err instanceof ApiError ? err.message : 'Failed to load source.';
    } finally {
        loading.value = false;
    }
}

function showError(err: unknown, fallback: string): void {
    notice.value = err instanceof ApiError ? err.message : fallback;
    if (err instanceof ApiError && Object.keys(err.errors).length > 0) {
        const first = Object.values(err.errors)[0]?.[0];
        if (first) {
            notice.value = first;
        }
    }
}

async function runSync(): Promise<void> {
    syncing.value = true;
    notice.value = null;

    try {
        const run = await api.post<{ id: number }>('/sync');
        notice.value = `Queued import run #${run.id}.`;
    } catch (err) {
        showError(err, 'Failed to queue sync.');
    } finally {
        syncing.value = false;
    }
}

function startEdit(): void {
    if (!source.value) {
        return;
    }

    editing.value = true;
    editName.value = source.value.name;
    editActive.value = source.value.is_active;
    editErrors.value = {};
}

function cancelEdit(): void {
    editing.value = false;
    editErrors.value = {};
}

async function saveEdit(): Promise<void> {
    if (!source.value) {
        return;
    }

    saving.value = true;
    notice.value = null;
    editErrors.value = {};

    try {
        source.value = await api.put<SheetSource>(`/sources/${props.id}`, {
            name: editName.value,
            is_active: editActive.value,
        });
        editing.value = false;
        notice.value = 'Source updated.';
    } catch (err) {
        if (err instanceof ApiError) {
            editErrors.value = err.errors;
        }
        showError(err, 'Failed to update source.');
    } finally {
        saving.value = false;
    }
}

async function deleteSource(): Promise<void> {
    if (!source.value) {
        return;
    }

    if (
        !window.confirm(
            `Delete source “${source.value.name}”? This cannot be undone.`,
        )
    ) {
        return;
    }

    deleting.value = true;
    notice.value = null;

    try {
        await api.delete(`/sources/${props.id}`);
        await router.push({ name: 'sources' });
    } catch (err) {
        showError(err, 'Failed to delete source.');
        deleting.value = false;
    }
}

async function addTab(): Promise<void> {
    notice.value = null;

    try {
        const tab = await api.post<SheetTab>(`/sources/${props.id}/tabs`, {
            name: tabName.value,
            header_row: 1,
            is_active: true,
        });
        if (source.value) {
            source.value.tabs = [...(source.value.tabs ?? []), tab];
        }
        selectedTabId.value = tab.id;
        tabName.value = '';
    } catch (err) {
        showError(err, 'Failed to add tab.');
    }
}

async function addMapping(): Promise<void> {
    if (!selectedTab.value) {
        return;
    }

    notice.value = null;

    try {
        const uniqueColumns = mappingUnique.value
            .split(',')
            .map((value) => value.trim())
            .filter(Boolean);

        const mapping = await api.post<TableMapping>(
            `/tabs/${selectedTab.value.id}/mappings`,
            {
                destination_table: mappingTable.value,
                unique_columns: uniqueColumns,
                is_active: true,
            },
        );

        selectedTab.value.mappings = [
            ...(selectedTab.value.mappings ?? []),
            mapping,
        ];
        selectedMappingId.value = mapping.id;
        mappingTable.value = '';
        mappingUnique.value = 'date,name';
    } catch (err) {
        showError(err, 'Failed to add mapping.');
    }
}

async function addColumn(): Promise<void> {
    if (!selectedMapping.value) {
        return;
    }

    notice.value = null;

    try {
        const column = await api.post<ColumnMapping>(
            `/mappings/${selectedMapping.value.id}/columns`,
            {
                source_column: columnSource.value,
                destination_column: columnDestination.value,
                data_type: columnType.value,
            },
        );

        selectedMapping.value.columns = [
            ...(selectedMapping.value.columns ?? []),
            column,
        ];
        columnSource.value = '';
        columnDestination.value = '';
        columnType.value = 'string';
    } catch (err) {
        showError(err, 'Failed to add column mapping.');
    }
}

function selectTab(tabId: number): void {
    selectedTabId.value = tabId;
    selectedMappingId.value =
        source.value?.tabs?.find((tab) => tab.id === tabId)?.mappings?.[0]
            ?.id ?? null;
}

onMounted(() => {
    void loadSource();
});
</script>

<template>
    <SpaLayout>
        <div class="mb-6">
            <RouterLink
                :to="{ name: 'sources' }"
                class="text-sm text-slate-500 hover:text-slate-800"
            >
                ← Sources
            </RouterLink>
        </div>

        <div v-if="loading" class="text-sm text-slate-500">Loading…</div>
        <div v-else-if="error" class="text-sm text-red-600">{{ error }}</div>
        <div v-else-if="source" class="space-y-6">
            <div
                class="flex flex-wrap items-start justify-between gap-4"
            >
                <div class="min-w-0 flex-1">
                    <div v-if="editing" class="space-y-3">
                        <label class="block space-y-1 text-sm">
                            <span class="font-medium">Name</span>
                            <input
                                v-model="editName"
                                required
                                class="w-full max-w-md rounded-md border border-slate-300 px-3 py-2"
                            />
                            <span
                                v-if="editErrors.name?.[0]"
                                class="text-red-600"
                            >{{ editErrors.name[0] }}</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                v-model="editActive"
                                type="checkbox"
                                class="rounded border-slate-300"
                            />
                            <span>Active</span>
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="rounded-md bg-slate-900 px-3 py-1.5 text-sm text-white hover:bg-slate-800 disabled:opacity-60"
                                :disabled="saving"
                                @click="saveEdit"
                            >
                                {{ saving ? 'Saving…' : 'Save' }}
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
                    <div v-else>
                        <h1 class="text-2xl font-semibold tracking-tight">
                            {{ source.name }}
                        </h1>
                        <p class="mt-1 font-mono text-sm text-slate-500">
                            {{ source.spreadsheet_id }}
                        </p>
                        <p class="mt-2 text-sm text-slate-600">
                            {{ tabCount }} tab(s), {{ mappingCount }} destination
                            table(s). New sheet columns are added on sync.
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ source.is_active ? 'Active' : 'Inactive' }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-if="!editing"
                        type="button"
                        class="rounded-md border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50"
                        @click="startEdit"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        class="rounded-md border border-red-200 px-3 py-2 text-sm text-red-700 hover:bg-red-50 disabled:opacity-60"
                        :disabled="deleting"
                        @click="deleteSource"
                    >
                        {{ deleting ? 'Deleting…' : 'Delete' }}
                    </button>
                    <button
                        type="button"
                        class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-60"
                        :disabled="syncing"
                        @click="runSync"
                    >
                        {{ syncing ? 'Queuing…' : 'Run sync now' }}
                    </button>
                </div>
            </div>

            <p v-if="notice" class="text-sm text-slate-600">{{ notice }}</p>

            <section class="rounded-xl border border-slate-200 bg-white p-4">
                <h2 class="font-medium">Configured tabs</h2>
                <ul
                    v-if="(source.tabs ?? []).length > 0"
                    class="mt-3 space-y-2 text-sm"
                >
                    <li
                        v-for="tab in source.tabs"
                        :key="tab.id"
                        class="flex flex-wrap justify-between gap-2 border-t border-slate-100 pt-2"
                    >
                        <span>{{ tab.name }}</span>
                        <span class="text-slate-500">
                            {{
                                tab.mappings
                                    ?.map((m) => m.destination_table)
                                    .join(', ') || 'no tables'
                            }}
                        </span>
                    </li>
                </ul>
                <p v-else class="mt-2 text-sm text-slate-500">
                    No tabs yet. Reconnect the sheet or use advanced
                    configuration.
                </p>
            </section>

            <div>
                <button
                    type="button"
                    class="text-sm font-medium text-slate-700 underline-offset-2 hover:underline"
                    @click="showAdvanced = !showAdvanced"
                >
                    {{
                        showAdvanced
                            ? 'Hide advanced configuration'
                            : 'Advanced configuration'
                    }}
                </button>
                <p class="mt-1 text-xs text-slate-500">
                    Rename columns (product_name → name) or map one sheet into
                    multiple tables.
                </p>
            </div>

            <div v-if="showAdvanced" class="space-y-8">
                <section
                    class="space-y-4 rounded-xl border border-slate-200 bg-white p-4"
                >
                    <h2 class="font-medium">Tabs</h2>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="tab in source.tabs ?? []"
                            :key="tab.id"
                            type="button"
                            class="rounded-md border px-3 py-1.5 text-sm"
                            :class="
                                tab.id === selectedTabId
                                    ? 'border-slate-900 bg-slate-900 text-white'
                                    : 'border-slate-300 hover:bg-slate-50'
                            "
                            @click="selectTab(tab.id)"
                        >
                            {{ tab.name }}
                        </button>
                    </div>
                    <form
                        class="flex flex-wrap gap-2"
                        @submit.prevent="addTab"
                    >
                        <input
                            v-model="tabName"
                            required
                            placeholder="Tab name"
                            class="rounded-md border border-slate-300 px-3 py-2 text-sm"
                        />
                        <button
                            type="submit"
                            class="rounded-md border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50"
                        >
                            Add tab
                        </button>
                    </form>
                </section>

                <section
                    v-if="selectedTab"
                    class="space-y-4 rounded-xl border border-slate-200 bg-white p-4"
                >
                    <h2 class="font-medium">
                        Mappings for {{ selectedTab.name }}
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="mapping in selectedTab.mappings ?? []"
                            :key="mapping.id"
                            type="button"
                            class="rounded-md border px-3 py-1.5 text-sm"
                            :class="
                                mapping.id === selectedMappingId
                                    ? 'border-slate-900 bg-slate-900 text-white'
                                    : 'border-slate-300 hover:bg-slate-50'
                            "
                            @click="selectedMappingId = mapping.id"
                        >
                            {{ mapping.destination_table }}
                        </button>
                    </div>
                    <form
                        class="grid gap-2 md:grid-cols-[1fr_1fr_auto]"
                        @submit.prevent="addMapping"
                    >
                        <input
                            v-model="mappingTable"
                            required
                            placeholder="destination_table"
                            class="rounded-md border border-slate-300 px-3 py-2 text-sm"
                        />
                        <input
                            v-model="mappingUnique"
                            required
                            placeholder="unique columns, comma-separated"
                            class="rounded-md border border-slate-300 px-3 py-2 text-sm"
                        />
                        <button
                            type="submit"
                            class="rounded-md border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50"
                        >
                            Add mapping
                        </button>
                    </form>
                </section>

                <section
                    v-if="selectedMapping"
                    class="space-y-4 rounded-xl border border-slate-200 bg-white p-4"
                >
                    <div>
                        <h2 class="font-medium">
                            Columns →
                            {{ selectedMapping.destination_table }}
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Unique key:
                            {{ selectedMapping.unique_columns.join(', ') }}
                        </p>
                    </div>

                    <table
                        v-if="(selectedMapping.columns ?? []).length > 0"
                        class="w-full text-left text-sm"
                    >
                        <thead class="text-slate-500">
                            <tr>
                                <th class="py-2 font-medium">Sheet column</th>
                                <th class="py-2 font-medium">DB column</th>
                                <th class="py-2 font-medium">Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="column in selectedMapping.columns"
                                :key="column.id"
                                class="border-t border-slate-100"
                            >
                                <td class="py-2 font-mono text-xs">
                                    {{ column.source_column }}
                                </td>
                                <td class="py-2 font-mono text-xs">
                                    {{ column.destination_column }}
                                </td>
                                <td class="py-2">{{ column.data_type }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <form
                        class="grid gap-2 md:grid-cols-[1fr_1fr_1fr_auto]"
                        @submit.prevent="addColumn"
                    >
                        <input
                            v-model="columnSource"
                            required
                            placeholder="sheet column"
                            class="rounded-md border border-slate-300 px-3 py-2 text-sm"
                        />
                        <input
                            v-model="columnDestination"
                            required
                            placeholder="db column"
                            class="rounded-md border border-slate-300 px-3 py-2 text-sm"
                        />
                        <select
                            v-model="columnType"
                            class="rounded-md border border-slate-300 px-3 py-2 text-sm"
                        >
                            <option
                                v-for="type in COLUMN_TYPES"
                                :key="type"
                                :value="type"
                            >
                                {{ type }}
                            </option>
                        </select>
                        <button
                            type="submit"
                            class="rounded-md border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50"
                        >
                            Add column
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </SpaLayout>
</template>
