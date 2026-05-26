<template>
    <section
        class="col-span-12 w-full overflow-hidden rounded-xl border border-slate-200/90 bg-gradient-to-br from-white via-white to-slate-50/80 shadow-md"
    >
        <!-- Header -->
        <div
            class="border-b border-slate-100 bg-gradient-to-r from-[var(--color-background)]/10 via-white to-[var(--color-primary)]/5 px-5 py-4"
        >
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex gap-3">
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[var(--color-background)] text-white shadow-md"
                    >
                        <Icon class="size-6" icon="material-symbols:palette-rounded" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900">Theme playground</h2>
                        <p class="mt-0.5 max-w-xl text-sm text-slate-600">
                            Tweak the live design tokens below — like a mini design system lab. Every change updates
                            the app instantly and is saved for your next visit.
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        class="inline-flex !min-w-0 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:border-[var(--color-primary)] hover:text-[var(--color-primary)]"
                        type="button"
                        @click="onExport"
                    >
                        <Icon class="size-[18px]" icon="material-symbols:download-rounded" />
                        Export
                    </button>
                    <button
                        class="inline-flex !min-w-0 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:border-[var(--color-primary)] hover:text-[var(--color-primary)]"
                        type="button"
                        @click="triggerImportFile"
                    >
                        <Icon class="size-[18px]" icon="material-symbols:upload-rounded" />
                        Import file
                    </button>
                    <button
                        class="button-primary-outline inline-flex !min-w-0 items-center gap-2 px-4 py-2 text-sm"
                        type="button"
                        @click="onReset"
                    >
                        <Icon class="size-[18px]" icon="material-symbols:restart-alt-rounded" />
                        Reset all
                    </button>
                </div>
            </div>

            <input
                ref="importFileRef"
                accept="application/json,.json"
                class="sr-only"
                type="file"
                @change="onImportFileChange"
            />

            <div
                v-if="importMessage"
                class="mt-4 flex gap-2.5 rounded-lg px-3.5 py-3 text-sm"
                :class="
                    importMessage.type === 'success'
                        ? 'border border-emerald-200/80 bg-emerald-50/90 text-emerald-950'
                        : 'border border-red-200/80 bg-red-50/90 text-red-950'
                "
            >
                <Icon
                    class="mt-0.5 size-5 shrink-0"
                    :icon="
                        importMessage.type === 'success'
                            ? 'material-symbols:check-circle-outline-rounded'
                            : 'material-symbols:error-outline-rounded'
                    "
                />
                <p>{{ importMessage.text }}</p>
            </div>

            <div class="mt-4 rounded-lg border border-slate-200/90 bg-white p-3 shadow-sm">
                <button
                    class="flex w-full cursor-pointer items-center justify-between gap-2 text-left text-sm font-medium text-slate-800"
                    type="button"
                    @click="showPasteImport = !showPasteImport"
                >
                    <span class="inline-flex items-center gap-2">
                        <Icon class="size-[18px] text-slate-500" icon="material-symbols:content-paste-rounded" />
                        Import from pasted JSON
                    </span>
                    <Icon
                        class="size-5 text-slate-400 transition"
                        :class="showPasteImport ? 'rotate-180' : ''"
                        icon="material-symbols:keyboard-arrow-down-rounded"
                    />
                </button>
                <div v-show="showPasteImport" class="mt-3 space-y-2">
                    <textarea
                        v-model="pasteJson"
                        class="min-h-[120px] w-full resize-y rounded-lg border border-slate-200 bg-white px-3 py-2 font-mono text-xs text-slate-800 shadow-sm focus:border-[var(--color-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/20"
                        placeholder='{ "overrides": { "--color-primary": "#006a7c" } }'
                        spellcheck="false"
                    />
                    <button
                        class="button-primary inline-flex !min-w-0 items-center gap-2 px-4 py-2 text-sm"
                        type="button"
                        @click="onImportPaste"
                    >
                        <Icon class="size-[18px]" icon="material-symbols:play-arrow-rounded" />
                        Apply pasted config
                    </button>
                </div>
            </div>

            <div
                class="mt-4 flex gap-2.5 rounded-lg border border-sky-200/80 bg-sky-50/90 px-3.5 py-3 text-sm text-sky-950"
            >
                <Icon class="mt-0.5 size-5 shrink-0 text-sky-600" icon="material-symbols:tips-and-updates-outline-rounded" />
                <p>
                    <span class="font-semibold">Pro tip:</span>
                    Use the color swatch for quick hex picks, or paste any valid CSS color
                    (<code class="rounded bg-white/80 px-1 text-xs">#e63223</code>,
                    <code class="rounded bg-white/80 px-1 text-xs">rgb()</code>,
                    <code class="rounded bg-white/80 px-1 text-xs">rgba()</code>). Open the employee list in another
                    tab to see your theme in the wild.
                </p>
            </div>
        </div>

        <div class="grid gap-6 p-5 lg:grid-cols-[1fr_minmax(280px,340px)]">
            <!-- Token editor -->
            <div class="space-y-5">
                <section
                    v-for="group in THEME_TOKEN_GROUPS"
                    :key="group.id"
                    class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm"
                >
                    <div class="flex items-start gap-3 border-b border-slate-100 bg-slate-50/60 px-4 py-3">
                        <div
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-white text-[var(--color-primary)] shadow-sm ring-1 ring-slate-200/80"
                        >
                            <Icon class="size-5" :icon="group.icon" />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">{{ group.title }}</h3>
                            <p class="mt-0.5 text-xs leading-relaxed text-slate-500">{{ group.description }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2">
                        <div
                            v-for="token in tokensForGroup(group.id)"
                            :key="token"
                            class="flex flex-col rounded-lg border border-slate-200/90 bg-white p-3 shadow-sm transition-shadow hover:shadow-md"
                            :class="
                                themeStore.isTokenOverridden(token)
                                    ? 'ring-1 ring-[var(--color-primary)]/25'
                                    : ''
                            "
                        >
                            <div class="mb-2 flex items-center gap-2">
                                <Icon
                                    class="size-4 shrink-0 text-slate-400"
                                    :icon="THEME_TOKEN_META[token].icon"
                                />
                                <div class="min-w-0 flex-1">
                                    <label
                                        :for="`theme-${token}`"
                                        class="block text-sm font-medium leading-tight text-slate-800"
                                    >
                                        {{ THEME_TOKEN_META[token].label }}
                                    </label>
                                    <p class="truncate font-mono text-[10px] text-slate-400">{{ token }}</p>
                                </div>
                                <button
                                    type="button"
                                    class="inline-flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-slate-200 disabled:hover:text-slate-500"
                                    :disabled="!themeStore.isTokenOverridden(token)"
                                    :title="
                                        themeStore.isTokenOverridden(token)
                                            ? `Reset ${THEME_TOKEN_META[token].label} to default`
                                            : 'Already using the default value'
                                    "
                                    @click="onResetToken(token)"
                                >
                                    <Icon class="size-4" icon="material-symbols:undo-rounded" />
                                </button>
                            </div>
                            <p class="mb-2 line-clamp-2 min-h-[2.5rem] text-xs leading-relaxed text-slate-500">
                                {{ THEME_TOKEN_META[token].hint }}
                            </p>
                            <div class="mt-auto flex items-center gap-2">
                                <input
                                    :id="`theme-${token}`"
                                    :value="themeStore.values[token]"
                                    class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-2.5 py-2 font-mono text-xs text-slate-800 shadow-sm transition focus:border-[var(--color-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/20 sm:text-sm"
                                    type="text"
                                    spellcheck="false"
                                    :placeholder="THEME_TOKEN_DEFAULTS[token]"
                                    @input="onTextInput(token, ($event.target as HTMLInputElement).value)"
                                />
                                <label
                                    class="relative h-10 w-14 shrink-0 cursor-pointer overflow-hidden rounded-lg border border-slate-200/90 shadow-sm ring-1 ring-inset ring-black/5 transition hover:ring-2 hover:ring-[var(--color-primary)]/40"
                                    :style="{ background: themeStore.values[token] }"
                                    :title="`Pick color for ${THEME_TOKEN_META[token].label}`"
                                >
                                    <input
                                        class="absolute inset-0 size-full cursor-pointer opacity-0"
                                        type="color"
                                        :value="pickerHex(token)"
                                        @input="onPickerChange(token, ($event.target as HTMLInputElement).value)"
                                    />
                                </label>
                            </div>
                            <p
                                v-if="errors[token]"
                                class="mt-1.5 flex items-center gap-1 text-xs text-[var(--color-error)]"
                            >
                                <Icon class="size-3.5 shrink-0" icon="material-symbols:info-outline-rounded" />
                                {{ errors[token] }}
                            </p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Live preview -->
            <aside class="lg:sticky lg:top-4 lg:self-start">
                <div class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm">
                    <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50/60 px-4 py-3">
                        <Icon class="size-5 text-[var(--color-primary)]" icon="material-symbols:visibility-rounded" />
                        <h3 class="text-sm font-semibold text-slate-900">Live preview</h3>
                    </div>

                    <div class="space-y-4 p-4">
                        <p class="text-xs text-slate-500">
                            These samples use the same utility classes as production UI.
                        </p>

                        <div
                            class="w-full max-w-[11rem] rounded-lg bg-[var(--color-sidebar-background)] p-2 shadow-sm"
                        >
                            <p class="mb-2 text-[10px] font-medium uppercase tracking-wide text-[var(--color-sidebar-item-text)]/70">
                                Sidebar
                            </p>
                            <div
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 text-[var(--color-sidebar-item-text)] hover:bg-[var(--color-sidebar-item-highlight-dark)]"
                            >
                                <Icon
                                    class="size-5 shrink-0 text-[var(--color-sidebar-icon)]"
                                    icon="material-symbols:group-rounded"
                                />
                                <span class="text-xs font-semibold">Menu item</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button class="button-primary inline-flex !min-w-0 items-center gap-1.5 px-4 py-2 text-sm" type="button">
                                <Icon class="size-4" icon="material-symbols:check-rounded" />
                                Primary
                            </button>
                            <button
                                class="button-primary-outline inline-flex !min-w-0 items-center gap-1.5 px-4 py-2 text-sm"
                                type="button"
                            >
                                <Icon class="size-4" icon="material-symbols:open-in-new-rounded" />
                                Outline
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span class="badge-primary inline-flex items-center gap-1">
                                <Icon class="size-3.5" icon="material-symbols:verified-rounded" />
                                Primary badge
                            </span>
                            <span class="badge-secondary">Secondary</span>
                        </div>

                        <div
                            class="rounded-lg border border-[var(--color-primary)]/30 bg-[var(--color-highlight-light)] p-4"
                        >
                            <p class="flex items-center gap-1.5 text-sm font-semibold text-[var(--color-primary)]">
                                <Icon class="size-4" icon="material-symbols:article-outline-rounded" />
                                Themed card
                            </p>
                            <p class="mt-2 text-sm leading-relaxed text-[var(--color-text)]">
                                This is how body text feels with your current
                                <span class="font-medium text-[var(--color-primary)]">primary</span>
                                and surface tokens.
                            </p>
                        </div>

                        <div class="grid grid-cols-3 gap-2 text-center text-[11px] font-medium text-white">
                            <span
                                class="flex flex-col items-center gap-1 rounded-lg px-2 py-2 shadow-sm"
                                :style="{ background: themeStore.values['--color-error'] }"
                            >
                                <Icon class="size-4" icon="material-symbols:close-rounded" />
                                Error
                            </span>
                            <span
                                class="flex flex-col items-center gap-1 rounded-lg px-2 py-2 shadow-sm"
                                :style="{ background: themeStore.values['--color-warning'] }"
                            >
                                <Icon class="size-4" icon="material-symbols:priority-high-rounded" />
                                Warning
                            </span>
                            <span
                                class="flex flex-col items-center gap-1 rounded-lg px-2 py-2 shadow-sm"
                                :style="{ background: themeStore.values['--color-success'] }"
                            >
                                <Icon class="size-4" icon="material-symbols:done-all-rounded" />
                                Success
                            </span>
                        </div>

                        <div
                            class="flex items-center gap-2 rounded-lg border border-dashed border-slate-200 bg-white px-3 py-2.5"
                        >
                            <Icon class="size-4 shrink-0 text-slate-400" icon="material-symbols:save-outline-rounded" />
                            <span class="text-xs text-slate-500">
                                <template v-if="themeStore.hasOverrides">
                                    Custom theme saved — refresh-safe.
                                </template>
                                <template v-else>Using defaults from global.css</template>
                            </span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </section>
</template>

<script lang="ts" setup>
    import { reactive, ref } from 'vue';
    import { Icon } from '@iconify/vue';
    import { useThemeStore } from '@/stores/theme';
    import { useToastService } from '@/composables/useToastService';
    import {
        EDITABLE_THEME_TOKENS,
        THEME_TOKEN_DEFAULTS,
        THEME_TOKEN_GROUPS,
        THEME_TOKEN_META,
        tokensForGroup,
        type ThemeToken,
    } from '@/modules/support/theme/themeTokens';

    defineOptions({ name: 'ThemeBuilder' });

    const themeStore = useThemeStore();
    const toast = useToastService();
    const errors = reactive<Partial<Record<ThemeToken, string>>>({});
    const importFileRef = ref<HTMLInputElement | null>(null);
    const showPasteImport = ref(false);
    const pasteJson = ref('');
    const importMessage = ref<{ type: 'success' | 'error'; text: string } | null>(null);

    function exportFilename(): string {
        const stamp = new Date().toISOString().slice(0, 10);
        return `hanab-theme-${stamp}.json`;
    }

    function onExport(): void {
        const json = themeStore.exportConfigJson();
        const blob = new Blob([json], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = exportFilename();
        link.click();
        URL.revokeObjectURL(url);
        toast.success(`Theme exported as ${exportFilename()}`);
    }

    function triggerImportFile(): void {
        importFileRef.value?.click();
    }

    function handleImportResult(result: ReturnType<typeof themeStore.importConfigJson>): void {
        if (!result.ok) {
            importMessage.value = { type: 'error', text: result.error };
            toast.error(result.error);
            return;
        }

        for (const key of EDITABLE_THEME_TOKENS) {
            delete errors[key];
        }

        const count = result.importedCount;
        const text = `Imported ${count} token${count === 1 ? '' : 's'} — theme applied and saved.`;
        importMessage.value = { type: 'success', text };
        toast.success(text);
    }

    function onImportFileChange(event: Event): void {
        const input = event.target as HTMLInputElement;
        const file = input.files?.[0];
        if (!file) {
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            const text = typeof reader.result === 'string' ? reader.result : '';
            handleImportResult(themeStore.importConfigJson(text));
            input.value = '';
        };
        reader.onerror = () => {
            importMessage.value = { type: 'error', text: 'Could not read the selected file.' };
            input.value = '';
        };
        reader.readAsText(file);
    }

    function onImportPaste(): void {
        if (!pasteJson.value.trim()) {
            importMessage.value = { type: 'error', text: 'Paste a JSON theme configuration first.' };
            return;
        }
        handleImportResult(themeStore.importConfigJson(pasteJson.value));
    }

    function pickerHex(token: ThemeToken): string {
        const value = themeStore.values[token];
        if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
            return value;
        }
        if (/^#[0-9A-Fa-f]{3}$/.test(value)) {
            const [, r, g, b] = value;
            return `#${r}${r}${g}${g}${b}${b}`;
        }
        return '#000000';
    }

    function applyToken(token: ThemeToken, value: string): void {
        if (!themeStore.setTokenValue(token, value)) {
            errors[token] = 'That doesn’t look like a valid CSS color — try #hex or rgba().';
            return;
        }
        delete errors[token];
    }

    function onTextInput(token: ThemeToken, value: string): void {
        applyToken(token, value);
    }

    function onPickerChange(token: ThemeToken, value: string): void {
        applyToken(token, value);
    }

    function onResetToken(token: ThemeToken): void {
        themeStore.resetTokenValue(token);
        delete errors[token];
    }

    function onReset(): void {
        themeStore.resetTheme();
        for (const key of EDITABLE_THEME_TOKENS) {
            delete errors[key];
        }
    }
</script>
