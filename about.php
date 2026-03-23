<?php
require_once 'includes/db.php';
$current_page = 'about';
$page_title   = 'Studio — MIA';
$about        = $MIA['about'];
include    'includes/header.php';
?>

<!-- ═══ MANIFESTO ═══ -->
<div class="about-hero">
  <p class="about-manifesto reveal"><?= $about['manifesto'] ?></p>
</div>

<!-- ═══ VISION ═══ -->
<div class="about-vision">
  <div>
    <div style="margin-bottom:20px"><span class="label label-coral">Notre vision</span></div>
    <h2 class="about-vision-title reveal"><?= $about['vision_title'] ?></h2>
    <?php foreach ($about['vision_texts'] as $text): ?>
      <p class="about-vision-text reveal"><?= htmlspecialchars($text) ?></p>
    <?php endforeach; ?>
  </div>
  <div class="about-vision-visual">
    <div style="background:var(--coral);border-radius:16px"></div>
    <div style="background:var(--cobalt);border-radius:16px"></div>
    <div style="background:var(--marigold);border-radius:16px"></div>
    <div style="background:var(--ink);border-radius:16px"></div>
  </div>
</div>

<!-- ═══ VALEURS ═══ -->
<div class="about-values">
  <div class="label" style="margin-bottom:16px">Ce qui nous définit</div>
  <h2 class="section-title reveal">Nos <em>valeurs</em></h2>
  <div class="values-grid">
    <?php foreach ($about['values'] as $i => $v): ?>
      <div class="value-card reveal<?= $i > 0 ? ' reveal-delay-' . $i : '' ?>">
        <div class="value-icon" style="color:<?= $v['color'] ?>"><?= $v['icon'] ?></div>
        <h3 class="value-name"><?= htmlspecialchars($v['name']) ?></h3>
        <p class="value-desc"><?= htmlspecialchars($v['desc']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
