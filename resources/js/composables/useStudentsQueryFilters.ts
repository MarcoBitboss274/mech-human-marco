import { useQueryState } from './useQueryState'

export const useStudentsQueryFilters = () => {
  const queryFilters = useQueryState<{
    query: string
    class_id: number[]
    status_id: number[]
    tutor_id: number[]
    scholarship: boolean
    date_of_birth: string[]
    registered_at: string[]
    average_grade_from: number
    average_grade_to: number
    city: string
  }>({
    query: null,
    class_id: [],
    status_id: [],
    tutor_id: [],
    scholarship: null,
    date_of_birth: [],
    registered_at: [],
    average_grade_from: null,
    average_grade_to: null,
    city: null
  })

  return {
    queryFilters
  }
}
