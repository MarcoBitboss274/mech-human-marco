import { hash } from "object-code";

export function isEqual(a: any, b: any) {
  return hash(a) === hash(b)
}
