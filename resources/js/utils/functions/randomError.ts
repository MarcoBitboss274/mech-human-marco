export const randomError = (string: string) => {
  if (Math.random() < 0.5) {
    throw new Error(string)
  }
  return true
}
