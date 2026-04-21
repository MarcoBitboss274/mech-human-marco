import type { Response } from '@/types/Response'

export const isErrorResponse = <T>(
  response: Response<T>
): response is Response<T> & { error: true } => {
  return response.error === true
}
