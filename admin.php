<?php
require_once 'includes/db.php';

/* =====================================================
   ADMIN — Gestion des tags par média
   Accessible via : /admin.php
===================================================== */

// ─── MOT DE PASSE (à changer ici) ───
define('ADMIN_PASSWORD', 'mia2026');
session_start();

// ─── DÉCONNEXION ───
if (isset($_GET['logout'])) {
  session_destroy();
  header('Location: admin.php');
  exit;
}

// ─── VÉRIFICATION MOT DE PASSE ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
  if ($_POST['password'] === ADMIN_PASSWORD) {
    $_SESSION['admin'] = true;
    header('Location: admin.php');
    exit;
  } else {
    $error = 'Mot de passe incorrect.';
  }
}

$logged_in = !empty($_SESSION['admin']);
$message = null;

// ─── ACTIONS (uniquement si connecté) ───
if ($logged_in && $_SERVER['REQUEST_METHOD'] === 'POST') {
  // Réordonner les projets par drag & drop
  if (isset($_POST['save_order'])) {
    $orderedIdsRaw = $_POST['ordered_ids'] ?? '[]';
    $orderedIds = json_decode($orderedIdsRaw, true);

    if (is_array($orderedIds) && !empty($orderedIds)) {
      $stmt = $pdo->prepare('UPDATE projects SET position = ? WHERE id = ?');

      foreach ($orderedIds as $index => $projectId) {
        $stmt->execute([$index + 1, (int) $projectId]);
      }

      $message = ['type' => 'success', 'text' => 'Ordre des médias mis à jour.'];
    } else {
      $message = ['type' => 'error', 'text' => 'Impossible de sauvegarder l’ordre des médias.'];
    }
  }

  // Sauvegarder les tags d'un projet
  if (isset($_POST['save_tags'])) {
    $projectId = (int) ($_POST['project_id'] ?? 0);
    $selectedTagIds = $_POST['tag_ids'] ?? [];

    $stmt = $pdo->prepare('DELETE FROM project_tags WHERE project_id = ?');
    $stmt->execute([$projectId]);

    if (!empty($selectedTagIds)) {
      $stmt = $pdo->prepare('INSERT INTO project_tags (project_id, tag_id) VALUES (?, ?)');

      foreach ($selectedTagIds as $tagId) {
        $stmt->execute([$projectId, (int) $tagId]);
      }
    }

    $message = ['type' => 'success', 'text' => 'Tags mis à jour.'];
  }

  // Mettre à jour le statut featured d'un projet
  if (isset($_POST['save_featured'])) {
    $projectId = (int) ($_POST['project_id'] ?? 0);
    $featured = isset($_POST['featured']) ? 1 : 0;

    $stmt = $pdo->prepare('UPDATE projects SET featured = ? WHERE id = ?');
    $stmt->execute([$featured, $projectId]);

    $message = ['type' => 'success', 'text' => 'Statut vitrine mis à jour.'];
  }

  // Ajouter un nouveau tag
  if (isset($_POST['add_tag'])) {
    $label = trim($_POST['tag_label'] ?? '');

    if ($label !== '') {
      try {
        $stmt = $pdo->prepare('INSERT INTO tags (label) VALUES (?)');
        $stmt->execute([$label]);
        $message = ['type' => 'success', 'text' => "Tag « {$label} » créé."];
      } catch (PDOException $e) {
        $message = ['type' => 'error', 'text' => 'Ce tag existe déjà ou ne peut pas être créé.'];
      }
    }
  }

  // Supprimer un tag
  if (isset($_POST['delete_tag'])) {
    $tagId = (int) ($_POST['tag_id'] ?? 0);

    $pdo->prepare('DELETE FROM tags WHERE id = ?')->execute([$tagId]);

    $message = ['type' => 'success', 'text' => 'Tag supprimé.'];
  }

  // Renommer un tag
  if (isset($_POST['rename_tag'])) {
    $tagId = (int) ($_POST['tag_id'] ?? 0);
    $newLabel = trim($_POST['new_label'] ?? '');

    if ($newLabel !== '') {
      try {
        $pdo->prepare('UPDATE tags SET label = ? WHERE id = ?')->execute([$newLabel, $tagId]);
        $message = ['type' => 'success', 'text' => 'Tag renommé.'];
      } catch (PDOException $e) {
        $message = ['type' => 'error', 'text' => 'Impossible de renommer ce tag.'];
      }
    }
  }

  // ─── UPLOAD MÉDIA ───
  if (isset($_POST['upload_media']) && isset($_FILES['media_file'])) {
    $file = $_FILES['media_file'];

    $brand = trim($_POST['upload_brand'] ?? '');
    $category = trim($_POST['upload_category'] ?? '');
    $height = trim($_POST['upload_height'] ?? '280px');
    $featured = isset($_POST['upload_featured']) ? 1 : 0;

    $allowedImages = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
    $allowedVideos = ['mp4', 'webm', 'mov', 'ogg'];
    $maxSize = 100 * 1024 * 1024; // 100 Mo

    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    $isImage = in_array($extension, $allowedImages, true);
    $isVideo = in_array($extension, $allowedVideos, true);

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      $message = ['type' => 'error', 'text' => "Erreur lors de l'upload (code " . ($file['error'] ?? 'inconnu') . ")."];
    } elseif (!$isImage && !$isVideo) {
      $message = ['type' => 'error', 'text' => 'Format non autorisé. Acceptés : jpg, jpeg, png, gif, webp, avif, mp4, webm, mov, ogg.'];
    } elseif (($file['size'] ?? 0) > $maxSize) {
      $message = ['type' => 'error', 'text' => 'Fichier trop lourd (max 100 Mo).'];
    } elseif ($brand === '') {
      $message = ['type' => 'error', 'text' => 'Le nom du média est requis.'];
    } else {
      $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($brand));
      $slug = trim($slug ?? '', '-');

      if ($slug === '') {
        $slug = 'media';
      }

      $filename = $slug . '-' . time() . '.' . $extension;
      $type = $isVideo ? 'video' : 'image';
      $destDir = __DIR__ . '/assets/' . ($isVideo ? 'videos' : 'images') . '/';

      if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
        $message = ['type' => 'error', 'text' => 'Impossible de créer le dossier de destination.'];
      } else {
        if (move_uploaded_file($file['tmp_name'], $destDir . $filename)) {
          $position = (int) $pdo->query('SELECT COALESCE(MAX(position), 0) + 1 FROM projects')->fetchColumn();

          $stmt = $pdo->prepare(
            'INSERT INTO projects (brand, category, color, height, featured, type, filename, position)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
          );

          $stmt->execute([
            $brand,
            $category,
            '#8C847A',
            $height,
            $featured,
            $type,
            $filename,
            $position
          ]);

          $newProjectId = (int) $pdo->lastInsertId();

          $uploadTagIds = $_POST['upload_tag_ids'] ?? [];
          if (!empty($uploadTagIds)) {
            $stmt = $pdo->prepare('INSERT INTO project_tags (project_id, tag_id) VALUES (?, ?)');

            foreach ($uploadTagIds as $tagId) {
              $stmt->execute([$newProjectId, (int) $tagId]);
            }
          }

          $message = ['type' => 'success', 'text' => "« {$brand} » uploadé et ajouté au portfolio."];
        } else {
          $message = ['type' => 'error', 'text' => 'Impossible de déplacer le fichier. Vérifiez les permissions de assets/.'];
        }
      }
    }
  }

  // ─── SUPPRIMER UN PROJET ───
  if (isset($_POST['delete_project'])) {
    $projectId = (int) ($_POST['project_id'] ?? 0);

    $stmt = $pdo->prepare('SELECT type, filename FROM projects WHERE id = ?');
    $stmt->execute([$projectId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($project && !empty($project['filename'])) {
      $dir = ($project['type'] ?? '') === 'video' ? 'videos' : 'images';
      $path = __DIR__ . '/assets/' . $dir . '/' . $project['filename'];

      if (file_exists($path)) {
        unlink($path);
      }
    }

    $pdo->prepare('DELETE FROM projects WHERE id = ?')->execute([$projectId]);

    $message = ['type' => 'success', 'text' => 'Média supprimé.'];
  }
}

// ─── DONNÉES (si connecté) ───
$allTags = [];
$projectsWithTags = [];

if ($logged_in) {
  $allTags = $pdo->query('SELECT * FROM tags ORDER BY label ASC')->fetchAll(PDO::FETCH_ASSOC);

  $projectsRows = $pdo->query(
    'SELECT id, brand, category, type, filename, color, height, featured, position
         FROM projects
         ORDER BY position ASC, id ASC'
  )->fetchAll(PDO::FETCH_ASSOC);

  $tagStmt = $pdo->prepare('SELECT tag_id FROM project_tags WHERE project_id = ?');

  foreach ($projectsRows as $project) {
    $tagStmt->execute([$project['id']]);
    $project['current_tag_ids'] = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
    $projectsWithTags[] = $project;
  }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin — Tags &amp; Upload · MIA</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

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

    .login-wrap {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .login-box {
      background: var(--white);
      border: 1px solid rgba(26, 22, 18, 0.1);
      border-radius: 16px;
      padding: 56px 48px;
      width: 100%;
      max-width: 400px;
      text-align: center;
    }

    .login-logo {
      font-family: 'Playfair Display', serif;
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .login-logo span {
      color: var(--coral);
    }

    .login-subtitle {
      font-family: 'DM Mono', monospace;
      font-size: 11px;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--stone);
      margin-bottom: 40px;
    }

    .login-box input[type="password"] {
      width: 100%;
      padding: 14px 18px;
      border: 1.5px solid rgba(26, 22, 18, 0.15);
      border-radius: 4px;
      font-family: inherit;
      font-size: 15px;
      outline: none;
      transition: border-color 0.2s;
      margin-bottom: 12px;
      background: var(--cream);
    }

    .login-box input[type="password"]:focus {
      border-color: var(--coral);
    }

    .btn-primary {
      width: 100%;
      padding: 14px;
      background: var(--coral);
      color: var(--white);
      border: none;
      border-radius: 4px;
      font-family: 'DM Sans', sans-serif;
      font-size: 15px;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.2s;
    }

    .btn-primary:hover {
      background: var(--ink);
    }

    .error-msg {
      color: var(--coral);
      font-size: 13px;
      margin-bottom: 16px;
    }

    .admin-header {
      background: var(--ink);
      padding: 20px 48px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
    }

    .admin-header-logo {
      font-family: 'Playfair Display', serif;
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--white);
    }

    .admin-header-logo span {
      color: var(--coral);
    }

    .admin-header-meta {
      display: flex;
      align-items: center;
      gap: 24px;
    }

    .admin-title-bar {
      font-family: 'DM Mono', monospace;
      font-size: 11px;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--stone);
    }

    .btn-logout {
      font-family: 'DM Mono', monospace;
      font-size: 11px;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      padding: 8px 16px;
      border-radius: 4px;
      border: 1px solid rgba(253, 252, 250, 0.2);
      background: transparent;
      color: var(--white);
      cursor: pointer;
      transition: background 0.2s;
      text-decoration: none;
    }

    .btn-logout:hover {
      background: rgba(253, 252, 250, 0.1);
    }

    .admin-body {
      max-width: 1200px;
      margin: 0 auto;
      padding: 48px;
    }

    .flash {
      padding: 14px 20px;
      border-radius: 4px;
      margin-bottom: 32px;
      font-size: 14px;
    }

    .flash.success {
      background: rgba(29, 63, 166, 0.08);
      color: var(--cobalt);
      border: 1px solid var(--cobalt);
    }

    .flash.error {
      background: rgba(232, 73, 42, 0.08);
      color: var(--coral);
      border: 1px solid var(--coral);
    }

    .section-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 24px;
    }

    .section-title em {
      font-style: italic;
      color: var(--coral);
    }

    .label {
      font-family: 'DM Mono', monospace;
      font-size: 11px;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--stone);
      margin-bottom: 8px;
      display: block;
    }

    hr {
      border: none;
      border-top: 1px solid rgba(26, 22, 18, 0.08);
      margin: 48px 0;
    }

    .tags-manager,
    .upload-box {
      background: var(--white);
      border: 1px solid rgba(26, 22, 18, 0.08);
      border-radius: 16px;
      padding: 32px;
      margin-bottom: 48px;
    }

    .tags-list {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 24px;
    }

    .tag-item {
      display: flex;
      align-items: center;
      gap: 8px;
      background: var(--cream);
      border-radius: 4px;
      padding: 6px 12px;
      border: 1px solid rgba(26, 22, 18, 0.1);
    }

    .tag-label-input {
      font-family: 'DM Mono', monospace;
      font-size: 11px;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      border: none;
      background: transparent;
      color: var(--ink);
      width: 120px;
      outline: none;
      padding: 2px 4px;
      border-radius: 2px;
    }

    .tag-label-input:focus {
      background: rgba(26, 22, 18, 0.05);
    }

    .btn-sm {
      font-size: 11px;
      font-family: 'DM Mono', monospace;
      letter-spacing: 0.08em;
      padding: 4px 10px;
      border-radius: 3px;
      border: none;
      cursor: pointer;
      transition: background 0.2s;
    }

    .btn-rename {
      background: var(--cobalt);
      color: var(--white);
    }

    .btn-rename:hover {
      background: var(--ink);
    }

    .btn-delete {
      background: rgba(232, 73, 42, 0.12);
      color: var(--coral);
    }

    .btn-delete:hover {
      background: var(--coral);
      color: var(--white);
    }

    .add-tag-form {
      display: flex;
      gap: 10px;
      margin-top: 16px;
    }

    .add-tag-form input[type="text"] {
      flex: 1;
      padding: 10px 16px;
      border: 1.5px solid rgba(26, 22, 18, 0.15);
      border-radius: 4px;
      font-family: inherit;
      font-size: 14px;
      outline: none;
      transition: border-color 0.2s;
    }

    .add-tag-form input:focus {
      border-color: var(--coral);
    }

    .btn-add,
    .btn-upload {
      padding: 10px 20px;
      background: var(--coral);
      color: var(--white);
      border: none;
      border-radius: 4px;
      font-family: 'DM Sans', sans-serif;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      white-space: nowrap;
      transition: background 0.2s;
    }

    .btn-add:hover,
    .btn-upload:hover {
      background: var(--ink);
    }

    .btn-upload {
      width: 100%;
      padding: 14px;
      font-size: 15px;
    }

    .projects-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
    }

    .project-card {
      background: var(--white);
      border: 1px solid rgba(26, 22, 18, 0.08);
      border-radius: 16px;
      overflow: hidden;
      cursor: grab;
      transition: transform 0.18s ease, opacity 0.18s ease, box-shadow 0.18s ease;
    }

    .project-card.dragging {
      opacity: 0.45;
      transform: scale(0.98);
      cursor: grabbing;
    }

    .project-card.drag-over {
      outline: 2px dashed var(--coral);
      outline-offset: -6px;
      box-shadow: 0 10px 30px rgba(232, 73, 42, 0.08);
    }

    .project-thumb {
      height: 160px;
      position: relative;
      overflow: hidden;
      background: var(--cream);
    }

    .project-thumb video,
    .project-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .project-thumb-color {
      width: 100%;
      height: 100%;
    }

    .project-thumb-badge {
      position: absolute;
      top: 10px;
      right: 10px;
      font-family: 'DM Mono', monospace;
      font-size: 9px;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      background: rgba(26, 22, 18, 0.7);
      color: var(--white);
      padding: 4px 8px;
      border-radius: 3px;
    }

    .project-body {
      padding: 20px 24px;
    }

    .project-sort-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 16px;
    }

    .project-sort-handle {
      font-family: 'DM Mono', monospace;
      font-size: 10px;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--stone);
      padding: 6px 10px;
      border: 1px dashed rgba(26, 22, 18, 0.15);
      border-radius: 4px;
      background: var(--cream);
      user-select: none;
    }

    .project-position-badge {
      font-family: 'DM Mono', monospace;
      font-size: 10px;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--stone);
    }

    .project-brand {
      font-family: 'Playfair Display', serif;
      font-size: 1.1rem;
      font-weight: 700;
      margin-bottom: 4px;
    }

    .project-category {
      font-family: 'DM Mono', monospace;
      font-size: 10px;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--stone);
      margin-bottom: 20px;
    }

    .tags-checkboxes {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 20px;
    }

    .tag-checkbox {
      display: none;
    }

    .tag-checkbox-label {
      font-family: 'DM Mono', monospace;
      font-size: 10px;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      padding: 6px 12px;
      border-radius: 3px;
      border: 1.5px solid rgba(26, 22, 18, 0.15);
      color: var(--stone);
      cursor: pointer;
      transition: all 0.15s;
      user-select: none;
    }

    .tag-checkbox:checked+.tag-checkbox-label {
      background: var(--coral);
      border-color: var(--coral);
      color: var(--white);
    }

    .btn-save {
      width: 100%;
      padding: 10px;
      background: var(--ink);
      color: var(--white);
      border: none;
      border-radius: 4px;
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.2s;
    }

    .btn-save:hover {
      background: var(--coral);
    }

    .upload-drop {
      border: 2px dashed rgba(26, 22, 18, 0.15);
      border-radius: 8px;
      padding: 40px 24px;
      text-align: center;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s;
      margin-bottom: 24px;
      position: relative;
    }

    .upload-drop:hover,
    .upload-drop.drag-over {
      border-color: var(--coral);
      background: rgba(232, 73, 42, 0.04);
    }

    .upload-drop input[type="file"] {
      position: absolute;
      inset: 0;
      opacity: 0;
      cursor: pointer;
      width: 100%;
      height: 100%;
    }

    .upload-drop-icon {
      font-size: 2rem;
      margin-bottom: 10px;
    }

    .upload-drop-text {
      font-size: 15px;
      color: var(--stone);
    }

    .upload-drop-text strong {
      color: var(--coral);
    }

    .upload-drop-filename {
      margin-top: 10px;
      font-family: 'DM Mono', monospace;
      font-size: 12px;
      color: var(--ink);
      font-weight: 500;
    }

    .upload-fields {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      margin-bottom: 20px;
    }

    .upload-field {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .upload-field label {
      font-family: 'DM Mono', monospace;
      font-size: 11px;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--stone);
    }

    .upload-field input[type="text"],
    .upload-field select {
      padding: 10px 14px;
      border: 1.5px solid rgba(26, 22, 18, 0.15);
      border-radius: 4px;
      font-family: inherit;
      font-size: 14px;
      outline: none;
      transition: border-color 0.2s;
    }

    .upload-field input:focus,
    .upload-field select:focus {
      border-color: var(--coral);
    }

    .upload-tags-label {
      grid-column: span 2;
    }

    .upload-featured {
      display: flex;
      align-items: center;
      gap: 10px;
      grid-column: span 2;
      font-size: 14px;
      color: var(--stone);
    }

    .upload-featured input {
      width: 18px;
      height: 18px;
      accent-color: var(--coral);
    }

    .upload-progress {
      display: none;
      margin-top: 16px;
    }

    .upload-progress-bar {
      height: 4px;
      background: rgba(26, 22, 18, 0.1);
      border-radius: 2px;
      overflow: hidden;
    }

    .upload-progress-fill {
      height: 100%;
      width: 0%;
      background: var(--coral);
      transition: width 0.3s var(--ease);
    }

    .upload-progress-text {
      font-family: 'DM Mono', monospace;
      font-size: 11px;
      color: var(--stone);
      text-align: center;
      margin-top: 8px;
    }

    .btn-delete-project {
      width: 100%;
      padding: 8px;
      margin-top: 8px;
      background: transparent;
      color: var(--stone);
      border: 1px solid rgba(26, 22, 18, 0.1);
      border-radius: 4px;
      font-family: 'DM Sans', sans-serif;
      font-size: 12px;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-delete-project:hover {
      background: rgba(232, 73, 42, 0.08);
      border-color: var(--coral);
      color: var(--coral);
    }

    @media (max-width: 640px) {
      .admin-body {
        padding: 24px;
      }

      .admin-header {
        padding: 16px 24px;
      }

      .projects-grid {
        grid-template-columns: 1fr;
      }

      .upload-fields {
        grid-template-columns: 1fr;
      }

      .upload-tags-label,
      .upload-featured {
        grid-column: span 1;
      }

      .add-tag-form {
        flex-direction: column;
      }
    }

    .featured-form {
    margin-bottom: 18px;
    padding: 12px 14px;
    background: rgba(26,22,18,0.03);
    border: 1px solid rgba(26,22,18,0.08);
    border-radius: 8px;
}

.featured-toggle {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    font-size: 13px;
    color: var(--ink);
    cursor: pointer;
}

.featured-toggle input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--coral);
}

.btn-featured-save {
    width: 100%;
    padding: 9px;
    background: var(--cream);
    color: var(--ink);
    border: 1px solid rgba(26,22,18,0.12);
    border-radius: 4px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-featured-save:hover {
    background: var(--coral);
    color: var(--white);
    border-color: var(--coral);
}
  </style>
</head>

<body>

  <?php if (!$logged_in): ?>
    <div class="login-wrap">
      <div class="login-box">
        <div class="login-logo">MI<span>A</span></div>
        <div class="login-subtitle">Administration</div>

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
        <span class="admin-title-bar">Gestion des tags & médias</span>
        <a href="admin-hero.php" class="btn-logout">Hero-Grid →</a>
        <a href="admin.php?logout=1" class="btn-logout">Déconnexion</a>
      </div>
    </header>

    <div class="admin-body">
      <?php if ($message): ?>
        <div class="flash <?= htmlspecialchars($message['type'], ENT_QUOTES, 'UTF-8') ?>">
          <?= htmlspecialchars($message['text'], ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <div class="tags-manager">
        <span class="label">Configuration</span>
        <h2 class="section-title">Gérer les <em>tags</em></h2>

        <div class="tags-list">
          <?php foreach ($allTags as $tag): ?>
            <div class="tag-item">
              <form method="POST" style="display:flex;align-items:center;gap:6px">
                <input type="hidden" name="tag_id" value="<?= (int) $tag['id'] ?>">
                <input
                  type="text"
                  name="new_label"
                  class="tag-label-input"
                  value="<?= htmlspecialchars($tag['label'], ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" name="rename_tag" class="btn-sm btn-rename">✓</button>
              </form>

              <form method="POST" onsubmit="return confirm('Supprimer ce tag ?')">
                <input type="hidden" name="tag_id" value="<?= (int) $tag['id'] ?>">
                <button type="submit" name="delete_tag" class="btn-sm btn-delete">✕</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>

        <form method="POST" class="add-tag-form">
          <input type="text" name="tag_label" placeholder="Nouveau tag…" required>
          <button type="submit" name="add_tag" class="btn-add">+ Ajouter</button>
        </form>
      </div>

      <hr>

      <span class="label">Nouveau média</span>
      <h2 class="section-title" style="margin-bottom:32px">Uploader un <em>fichier</em></h2>

      <div class="upload-box">
        <form method="POST" enctype="multipart/form-data" id="upload-form">
          <div class="upload-drop" id="upload-drop">
            <input type="file" name="media_file" id="media_file" accept="image/*,video/*" required>
            <div class="upload-drop-icon">📁</div>
            <div class="upload-drop-text">
              Glissez un fichier ici ou <strong>cliquez pour parcourir</strong><br>
              <small>Images : jpg, png, webp, avif · Vidéos : mp4, webm, mov, ogg · Max 100 Mo</small>
            </div>
            <div class="upload-drop-filename" id="upload-filename"></div>
          </div>

          <div class="upload-fields">
            <div class="upload-field">
              <label for="upload_brand">Nom du média *</label>
              <input
                type="text"
                name="upload_brand"
                id="upload_brand"
                placeholder="Ex: ÉLISE"
                required>
            </div>

            <div class="upload-field">
              <label for="upload_category">Catégorie</label>
              <input
                type="text"
                name="upload_category"
                id="upload_category"
                placeholder="Ex: Lifestyle IA">
            </div>

            <div class="upload-field">
              <label for="upload_height">Hauteur (grille work)</label>
              <input
                type="text"
                name="upload_height"
                id="upload_height"
                value="280px"
                placeholder="280px">
            </div>

            <div class="upload-field">
              <label>Position</label>
              <input type="text" value="Ajout automatique en dernière position" disabled>
            </div>

            <div class="upload-field upload-tags-label">
              <label>Tags</label>
              <div class="tags-checkboxes" style="margin-top:8px">
                <?php foreach ($allTags as $tag): ?>
                  <input
                    type="checkbox"
                    class="tag-checkbox"
                    id="utag_<?= (int) $tag['id'] ?>"
                    name="upload_tag_ids[]"
                    value="<?= (int) $tag['id'] ?>">
                  <label class="tag-checkbox-label" for="utag_<?= (int) $tag['id'] ?>">
                    <?= htmlspecialchars($tag['label'], ENT_QUOTES, 'UTF-8') ?>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="upload-featured">
              <input type="checkbox" name="upload_featured" id="upload_featured" value="1">
              <label for="upload_featured">Afficher dans la vitrine (homepage showcase)</label>
            </div>
          </div>

          <div class="upload-progress" id="upload-progress">
            <div class="upload-progress-bar">
              <div class="upload-progress-fill" id="upload-fill"></div>
            </div>
            <div class="upload-progress-text" id="upload-progress-text">Envoi en cours…</div>
          </div>

          <button type="submit" name="upload_media" class="btn-upload">↑ Uploader le fichier</button>
        </form>
      </div>

      <hr>

      <span class="label">Médias existants</span>
      <h2 class="section-title" style="margin-bottom:32px">Organiser les <em>médias</em></h2>

      <form method="POST" id="sort-order-form" style="display:none;">
        <input type="hidden" name="save_order" value="1">
        <input type="hidden" name="ordered_ids" id="ordered_ids_input">
      </form>

      <div class="projects-grid" id="projects-grid">
        <?php foreach ($projectsWithTags as $project): ?>
          <div class="project-card" data-project-id="<?= (int) $project['id'] ?>" draggable="true">
            <div class="project-thumb">
              <?php
              $type = $project['type'] ?? 'color';
              $file = $project['filename'] ?? '';
              ?>

              <?php if ($type === 'video' && $file !== ''): ?>
                <video autoplay muted loop playsinline preload="metadata">
                  <source src="assets/videos/<?= htmlspecialchars($file, ENT_QUOTES, 'UTF-8') ?>" type="video/mp4">
                </video>
              <?php elseif ($type === 'image' && $file !== ''): ?>
                <img
                  src="assets/images/<?= htmlspecialchars($file, ENT_QUOTES, 'UTF-8') ?>"
                  alt="<?= htmlspecialchars($project['brand'], ENT_QUOTES, 'UTF-8') ?>">
              <?php else: ?>
                <div
                  class="project-thumb-color"
                  style="background:<?= htmlspecialchars($project['color'] ?? '#8C847A', ENT_QUOTES, 'UTF-8') ?>"></div>
              <?php endif; ?>

              <span class="project-thumb-badge"><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <div class="project-body">
              <div class="project-sort-bar">
                <span class="project-sort-handle">↕ Déplacer</span>
                <span class="project-position-badge">Position : <?= (int) $project['position'] ?></span>
              </div>

              <div class="project-brand"><?= htmlspecialchars($project['brand'], ENT_QUOTES, 'UTF-8') ?></div>
              <div class="project-category"><?= htmlspecialchars($project['category'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              <form method="POST" class="featured-form">
                <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">

                <label class="featured-toggle">
                  <input
                    type="checkbox"
                    name="featured"
                    value="1"
                    <?= !empty($project['featured']) ? 'checked' : '' ?>>
                  <span>Afficher dans la vitrine</span>
                </label>

                <button type="submit" name="save_featured" class="btn-featured-save">
                  Enregistrer la vitrine
                </button>
              </form>
              <form method="POST">
                <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">

                <div class="tags-checkboxes">
                  <?php foreach ($allTags as $tag): ?>
                    <?php $checked = in_array($tag['id'], $project['current_tag_ids'], false); ?>
                    <input
                      type="checkbox"
                      class="tag-checkbox"
                      id="tag_<?= (int) $project['id'] ?>_<?= (int) $tag['id'] ?>"
                      name="tag_ids[]"
                      value="<?= (int) $tag['id'] ?>"
                      <?= $checked ? 'checked' : '' ?>>
                    <label
                      class="tag-checkbox-label"
                      for="tag_<?= (int) $project['id'] ?>_<?= (int) $tag['id'] ?>">
                      <?= htmlspecialchars($tag['label'], ENT_QUOTES, 'UTF-8') ?>
                    </label>
                  <?php endforeach; ?>
                </div>

                <button type="submit" name="save_tags" class="btn-save">Enregistrer les tags</button>
              </form>

              <form
                method="POST"
                onsubmit="return confirm('Supprimer « <?= htmlspecialchars($project['brand'], ENT_QUOTES, 'UTF-8') ?> » et son fichier ?')">
                <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
                <button type="submit" name="delete_project" class="btn-delete-project">
                  ✕ Supprimer ce média
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <script>
      const drop = document.getElementById('upload-drop');
      const input = document.getElementById('media_file');
      const filenameLabel = document.getElementById('upload-filename');

      input.addEventListener('change', () => {
        filenameLabel.textContent = input.files[0] ? '✓ ' + input.files[0].name : '';
      });

      drop.addEventListener('dragover', event => {
        event.preventDefault();
        drop.classList.add('drag-over');
      });

      drop.addEventListener('dragleave', () => {
        drop.classList.remove('drag-over');
      });

      drop.addEventListener('drop', event => {
        event.preventDefault();
        drop.classList.remove('drag-over');

        if (event.dataTransfer.files.length) {
          input.files = event.dataTransfer.files;
          filenameLabel.textContent = '✓ ' + event.dataTransfer.files[0].name;
        }
      });

      document.getElementById('upload-form').addEventListener('submit', function() {
        const progress = document.getElementById('upload-progress');
        const fill = document.getElementById('upload-fill');
        const text = document.getElementById('upload-progress-text');

        progress.style.display = 'block';

        let percent = 0;
        const timer = setInterval(() => {
          percent = Math.min(percent + Math.random() * 15, 92);
          fill.style.width = percent + '%';
          text.textContent = 'Envoi en cours… ' + Math.floor(percent) + '%';

          if (percent >= 92) {
            clearInterval(timer);
          }
        }, 200);
      });

      const grid = document.getElementById('projects-grid');
      const orderForm = document.getElementById('sort-order-form');
      const orderedIdsInput = document.getElementById('ordered_ids_input');

      if (grid && orderForm && orderedIdsInput) {
        let draggedCard = null;

        const getCards = () => [...grid.querySelectorAll('.project-card')];

        function updateDisplayedPositions() {
          getCards().forEach((card, index) => {
            const badge = card.querySelector('.project-position-badge');
            if (badge) {
              badge.textContent = 'Position : ' + (index + 1);
            }
          });
        }

        function saveOrder() {
          const orderedIds = getCards().map(card => card.dataset.projectId);
          orderedIdsInput.value = JSON.stringify(orderedIds);
          orderForm.submit();
        }

        function bindCardEvents(card) {
          card.addEventListener('dragstart', () => {
            draggedCard = card;
            card.classList.add('dragging');
          });

          card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
            getCards().forEach(item => item.classList.remove('drag-over'));

            if (draggedCard) {
              updateDisplayedPositions();
              saveOrder();
            }

            draggedCard = null;
          });

          card.addEventListener('dragover', event => {
            event.preventDefault();

            if (!draggedCard || draggedCard === card) {
              return;
            }

            card.classList.add('drag-over');
          });

          card.addEventListener('dragleave', () => {
            card.classList.remove('drag-over');
          });

          card.addEventListener('drop', event => {
            event.preventDefault();

            if (!draggedCard || draggedCard === card) {
              return;
            }

            card.classList.remove('drag-over');

            const cards = getCards();
            const draggedIndex = cards.indexOf(draggedCard);
            const targetIndex = cards.indexOf(card);

            if (draggedIndex < targetIndex) {
              grid.insertBefore(draggedCard, card.nextSibling);
            } else {
              grid.insertBefore(draggedCard, card);
            }

            updateDisplayedPositions();
          });
        }

        getCards().forEach(bindCardEvents);
      }
    </script>
  <?php endif; ?>

</body>

</html>