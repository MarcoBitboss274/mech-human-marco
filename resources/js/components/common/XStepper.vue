<template>
    <span ref="stepper" class="stepper" :class="`stepper--${direction}`">
        <slot v-bind="stepperContext"></slot>
    </span>
</template>

<script setup lang="ts">
import { clamp } from '@/utils/functions/clamp';
import { stepperKey } from '@/utils/misc/stepperKey';
import { computed, provide, ref, watch } from 'vue';

export type StepperContext = typeof stepperContext;

type Props = {
    modelValue?: null | number;
    direction?: 'left' | 'right' | 'top' | 'bottom';
};

const props = withDefaults(defineProps<Props>(), {
    modelValue: 1,
    direction: 'left',
});

const emit = defineEmits<{
    (event: 'update:modelValue', value: number): void;
}>();

const stepper = ref<HTMLSpanElement | null>(null);

const internalValue = ref(props.modelValue);
watch(internalValue, async (value) => {
    if (value !== props.modelValue) {
        emit('update:modelValue', value!);
    }
});
watch(
    () => props.modelValue,
    (value) => {
        if (value !== internalValue.value) {
            internalValue.value = value;
        }
    },
);

const steps = ref(0);

const next = () => {
    internalValue.value = clamp(internalValue.value! + 1, 1, steps.value);
};
const previous = () => {
    internalValue.value = clamp(internalValue.value! - 1, 1, steps.value);
};
const first = () => {
    internalValue.value = clamp(1, 1, steps.value);
};
const last = () => {
    internalValue.value = clamp(steps.value, 1, steps.value);
};

const registerStep = () => ++steps.value;
const stepperContext = computed(() => {
    return {
        currentStep: internalValue.value!,
        totalSteps: steps.value,
        next,
        previous,
        first,
        last,
        registerStep,
        isEnd: internalValue.value === steps.value,
        isStart: internalValue.value === 1,
    };
});

provide(stepperKey, stepperContext);
</script>

<style>
@reference '@/../css/base.css';
.stepper {
    --duration: 0.5s;
    --margin: 16px;
    &.stepper--left {
        --horizontal-multiplier: -1;
        --vertical-multiplier: 0;
    }
    &.stepper--right {
        --horizontal-multiplier: 1;
        --vertical-multiplier: 0;
    }
    &.stepper--top {
        --horizontal-multiplier: 0;
        --vertical-multiplier: -1;
    }
    &.stepper--bottom {
        --horizontal-multiplier: 0;
        --vertical-multiplier: 1;
    }
    @apply relative block overflow-clip bg-[var(--bb-ui-bg)];
    .stepper__step {
        @apply absolute left-0 w-full bg-inherit opacity-0;
        transition:
            translate var(--duration),
            opacity calc(var(--duration) / 2);
        & ~ .stepper__step {
            @apply top-0;
        }

        &.stepper__step--past {
            @apply opacity-0;
            translate: calc(var(--horizontal-multiplier) * 100% + var(--margin) * var(--horizontal-multiplier))
                calc(var(--vertical-multiplier) * 100% + var(--margin) * var(--vertical-multiplier));
        }
        &.stepper__step--current {
            @apply relative opacity-100;
            translate: calc(var(--horizontal-multiplier) * 0) calc(var(--vertical-multiplier) * 0);
        }
        &.stepper__step--future {
            translate: calc(var(--horizontal-multiplier) * -100% - var(--margin) * var(--horizontal-multiplier))
                calc(var(--vertical-multiplier) * -100% - var(--margin) * var(--vertical-multiplier));
        }
    }
}
</style>
