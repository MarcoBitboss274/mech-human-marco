export const replace = <T>(
	arr: T[],
	indexOrIteratee:
		| number
		| ((
				value: (typeof arr)[number],
				index: number,
				array: typeof arr
		  ) => boolean),
	item: T
): T[] => {
	if (typeof indexOrIteratee === 'number') {
		return arr
			.slice(0, indexOrIteratee)
			.concat(item)
			.concat(arr.slice(indexOrIteratee + 1));
	} else {
		const index = arr.findIndex(indexOrIteratee);
		if (index !== -1) {
			return replace(arr, index, item);
		}
		return arr;
	}
};
