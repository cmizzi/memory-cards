# Mémorisation de cartes

Implémentation simple d'un jeu de mémorisation de cartes.

## Les règles

1. 18 paires sont présentes sur le tapis ;
2. Le joueur doit trouver chaque paire avant le temps imparti (5 minutes) ;
3. Si la paire n'est pas trouvée, alors les deux cartes jouées sont retournées face cachée ;
3. En cas de victoire, le temps doit être sauvegardé dans le tableau des scores, afin d'être partagé avec les autres
   joueurs ;

## Implémentation

### Serveur autoritaire

Lorsque l'on développe un jeu vidéo, et dès lors que l'on ne souhaite pas que les scores puissent être modifié par un
robot/humain, les actions effectuées par le joueur ne doivent pas être exécutées côté client, mais côté serveur.

1. L'utilisateur effectue une action (cliquer sur une carte, lancer une nouvelle partie, etc.) et envoie l'information
   au serveur ;
2. Le serveur reçoit l'information et effectue l'action, puis renvoie l'état actuel du jeu au joueur ;

De cette manière, il est impossible pour un utilisateur d'envoyer au serveur un temps de résolution aléatoire.

De très bonnes ressources existent sur Internet afin de mieux comprendre l'intérêt d'un serveur autoritaire, et je vous
invite à lire quelques articles à ce sujet :

- https://www.gabrielgambetta.com/client-server-game-architecture.html ;
- https://gamedev.stackexchange.com/questions/131698/best-approach-to-implement-server-authority-in-client-server-game ;

### Client de jeu

Le client est entièrement écrit en HTML, CSS et JS. Il permet l'interaction, le rendu dans le navigateur. Lorsque
l'utilisateur clique sur le bouton « recommencer », alors l'action `reset` sera envoyée au serveur. Dès lors qu'un
utilisateur cliquera sur une carte afin de la retourner, l'action `reveal` sera envoyée. Le client ne s'occupe que
rendre l'état du jeu en cours (renvoyé par le serveur entre chaque action) et d'envoyer les différentes actions
réalisées par l'utilisateur au serveur.

### Serveur de jeu

Le serveur de jeu est entièrement développé en PHP. Puisque le serveur est autoritaire, il faut un moyen au serveur de
reconnaître un utilisateur pour chaque action. Pour ce faire, nous allons utiliser les sessions. Chaque session est
unique par utilisateur et ce comportement est géré nativement par PHP dès lors que la fonction `session_start` est
appelée. Pour que le jeu puisse fonctionner, deux actions sont à implémenter :

- `reset` : permet de redémarrer une instance de jeu (recommencer) ;
- `reveal` : permet de retourner une carte ;

#### Nouvelle partie

Dès lors qu'un utilisateur arrive sur le jeu et souhaite commencer une partie,  l'action `reset` est envoyée au serveur.
Celui-ci créé alors un nouvel état de jeu :

- 18 cartes uniques sont disponibles et doublées afin d'obtenir 2 paires uniques (total de 36 cartes) ;
- les cartes sont mélangées aléatoirement ;
- la planche de jeu est envoyée au client ;

#### Retourner une carte

Lorsque la planche est reçue par le client, il est désormais possible de retourner une carte : lorsque l'utilisateur
clique sur une carte face cachée, l'action `reveal` est envoyée au serveur, contenant l'identifiant de la carte. Deux
cas sont alors possibles :

1. Aucune suite n'est en cours (aucune carte n'est en attente de trouver sa paire) ;
2. Une suite est en cours : une carte a déjà été retournée et attend de trouver sa paire ;

Dans le premier cas, le serveur devra retourner la carte. Dans le second cas, il faut vérifier que la carte retournée
est du même type que la carte retournée précédemment :

- si les deux cartes sont les mêmes, alors la carte jouée peut-être retournée et validée ;
- si les deux cartes ne sont pas du même type, alors la carte jouée ainsi que la carte précédente sont retournées face
  cachée.

Cependant, pour faciliter l'approche du jeu, même si la deuxième carte jouée n'est pas du même type que la première,
alors avant de retourner les cartes, le serveur renverra tout de même le type de la carte jouée afin de pouvoir
visualiser de quel type est la carte.

#### Fin de partie

La partie prend fin lorsque :

- toutes les paires ont été trouvées ;
- le temps imparti est dépassé ;

Dans les deux cas, le serveur renverra l'information (`winner` ou `party_over`) afin que le client puisse réagir de
différentes manières en cas de victoire ou de défaite.

### Communication

Pour réaliser une communication entre le client et le serveur, 3 entrées HTTP doivent être déclarées :

- `POST /api/game` : permet d'envoyer au serveur une action client ;
- `GET /api/scores` : permet de récupérer les meilleurs scores ;
- `POST /api/scores` : permet de sauvegarder le temps stocké côté serveur dans la base de données ;

### Base de données

Afin de pouvoir stocker et lire les différents temps de résolution de chaque utilisateur, nous avons besoin d'une base
de données. Il en existe plusieurs, mais pour nos besoins, nous utiliserons MySQL qui se veut légère et rapide. Nous
aurons aussi besoin d'une seule table `scores`, qui sera définie comme suivant :

```
id         : identifiant unique ;
username   : nom de l'utilisateur (optionnel) ;
score      : le temps (en seconde) de résolution du puzzle ;
created_at : la date d'enregistrement ;
```

Ces données ne peuvent pas être éditées, ni supprimées par le client.