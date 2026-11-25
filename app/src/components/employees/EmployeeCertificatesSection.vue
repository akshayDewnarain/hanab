<template>
    <section class="rounded-xl border border-list-border bg-list-surface px-5 py-4 shadow-sm">
        <h2 class="m-0 flex items-center gap-2 text-base font-semibold text-list-text">
            <Icon class="size-5 text-list-text-muted" icon="material-symbols:workspace-premium-outline" />
            {{ t('EMPLOYEE_PROFILE_CERTIFICATES') }}
        </h2>
        <div v-if="certificates.length" class="mt-3 flex flex-wrap gap-2">
            <ColorLabel
                v-for="certificate in visibleCertificates"
                :key="certificate.id"
                pressable
                size="md"
                :label="certificateLabel(certificate)"
                :background-color="certificate.label_background_color"
                :text-color="certificate.label_text_color"
                :border-color="certificate.label_border_color"
                :description="certificateDetail(certificate)"
                @select="openColleagues(certificate.id)"
            />
        </div>
        <button
            v-if="certificates.length > limit"
            type="button"
            class="mt-3 cursor-pointer text-sm font-medium text-emphasis"
            @click="expanded = !expanded"
        >
            {{ expanded ? t('EMPLOYEE_PROFILE_LESS') : t('EMPLOYEE_PROFILE_MORE', { count: certificates.length - limit }) }}
        </button>
        <p v-if="!certificates.length" class="m-0 mt-3 max-w-prose text-sm text-list-text-muted">
            {{ t('EMPLOYEE_NO_CERTIFICATES') }}
            <router-link
                v-if="canEdit"
                class="ml-2 font-medium text-emphasis"
                :to="{ name: 'admin-employees-edit', params: { id: String(employeeId) } }"
            >
                {{ t('EMPLOYEE_ADD_CERTIFICATE') }}
            </router-link>
        </p>
    </section>
</template>

<script lang="ts" setup>
    import { computed, ref } from 'vue';
    import { useRouter } from 'vue-router';
    import { Icon } from '@iconify/vue';
    import { useI18n } from 'vue-i18n';
    import { ColorLabel } from '@freelance/vue-ui';
    import type { CertificateRecord } from '@/modules/types/support/models/CertificateRecord.ts';

    defineOptions({ name: 'EmployeeCertificatesSection' });

    const props = defineProps<{
        employeeId: number;
        canEdit: boolean;
        certificates: Array<CertificateRecord & { expires_at?: string | null; certificate_number?: string | null }>;
    }>();

    const { t } = useI18n();
    const router = useRouter();
    const limit = 8;
    const expanded = ref(false);
    const visibleCertificates = computed(() => (expanded.value ? props.certificates : props.certificates.slice(0, limit)));

    function certificateLabel(certificate: CertificateRecord): string {
        return certificate.name.trim() || certificate.code?.trim() || '';
    }

    function certificateDetail(certificate: CertificateRecord & { expires_at?: string | null; certificate_number?: string | null }): string {
        const code = certificate.code?.trim() ?? '';
        const name = certificate.name.trim();
        const lines = [code && code.toLowerCase() !== name.toLowerCase() ? code : '', certificate.description?.trim() ?? ''];
        const number = certificate.certificate_number?.trim() ?? '';
        if (number) lines.push(t('EMPLOYEE_CERTIFICATE_NUMBER', { number }));
        if (certificate.expires_at) lines.push(t('EMPLOYEE_EXPIRES_ON', { date: certificate.expires_at }));
        const synonyms = certificate.synonyms?.trim() ?? '';
        if (synonyms) lines.push(t('EMPLOYEE_ALSO_DISCOVERABLE', { terms: synonyms }));
        return lines.filter(Boolean).join('\n');
    }

    function openColleagues(certificateId: number): void {
        void router.push({
            name: 'admin-employees-overview',
            query: { page: '1', 'filter[certificate_ids]': String(certificateId) },
        });
    }
</script>
