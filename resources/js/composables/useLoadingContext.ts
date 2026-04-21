import { injectLocal, provideLocal } from '@vueuse/core'
import { nanoid } from 'nanoid'
import { type Ref, computed, onBeforeUnmount, ref } from 'vue'

export const injectionKey = Symbol('loadingContext')

export const useLoadingContext = () => {
  const existingContext = injectLocal(injectionKey, null)

  if (existingContext) {
    return existingContext
  }
  const stack: Ref<{ _value: Ref<boolean>; id: string }[]> = ref([])

  /**
   * Register an error to the stack, when the component is unmounted, the error will be unregistered automatically.
   * @param error - The error to register
   * @returns The id of the error, which can be used to unregister the error
   */
  const register = (ref: Ref<boolean>) => {
    const id = nanoid()
    stack.value.push({ _value: ref, id })
    onBeforeUnmount(() => {
      unregister(id)
    })
  }

  /**
   * Unregister an error from the stack
   * @param id - The id of the error to unregister
   */
  const unregister = (id: string) => {
    stack.value = stack.value.filter((e) => {
      return e.id !== id
    })
  }

  /**
   * Clear all errors from the stack. They are not removed from the stack, but their value is set to null.
   * This function has side effects on all the refs that are being tracked by the error context.
   */
  const clear = () => {
    stack.value.forEach((e) => {
      e._value.value = false
    })
  }

  const isLoading = computed(() => {
    return stack.value.some((e) => e._value)
  })
  const context = {
    stack,
    isLoading,
    register,
    unregister,
    clear
  }

  provideLocal(injectionKey, context)

  return injectLocal<typeof context>(injectionKey)!
}
