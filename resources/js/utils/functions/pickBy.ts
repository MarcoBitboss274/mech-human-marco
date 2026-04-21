export const pickBy = <T extends object>(
	obj: T,
	iteratee: (value: T[keyof T], key: keyof T, object: T) => boolean
): Partial<T> => {
	const accumulator: Partial<T> = {};
	for (const key in obj) {
		if (Object.prototype.hasOwnProperty.call(obj, key)) {
			const element = obj[key];
			if (iteratee(element, key, obj)) accumulator[key] = element;
		}
	}
	return accumulator;
};
