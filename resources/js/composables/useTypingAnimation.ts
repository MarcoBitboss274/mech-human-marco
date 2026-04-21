import type { Ref } from 'vue';
import { onMounted, onUnmounted, ref } from 'vue';

// Define the return type of the composable
interface TypingAnimationComposable {
	displayText: Ref<string>;
}

export function useTypingAnimation(
	strings: string[],
	speed: number = 100 // Speed in ms per character
): TypingAnimationComposable {
	const displayText = ref(strings[0]); // Start with the first string fully typed
	const currentStringIndex = ref(0); // Index of the current string
	let isDeleting = false; // Track whether the animation is in the deleting phase
	let timeoutId: ReturnType<typeof setTimeout> | null = null; // For cancelling setTimeout

	// Get the common prefix between two strings
	const getCommonPrefix = (str1: string, str2: string): string => {
		let i = 0;
		while (i < str1.length && i < str2.length && str1[i] === str2[i]) {
			i++;
		}
		return str1.slice(0, i);
	};

	// Function to handle typing and deleting animation
	const typeText = (
		currentString: string,
		nextString: string,
		index: number = 0
	): void => {
		const commonPrefix = getCommonPrefix(currentString, nextString);

		if (!isDeleting) {
			// Typing forward (adding characters after the common prefix)
			if (index <= currentString.length) {
				displayText.value =
					commonPrefix + currentString.slice(commonPrefix.length, index);
				timeoutId = setTimeout(
					() => typeText(currentString, nextString, index + 1),
					speed
				);
			} else {
				// Pause before starting to delete characters
				setTimeout(() => {
					isDeleting = true;
					typeText(currentString, nextString, currentString.length);
				}, speed * 30); // Small pause before deleting
			}
		} else {
			// Deleting backward (removing characters until the common prefix)
			if (index >= commonPrefix.length) {
				displayText.value = currentString.slice(0, index);
				timeoutId = setTimeout(
					() => typeText(currentString, nextString, index - 1),
					speed
				);
			} else {
				// After deletion, move to the next string
				setTimeout(() => {
					isDeleting = false;
					currentStringIndex.value =
						(currentStringIndex.value + 1) % strings.length;
					typeText(
						strings[currentStringIndex.value],
						strings[(currentStringIndex.value + 1) % strings.length]
					);
				}, speed * 5); // Small pause before starting the next string
			}
		}
	};

	// Start the typing animation
	const startTyping = (): void => {
		// Begin deleting from the first string to transition to the second string
		const initialString = strings[currentStringIndex.value];
		const nextString = strings[(currentStringIndex.value + 1) % strings.length];
		setTimeout(() => {
			isDeleting = true;
			typeText(initialString, nextString, initialString.length); // Start by deleting the first string
		}, speed * 10); // Add a small pause before deleting the first string
	};

	// Setup hooks to start and stop animation
	onMounted(() => {
		startTyping(); // Start animation after the first string is displayed
	});

	onUnmounted(() => {
		if (timeoutId) {
			clearTimeout(timeoutId); // Cleanup on unmount
		}
	});

	return {
		displayText,
	};
}
