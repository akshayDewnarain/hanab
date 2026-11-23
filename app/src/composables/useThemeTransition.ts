import { nextTick } from 'vue';

/**
 * Mask geometry, matched to the theme-wave keyframes in global.css.
 * The image is 500 units square and shown at 500%, so the viewport is a
 * 100-unit window. White sits on the bottom-left of one gentle sine wave.
 * The window starts with that crest just entering the bottom-left corner
 * and ends inside the solid white, after the crest has left the top-right.
 * Positions live in global.css and must stay on this single non-repeating image.
 */
const WAVE_VIEWBOX = 500;
const WAVE_AMPLITUDE = 7;
const WAVE_CYCLES = 2.5;
const WAVE_SAMPLES = 96;

const TRANSITION_LOCK = 'theme-transitioning';

interface ThemeViewTransition {
    finished: Promise<void>;
}

type ViewTransitionDocument = Document & {
    startViewTransition?: (callback: () => void | Promise<void>) => ThemeViewTransition;
};

let waveMask: string | null = null;
let pendingApply: (() => void) | null = null;
let pumping = false;

function prefersReducedMotion(): boolean {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function viewTransitionDocument(): ViewTransitionDocument | null {
    if (typeof document === 'undefined') return null;
    return document;
}

function canAnimate(): boolean {
    const doc = viewTransitionDocument();
    return !!doc && typeof doc.startViewTransition === 'function' && !prefersReducedMotion();
}

/** One diagonal sine boundary. The blur is only wide enough to soften the crest. */
function buildWaveMask(): string {
    const normalX = Math.SQRT1_2;
    const normalY = -Math.SQRT1_2;
    const points: string[] = [];

    for (let index = 0; index <= WAVE_SAMPLES; index += 1) {
        const progress = index / WAVE_SAMPLES;
        const base = -60 + progress * (WAVE_VIEWBOX + 120);
        const wave = Math.sin(progress * WAVE_CYCLES * Math.PI * 2);
        const x = base + wave * WAVE_AMPLITUDE * normalX;
        const y = base + wave * WAVE_AMPLITUDE * normalY;
        points.push(`${x.toFixed(1)},${y.toFixed(1)}`);
    }

    const path = [
        `M ${points.join(' L ')}`,
        `L ${WAVE_VIEWBOX + 80},${WAVE_VIEWBOX + 140}`,
        `L -80,${WAVE_VIEWBOX + 140}`,
        'L -80,-80 Z',
    ].join(' ');

    const svg = [
        `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${WAVE_VIEWBOX} ${WAVE_VIEWBOX}">`,
        '<defs><filter id="feather" x="-20%" y="-20%" width="140%" height="140%">',
        '<feGaussianBlur stdDeviation="3"/></filter></defs>',
        `<path filter="url(#feather)" fill="white" d="${path}"/>`,
        '</svg>',
    ].join('');

    return `url("data:image/svg+xml,${encodeURIComponent(svg)}")`;
}

/** Commit an in-flight slide so the page snapshot cannot cancel it and snap the thumb back. */
function commitCssTransitions(): void {
    for (const animation of document.getAnimations()) {
        if (typeof CSSTransition !== 'undefined' && animation instanceof CSSTransition) animation.finish();
    }
}

function ensureWaveMask(): void {
    if (!waveMask) waveMask = buildWaveMask();
    document.documentElement.style.setProperty('--theme-wave-mask', waveMask);
}

function lockTransitions(): void {
    // Temporarily off so the theme slider can keep its own animation.
}

function unlockTransitions(): void {
    document.documentElement.classList.remove(TRANSITION_LOCK);
}

/** Apply immediately when the wave cannot run, without a separate color fade. */
function applySettled(apply: () => void): void {
    lockTransitions();
    apply();
    window.requestAnimationFrame(() => {
        window.requestAnimationFrame(unlockTransitions);
    });
}

async function play(apply: () => void): Promise<void> {
    const doc = viewTransitionDocument();
    const startViewTransition = doc?.startViewTransition;
    if (!doc || typeof startViewTransition !== 'function') {
        applySettled(apply);
        await nextTick();
        return;
    }

    commitCssTransitions();
    ensureWaveMask();

    try {
        const transition = startViewTransition.call(doc, async () => {
            lockTransitions();
            apply();
            await nextTick();
        });
        await transition.finished.catch(() => undefined);
        unlockTransitions();
    } catch {
        applySettled(apply);
        await nextTick();
    }
}

async function pump(): Promise<void> {
    try {
        while (pendingApply) {
            const apply = pendingApply;
            pendingApply = null;
            await play(apply);
        }
    } finally {
        pumping = false;
        if (pendingApply) {
            pumping = true;
            void pump();
        }
    }
}

/**
 * Capture the current page, run `apply` once, then reveal the new page with the wave.
 * A selection made during a wave waits and plays after this one, instead of cutting it off.
 */
export function runThemeTransition(apply: () => void): void {
    if (!canAnimate()) {
        applySettled(apply);
        return;
    }

    pendingApply = apply;
    if (pumping) return;

    pumping = true;
    void pump();
}

export function useThemeTransition() {
    return { runThemeTransition };
}
