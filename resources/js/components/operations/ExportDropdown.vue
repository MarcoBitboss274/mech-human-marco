<template>
    <BbTooltip v-if="disabled && disabledReason">
        <template #activator="{ props: tooltipProps }">
            <span v-bind="tooltipProps" class="export-dropdown__disabled-wrapper">
                <button
                    v-if="variant === 'link'"
                    type="button"
                    class="export-dropdown__link export-dropdown__link--disabled"
                    :disabled="true"
                >
                    <span class="export-dropdown__content">
                        <svg
                            class="export-dropdown__icon"
                            :width="iconSize"
                            :height="iconSize"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="7 10 12 15 17 10" />
                            <line x1="12" y1="15" x2="12" y2="3" />
                        </svg>
                        <span class="export-dropdown__label">{{ buttonLabel }}</span>
                    </span>
                </button>
                <BbButton v-else :disabled="true" :loading="loading" variant="outline" class="export-dropdown__btn">
                    <span class="export-dropdown__content">
                        <svg
                            class="export-dropdown__icon"
                            :width="iconSize"
                            :height="iconSize"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="7 10 12 15 17 10" />
                            <line x1="12" y1="15" x2="12" y2="3" />
                        </svg>
                        <span class="export-dropdown__label">{{ buttonLabel }}</span>
                    </span>
                </BbButton>
            </span>
        </template>
        {{ disabledReason }}
    </BbTooltip>
    <BbDropdown v-else :items="items" :offset="8">
        <template #activator="{ props }">
            <button
                v-if="variant === 'link'"
                v-bind="props"
                type="button"
                class="export-dropdown__link"
                :class="{ 'export-dropdown__link--disabled': disabled || loading }"
                :disabled="disabled || loading"
            >
                <span class="export-dropdown__content">
                    <svg
                        class="export-dropdown__icon"
                        :width="iconSize"
                        :height="iconSize"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="7 10 12 15 17 10" />
                        <line x1="12" y1="15" x2="12" y2="3" />
                    </svg>
                    <span class="export-dropdown__label">{{ buttonLabel }}</span>
                </span>
            </button>
            <BbButton
                v-else
                v-bind="props"
                variant="outline"
                :disabled="disabled || loading"
                :loading="loading"
                class="export-dropdown__btn"
            >
                <span class="export-dropdown__content">
                    <svg
                        class="export-dropdown__icon"
                        :width="iconSize"
                        :height="iconSize"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="7 10 12 15 17 10" />
                        <line x1="12" y1="15" x2="12" y2="3" />
                    </svg>
                    <span class="export-dropdown__label">{{ buttonLabel }}</span>
                </span>
            </BbButton>
        </template>
    </BbDropdown>
</template>

<script setup lang="ts">
import { BbButton, BbDropdown, type BbDropdownItem, BbTooltip, useToast } from 'bitboss-ui';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

type Variant = 'button' | 'link';

type Props = {
    exportUrl: string;
    label?: string;
    disabled?: boolean;
    disabledReason?: string;
    variant?: Variant;
};

const props = withDefaults(defineProps<Props>(), {
    label: undefined,
    disabled: false,
    disabledReason: undefined,
    variant: 'button',
});

const { t } = useI18n();
const { toast } = useToast();

const loading = ref(false);

const buttonLabel = computed(() => props.label ?? t('Esporta'));

// 12px per la variante button (icona compatta dentro outline scuro),
// 14px per la variante link (icona accanto al testo).
const iconSize = computed(() => (props.variant === 'button' ? 12 : 14));

const buildUrl = (format: 'csv' | 'xlsx'): string => {
    const separator = props.exportUrl.includes('?') ? '&' : '?';
    return `${props.exportUrl}${separator}format=${format}`;
};

const triggerDownload = (format: 'csv' | 'xlsx') => {
    if (loading.value || props.disabled) return;
    loading.value = true;
    try {
        window.location.href = buildUrl(format);
        toast({
            theme: 'success',
            text: t('Esportazione avviata'),
        });
    } catch {
        toast({
            theme: 'error',
            text: t('Esportazione non riuscita. Riprova'),
        });
    } finally {
        // Re-enable the button shortly after navigation; the browser handles the actual download.
        setTimeout(() => {
            loading.value = false;
        }, 1500);
    }
};

const items = computed<BbDropdownItem[]>(() => [
    {
        key: 'csv',
        text: t('Esporta CSV'),
        'prepend:icon': 'download',
        onClick: () => triggerDownload('csv'),
    },
    {
        key: 'xlsx',
        text: t('Esporta Excel'),
        'prepend:icon': 'download',
        onClick: () => triggerDownload('xlsx'),
    },
]);
</script>

<style scoped>
.export-dropdown__disabled-wrapper {
    display: inline-flex;
}

/* Variante BUTTON outline scuro: override delle CSS variabili che BbButton consuma per
   colore di bordo, testo e accent. Le var settate qui si propagano nei children. */
.export-dropdown__btn {
    --color: #374151; /* gray-700 */
    --border-color: #374151;
    --text-color: #374151;
}

/* Variante LINK: testo grigio cliccabile, no border, no background.
   Padding e altezza azzerati così il testo si allinea esattamente alla
   baseline del testo del counter accanto.
   `position: relative; top: 2px` abbassa l'intero pulsante (icona + testo)
   di 2px per allinearlo otticamente al contesto in cui è inserito. */
.export-dropdown__link {
    display: inline-flex;
    align-items: baseline;
    gap: 6px;
    padding: 0;
    margin: 0;
    background: transparent;
    border: 0;
    color: #6b7280; /* gray-500 */
    font-size: 14px;
    font-weight: 500;
    line-height: 20px;
    cursor: pointer;
    text-decoration: none;
    vertical-align: baseline;
    position: relative;
    top: 2px;
}

.export-dropdown__link:hover:not(.export-dropdown__link--disabled) {
    color: #374151;
    text-decoration: underline;
}

.export-dropdown__link:focus-visible {
    outline: 2px solid #6b7280;
    outline-offset: 2px;
    border-radius: 2px;
}

.export-dropdown__link--disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.export-dropdown__content {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.export-dropdown__icon {
    flex-shrink: 0;
    color: currentColor;
}

/* Per la variante button outline, l'icona prende il colore scuro. */
.export-dropdown__btn .export-dropdown__icon {
    color: #374151;
}

/* Sposta il TESTO (non l'icona) di 1px in basso per migliorare l'allineamento
   ottico col counter accanto / col contenuto degli altri pulsanti dell'header.
   `!important` per battere eventuali rule più specifiche e cache di style. */
.export-dropdown__link .export-dropdown__label,
.export-dropdown__btn .export-dropdown__label {
    display: inline-block !important;
    position: relative !important;
    top: 1px !important;
}
</style>
