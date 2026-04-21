import type { UserFilters } from '@/api/users'
import { useAsyncFn } from './useAsyncFn'
import { getPaginated } from '@/api/users'
import type { StudentStatus } from '@/types/StudentStatus'
import { isErrorResponse } from '@/utils/functions/isErrorResponse'
import { waitFor } from '@/utils/functions/waitFor'
import { ref } from 'vue'
import { isNotNil } from '@/utils/functions/isNotNil'

type DefaultData = Extract<
  Awaited<ReturnType<typeof getPaginated>>,
  { error: false }
>['data']['data']

export const useTutorOptions = (
  params: Parameters<typeof useAsyncFn<DefaultData>>[1] = { immediate: false, defaultData: [] }
) => {
  const query = ref<string>('')
  const prefill = ref(false)
  const modelValue = ref(null)
  const data = useAsyncFn(async () => {
    const filters: UserFilters = {}
    if (prefill.value) {
      const idFilter = [modelValue.value].flat().filter(isNotNil)
      if (idFilter.length) {
        filters.id = { value: idFilter }
      }
    }
    if (query.value) {
      filters.query = { value: query.value }
    }
    const res = await getPaginated({
      page: 1,
      per_page: 100,
      filters
    })
    if (isErrorResponse(res)) {
      throw new Error(res.error_message)
    }
    return res.data.data
  }, params)

  return {
    ...data,
    execute: async (q: string, p: boolean, m: any) => {
      query.value = q
      prefill.value = p
      modelValue.value = m

      return data.execute() as Promise<DefaultData>
    }
  }
}
