<?php
require_once 'includes/db.php';
$current_page = 'work';
$page_title   = 'Travail — MIA';

// Tous les tags disponibles depuis la table tags
$all_tags = $pdo->query('SELECT label FROM tags ORDER BY label')->fetchAll(PDO::FETCH_COLUMN);

include 'includes/header.php';
?>

<style>
  .work-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 24px;
    align-items: start;
  }

  .work-item {
    grid-column: span 4;
    transition: opacity 0.25s ease, transform 0.25s ease;
  }

  .work-item--hidden {
    display: none !important;
  }

  .work-item.work-item--portrait {
    grid-column: span 4;
  }

  .work-item.work-item--landscape {
    grid-column: span 4;
  }

  .work-item.work-item--square {
    grid-column: span 4;
  }

  .work-item-inner {
    position: relative;
    overflow: hidden;
    border-radius: 0;
  }

  .work-rect,
  .work-rect--image,
  .work-rect--video {
    position: relative;
    width: 100%;
    overflow: hidden;
    background: #f3eee7;
  }

  .work-item--portrait .work-rect,
  .work-item--portrait .work-rect--image,
  .work-item--portrait .work-rect--video {
    aspect-ratio: 4 / 5;
    height: auto !important;
  }

  .work-item--landscape .work-rect,
  .work-item--landscape .work-rect--image,
  .work-item--landscape .work-rect--video {
    aspect-ratio: 16 / 10;
    height: auto !important;
  }

  .work-item--square .work-rect,
  .work-item--square .work-rect--image,
  .work-item--square .work-rect--video {
    aspect-ratio: 1 / 1;
    height: auto !important;
  }

  .work-rect img,
  .work-rect video,
  .work-rect--image img,
  .work-rect--video video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }

  @media (max-width: 1100px) {
    .work-item,
    .work-item.work-item--portrait,
    .work-item.work-item--landscape,
    .work-item.work-item--square {
      grid-column: span 6;
    }
  }

  @media (max-width: 768px) {
    .work-grid {
      grid-template-columns: repeat(1, 1fr);
    }

    .work-item,
    .work-item.work-item--portrait,
    .work-item.work-item--landscape,
    .work-item.work-item--square {
      grid-column: span 1;
    }
  }
</style>

<div class="page-header" style="padding-bottom:40px">
  <div class="label">Portfolio complet</div>
  <h1 class="section-title" style="margin-top:12px">Notre <em>travail</em></h1>
</div>

<!-- ═══ FILTRES ═══ -->
<div class="work-filters">
  <button class="filter-btn active" data-filter="*">Tout</button>
  <?php foreach ($all_tags as $tag): ?>
    <button class="filter-btn" data-filter="<?= htmlspecialchars($tag) ?>">
      <?= htmlspecialchars($tag) ?>
    </button>
  <?php endforeach; ?>
</div>

<!-- ═══ GRILLE ═══ -->
<div class="work-grid" id="work-grid">
  <?php foreach ($MIA['projects'] as $p):
    $type      = $p['type'] ?? 'color';
    $file      = $p['filename'] ?? '';
    $tags_json = htmlspecialchars(json_encode($p['tags'] ?? []), ENT_QUOTES, 'UTF-8');
  ?>
    <div class="work-item reveal" data-tags="<?= $tags_json ?>">
      <div class="work-item-inner">

        <?php if ($type === 'video' && $file): ?>
          <div class="work-rect work-rect--video">
            <video autoplay muted loop playsinline preload="metadata" class="js-media-ratio">
              <source src="assets/videos/<?= htmlspecialchars($file, ENT_QUOTES, 'UTF-8') ?>" type="video/mp4">
            </video>
          </div>

        <?php elseif ($type === 'image' && $file): ?>
          <div class="work-rect work-rect--image">
            <img
              src="assets/images/<?= htmlspecialchars($file, ENT_QUOTES, 'UTF-8') ?>"
              alt="<?= htmlspecialchars($p['brand'], ENT_QUOTES, 'UTF-8') ?>"
              class="js-media-ratio"
            >
          </div>

        <?php else: ?>
          <div
            class="work-rect"
            style="background:<?= htmlspecialchars($p['color'] ?? '#8C847A', ENT_QUOTES, 'UTF-8') ?>; aspect-ratio: 1 / 1;"
          ></div>
        <?php endif; ?>

        <div class="work-overlay">
          <div class="work-brand"><?= htmlspecialchars($p['brand'], ENT_QUOTES, 'UTF-8') ?></div>
          <div class="work-type"><?= htmlspecialchars($p['category'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
          <?php if (!empty($p['tags'])): ?>
            <div class="work-tags">
              <?php foreach ($p['tags'] as $tag): ?>
                <span class="work-tag"><?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  <?php endforeach; ?>
</div>

<script>
(function () {
  const btnTout = document.querySelector('.filter-btn[data-filter="*"]');
  const btnsFiltres = document.querySelectorAll('.filter-btn:not([data-filter="*"])');
  const items = document.querySelectorAll('.work-item');
  const actifs = new Set();

  function appliquerFiltres() {
    items.forEach(item => {
      const tags = JSON.parse(item.dataset.tags || '[]');
      const match = actifs.size === 0 || tags.some(tag => actifs.has(tag));
      item.classList.toggle('work-item--hidden', !match);
    });
  }

  if (btnTout) {
    btnTout.addEventListener('click', () => {
      actifs.clear();
      btnsFiltres.forEach(button => button.classList.remove('active'));
      btnTout.classList.add('active');
      appliquerFiltres();
    });
  }

  btnsFiltres.forEach(btn => {
    btn.addEventListener('click', () => {
      const filter = btn.dataset.filter;

      if (actifs.has(filter)) {
        actifs.delete(filter);
        btn.classList.remove('active');
      } else {
        actifs.add(filter);
        btn.classList.add('active');
      }

      if (btnTout) {
        btnTout.classList.toggle('active', actifs.size === 0);
      }

      appliquerFiltres();
    });
  });
})();
</script>

<script>
(function () {
  const mediaElements = document.querySelectorAll('.js-media-ratio');

  function applyRatioClass(media) {
    const workItem = media.closest('.work-item');
    if (!workItem) return;

    workItem.classList.remove('work-item--portrait', 'work-item--landscape', 'work-item--square');

    let width = 0;
    let height = 0;

    if (media.tagName === 'IMG') {
      width = media.naturalWidth;
      height = media.naturalHeight;
    } else if (media.tagName === 'VIDEO') {
      width = media.videoWidth;
      height = media.videoHeight;
    }

    if (!width || !height) return;

    const ratio = width / height;

    if (ratio > 1.1) {
      workItem.classList.add('work-item--landscape');
    } else if (ratio < 0.9) {
      workItem.classList.add('work-item--portrait');
    } else {
      workItem.classList.add('work-item--square');
    }
  }

  mediaElements.forEach(media => {
    if (media.tagName === 'IMG') {
      if (media.complete) {
        applyRatioClass(media);
      } else {
        media.addEventListener('load', () => applyRatioClass(media));
      }
    }

    if (media.tagName === 'VIDEO') {
      media.addEventListener('loadedmetadata', () => applyRatioClass(media));
    }
  });
})();
</script>

<?php include 'includes/footer.php'; ?>