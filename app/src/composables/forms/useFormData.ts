import { computed, reactive, ref, watch } from 'vue';
import { FieldWidth } from '@/modules/enums/support/inputs/FieldWidth.ts';
import { useFormValidator } from '@/composables/forms/useFormValidator';
import type BaseInputField from '@/modules/models/forms/BaseInputField';
import type { FormBuilder as FormDef, FormBuilderPanel } from '@/modules/types/FormBuilderTypes';
import type Model from '@/modules/models/Model';
import { useToastService } from '@/composables/useToastService';

export function useFormData(
    instance: Model<unknown>,
    options: {
        readonly?: boolean;
        syncToInstanceOnChange?: boolean;
    } = {},
) {
    const { syncToInstanceOnChange = true } = options;

    const schema = computed<FormDef>(() => instance.detailSchematic());
    const formData = reactive<Record<string, unknown>>({});
    const activeTabs = reactive<Record<string, number>>({});
    const validator = ref<ReturnType<typeof useFormValidator>>();
    const toastService = useToastService();

    function tabStorageKey(panelIndex: number, stackedIndex?: number): string {
        return stackedIndex === undefined ? String(panelIndex) : `${panelIndex}_s${stackedIndex}`;
    }

    function forEachLeafPanel(cb: (panel: FormBuilderPanel) => void): void {
        for (const panel of schema.value.panels ?? []) {
            if (panel.stackedPanels?.length) {
                for (const inner of panel.stackedPanels) {
                    cb(inner);
                }
            } else {
                cb(panel);
            }
        }
    }

    /** Flatten fields (order preserved) */
    const allFields = computed<BaseInputField[]>(() => {
        const out: BaseInputField[] = [];
        forEachLeafPanel((panel) => {
            for (const tab of panel.tabs ?? []) {
                for (const row of tab.rows ?? []) {
                    if (row.type === 'fields' && row.fields?.length) out.push(...row.fields);
                }
            }
        });

        const extraNames = new Set<string>();
        for (const f of out) {
            const names = (f.options as { includeInputFieldNamesForPayload?: string[] }).includeInputFieldNamesForPayload;
            if (names?.length) {
                for (const n of names) {
                    extraNames.add(n);
                }
            }
        }

        const seen = new Set(out.map((x) => x.options.name));
        const defs = instance.inputFields();
        for (const n of extraNames) {
            if (seen.has(n)) continue;
            const def = defs.find((d) => d.options.name === n);
            if (def && !def.isExcludedFromForm()) {
                out.push(def);
                seen.add(n);
            }
        }

        return out;
    });

    /** Get a value from an object by a dot-separated path */
    function getPath(obj: unknown, path: string) {
        return path.split('.').reduce((o, k) => (o == null ? o : (o as Record<string, unknown>)[k]), obj);
    }

    /** Set a value in an object by a dot-separated path */
    function setPath(obj: unknown, path: string, value: unknown) {
        const parts = path.split('.');
        let o = obj as Record<string, unknown>;
        for (let i = 0; i < parts.length - 1; i++) {
            o[parts[i]] ??= {};
            o = o[parts[i]] as Record<string, unknown>;
        }
        o[parts[parts.length - 1]] = value;
    }

    /** Hydrate formData from instance input fields */
    function hydrateFormData(): void {
        const fields = instance.inputFields();
        const next: Record<string, unknown> = {};

        for (const field of fields) {
            if (field.isExcludedFromForm()) {
                continue;
            }

            const name = field.options.name;
            const relation = field.options.relation;
            const path = relation?.path ?? name;
            const raw = getPath(instance, path);

            if (relation) {
                const idKey = relation.idKey ?? 'id';
                if (relation.type === 'belongsTo') {
                    next[name] = (raw as Record<string, unknown>)?.[idKey] ?? null;
                } else if (relation.type === 'manyToMany' && relation.pivot) {
                    next[name] = Array.isArray(raw)
                        ? (raw as Record<string, unknown>[]).map((e) => {
                              const pivot = (e as Record<string, unknown>)?.pivot as
                                  | Record<string, unknown>
                                  | undefined;
                              return {
                                  id: (e as Record<string, unknown>)?.[idKey],
                                  name: (e as Record<string, unknown>)?.name,
                                  quantity: pivot?.quantity ?? 1,
                                  unit_price: pivot?.unit_price ?? 0,
                                  meta: pivot?.meta ?? null,
                              };
                          })
                        : [];
                } else if (relation.type === 'manyToMany' || relation.type === 'hasMany') {
                    next[name] = Array.isArray(raw)
                        ? raw.map((e: unknown) => {
                              if (typeof e === 'string' || typeof e === 'number') {
                                  return e;
                              }

                              return (e as Record<string, unknown>)?.[idKey];
                          })
                        : [];
                }
            } else {
                // Handle different field types appropriately
                if (field.getComponent()?.name === 'FieldUploadImage') {
                    // For image fields, ensure we pass a string (URL) or number (ID), not a File object
                    next[name] =
                        typeof raw === 'string' || typeof raw === 'number' ? raw : (field.options.defaultValue ?? '');
                } else {
                    next[name] = raw ?? field.options.defaultValue ?? '';
                }
            }
        }

        Object.assign(formData, next);
        validator.value = useFormValidator(fields, formData);
    }

    /** UI helpers */
    function setActiveTab(tabKey: string, tabIndex: number) {
        activeTabs[tabKey] = tabIndex;
    }

    function normalizeCol(col?: number) {
        const allowed = [1, 2, 3, 4, 6, 8, 9, 10, 12];
        if (!col || !allowed.includes(col)) return 12;
        return col;
    }

    function colClass(col?: number) {
        return `col-span-${normalizeCol(col)}`;
    }

    function fieldWidthClass(width?: FieldWidth | string) {
        switch (width) {
            case FieldWidth.HALF:
                return 'col-span-6';
            case FieldWidth.THIRD:
                return 'col-span-4';
            case FieldWidth.QUARTER:
                return 'col-span-3';
            default:
                return 'col-span-12';
        }
    }

    /** Field change */
    function onFieldUpdate(name: string, value: unknown) {
        const field = allFields.value.find((f) => f.options.name === name);
        if (field?.isExcludedFromForm()) {
            return;
        }

        formData[name] = value;
        if (syncToInstanceOnChange) {
            setPath(instance, name, value);
        }
    }

    /** Validate + submit */
    function validate(): boolean {
        if (!validator.value) {
            return false;
        }

        if (!validator.value?.validateAll()) {
            toastService.warning('ERROR_FORM_VALIDATION_NOT_PASSED');
        }

        return validator.value?.validateAll();
    }

    async function onSubmit(): Promise<Record<string, unknown>> {
        if (!validate()) throw new Error('VALIDATION_FAILED');

        if (!syncToInstanceOnChange) {
            for (const f of allFields.value) {
                if (f.isExcludedFromForm()) {
                    continue;
                }
                const name = f.options.name;
                setPath(instance, name, formData[name]);
            }
        }

        const payload: Record<string, unknown> = {};
        for (const f of allFields.value) {
            if (f.isExcludedFromForm()) {
                continue;
            }
            const name = f.options.name;
            const payloadKey = (f.options.relation?.formDataKey ?? name) as string;
            if (name in formData) {
                payload[payloadKey] = formData[name];
            }
        }

        return payload;
    }

    function initActiveTabs() {
        const validKeys = new Set<string>();
        schema.value.panels?.forEach((panel, i) => {
            if (panel.stackedPanels?.length) {
                panel.stackedPanels.forEach((_, j) => {
                    validKeys.add(tabStorageKey(i, j));
                });
                return;
            }
            validKeys.add(tabStorageKey(i));
        });

        for (const k of Object.keys(activeTabs)) {
            if (!validKeys.has(k)) {
                delete activeTabs[k];
            }
        }

        schema.value.panels?.forEach((panel, i) => {
            const inits = (leaf: FormBuilderPanel, key: string) => {
                const tabCount = leaf.tabs?.length ?? 0;
                if (tabCount <= 1 && !leaf.alwaysShowTabs) {
                    delete activeTabs[key];
                    return;
                }
                if (tabCount === 0) {
                    delete activeTabs[key];
                    return;
                }
                if (activeTabs[key] == null) {
                    activeTabs[key] = 0;
                } else if (activeTabs[key] >= tabCount) {
                    activeTabs[key] = 0;
                }
            };

            if (panel.stackedPanels?.length) {
                panel.stackedPanels.forEach((inner, j) => {
                    inits(inner, tabStorageKey(i, j));
                });
            } else {
                inits(panel, tabStorageKey(i));
            }
        });
    }

    function isActive(tabKey: string, tabIndex: number) {
        return (activeTabs[tabKey] ?? 0) === tabIndex;
    }

    const GRID_COLS = 12;

    const panelRows = computed<number[]>(() => {
        const panels = schema.value.panels ?? [];
        const rows: number[] = [];
        let row = 1;
        let width = 0;

        for (let i = 0; i < panels.length; i++) {
            const col = Math.min(GRID_COLS, panels[i]?.col ?? GRID_COLS);

            if (width > 0 && width + col > GRID_COLS) {
                row += 1;
                width = 0;
            }

            rows[i] = row;
            width += col;

            if (width === GRID_COLS) {
                width = 0;
                row += 1;
            }
        }
        return rows;
    });

    const totalPanelRows = computed(() => {
        const r = panelRows.value;
        return r.length ? Math.max(...r) : 0;
    });

    function zForPanel(i: number) {
        const row = panelRows.value[i] ?? 1;
        return totalPanelRows.value - row + 1;
    }

    // Watchers
    watch(schema, initActiveTabs, { immediate: true });

    watch(
        () => instance,
        () => {
            hydrateFormData();
        },
        { immediate: true, deep: true },
    );

    return {
        // State
        schema,
        formData,
        activeTabs,
        validator,
        allFields,
        panelRows,
        totalPanelRows,

        // Methods
        hydrateFormData,
        setActiveTab,
        normalizeCol,
        colClass,
        fieldWidthClass,
        onFieldUpdate,
        validate,
        onSubmit,
        initActiveTabs,
        isActive,
        zForPanel,
        tabStorageKey,

        // Utilities
        getPath,
        setPath,
    };
}
