<template>
    <span
        aria-role="tabpanel"
        class="stepper__step"
        :class="{
            'stepper__step--past': context.currentStep > stepNumber,
            'stepper__step--future': context.currentStep < stepNumber,
            'stepper__step--current': context.currentStep === stepNumber,
        }"
        :inert="context.currentStep !== stepNumber"
    >
        <slot v-bind="context"></slot>
    </span>
</template>

<script setup lang="ts">
import { stepperKey } from '@/utils/misc/stepperKey';
import { computed, inject } from 'vue';

const stepperContext = inject(stepperKey)!;
const stepNumber = stepperContext.value.registerStep();
const context = computed(() => ({
    ...stepperContext.value,
    stepNumber,
}));
</script>

<style>
@reference '@/../css/base.css';
.stepper__step {
    @apply block;
}
</style>
