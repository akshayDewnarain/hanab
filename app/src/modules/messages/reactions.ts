/** Stable reaction ids. The API still stores the emoji these map to. */
export const MESSAGE_REACTIONS = [
    { id: 'like', emoji: '👍', labelKey: 'MESSAGE_REACTION_LIKE' },
    { id: 'celebrate', emoji: '🎉', labelKey: 'MESSAGE_REACTION_CELEBRATE' },
    { id: 'applause', emoji: '👏', labelKey: 'MESSAGE_REACTION_APPLAUSE' },
] as const;

export type MessageReactionId = (typeof MESSAGE_REACTIONS)[number]['id'];

export function reactionIdForEmoji(emoji: string | null | undefined): string | null {
    if (!emoji) return null;
    return MESSAGE_REACTIONS.find((item) => item.emoji === emoji)?.id ?? emoji;
}

export function emojiForReactionId(id: string | null): string | null {
    if (!id) return null;
    return MESSAGE_REACTIONS.find((item) => item.id === id)?.emoji ?? id;
}
