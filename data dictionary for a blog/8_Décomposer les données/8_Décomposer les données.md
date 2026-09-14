# Analyser les donnees :

COMMANDE(
    numero_commande,
    date_commande,
    nom_client,
    email_client,
    nom_produit,
    prix_produit,
    quantite_commandee
)
Observing :

| **numero_commande** | **date_commande** | **nom_client** | **email_client** | **nom_produit** | **prix_produit** | **quantite_commandee** |
| ------------------- | ----------------- | -------------- | ---------------- | --------------- | ---------------- | ---------------------- |
| C001                | 10/09/2026        | Madani Ali     | madani@mail.com  | Clavier         | 200              | 2                      |
| C001                | 10/09/2026        | Madani Ali     | madani@mail.com  | Souris          | 100              | 1                      |
| C002                | 11/09/2026        | Sara Amrani    | sara@mail.com    | Clavier         | 200              | 3                      |


we can recognize that client name and clien_email are dependences that are dependent on the client so we can kick them out of the article table and add to them an identifier (id_clien).


Client (
	id_client ,
	nom_client ,
	email_client ,
)

same thing for the name and price of the product :

Produit(
	id_produit,
	nom_produit,
	prix_produit
)

and so the table of Commande will become (we need to add identifier for the commandes) :

Commande(
	id_commande,
	numero_commande,
    date_commande,
    quantite_commandee,
    id_client,
	id_commande
)

## Commandes table:

| id_commande | numero_commande | date_commande | quantite_commande | id_client | id_produit |
| ----------- | --------------- | ------------- | ----------------- | --------- | ---------- |
| 1           | C001            | 10/09/2026    | 2                 | 1         | 1          |
| 2           | C002            | 10/09/2026    | 1                 | 1         | 2          |
| 3           | C003            | 11/09/2026    | 3                 | 2         | 1          |

## clien table :

| id_clien | nom_client  | email_client    |
| -------- | ----------- | --------------- |
| 1        | Madani Ali  | madani@mail.com |
| 2        | Sara Amrani | sara@mail.com   |

## product table:

| id_produit | nom_produit | prix_produit |
| ---------- | ----------- | ------------ |
| 1          | Clavier     | 200          |
| 2          | Souris      | 100          |