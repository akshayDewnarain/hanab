/**
 * A single segment in the admin breadcrumb trail.
 *
 * @property name - Display label for the crumb.
 * @property path - Router path for the crumb link.
 */
export type BreadcrumbCrumb = {
    name: string;
    path: string;
};
