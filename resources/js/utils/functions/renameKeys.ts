type StringKeys<T> = keyof T & string

type RenameMap<T> = Partial<Record<StringKeys<T>, string>>

type RenamedObject<T extends Record<string, any>, M extends RenameMap<T>> = {
  [K in StringKeys<T> as K extends keyof M ? (M[K] extends string ? M[K] : K) : K]: T[K]
}

export function renameKeys<T extends Record<string, any>, M extends RenameMap<T>>(
  obj: T,
  keyMap: M
): RenamedObject<T, M> {
  const result: Record<string, any> = {}

  for (const [key, value] of Object.entries(obj)) {
    const newKey = key in keyMap ? keyMap[key as keyof M] : key
    // @ts-expect-error
    result[newKey] = value
  }

  return result as RenamedObject<T, M>
}
