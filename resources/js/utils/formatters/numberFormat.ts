import { isNotNil } from '../functions/isNotNil'

type FormatOptions = {
  locales?: string[]
  value?: number | null
} & Intl.NumberFormatOptions

export const numberFormat: (options: FormatOptions) => string = ({
  locales = ['it-IT'],
  value = null,
  maximumFractionDigits = 0,
  ...rest
} = {}) => {
  if (isNotNil(value)) {
    return new Intl.NumberFormat(locales, {
      maximumFractionDigits,
      ...rest
    }).format(value)
  }
  return ''
}
