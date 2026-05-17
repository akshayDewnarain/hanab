/** Group prefix using the last underscore only (e.g. BOAT_CATEGORY_UPDATE → BOAT_CATEGORY). */
export function getGroupKey(permission: string): string {
    const i = permission.lastIndexOf('_');

    return i === -1 ? permission : permission.slice(0, i);
}

/** Action suffix using the last underscore only (e.g. BOAT_CATEGORY_UPDATE → UPDATE). */
export function getActionKey(permission: string): string {
    const i = permission.lastIndexOf('_');

    return i === -1 ? permission : permission.slice(i + 1);
}
