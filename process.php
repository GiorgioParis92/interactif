<?php
require_once 'includes/db.php';
$current_page = 'process';
$page_title   = 'Process — MIA';
include    'includes/header.php';
?>

<div class="page-header">
  <div class="label">Notre méthode</div>
  <h1 class="section-title">5 étapes vers vos<br>visuels <em>parfaits</em></h1>
</div>

<div class="process-content">
  <div class="process-timeline">
    <?php foreach ($MIA['process'] as $step): ?>
      <div class="timeline-item reveal">
        <div class="timeline-marker">
          <div class="timeline-dot" style="background:<?= $step['color'] ?>"></div>
          <div class="timeline-num" style="color:<?= $step['color'] ?>;opacity:0.15"><?= $step['num'] ?></div>
        </div>
        <div class="timeline-content">
          <div class="timeline-step-label">
            <span class="label <?= $step['label_class'] ?>"
              <?= !$step['label_class'] ? 'style="color:' . $step['color'] . '"' : '' ?>>
              <?= htmlspecialchars($step['label']) ?>
            </span>
          </div>
          <h2 class="timeline-title"><?= htmlspecialchars($step['title']) ?></h2>
          <p class="timeline-desc"><?= htmlspecialchars($step['desc']) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div style="margin-top:40px;text-align:center;padding-bottom:60px">
    <a class="btn btn-coral" href="contact.php"><span>Lancer mon projet →</span></a>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
