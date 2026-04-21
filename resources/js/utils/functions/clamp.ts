/**
 * Returns value ensuring it doesn't go below min and exceed max.
 * EXAMPLE: clamp(5, 7, 20) === 7
 * @param value The value to clamp
 * @param min Minimum bound
 * @param max Maximum bound
 * @returns
 */

export const clamp = (value: number, min: number, max: number) => {
	return Math.min(Math.max(value, min), max);
};
