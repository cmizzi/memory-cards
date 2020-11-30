export default async function scoreboard() {
	// Fetch the latest scores from the API.
	const {scores} = await (await fetch("/api/scores")).json()

	// Get the scoreboard element (using id) and fetch the `tbody` node element.
	const el = document.querySelector("#scoreboard tbody")

	// Fetch the template that we'll use to generate each entry.
	const template = document.querySelector("#scoreboard-row")

	// Parse each score
	for (const key in Object.keys(scores)) {
		// Clone the template and select all td available.
		const row = template.content.cloneNode(true)
		const children = row.querySelectorAll("td");

		children[0].textContent = +key + 1
		children[1].textContent = scores[key].name
		children[2].textContent = scores[key].score

		// Append the template into the scoreboard.
		el.appendChild(row)
	}

	document.querySelector("#scoreboard").removeAttribute("x-cloak");
	document.querySelector("#loading").setAttribute("x-cloak", "x-cloak");
}