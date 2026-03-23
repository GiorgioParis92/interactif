<?php
require_once 'includes/db.php';
require_once __DIR__ . '/includes/contact_mail.php';

$current_page = 'contact';
$page_title   = 'Contact — MIA';

/* ─── TRAITEMENT DU FORMULAIRE ─── */
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $prenom  = trim($_POST['prenom']  ?? '');
  $nom     = trim($_POST['nom']     ?? '');
  $email   = trim($_POST['email']   ?? '');
  $marque  = trim($_POST['marque']  ?? '');
  $besoin  = trim($_POST['besoin']  ?? '');
  $message = trim($_POST['message'] ?? '');

  // Validation basique
  if (!$prenom || !$nom || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$marque || !$besoin || !$message) {
    $flash = ['type' => 'error', 'msg' => 'Merci de remplir tous les champs correctement.'];
  } else {
    $to           = trim($MIA['site']['email'] ?? '');
    $subjectPlain = "Nouveau brief de $prenom $nom — $marque";
    $subjectMime  = '=?UTF-8?B?' . base64_encode($subjectPlain) . '?=';

    $body  = "Prénom : $prenom\r\n";
    $body .= "Nom : $nom\r\n";
    $body .= "Email : $email\r\n";
    $body .= "Marque : $marque\r\n";
    $body .= "Besoin : $besoin\r\n\r\n";
    $body .= "Message :\r\n$message";

    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
      $flash = ['type' => 'error', 'msg' => 'Email de destination du site non configuré.'];
    } elseif ($is_local) {
      $flash = ['type' => 'success', 'msg' => 'Message envoyé ✓ (mode local — aucun mail réel).'];
      $_POST = [];
    } else {
      $sent = mia_contact_send_mail($to, $email, $subjectMime, $subjectPlain, $body);

      if ($sent) {
        $flash = ['type' => 'success', 'msg' => 'Message envoyé ✓ Nous vous répondons sous 24h.'];
        $_POST = [];
      } else {
        $hint = is_readable(__DIR__ . '/includes/contact_smtp_config.php')
          ? ''
          : ' Si le problème continue, configure includes/contact_smtp_config.php (voir contact_smtp_config.example.php).';
        $flash = ['type' => 'error', 'msg' => "Erreur lors de l'envoi. Réessayez ou écrivez-nous directement à $to.$hint"];
      }
    }
  }
}

include 'includes/header.php';
?>

<div class="contact-layout">

  <!-- ═══ INFOS ═══ -->
  <div>
    <div class="label" style="margin-bottom:16px">Parlons de votre projet</div>
    <h1 class="contact-title">Démarrons<br>quelque chose <em>ensemble</em>.</h1>
    <p class="contact-desc">Envoyez-nous votre brief et recevez une réponse sous 24h. Pas de formulaire compliqué, pas d'engagement — juste une conversation.</p>
    <div class="contact-detail">
      <div class="contact-item">
        <div class="contact-item-icon">✉</div>
        <div class="contact-item-text">
          <strong>Email</strong>
          <span><a href="mailto:<?= htmlspecialchars($MIA['site']['email']) ?>"><?= htmlspecialchars($MIA['site']['email']) ?></a></span>
        </div>
      </div>
      <div class="contact-item">
        <div class="contact-item-icon">◎</div>
        <div class="contact-item-text">
          <strong>Localisation</strong>
          <span><?= htmlspecialchars($MIA['site']['location']) ?></span>
        </div>
      </div>
      <div class="contact-item">
        <div class="contact-item-icon">⚡</div>
        <div class="contact-item-text">
          <strong>Délai de réponse</strong>
          <span><?= htmlspecialchars($MIA['site']['response']) ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══ FORMULAIRE ═══ -->
  <form method="POST" action="contact.php">

    <?php if ($flash): ?>
      <div class="flash <?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <div class="form-row">
      <div class="form-group">
        <label for="prenom">Prénom</label>
        <input type="text" id="prenom" name="prenom" placeholder="Sophie"
               value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label for="nom">Nom</label>
        <input type="text" id="nom" name="nom" placeholder="Martin"
               value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
      </div>
    </div>

    <div class="form-group">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" placeholder="sophie@mamarque.com"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
    </div>

    <div class="form-group">
      <label for="marque">Nom de la marque</label>
      <input type="text" id="marque" name="marque" placeholder="Ma Marque"
             value="<?= htmlspecialchars($_POST['marque'] ?? '') ?>" required>
    </div>

    <div class="form-group">
      <label for="besoin">Votre besoin</label>
      <select id="besoin" name="besoin" required>
        <option value="">Sélectionnez un service</option>
        <?php foreach ($MIA['services'] as $s): ?>
          <option value="<?= $s['id'] ?>"
            <?= (($_POST['besoin'] ?? '') === $s['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($s['name']) ?> — <?= $s['price'] ?>€ / <?= htmlspecialchars($s['price_label']) ?>
          </option>
        <?php endforeach; ?>
        <option value="multiple" <?= (($_POST['besoin'] ?? '') === 'multiple') ? 'selected' : '' ?>>Plusieurs services</option>
        <option value="autre"    <?= (($_POST['besoin'] ?? '') === 'autre')    ? 'selected' : '' ?>>Autre / Sur-mesure</option>
      </select>
    </div>

    <div class="form-group">
      <label for="message">Votre message</label>
      <textarea id="message" name="message"
                placeholder="Décrivez votre projet, votre univers de marque, vos références..." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
    </div>

    <button type="submit" class="btn btn-coral form-submit">
      <span>Envoyer mon brief →</span>
    </button>

  </form>
</div>

<?php include 'includes/footer.php'; ?>
