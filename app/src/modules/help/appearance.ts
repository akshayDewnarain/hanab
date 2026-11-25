export type HelpAccent = 'blue' | 'teal' | 'amber' | 'purple' | 'slate';

const ACCENT: Record<string, HelpAccent> = {
    'aan-de-slag': 'blue',
    collegas: 'teal',
    medewerkersbeheer: 'teal',
    organogram: 'amber',
    'importeren-en-exporteren': 'slate',
    'vaardigheden-en-certificaten': 'purple',
    berichten: 'blue',
    gebruikersbeheer: 'slate',
    instellingen: 'slate',
};

const TILE: Record<HelpAccent, string> = {
    blue: 'bg-primary-light text-emphasis',
    teal: 'bg-teal-50 text-teal-800 dark:bg-teal-950/40 dark:text-teal-100',
    amber: 'bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-100',
    purple: 'bg-violet-50 text-violet-800 dark:bg-violet-950/40 dark:text-violet-100',
    slate: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-100',
};

const BAR: Record<HelpAccent, string> = {
    blue: 'bg-primary',
    teal: 'bg-teal-600',
    amber: 'bg-amber-500',
    purple: 'bg-violet-600',
    slate: 'bg-slate-400',
};

export function helpAccent(slug: string | null | undefined): HelpAccent {
    if (!slug) return 'slate';
    return ACCENT[slug] ?? 'slate';
}

export function helpTileClass(slug: string | null | undefined): string {
    return TILE[helpAccent(slug)];
}

export function helpBarClass(slug: string | null | undefined): string {
    return BAR[helpAccent(slug)];
}

const RECENT_MS = 14 * 24 * 60 * 60 * 1000;

export function helpIsRecent(value: string | null | undefined, now = Date.now()): boolean {
    if (!value) return false;
    const time = new Date(value).getTime();
    if (Number.isNaN(time)) return false;
    const age = now - time;
    return age >= 0 && age <= RECENT_MS;
}
