<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function appSchema(): array
{
    return [
        'profil' => [
            'label' => 'Profil',
            'types' => ['Informations personnelles', 'Situation familiale', 'Objectifs patrimoniaux'],
            'niveau1Required' => ['nomDossier', 'typeFoyer', 'situationFamiliale'],
            'niveau1' => [
                'nomDossier' => field('Nom du dossier', 'text', true),
                'typeFoyer' => field('Type de foyer', 'select', true, ['personne seule', 'couple', 'famille monoparentale', 'famille recomposée', 'autre']),
                'situationFamiliale' => field('Situation familiale', 'select', true, ['célibataire', 'marié', 'pacsé', 'concubinage', 'divorcé', 'séparé', 'veuf']),
                'regimeMatrimonial' => field('Régime matrimonial', 'select', false, ['aucun', 'communauté réduite aux acquêts', 'séparation de biens', 'communauté universelle', 'participation aux acquêts', 'autre']),
                'nombreEnfants' => field('Nombre d’enfants', 'number'),
            ],
            'niveau2' => [
                'personnesACharge' => field('Personnes à charge', 'text'),
                'objectifs' => field('Objectifs', 'textarea'),
                'commentaires' => field('Commentaires', 'textarea'),
            ],
        ],
        'revenus' => [
            'label' => 'Revenus',
            'types' => ['Salaire', 'Retraite', 'Rente', 'Pension', 'Allocation', 'Revenu indépendant', 'Autre revenu'],
            'niveau1Required' => ['type', 'titre', 'montantAnnuel'],
            'niveau1' => [
                'montant' => field('Montant', 'number', true, [], true),
                'fiscalise' => field('Fiscalisé', 'select', false, ['oui', 'non']),
            ],
            'niveau2' => [
                'dateDebut' => field('Date de début', 'date'),
                'dateFin' => field('Date de fin', 'date'),
                'reversionPossible' => field('Réversion possible', 'select', false, ['oui', 'non', 'inconnu']),
                'conditions' => field('Conditions', 'textarea'),
            ],
            'typeSchemas' => revenusTypeSchemas(),
        ],
        'dettesCredits' => [
            'label' => 'Dettes et crédits',
            'types' => ['Crédit immobilier', 'Crédit consommation', 'Prêt familial', 'Dette fiscale', 'Dette personnelle', 'Découvert bancaire', 'Autre dette'],
            'niveau1Required' => ['type', 'titre', 'capitalRestantDu', 'mensualiteAnnuelle'],
            'niveau1' => [
                'creancier' => field('Créancier', 'text'),
                'capitalRestantDu' => field('Capital restant dû', 'number', true),
                'mensualite' => field('Mensualité', 'number', true, [], true, 'mensualite'),
                'tauxInteret' => field('Taux d’intérêt', 'number'),
                'dateFin' => field('Date de fin', 'date'),
            ],
            'niveau2' => [
                'capitalInitial' => field('Capital initial', 'number'),
                'dateDebut' => field('Date de début', 'date'),
                'dureeRestanteMois' => field('Durée restante en mois', 'number'),
                'assuranceEmprunteur' => field('Assurance emprunteur', 'number', false, [], true, 'assuranceEmprunteur'),
                'remboursementAnticipePossible' => field('Remboursement anticipé possible', 'select', false, ['oui', 'non', 'inconnu']),
                'bienAssocie' => field('Bien associé', 'text'),
                'conditionsParticulieres' => field('Conditions particulières', 'textarea'),
            ],
        ],
        'chargesAnnuelles' => [
            'label' => 'Charges annuelles',
            'types' => ['Immobilier', 'Véhicules', 'Assurances', 'Santé', 'Vie courante', 'Famille', 'Impôts', 'Crédits', 'Banque / placements', 'Professionnel / SCI', 'Charges exceptionnelles', 'Autres charges'],
            'niveau1Required' => ['type', 'titre', 'montantAnnuel'],
            'niveau1' => [
                'categorieDetaillee' => field('Catégorie détaillée', 'select', false, [
                    'Logement',
                    'Énergie et eau',
                    'Alimentation et courses',
                    'Transport',
                    'Santé',
                    'Assurances',
                    'Impôts et taxes',
                    'Télécoms et abonnements',
                    'Banque et frais financiers',
                    'Crédits et dettes',
                    'Épargne et placements',
                    'Famille et enfants',
                    'Loisirs, culture et sorties',
                    'Vacances et voyages',
                    'Habillement et équipement',
                    'Services et aide à domicile',
                    'Professionnel',
                    'Dons et solidarité',
                    'Dépenses exceptionnelles',
                    'Autre',
                ]),
                'montant' => field('Montant', 'number', true, [], true),
                'payeur' => field('Payeur', 'select', false, ['Nicolas', 'Conjoint', 'Indivision', 'SCI', 'Société', 'Foyer', 'Autre']),
                'obligatoire' => field('Obligatoire', 'select', false, ['oui', 'non']),
            ],
            'niveau2' => [
                'fournisseur' => field('Fournisseur', 'text'),
                'organisme' => field('Organisme', 'text'),
                'dateEcheance' => field('Date d’échéance', 'date'),
                'modePaiement' => field('Mode de paiement', 'select', false, ['prélèvement', 'virement', 'carte bancaire', 'chèque', 'espèces', 'autre']),
                'contratReference' => field('Contrat / référence', 'text'),
                'contratAssocie' => field('Contrat associé', 'text'),
                'justificatif' => field('Justificatif', 'select', false, ['PDF', 'facture', 'avis d’impôt', 'contrat', 'photo', 'autre', 'aucun']),
            ],
            'typeSchemas' => chargesAnnuellesTypeSchemas(),
        ],
        'immobilier' => [
            'label' => 'Immobilier',
            'types' => ['Résidence principale', 'Résidence secondaire', 'Bien locatif nu', 'Bien locatif meublé', 'Garage', 'Terrain', 'Local commercial', 'Parts de SCI immobilière', 'Autre bien immobilier'],
            'niveau1Required' => ['type', 'titre', 'valeurActuelle', 'quotePartDetenue'],
            'niveau1' => [
                'adresse' => field('Adresse', 'text'),
                'lienWeb' => field('Lien web', 'url'),
                'valeurActuelle' => field('Valeur actuelle', 'number', true),
                'modeDetention' => field('Mode de détention', 'select', false, ['Pleine propriété', 'Indivision', 'Usufruit', 'Nue-propriété', 'SCI', 'Autre']),
                'quotePartDetenue' => field('Quote-part détenue', 'number', true),
                'pourcentageUsufruit' => field('% usufruit', 'number'),
                'creditAssocie' => field('Crédit associé', 'text'),
                'revenuAssocie' => field('Revenu associé', 'text'),
                'chargeAssociee' => field('Charge associée', 'text'),
            ],
            'niveau2' => [
                'surfaceM2' => field('Surface m²', 'number'),
                'dateAcquisition' => field('Date d’acquisition', 'date'),
                'prixAchat' => field('Prix d’achat', 'number'),
                'fraisNotaire' => field('Frais de notaire', 'number'),
                'travauxRealises' => field('Travaux réalisés', 'number'),
            ],
            'typeSchemas' => immobilierTypeSchemas(),
        ],
        'mobilierFinancier' => [
            'label' => 'Mobilier et financier',
            'types' => ['Compte courant', 'Livret bancaire', 'Compte à terme', 'PEA', 'Compte-titres', 'Assurance-vie', 'PER', 'SCPI', 'ETF', 'Actions', 'Obligations', 'Crypto-actifs', 'Bijoux', 'Or', 'Œuvre d’art', 'Véhicule', 'Mobilier ancien', 'Collection', 'Montre', 'Autre bien mobilier ou financier'],
            'niveau1Required' => ['type', 'titre', 'valeurActuelle', 'quotePartDetenue'],
            'niveau1' => [
                'etablissement' => field('Établissement', 'text'),
                'lienWeb' => field('Lien web', 'url'),
                'login' => field('Login', 'text'),
                'password' => field('Password', 'text'),
                'valeurActuelle' => field('Valeur actuelle', 'number', true),
                'quotePartDetenue' => field('Quote-part détenue', 'number', true),
                'liquidite' => field('Liquidité', 'select', false, ['immédiate', 'sous quelques jours', 'à échéance', 'bloquée', 'autre']),
            ],
            'niveau2' => [
                'description' => field('Description', 'textarea'),
            ],
            'typeSchemas' => mobilierFinancierTypeSchemas(),
        ],
        'fiscalite' => [
            'label' => 'Fiscalité',
            'types' => ['Avis / déclaration IR', 'Prélèvement à la source', 'IFI', 'Revenus fonciers', 'Plus-value immobilière', 'Déficit foncier reportable', 'Crédit ou réduction d’impôt', 'Taxe foncière', 'Autre information fiscale'],
            'niveau1Required' => ['type', 'titre', 'annee'],
            'niveau1' => [
                'annee' => field('Année', 'number', true),
                'statut' => field('Statut', 'select', false, ['à préparer', 'déclaré', 'avis reçu', 'payé', 'à vérifier']),
                'montantPrincipal' => field('Montant principal', 'number'),
            ],
            'niveau2' => [
                'formulaire' => field('Formulaire / annexe', 'text'),
                'dateDepot' => field('Date de dépôt', 'date'),
                'datePaiement' => field('Date limite ou paiement', 'date'),
                'numeroFiscal' => field('Numéro fiscal', 'text'),
                'referenceAvis' => field('Référence avis / document', 'text'),
            ],
            'typeSchemas' => fiscaliteTypeSchemas(),
        ],
        'successionTransmission' => [
            'label' => 'Succession et transmission',
            'types' => ['Héritiers présumés', 'Donation', 'Testament', 'Donation entre époux', 'Clause bénéficiaire assurance-vie', 'Usufruit', 'Nue-propriété', 'Objectif de transmission', 'Autre élément de succession'],
            'niveau1Required' => ['type', 'titre'],
            'niveau1' => [
                'heritiersPresumes' => field('Héritiers présumés', 'textarea'),
                'donationsDejaFaites' => field('Donations déjà faites', 'select', false, ['oui', 'non']),
                'testament' => field('Testament', 'select', false, ['oui', 'non', 'prévu']),
                'clausesBeneficiairesAssuranceVie' => field('Clauses bénéficiaires assurance-vie', 'select', false, ['à jour', 'à vérifier', 'absentes', 'non concerné']),
            ],
            'niveau2' => [
                'montantDonations' => field('Montant des donations', 'number'),
                'dateDonations' => field('Date des donations', 'date'),
                'beneficiairesDonations' => field('Bénéficiaires donations', 'textarea'),
                'donationEntreEpoux' => field('Donation entre époux', 'select', false, ['oui', 'non', 'prévue']),
                'biensUsufruit' => field('Biens en usufruit', 'textarea'),
                'biensNuePropriete' => field('Biens en nue-propriété', 'textarea'),
                'objectifsTransmission' => field('Objectifs de transmission', 'textarea'),
                'notaire' => field('Notaire', 'text'),
            ],
        ],
        'documents' => [
            'label' => 'Documents',
            'types' => ['Acte notarié', 'Bail', 'Diagnostic', 'Facture', 'Relevé bancaire', 'Contrat assurance', 'Avis d’imposition', 'Déclaration fiscale', 'Photo', 'Autre document'],
            'niveau1Required' => ['type', 'titre'],
            'niveau1' => [
                'rubriqueAssociee' => field('Rubrique associée', 'select', false, RUBRIQUES),
                'ficheAssocieeId' => field('Fiche associée', 'text'),
                'nomFichier' => field('Nom du fichier', 'text'),
                'dateDocument' => field('Date du document', 'date'),
            ],
            'niveau2' => [
                'cheminFichier' => field('Chemin du fichier', 'text'),
                'description' => field('Description', 'textarea'),
                'dateExpiration' => field('Date d’expiration', 'date'),
            ],
        ],
        'alertes' => [
            'label' => 'Alertes',
            'types' => ['Échéance bail', 'Révision de loyer', 'Assurance à renouveler', 'Crédit arrivant à échéance', 'Taxe foncière', 'Déclaration fiscale', 'Contrôle chaudière', 'Diagnostic expiré', 'Fin de garantie', 'Mise à jour valeur bien', 'Autre alerte'],
            'niveau1Required' => ['type', 'titre', 'dateAlerte', 'statut'],
            'niveau1' => [
                'dateAlerte' => field('Date de l’alerte', 'date', true),
                'statut' => field('Statut', 'select', true, ['À faire', 'Fait', 'Ignoré']),
                'importance' => field('Importance', 'select', false, ['faible', 'moyenne', 'forte']),
            ],
            'niveau2' => [
                'rubriqueAssociee' => field('Rubrique associée', 'select', false, RUBRIQUES),
                'ficheAssocieeId' => field('Fiche associée', 'text'),
                'recurrence' => field('Récurrence', 'select', false, ['aucune', 'mensuelle', 'trimestrielle', 'semestrielle', 'annuelle']),
            ],
        ],
    ];
}

function field(string $label, string $type = 'text', bool $required = false, array $options = [], bool $periodic = false, ?string $periodicBase = null): array
{
    return [
        'label' => $label,
        'type' => $type,
        'required' => $required,
        'options' => $options,
        'periodic' => $periodic,
        'periodicBase' => $periodicBase,
    ];
}

function revenusTypeSchemas(): array
{
    $pensionFields = [
        'niveau1' => [
            'organismePayeur' => field('Caisse / organisme payeur', 'text'),
            'montant' => field('Montant de la pension', 'number', true, [], true),
            'fiscalise' => field('Fiscalisé', 'select', false, ['oui', 'non']),
        ],
        'niveau2' => [
            'dateDebut' => field('Date de début de versement', 'date'),
            'dateFin' => field('Date de fin prévue', 'date'),
            'reversionPossible' => field('Réversion possible', 'select', false, ['oui', 'non', 'inconnu']),
            'conditions' => field('Conditions / remarques', 'textarea'),
        ],
    ];

    return [
        'Salaire' => [
            'niveau1' => [
                'organismePayeur' => field('Employeur', 'text'),
                'montant' => field('Salaire net / imposable', 'number', true, [], true),
                'fiscalise' => field('Fiscalisé', 'select', false, ['oui', 'non']),
            ],
            'niveau2' => [
                'dateDebut' => field('Date de début du contrat', 'date'),
                'dateFin' => field('Date de fin du contrat', 'date'),
                'conditions' => field('Contrat / primes / avantages', 'textarea'),
            ],
        ],
        'Retraite' => $pensionFields,
        'Rente' => $pensionFields,
        'Pension' => $pensionFields,
        'Allocation' => $pensionFields,
        'Revenu indépendant' => [
            'niveau1' => [
                'organismePayeur' => field('Client / activité', 'text'),
                'montant' => field('Revenu', 'number', true, [], true),
                'fiscalise' => field('Fiscalisé', 'select', false, ['oui', 'non']),
            ],
            'niveau2' => [
                'dateDebut' => field('Date de début d’activité', 'date'),
                'dateFin' => field('Date de fin prévue', 'date'),
                'conditions' => field('Charges, périodicité, remarques', 'textarea'),
            ],
        ],
        'Autre revenu' => [
            'niveau1' => [
                'organismePayeur' => field('Origine du revenu', 'text'),
                'bienImmobilierAssocie' => field('Bien immobilier associé', 'text'),
                'montant' => field('Montant', 'number', true, [], true),
                'fiscalise' => field('Fiscalisé', 'select', false, ['oui', 'non']),
            ],
            'niveau2' => [
                'dateDebut' => field('Date de début', 'date'),
                'dateFin' => field('Date de fin', 'date'),
                'conditions' => field('Détail du calcul / conditions', 'textarea'),
            ],
        ],
    ];
}

function chargesAnnuellesTypeSchemas(): array
{
    $common = [
        'niveau1' => [
            'montant' => field('Montant de la charge', 'number', true, [], true),
            'payeur' => field('Payeur', 'select', false, ['Nicolas', 'Conjoint', 'Indivision', 'SCI', 'Société', 'Foyer', 'Autre']),
            'obligatoire' => field('Obligatoire', 'select', false, ['oui', 'non']),
        ],
        'niveau2' => [
            'fournisseur' => field('Fournisseur', 'text'),
            'organisme' => field('Organisme', 'text'),
            'dateEcheance' => field('Date d’échéance', 'date'),
            'modePaiement' => field('Mode de paiement', 'select', false, ['prélèvement', 'virement', 'carte bancaire', 'chèque', 'espèces', 'autre']),
            'contratReference' => field('Contrat / référence', 'text'),
            'contratAssocie' => field('Contrat associé', 'text'),
            'justificatif' => field('Justificatif', 'select', false, ['PDF', 'facture', 'avis d’impôt', 'contrat', 'photo', 'autre', 'aucun']),
        ],
    ];

    return [
        'Immobilier' => array_replace_recursive($common, [
            'niveau1' => [
                'categorieDetaillee' => field('Catégorie immobilière', 'select', false, ['Logement', 'Énergie et eau', 'Assurances', 'Impôts et taxes', 'Crédits et dettes', 'Services et aide à domicile', 'Autre']),
                'bienConcerne' => field('Bien concerné', 'text'),
                'deductibleFiscalement' => field('Déductible fiscalement', 'select', false, ['oui', 'non', 'partiellement', 'à vérifier']),
                'recuperableLocataire' => field('Récupérable sur locataire', 'select', false, ['oui', 'non', 'partiellement', 'non concerné']),
            ],
        ]),
        'Crédits' => array_replace_recursive($common, [
            'niveau1' => [
                'categorieDetaillee' => field('Catégorie de crédit', 'select', false, ['Crédits et dettes', 'Banque et frais financiers', 'Autre']),
                'bienConcerne' => field('Bien / projet concerné', 'text'),
                'deductibleFiscalement' => field('Déductible fiscalement', 'select', false, ['oui', 'non', 'partiellement', 'à vérifier']),
            ],
        ]),
        'Banque / placements' => array_replace_recursive($common, [
            'niveau1' => [
                'categorieDetaillee' => field('Catégorie bancaire', 'select', false, ['Banque et frais financiers', 'Épargne et placements', 'Autre']),
                'bienConcerne' => field('Compte / placement concerné', 'text'),
            ],
        ]),
        'Impôts' => array_replace_recursive($common, [
            'niveau1' => [
                'categorieDetaillee' => field('Catégorie fiscale', 'select', false, ['Impôts et taxes', 'Autre']),
                'bienConcerne' => field('Bien ou foyer concerné', 'text'),
                'deductibleFiscalement' => field('Déductible fiscalement', 'select', false, ['oui', 'non', 'partiellement', 'à vérifier']),
            ],
        ]),
        'Santé' => array_replace_recursive($common, [
            'niveau1' => [
                'categorieDetaillee' => field('Catégorie santé', 'select', false, ['Santé', 'Assurances', 'Autre']),
                'bienConcerne' => field('Personne concernée', 'text'),
            ],
        ]),
        'Vie courante' => array_replace_recursive($common, [
            'niveau1' => [
                'categorieDetaillee' => field('Catégorie vie courante', 'select', false, ['Alimentation et courses', 'Transport', 'Télécoms et abonnements', 'Loisirs, culture et sorties', 'Habillement et équipement', 'Autre']),
                'bienConcerne' => field('Poste concerné', 'text'),
            ],
        ]),
        'Famille' => array_replace_recursive($common, [
            'niveau1' => [
                'categorieDetaillee' => field('Catégorie famille', 'select', false, ['Famille et enfants', 'Santé', 'Services et aide à domicile', 'Autre']),
                'bienConcerne' => field('Personne concernée', 'text'),
            ],
        ]),
    ];
}

function immobilierTypeSchemas(): array
{
    $locatif = [
        'niveau2' => [
            'administrateurBien' => field('Administrateur du bien', 'text'),
            'administrateurBienUrl' => field('Lien web administrateur du bien', 'url'),
            'administrateurBienIdentifiant' => field('Identifiant administrateur du bien', 'text'),
            'administrateurBienMotDePasse' => field('Mot de passe administrateur du bien', 'text'),
            'administrateurBienFrais' => field('Frais gestionnaire', 'number', false, [], true, 'administrateurBienFrais'),
            'administrateurBienInfos' => field('Infos administrateur du bien', 'textarea'),
            'syndicBien' => field('Syndic du bien', 'text'),
            'syndicBienUrl' => field('Lien web syndic du bien', 'url'),
            'syndicBienIdentifiant' => field('Identifiant syndic du bien', 'text'),
            'syndicBienMotDePasse' => field('Mot de passe syndic du bien', 'text'),
            'syndicBienFrais' => field('Frais syndic', 'number', false, [], true, 'syndicBienFrais'),
            'syndicBienInfos' => field('Infos syndic du bien', 'textarea'),
            'loyer' => field('Loyer encaissé', 'number', false, [], true, 'loyer'),
            'chargesNonRecuperables' => field('Charges non récupérables', 'number', false, [], true, 'chargesNonRecuperables'),
            'taxeFonciere' => field('Taxe foncière', 'number', false, [], true, 'taxeFonciere'),
            'assurancePNO' => field('Assurance PNO', 'number', false, [], true, 'assurancePNO'),
            'regimeFiscal' => field('Régime fiscal locatif', 'select', false, ['Micro-foncier', 'Réel foncier', 'Micro-BIC', 'LMNP réel', 'LMP', 'SCI IR', 'SCI IS', 'Autre']),
            'locataireActuel' => field('Locataire actuel', 'text'),
            'dateDebutBail' => field('Début du bail', 'date'),
            'dateFinBail' => field('Fin du bail', 'date'),
            'depotGarantie' => field('Dépôt de garantie', 'number'),
        ],
    ];

    return [
        'Résidence principale' => [
            'niveau2' => [
                'administrateurBien' => field('Administrateur du bien', 'text'),
                'administrateurBienUrl' => field('Lien web administrateur du bien', 'url'),
                'administrateurBienIdentifiant' => field('Identifiant administrateur du bien', 'text'),
                'administrateurBienMotDePasse' => field('Mot de passe administrateur du bien', 'text'),
                'administrateurBienFrais' => field('Frais gestionnaire', 'number', false, [], true, 'administrateurBienFrais'),
                'administrateurBienInfos' => field('Infos administrateur du bien', 'textarea'),
                'syndicBien' => field('Syndic du bien', 'text'),
                'syndicBienUrl' => field('Lien web syndic du bien', 'url'),
                'syndicBienIdentifiant' => field('Identifiant syndic du bien', 'text'),
                'syndicBienMotDePasse' => field('Mot de passe syndic du bien', 'text'),
                'syndicBienFrais' => field('Frais syndic', 'number', false, [], true, 'syndicBienFrais'),
                'syndicBienInfos' => field('Infos syndic du bien', 'textarea'),
                'surfaceM2' => field('Surface m²', 'number'),
                'dateAcquisition' => field('Date d’acquisition', 'date'),
                'prixAchat' => field('Prix d’achat', 'number'),
                'fraisNotaire' => field('Frais de notaire', 'number'),
                'travauxRealises' => field('Travaux réalisés', 'number'),
                'taxeFonciere' => field('Taxe foncière', 'number', false, [], true, 'taxeFonciere'),
            ],
        ],
        'Résidence secondaire' => [
            'niveau2' => [
                'administrateurBien' => field('Administrateur du bien', 'text'),
                'administrateurBienUrl' => field('Lien web administrateur du bien', 'url'),
                'administrateurBienIdentifiant' => field('Identifiant administrateur du bien', 'text'),
                'administrateurBienMotDePasse' => field('Mot de passe administrateur du bien', 'text'),
                'administrateurBienFrais' => field('Frais gestionnaire', 'number', false, [], true, 'administrateurBienFrais'),
                'administrateurBienInfos' => field('Infos administrateur du bien', 'textarea'),
                'syndicBien' => field('Syndic du bien', 'text'),
                'syndicBienUrl' => field('Lien web syndic du bien', 'url'),
                'syndicBienIdentifiant' => field('Identifiant syndic du bien', 'text'),
                'syndicBienMotDePasse' => field('Mot de passe syndic du bien', 'text'),
                'syndicBienFrais' => field('Frais syndic', 'number', false, [], true, 'syndicBienFrais'),
                'syndicBienInfos' => field('Infos syndic du bien', 'textarea'),
                'surfaceM2' => field('Surface m²', 'number'),
                'dateAcquisition' => field('Date d’acquisition', 'date'),
                'prixAchat' => field('Prix d’achat', 'number'),
                'fraisNotaire' => field('Frais de notaire', 'number'),
                'travauxRealises' => field('Travaux réalisés', 'number'),
                'taxeFonciere' => field('Taxe foncière', 'number', false, [], true, 'taxeFonciere'),
                'assurancePNO' => field('Assurance propriétaire', 'number', false, [], true, 'assurancePNO'),
            ],
        ],
        'Bien locatif nu' => $locatif,
        'Bien locatif meublé' => $locatif,
        'Garage' => $locatif,
        'Local commercial' => $locatif,
        'Parts de SCI immobilière' => [
            'niveau2' => [
                'administrateurBien' => field('Gérant / administrateur', 'text'),
                'administrateurBienUrl' => field('Lien web gérant / SCI', 'url'),
                'administrateurBienIdentifiant' => field('Identifiant gérant / SCI', 'text'),
                'administrateurBienMotDePasse' => field('Mot de passe gérant / SCI', 'text'),
                'administrateurBienFrais' => field('Frais gérant / SCI', 'number', false, [], true, 'administrateurBienFrais'),
                'administrateurBienInfos' => field('Infos gérant / SCI', 'textarea'),
                'regimeFiscal' => field('Régime fiscal de la SCI', 'select', false, ['SCI IR', 'SCI IS', 'Micro-foncier', 'Réel foncier', 'Autre']),
                'loyer' => field('Revenu distribué / estimé', 'number', false, [], true, 'loyer'),
                'chargesNonRecuperables' => field('Charges non récupérables', 'number', false, [], true, 'chargesNonRecuperables'),
                'taxeFonciere' => field('Taxe foncière', 'number', false, [], true, 'taxeFonciere'),
                'assurancePNO' => field('Assurance PNO', 'number', false, [], true, 'assurancePNO'),
            ],
        ],
        'Terrain' => [
            'niveau2' => [
                'surfaceM2' => field('Surface m²', 'number'),
                'dateAcquisition' => field('Date d’acquisition', 'date'),
                'prixAchat' => field('Prix d’achat', 'number'),
                'taxeFonciere' => field('Taxe foncière', 'number', false, [], true, 'taxeFonciere'),
            ],
        ],
        'Autre bien immobilier' => $locatif,
    ];
}

function mobilierFinancierTypeSchemas(): array
{
    $bankAccount = [
        'niveau2' => [
            'numeroMasque' => field('Numéro de compte masqué', 'text'),
            'dateOuverture' => field('Date d’ouverture', 'date'),
            'disponibiliteFonds' => field('Disponibilité des fonds', 'select', false, ['immédiate', 'sous quelques jours', 'à échéance', 'bloquée', 'autre']),
            'description' => field('Mémo', 'textarea'),
        ],
    ];
    $marketAccount = [
        'niveau2' => [
            'numeroMasque' => field('Numéro de contrat / compte masqué', 'text'),
            'dateOuverture' => field('Date d’ouverture', 'date'),
            'montantVerse' => field('Montant versé', 'number'),
            'plusValueLatente' => field('Plus-value latente', 'number'),
            'supportsDetenus' => field('Supports détenus', 'textarea'),
            'niveauRisque' => field('Niveau de risque', 'select', false, ['faible', 'moyen', 'élevé']),
            'fiscalite' => field('Fiscalité', 'text'),
            'disponibiliteFonds' => field('Disponibilité des fonds', 'select', false, ['immédiate', 'sous quelques jours', 'à échéance', 'bloquée', 'autre']),
        ],
    ];

    return [
        'Compte courant' => $bankAccount,
        'Livret bancaire' => $bankAccount,
        'Compte à terme' => $bankAccount,
        'PEA' => $marketAccount,
        'Compte-titres' => $marketAccount,
        'SCPI' => $marketAccount,
        'ETF' => $marketAccount,
        'Actions' => $marketAccount,
        'Obligations' => $marketAccount,
        'Crypto-actifs' => $marketAccount,
        'Assurance-vie' => array_replace_recursive($marketAccount, [
            'niveau2' => [
                'beneficiaires' => field('Bénéficiaires', 'textarea'),
                'clauseBeneficiaire' => field('Clause bénéficiaire', 'select', false, ['standard', 'personnalisée', 'à vérifier', 'non renseignée']),
            ],
        ]),
        'PER' => array_replace_recursive($marketAccount, [
            'niveau2' => [
                'beneficiaires' => field('Bénéficiaires', 'textarea'),
                'clauseBeneficiaire' => field('Clause bénéficiaire', 'select', false, ['standard', 'personnalisée', 'à vérifier', 'non renseignée']),
            ],
        ]),
        'Bijoux' => [
            'niveau2' => [
                'lieuConservation' => field('Lieu de conservation', 'text'),
                'assuranceSpecifique' => field('Assurance spécifique', 'select', false, ['oui', 'non']),
                'dateAchat' => field('Date d’achat', 'date'),
                'prixAchat' => field('Prix d’achat', 'number'),
                'description' => field('Description', 'textarea'),
            ],
        ],
        'Or' => [
            'niveau2' => [
                'lieuConservation' => field('Lieu de conservation', 'text'),
                'assuranceSpecifique' => field('Assurance spécifique', 'select', false, ['oui', 'non']),
                'dateAchat' => field('Date d’achat', 'date'),
                'prixAchat' => field('Prix d’achat', 'number'),
                'description' => field('Description', 'textarea'),
            ],
        ],
        'Œuvre d’art' => [
            'niveau2' => [
                'lieuConservation' => field('Lieu de conservation', 'text'),
                'assuranceSpecifique' => field('Assurance spécifique', 'select', false, ['oui', 'non']),
                'dateAchat' => field('Date d’achat', 'date'),
                'prixAchat' => field('Prix d’achat', 'number'),
                'description' => field('Description', 'textarea'),
            ],
        ],
        'Véhicule' => [
            'niveau2' => [
                'lieuConservation' => field('Lieu de stationnement', 'text'),
                'assuranceSpecifique' => field('Assurance spécifique', 'select', false, ['oui', 'non']),
                'dateAchat' => field('Date d’achat', 'date'),
                'prixAchat' => field('Prix d’achat', 'number'),
                'description' => field('Description', 'textarea'),
            ],
        ],
    ];
}

function fiscaliteTypeSchemas(): array
{
    return [
        'Avis / déclaration IR' => [
            'niveau1Required' => ['annee', 'revenuFiscalReference', 'nombrePartsFiscales'],
            'niveau1' => [
                'revenuFiscalReference' => field('Revenu fiscal de référence', 'number', true),
                'nombrePartsFiscales' => field('Nombre de parts fiscales', 'number', true),
                'revenuImposable' => field('Revenu imposable', 'number'),
                'impotNet' => field('Impôt net', 'number'),
                'resteAPayerOuRemboursement' => field('Reste à payer / remboursement', 'number'),
            ],
            'niveau2' => [
                'tauxMarginalImposition' => field('Taux marginal d’imposition (%)', 'number'),
                'prelevementSourceDejaPaye' => field('Prélèvement à la source déjà payé', 'number'),
                'acomptesContemporains' => field('Acomptes contemporains', 'number'),
                'echeancierPaiement' => field('Échéancier de paiement', 'textarea'),
            ],
        ],
        'Prélèvement à la source' => [
            'niveau1Required' => ['annee', 'tauxPrelevement'],
            'niveau1' => [
                'tauxPrelevement' => field('Taux de prélèvement (%)', 'number', true),
                'typeTaux' => field('Type de taux', 'select', false, ['personnalisé', 'individualisé', 'neutre', 'non renseigné']),
                'montantMensuelPreleve' => field('Montant mensuel prélevé', 'number'),
                'periodiciteAcompte' => field('Périodicité acompte', 'select', false, ['mensuelle', 'trimestrielle', 'aucune']),
            ],
            'niveau2' => [
                'revenuConcerne' => field('Revenu concerné', 'text'),
                'compteBancaire' => field('Compte bancaire / IBAN masqué', 'text'),
                'dateEffetTaux' => field('Date d’effet du taux', 'date'),
                'modulationPrevue' => field('Modulation prévue', 'select', false, ['oui', 'non', 'à vérifier']),
            ],
        ],
        'IFI' => [
            'niveau1Required' => ['annee', 'patrimoineNetTaxable'],
            'niveau1' => [
                'actifImmobilierTaxable' => field('Actif immobilier taxable', 'number'),
                'dettesDeductibles' => field('Dettes déductibles', 'number'),
                'patrimoineNetTaxable' => field('Patrimoine net taxable', 'number', true),
                'ifiDu' => field('IFI dû', 'number'),
            ],
            'niveau2' => [
                'valeurResidencePrincipale' => field('Valeur résidence principale', 'number'),
                'abattementResidencePrincipale' => field('Abattement résidence principale', 'number'),
                'biensExoneresOuPartiels' => field('Biens exonérés ou partiellement exonérés', 'textarea'),
                'annexe2042IFI' => field('Annexe 2042-IFI déposée', 'select', false, ['oui', 'non', 'à préparer']),
            ],
        ],
        'Revenus fonciers' => [
            'niveau1Required' => ['annee', 'regimeFoncier', 'revenusBrutsFonciers'],
            'niveau1' => [
                'regimeFoncier' => field('Régime foncier', 'select', true, ['micro-foncier', 'réel 2044', 'réel 2044 spéciale', 'à vérifier']),
                'revenusBrutsFonciers' => field('Revenus fonciers bruts', 'number', true),
                'chargesDeductibles' => field('Charges déductibles', 'number'),
                'interetsEmprunt' => field('Intérêts d’emprunt', 'number'),
                'resultatFoncier' => field('Résultat foncier', 'number'),
            ],
            'niveau2' => [
                'immeublesConcernes' => field('Immeubles concernés', 'textarea'),
                'formulaire2044' => field('Formulaire 2044 / 2044 spéciale', 'select', false, ['2044', '2044 spéciale', 'non concerné', 'à vérifier']),
                'optionRegimeReel' => field('Option régime réel', 'select', false, ['oui', 'non', 'à vérifier']),
                'recettesAccessoires' => field('Recettes accessoires', 'number'),
            ],
        ],
        'Plus-value immobilière' => [
            'niveau1Required' => ['annee', 'bienCede', 'prixCession'],
            'niveau1' => [
                'bienCede' => field('Bien cédé', 'text', true),
                'dateCession' => field('Date de cession', 'date'),
                'prixCession' => field('Prix de cession', 'number', true),
                'prixAcquisition' => field('Prix / valeur d’acquisition', 'number'),
                'plusValueImposable' => field('Plus-value imposable', 'number'),
            ],
            'niveau2' => [
                'fraisCession' => field('Frais de cession', 'number'),
                'travauxRetenus' => field('Travaux retenus', 'number'),
                'dureeDetentionAnnees' => field('Durée de détention (années)', 'number'),
                'exoneration' => field('Exonération', 'select', false, ['résidence principale', 'durée de détention', 'remploi', 'aucune', 'à vérifier']),
                'notaireOuDeclarant' => field('Notaire / déclarant', 'text'),
            ],
        ],
        'Déficit foncier reportable' => [
            'niveau1Required' => ['anneeOrigineDeficit', 'montantDeficitRestant'],
            'niveau1' => [
                'anneeOrigineDeficit' => field('Année d’origine du déficit', 'number', true),
                'montantDeficitInitial' => field('Déficit initial', 'number'),
                'montantDeficitRestant' => field('Déficit restant à imputer', 'number', true),
                'fractionInteretsEmprunt' => field('Fraction liée aux intérêts d’emprunt', 'number'),
            ],
            'niveau2' => [
                'imputationRevenuGlobal' => field('Imputation sur revenu global', 'number'),
                'reportableJusqua' => field('Reportable jusqu’à', 'number'),
                'immeubleOrigine' => field('Immeuble d’origine', 'text'),
                'historiqueImputations' => field('Historique des imputations', 'textarea'),
            ],
        ],
        'Crédit ou réduction d’impôt' => [
            'niveau1Required' => ['annee', 'natureAvantage', 'montantDepense'],
            'niveau1' => [
                'natureAvantage' => field('Nature de l’avantage fiscal', 'select', true, ['emploi à domicile', 'dons', 'frais de garde', 'dépendance', 'investissement locatif', 'autre']),
                'montantDepense' => field('Dépense déclarée', 'number', true),
                'montantCreditReduction' => field('Crédit / réduction estimé', 'number'),
                'caseDeclarative' => field('Case déclarative', 'text'),
            ],
            'niveau2' => [
                'formulaireRICI' => field('Formulaire 2042 RICI', 'select', false, ['oui', 'non', 'à vérifier']),
                'organismeBeneficiaire' => field('Organisme / bénéficiaire', 'text'),
                'avanceRecue' => field('Avance reçue', 'number'),
                'justificatifs' => field('Justificatifs', 'textarea'),
            ],
        ],
        'Taxe foncière' => [
            'niveau1Required' => ['annee', 'bienConcerne', 'montantTaxeFonciere'],
            'niveau1' => [
                'bienConcerne' => field('Bien concerné', 'text', true),
                'commune' => field('Commune', 'text'),
                'montantTaxeFonciere' => field('Montant taxe foncière', 'number', true),
                'montantOrduresMenageres' => field('Taxe ordures ménagères', 'number'),
            ],
            'niveau2' => [
                'numeroAvis' => field('Numéro d’avis', 'text'),
                'proprietaireOuIndivision' => field('Propriétaire / indivision', 'text'),
                'mensualisation' => field('Mensualisation', 'select', false, ['oui', 'non']),
                'dateLimitePaiement' => field('Date limite de paiement', 'date'),
            ],
        ],
        'Autre information fiscale' => [
            'niveau1' => [
                'categorieFiscale' => field('Catégorie fiscale', 'text'),
                'montantEstime' => field('Montant estimé', 'number'),
                'echeance' => field('Échéance', 'date'),
            ],
            'niveau2' => [
                'description' => field('Description détaillée', 'textarea'),
                'actionARealiser' => field('Action à réaliser', 'textarea'),
            ],
        ],
    ];
}

function schemaFor(string $rubrique): array
{
    $schema = appSchema();
    if (!isset($schema[$rubrique])) {
        throw new InvalidArgumentException('Rubrique inconnue.');
    }
    return $schema[$rubrique];
}

function schemaForFiche(string $rubrique, string $type): array
{
    $schema = schemaFor($rubrique);
    $typeSchema = $schema['typeSchemas'][$type] ?? null;
    unset($schema['typeSchemas']);
    if (!$typeSchema) {
        return $schema;
    }
    $schema['niveau1Required'] = array_values(array_unique(array_merge($schema['niveau1Required'] ?? [], $typeSchema['niveau1Required'] ?? [])));
    $schema['niveau1'] = array_replace($schema['niveau1'] ?? [], $typeSchema['niveau1'] ?? []);
    $schema['niveau2'] = array_replace($schema['niveau2'] ?? [], $typeSchema['niveau2'] ?? []);
    return $schema;
}
