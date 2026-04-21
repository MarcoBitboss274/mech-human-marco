/** Wait until iteratee returns true to resolve the promise */
export const waitFor: (
	iteratee: () => boolean,
	interval?: number
) => Promise<void> = (iteratee, interval = 50) =>
	new Promise((resolve) => {
		const i = setInterval(() => {
			if (iteratee()) {
				clearInterval(i);
				resolve();
			}
		}, interval);
	});
