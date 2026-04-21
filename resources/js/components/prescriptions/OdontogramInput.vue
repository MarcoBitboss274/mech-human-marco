<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    modelValue: number[];
    disabled?: boolean;
    label?: string;
    errors?: string;
};

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    label: undefined,
    errors: undefined,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: number[]): void;
}>();

const upperTeeth = [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28] as const;
const lowerTeeth = [48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38] as const;

const images = import.meta.glob('@/assets/images/odonto/*.png', { eager: true, import: 'default' }) as Record<string, string>;
const imageByTooth: Record<number, string> = {};
for (const [path, url] of Object.entries(images)) {
    const match = path.match(/\/(\d+)\.png$/);
    if (!match) continue;
    imageByTooth[Number(match[1])] = url;
}

const selectedSet = computed(() => new Set(props.modelValue));

const isSelected = (tooth: number) => selectedSet.value.has(tooth);
const toothImage = (tooth: number) => imageByTooth[tooth] ?? '';

const toggleTooth = (tooth: number) => {
    if (props.disabled) return;

    const next = new Set(props.modelValue);
    if (next.has(tooth)) next.delete(tooth);
    else next.add(tooth);

    emit(
        'update:modelValue',
        Array.from(next).sort((a, b) => a - b),
    );
};
</script>

<template>
    <div class="odontogram-input" :class="{ 'odontogram-input--disabled': disabled }">
        <label v-if="label" class="bb-label mb-2 block">{{ label }}</label>

        <div class="odontogram-input__section">
            <div class="odontogram-input__title">{{ t('Arcata Superiore') }}</div>
            <div class="odontogram-input__grid">
                <button
                    v-for="tooth in upperTeeth"
                    :key="tooth"
                    type="button"
                    class="odontogram-input__tooth"
                    :class="{ active: isSelected(tooth) }"
                    :disabled="disabled"
                    @click="toggleTooth(tooth)"
                >
                    <span class="odontogram-input__tooth-image" :style="{ backgroundImage: `url(${toothImage(tooth)})` }" />
                    <span class="odontogram-input__tooth-number">{{ tooth }}</span>
                </button>
            </div>
        </div>

        <div class="odontogram-input__section">
            <div class="odontogram-input__title">{{ t('Arcata Inferiore') }}</div>
            <div class="odontogram-input__grid">
                <button
                    v-for="tooth in lowerTeeth"
                    :key="tooth"
                    type="button"
                    class="odontogram-input__tooth"
                    :class="{ active: isSelected(tooth) }"
                    :disabled="disabled"
                    @click="toggleTooth(tooth)"
                >
                    <span class="odontogram-input__tooth-image" :style="{ backgroundImage: `url(${toothImage(tooth)})` }" />
                    <span class="odontogram-input__tooth-number">{{ tooth }}</span>
                </button>
            </div>
        </div>

        <div v-if="errors" class="odontogram-input__error">
            {{ errors }}
        </div>
    </div>
</template>

<style>
@reference '@/../css/base.css';

.odontogram-input {
    --active-color: var(--bb-primary);
    --active-bg: color-mix(in srgb, var(--bb-primary) 12%, white);
    @apply w-full;
}

.odontogram-input__section {
    @apply flex flex-col gap-2;
}

.odontogram-input__section + .odontogram-input__section {
    @apply mt-4;
}

.odontogram-input__title {
    @apply text-center text-sm font-semibold text-gray-600;
}

.odontogram-input__grid {
    @apply grid grid-cols-8 justify-items-center gap-x-2 gap-y-3 md:grid-cols-16;
}

.odontogram-input__tooth {
    @apply flex w-10 cursor-pointer flex-col items-center gap-1 rounded-md border border-gray-200 bg-white p-1 transition-transform duration-150;
}

.odontogram-input__tooth:hover:not(:disabled) {
    transform: scale(1.05);
}

.odontogram-input__tooth:disabled {
    @apply cursor-not-allowed opacity-50;
}

.odontogram-input__tooth.active {
    @apply border-[var(--active-color)] bg-[var(--active-bg)];
}

.odontogram-input__tooth-image {
    @apply block h-8 w-8 bg-contain bg-center bg-no-repeat;
}

.odontogram-input__tooth-number {
    @apply text-[11px] font-medium text-gray-500;
}

.odontogram-input__tooth.active .odontogram-input__tooth-number {
    @apply text-[var(--active-color)];
}

.odontogram-input__error {
    @apply mt-2 text-sm font-medium text-red-600;
}
</style>
