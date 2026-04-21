import type { Vintage } from '@/types/Vintage'
import { useQueryState } from './useQueryState'
import type { WineDetail } from '@/types/Wine'
import type { Winery } from '@/types/Winery'

export const useWineQueryFilters = () => {
  const queryFilters = useQueryState<{
    wine_id: WineDetail['id'][]
    vintage_id: Vintage['id'][]
    winery_id: Winery['id'][]
  }>({
    wine_id: [],
    vintage_id: [],
    winery_id: []
  })

  return {
    queryFilters
  }
}
