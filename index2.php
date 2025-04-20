<?php
// index.php

// Connexion MySQL
$pdo = new PDO("mysql:host=localhost;dbname=inventaire_machines;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Supprimer une machine
if (isset($_GET['supprimer']) && is_numeric($_GET['supprimer'])) {
    $stmt = $pdo->prepare("DELETE FROM machines WHERE id = ?");
    $stmt->execute([$_GET['supprimer']]);
    header("Location: index.php");
    exit;
}

// Ajouter une machine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_machine'])) {
    $stmt = $pdo->prepare("INSERT INTO machines (nom, ip, mac, description) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $_POST['nom'],
        $_POST['ip'],
        $_POST['mac'],
        $_POST['description']
    ]);
    $machine_id = $pdo->lastInsertId();

    if (!empty($_POST['services'])) {
        foreach ($_POST['services'] as $service) {
            if (!empty($service['nom']) && !empty($service['port'])) {
                $stmt = $pdo->prepare("INSERT INTO services (machine_id, nom, port, protocole) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $machine_id,
                    $service['nom'],
                    $service['port'],
                    $service['protocole'] ?? 'TCP'
                ]);
                $service_id = $pdo->lastInsertId();

                if (!empty($service['domaines'])) {
                    foreach ($service['domaines'] as $domaine) {
                        if (!empty($domaine['domaine'])) {
                            $stmt = $pdo->prepare("INSERT INTO domaines (service_id, domaine, ssl_enabled) VALUES (?, ?, ?)");
                            $stmt->execute([
                                $service_id,
                                $domaine['domaine'],
                                isset($domaine['ssl_enabled']) ? 1 : 0
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
  <style>
    body { font-family: Arial, sans-serif; background: #f9f9f9; padding: 2em; }
    h1, h2 { color: #333; }
    .machine { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .service { margin-left: 20px; padding-left: 10px; border-left: 2px solid #ccc; }
    .domaine { margin-left: 40px; font-style: italic; }
    .actions { float: right; }
    .hidden { display: none; }
    button.toggle { margin-top: 10px; }
    input, textarea, select { width: 100%; padding: 6px; margin: 5px 0; }
    fieldset { background: #f0f0f0; border: 1px solid #ccc; margin-top: 10px; padding: 10px; border-radius: 5px; }
  </style>
  <script>
    function addServiceField() {
      const container = document.getElementById('services-container');
      const index = container.children.length;
      const html = `
        <fieldset>
          <legend>Service</legend>
          <label>Nom : <input type="text" name="services[${index}][nom]"></label>
          <label>Port : <input type="number" name="services[${index}][port]"></label>
          <label>Protocole :
            <select name="services[${index}][protocole]">
              <option value="TCP">TCP</option>
              <option value="UDP">UDP</option>
            </select>
          </label>
          <div class="domaines">
            <label>Domaine : <input type="text" name="services[${index}][domaines][0][domaine]"></label>
            <label><input type="checkbox" name="services[${index}][domaines][0][ssl_enabled]"> SSL</label>
          </div>
        </fieldset>
      `;
      container.insertAdjacentHTML('beforeend', html);
    }
  </script>
</head>
<body>
  <h1>Inventaire des machines</h1>

  <?php foreach ($machines as $machine): ?>
    <div class="machine">
      <div class="actions">
        <a href="?supprimer=<?= $machine['id'] ?>" onclick="return confirm('Supprimer cette machine ?')">🗑️</a>
        <button onclick="this.nextElementSibling.classList.toggle('hidden')">✏️ Modifier</button>
      </div>
      <strong><?= htmlspecialchars($machine['nom']) ?></strong> – IP: <?= $machine['ip'] ?> – MAC: <?= $machine['mac'] ?><br>
      <em><?= nl2br(htmlspecialchars($machine['description'])) ?></em>

      <?php foreach ($services_by_machine[$machine['id']] as $service): ?>
        <div class="service">
          🔧 <?= htmlspecialchars($service['nom']) ?> (<?= $service['protocole'] ?> <?= $service['port'] ?>)
          <?php foreach ($domaines_by_service[$service['id']] as $domaine): ?>
            <div class="domaine">
              🌐 <?= htmlspecialchars($domaine['domaine']) ?> <?= $domaine['ssl_enabled'] ? '(SSL)' : '' ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>

      <div class="hidden">
        <!-- Édition possible ici plus tard -->
        <em>(Formulaire d'édition à implémenter)</em>
      </div>
    </div>
  <?php endforeach; ?>

  <h2>Ajouter une machine</h2>
  <form method="post">
    <input type="hidden" name="ajouter_machine" value="1">
    <label>Nom : <input type="text" name="nom" required></label>
    <label>IP : <input type="text" name="ip" required></label>
    <label>MAC : <input type="text" name="mac"></label>
    <label>Description :<br>
      <textarea name="description" rows="3"></textarea>
    </label>

    <div id="services-container"></div>
    <button type="button" onclick="addServiceField()">+ Ajouter un service</button>

    <br><br>
    <button type="submit">Ajouter la machine</button>
  </form>
</body>
</html>
