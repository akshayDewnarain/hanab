import type { InjectionKey } from 'vue';

export const formBuilderGetFieldErrorKey = Symbol('formBuilderGetFieldError') as InjectionKey<
    (name: string) => string | undefined
>;

export const formBuilderMarkFieldTouchedKey = Symbol('formBuilderMarkFieldTouched') as InjectionKey<
    (name: string) => void
>;
