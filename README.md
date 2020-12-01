# Mémorisation de cartes

Implémentation simple d'un jeu de mémorisation de cartes.

## Les règles

1. 9 paires de cartes sont posées sur le plateau de façon aléatoire ;
2. Le but du jeu est de retrouver toutes les paires dans le temps imparti ;

## Implémentation

### Serveur autoritaire

L'objectif de ce projet n'est pas de prévenir de la triche, mais nous pouvons tout de même y réfléchir dans notre
implémentation. Dès lors que l'on parle d'échanger une information comparative avec d'autres joueurs (comme ici, les
scores), nous ne pouvons pas faire confiance au client qui envoie les données. En effet, il serait très simple à un
robot ou un humain d'envoyer une requête au serveur indiquant un score complètement erroné, poussant en tête le score
du joueur malicieux. Pour éviter ce genre de pratique, il est très souvent utile que le code exécutant les actions
soit uniquement réalisé sur un serveur (les utilisateurs ne peuvent pas modifier le code présent sur ce serveur). Nous
n'allons pas pousser le vice très loin dans l'implémentation, et gardez à l'esprit qu'il sera toujours possible de
tricher, cependant notre objectif ici est de limité au minimum les cas typiques de triche : exécution de code aléatoire
sur le client, permettant d'envoyer des informations hors de contrôle en base de données.

Voici donc ce qu'est un serveur autoritaire : il s'agit du code logique qui répondra aux actions utilisateur et
génèrera l'état du jeu après chaque action réalisée. A chaque action, le client n'aura pour but que de ne réagir au
nouvel état envoyé par le serveur (retourner une carte, etc.).

Pour plus d'informations à ce sujet, n'hésitez pas à Googliser ou bien lire quelques articles :

- https://www.gabrielgambetta.com/client-server-game-architecture.html

Bien sûr, nous ne développons pas un jeu vidéo complètement génial dans lequel nous souhaitons prévenir tous les cas
de triche possible (ce qui demanderait beaucoup, beaucoup de temps). Cependant, et vous pourrez le constater sur le code
proposé, il sera toujours possible de tricher : en effet, les requêtes envoyées du client vers le serveur ne sont pas
limitée à une utilisation unique par Javascript : elles peuvent être effectuées depuis n'importe quel logiciel. De ce
fait, rien n'empêche un humain d'envoyer les requêtes manuellement (sans ouvrir son navigateur et cliquer sur les
cartes) afin de résoudre le puzzle. Cependant, gardez en tête que des techniques existent afin de prévenir ce genre de
cas.

#### Comment ça marche ?

Le code client (Javascript) est exécuté dans le navigateur. Il permet, dans notre cas précis, de réagir à deux actions
que l'utilisateur (vous, derrière votre écran) peut émettre :

- ré-initialiser le jeu ;
- retourner une carte ;

Dès lors, une requête HTTP sera envoyée au serveur afin qu'il puisse calculer le nouveau plateau et renvoyer la réponse
au client afin qu'il puisse visuellement mettre à jour les informations. Essayons de schematiser vulgairement la
procédure :

```
- un utilisateur clique sur le bouton « ré-initialiser »
   - HTTP/1.0 GET /api/game?action=reset ;
   - rendu du nouveau plateau ;
   
- un utilisateur clique sur une carte face cachée
   - HTTP/1.0 GET /api/game?action=reveal&with=4 ;
   - rendu du nouveau plateau ;
```

Pour le moment, il n'est pas nécessaire d'avoir une très bonne connaissance en HTTP pour comprendre ce qu'il se passe.
En tous les cas, il faut correctement comprendre le fait que pour chaque action, une requête HTTP est émise au serveur
et celui-ci retourne un nouvel état de jeu, utilisable par le client, afin d'indiquer au joueur l'exécution de ses
actions.

### Implémentation

#### Client

Le client est implémenté via de l'HTML, CSS et JS. Deux pages sont disponibles :

- la page d'accueil, permettant de visualiser les derniers scores ;
- la page de jeu ;

La page d'accueil se veut très simple : elle ne récupère que les derniers top-score réalisés et les affiche. Elle
propose aussi un lien permettant de démarrer une nouvelle partie. La page de jeu est un peu plus complexe : cette page
gère les interactions entre les actions utilisateurs vers le serveur (et vice-versa). Le rendu de cette page est donc
totalement dynamique :

1. On charge l'HTML, CSS et JS ;
2. On envoie une requête au serveur pour démarrer un nouveau jeu ;
3. On reçoit la réponse du serveur avec l'état du jeu de départ ;
4. On réagit aux actions utilisateur et envoie les actions au serveur ;
5. On reçoit les réponses et faire réagir le rendu ;

#### Serveur

##### Requête HTTP

Le serveur s'attend à recevoir des requêtes du client. Une entrée spécifique y est donc dédiée : `/api/game`. Celle-ci
se structure comme ci-dessous :

```
/api/game?action=ACTION_NAME
/api/game?action=ACTION_NAME&with=ARGUMENT
```

Typiquement, l'action `reset` n'a pas besoin d'argument, alors `with` n'est pas obligatoire. L'action `reveal`, quant
à celle a besoin d'un argument, à savoir l'identifiant de la carte à retourner. Un système de routage interne permet
définir des entrées et de réagir en fonction de.

##### Structure du code

```
app/ -> contient le code du serveur
  Game.php -> contient la logique pour convertir une requête d'un client à une action spécifique du jeu
  GameState.php -> contient la logique du jeu
```

Les autres dossiers servent à ranger les différents outils auxquels nous avons besoin afin de simplifier et découper
toute la logique de notre code. Par exemple, le dossier `app/Exceptions` contient toutes les exceptions liées au jeu. Le
dossier `app/Models` contient la logique lié à la base de données : en effet, nous avons besoin de stocker les temps
de résolution de chaque joueur ayant remporté la manche. Pour ce faire, et plutôt que de devoir travailler de partout
avec des requêtes SQL, convertir les informations (etc.), l'utilisation de modèles permet de n'avoir qu'une source de
vérité sur l'utilisation des informations récupérer/à envoyer à la base de données. Voyez ça comme un système modulaire
permettant de faciliter les accès à la base de données. Les dossiers contenus `app/Http` sont propres aux requêtes HTTP : 

- quelle action effectuer sur une entrée spécifique (`app/Http/Controllers`) ;
- comment envoyer une réponse à un client (`app/Http/Response.php`) ;

Toute la logique HTTP est utilisée au travers du fichier `public/index.php`, qui est le point de départ de toute notre
application.

##### Implémentation

Le jeu est généré via la classe `App\GameState`. Il est sauvegardé dans la session depuis la classe `App\Game`, qui est
une sorte de glue entre les intéractions utilisateur et le code logique exécuté côté serveur.

Lors de la création d'un nouveau jeu (action `reset`), nous effectuons plusieurs actions :

1. Générons une liste de 9 entrées (la clef correspond à l'index de la carte, et la valeur au type unique - citron,
   fruit, etc. sout forme de chiffre);
2. Dupliquons la liste sur elle-même afin d'obtenir les 18 entrées nécessaires (9 uniques * 2) ;
3. Trions de manière aléatoire la liste afin que les cartes ne soient pas toujours placées aux mêmes endroits ;

Afin d'illustrer ça, voici un peu plus de détails :

```
-> Génération des cartes uniques
[
	0  => 0, // citron
	1  => 1, // fraise
	2  => 2, // ...
	3  => 3,
	4  => 4,
	5  => 5,
	6  => 6,
	7  => 7,
	8  => 8,
]

-> Dupliquer l'échantillonage afin d'obtenir une liste de 9 * 2 entrées

[
	0  => 0, // citron
	1  => 1, // fraise
	2  => 2, // ...
	3  => 3,
	4  => 4,
	5  => 5,
	6  => 6,
	7  => 7,
	8  => 8,
	9  => 0, // citron
	10 => 1, // fraise
	11 => 2, // ...
	12 => 3,
	13 => 4,
	14 => 5,
	15 => 6,
	16 => 7,
	17 => 8,
]

-> Tri aléatoire

[
	0  => 3,
	1  => 1, // fraise
	2  => 0, // citron
	3  => 8,
	4  => 5,
	5  => 2,
	6  => 7,
	7  => 8,
	8  => 4,
	9  => 6,
	10 => 2,
	11 => 5,
	12 => 4,
	13 => 7,
	14 => 0, // citron
	15 => 3,
	16 => 6,
	17 => 1, // fraise
]
```

Cependant, la structure du tableau est un peu plus complexe que ça. En effet, d'un point de vu du serveur, il doit
toujours être capable de savoir quelle carte a déjà été retournée face visible sur le tapis, et lesquelles sont encore
face cachée. Alors, le tableau ne contient pas seulement un nombre pour définir le type de la carte, mais plutôt un
sous-tableau correspondant à des metadonnées :

```
[
	"type"   => 0, // citron
	"reveal" => false, // La carte est face cachée
]
```

Nous travaillerons donc réellement avec un tableau de cette envergure :

```
[
	0  => ["type" => 3, "reveal" => false],
	1  => ["type" => 1, "reveal" => false], // fraise
	2  => ["type" => 0, "reveal" => false], // citron
	3  => ["type" => 8, "reveal" => false],
	4  => ["type" => 5, "reveal" => false],
	5  => ["type" => 2, "reveal" => false],
	6  => ["type" => 7, "reveal" => false],
	7  => ["type" => 8, "reveal" => false],
	8  => ["type" => 4, "reveal" => false],
	9  => ["type" => 6, "reveal" => false],
	10 => ["type" => 2, "reveal" => false],
	11 => ["type" => 5, "reveal" => false],
	12 => ["type" => 4, "reveal" => false],
	13 => ["type" => 7, "reveal" => false],
	14 => ["type" => 0, "reveal" => false], // citron
	15 => ["type" => 3, "reveal" => false],
	16 => ["type" => 6, "reveal" => false],
	17 => ["type" => 1, "reveal" => false], // fraise
]
```

Grâce à ça, nous avons tout entre nos mains afin que le jeu puisse se passer dans les meilleures conditions. Nous
pouvons itérer de bout en bout le tableau afin de savoir quel type de carte n'a pas encore été trouvé, quelles cartes
sont visibles pour l'utilisateur, etc. Bien sûr, d'autres notions sont disponibles dans l'état de jeu :

- la date et le temps à laquelle l'utilisateur a commencé sa partie ;
- la suite en cours (si l'utilisateur a déjà retourné une carte et qu'il retourne une carte d'un autre type, la suite
  n'est pas respectée) ;
- le type de la carte que le joueur vient de retourner (purement visuel, permet d'afficher la carte retournée par
  l'utilisateur si la suite n'est pas respectée) ;
  
D'autres informations y sont stockées comme un bool permettant de savoir si la partie est gagné, si la partie est perdue
(le temps limite est atteint), etc. Ne prenons pas peur, n'hésitez pas à regarder la structure du fichier
`App/GameState` afin d'y comprendre un peu mieux l'implémentation. Le code, dans son implémentation intègre des notions
avancée et générique sur la gestion du jeu : si vous modifiez les valeurs de `MAX_DISTINCT_CARDS`, le plateau généré
prendra en compte cette modification et le client dessinera plus de cartes à jouer.

#### Tests

La quasi-totalité du code est testé. Si vous modifiez le comportement d'une fonction, il faudra bien entendu modifier le
test associé afin que tout reste dans le vert. Le test unitaire permet de vérifier la validité des règles du jeu sans
action manuelle (pas la peine d'ouvrir le navigateur et de faire des tests manuellement pour vérifier tout ça). Toute la
structure des tests est placée dans le dossier `tests`. Vous pouvez essayer de lancer les tests sur votre machine :

```bash
./vendor/bin/phpunit
```

Les tests ont été implémentés de façon à couvrir le maximum de détail possible.

- Quel comportement est attendu si une suite est en cours et que la carte retournée n'est pas du même type ?
- Quel comportement est attendu toutes les cartes ont été trouvées ?
- ...

N'hésitez pas à y faire un tour.

## Installation

La version 8 de PHP est exigée pour ce projet. En effet, il est toujours bon d'être à jour et de bénéficier des
dernières fonctionnalités proposées par le language. De plus, `composer` ainsi que `yarn` sont les outils à tour faire,
permettant de gérer les dépendances PHP et les dépendances JS (respectivement). Je vous
recommande vivement d'aller voir les documentations officielles si vous ne savez pas comment les installer.

Une fois votre environnement en place, il n'y a plus qu'à installer tout ça :

```bash
composer install
yarn build
```

A partir de ce point, le projet devrait être entièrement fonctionnel. Il ne reste plus qu'à configurer votre serveur PHP
afin de résoudre le projet.

## Exercices

Histoire que ce soit plus fun, voici quelques propositions de modification :

- afficher de manière dynamique les meilleurs scores (pagination ou scroll infini) ;
- supprimer les `alert` lors de victoire ou défaite et mettre en place des modales, avec des boutons de retour ;
- permettre de jouer avec plus de 2 cartes uniques (par exemple, trouver 3, 4 ou `n` cartes du même type) ;
- permettre d'enregistrer un nom dès que la partie est terminée afin d'ajouter cette information dans le tableau des
  scores ;