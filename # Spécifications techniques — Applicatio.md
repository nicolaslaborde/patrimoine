# Spécifications techniques — Application PHP de gestion de patrimoine personnel

## 1. Objectif général

Créer une application web auto-hébergée permettant de gérer le patrimoine personnel d’un ou plusieurs utilisateurs.

L’application doit permettre :

- de se connecter avec un login et un mot de passe ;
- de créer plusieurs utilisateurs ;
- d’associer à chaque utilisateur un fichier patrimoine JSON distinct ;
- de gérer un patrimoine complet par grandes rubriques ;
- d’ajouter, modifier, supprimer et consulter chaque fiche patrimoniale ;
- de distinguer les informations minimales nécessaires au bilan patrimonial des informations détaillées servant de mémo ;
- de stocker les données dans des fichiers JSON locaux ;
- de mettre à jour le fichier JSON immédiatement après chaque modification ;
- d’afficher un tableau de bord consolidé ;
- de générer une synthèse globale du patrimoine ;
- de signaler les informations manquantes du niveau 1 ;
- d’ajouter plusieurs liens web à chaque fiche.

L’application doit fonctionner sur un hébergement mutualisé IONOS classique, sans Node.js.

---

## 2. Technologies

Technologies utilisées :

```text
PHP 7.4+ / PHP 8+
HTML5
CSS3
JavaScript vanilla
JSON local
Sessions PHP
```

Ne pas utiliser :

```text
Node.js
Express
MongoDB
Base SQL obligatoire
Framework lourd
Composer obligatoire
```

L’application doit pouvoir être envoyée par FTP sur un hébergement IONOS.

---

## 3. Structure du projet

```text
/patrimoine
  index.php
  login.php
  logout.php
  dashboard.php
  rubrique.php
  form.php
  synthese.php
  liquidites.php
  utilisateurs.php
  diagnostic.php
  README.md

  /api
    patrimoine.php
    dashboard.php
    rubrique.php
    profil.php
    missing.php
    export-json.php
    export-csv.php

  /includes
    config.php
    auth.php
    storage.php
    calculations.php
    schema.php
    helpers.php
    layout.php

  /assets
    /css
      style.css
    /js
      app.js
      forms.js

  /data
    users.json
    patrimoine.json
    patrimoine-{utilisateur}.json
    .htaccess
    /backups
```

---

## 4. Authentification et utilisateurs

Au premier lancement, créer automatiquement l’utilisateur administrateur initial :

```text
user : nicolas
password : change-me-before-deploy
role : admin
```

Le mot de passe ne doit jamais être stocké en clair.

Utiliser :

```php
password_hash()
password_verify()
session_regenerate_id(true)
```

Fichier utilisateur :

```text
/data/users.json
```

Structure :

```json
[
  {
    "id": "user-1",
    "username": "nicolas",
    "passwordHash": "HASH",
    "role": "admin",
    "patrimoineFile": "patrimoine.json",
    "createdAt": "2026-06-01T00:00:00+02:00"
  }
]
```

Un administrateur doit pouvoir créer un nouvel utilisateur depuis la page :

```text
utilisateurs.php
```

Chaque nouvel utilisateur doit recevoir un fichier patrimoine dédié :

```text
/data/patrimoine-{username}.json
```

Exemples :

```text
nicolas -> /data/patrimoine.json
paul    -> /data/patrimoine-paul.json
marie   -> /data/patrimoine-marie.json
```

À la connexion, l’application doit charger automatiquement le fichier patrimoine associé à l’utilisateur connecté.

---

## 5. Protection du dossier data

Créer :

```text
/data/.htaccess
```

Contenu :

```apache
Require all denied
```

Le navigateur ne doit pas pouvoir accéder directement à :

```text
/data/users.json
/data/patrimoine.json
/data/patrimoine-{utilisateur}.json
```

---

## 6. Stockage JSON

Chaque fichier patrimoine doit être en version 2.0.

Structure initiale :

```json
{
  "metadata": {
    "version": "2.0",
    "createdAt": "",
    "updatedAt": "",
    "owner": ""
  },
  "profil": {
    "nomDossier": "",
    "typeFoyer": "",
    "situationFamiliale": "",
    "regimeMatrimonial": "",
    "nombreEnfants": null,
    "personnesACharge": "",
    "objectifs": "Suivi et consolidation du patrimoine personnel",
    "commentaires": "",
    "liens": []
  },
  "revenus": [],
  "dettesCredits": [],
  "chargesAnnuelles": [],
  "immobilier": [],
  "mobilierFinancier": [],
  "fiscalite": [],
  "successionTransmission": [],
  "documents": [],
  "alertes": []
}
```

L’écriture doit être atomique :

1. écrire dans un fichier temporaire ;
2. remplacer le JSON cible.

Avant chaque modification, créer une sauvegarde dans :

```text
/data/backups/
```

---

## 7. Grandes rubriques

L’application affiche les grandes rubriques suivantes :

```text
Profil
Revenus
Dettes et crédits
Charges annuelles
Immobilier
Mobilier et financier
Fiscalité
Succession et transmission
Documents
Alertes
```

Chaque grande rubrique peut contenir plusieurs fiches.

---

## 8. Structure commune d’une fiche

Chaque fiche doit respecter la structure :

```json
{
  "id": "uuid",
  "rubrique": "immobilier",
  "type": "Résidence principale",
  "titre": "",
  "createdAt": "",
  "updatedAt": "",
  "niveau1": {},
  "niveau2": {},
  "liens": [],
  "commentaire": ""
}
```

Explication :

```text
id          identifiant unique
rubrique    grande rubrique
type        type précis de fiche
titre       libellé affiché dans les tableaux
niveau1     informations minimales nécessaires au bilan patrimonial
niveau2     informations détaillées servant de mémo
liens       liens web associés à la fiche
commentaire commentaire libre général
```

---

## 9. Formulaires

Chaque formulaire doit être généré depuis :

```text
/includes/schema.php
```

Chaque formulaire contient :

```text
Type de fiche
Titre de la fiche
1. Informations minimales pour le bilan patrimonial
2. Informations détaillées et mémo
Liens web multiples
Commentaire général
Enregistrer
Annuler
```

Le niveau 1 est ouvert par défaut.
Le niveau 2 et les liens peuvent être affichés dans des blocs repliables.

Les champs obligatoires doivent être marqués par un astérisque rouge.

Les champs avec réponses attendues doivent être affichés en listes déroulantes.

---

## 10. Liens web multiples

Chaque fiche doit permettre d’ajouter plusieurs liens web.

Structure :

```json
"liens": [
  {
    "url": "https://exemple.fr",
    "description": "Espace client",
    "createdAt": "2026-06-01T00:00:00+02:00"
  }
]
```

Validation :

```text
L’URL doit commencer par http:// ou https://
La description est facultative
```

Dans les tableaux, les liens doivent être cliquables et ouvrir un nouvel onglet :

```html
target="_blank" rel="noopener noreferrer"
```

---

## 11. Montants périodiques

Les champs de flux financiers récurrents doivent pouvoir être saisis en :

```text
Mensuel
Trimestriel
Annuel
```

Le formulaire affiche :

```text
Montant saisi
Périodicité
Montant mensuel calculé
Montant annuel calculé
```

Règles :

```text
mensuel      -> annuel = montant × 12
trimestriel -> annuel = montant × 4
annuel       -> mensuel = montant ÷ 12
trimestriel -> mensuel = montant ÷ 3
```

Stockage :

```json
"montant": {
  "valeurSaisie": 1200,
  "periodicite": "mensuel",
  "mensuel": 1200,
  "annuel": 14400
}
```

Pour faciliter les calculs, renseigner aussi :

```json
"montantMensuel": 1200,
"montantAnnuel": 14400
```

---

## 12. Champs par rubrique

### 12.1 Profil

Niveau 1 :

```text
nomDossier
typeFoyer
situationFamiliale
regimeMatrimonial
nombreEnfants
```

Niveau 2 :

```text
personnesACharge
objectifs
commentaires
```

Champs obligatoires pour consolidation :

```text
typeFoyer
situationFamiliale
```

---

### 12.2 Revenus

Types :

```text
Salaire
Retraite
Rente
Pension
Allocation
Revenu indépendant
Autre revenu
```

Niveau 1 :

```text
type
titre
organismePayeur
bienImmobilierAssocie
montant
montantMensuel
montantAnnuel
fiscalise
```

Le champ `bienImmobilierAssocie` doit être une liste des titres des fiches Immobilier + Autre.

Niveau 2 :

```text
dateDebut
dateFin
reversionPossible
conditions
```

Champs obligatoires :

```text
type
titre
montantAnnuel
```

---

### 12.3 Dettes et crédits

Types :

```text
Crédit immobilier
Crédit consommation
Prêt familial
Dette fiscale
Dette personnelle
Découvert bancaire
Autre dette
```

Niveau 1 :

```text
type
titre
creancier
capitalRestantDu
mensualite
mensualiteMensuelle
mensualiteAnnuelle
tauxInteret
dateFin
```

Niveau 2 :

```text
capitalInitial
dateDebut
dureeRestanteMois
assuranceEmprunteur
assuranceEmprunteurMensuelle
assuranceEmprunteurAnnuelle
remboursementAnticipePossible
bienAssocie
conditionsParticulieres
```

Champs obligatoires :

```text
type
titre
capitalRestantDu
mensualiteAnnuelle
```

---

### 12.4 Charges annuelles

Types/familles :

```text
Immobilier
Véhicules
Assurances
Santé
Vie courante
Famille
Impôts
Crédits
Banque / placements
Professionnel / SCI
Charges exceptionnelles
Autres charges
```

Niveau 1 :

```text
type
titre
categorieDetaillee
bienConcerne
montant
montantMensuel
montantAnnuel
payeur
deductibleFiscalement
recuperableLocataire
obligatoire
```

Le champ `bienConcerne` doit être une liste des titres des fiches Immobilier + Autre.

Le champ `categorieDetaillee` contient notamment :

```text
Taxe foncière
Assurance habitation
Charges de copropriété
Eau
Électricité
Gaz
Internet / téléphone
Assurance PNO
Frais de gestion locative
Assurance véhicule
Carburant
Mutuelle santé
Courses alimentaires
Impôt sur le revenu
Prélèvements sociaux
Mensualité prêt immobilier
Frais bancaires
Comptable
Travaux prévus
Imprévus
Autre
```

Niveau 2 :

```text
fournisseur
organisme
dateEcheance
modePaiement
contratReference
contratAssocie
justificatif
```

Champs obligatoires :

```text
type
titre
montantAnnuel
```

Dans la vue Charges annuelles, afficher :

```text
Type
Titre
Catégorie
Bien concerné
Coût/mois
Coût/an
Liens
Commentaire
```

Ne pas afficher :

```text
Valeur
Revenu/an
Dette
```

Ajouter une ligne Total avec :

```text
Total coût/mois
Total coût/an
```

---

### 12.5 Immobilier

Types :

```text
Résidence principale
Résidence secondaire
Bien locatif nu
Bien locatif meublé
Garage
Terrain
Local commercial
Parts de SCI immobilière
Autre bien immobilier
```

Niveau 1 :

```text
type
titre
adresse
valeurActuelle
modeDetention
quotePartDetenue
creditAssocie
revenuAssocie
chargeAssociee
```

Niveau 2 :

```text
surfaceM2
dateAcquisition
prixAchat
fraisNotaire
travauxRealises
loyer
loyerMensuel
loyerAnnuel
chargesNonRecuperables
taxeFonciere
assurancePNO
regimeFiscal
locataireActuel
dateDebutBail
dateFinBail
depotGarantie
```

Champs obligatoires :

```text
type
titre
valeurActuelle
quotePartDetenue
```

Dans la vue Immobilier, `Revenu/an` doit être calculé ainsi :

1. additionner les fiches Revenus dont `bienImmobilierAssocie` correspond au titre du bien ;
2. si aucun revenu associé n’existe, utiliser le loyer mémo de la fiche immobilier (`loyerAnnuel`) s’il existe.

---

### 12.6 Mobilier et financier

Types :

```text
Compte courant
Livret bancaire
Compte à terme
PEA
Compte-titres
Assurance-vie
PER
SCPI
ETF
Actions
Obligations
Crypto-actifs
Bijoux
Or
Œuvre d’art
Véhicule
Mobilier ancien
Collection
Montre
Autre bien mobilier ou financier
```

Niveau 1 :

```text
type
titre
etablissement
valeurActuelle
quotePartDetenue
liquidite
```

Niveau 2 :

```text
numeroMasque
dateOuverture
montantVerse
plusValueLatente
supportsDetenus
niveauRisque
fiscalite
disponibiliteFonds
beneficiaires
clauseBeneficiaire
lieuConservation
assuranceSpecifique
dateAchat
prixAchat
description
```

Champs obligatoires :

```text
type
titre
valeurActuelle
quotePartDetenue
```

Liquidités :

```text
liquiditesDisponibles =
somme des fiches Mobilier et financier dont liquidite = immédiate
```

La tuile Liquidités du tableau de bord doit ouvrir une vue `liquidites.php` listant les sources.

---

### 12.7 Fiscalité

Types :

```text
Déclaration annuelle
Impôt sur le revenu
IFI
Revenus fonciers
Plus-value
Déficit reportable
Crédit d’impôt
Autre information fiscale
```

Niveau 1 :

```text
type
titre
annee
revenuFiscalReference
nombrePartsFiscales
tauxMarginalImposition
ifiApplicable
```

Niveau 2 :

```text
prelevementsSociaux
revenusFonciersImposables
plusValuesLatentes
deficitsReportables
creditsImpots
numeroFiscal
```

Champs obligatoires :

```text
annee
revenuFiscalReference
nombrePartsFiscales
```

---

### 12.8 Succession et transmission

Types :

```text
Héritiers présumés
Donation
Testament
Donation entre époux
Clause bénéficiaire assurance-vie
Usufruit
Nue-propriété
Objectif de transmission
Autre élément de succession
```

Niveau 1 :

```text
type
titre
heritiersPresumes
donationsDejaFaites
testament
clausesBeneficiairesAssuranceVie
```

Niveau 2 :

```text
montantDonations
dateDonations
beneficiairesDonations
donationEntreEpoux
biensUsufruit
biensNuePropriete
objectifsTransmission
notaire
```

Champs obligatoires :

```text
type
titre
```

---

### 12.9 Documents

Types :

```text
Acte notarié
Bail
Diagnostic
Facture
Relevé bancaire
Contrat assurance
Avis d’imposition
Déclaration fiscale
Photo
Autre document
```

Niveau 1 :

```text
type
titre
rubriqueAssociee
ficheAssocieeId
nomFichier
dateDocument
```

Niveau 2 :

```text
cheminFichier
description
dateExpiration
```

Champs obligatoires :

```text
type
titre
```

---

### 12.10 Alertes

Types :

```text
Échéance bail
Révision de loyer
Assurance à renouveler
Crédit arrivant à échéance
Taxe foncière
Déclaration fiscale
Contrôle chaudière
Diagnostic expiré
Fin de garantie
Mise à jour valeur bien
Autre alerte
```

Niveau 1 :

```text
type
titre
dateAlerte
statut
importance
```

Statuts :

```text
À faire
Fait
Ignoré
```

Niveau 2 :

```text
rubriqueAssociee
ficheAssocieeId
recurrence
```

Champs obligatoires :

```text
type
titre
dateAlerte
statut
```

---

## 13. Tableau de bord

La page `dashboard.php` affiche :

```text
Patrimoine brut total
Dettes totales
Patrimoine net
Immobilier total
Mobilier et financier total
Liquidités disponibles
Revenus annuels totaux
Charges annuelles totales
Revenu net annuel estimé
Nombre d’informations manquantes
Dernière mise à jour
```

La tuile `Liquidités` doit être cliquable et ouvrir :

```text
liquidites.php
```

La page dashboard affiche aussi une carte compacte par grande rubrique avec :

```text
Nom de rubrique
Nombre de fiches
Nombre d’informations manquantes
Valeur
Revenus
Charges
Bouton Voir
Bouton Ajouter
Popup des attendus
```

---

## 14. Calculs patrimoniaux

### Immobilier

```text
immobilierTotal =
somme immobilier.niveau1.valeurActuelle × quotePartDetenue
```

Si `quotePartDetenue > 1`, l’interpréter comme un pourcentage.

Exemple :

```text
50 = 50 %
1 = 100 %
```

### Mobilier et financier

```text
mobilierFinancierTotal =
somme mobilierFinancier.niveau1.valeurActuelle × quotePartDetenue
```

### Patrimoine brut

```text
patrimoineBrutTotal =
immobilierTotal + mobilierFinancierTotal
```

### Dettes

```text
dettesTotales =
somme dettesCredits.niveau1.capitalRestantDu
```

### Patrimoine net

```text
patrimoineNet =
patrimoineBrutTotal - dettesTotales
```

### Revenus annuels

```text
revenusAnnuelsTotaux =
somme revenus.niveau1.montantAnnuel
```

### Charges annuelles

```text
chargesAnnuellesTotales =
somme chargesAnnuelles.niveau1.montantAnnuel
+ somme dettesCredits.niveau1.mensualiteAnnuelle
```

### Revenu net annuel estimé

```text
revenuNetAnnuelEstime =
revenusAnnuelsTotaux - chargesAnnuellesTotales
```

---

## 15. Informations manquantes

La fonction :

```php
getMissingInformation(array $data): array
```

doit contrôler uniquement les champs du niveau 1.

Les champs du niveau 2 ne sont pas bloquants.

Retour attendu :

```json
{
  "rubrique": "immobilier",
  "entryId": "uuid",
  "entryLabel": "Appartement Troyes",
  "field": "valeurActuelle",
  "niveau": "niveau1",
  "importance": "forte",
  "message": "Le champ valeurActuelle est nécessaire pour consolider le bilan patrimonial."
}
```

---

## 16. Vue rubrique

La page :

```text
rubrique.php?rubrique=immobilier
```

affiche la liste des fiches d’une rubrique.

Pour les rubriques standards :

```text
Type
Titre
Valeur
Revenu/an
Charge/an
Dette
Liens
Commentaire
Actions
```

Ajouter une ligne Total en fin de liste additionnant :

```text
Valeur
Revenu/an
Charge/an
Dette
```

Pour `Charges annuelles`, utiliser une vue spécifique :

```text
Type
Titre
Catégorie
Bien concerné
Coût/mois
Coût/an
Liens
Commentaire
Actions
```

---

## 17. Vue liquidités

La page :

```text
liquidites.php
```

affiche les sources de liquidités.

Elle liste les fiches de `mobilierFinancier` dont :

```text
liquidite = immédiate
```

Colonnes :

```text
Type
Titre
Établissement
Valeur
Quote-part
Liquidité
Commentaire
Modifier
```

---

## 18. Synthèse patrimoine

La page :

```text
synthese.php
```

affiche une vue consolidée par grands postes.

Elle doit inclure :

```text
Patrimoine brut
Dettes totales
Patrimoine net
Revenu net estimé annuel
Vue globale par grand poste
Flux annuels
Qualité des données
```

Dans la table des grands postes, afficher une icône :

```text
>
```

avant chaque poste contenant au moins une fiche.

Un clic sur cette icône ouvre le détail global du poste.

Le détail affiche :

```text
Type
Titre
Valeur
Revenu/an
Charge/an
Dette
Liens
Commentaire
```

Le bouton `Imprimer` doit :

1. ouvrir tous les postes ayant des fiches ;
2. afficher tous les détails ;
3. lancer l’impression.

En mode impression, les détails doivent rester visibles.

---

## 19. Exports

### Export JSON

Télécharger le fichier complet de l’utilisateur connecté :

```text
patrimoine-{username}-YYYY-MM-DD.json
```

### Export CSV

Colonnes :

```text
Rubrique
Type
Titre
Valeur patrimoniale
Revenu annuel
Charge annuelle
Dette restante
Nombre de liens
Commentaire
```

Encodage :

```text
UTF-8 avec BOM
Séparateur ;
```

Nom :

```text
patrimoine-synthese-{username}-YYYY-MM-DD.csv
```

---

## 20. API

Toutes les API doivent être protégées par `requireLogin()`.

Routes :

```text
GET  /api/patrimoine.php
GET  /api/dashboard.php
GET  /api/missing.php
GET  /api/export-json.php
GET  /api/export-csv.php
GET  /api/profil.php
POST /api/profil.php
```

Routes rubriques :

```text
GET  /api/rubrique.php?action=list&rubrique=immobilier
GET  /api/rubrique.php?action=get&rubrique=immobilier&id=uuid
GET  /api/rubrique.php?action=schema&rubrique=immobilier
POST /api/rubrique.php?action=create&rubrique=immobilier
POST /api/rubrique.php?action=update&rubrique=immobilier&id=uuid
POST /api/rubrique.php?action=delete&rubrique=immobilier&id=uuid
```

---

## 21. Sécurité

Implémenter :

```text
password_hash()
password_verify()
session_regenerate_id(true)
requireLogin()
requireAdmin()
htmlspecialchars() pour tout affichage HTML
validation stricte des rubriques autorisées
validation stricte des champs autorisés depuis schema.php
blocage du dossier /data avec .htaccess
aucun chemin fichier fourni directement par l’utilisateur
écriture atomique du JSON
sauvegarde automatique avant modification
validation des liens web
```

Le fichier `diagnostic.php` peut aider à diagnostiquer IONOS, mais il doit être supprimé ou protégé après déploiement final.

---

## 22. Interface utilisateur

Interface compacte pour afficher un maximum d’informations par page.

Principes :

```text
typographie dense
cartes compactes
tableaux serrés
boutons courts
navigation horizontale compacte
peu d’espace vertical perdu
```

Navigation principale :

```text
Tableau de bord
Profil
Revenus
Dettes et crédits
Charges annuelles
Immobilier
Mobilier et financier
Fiscalité
Succession
Documents
Alertes
Utilisateurs (admin uniquement)
Déconnexion
```

Couleurs :

```text
Fond général : #f5f6f8
Carte : #ffffff
Texte principal : #1f2937
Bordure : #e5e7eb
Bouton principal : #2563eb
Bouton secondaire : #6b7280
Bouton danger : #dc2626
Alerte : #f59e0b
Succès : #16a34a
```

---

## 23. Résultat attendu

À la fin, l’utilisateur doit pouvoir :

```text
1. Envoyer le dossier sur un hébergement IONOS
2. Ouvrir index.php
3. Se connecter avec nicolas / change-me-before-deploy
4. Créer d’autres utilisateurs
5. Avoir un fichier patrimoine JSON séparé par utilisateur
6. Voir le tableau de bord
7. Voir les grandes rubriques regroupées
8. Ajouter, modifier, supprimer des fiches
9. Saisir les montants en mensuel, trimestriel ou annuel
10. Voir les montants annualisés automatiquement
11. Ajouter plusieurs liens web par fiche
12. Voir le patrimoine brut
13. Voir les dettes totales
14. Voir le patrimoine net
15. Voir les revenus annuels
16. Voir les charges annuelles
17. Voir les sources de liquidités
18. Générer une synthèse patrimoine
19. Imprimer la synthèse avec tous les détails ouverts
20. Voir les informations manquantes du niveau 1
21. Exporter les données en JSON
22. Exporter une synthèse CSV
23. Retrouver les données après fermeture et réouverture
```
