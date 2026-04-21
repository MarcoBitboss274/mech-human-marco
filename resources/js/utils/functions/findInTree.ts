/**
 * Find the eleemnt for which iteratee rfeturns true in a tree like structure
 */
export const findInTree = <T extends Record<string, any>>(
  items: T[],
  keyName: keyof T,
  iteratee: (item: T) => boolean
): null | T => {
  return items.reduce((acc: null | T, curr) => {
    if (acc) return acc
    if (iteratee(curr)) {
      return curr
    }

    if (keyName in curr) {
      const fromChildren = findInTree(curr[keyName], keyName, iteratee)
      if (fromChildren) {
        return fromChildren
      }
    }
    return acc
  }, null)
}
