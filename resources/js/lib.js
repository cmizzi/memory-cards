/**
 * Cette fonction magique est un helper. Elle nous permet de n'exécuter une fonction une seule fois pour un interval
 * de temps donné.
 *
 * @param func
 * @param wait
 * @param immediate
 * @returns {function(): void}
 */
export function debounce(func, wait, immediate) {
	let timeout;

	return function() {
		const context = this, args = arguments;
		const later = function() {
			timeout = null;

			if (!immediate) {
				func.apply(context, args);
			}
		};

		let callNow = immediate && !timeout;
		clearTimeout(timeout);
		timeout = setTimeout(later, wait);

		if (callNow) {
			func.apply(context, args);
		}
	};
}

