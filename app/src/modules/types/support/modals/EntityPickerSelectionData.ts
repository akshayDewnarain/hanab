/**
 * Payload returned by {@link EntityPicker} from `getData()` on save.
 *
 * @property model - Child model constructor echoed from picker props.
 * @property selectedIds - Full selected id list after picker confirm.
 * @property selectedEntities - Resolved row objects for the selected ids when available.
 */
export type EntityPickerSelectionData = {
    model?: unknown;
    selectedIds?: number[];
    selectedEntities?: unknown[];
};
