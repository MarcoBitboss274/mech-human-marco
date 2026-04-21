import type { StepperContext } from '@/components/common/XStepper.vue'
import type { InjectionKey } from 'vue'

export const stepperKey = Symbol() as InjectionKey<StepperContext>
