import { mapValues } from '@/utils/functions/mapValues'
import { replace } from '@/utils/functions/replace'
import { useRouteQuery } from '@vueuse/router'
import { computed } from 'vue'

/**
 * A function that returns the next value of the sort direction.
 * @param currentValue - The current value of the sort direction.
 * @returns The next value of the sort direction.
 */
const nextValue = (currentValue: 'asc' | 'desc' | undefined) => {
  if (currentValue === 'asc') return 'desc'
  if (currentValue === 'desc') return undefined
  return 'asc'
}

export const useSort = () => {
  const sortBy = useRouteQuery<string[]>('sort_by[]', [], {
    mode: 'replace',
    transform: (value) => {
      return [].concat(value as any)
    }
  })

  /**
   * A map of the sort by query entries.
   * The key is the field name, the value is an object with the order and the index of the field in the sortBy array.
   */
  const mapped = computed(() => {
    return sortBy.value.reduce(
      (acc, item, index) => {
        const [key, direction = 'asc'] = item.split(':')
        acc[key] = { direction: direction as 'asc' | 'desc', index }
        return acc
      },
      {} as Record<string, { direction: 'asc' | 'desc'; index: number }>
    )
  })

  /**
   * Check if sorting for a specific key is enabled.
   * @param key - The key to check.
   * @returns True if sorting for the key is enabled, false otherwise.
   */
  const isSorted = (key: string) => {
    return !!mapped.value[key]
  }

  /**
   * Set the next sort direction for a specific key.
   * @param key - The key to set the next sort direction for.
   */
  const next = (key: string) => {
    const nextOrder = nextValue(mapped.value[key]?.direction)
    if (mapped.value[key]) {
      sortBy.value = replace(sortBy.value, mapped.value[key].index, `${key}:${nextOrder}`)
    } else {
      sortBy.value = [...sortBy.value, `${key}:${nextOrder}`]
    }
  }

  /**
   * Set the sort direction for a specific key.
   * @param key - The key to set the sort direction for.
   * @param value - The value to set the sort direction to.
   */
  const set = (key: string, value?: string) => {
    if (value === undefined) {
      if (mapped.value[key]?.direction === 'desc') {
        unset(key)
      } else {
        next(key)
      }
    } else {
      sortBy.value = replace(sortBy.value, mapped.value[key].index, `${key}:${value}`)
    }
  }

  /**
   * Unset the sort direction for a specific key.
   * @param key - The key to unset the sort direction for.
   */
  const unset = (key: string) => {
    if (mapped.value[key]) {
      sortBy.value = sortBy.value.filter((item) => !item.startsWith(key))
    }
  }

  /**
   * A computed property that returns the sort by query entries as a string array.
   * @returns The sort by query entries as a string array [key:direction, ...key:direction[]].
   */
  const parsed = computed(() => {
    return Object.entries(mapValues(mapped.value, (v) => v.direction)).map((pair) => pair.join(':'))
  })

  return {
    data: mapped,
    next,
    set,
    unset,
    isSorted,
    parsed
  }
}
