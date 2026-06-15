<?php
declare(strict_types=1);

const APP_NAME = 'Patrimoine';
const APP_OWNER = 'nicolas';
const INITIAL_USERNAME = 'nicolas';
const INITIAL_PASSWORD = 'change-me-before-deploy';

const ROOT_DIR = __DIR__ . '/..';
const DATA_DIR = ROOT_DIR . '/data';
const BACKUP_DIR = DATA_DIR . '/backups';
const USERS_FILE = DATA_DIR . '/users.json';
const PATRIMOINE_FILE = DATA_DIR . '/patrimoine.json';

const RUBRIQUES = [
    'profil',
    'revenus',
    'dettesCredits',
    'chargesAnnuelles',
    'immobilier',
    'mobilierFinancier',
    'fiscalite',
    'successionTransmission',
    'documents',
    'alertes',
];
