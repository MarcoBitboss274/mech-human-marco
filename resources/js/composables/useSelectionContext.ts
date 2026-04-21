import { injectLocal, provideLocal } from '@vueuse/core'
import { reactive } from 'vue'
export const injectionKey = Symbol('selectionContext')

type SelectionContext<T> = {
  all: boolean
  selected: T[]
  unselected: T[]
}

export const useSelectionContext = <T>() => {
  const existingContext = injectLocal(injectionKey, null)

  if (existingContext) {
    return existingContext
  }

  const context: SelectionContext<T> = reactive({
    all: false,
    selected: [],
    unselected: []
  })
  provideLocal<SelectionContext<T>>(injectionKey, context)

  return injectLocal<SelectionContext<T>>(injectionKey)!
}
