<?php
// index.php

require_once("db_pass.php");

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_machine'])) {
  $stmt = $pdo->prepare("UPDATE machines SET nom = ?, ip = ?, mac = ?, description = ?, hostnames = ? WHERE id = ?");
  $stmt->execute([
    $_POST['nom'],
    $_POST['ip'],
    $_POST['mac'],
    $_POST['description'],
    $_POST['hostnames'],
    $_POST['modifier_machine']
  ]);

  header("Location: index.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_service_machine_id'])) {
  $stmt = $pdo->prepare("INSERT INTO services (machine_id, nom, port, protocole) VALUES (?, ?, ?, ?)");
  $stmt->execute([
    $_POST['ajouter_service_machine_id'],
    $_POST['service_nom'],
    $_POST['service_port'],
    $_POST['service_protocole'] ?? 'TCP'
  ]);
  $service_id = $pdo->lastInsertId();

  if (!empty($_POST['domaine'])) {
    foreach ($_POST['domaine'] as $i => $dom) {
      if (!empty($dom)) {
        $stmt = $pdo->prepare("INSERT INTO domaines (service_id, domaine, ssl_enabled) VALUES (?, ?, ?)");
        $stmt->execute([
          $service_id,
          $dom,
          isset($_POST['ssl_enabled'][$i]) ? 1 : 0
        ]);
      }
    }
  }
  header("Location: index.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_domaine_service_id'])) {
  $stmt = $pdo->prepare("INSERT INTO domaines (service_id, domaine, ssl_enabled) VALUES (?, ?, ?)");
  $stmt->execute([
    $_POST['ajouter_domaine_service_id'],
    $_POST['domaine'],
    isset($_POST['ssl_enabled']) ? 1 : 0
  ]);
  header("Location: index.php");
  exit;
}



// Supprimer une machine
if (isset($_GET['supprimer']) && is_numeric($_GET['supprimer'])) {
  $stmt = $pdo->prepare("DELETE FROM machines WHERE id = ?");
  $stmt->execute([$_GET['supprimer']]);
  header("Location: index.php");
  exit;
}

// Ajouter une machine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_machine'])) {
  $stmt = $pdo->prepare("INSERT INTO machines (nom, ip, mac, description, hostnames) VALUES (?, ?, ?, ?, ?)");
  $stmt->execute([
    $_POST['nom'],
    $_POST['ip'],
    $_POST['mac'],
    $_POST['description'],
    $_POST['hostnames']
  ]);

  $machine_id = $pdo->lastInsertId();

  if (!empty($_POST['service_nom']) && is_array($_POST['service_nom'])) {
    foreach ($_POST['service_nom'] as $i => $nom) {
      if (!empty($nom) && !empty($_POST['service_port'][$i])) {
        $stmt = $pdo->prepare("INSERT INTO services (machine_id, nom, port, protocole) VALUES (?, ?, ?, ?)");
        $stmt->execute([
          $machine_id,
          $nom,
          $_POST['service_port'][$i],
          $_POST['service_protocole'][$i] ?? 'TCP'
        ]);
        $service_id = $pdo->lastInsertId();

        if (!empty($_POST['domaine'][$i])) {
          foreach ($_POST['domaine'][$i] as $j => $domaine) {
            if (!empty($domaine)) {
              $stmt = $pdo->prepare("INSERT INTO domaines (service_id, domaine, ssl_enabled) VALUES (?, ?, ?)");
              $stmt->execute([
                $service_id,
                $domaine,
                isset($_POST['ssl_enabled'][$i][$j]) ? 1 : 0
              ]);
            }
          }
        }
      }
    }
  }

  header("Location: index.php");
  exit;
}

// Récupérer toutes les machines avec leurs services et domaines
$machines = $pdo->query("SELECT * FROM machines")->fetchAll(PDO::FETCH_ASSOC);
$services_by_machine = [];
$domaines_by_service = [];

foreach ($machines as $machine) {
  $services = $pdo->prepare("SELECT * FROM services WHERE machine_id = ?");
  $services->execute([$machine['id']]);
  $services = $services->fetchAll(PDO::FETCH_ASSOC);
  $services_by_machine[$machine['id']] = $services;

  foreach ($services as $service) {
    $domaines = $pdo->prepare("SELECT * FROM domaines WHERE service_id = ?");
    $domaines->execute([$service['id']]);
    $domaines_by_service[$service['id']] = $domaines->fetchAll(PDO::FETCH_ASSOC);
  }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Inventaire des machines</title>
  <link rel="stylesheet" href="./css/style.css">

  <script src="./js/script.js"></script>
</head>

<body>
  <h1>Inventaire des machines</h1>

  <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
    <input type="checkbox" id="toggleReadOnly" onchange="toggleReadOnly()">
    🔒 Mode lecture seule
  </label>

  <div id="machines-list" style="display: flex; flex-wrap: wrap; gap: 20px;">
    <?php foreach ($machines as $machine): ?>
      <div class="machine" style="max-width: 30%;">
        <div style="position: absolute; top: 1rem; right: 1rem; display: flex; gap: 10px;">
          <button class="edit-button" type="button" title="Modifier" onclick="document.getElementById('edit-<?= $machine['id'] ?>').classList.toggle('hidden')">✏️</button>
          <a class="delete-button" href="?supprimer=<?= $machine['id'] ?>" onclick="return confirm('Supprimer cette machine ?')" title="Supprimer">🗑️</a>
        </div>
        <h3><?= htmlspecialchars($machine['nom']) ?></h3>
        <p>IP: <?= htmlspecialchars($machine['ip']) ?> – MAC: <?= htmlspecialchars($machine['mac']) ?></p>
        <?php if (!empty($machine['hostnames'])): ?>
          <p><strong>Hostnames :</strong> <?= htmlspecialchars($machine['hostnames'] ?? '') ?></p>
        <?php endif; ?>
        <p><em><?= nl2br(htmlspecialchars($machine['description'])) ?></em></p>
        <?php foreach ($services_by_machine[$machine['id']] as $service): ?>
          <div class="service">
            🔧 <?= htmlspecialchars($service['nom']) ?> (<?= $service['protocole'] ?> <?= $service['port'] ?>)
            <?php foreach ($domaines_by_service[$service['id']] as $domaine): ?>
              <div class="domaine domain-block">
                🌐 <a class="nolink" href="<?= htmlspecialchars($domaine['domaine']) ?>" target="_blank"><?= htmlspecialchars($domaine['domaine']) ?></a> <?= $domaine['ssl_enabled'] ? '(SSL)' : '' ?>
              </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="inline-action add-button" onclick="document.getElementById('add-domaine-<?= $service['id'] ?>').classList.toggle('hidden')">+ Ajouter un domaine</button>

          <div class="form-inline hidden" id="add-domaine-<?= $service['id'] ?>">
            <form method="post">
              <input type="hidden" name="ajouter_domaine_service_id" value="<?= $service['id'] ?>">
              <div class="domain-block">
                <label>Domaine: <input type="text" name="domaine"></label>
                <label><input type="checkbox" name="ssl_enabled"> SSL</label>
              </div>
              <button type="submit">💾 Ajouter domaine</button>
            </form>
          </div>

        <?php endforeach; ?>
        <div class="hidden" id="edit-<?= $machine['id'] ?>">
          <form method="post">
            <input type="hidden" name="modifier_machine" value="<?= $machine['id'] ?>">
            <label>Nom: <input type="text" name="nom" value="<?= htmlspecialchars($machine['nom']) ?>"></label><br>
            <label>IP: <input type="text" name="ip" value="<?= htmlspecialchars($machine['ip']) ?>"></label><br>
            <label>MAC: <input type="text" name="mac" value="<?= htmlspecialchars($machine['mac']) ?>"></label><br>
            <label>Hostnames :
              <input type="text" name="hostnames" value="<?= htmlspecialchars($machine['hostnames'] ?? '') ?>">
            </label>

            <label>Description:<br>
              <textarea name="description"><?= htmlspecialchars($machine['description']) ?></textarea>
            </label><br>
            <button type="submit">💾 Enregistrer</button>
          </form>
        </div>

        <button type="button" class="inline-action add-button" onclick="document.getElementById('add-service-<?= $machine['id'] ?>').classList.toggle('hidden')">+ Ajouter un service</button>

        <div class="form-inline hidden" id="add-service-<?= $machine['id'] ?>">
          <form method="post">
            <input type="hidden" name="ajouter_service_machine_id" value="<?= $machine['id'] ?>">
            <label>Nom: <input type="text" name="service_nom"></label><br>
            <label>Port: <input type="number" name="service_port"></label><br>
            <label>Protocole:
              <select name="service_protocole">
                <option value="TCP">TCP</option>
                <option value="UDP">UDP</option>
              </select>
            </label><br>
            <div class="domain-block">
              <label>Domaine: <input type="text" name="domaine[0]"></label>
              <label><input type="checkbox" name="ssl_enabled[0]"> SSL</label>
            </div>
            <button type="submit">💾 Ajouter service</button>
          </form>
        </div>


      </div>
    <?php endforeach; ?>
  </div>

  <button type="button" onclick="toggleForm()">+ Ajouter une machine</button>


  <form method="post" class="form-section hidden add-button" id="machine-form">
    <h2>Ajouter une machine</h2>
    <input type="hidden" name="ajouter_machine" value="1">
    <label>Nom: <input type="text" name="nom" required></label><br>
    <label>IP: <input type="text" name="ip" required></label><br>
    <label>MAC: <input type="text" name="mac"></label><br>
    <label>Hostnames (séparés par des virgules) :
      <input type="text" name="hostnames" placeholder="ex: nas.local, nas.domain.local">
    </label>
    <label>Description:<br>
      <textarea name="description" rows="3" cols="50"></textarea>
    </label><br>

    <div id="services-container"></div>
    <button type="button" onclick="addService()">+ Ajouter un service</button><br><br>
    <button type="submit">💾 Enregistrer</button>
  </form>

  <script>
    function toggleReadOnly() {
      const isChecked = document.getElementById('toggleReadOnly').checked;
      document.querySelectorAll('.add-button, .delete-button, .edit-button, .inline-action, .form-section').forEach(el => {
        el.classList.toggle('hidden', isChecked);
      });
    }


    document.addEventListener('DOMContentLoaded', () => {
      document.getElementById('toggleReadOnly').checked = true;
      toggleReadOnly();
    });
  </script>
</body>

</html>