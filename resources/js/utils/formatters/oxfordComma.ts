type Clause = ((...args: any[]) => boolean) | boolean | string;

export const oxfordComma = (items: [Clause, string][] | string[]) => {
	const mapped = items.reduce((acc, curr) => {
		let clause: Clause | undefined;
		let string: string | undefined;
		if (Array.isArray(curr)) {
			clause = curr[0];
			string = curr[1];
		} else if (typeof curr === 'string') {
			string = curr;
		}
		if (clause && typeof clause === 'function' && string) {
			if (clause()) {
				acc.push(string);
			}
		} else if (clause && typeof clause === 'boolean' && string) {
			acc.push(string);
		} else if (string && typeof string === 'string') {
			acc.push(string);
		}
		return acc;
	}, [] as string[]);

	return mapped.length === 2
		? mapped.join(' e ')
		: mapped.length > 2
			? mapped
					.slice(0, mapped.length - 1)
					.concat(`e ${mapped.slice(-1)}`)
					.join(', ')
			: mapped.join(', ');
};
