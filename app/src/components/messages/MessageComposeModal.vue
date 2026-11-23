<template>
    <section class="flex max-h-[80vh] flex-col bg-list-surface text-list-text">
        <header class="flex items-start justify-between gap-3 border-b border-list-border px-4 py-3">
            <div>
                <h2 class="m-0 text-base font-semibold">{{ editing ? t('MESSAGE_COMPOSE_EDIT') : t('MESSAGE_COMPOSE_TITLE') }}</h2>
                <p class="m-0 mt-1 text-sm text-list-text-muted">{{ t('MESSAGE_COMPOSE_INTRO') }}</p>
            </div>
            <button
                type="button"
                class="inline-flex size-8 cursor-pointer items-center justify-center rounded-lg text-list-text-muted hover:bg-list-row-hover"
                :aria-label="t('MESSAGES_CLOSE')"
                @click="dismissModal"
            >
                <Icon icon="material-symbols:close-rounded" class="size-5" />
            </button>
        </header>

        <div class="overflow-y-auto px-4 py-3">
            <div v-if="previewing" class="grid gap-2">
                <p class="m-0 text-xs font-semibold uppercase tracking-wide text-list-text-muted">{{ t('MESSAGE_CARD_COMPANY') }}</p>
                <h3 class="m-0 text-lg font-semibold">{{ title || t('MESSAGE_COMPOSE_TITLE_PLACEHOLDER') }}</h3>
                <UiRichTextContent :document="document" />
            </div>
            <div v-else class="grid gap-3">
                <label class="grid gap-1">
                    <span class="text-sm font-medium">{{ t('MESSAGE_COMPOSE_TITLE_LABEL') }}</span>
                    <input
                        v-model="title"
                        maxlength="255"
                        class="h-9 rounded-lg border border-list-border bg-transparent px-3 text-sm text-list-text focus-visible:outline-2 focus-visible:outline-primary"
                        :placeholder="t('MESSAGE_COMPOSE_TITLE_PLACEHOLDER')"
                    />
                </label>
                <div class="grid gap-1">
                    <span class="text-sm font-medium">{{ t('MESSAGE_COMPOSE_CONTENT') }}</span>
                    <UiRichTextEditor
                        v-model="document"
                        min-height="8rem"
                        :placeholder="t('MESSAGE_COMPOSE_PLACEHOLDER')"
                        :labels="editorLabels"
                        :error="invalid ? t('MESSAGE_COMPOSE_REQUIRED') : ''"
                        :max-length="5000"
                    />
                </div>
            </div>
        </div>

        <footer class="flex flex-wrap items-center justify-between gap-2 border-t border-list-border px-4 py-3">
            <button
                type="button"
                class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-full border border-list-border px-4 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="busy"
                @click="previewing = !previewing"
            >
                <Icon :icon="previewing ? 'material-symbols:edit-outline' : 'material-symbols:visibility-outline'" class="size-4" />
                {{ previewing ? t('MESSAGE_COMPOSE_EDIT_AGAIN') : t('MESSAGE_COMPOSE_PREVIEW') }}
            </button>
            <div class="flex flex-wrap gap-2">
                <button
                    v-if="showDraft"
                    type="button"
                    class="inline-flex h-10 cursor-pointer items-center rounded-full px-4 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                    :class="canPublish ? 'border border-list-border' : 'bg-primary text-on-brand'"
                    :disabled="busy"
                    @click="save(false)"
                >
                    {{ t('MESSAGE_COMPOSE_DRAFT') }}
                </button>
                <button
                    v-if="published || canPublish"
                    type="button"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-full bg-primary px-4 text-sm font-semibold text-on-brand disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="busy"
                    @click="published ? save(false) : save(true)"
                >
                    <Icon v-if="!published" icon="material-symbols:send-outline" class="size-4" />
                    {{ published ? t('MESSAGE_COMPOSE_SAVE') : t('MESSAGE_COMPOSE_PUBLISH') }}
                </button>
            </div>
        </footer>
    </section>
</template>

<script lang="ts" setup>
    import type { FeedMessage } from '@/composables/useMessages';
    import { useMessages } from '@/composables/useMessages';
    import { useModalService } from '@/composables/useModalService';
    import { useToastService } from '@/composables/useToastService';
    import ConfirmationModal from '@/components/modals/content-modals/ConfirmationModal.vue';
    import { ModalActionTarget } from '@/modules/enums/support/modals/ModalActionTarget';
    import { ModalActionType } from '@/modules/enums/support/modals/ModalActionType';
    import { ModalResultType } from '@/modules/enums/support/modals/ModalResultType';
    import { ModalType } from '@/modules/enums/support/modals/ModalType';
    import { ModalVariant } from '@/modules/enums/support/modals/ModalVariant';
    import {
        UiRichTextContent,
        UiRichTextEditor,
        documentIsEmpty,
        emptyDocument,
        parseMessageBody,
        textToDocument,
        type RichTextDocument,
        type RichTextEditorLabels,
    } from '@freelance/vue-ui/editor';
    import { Icon } from '@iconify/vue';
    import { computed, inject, ref } from 'vue';
    import { useI18n } from 'vue-i18n';

    defineOptions({ name: 'MessageComposeModal' });

    const props = defineProps<{
        props: {
            message: FeedMessage | null;
            canPublish: boolean;
        };
    }>();

    const dismissModal = inject<() => void>('dismissModal', () => undefined);
    const { t } = useI18n();
    const { saveCompanyMessage } = useMessages();
    const modalService = useModalService();
    const toast = useToastService();

    const message = props.props.message;
    const editing = message !== null;
    const published = message?.status === 'published';
    const showDraft = !published;
    const canPublish = props.props.canPublish;
    const title = ref(message?.type === 'contract' ? message.title : '');
    const document = ref<RichTextDocument>(startingDocument(message));
    const previewing = ref(false);
    const invalid = ref(false);
    const busy = ref(false);

    const editorLabels = computed<Partial<RichTextEditorLabels>>(() => ({
        content: t('EDITOR_CONTENT'),
        bold: t('EDITOR_BOLD'),
        italic: t('EDITOR_ITALIC'),
        underline: t('EDITOR_UNDERLINE'),
        heading: t('EDITOR_HEADING'),
        bulletList: t('EDITOR_BULLET_LIST'),
        orderedList: t('EDITOR_ORDERED_LIST'),
        link: t('EDITOR_LINK'),
        blockquote: t('EDITOR_BLOCKQUOTE'),
        clear: t('EDITOR_CLEAR'),
        undo: t('EDITOR_UNDO'),
        redo: t('EDITOR_REDO'),
        linkUrl: t('EDITOR_LINK_URL'),
        linkApply: t('EDITOR_LINK_APPLY'),
        linkRemove: t('EDITOR_LINK_REMOVE'),
        linkInvalid: t('EDITOR_LINK_INVALID'),
        tooLong: t('EDITOR_TOO_LONG'),
    }));

    function startingDocument(current: FeedMessage | null): RichTextDocument {
        if (!current?.rawBody) return emptyDocument();
        const parsed = parseMessageBody(current.rawBody);
        return parsed.document ?? textToDocument(parsed.text);
    }

    async function save(publish: boolean): Promise<void> {
        if (busy.value) return;
        if (!title.value.trim() || documentIsEmpty(document.value)) {
            invalid.value = true;
            previewing.value = false;
            return;
        }
        invalid.value = false;
        if (publish && !props.props.canPublish) return;
        if (publish) {
            const confirmed = await modalService.open({
                title: 'MESSAGE_PUBLISH_CONFIRM_TITLE',
                type: ModalType.SLIDE_IN_TOP_CENTER,
                component: ConfirmationModal,
                props: { message: 'MESSAGE_PUBLISH_CONFIRM', variant: ModalVariant.INFO },
                actions: [
                    {
                        type: ModalResultType.DISMISS,
                        name: 'GENERAL_CANCEL',
                        variant: ModalActionType.DISMISS,
                        target: ModalActionTarget.CANCEL,
                    },
                    {
                        type: ModalResultType.ACCEPT,
                        name: 'MESSAGE_COMPOSE_PUBLISH',
                        variant: ModalActionType.PRIMARY,
                        target: ModalActionTarget.CONFIRM,
                    },
                ],
            });
            if (confirmed.type !== ModalResultType.ACCEPT) return;
        }

        busy.value = true;
        try {
            await saveCompanyMessage({
                id: message?.id,
                title: title.value.trim(),
                body: JSON.stringify(document.value),
                status: publish ? 'published' : published ? undefined : 'draft',
            });
            toast.success(publish ? 'MESSAGE_PUBLISHED_TOAST' : published ? 'MESSAGE_UPDATED_TOAST' : 'MESSAGE_DRAFT_SAVED');
            dismissModal();
        } catch {
            toast.error('MESSAGE_SAVE_ERROR');
        } finally {
            busy.value = false;
        }
    }
</script>
