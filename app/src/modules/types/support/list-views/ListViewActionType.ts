import type { ModalProps } from '@/modules/types/support/modals/ModalProps.ts';

/**
 * Optional header action for a list view (e.g. calendar, export, deep link).
 *
 * Used by {@link import('@/composables/useListViewHelper.ts').useListViewHelper useListViewHelper} and {@link import('@/components/list-views/ListView.vue').default ListView} for `customActions`.
 *
 * Exactly one of `to`, `modal`, or `action` should be set for a meaningful click handler.
 *
 * @property label - i18n key or literal for the button label.
 * @property icon - Iconify icon id (e.g. `material-symbols:calendar-month`).
 * @property to - Route path to push when clicked.
 * @property modal - Modal configuration opened via the modal service when clicked.
 * @property action - Arbitrary callback when clicked.
 */
export interface ListViewActionType {
    label: string;
    icon: string;
    to?: string;
    modal?: ModalProps;
    action?: () => void;
}
