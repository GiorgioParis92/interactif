<?php
/* =====================================================
   FOOTER — inclus sur toutes les pages
   Usage : <?php include 'includes/footer.php'; ?>
===================================================== */
?>

<footer>
  <div class="footer-top">
    <div>
      <div class="footer-logo">
        
      <a class="nav-logo" href="index.php">
    <?php if (!empty($MIA['site']['logo'])): ?>
      <img src="assets/images/<?= htmlspecialchars($MIA['site']['logo']) ?>"
           alt="<?= htmlspecialchars($MIA['site']['name']) ?>">
    <?php else: ?>
      MI<span>A</span>
    <?php endif; ?>
  </a>

      </div>
      <p class="footer-tagline"><?= htmlspecialchars($MIA['site']['tagline']) ?></p>
    </div>
    <div>
      <div class="footer-col-title">Navigation</div>
      <ul class="footer-links">
        <?php foreach ($MIA['nav'] as $item): ?>
          <li><a href="<?= $item['id'] ?>.php"><?= htmlspecialchars($item['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div>
      <div class="footer-col-title">Contact</div>
      <div class="footer-contact-item">
        <span class="label">Email</span>
        <p><a href="mailto:<?= htmlspecialchars($MIA['site']['email']) ?>"><?= htmlspecialchars($MIA['site']['email']) ?></a></p>
      </div>
      <div class="footer-contact-item">
        <span class="label">Localisation</span>
        <p><?= htmlspecialchars($MIA['site']['location']) ?></p>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <p class="footer-copy">&copy; <?= htmlspecialchars($MIA['site']['copyright']) ?></p>
    <p class="footer-credits">Contenu visuel IA par <span><?= htmlspecialchars($MIA['site']['name']) ?></span></p>
  </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
