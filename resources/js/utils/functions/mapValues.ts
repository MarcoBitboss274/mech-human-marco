export const mapValues = <T extends Record<string, any>, U>(
  obj: T,
  fn: (value: T[keyof T], key: string, obj: T) => U
): {
  [key in keyof T]: U
} => {
  return Object.fromEntries(
    Object.entries(obj).map(([key, value]) => [key, fn(value, key, obj)])
  ) as {
    [key in keyof T]: U
  }
}
