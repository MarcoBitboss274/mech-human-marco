/** Wait a specified amount of time before resolving a promise */
export const wait: (ms: number) => Promise<void> = (ms) =>
	new Promise((resolve) => setTimeout(resolve, ms));
