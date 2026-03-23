<?php
/* =====================================================
   HEADER — inclus sur toutes les pages
   Usage : <?php include 'includes/header.php'; ?>
===================================================== */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title ?? $MIA['site']['name'] . ' — Contenu Visuel IA pour la Mode') ?></title>
  <meta name="description" content="<?= htmlspecialchars($MIA['site']['description']) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- GRAIN TEXTURE -->
<svg id="grain" xmlns="http://www.w3.org/2000/svg">
  <filter id="noise">
    <feTurbulence type="fractalNoise" baseFrequency="0.65" numOctaves="3" stitchTiles="stitch"/>
    <feColorMatrix type="saturate" values="0"/>
  </filter>
  <rect width="100%" height="100%" filter="url(#noise)"/>
</svg>

<!-- CURSOR -->
<div id="cursor-dot"></div>
<div id="cursor-follower"></div>

<!-- LOADER -->
<div id="loader">
  <?php if (!empty($MIA['site']['logo'])): ?>
    <img id="loader-logo" src="assets/images/<?= htmlspecialchars($MIA['site']['logo']) ?>"
         alt="<?= htmlspecialchars($MIA['site']['name']) ?>">
  <?php else: ?>
    <svg viewBox="0 0 160 70" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path id="loader-path"
        d="M8 52 L8 18 L28 44 L48 18 L48 52
           M64 52 L64 18 M64 35 L84 18 M64 35 L84 52
           M100 18 L92 18 L92 52 L100 52 Q122 52 122 35 Q122 18 100 18 Z"
        stroke="#E8492A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  <?php endif; ?>
</div>

<!-- PAGE TRANSITION -->
<div id="page-transition"></div>

<!-- NAVIGATION -->
<nav id="navbar">
  <a class="nav-logo" href="index.php">
    <?php if (!empty($MIA['site']['logo'])): ?>
      <img src="assets/images/<?= htmlspecialchars($MIA['site']['logo']) ?>"
           alt="<?= htmlspecialchars($MIA['site']['name']) ?>">
    <?php else: ?>
      MI<span>A</span>
    <?php endif; ?>
  </a>
  <ul class="nav-links">
    <?php foreach ($MIA['nav'] as $item): ?>
      <li>
        <a href="<?= $item['id'] ?>.php"
           <?= ($current_page ?? '') === $item['id'] ? 'class="active"' : '' ?>>
          <?= htmlspecialchars($item['label']) ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
  <a class="nav-cta" href="contact.php"><span>Démarrer un projet</span></a>
</nav>