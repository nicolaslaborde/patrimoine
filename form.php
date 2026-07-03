<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/immobilier-revenus.php';

$rubrique = $_GET['rubrique'] ?? 'profil';
if (!in_array($rubrique, RUBRIQUES, true)) {
    redirect('dashboard.php');
}
$schema = schemaFor($rubrique);
$baseSchema = $schema;
$data = loadPatrimoine();
$id = $_GET['id'] ?? null;
$entry = null;
if ($rubrique !== 'profil' && $id) {
    foreach (($data[$rubrique] ?? []) as $candidate) {
        if (($candidate['id'] ?? '') === $id) {
            $entry = $candidate;
            break;
        }
    }
}
if ($rubrique === 'profil') {
    $entry = ['type' => 'Informations personnelles', 'titre' => $data['profil']['nomDossier'] ?? 'Profil', 'niveau1' => $data['profil'], 'niveau2' => $data['profil'], 'liens' => $data['profil']['liens'] ?? [], 'commentaire' => $data['profil']['commentaires'] ?? ''];
}
$entry ??= ['type' => '', 'titre' => '', 'niveau1' => [], 'niveau2' => [], 'liens' => [], 'historique' => [], 'commentaire' => ''];
$selectedType = (string)(($_GET['type'] ?? '') ?: ($entry['type'] ?? ''));
if ($selectedType !== '' && in_array($selectedType, $baseSchema['types'] ?? [], true)) {
    $entry['type'] = $selectedType;
    $schema = schemaForFiche($rubrique, $selectedType);
    $schema['types'] = $baseSchema['types'];
}
$schema = applyContextualOptions($schema, $rubrique, $data);
$ficheNavigation = ficheNavigation($data, $rubrique, (string)$id);
$isAutoImmobilierRevenu = $rubrique === 'revenus' && (($entry['sourceAutomatique'] ?? '') === 'immobilierRegimeFiscal');
renderHeader(($id ? 'Modifier' : 'Ajouter') . ' - ' . $schema['label']);
?>
<div class="page-head dense-head">
  <div>
    <h1><?= e(pageH1($isAutoImmobilierRevenu ? 'Voir une fiche' : ($rubrique === 'profil' ? 'Profil' : ($id ? 'Modifier une fiche' : 'Ajouter une fiche')))) ?></h1>
    <?php if ($ficheNavigation['show']): ?>
      <div class="fiche-nav-line" aria-label="Navigation entre fiches">
        <?php if ($ficheNavigation['previous']): ?>
          <a class="fiche-nav-icon" href="<?= e($ficheNavigation['previous']['url']) ?>" title="<?= e($ficheNavigation['previous']['title']) ?>" aria-label="Fiche précédente">&lt;</a>
        <?php else: ?>
          <span class="fiche-nav-icon disabled" aria-disabled="true">&lt;</span>
        <?php endif; ?>
        <span class="muted"><?= e($schema['label']) ?> (fiche <?= e($ficheNavigation['position']) ?> sur <?= e($ficheNavigation['total']) ?>)</span>
        <?php if ($ficheNavigation['next']): ?>
          <a class="fiche-nav-icon" href="<?= e($ficheNavigation['next']['url']) ?>" title="<?= e($ficheNavigation['next']['title']) ?>" aria-label="Fiche suivante">&gt;</a>
        <?php else: ?>
          <span class="fiche-nav-icon disabled" aria-disabled="true">&gt;</span>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <p class="muted"><?= e($schema['label']) ?></p>
    <?php endif; ?>
  </div>
</div>

<?php if ($isAutoImmobilierRevenu): ?>
  <?php
    $sources = immobilierRegimeFiscalSources($data['immobilier'] ?? [], (string)($entry['titre'] ?? ''));
    $revenuBrutTotal = array_reduce($sources, fn(float $sum, array $source): float => $sum + $source['revenuBrutAnnuel'], 0.0);
    $revenuNetTotal = array_reduce($sources, fn(float $sum, array $source): float => $sum + $source['revenuNetAnnuel'], 0.0);
    $revenuNetMensuelTotal = $revenuNetTotal / 12;
  ?>
  <section class="form-band">
    <h2><?= e($entry['titre'] ?? '') ?></h2>
    <p class="muted"><?= e($entry['commentaire'] ?? 'Revenu net immobilier calculé automatiquement par régime fiscal.') ?></p>
  </section>

  <section class="form-band">
    <h2>Sources immobilières</h2>
    <div class="table-wrap">
      <table class="dense-table inner-table">
        <thead>
          <tr>
            <th>Bien</th>
            <th>Revenu/an brut</th>
            <th>Revenu/mois net</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$sources): ?><tr><td colspan="3">Aucune source immobilière pour ce régime fiscal.</td></tr><?php endif; ?>
          <?php foreach ($sources as $source): ?>
            <tr>
              <td>
                <a href="form.php?rubrique=immobilier&id=<?= e($source['id']) ?>"><?= e($source['titre']) ?></a>
              </td>
              <td><?= e(euro($source['revenuBrutAnnuel'])) ?></td>
              <td><?= e(euro($source['revenuNetAnnuel'] / 12)) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="total-row">
            <td>Total</td>
            <td><?= e(euro($revenuBrutTotal)) ?></td>
            <td><?= e(euro($revenuNetMensuelTotal)) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </section>

  <div class="sticky-actions">
    <a class="button secondary" href="rubrique.php?rubrique=revenus">Retour</a>
  </div>
  <?php renderFooter(); ?>
  <?php exit; ?>
<?php endif; ?>

<form id="ficheForm" class="form-compact" data-rubrique="<?= e($rubrique) ?>" data-id="<?= e((string)$id) ?>" data-is-profil="<?= $rubrique === 'profil' ? '1' : '0' ?>">
  <?php if ($rubrique !== 'profil'): ?>
    <section class="form-band fiche-head-fields">
      <label>Titre de la fiche <span class="required-star">*</span>
        <input name="titre" value="<?= e($entry['titre'] ?? '') ?>" required>
      </label>
      <label>Type de fiche <span class="required-star">*</span>
        <select name="type" required data-type-specific="1">
          <option value="">Choisir...</option>
          <?php foreach ($schema['types'] as $type): ?>
            <option value="<?= e($type) ?>" <?= ($entry['type'] ?? '') === $type ? 'selected' : '' ?>><?= e($type) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php if ($rubrique === 'immobilier' && isset($schema['niveau1']['typeBien'])): ?>
        <?= renderField('typeBien', $schema['niveau1']['typeBien'], ($entry['niveau1'] ?? [])['typeBien'] ?? '', 'niveau1', false) ?>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <section class="form-band">
    <h2>1. Informations minimales pour le bilan patrimonial</h2>
    <div class="form-grid">
      <?php foreach ($schema['niveau1'] as $field => $def): ?>
        <?php if ($rubrique === 'immobilier' && $field === 'typeBien') { continue; } ?>
        <?= renderField($field, $def, ($entry['niveau1'] ?? [])[$field] ?? null, 'niveau1', in_array($field, $schema['niveau1Required'], true)) ?>
      <?php endforeach; ?>
    </div>
  </section>

  <?php if ($rubrique === 'immobilier'): ?>
    <section class="form-band">
      <div class="section-title-row">
        <h2>Historique</h2>
        <button class="button tiny secondary" type="button" id="addHistory">Ajouter historique</button>
      </div>
      <div id="historyBox" class="history-box">
        <?php foreach (($entry['historique'] ?? []) as $history): ?>
          <div class="history-row">
            <input type="date" data-history-date value="<?= e($history['date'] ?? date('Y-m-d')) ?>">
            <textarea data-history-texte placeholder="Information historique"><?= e($history['texte'] ?? '') ?></textarea>
            <span class="url-open-field">
              <input type="url" data-history-url value="<?= e($history['url'] ?? '') ?>" placeholder="https://...">
              <?= renderUrlOpenIcon((string)($history['url'] ?? '')) ?>
            </span>
            <button class="button tiny danger" type="button" data-remove-history>Supprimer</button>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!empty($schema['niveau2'])): ?>
    <details class="form-band" open>
      <summary>2. Informations détaillées et mémo</summary>
      <div class="form-grid">
        <?php foreach ($schema['niveau2'] as $field => $def): ?>
          <?= renderField($field, $def, ($entry['niveau2'] ?? [])[$field] ?? null, 'niveau2', false) ?>
        <?php endforeach; ?>
      </div>
    </details>
  <?php endif; ?>

  <details class="form-band" open>
    <summary>Liens web</summary>
    <div id="linksBox" class="links-box">
      <?php foreach (($entry['liens'] ?? []) as $link): ?>
        <div class="link-row<?= $rubrique === 'immobilier' ? ' has-open-icon' : '' ?>">
          <input data-link-url value="<?= e($link['url'] ?? '') ?>" placeholder="https://...">
          <input data-link-description value="<?= e($link['description'] ?? '') ?>" placeholder="Description">
          <?php if ($rubrique === 'immobilier'): ?>
            <?= renderUrlOpenIcon((string)($link['url'] ?? '')) ?>
          <?php endif; ?>
          <button class="button tiny danger" type="button" data-remove-link>Supprimer</button>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="button tiny secondary" type="button" id="addLink">Ajouter un lien</button>
  </details>

  <section class="form-band">
    <label>Commentaire général
      <textarea name="commentaire"><?= e($entry['commentaire'] ?? '') ?></textarea>
    </label>
  </section>

  <div class="sticky-actions">
    <a class="button secondary" href="<?= $rubrique === 'profil' ? 'dashboard.php' : 'rubrique.php?rubrique=' . e($rubrique) ?>">Annuler</a>
    <button class="button primary">Enregistrer</button>
  </div>
</form>
<?php renderFooter(); ?>

<?php
function renderField(string $field, array $def, $value, string $level, bool $required): string
{
    global $rubrique;
    $name = "{$level}[{$field}]";
    $label = e($def['label']) . ($required || !empty($def['required']) ? ' <span class="required-star">*</span>' : '');
    $requiredAttr = ($required || !empty($def['required'])) ? 'required' : '';
    $type = $def['type'] ?? 'text';
    $full = in_array($type, ['textarea'], true) || !empty($def['periodic']) ? ' full' : '';
    $withOpenIcon = in_array($rubrique, ['immobilier', 'mobilierFinancier'], true) && $type === 'url';
    ob_start();
    if (!empty($def['periodic'])):
        $p = is_array($value) ? $value : [];
        ?>
        <fieldset class="periodic-amount<?= e($full) ?>" data-periodic="<?= e($name) ?>">
          <legend><?= $label ?></legend>
          <input type="number" step="any" data-periodic-input value="<?= e($p['valeurSaisie'] ?? '') ?>" <?= $requiredAttr ?>>
          <select data-periodic-select>
            <option value="mensuel" <?= ($p['periodicite'] ?? '') === 'mensuel' ? 'selected' : '' ?>>Mensuel</option>
            <option value="trimestriel" <?= ($p['periodicite'] ?? '') === 'trimestriel' ? 'selected' : '' ?>>Trimestriel</option>
            <option value="annuel" <?= ($p['periodicite'] ?? 'annuel') === 'annuel' ? 'selected' : '' ?>>Annuel</option>
          </select>
          <input type="number" step="any" data-periodic-monthly value="<?= e($p['mensuel'] ?? '') ?>" readonly>
          <input type="number" step="any" data-periodic-annual value="<?= e($p['annuel'] ?? '') ?>" readonly>
          <input type="hidden" name="<?= e($name) ?>[valeurSaisie]" data-periodic-hidden-value value="<?= e($p['valeurSaisie'] ?? '') ?>">
          <input type="hidden" name="<?= e($name) ?>[periodicite]" data-periodic-hidden-period value="<?= e($p['periodicite'] ?? 'annuel') ?>">
          <input type="hidden" name="<?= e($name) ?>[mensuel]" data-periodic-hidden-monthly value="<?= e($p['mensuel'] ?? '') ?>">
          <input type="hidden" name="<?= e($name) ?>[annuel]" data-periodic-hidden-annual value="<?= e($p['annuel'] ?? '') ?>">
        </fieldset>
        <?php
    elseif ($type === 'select'): ?>
        <label class="<?= e($full) ?>"><?= $label ?>
          <select name="<?= e($name) ?>" <?= $requiredAttr ?>>
            <option value="">Choisir...</option>
            <?php foreach (($def['options'] ?? []) as $option): ?>
              <option value="<?= e($option) ?>" <?= (string)$value === (string)$option ? 'selected' : '' ?>><?= e($option) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
    <?php elseif ($type === 'textarea'): ?>
        <label class="<?= e($full) ?>"><?= $label ?><textarea name="<?= e($name) ?>" <?= $requiredAttr ?>><?= e($value) ?></textarea></label>
    <?php else: ?>
        <label class="<?= e($full) ?>"><?= $label ?>
          <?php if ($withOpenIcon): ?>
            <span class="url-open-field">
              <input type="<?= e($type) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" data-url-open-source <?= $requiredAttr ?>>
              <?= renderUrlOpenIcon((string)$value) ?>
            </span>
          <?php else: ?>
            <input type="<?= e($type) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" <?= $type === 'number' ? 'step="any"' : '' ?> <?= $type === 'password' ? 'autocomplete="new-password"' : '' ?> <?= $requiredAttr ?>>
          <?php endif; ?>
        </label>
    <?php endif;
    return ob_get_clean();
}

function renderUrlOpenIcon(string $url): string
{
    $url = trim($url);
    $hidden = $url === '' ? ' hidden' : '';
    return '<a class="url-open-icon" href="' . e($url) . '" target="_blank" rel="noopener noreferrer" title="Ouvrir le lien" aria-label="Ouvrir le lien"' . $hidden . '>↗</a>';
}

function applyContextualOptions(array $schema, string $rubrique, array $data): array
{
    if ($rubrique === 'chargesAnnuelles' && isset($schema['niveau1']['bienConcerne'])) {
        $schema['niveau1']['bienConcerne']['type'] = 'select';
        $schema['niveau1']['bienConcerne']['options'] = immobilierOptions($data);
    }
    if ($rubrique === 'revenus' && isset($schema['niveau1']['bienImmobilierAssocie'])) {
        $schema['niveau1']['bienImmobilierAssocie']['type'] = 'select';
        $schema['niveau1']['bienImmobilierAssocie']['options'] = immobilierOptions($data, true);
    }
    return $schema;
}

function ficheNavigation(array $data, string $rubrique, string $id): array
{
    $empty = ['show' => false, 'previous' => null, 'next' => null, 'position' => 0, 'total' => 0];
    if ($rubrique === 'profil' || $id === '') {
        return $empty;
    }
    $entries = array_values($data[$rubrique] ?? []);
    $currentIndex = null;
    foreach ($entries as $index => $entry) {
        if (($entry['id'] ?? '') === $id) {
            $currentIndex = $index;
            break;
        }
    }
    if ($currentIndex === null) {
        return $empty;
    }
    return [
        'show' => count($entries) > 1,
        'previous' => ficheNavigationTarget($rubrique, $entries[$currentIndex - 1] ?? null),
        'next' => ficheNavigationTarget($rubrique, $entries[$currentIndex + 1] ?? null),
        'position' => $currentIndex + 1,
        'total' => count($entries),
    ];
}

function ficheNavigationTarget(string $rubrique, ?array $entry): ?array
{
    if (!$entry || empty($entry['id'])) {
        return null;
    }
    return [
        'url' => 'form.php?rubrique=' . rawurlencode($rubrique) . '&id=' . rawurlencode((string)$entry['id']),
        'title' => trim((string)($entry['titre'] ?? 'Fiche')),
    ];
}

function immobilierOptions(array $data, bool $includeEmpty = false): array
{
    $options = $includeEmpty ? [''] : ['Autre'];
    foreach (($data['immobilier'] ?? []) as $entry) {
        $label = trim((string)($entry['titre'] ?? ''));
        if ($label !== '' && !in_array($label, $options, true)) {
            $options[] = $label;
        }
    }
    if (!in_array('Autre', $options, true)) {
        $options[] = 'Autre';
    }
    return $options;
}
