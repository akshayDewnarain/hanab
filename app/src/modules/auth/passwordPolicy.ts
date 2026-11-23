import type { PasswordRule } from '@freelance/vue-ui';
import http from '@/utils/http';

export type PasswordPolicyDescriptor = {
    min: number;
    max: number;
    uppercase: boolean;
    lowercase: boolean;
    numbers: boolean;
    symbols: boolean;
};

const fallback: PasswordPolicyDescriptor = {
    min: 12,
    max: 128,
    uppercase: true,
    lowercase: true,
    numbers: true,
    symbols: true,
};

let cached: PasswordPolicyDescriptor | null = null;

export async function loadPasswordPolicy(): Promise<PasswordPolicyDescriptor> {
    if (cached) return cached;
    try {
        const response = await http.get('public/password-policy');
        cached = { ...fallback, ...(response.data.data ?? {}) };
    } catch {
        cached = fallback;
    }
    return cached;
}

export function passwordRules(
    policy: PasswordPolicyDescriptor,
    label: (key: string, values?: Record<string, unknown>) => string,
): PasswordRule[] {
    const rules: PasswordRule[] = [
        {
            id: 'length',
            label: label('PASSWORD_RULE_LENGTH', { count: policy.min }),
            test: (value) => value.length >= policy.min && value.length <= policy.max,
        },
    ];
    if (policy.uppercase) {
        rules.push({ id: 'upper', label: label('PASSWORD_RULE_UPPER'), test: (value) => /\p{Lu}/u.test(value) });
    }
    if (policy.lowercase) {
        rules.push({ id: 'lower', label: label('PASSWORD_RULE_LOWER'), test: (value) => /\p{Ll}/u.test(value) });
    }
    if (policy.numbers) {
        rules.push({ id: 'number', label: label('PASSWORD_RULE_NUMBER'), test: (value) => /\p{N}/u.test(value) });
    }
    if (policy.symbols) {
        rules.push({ id: 'symbol', label: label('PASSWORD_RULE_SYMBOL'), test: (value) => /\p{Z}|\p{S}|\p{P}/u.test(value) });
    }
    return rules;
}

export function passwordMatchesPolicy(policy: PasswordPolicyDescriptor, value: string): boolean {
    return passwordRules(policy, () => '').every((rule) => rule.test(value));
}
