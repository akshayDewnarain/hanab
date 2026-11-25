<script lang="ts">
    /** Only one click-persisted action stack open at a time. Shared with the media card pattern. */
    let activeClose: (() => void) | null = null;
</script>

<script setup lang="ts">
    import '@freelance/vue-ui/bubble-pop.css';
    import { computed, onMounted, onUnmounted, ref } from 'vue';
    import { Icon } from '@iconify/vue';

    export type ExpandableCardAction = {
        key: string;
        icon: string;
        label: string;
        disabled?: boolean;
        successIcon?: string;
    };

    defineOptions({ name: 'ExpandableCardActions' });

    const props = withDefaults(
        defineProps<{
            actions: ExpandableCardAction[];
            triggerIcon?: string;
            triggerLabel?: string;
            size?: 'sm' | 'md';
            openOnHover?: boolean;
            openOnClick?: boolean;
            disabled?: boolean;
            closeDelayMs?: number;
            activeKey?: string | null;
            /** `inline` sits in a toolbar. `overlay` matches the media card corner. */
            placement?: 'overlay' | 'inline';
        }>(),
        {
            triggerIcon: 'material-symbols:more-horiz-rounded',
            triggerLabel: 'Actions',
            size: 'sm',
            openOnHover: true,
            openOnClick: true,
            disabled: false,
            closeDelayMs: 120,
            activeKey: null,
            placement: 'overlay',
        },
    );

    const emit = defineEmits<{
        action: [key: string];
        open: [];
        close: [];
    }>();

    type OpenMode = 'hover' | 'click' | null;

    const rootRef = ref<HTMLElement | null>(null);
    const triggerRef = ref<HTMLButtonElement | null>(null);
    const isOpen = ref(false);
    const openMode = ref<OpenMode>(null);
    const successKey = ref<string | null>(null);
    let closeTimer: ReturnType<typeof setTimeout> | null = null;
    let successTimer: ReturnType<typeof setTimeout> | null = null;

    const visibleActions = computed(() => (isOpen.value ? props.actions : []));

    function clearCloseTimer(): void {
        if (closeTimer) {
            clearTimeout(closeTimer);
            closeTimer = null;
        }
    }

    function unregisterActive(): void {
        if (activeClose === closeFromManager) {
            activeClose = null;
        }
    }

    function closeFromManager(): void {
        doClose();
    }

    function doClose(restoreFocus = false): void {
        clearCloseTimer();
        if (!isOpen.value) return;
        isOpen.value = false;
        openMode.value = null;
        unregisterActive();
        emit('close');
        if (restoreFocus) {
            triggerRef.value?.focus();
        }
    }

    function open(mode: 'hover' | 'click'): void {
        if (props.disabled || props.actions.length === 0) return;
        clearCloseTimer();
        if (mode === 'click') {
            activeClose?.();
            activeClose = closeFromManager;
        }
        const wasClosed = !isOpen.value;
        isOpen.value = true;
        openMode.value = mode;
        if (wasClosed) {
            emit('open');
        }
    }

    function close(reason: 'pointer' | 'outside' | 'keyboard' | 'action' = 'pointer'): void {
        if (reason === 'pointer' && openMode.value === 'click') {
            return;
        }
        doClose(reason === 'keyboard');
    }

    function scheduleClose(): void {
        clearCloseTimer();
        closeTimer = setTimeout(() => close('pointer'), props.closeDelayMs);
    }

    function onPointerEnter(): void {
        if (!props.openOnHover || props.disabled) return;
        if (openMode.value === 'click') {
            clearCloseTimer();
            return;
        }
        open('hover');
    }

    function onPointerLeave(): void {
        if (!props.openOnHover || openMode.value === 'click') return;
        scheduleClose();
    }

    function onTriggerClick(): void {
        if (props.disabled || !props.openOnClick) return;
        if (isOpen.value && openMode.value === 'click') {
            doClose();
            return;
        }
        open('click');
    }

    function onActionClick(action: ExpandableCardAction): void {
        if (action.disabled || props.disabled) return;
        emit('action', action.key);
        if (action.successIcon) {
            successKey.value = action.key;
            if (successTimer) clearTimeout(successTimer);
            successTimer = setTimeout(() => {
                successKey.value = null;
            }, 1000);
        }
        doClose();
    }

    function focusAction(offset: number): void {
        const items = rootRef.value?.querySelectorAll<HTMLButtonElement>('[role="menuitem"]');
        if (!items?.length) return;
        const list = [...items];
        const current = list.indexOf(document.activeElement as HTMLButtonElement);
        const next = list[(current + offset + list.length) % list.length];
        next?.focus();
    }

    function handleClickOutside(event: MouseEvent): void {
        if (!isOpen.value || openMode.value !== 'click') return;
        const root = rootRef.value;
        if (!root || event.composedPath().includes(root)) return;
        doClose();
    }

    function handleKeydown(event: KeyboardEvent): void {
        if (!isOpen.value) return;
        if (event.key === 'Escape') {
            doClose(true);
            return;
        }
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            focusAction(1);
        }
        if (event.key === 'ArrowUp') {
            event.preventDefault();
            focusAction(-1);
        }
    }

    onMounted(() => {
        document.addEventListener('click', handleClickOutside, true);
        document.addEventListener('keydown', handleKeydown);
    });

    onUnmounted(() => {
        clearCloseTimer();
        if (successTimer) clearTimeout(successTimer);
        unregisterActive();
        document.removeEventListener('click', handleClickOutside, true);
        document.removeEventListener('keydown', handleKeydown);
    });
</script>

<template>
    <div
        ref="rootRef"
        class="expandable-card-actions"
        :class="{
            'expandable-card-actions--open': isOpen,
            'expandable-card-actions--sm': size === 'sm',
            'expandable-card-actions--md': size === 'md',
            'expandable-card-actions--inline': placement === 'inline',
        }"
        @pointerenter="onPointerEnter"
        @pointerleave="onPointerLeave"
        @click.stop
    >
        <button
            ref="triggerRef"
            type="button"
            class="expandable-card-actions__trigger"
            :aria-label="triggerLabel"
            :aria-expanded="isOpen"
            aria-haspopup="menu"
            :disabled="disabled"
            :title="triggerLabel"
            @click.stop="onTriggerClick"
        >
            <Icon :icon="triggerIcon" aria-hidden="true" />
        </button>

        <TransitionGroup
            name="ui-bubble-pop"
            tag="div"
            class="expandable-card-actions__stack"
            role="menu"
            :aria-hidden="!isOpen"
        >
            <button
                v-for="(action, index) in visibleActions"
                :key="action.key"
                type="button"
                class="expandable-card-actions__bubble"
                :class="{
                    'expandable-card-actions__bubble--disabled': action.disabled,
                    'expandable-card-actions__bubble--success': successKey === action.key,
                    'expandable-card-actions__bubble--active': activeKey === action.key,
                }"
                :style="{ '--bubble-index': index }"
                role="menuitem"
                :disabled="action.disabled || disabled"
                :aria-label="action.label"
                :data-tooltip="action.label"
                @click.stop="onActionClick(action)"
            >
                <Icon
                    :icon="successKey === action.key && action.successIcon ? action.successIcon : action.icon"
                    aria-hidden="true"
                />
            </button>
        </TransitionGroup>
    </div>
</template>

<style scoped>
    .expandable-card-actions {
        --eca-size: 2rem;
        --eca-gap: 0.4rem;
        position: absolute;
        top: 0.65rem;
        right: 0.65rem;
        z-index: 3;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--eca-gap);
        opacity: 0.88;
        transition: opacity 140ms ease;
    }

    .expandable-card-actions--inline {
        position: relative;
        top: auto;
        right: auto;
        opacity: 1;
    }

    .expandable-card-actions--md {
        --eca-size: 2.25rem;
        --eca-gap: 0.45rem;
    }

    .expandable-card-actions--open,
    .expandable-card-actions:hover,
    .expandable-card-actions:focus-within {
        opacity: 1;
    }

    .expandable-card-actions__trigger,
    .expandable-card-actions__bubble {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: var(--eca-size);
        height: var(--eca-size);
        border: 1px solid var(--color-list-border, #e2e8f0);
        border-radius: 999px;
        background: color-mix(in srgb, var(--color-list-surface, #fff) 96%, transparent);
        color: var(--color-list-text, #0f172a);
        cursor: pointer;
        box-shadow: 0 1px 3px color-mix(in srgb, var(--color-list-shadow-tint, #0f172a) 16%, transparent);
        transition:
            background 140ms ease,
            color 140ms ease,
            border-color 140ms ease,
            transform 160ms ease,
            box-shadow 140ms ease;
    }

    .expandable-card-actions__trigger svg,
    .expandable-card-actions__bubble svg {
        width: 0.95rem;
        height: 0.95rem;
    }

    .expandable-card-actions__trigger:hover,
    .expandable-card-actions__bubble:hover:not(:disabled),
    .expandable-card-actions__trigger:focus-visible,
    .expandable-card-actions__bubble:focus-visible {
        color: var(--color-emphasis, var(--color-primary));
        border-color: color-mix(in srgb, var(--color-emphasis, var(--color-primary)) 45%, var(--color-list-border, #cbd5e1));
        background: var(--color-list-row-hover, #fff);
        outline: none;
    }

    .expandable-card-actions--open .expandable-card-actions__trigger {
        transform: rotate(90deg);
        border-color: color-mix(in srgb, var(--color-emphasis, var(--color-primary)) 40%, var(--color-list-border, #cbd5e1));
        color: var(--color-emphasis, var(--color-primary));
    }

    .expandable-card-actions__trigger:disabled,
    .expandable-card-actions__bubble:disabled,
    .expandable-card-actions__bubble--disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }

    .expandable-card-actions__stack {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--eca-gap);
        min-height: 0;
    }

    .expandable-card-actions__bubble {
        position: relative;
    }

    .expandable-card-actions__bubble--success {
        color: #15803d;
        border-color: color-mix(in srgb, #15803d 35%, #cbd5e1);
    }

    .expandable-card-actions__bubble--active {
        color: var(--color-emphasis, var(--color-primary));
        border-color: color-mix(in srgb, var(--color-emphasis, var(--color-primary)) 40%, var(--color-list-border, #cbd5e1));
        background: color-mix(in srgb, var(--color-emphasis, var(--color-primary)) 14%, var(--color-list-surface, #fff));
    }

    .expandable-card-actions__bubble[data-tooltip]::after {
        content: attr(data-tooltip);
        position: absolute;
        left: calc(100% + 0.45rem);
        top: 50%;
        transform: translateY(-50%);
        padding: 0.25rem 0.45rem;
        border-radius: 0.35rem;
        background: color-mix(in srgb, #0f172a 90%, transparent);
        color: #fff;
        font-size: 0.7rem;
        font-weight: 600;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 120ms ease;
        z-index: 4;
    }

    .expandable-card-actions--inline .expandable-card-actions__bubble[data-tooltip]::after {
        left: auto;
        right: calc(100% + 0.45rem);
    }

    .expandable-card-actions__bubble:hover::after,
    .expandable-card-actions__bubble:focus-visible::after {
        opacity: 1;
    }

    @media (pointer: coarse) {
        .expandable-card-actions {
            --eca-size: 2.35rem;
            opacity: 1;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .expandable-card-actions,
        .expandable-card-actions__trigger,
        .expandable-card-actions__bubble {
            transition: none !important;
        }
    }
</style>
