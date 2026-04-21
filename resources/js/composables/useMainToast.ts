import { useToast } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

export const useMainToast = () => {
    const { toast } = useToast();
    const { t } = useI18n();

    const info = (text: string, title?: string) => {
        toast({
            theme: 'info',
            text: t(text),
            title: title,
        });
    };

    const success = (text: string, title?: string) => {
        toast({
            theme: 'success',
            text: t(text),
            title: title,
        });
    };

    const warning = (text: string, title?: string) => {
        toast({
            theme: 'warning',
            text: t(text),
            title: title,
        });
    };

    const error = (text?: string, title?: string) => {
        toast({
            theme: 'error',
            text: t(text ?? 'Si è verificato un errore'),
            title: title,
        });
    };

    return {
        info,
        success,
        warning,
        error,
    };
};
