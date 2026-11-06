<?php
/** Super Schoon – instellingen voor de koppeling met Teamleader Focus. */
declare(strict_types=1);
define('SUPERSCHOON', true);
require __DIR__ . '/auth.php';
require __DIR__ . '/tl_lib.php';

if (!dash_ingelogd()) { header('Location: ./'); exit; }
$_SESSION['tijd'] = time();
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$https = dash_https() || ($_SERVER['HTTP_HOST'] ?? '') === 'www.superschoon.be';
$basis = ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'www.superschoon.be');
$redirect = $basis . '/dashboard/teamleader.php';
$melding = ''; $fout = '';
$c = tl_cfg();

// terugkeer van Teamleader na het koppelen
if (isset($_GET['code'])) {
    if (!hash_equals((string)($_SESSION['tl_state'] ?? ''), (string)($_GET['state'] ?? ''))) $fout = 'De koppeling kon niet gecontroleerd worden. Probeer opnieuw.';
    else {
        try { tl_tokens(['grant_type' => 'authorization_code', 'code' => (string)$_GET['code'], 'redirect_uri' => $redirect]); $melding = 'Teamleader is gekoppeld. Kies hieronder de instellingen.'; }
        catch (Throwable $t) { $fout = $t->getMessage(); }
    }
    unset($_SESSION['tl_state']);
    $c = tl_cfg();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!hash_equals(dash_csrf(), (string)($_POST['csrf'] ?? ''))) { $fout = 'Sessie verlopen. Herlaad de pagina.'; }
    else {
        $actie = (string)($_POST['actie'] ?? '');
        if ($actie === 'app') {
            $c['client_id'] = trim((string)$_POST['client_id']);
            if (trim((string)$_POST['client_secret']) !== '') $c['client_secret'] = trim((string)$_POST['client_secret']);
            tl_bewaar_cfg($c); $melding = 'Gegevens van de app bewaard.';
        } elseif ($actie === 'koppel') {
            $_SESSION['tl_state'] = bin2hex(random_bytes(16));
            header('Location: ' . TL_AUTH . '?' . http_build_query(['client_id' => $c['client_id'], 'response_type' => 'code', 'redirect_uri' => $redirect, 'state' => $_SESSION['tl_state']]));
            exit;
        } elseif ($actie === 'instellingen') {
            foreach (['department_id', 'pipeline_id', 'fase_nieuw', 'fase_aanvaard', 'fase_betaald', 'tax_rate_id', 'responsible_user_id', 'source_id'] as $k) $c[$k] = trim((string)($_POST[$k] ?? ''));
            $c['modus'] = in_array($_POST['modus'] ?? '', ['uit', 'test', 'aan'], true) ? $_POST['modus'] : 'uit';
            tl_bewaar_cfg($c); $melding = 'Instellingen bewaard.';
        } elseif ($actie === 'webhook') {
            try {
                $c['webhook_sleutel'] = $c['webhook_sleutel'] ?? bin2hex(random_bytes(16)); tl_bewaar_cfg($c);
                tl_api('webhooks.register', ['url' => $basis . '/tl-webhook.php?k=' . $c['webhook_sleutel'], 'types' => ['invoice.paymentRegistered']]);
                $c['webhook'] = date('c'); tl_bewaar_cfg($c); $melding = 'Betalingsmeldingen zijn geactiveerd.';
            } catch (Throwable $t) { $fout = $t->getMessage(); }
        } elseif ($actie === 'ontkoppel') {
            unset($c['access_token'], $c['refresh_token'], $c['verloopt']); $c['modus'] = 'uit'; tl_bewaar_cfg($c); $melding = 'Teamleader is ontkoppeld.';
        }
        $c = tl_cfg();
    }
}

// keuzelijsten ophalen
$lijsten = []; $wie = '';
if (!empty($c['refresh_token'])) {
    try {
        $wie = tl_api('users.me')['data']['first_name'] ?? '';
        $lijsten['dep'] = tl_api('departments.list', ['filter' => ['status' => ['active']]])['data'] ?? [];
        $lijsten['pipe'] = tl_api('dealPipelines.list', ['page' => ['size' => 50]])['data'] ?? [];
        if ($c['pipeline_id']) $lijsten['fase'] = tl_api('dealPhases.list', ['filter' => ['deal_pipeline_id' => $c['pipeline_id']], 'page' => ['size' => 50]])['data'] ?? [];
        if ($c['department_id']) $lijsten['btw'] = tl_api('taxRates.list', ['filter' => ['department_id' => $c['department_id']], 'page' => ['size' => 50]])['data'] ?? [];
        $lijsten['users'] = tl_api('users.list', ['page' => ['size' => 50]])['data'] ?? [];
        $lijsten['bron'] = tl_api('dealSources.list', ['page' => ['size' => 50]])['data'] ?? [];
    } catch (Throwable $t) { $fout = $fout ?: 'Teamleader antwoordt niet zoals verwacht: ' . $t->getMessage(); }
}
function kies(string $naam, array $lijst, string $huidig, string $leeg, callable $label): string {
    $o = '<select name="' . $naam . '" id="' . $naam . '"><option value="">' . htmlspecialchars($leeg) . '</option>';
    foreach ($lijst as $x) $o .= '<option value="' . htmlspecialchars($x['id']) . '"' . ($x['id'] === $huidig ? ' selected' : '') . '>' . htmlspecialchars($label($x)) . '</option>';
    return $o . '</select>';
}
$log = is_file(stats_dir() . '/teamleader.log') ? array_slice(file(stats_dir() . '/teamleader.log'), -30) : [];
$v = fn($f) => $f . '?v=' . substr(md5((string)@filemtime(__DIR__ . '/' . $f)), 0, 8);
$csrf = $h(dash_csrf());
?><!doctype html>
<html lang="nl-BE"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title>Teamleader | Dashboard Super Schoon</title><link rel="icon" href="/favicon.svg" type="image/svg+xml"><link rel="stylesheet" href="<?= $v('dashboard.css') ?>"></head>
<body>
<header class="kop"><div class="kop-in">
  <a class="merk" href="./"><img src="/assets/img/logo-superschoon-wit.svg" alt="Super Schoon" width="180" height="31"></a>
  <span class="kop-titel">Teamleader-koppeling</span>
  <nav class="kop-links" style="margin-left:auto"><a href="./">Terug naar dashboard</a><a href="./?uit=1">Afmelden</a></nav>
</div></header>
<main class="wrap">
  <?php if ($melding): ?><p class="paneel" role="status"><span class="status goed">✓</span> <?= $h($melding) ?></p><?php endif; ?>
  <?php if ($fout): ?><p class="paneel" role="alert"><span class="status slecht">✕</span> <?= $h($fout) ?></p><?php endif; ?>

  <section class="paneel">
    <h2>Status</h2>
    <ul class="checks" style="margin-top:12px">
      <li><?= $c['client_id'] ? '<span class="status goed">✓</span>' : '<span class="status slecht">✕</span>' ?><span>App-gegevens ingevuld</span></li>
      <li><?= !empty($c['refresh_token']) ? '<span class="status goed">✓</span>' : '<span class="status slecht">✕</span>' ?><span>Gekoppeld<?= $wie ? ' (als ' . $h($wie) . ')' : '' ?></span></li>
      <li><?= tl_gekoppeld($c) ? '<span class="status goed">✓</span>' : '<span class="status slecht">✕</span>' ?><span>Afdeling, pijplijn, fasen en btw gekozen</span></li>
      <li><?= !empty($c['webhook']) ? '<span class="status goed">✓</span>' : '<span class="status let">!</span>' ?><span>Betalingsmeldingen actief</span></li>
      <li><span class="status <?= $c['modus'] === 'aan' ? 'goed' : 'let' ?>"><?= $c['modus'] === 'aan' ? 'AAN' : ($c['modus'] === 'test' ? 'TEST' : 'UIT') ?></span><span><?= $c['modus'] === 'aan' ? 'Alle aanvragen gaan naar Teamleader' : ($c['modus'] === 'test' ? 'Alleen aanvragen met een @ssprojects.be-adres; voorschotfacturen blijven concept' : 'Er gaat niets naar Teamleader') ?></span></li>
    </ul>
  </section>

  <section class="paneel">
    <h2>1. App in Teamleader</h2>
    <p>Maak in de Teamleader Marketplace een eigen integratie aan (Ontwikkelaars → Integratie toevoegen) met deze <b>redirect-URL</b>:</p>
    <p><code style="user-select:all;background:var(--bg);padding:4px 8px;border-radius:4px"><?= $h($redirect) ?></code></p>
    <p class="zacht">Geef de integratie toegang tot contacten, deals, offertes, facturen, instellingen (afdelingen en btw-tarieven), gebruikers en webhooks. Vul daarna de client-ID en het client-secret van die integratie hier in.</p>
    <form method="post" class="ww" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="actie" value="app">
      <label>Client-ID <input name="client_id" value="<?= $h($c['client_id']) ?>" required></label>
      <label>Client-secret <input name="client_secret" type="password" placeholder="<?= $c['client_secret'] ? '•••••••• (bewaard)' : '' ?>"></label>
      <button class="knop">Bewaren</button>
    </form>
    <?php if ($c['client_id'] && $c['client_secret']): ?>
    <form method="post" style="margin-top:14px"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="actie" value="koppel">
      <button class="knop"><?= !empty($c['refresh_token']) ? 'Opnieuw koppelen' : 'Koppelen met Teamleader' ?></button></form>
    <?php endif; ?>
  </section>

  <?php if (!empty($c['refresh_token'])): ?>
  <section class="paneel">
    <h2>2. Instellingen</h2>
    <form method="post" class="ww" style="max-width:520px">
      <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="actie" value="instellingen">
      <label>Bedrijfsentiteit (afdeling) <?= kies('department_id', $lijsten['dep'] ?? [], $c['department_id'], 'Kies…', fn($x) => $x['name']) ?></label>
      <label>Btw-tarief voor reiniging (21%) <?= kies('tax_rate_id', $lijsten['btw'] ?? [], $c['tax_rate_id'] ?: (string)(array_values(array_filter($lijsten['btw'] ?? [], fn($x) => abs((float)$x['rate'] - 0.21) < 0.001))[0]['id'] ?? ''), 'Kies eerst een afdeling en bewaar', fn($x) => $x['description'] . ' (' . round((float)$x['rate'] * 100) . '%)') ?></label>
      <label>Pijplijn <?= kies('pipeline_id', $lijsten['pipe'] ?? [], $c['pipeline_id'], 'Kies…', fn($x) => $x['name']) ?></label>
      <label>Fase bij nieuwe offerte <?= kies('fase_nieuw', $lijsten['fase'] ?? [], $c['fase_nieuw'], 'Kies eerst een pijplijn en bewaar', fn($x) => $x['name']) ?></label>
      <label>Fase na online aanvaarding <?= kies('fase_aanvaard', $lijsten['fase'] ?? [], $c['fase_aanvaard'], '(niet verplaatsen)', fn($x) => $x['name']) ?></label>
      <label>Fase na betaling voorschot <?= kies('fase_betaald', $lijsten['fase'] ?? [], $c['fase_betaald'], '(niet verplaatsen)', fn($x) => $x['name']) ?></label>
      <label>Verantwoordelijke (optioneel) <?= kies('responsible_user_id', $lijsten['users'] ?? [], $c['responsible_user_id'], '(standaard)', fn($x) => trim(($x['first_name'] ?? '') . ' ' . ($x['last_name'] ?? ''))) ?></label>
      <label>Herkomst van de deal (optioneel) <?= kies('source_id', $lijsten['bron'] ?? [], $c['source_id'], '(geen)', fn($x) => $x['name']) ?></label>
      <fieldset style="border:1px solid var(--line);border-radius:6px;padding:10px 14px"><legend>Koppeling</legend>
        <?php foreach (['uit' => 'Uit', 'test' => 'Test – alleen aanvragen met een @ssprojects.be-adres, voorschotfactuur blijft concept', 'aan' => 'Aan – alle aanvragen'] as $m => $l): ?>
        <label class="radio"><input type="radio" name="modus" value="<?= $m ?>"<?= $c['modus'] === $m ? ' checked' : '' ?>> <?= $h($l) ?></label>
        <?php endforeach; ?>
      </fieldset>
      <button class="knop">Bewaren</button>
    </form>
  </section>

  <section class="paneel">
    <h2>3. Betalingsmeldingen</h2>
    <p>Zodra een klant het voorschot online betaalt, laat Teamleader het de website weten. De deal gaat dan automatisch naar de gekozen fase en de offertepagina toont "voorschot ontvangen".</p>
    <form method="post"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="actie" value="webhook">
      <button class="knop licht"><?= !empty($c['webhook']) ? 'Opnieuw activeren' : 'Activeren' ?></button></form>
  </section>

  <section class="paneel">
    <h2>Logboek</h2>
    <?php if ($log): ?><pre style="white-space:pre-wrap;font-size:12.5px;background:var(--bg);padding:12px;border-radius:6px;max-height:320px;overflow:auto"><?= $h(implode('', array_reverse($log))) ?></pre>
    <?php else: ?><p class="leeg">Nog geen activiteit.</p><?php endif; ?>
    <form method="post" onsubmit="return confirm('Teamleader ontkoppelen?')"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="actie" value="ontkoppel">
      <button class="knop licht">Ontkoppelen</button></form>
  </section>
  <?php endif; ?>
</main>
</body></html>
