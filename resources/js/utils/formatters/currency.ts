import { isNotNil } from '@/utils/functions/isNotNil';

type FormatOptions = {
    locales?: string[];
    value?: number | null;
} & Intl.NumberFormatOptions;

export const currency: (options: FormatOptions) => string = ({
    currency = 'EUR',
    locales = ['it-IT'],
    style = 'currency',
    value = null,
    maximumFractionDigits = 0,
    ...rest
} = {}) => {
    if (isNotNil(value)) {
        return new Intl.NumberFormat(locales, {
            style,
            currency,
            maximumFractionDigits,
            ...rest,
        }).format(value);
    }
    return '';
};
