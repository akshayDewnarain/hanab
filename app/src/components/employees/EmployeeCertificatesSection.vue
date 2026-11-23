<template>
    <section class="rounded-xl border border-list-border bg-list-surface px-5 py-4 shadow-sm">
        <h2 class="m-0 flex items-center gap-2 text-sm font-semibold text-list-text">
            <Icon class="size-4 text-list-text-muted" icon="material-symbols:workspace-premium-outline" />
            {{ t('EMPLOYEE_PROFILE_CERTIFICATES') }}
        </h2>
        <div v-if="certificates.length" class="mt-3 flex flex-wrap gap-2">
            <ColorLabel
                v-for="certificate in certificates"
                :key="certificate.id"
                :label="certificateLabel(certificate)"
                :background-color="certificate.label_background_color"
                :text-color="certificate.label_text_color"
                :border-color="certificate.label_border_color"
                :description="certificateDetail(certificate)"
            />
        </div>
        <p v-else class="m-0 mt-3 text-sm text-list-text-muted">{{ t('EMPLOYEE_NO_CERTIFICATES') }}</p>
    </section>
</template>

<script lang="ts" setup>
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import { ColorLabel } from '@freelance/vue-ui';
    import type { CertificateRecord } from '@/modules/types/support/models/CertificateRecord.ts';

    defineOptions({ name: 'EmployeeCertificatesSection' });

    defineProps<{
        certificates: Array<CertificateRecord & { expires_at?: string | null }>;
    }>();

    const { t } = useI18n();

    function certificateLabel(certificate: CertificateRecord): string {
        const code = certificate.code?.trim() ?? '';
        return code || certificate.name;
    }

    function certificateDetail(certificate: CertificateRecord & { expires_at?: string | null }): string {
        const code = certificate.code?.trim() ?? '';
        const name = certificate.name.trim();
        const lines = [code && code.toLowerCase() !== name.toLowerCase() ? name : '', certificate.description?.trim() ?? ''];
        if (certificate.expires_at) lines.push(t('EMPLOYEE_EXPIRES_ON', { date: certificate.expires_at }));
        const synonyms = certificate.synonyms?.trim() ?? '';
        if (synonyms) lines.push(t('EMPLOYEE_ALSO_DISCOVERABLE', { terms: synonyms }));
        return lines.filter(Boolean).join('\n');
    }
</script>
