/**
 * Configuration for a primary or secondary entry in the admin sidebar.
 *
 * Used by {@link import('@/components/navigation/Sidebar.vue').default Sidebar} to build
 * navigation links with optional permission gating and route-active rules.
 *
 * @property label - i18n key for the item label.
 * @property icon - Iconify icon id for the item.
 * @property to - Vue Router location string for the link target.
 * @property permissionEntity - Entity key for `canViewEntity` checks (e.g. DRINK, RESERVATION). Omitted items stay visible by default.
 * @property visible - Optional direct visibility toggle after permission resolution.
 * @property activeExact - When true, active only when the current path exactly matches resolved `to` (normalized trailing slashes).
 * @property inactiveWhenPathStartsWith - When set, never active if the current path starts with this prefix (e.g. exclude calendar from Reservations highlight).
 */
export interface SidebarItemInterface {
    label: string;
    icon: string;
    to: string;
    permissionEntity?: string;
    visible?: boolean;
    activeExact?: boolean;
    inactiveWhenPathStartsWith?: string;
}
