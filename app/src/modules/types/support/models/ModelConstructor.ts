import type Model from '@/modules/models/support/Model.ts';

/**
 * Constructor signature for concrete {@link Model} subclasses.
 *
 * Used by remote option loaders and entity modals where the exact subclass is passed as a value.
 * Concrete subclasses often bake `entity` / `entitySingular` into `super(...)` and expose a zero-arg ctor.
 */
export type ModelConstructor<T = unknown> = new (...args: unknown[]) => Model<T>;
