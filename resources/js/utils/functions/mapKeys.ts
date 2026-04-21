export const mapKeys = <T, U>(
  obj: Record<string, T>,
  fn: (value: T, key: string) => U
): Record<string, U> => {
  return Object.fromEntries(Object.entries(obj).map(([key, value]) => [fn(value, key), value]))
}
