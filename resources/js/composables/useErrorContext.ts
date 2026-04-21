import { injectLocal, provideLocal } from '@vueuse/core'
import { nanoid } from 'nanoid'
import { type Ref, computed, onBeforeUnmount, ref } from 'vue'

export const injectionKey = Symbol('errorContext')

export const useErrorContext = () => {
  const existingContext = injectLocal(injectionKey, null)

  if (existingContext) {
    return existingContext
  }
  const stack: Ref<{ error: Ref<Error | null>; id: string }[]> = ref([])

  /**
   * Register an error to the stack, when the component is unmounted, the error will be unregistered automatically.
   * @param error - The error to register
   * @returns The id of the error, which can be used to unregister the error
   */
  const register = (error: Ref<Error | null>) => {
    const id = nanoid()
    stack.value.push({ error: error, id })
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
      if (e.error.value) {
        e.error.value = null
      }
    })
  }

  const errors = computed(() => {
    return stack.value.reduce((acc: string[], e) => {
      if (e.error && 'message' in e.error) {
        /* @ts-expect-error */
        acc.push(e.error.message)
      }
      return acc
    }, [])
  })
  const hasErrors = computed(() => {
    return errors.value.length > 0
  })
  const first = computed<string | undefined>(() => {
    return errors.value[0]
  })

  const context = {
    stack,
    hasErrors,
    first,
    errors,
    register,
    unregister,
    clear
  }

  provideLocal(injectionKey, context)

  return injectLocal<typeof context>(injectionKey)!
}
