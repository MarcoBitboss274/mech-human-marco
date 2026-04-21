type PaginationOptions = {
  page?: number
  per_page?: number
  sort_by?: string[]
}

export type PaginatedBody<T = object> = PaginationOptions & T

export type PaginatedResponse<T> = {
  data: T[]
  total: number
  page: number
  per_page: number
  pages: number
}
