/* 
  This function is used to check if any of the passed properties of an object match a given string.
*/

export const matchesProperties = <
  T extends Record<U, any> & Record<string, any>,
  U extends keyof T & string
>(
  el: T,
  propNames: U[],
  string: string | null | undefined
) => {
  if (!string) return true
  for (const prop of propNames) {
    if (el[prop] === null || el[prop] === undefined) {
      continue
    } else if (el[prop]?.toLowerCase().includes(string.toLowerCase())) {
      return true
    }
  }
  return false
}
