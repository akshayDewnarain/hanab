import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

type Pos = 'top' | 'bottom';
type Align = 'left' | 'right';

type Options = {
    preferredPosition?: Pos; // default 'bottom'
    align?: Align; // default 'left'
    offset?: number; // gap from trigger; default 8
    matchWidth?: boolean; // default true
    viewportPadding?: number; // default 8
};

/** Only one menu open at a time; works even when triggers use @click.stop (no document bubble). */
let activeDropdownClose: (() => void) | null = null;

function elementIsScrollable(el: HTMLElement): boolean {
    const style = window.getComputedStyle(el);
    const ox = style.overflowX;
    const oy = style.overflowY;
    return /(auto|scroll|overlay)/.test(ox) || /(auto|scroll|overlay)/.test(oy);
}

export function useDropdown(onBlur?: () => void, opts: Options = {}) {
    const { preferredPosition = 'bottom', align = 'left', offset = 8, matchWidth = true, viewportPadding = 8 } = opts;
    const isOpen = ref(false);
    const triggerRef = ref<HTMLElement | null>(null);
    const dropdownRef = ref<HTMLElement | null>(null);
    const position = ref<Pos>(preferredPosition);
    const dropdownStyles = ref<Record<string, string>>({ zIndex: '1000' });
    /** Scroll containers between trigger and viewport (teleported fixed menus must reposition on these). */
    const scrollAncestorEls: HTMLElement[] = [];

    function unregisterActive() {
        if (activeDropdownClose === closeThisInstance) {
            activeDropdownClose = null;
        }
    }

    function unbindScrollAncestors() {
        for (const el of scrollAncestorEls) {
            el.removeEventListener('scroll', handleWindowChange);
        }
        scrollAncestorEls.length = 0;
    }

    function bindScrollAncestors() {
        unbindScrollAncestors();
        const start = triggerRef.value;
        if (!start) return;
        let el: HTMLElement | null = start.parentElement;
        while (el) {
            if (elementIsScrollable(el)) {
                el.addEventListener('scroll', handleWindowChange, { passive: true });
                scrollAncestorEls.push(el);
            }
            el = el.parentElement;
        }
    }

    function closeThisInstance() {
        if (!isOpen.value) return;
        unbindScrollAncestors();
        if (onBlur) onBlur();
        isOpen.value = false;
        unregisterActive();
    }

    async function openDropdown() {
        if (isOpen.value) return;
        activeDropdownClose?.();
        activeDropdownClose = closeThisInstance;
        isOpen.value = true;

        await nextTick();
        await new Promise<void>((resolve) => requestAnimationFrame(() => resolve()));

        updatePlacement();
    }

    function closeDropdown() {
        closeThisInstance();
    }

    function toggleDropdown() {
        if (isOpen.value) {
            closeDropdown();
        } else {
            openDropdown();
        }
    }

    function selectOption(option: unknown, emit: (event: 'update:modelValue', value: unknown) => void) {
        if (typeof option === 'object' && option !== null && 'value' in option) {
            emit('update:modelValue', option.value);
        } else {
            emit('update:modelValue', option);
        }

        closeDropdown();
    }

    function handleClickOutside(event: MouseEvent) {
        const target = event.target as Node;

        const clickedInsideTrigger = triggerRef.value?.contains(target);
        const clickedInsideDropdown = dropdownRef.value?.contains(target);

        if (!clickedInsideTrigger && !clickedInsideDropdown) {
            closeDropdown();
        }
    }

    function updatePlacement() {
        const trigger = triggerRef.value;
        const menu = dropdownRef.value;
        if (!trigger || !menu) return;

        // Viewport rects
        const r = trigger.getBoundingClientRect();
        const menuH = menu.offsetHeight || 240;
        const menuW = matchWidth ? r.width : menu.offsetWidth || r.width;

        // Space checks
        const spaceBelow = window.innerHeight - r.bottom - viewportPadding;
        const spaceAbove = r.top - viewportPadding;

        // Decide vertical placement
        const wantBottom = preferredPosition === 'bottom';
        if (wantBottom) {
            position.value = spaceBelow >= menuH || spaceBelow >= spaceAbove ? 'bottom' : 'top';
        } else {
            position.value = spaceAbove >= menuH || spaceAbove >= spaceBelow ? 'top' : 'bottom';
        }

        // Compute fixed coords (viewport-based)
        const top = position.value === 'bottom' ? r.bottom + offset : Math.max(viewportPadding, r.top - menuH - offset);

        const left = align === 'left' ? Math.max(viewportPadding, r.left) : Math.max(viewportPadding, r.right - menuW);

        dropdownStyles.value = {
            position: 'fixed',
            top: `${Math.round(top)}px`,
            left: `${Math.round(left)}px`,
            zIndex: '1000',
            ...(matchWidth && {
                minWidth: `${Math.round(r.width)}px`,
                width: `${Math.round(r.width)}px`,
            }),
        };
    }

    watch(isOpen, async (open: boolean): Promise<void> => {
        await nextTick();
        if (open) {
            await new Promise<void>((resolve) => requestAnimationFrame(() => resolve()));
            bindScrollAncestors();
            const el = dropdownRef.value;
            if (el) {
                el.setAttribute('data-dropdown-open', 'true');
                updatePlacement();
            }
        } else {
            unbindScrollAncestors();
            const el = dropdownRef.value;
            if (el) {
                el.removeAttribute('data-dropdown-open');
            }
        }
    });

    function handleWindowChange() {
        if (isOpen.value) updatePlacement();
    }

    onMounted(() => {
        document.addEventListener('click', handleClickOutside);
        window.addEventListener('resize', handleWindowChange, { passive: true });
        window.addEventListener('scroll', handleWindowChange, { passive: true });
    });

    onUnmounted(() => {
        unbindScrollAncestors();
        unregisterActive();
        document.removeEventListener('click', handleClickOutside);
        window.removeEventListener('resize', handleWindowChange);
        window.removeEventListener('scroll', handleWindowChange);
    });

    return {
        isOpen,
        triggerRef,
        dropdownRef,
        position,
        dropdownStyles,
        toggleDropdown,
        closeDropdown,
        selectOption,
        updatePlacement,
    };
}
