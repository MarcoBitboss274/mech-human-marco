import { cloneDeep } from '@/utils/functions/cloneDeep'
import { type MaybeRef, computed, ref, toValue, watch } from 'vue'

export const useStaticPagination = <T>({
  items,
  perPage
}: {
  items: MaybeRef<T[]>
  perPage: number
}) => {
  const page = ref(1)
  const pages = computed(() => Math.ceil(toValue(items).length / perPage))
  const total = computed(() => toValue(items).length)

  const data = ref<T[]>([])
  const getData = () => {
    data.value = cloneDeep(toValue(items).slice((page.value - 1) * perPage, page.value * perPage))
  }
  watch([page, () => toValue(items)], getData, { immediate: true })

  return { page, pages, total, data, perPage, refresh: getData }
}
