<?php
require_once 'includes/db.php';
$current_page = 'home';
$page_title   = 'MIA — Contenu Visuel IA pour la Mode';
include    'includes/header.php';
?>

<!-- ═══ HERO ═══ -->
<div class="hero">
  <div class="hero-content">
    <div class="hero-label">
      <span class="dot"></span>
      <span class="label label-coral"><?= $MIA['hero']['label'] ?></span>
    </div>
    <h1 class="hero-title">
      <?php foreach ($MIA['hero']['title'] as $word): ?>
        <span class="word"><span class="word-inner"><?= $word ?></span></span>
      <?php endforeach; ?>
    </h1>
    <p class="hero-sub"><?= htmlspecialchars($MIA['hero']['subtitle']) ?></p>
    <div class="hero-actions">
      <?php foreach ($MIA['hero']['cta'] as $cta): ?>
        <a class="btn <?= $cta['style'] ?>" href="<?= $cta['page'] ?>.php">
          <span><?= htmlspecialchars($cta['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="hero-visual">
    <div class="hero-grid">

      <?php
      $video_ext = ['mp4', 'webm', 'ogg', 'mov'];
      $total     = 6;

      // Indexer les médias par position (1 à 6)
      $medias_by_pos = [];
      foreach ($MIA['home_medias'] as $m) {
          $medias_by_pos[(int)$m['position']] = $m;
      }

      for ($slot = 1; $slot <= $total; $slot++):
          $media    = $medias_by_pos[$slot] ?? null;
          $is_video = false;
          $path     = '';
          $alt      = '';

          if ($media) {
              $ext      = strtolower(pathinfo($media['filename'], PATHINFO_EXTENSION));
              $is_video = in_array($ext, $video_ext);
              $path     = 'assets/' . ($is_video ? 'videos' : 'images') . '/' . $media['filename'];
              $alt      = pathinfo($media['filename'], PATHINFO_FILENAME);
          }
      ?>

        <?php if ($media && $is_video): ?>
          <div class="hero-rect hero-rect--video">
            <video autoplay muted loop playsinline preload="auto">
              <source src="<?= htmlspecialchars($path) ?>?v=<?= $media['id'] ?>" type="video/<?= $ext ?>">
            </video>
          </div>

        <?php elseif ($media): ?>
          <div class="hero-rect">
            <img src="<?= htmlspecialchars($path) ?>"
                 alt="<?= htmlspecialchars($alt) ?>">
          </div>

        <?php else: ?>
          <div class="hero-rect"></div>
        <?php endif; ?>

      <?php endfor; ?>

    </div>
    <svg class="hero-badge" viewBox="0 0 110 110" xmlns="http://www.w3.org/2000/svg">
      <defs><path id="badgePath" d="M55,55 m-38,0 a38,38 0 1,1 76,0 a38,38 0 1,1 -76,0"/></defs>
      <text><textPath href="#badgePath">IA × MODE × CONTENU ✦ IA × MODE × CONTENU ✦ </textPath></text>
    </svg>
  </div>
</div>

<!-- ═══ MARQUEE ═══ -->
<div class="marquee-strip" aria-hidden="true">
  <div class="marquee-inner">
    <?php $items = array_merge($MIA['marquee'], $MIA['marquee']); ?>
    <?php foreach ($items as $item): ?>
      <span class="marquee-text"><?= htmlspecialchars($item) ?><span class="marquee-sep">✦</span></span>
    <?php endforeach; ?>
  </div>
</div>

<!-- ═══ SHOWCASE ═══ -->
<div class="showcase">
  <div class="section-header reveal">
    <div>
      <div class="label" style="margin-bottom:12px">02 — Portfolio</div>
      <h2 class="section-title">Notre <em>travail</em></h2>
    </div>
    <a class="btn btn-outline" href="work.php"><span>Voir tout →</span></a>
  </div>
  <div class="showcase-grid">
    <?php
     
    $featured = array_values(array_filter($MIA['projects'], fn($p) => $p['featured']));
    foreach ($featured as $i => $p):
      $delay = $i > 0 ? ' reveal-delay-' . min($i, 3) : '';
      $type  = $p['type'] ?? 'color';
      $file  = $p['filename'] ?? '';

    ?>
      <div class="showcase-card reveal<?= $delay ?>">

        <?php if ($type === 'video' && $file): ?>
          <div class="card-bg card-bg--video">
            <video autoplay muted loop playsinline preload="auto">
              <source src="assets/videos/<?= htmlspecialchars($file) ?>" type="video/mp4">
            </video>
          </div>

        <?php elseif ($type === 'image' && $file): ?>
          <div class="card-bg card-bg--image">
            <img src="assets/images/<?= htmlspecialchars($file) ?>"
                 alt="<?= htmlspecialchars($p['brand']) ?>">
          </div>

        <?php else: ?>
          <div class="card-bg" style="background:<?= htmlspecialchars($p['color']) ?>"></div>
        <?php endif; ?>

        <div class="card-overlay">
          <div>
            <div class="card-brand"><?= htmlspecialchars($p['brand']) ?></div>
            <div class="card-cat"><?= htmlspecialchars($p['category']) ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ═══ STATS ═══ -->
<div class="stats-section">
  <p class="stats-quote reveal"><?= $MIA['stats']['quote'] ?></p>
  <div class="stats-grid">
    <?php foreach ($MIA['stats']['items'] as $i => $stat): ?>
      <div class="stat-item reveal<?= $i > 0 ? ' reveal-delay-' . $i : '' ?>" data-count="<?= $stat['count'] ?>">
        <span class="stat-num">
          <span class="counter">0</span><span class="stat-suffix"><?= $stat['suffix'] ?></span>
        </span>
        <p class="stat-label"><?= htmlspecialchars($stat['label']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ═══ SERVICES ═══ -->
<div class="services-home">
  <div style="position:relative">
    <div class="section-num" style="position:absolute;top:-20px;right:0">03</div>
    <div class="label" style="margin-bottom:12px">03 — Nos offres</div>
    <h2 class="section-title reveal">Des visuels <em>professionnels</em>,<br>à portée de toutes les marques.</h2>
  </div>
  <div class="services-grid">
    <?php foreach ($MIA['services'] as $i => $s): ?>
      <div class="service-card <?= $s['theme'] ?> reveal<?= $i > 0 ? ' reveal-delay-' . $i : '' ?>">
        <div class="service-num"><?= $s['num'] ?> / 0<?= count($MIA['services']) ?></div>
        <div class="service-icon"><?= $s['icon'] ?></div>
        <h3 class="service-name"><?= htmlspecialchars($s['name']) ?></h3>
        <p class="service-desc"><?= htmlspecialchars($s['short_desc']) ?></p>
        <div class="service-price"><?= $s['price'] ?>€</div>
        <div class="service-price-label"><?= htmlspecialchars($s['price_label']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ═══ PROCESS HOME ═══ -->
<div class="process-home">
  <div>
    <div class="label" style="margin-bottom:12px">04 — Comment ça marche</div>
    <h2 class="section-title reveal">Simple, rapide,<br><em>efficace</em>.</h2>
  </div>
  <div class="process-steps">
    <?php foreach ($MIA['home_process'] as $i => $step): ?>
      <div class="process-step reveal<?= $i > 0 ? ' reveal-delay-' . $i : '' ?>">
        <div class="step-num"><?= $step['num'] ?></div>
        <h3 class="step-name"><?= htmlspecialchars($step['name']) ?></h3>
        <p class="step-desc"><?= htmlspecialchars($step['desc']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ═══ CTA ═══ -->
<div class="cta-section">
  <h2 class="cta-title reveal">Prêt à transformer<br>vos visuels ?</h2>
  <p class="cta-sub reveal">Rejoignez les marques qui font confiance à MIA.</p>
  <div class="cta-actions reveal">
    <a class="btn btn-outline-white" href="contact.php"><span>Démarrer un projet</span></a>
    <a class="btn" style="background:var(--white);color:var(--coral)" href="services.php"><span>Voir les tarifs</span></a>
  </div>
</div>

<?php include 'includes/footer.php'; ?>