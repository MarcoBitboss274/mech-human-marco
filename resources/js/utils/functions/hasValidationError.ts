import type { Response } from '@/types/Response'

export const hasValidationError = (
  error: any
): error is Response<{
  data: Record<string, string[]>
  error: true
  error_code: 1001
  error_message: string
}> => {
  return error.error_code === '0-0002'
}
