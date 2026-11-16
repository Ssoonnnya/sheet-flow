<script setup lang="ts">
import { computed } from 'vue';
import {
    PAGE_SIZES,
    type PageSize,
    type PaginationMeta,
} from '@/spa/api/types';

const props = defineProps<{
    meta: PaginationMeta;
}>();

const emit = defineEmits<{
    page: [page: number];
    'per-page': [perPage: PageSize];
}>();

const rangeLabel = computed(() => {
    const { from, to, total } = props.meta;

    if (total === 0 || from === null || to === null) {
        return 'Showing 0 of 0';
    }

    return `Showing ${from}–${to} of ${total}`;
});

const pages = computed(() => {
    const current = props.meta.current_page;
    const last = props.meta.last_page;
    const items: Array<number | 'ellipsis'> = [];

    if (last <= 7) {
        for (let page = 1; page <= last; page += 1) {
            items.push(page);
        }

        return items;
    }

    items.push(1);

    if (current > 3) {
        items.push('ellipsis');
    }

    const start = Math.max(2, current - 1);
    const end = Math.min(last - 1, current + 1);

    for (let page = start; page <= end; page += 1) {
        items.push(page);
    }

    if (current < last - 2) {
        items.push('ellipsis');
    }

    items.push(last);

    return items;
});

function goTo(page: number): void {
    if (page < 1 || page > props.meta.last_page || page === props.meta.current_page) {
        return;
    }

    emit('page', page);
}

function onPerPageChange(event: Event): void {
    const value = Number((event.target as HTMLSelectElement).value);

    if ((PAGE_SIZES as readonly number[]).includes(value)) {
        emit('per-page', value as PageSize);
    }
}
</script>

<template>
    <div
        class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4 text-sm text-slate-600"
    >
        <p>{{ rangeLabel }}</p>

        <div class="flex flex-wrap items-center gap-3">
            <label class="flex items-center gap-2">
                <span class="text-slate-500">Per page</span>
                <select
                    class="rounded-md border border-slate-300 px-2 py-1 text-sm"
                    :value="meta.per_page"
                    @change="onPerPageChange"
                >
                    <option
                        v-for="size in PAGE_SIZES"
                        :key="size"
                        :value="size"
                    >
                        {{ size }}
                    </option>
                </select>
            </label>

            <div class="flex items-center gap-1">
                <button
                    type="button"
                    class="rounded-md border border-slate-300 px-2.5 py-1 hover:bg-slate-50 disabled:opacity-40"
                    :disabled="meta.current_page <= 1"
                    @click="goTo(meta.current_page - 1)"
                >
                    Prev
                </button>

                <template v-for="(item, index) in pages" :key="`${item}-${index}`">
                    <span
                        v-if="item === 'ellipsis'"
                        class="px-1 text-slate-400"
                    >…</span>
                    <button
                        v-else
                        type="button"
                        class="min-w-8 rounded-md border px-2.5 py-1"
                        :class="
                            item === meta.current_page
                                ? 'border-slate-900 bg-slate-900 text-white'
                                : 'border-slate-300 hover:bg-slate-50'
                        "
                        @click="goTo(item)"
                    >
                        {{ item }}
                    </button>
                </template>

                <button
                    type="button"
                    class="rounded-md border border-slate-300 px-2.5 py-1 hover:bg-slate-50 disabled:opacity-40"
                    :disabled="meta.current_page >= meta.last_page"
                    @click="goTo(meta.current_page + 1)"
                >
                    Next
                </button>
            </div>
        </div>
    </div>
</template>
