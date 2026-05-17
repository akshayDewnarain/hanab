/**
 * Props for {@link import('@/components/navigation/SettingSideBarItem.vue').default SettingSideBarItem}.
 *
 * @property label - i18n key for the settings sub-navigation label.
 * @property to - Vue Router location string for the link target.
 * @property icon - Iconify icon id (reserved for future use; label-only layout today).
 */
export interface SettingsSidebarItemProps {
    label: string;
    to: string;
    icon: string;
}
