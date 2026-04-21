import { isEqual } from './isEqual'

export function shallowDiff(
  source: Record<string, any>,
  result: Record<string, any>
): Record<string, any> {
  return Object.keys(source).reduce(
    (acc, key) => {
      if (!isEqual(source[key], result[key])) {
        acc[key] = result[key]
      }
      return acc
    },
    {} as Record<string, any>
  )
}
