/**
 * Loose object map for JSON-like or dynamic payloads where keys are strings and values are unknown until narrowed.
 *
 * Used by payload helpers when cloning, diffing, or traversing nested structures without fixing a schema.
 */
export type AnyRec = Record<string, unknown>;
