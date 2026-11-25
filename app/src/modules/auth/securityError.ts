import axios from 'axios';

export function securityErrorMessage(error: unknown, translate: (key: string) => string): string {
    if (!axios.isAxiosError(error)) return translate('ACCOUNT_ACTION_FAILED');
    const message = error.response?.data?.message;
    if (message === 'The current password is incorrect.') return translate('ACCOUNT_PASSWORD_INCORRECT');
    if (message === 'The verification code is invalid or expired.') return translate('ACCOUNT_CODE_INVALID');
    return translate('ACCOUNT_ACTION_FAILED');
}
