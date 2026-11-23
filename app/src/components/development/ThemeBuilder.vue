<template>
    <ThemeBuilder
        v-model:values="themeStore.values"
        class="col-span-12"
        :file-name="fileName"
        :labels="labels"
        @change="onChange"
        @reset="onReset"
        @reset-token="onResetToken"
        @import="onImport"
        @export="onExport"
    />
</template>

<script lang="ts" setup>
    import { ThemeBuilder, type ThemeImportResult, type ThemeToken } from '@freelance/vue-ui';
    import { useThemeStore } from '@/stores/theme';
    import { useToastService } from '@/composables/useToastService';

    defineOptions({ name: 'ThemeBuilder' });

    const themeStore = useThemeStore();
    const toast = useToastService();
    const fileName = `hanab-theme-${new Date().toISOString().slice(0, 10)}.json`;
    const labels = {
        description: 'Edit the design tokens below. Each valid change is applied to this app and saved in the browser.',
        imported: 'Imported {count} tokens — theme applied and saved.',
    };

    let ignoreNextChange = false;

    function onChange(token: ThemeToken, value: string): void {
        if (ignoreNextChange) {
            ignoreNextChange = false;
            return;
        }
        themeStore.setTokenValue(token, value);
    }

    function onResetToken(token: ThemeToken): void {
        ignoreNextChange = true;
        themeStore.resetTokenValue(token);
    }

    function onReset(): void {
        themeStore.resetTheme();
    }

    function onImport(result: ThemeImportResult): void {
        if (!result.ok) {
            toast.error(result.error);
            return;
        }
        themeStore.applyImportedTheme(result);
        toast.success(
            `Imported ${result.importedCount} token${result.importedCount === 1 ? '' : 's'} — theme applied and saved.`,
        );
    }

    function onExport(payload: { fileName: string }): void {
        toast.success(`Theme exported as ${payload.fileName}`);
    }
</script>
