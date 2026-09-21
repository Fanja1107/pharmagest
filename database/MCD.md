# MODÈLE CONCEPTUEL DE DONNÉES (MCD) — PharmaGest

## Entités et leurs propriétés

### UTILISATEUR
- id_utilisateur
- nom
- prenom
- email
- mot_de_passe
- role
- statut
- date_creation

### CATEGORIE
- id_categorie
- libelle
- description

### UNITE
- id_unite
- nom_unite
- symbole

### MEDICAMENT
- id_medicament
- reference
- nom
- dosage
- forme
- description
- seuil_alerte
- statut
- date_creation

### CONDITIONNEMENT
- id_conditionnement
- libelle
- quantite_base
- prix_vente
- vendable
- achetable
- statut

### FOURNISSEUR
- id_fournisseur
- raison_sociale
- telephone
- email
- adresse
- nif
- stat
- statut
- date_creation

### LOT
- id_lot
- numero_lot
- date_fabrication
- date_expiration
- quantite_base
- prix_achat_base
- statut

### APPROVISIONNEMENT
- id_approvisionnement
- numero_appro
- date_approvisionnement
- total_achat
- statut

### CLIENT
- id_client
- nom
- prenom
- telephone
- email
- adresse
- date_creation

### VENTE
- id_vente
- numero_vente
- date_vente
- total
- statut
- mode_paiement

## Associations et cardinalités