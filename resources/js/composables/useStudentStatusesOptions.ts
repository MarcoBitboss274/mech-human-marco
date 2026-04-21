import { useAsyncFn } from './useAsyncFn'
import { getPaginated } from '@/api/studentsStatuses'
import type { StudentStatus } from '@/types/StudentStatus'
import { isErrorResponse } from '@/utils/functions/isErrorResponse'
import { waitFor } from '@/utils/functions/waitFor'

type DefaultData = Extract<
  Awaited<ReturnType<typeof getPaginated>>,
  { error: false }
>['data']['data']

let cached: StudentStatus[] | null = null

export const useStudentStatusesOptions = (
  params: Parameters<typeof useAsyncFn<DefaultData>>[1] = {
    immediate: false,
    /* Default data needs to be of the same type as the response data */
    defaultData: []
  }
) => {
  const data = useAsyncFn(async () => {
    if (cached) {
      return cached
    }
    const res = await getPaginated({
      page: 1,
      per_page: 100
    })
    if (isErrorResponse(res)) {
      throw new Error(res.error_message)
    }
    cached = res.data.data
    return res.data.data
  }, params)

  return {
    ...data,
    execute: async () => {
      if (data.loading.value) {
        await waitFor(() => !data.loading.value)
      }
      if (!data.counter.value) {
        await data.execute()
      }
      return data.data.value
    }
  }
}
