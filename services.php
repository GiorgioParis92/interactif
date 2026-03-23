<?php
require_once 'includes/db.php';
$current_page = 'services';
$page_title   = 'Services — MIA';
include    'includes/header.php';
?>

<div class="page-header">
  <div class="label">Nos offres</div>
  <h1 class="section-title">Des visuels qui <em>convertissent</em></h1>
</div>

<div class="services-content">

  <!-- ═══ FICHES SERVICES ═══ -->
  <?php foreach ($MIA['services'] as $i => $s): ?>
    <div class="service-detail-card<?= $i % 2 !== 0 ? ' reverse' : '' ?> reveal">
      <div>
        <div class="service-detail-label">
          <span class="label <?= $s['label_color'] ?>"><?= $s['num'] ?> — <?= htmlspecialchars($s['name']) ?></span>
        </div>
        <h2 class="service-detail-title"><em><?= htmlspecialchars($s['name']) ?></em></h2>
        <p class="service-detail-desc"><?= htmlspecialchars($s['full_desc']) ?></p>
        <ul class="service-features">
          <?php foreach ($s['features'] as $f): ?>
            <li><?= htmlspecialchars($f) ?></li>
          <?php endforeach; ?>
        </ul>
        <div class="service-detail-price">
          <?= $s['price'] ?>€
          <span style="font-size:1rem;font-weight:400;color:var(--stone)">/ <?= htmlspecialchars($s['price_label']) ?></span>
        </div>
        <a class="btn <?= $s['btn'] ?>" href="contact.php" style="margin-top:28px"><span>Commander</span></a>
      </div>
      <div class="service-detail-visual" style="background:<?= $s['color'] ?>"></div>
    </div>
  <?php endforeach; ?>

  <!-- ═══ COMPARATIF ═══ -->
  <div class="comparison-section">
    <div class="label" style="margin-bottom:16px">Comparatif</div>
    <h2 class="section-title reveal">Choisissez votre <em>formule</em></h2>
    <table class="comparison-table">
      <thead>
        <tr>
          <th>Inclus</th>
          <?php foreach ($MIA['services'] as $s): ?>
            <th><?= htmlspecialchars($s['name']) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($MIA['comparison'] as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['label']) ?></td>
            <?php foreach (['lifestyle','eshop','video'] as $col): ?>
              <td<?= $col === 'eshop' ? ' class="col-highlight"' : '' ?>>
                <?php if ($row[$col] === true): ?>
                  <span class="check">✓</span>
                <?php elseif ($row[$col] === false): ?>
                  —
                <?php else: ?>
                  <strong><?= htmlspecialchars($row[$col]) ?></strong>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- ═══ FAQ ═══ -->
  <div class="faq-section">
    <div class="label" style="margin-bottom:16px">Questions fréquentes</div>
    <h2 class="section-title reveal">Tout ce que vous<br>voulez <em>savoir</em></h2>
    <div class="faq-list">
      <?php foreach ($MIA['faq'] as $item): ?>
        <div class="faq-item">
          <button class="faq-question">
            <span><?= htmlspecialchars($item['q']) ?></span>
            <span class="faq-icon">+</span>
          </button>
          <div class="faq-answer">
            <div class="faq-answer-inner"><?= htmlspecialchars($item['a']) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

</div><!-- .services-content -->

<?php include 'includes/footer.php'; ?>
