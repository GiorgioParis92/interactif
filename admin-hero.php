<?php
require_once 'includes/db.php';

/* =====================================================
   ADMIN — Configuration du Hero-Grid (homepage)
   Accessible via : /admin-hero.php
===================================================== */

define('ADMIN_PASSWORD', 'mia2026');
session_start();

if (isset($_GET['logout'])) {
  session_destroy();
  header('Location: admin-hero.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
  if ($_POST['password'] === ADMIN_PASSWORD) {
    $_SESSION['admin'] = true;
    header('Location: admin-hero.php');
    exit;
  } else {
    $error = 'Mot de passe incorrect.';
  }
}

$logged_in = !empty($_SESSION['admin']);
$message   = null;

$video_ext   = ['mp4', 'webm', 'ogg', 'mov'];
$image_ext   = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
$allowed_ext = array_merge($image_ext, $video_ext);
$max_size    = 100 * 1024 * 1024;

// ─── ACTIONS ───
if ($logged_in && $_SERVER['REQUEST_METHOD'] === 'POST') {

  // Upload a new file into a hero slot
  if (isset($_POST['upload_hero_media']) && isset($_FILES['hero_file'])) {
    $file     = $_FILES['hero_file'];
    $position = (int) ($_POST['slot_position'] ?? 0);

    if ($position < 1 || $position > 6) {
      $message = ['type' => 'error', 'text' => 'Position invalide.'];
    } else {
      $ext     = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
      $isImage = in_array($ext, $image_ext, true);
      $isVideo = in_array($ext, $video_ext, true);

      if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $message = ['type' => 'error', 'text' => "Erreur lors de l'upload (code " . ($file['error'] ?? '?') . ")."];
      } elseif (!$isImage && !$isVideo) {
        $message = ['type' => 'error', 'text' => 'Format non autorisé.'];
      } elseif (($file['size'] ?? 0) > $max_size) {
        $message = ['type' => 'error', 'text' => 'Fichier trop lourd (max 100 Mo).'];
      } else {
        $filename = 'hero-' . $position . '-' . time() . '.' . $ext;
        $destDir  = __DIR__ . '/assets/' . ($isVideo ? 'videos' : 'images') . '/';

        if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
          $message = ['type' => 'error', 'text' => 'Impossible de créer le dossier de destination.'];
        } elseif (move_uploaded_file($file['tmp_name'], $destDir . $filename)) {
          $existing = $pdo->prepare('SELECT id FROM home_medias WHERE position = ?');
          $existing->execute([$position]);

          if ($existing->fetch()) {
            $pdo->prepare('UPDATE home_medias SET filename = ? WHERE position = ?')
                ->execute([$filename, $position]);
          } else {
            $pdo->prepare('INSERT INTO home_medias (position, filename) VALUES (?, ?)')
                ->execute([$position, $filename]);
          }
          $message = ['type' => 'success', 'text' => "Slot {$position} mis à jour (upload)."];
        } else {
          $message = ['type' => 'error', 'text' => 'Impossible de déplacer le fichier.'];
        }
      }
    }
  }

  // Pick an existing project media
  if (isset($_POST['pick_hero_media'])) {
    $position = (int) ($_POST['slot_position'] ?? 0);
    $filename = trim($_POST['picked_filename'] ?? '');

    if ($position < 1 || $position > 6) {
      $message = ['type' => 'error', 'text' => 'Position invalide.'];
    } elseif ($filename === '') {
      $message = ['type' => 'error', 'text' => 'Aucun média sélectionné.'];
    } else {
      $existing = $pdo->prepare('SELECT id FROM home_medias WHERE position = ?');
      $existing->execute([$position]);

      if ($existing->fetch()) {
        $pdo->prepare('UPDATE home_medias SET filename = ? WHERE position = ?')
            ->execute([$filename, $position]);
      } else {
        $pdo->prepare('INSERT INTO home_medias (position, filename) VALUES (?, ?)')
            ->execute([$position, $filename]);
      }
      $message = ['type' => 'success', 'text' => "Slot {$position} mis à jour (média existant)."];
    }
  }

  // Remove a hero slot
  if (isset($_POST['remove_hero_media'])) {
    $position = (int) ($_POST['slot_position'] ?? 0);

    if ($position >= 1 && $position <= 6) {
      $pdo->prepare('DELETE FROM home_medias WHERE position = ?')->execute([$position]);
      $message = ['type' => 'success', 'text' => "Slot {$position} vidé."];
    }
  }
}

// ─── DONNÉES ───
$slots = [];
if ($logged_in) {
  $rows = $pdo->query('SELECT * FROM home_medias ORDER BY position')->fetchAll();
  foreach ($rows as $r) {
    $slots[(int) $r['position']] = $r;
  }

  $project_medias = $pdo->query(
    "SELECT filename, brand, type FROM projects
     WHERE filename IS NOT NULL AND filename != ''
     ORDER BY brand ASC"
  )->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin — Hero-Grid · MIA</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --coral: #E8492A;
      --cobalt: #1D3FA6;
      --ink: #1A1612;
      --stone: #8C847A;
      --cream: #F5F0E8;
      --white: #FDFCFA;
      --ease: cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--cream);
      color: var(--ink);
      min-height: 100vh;
    }

    /* ── Login ── */
    .login-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .login-box {
      background: var(--white); border: 1px solid rgba(26,22,18,0.1);
      border-radius: 16px; padding: 56px 48px; width: 100%; max-width: 400px; text-align: center;
    }
    .login-logo { font-family: 'Playfair Display', serif; font-size: 2.5rem; font-weight: 700; margin-bottom: 8px; }
    .login-logo span { color: var(--coral); }
    .login-subtitle {
      font-family: 'DM Mono', monospace; font-size: 11px; letter-spacing: 0.12em;
      text-transform: uppercase; color: var(--stone); margin-bottom: 40px;
    }
    .login-box input[type="password"] {
      width: 100%; padding: 14px 18px; border: 1.5px solid rgba(26,22,18,0.15);
      border-radius: 4px; font-family: inherit; font-size: 15px; outline: none;
      transition: border-color 0.2s; margin-bottom: 12px; background: var(--cream);
    }
    .login-box input[type="password"]:focus { border-color: var(--coral); }
    .error-msg { color: var(--coral); font-size: 13px; margin-bottom: 16px; }

    .btn-primary {
      width: 100%; padding: 14px; background: var(--coral); color: var(--white);
      border: none; border-radius: 4px; font-family: 'DM Sans', sans-serif;
      font-size: 15px; font-weight: 500; cursor: pointer; transition: background 0.2s;
    }
    .btn-primary:hover { background: var(--ink); }

    /* ── Header ── */
    .admin-header {
      background: var(--ink); padding: 20px 48px;
      display: flex; align-items: center; justify-content: space-between; gap: 20px;
    }
    .admin-header-logo { font-family: 'Playfair Display', serif; font-size: 1.4rem; font-weight: 700; color: var(--white); }
    .admin-header-logo span { color: var(--coral); }
    .admin-header-meta { display: flex; align-items: center; gap: 24px; }
    .admin-title-bar {
      font-family: 'DM Mono', monospace; font-size: 11px; letter-spacing: 0.12em;
      text-transform: uppercase; color: var(--stone);
    }
    .btn-nav, .btn-logout {
      font-family: 'DM Mono', monospace; font-size: 11px; letter-spacing: 0.1em;
      text-transform: uppercase; padding: 8px 16px; border-radius: 4px;
      border: 1px solid rgba(253,252,250,0.2); background: transparent;
      color: var(--white); cursor: pointer; transition: background 0.2s; text-decoration: none;
    }
    .btn-nav:hover, .btn-logout:hover { background: rgba(253,252,250,0.1); }

    /* ── Body ── */
    .admin-body { max-width: 1200px; margin: 0 auto; padding: 48px; }

    .flash { padding: 14px 20px; border-radius: 4px; margin-bottom: 32px; font-size: 14px; }
    .flash.success { background: rgba(29,63,166,0.08); color: var(--cobalt); border: 1px solid var(--cobalt); }
    .flash.error   { background: rgba(232,73,42,0.08); color: var(--coral); border: 1px solid var(--coral); }

    .section-title {
      font-family: 'Playfair Display', serif; font-size: 1.5rem;
      font-weight: 700; margin-bottom: 24px;
    }
    .section-title em { font-style: italic; color: var(--coral); }
    .label {
      font-family: 'DM Mono', monospace; font-size: 11px; letter-spacing: 0.12em;
      text-transform: uppercase; color: var(--stone); margin-bottom: 8px; display: block;
    }

    /* ── Hero Grid Preview ── */
    .hero-grid-admin {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      grid-template-rows: repeat(3, 1fr);
      gap: 16px;
      height: 600px;
      margin-bottom: 48px;
    }
    .hero-slot {
      background: var(--white);
      border: 2px dashed rgba(26,22,18,0.12);
      border-radius: 12px;
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: border-color 0.2s;
    }
    .hero-slot:hover { border-color: var(--coral); }
    .hero-slot:nth-child(1) { grid-row: span 2; }
    .hero-slot:nth-child(3) { grid-row: span 2; }
    .hero-slot:nth-child(5) { grid-column: span 2; }

    .slot-preview {
      flex: 1;
      position: relative;
      overflow: hidden;
      background: var(--cream);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .slot-preview img, .slot-preview video {
      width: 100%; height: 100%; object-fit: cover; display: block;
    }
    .slot-empty-icon {
      font-size: 2rem;
      color: var(--stone);
      opacity: 0.4;
    }
    .slot-number {
      position: absolute; top: 10px; left: 10px;
      font-family: 'DM Mono', monospace; font-size: 10px; letter-spacing: 0.1em;
      text-transform: uppercase; background: rgba(26,22,18,0.7); color: var(--white);
      padding: 4px 10px; border-radius: 3px; z-index: 2;
    }
    .slot-badge {
      position: absolute; top: 10px; right: 10px;
      font-family: 'DM Mono', monospace; font-size: 9px; letter-spacing: 0.1em;
      text-transform: uppercase; background: var(--coral); color: var(--white);
      padding: 4px 8px; border-radius: 3px; z-index: 2;
    }

    .slot-actions {
      padding: 14px;
      background: var(--white);
      border-top: 1px solid rgba(26,22,18,0.06);
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .slot-actions summary {
      font-family: 'DM Mono', monospace; font-size: 10px; letter-spacing: 0.1em;
      text-transform: uppercase; color: var(--stone); cursor: pointer;
      padding: 6px 0; user-select: none;
    }
    .slot-actions summary:hover { color: var(--coral); }
    .slot-actions details[open] summary { color: var(--coral); margin-bottom: 10px; }

    .slot-upload-zone {
      border: 2px dashed rgba(26,22,18,0.12); border-radius: 6px;
      padding: 16px 12px; text-align: center; position: relative;
      cursor: pointer; transition: border-color 0.2s, background 0.2s;
      margin-bottom: 8px;
    }
    .slot-upload-zone:hover, .slot-upload-zone.drag-over {
      border-color: var(--coral); background: rgba(232,73,42,0.04);
    }
    .slot-upload-zone input[type="file"] {
      position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
    }
    .slot-upload-zone .upload-label {
      font-size: 12px; color: var(--stone);
    }
    .slot-upload-zone .upload-label strong { color: var(--coral); }
    .slot-upload-filename {
      font-family: 'DM Mono', monospace; font-size: 11px; color: var(--ink);
      margin-top: 6px; font-weight: 500; min-height: 16px;
    }

    .btn-slot {
      width: 100%; padding: 9px; border: none; border-radius: 4px;
      font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 500;
      cursor: pointer; transition: all 0.2s;
    }
    .btn-slot-upload { background: var(--coral); color: var(--white); }
    .btn-slot-upload:hover { background: var(--ink); }
    .btn-slot-pick { background: var(--cobalt); color: var(--white); }
    .btn-slot-pick:hover { background: var(--ink); }
    .btn-slot-remove {
      background: transparent; color: var(--stone);
      border: 1px solid rgba(26,22,18,0.1);
    }
    .btn-slot-remove:hover { background: rgba(232,73,42,0.08); border-color: var(--coral); color: var(--coral); }

    .pick-select {
      width: 100%; padding: 8px 10px;
      border: 1.5px solid rgba(26,22,18,0.15); border-radius: 4px;
      font-family: inherit; font-size: 12px; outline: none;
      transition: border-color 0.2s; margin-bottom: 8px;
      background: var(--cream);
    }
    .pick-select:focus { border-color: var(--coral); }

    @media (max-width: 900px) {
      .admin-body { padding: 24px; }
      .admin-header { padding: 16px 24px; }
      .hero-grid-admin {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto;
        height: auto;
      }
      .hero-slot { min-height: 260px; }
      .hero-slot:nth-child(1),
      .hero-slot:nth-child(3) { grid-row: auto; }
      .hero-slot:nth-child(5) { grid-column: auto; }
    }

    @media (max-width: 540px) {
      .hero-grid-admin { grid-template-columns: 1fr; }
    }
  </style>
</head>

<body>

  <?php if (!$logged_in): ?>
    <div class="login-wrap">
      <div class="login-box">
        <div class="login-logo">MI<span>A</span></div>
        <div class="login-subtitle">Hero-Grid</div>
        <?php if (!empty($error)): ?>
          <p class="error-msg"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <form method="POST">
          <input type="password" name="password" placeholder="Mot de passe" autofocus>
          <button type="submit" class="btn-primary">Accéder</button>
        </form>
      </div>
    </div>

  <?php else: ?>
    <header class="admin-header">
      <div class="admin-header-logo">MI<span>A</span></div>
      <div class="admin-header-meta">
        <span class="admin-title-bar">Hero-Grid</span>
        <a href="admin.php" class="btn-nav">← Médias & Tags</a>
        <a href="admin-hero.php?logout=1" class="btn-logout">Déconnexion</a>
      </div>
    </header>

    <div class="admin-body">
      <?php if ($message): ?>
        <div class="flash <?= htmlspecialchars($message['type'], ENT_QUOTES, 'UTF-8') ?>">
          <?= htmlspecialchars($message['text'], ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <span class="label">Homepage</span>
      <h2 class="section-title" style="margin-bottom:32px">Configurer le <em>hero-grid</em></h2>

      <div class="hero-grid-admin">
        <?php for ($slot = 1; $slot <= 6; $slot++):
          $media    = $slots[$slot] ?? null;
          $is_video = false;
          $path     = '';

          if ($media) {
            $ext      = strtolower(pathinfo($media['filename'], PATHINFO_EXTENSION));
            $is_video = in_array($ext, $video_ext);
            $path     = 'assets/' . ($is_video ? 'videos' : 'images') . '/' . $media['filename'];
          }
        ?>
          <div class="hero-slot">
            <div class="slot-preview">
              <span class="slot-number">Slot <?= $slot ?></span>

              <?php if ($media && $is_video): ?>
                <span class="slot-badge">Vidéo</span>
                <video autoplay muted loop playsinline preload="metadata">
                  <source src="<?= htmlspecialchars($path) ?>?v=<?= $media['id'] ?>" type="video/<?= $ext ?>">
                </video>
              <?php elseif ($media): ?>
                <span class="slot-badge">Image</span>
                <img src="<?= htmlspecialchars($path) ?>?v=<?= $media['id'] ?>"
                     alt="slot <?= $slot ?>">
              <?php else: ?>
                <span class="slot-empty-icon">＋</span>
              <?php endif; ?>
            </div>

            <div class="slot-actions">
              <!-- Upload -->
              <details>
                <summary>↑ Uploader un fichier</summary>
                <form method="POST" enctype="multipart/form-data">
                  <input type="hidden" name="slot_position" value="<?= $slot ?>">
                  <div class="slot-upload-zone" data-slot="<?= $slot ?>">
                    <input type="file" name="hero_file"
                           accept="image/*,video/*" required
                           data-filename-target="fn-upload-<?= $slot ?>">
                    <div class="upload-label">Glisser ou <strong>parcourir</strong></div>
                    <div class="slot-upload-filename" id="fn-upload-<?= $slot ?>"></div>
                  </div>
                  <button type="submit" name="upload_hero_media" class="btn-slot btn-slot-upload">Uploader</button>
                </form>
              </details>

              <!-- Pick existing -->
              <details>
                <summary>⊞ Choisir un média existant</summary>
                <form method="POST">
                  <input type="hidden" name="slot_position" value="<?= $slot ?>">
                  <select name="picked_filename" class="pick-select" required>
                    <option value="">— Sélectionner —</option>
                    <?php foreach ($project_medias as $pm): ?>
                      <option value="<?= htmlspecialchars($pm['filename'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($pm['brand'], ENT_QUOTES, 'UTF-8') ?>
                        (<?= htmlspecialchars($pm['type'], ENT_QUOTES, 'UTF-8') ?>)
                        — <?= htmlspecialchars($pm['filename'], ENT_QUOTES, 'UTF-8') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" name="pick_hero_media" class="btn-slot btn-slot-pick">Appliquer</button>
                </form>
              </details>

              <!-- Remove -->
              <?php if ($media): ?>
                <form method="POST" onsubmit="return confirm('Vider le slot <?= $slot ?> ?')">
                  <input type="hidden" name="slot_position" value="<?= $slot ?>">
                  <button type="submit" name="remove_hero_media" class="btn-slot btn-slot-remove">✕ Vider ce slot</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endfor; ?>
      </div>
    </div>

    <script>
      document.querySelectorAll('.slot-upload-zone').forEach(zone => {
        const input = zone.querySelector('input[type="file"]');
        const target = document.getElementById(input.dataset.filenameTarget);

        input.addEventListener('change', () => {
          target.textContent = input.files[0] ? '✓ ' + input.files[0].name : '';
        });

        zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
        zone.addEventListener('drop', e => {
          e.preventDefault();
          zone.classList.remove('drag-over');
          if (e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            target.textContent = '✓ ' + e.dataTransfer.files[0].name;
          }
        });
      });
    </script>
  <?php endif; ?>

</body>

</html>
