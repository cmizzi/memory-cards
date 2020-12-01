import {debounce} from "./lib";

// Permet de savoir un rafraichissement du plateau est en attente.
let pendingRefresh = false;

// Interval de rafraichissement pour la progression du score en cours.
let progressInterval = null

/**
 * Cette méthode est appelée après chaque action utilisateur. Elle permet de convertir le nouveau plateau mis à jour
 * par le serveur en une modification du DOM.
 *
 * @param board
 */
const refresh = (data) => {
	const board  = data.board
	const parent = document.getElementById("board");

	for (const i in board) {
		const el   = parent.querySelector(`.card[card-id="${i}"]`)
		const card = board[i];
		const cls  = `card__card-${card.type}`;

		// On supprime toute erreur.
		el.classList.remove("failure");

		// Si la carte est révélée, alors on affiche le contenu.
		if (card.reveal) {
			el.querySelector(".card__card").classList.add(cls);
			el.querySelector(".card__card").innerHTML = "";
			el.setAttribute("card-reveal", "true");
		}

		// Sinon, on supprime l'affichage et on bascule sur un `?`
		else {
			el.querySelector(".card__card").classList = 'card__card';
			el.querySelector(".card__card").innerHTML = "?";
			el.setAttribute("card-reveal", "false");
		}
	}

	// La rafraichissement a eu lieu, on peut définir qu'aucun chargement n'est en attente.
	pendingRefresh = false;
};

/**
 * Cette définition nous permet simplement de définir qu'un rafraichissement ne sera fera au maximum qu'une seule
 * fois par seconde.
 *
 * @type {function(): void}
 */
const debounceRefresh = debounce(refresh, 1000);

/**
 * Cette méthode est appelée pour le premier rendu : il génère les différentes cartes et ajoute des événements aux
 * endroits désirés afin de pouvoir réagir aux actions utilisateurs.
 *
 * @param board
 */
const render = (data) => {
	const board    = data.board
	const template = document.getElementById("card");
	const parent   = document.getElementById("board");

	for (const i in board) {
		// Affichage des cartes.
		const el   = template.content.firstElementChild.cloneNode(true);
		const card = board[i];

		// Lors du premier rendu, nous n'avons aucune carte visible. Considérons qu'elles sont face cachée.
		el.querySelector(".card__card").innerHTML = "?";
		el.setAttribute("card-id", i);

		// Lors du clic sur chacune des cartes, ajoutons un événement afin de pouvoir émettre l'action au serveur.
		el.addEventListener("click", async function (ev) {
			if (pendingRefresh || ev.target.getAttribute("card-reveal") === "true") {
				return;
			}

			// On envoie l'action au serveur et attend la réponse.
			const data = await (await fetch("/api/game?action=reveal&with=" + i)).json()
			const cls = `card__card-${data.current_card}`;

			// On affiche la carte courante.
			el.querySelector(".card__card").classList.add(cls);
			el.querySelector(".card__card").innerHTML = "";

			// Indiquons qu'un rafraichissement doit être effectué.
			pendingRefresh = true;

			if (data.is_party_over && data.is_winner) {
				clearInterval(progressInterval);
				alert("You did it in " + data.score.score + " seconds!");

				return;
			}

			if (data.is_party_over) {
				clearInterval(progressInterval);
				alert("oh... the game is over. you exceed the time limit.");

				return;
			}

			// Si l'utilisateur n'a pas tourné la bonne carte, nous allons devoir effectuer quelques effets visuels
			// afin de lui indiquer que la suite a été interrompue.
			if (data.has_failed) {
				// Ajoutons une classe spécifique à la carte cliquée.
				el.classList.add("failure");

				// Pour chaque element dans le plateau, nous allons effectuer une différentiel avec le tableau que
				// nous à renvoyer le serveur. Cette opération a pour but de ne pas immédiatement appliquer le
				// nouvel état de jeu mais plutôt effectuer une transition afin de montrer à l'utilisateur les
				// cartes en erreur.
				for (const i in data.board) {
					const card = parent.querySelector(`.card[card-id="${i}"]`)

					// Si la carte a été retournée face cachée par le serveur, ajoutons une classe spécifique.
					if (card.getAttribute("card-reveal") === "true" && data.board[i].reveal === false) {
						card.classList.add("failure");
					}
				}

				// Nous n'allons actualiser le plateau qu'après 2 secondes.
				debounceRefresh(data);
			}

			// Si aucune erreur n'a été effectuée (suite ou début de suite), alors nous pouvons directement
			// appliquer le nouveau plateau.
			else {
				refresh(data);
			}
		});

		// On ajoute chaque enfant au parent.
		parent.appendChild(el);
	}

	let i = Date.now() / 1000 - data.started_at;
	const progress = document.querySelector("#progress");

	progress.setAttribute("max", data.max_score);
	progress.value = data.max_score - i;

	progressInterval = setInterval(() => {
		progress.value = data.max_score - i++;
	}, 1000);
}

window.onload = async () => {
	// Lors du chargement, nous ne souhaitons pas démarrer le jeu tout de suite. Un bouton est disponible afin de
	// charger le plateau.
	document.querySelector("#restart")
		.addEventListener("click", async (ev) => {
			// Le bouton de démarrage a été cliqué. Nous pouvons charger le jeu et le rendre.
			ev.target.setAttribute("x-cloak", "x-cloak");

			// Affichons l'indicateur de chargement.
			document.querySelector("#loading").removeAttribute("x-cloak");

			// On démarre le jeu. Nous n'avons pas besoin de récupérer l'intégralité de la réponse à ce moment donné, puisque
			// seul le plateau nous intéresse.
			const data = await (await fetch("/api/game?action=reset")).json();

			// On effectue le premier rendu.
			render(data);

			// On peut enlever l'indicateur, tout est chargé !
			document.querySelector("#loading").setAttribute("x-cloak", "x-cloak");
		});

	// On enlève l'indicateur de chargement.
	document.querySelector("#loading").setAttribute("x-cloak", "x-cloak");
}