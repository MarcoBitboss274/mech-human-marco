import dayjs from 'dayjs';
import 'dayjs/locale/it';
import customParseFormat from 'dayjs/plugin/customParseFormat';
import timezone from 'dayjs/plugin/timezone';
import updateLocale from 'dayjs/plugin/updateLocale';
import utc from 'dayjs/plugin/utc';
import { isNotNil } from '../functions/isNotNil';

dayjs.locale('it');
dayjs.extend(updateLocale);
dayjs.extend(customParseFormat);
dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.updateLocale('it', {
    months: ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'],
    weekdays: ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'],
});

export const date = (date?: Parameters<typeof dayjs>[0], formatString = 'DD/MM/YYYY') => {
    if (isNotNil(date)) {
        return dayjs(date).format(formatString);
    }
    return date;
};

export const dateTime = (date?: Parameters<typeof dayjs>[0], formatString = 'DD/MM/YYYY HH:mm') => {
    return date ? dayjs(date).format(formatString) : date;
};
