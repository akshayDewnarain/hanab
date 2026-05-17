/**
 * Reference to uploaded or persisted media shown in image fields.
 *
 * Extend at the API boundary when responses include thumbnails, variants, or metadata.
 *
 * @property url - Absolute or root-relative URL suitable for `<img src>`.
 * @property thumb_url - Optional thumbnail URL for compact previews.
 */
export default interface MediaURL {
    url: string;
    thumb_url?: string;
}
