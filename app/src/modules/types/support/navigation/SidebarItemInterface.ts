/**
 * Configuration for a primary or secondary entry in the admin sidebar.
 *
 * Used by {@link import('@/components/navigation/Sidebar.vue').default Sidebar} to build
 * navigation links with optional permission gating and route-active rules.
 *
 * @property label - i18n key for the item label.
 * @property icon - Iconify icon id for the item.
 * @property to - Vue Router location string for the link target.
 * @property permission - Exact permission name. A super-admin passes every check.
 * @property permissionEntity - Resource key for `view {entity}` (for example `skills`). Omitted items stay visible.
 * @property visible - Optional direct visibility toggle after permission resolution.
 * @property activeExact - When true, active only when the current path exactly matches resolved `to` (normalized trailing slashes).
 * @property inactiveWhenPathStartsWith - When set, never active if the current path starts with this prefix.
 */
export interface SidebarItemInterface {
    label: string;
    icon: string;
    to: string;
    permission?: string;
    permissionEntity?: string;
    visible?: boolean;
    activeExact?: boolean;
    inactiveWhenPathStartsWith?: string;
}
