<template>
    <div v-if="modelKey && allowed" ref="root" class="relative">
        <button
            type="button"
            class="inline-flex cursor-pointer items-center justify-center gap-2 border border-list-border bg-list-surface text-sm font-semibold text-list-text shadow-sm hover:border-[var(--color-primary)] hover:text-[var(--color-primary)]"
            :class="compact ? 'size-9 rounded-lg' : 'min-h-10 w-full rounded-lg px-3 py-2'"
            :aria-expanded="open"
            :aria-label="t('LIST_ACTIONS')"
            :title="t('LIST_ACTIONS')"
            @click="toggle"
        >
            <Icon class="size-[18px]" icon="material-symbols:more-horiz" />
            <span v-if="!compact">{{ t('LIST_ACTIONS') }}</span>
        </button>
        <div
            v-if="open"
            class="fixed z-50 flex w-56 flex-col overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
            :style="menuStyle"
        >
            <button
                v-for="item in items"
                :key="item.id"
                type="button"
                class="cursor-pointer px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="item.run"
            >
                {{ t(item.label) }}
            </button>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import { computed, onBeforeUnmount, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useTransferModals } from '@/composables/useTransferModals';
    import { transferPermission } from '@/modules/support/transfer/transferModels';
    import { useAuthStore } from '@/stores/auth';

    defineOptions({ name: 'ImportExportActions' });

    const props = withDefaults(
        defineProps<{
            transferKey: string | null;
            refresh: () => void;
            filters?: Record<string, unknown>;
            search?: string;
            compact?: boolean;
        }>(),
        { compact: false },
    );

    const { t } = useI18n();
    const auth = useAuthStore();
    const { openImport, openTransfer } = useTransferModals();
    const open = ref(false);
    const root = ref<HTMLElement | null>(null);
    const menuStyle = ref({ top: '0px', left: '0px' });

    function toggle(event: MouseEvent): void {
        open.value = !open.value;
        const button = event.currentTarget;
        if (open.value && button instanceof HTMLElement) {
            const rect = button.getBoundingClientRect();
            menuStyle.value = {
                top: `${rect.bottom + 4}px`,
                left: `${Math.max(8, rect.right - 224)}px`,
            };
        }
    }

    function onPointerDown(event: MouseEvent): void {
        if (!open.value) return;
        const target = event.target;
        if (target instanceof Node && root.value?.contains(target)) return;
        open.value = false;
    }

    window.addEventListener('mousedown', onPointerDown);
    onBeforeUnmount(() => window.removeEventListener('mousedown', onPointerDown));

    const modelKey = computed(() => props.transferKey);
    const allowed = computed(() => (modelKey.value ? auth.hasPermission(transferPermission(modelKey.value)) : false));

    const items = computed(() => [
        { id: 'import', label: 'IMPORT_DATA', run: startImport },
        { id: 'export', label: 'EXPORT_DATA', run: () => startTransfer('export') },
        { id: 'template', label: 'DATA_TRANSFER_TEMPLATE', run: () => startTransfer('template') },
    ]);

    function close(): void {
        open.value = false;
    }

    function startImport(): void {
        close();
        if (!modelKey.value) return;
        openImport(modelKey.value, props.refresh);
    }

    function startTransfer(purpose: 'export' | 'template'): void {
        close();
        if (!modelKey.value) return;
        openTransfer(modelKey.value, purpose, props.filters ?? {}, props.search ?? '');
    }
</script>
