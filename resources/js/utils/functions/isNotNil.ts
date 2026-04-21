/**
 * Returns true if the item is not null or undefined
 * @param value The item to check
 * @returns true | false
 */
export const isNotNil = <T>(
	value: T
): value is Exclude<T, null | undefined> => {
	if (typeof value === 'undefined') return false;
	if (value === null) return false;
	return true;
};
