import type { SidebarItemInterface } from '@/modules/types/support/navigation/SidebarItemInterface';

/**
 * Props for {@link import('@/components/navigation/SidebarItem.vue').default SidebarItem}.
 *
 * @property collapsed - When true, the sidebar is expanded and item labels are visible.
 */
export interface SidebarItemComponentProps extends SidebarItemInterface {
    collapsed: boolean;
}
