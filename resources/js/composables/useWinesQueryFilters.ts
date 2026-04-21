import { useQueryState } from './useQueryState'
import type { WineSize, WineType } from '@/types/Wine'

export const useWinesQueryFilters = () => {
  const queryFilters = useQueryState<{
    query: string
    type: WineType['id'][]
    size: WineSize['id'][]
  }>({
    query: null,
    type: [],
    size: []
  })

  return {
    queryFilters
  }
}
