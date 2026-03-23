<?php
/* =====================================================
   MIA — BASE DE DONNÉES
   includes/db.php
   
   Lit toutes les données depuis MySQL
   et les met en forme dans $MIA exactement
   comme avant — aucune autre page à modifier.
===================================================== */

require_once __DIR__ . '/connexion.php';

/* ─── Infos générales ─── */
$site = $pdo->query('SELECT * FROM site LIMIT 1')->fetch();

/* ─── Navigation ─── */
$nav = $pdo->query('SELECT page_id AS id, label FROM nav ORDER BY position')->fetchAll();

/* ─── Marquee ─── */
$marquee = $pdo->query('SELECT texte FROM marquee ORDER BY position')
               ->fetchAll(PDO::FETCH_COLUMN);

/* ─── Hero ─── */
$hero_row   = $pdo->query('SELECT * FROM hero LIMIT 1')->fetch();
$hero_words = $pdo->query('SELECT word FROM hero_title_words ORDER BY position')
                  ->fetchAll(PDO::FETCH_COLUMN);
$hero_cta   = $pdo->query('SELECT label, page, style FROM hero_cta ORDER BY position')
                  ->fetchAll();

/* ─── Stats ─── */
$stats_row   = $pdo->query('SELECT quote FROM stats LIMIT 1')->fetch();
$stats_items = $pdo->query('SELECT count, suffix, label FROM stats_items ORDER BY position')
                   ->fetchAll();

/* ─── Projets ─── */
$projects_rows = $pdo->query(
    'SELECT id, brand, category, color, height, featured, type, filename
     FROM projects ORDER BY position'
)->fetchAll();

$projects = [];
foreach ($projects_rows as $p) {
    // Récupérer les tags de ce projet
    $stmt = $pdo->prepare(
        'SELECT t.label FROM tags t
         JOIN project_tags pt ON pt.tag_id = t.id
         WHERE pt.project_id = ?
         ORDER BY t.label'
    );
    $stmt->execute([$p['id']]);
    $p['tags']     = $stmt->fetchAll(PDO::FETCH_COLUMN); // tableau de labels
    $p['featured'] = (bool) $p['featured'];
    $projects[]    = $p;
}

/* ─── Services + features ─── */
$services_rows = $pdo->query(
    'SELECT id, slug AS id_slug, num, icon, name,
            short_desc, full_desc, price, price_label,
            theme, btn, label_color, color
     FROM services ORDER BY position'
)->fetchAll();

$services = [];
foreach ($services_rows as $row) {
    $stmt = $pdo->prepare('SELECT feature FROM service_features WHERE service_id = ? ORDER BY position');
    $stmt->execute([$row['id']]);
    $features = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $price = (float)$row['price'];
    $services[] = [
        'id'          => $row['id_slug'],
        'num'         => $row['num'],
        'icon'        => $row['icon'],
        'name'        => $row['name'],
        'short_desc'  => $row['short_desc'],
        'full_desc'   => $row['full_desc'],
        'price'       => $price == (int)$price ? (string)(int)$price : (string)$price,
        'price_label' => $row['price_label'],
        'theme'       => $row['theme'],
        'btn'         => $row['btn'],
        'label_color' => $row['label_color'],
        'color'       => $row['color'],
        'features'    => $features,
    ];
}

/* ─── Comparatif ─── */
function _parseCell(string $val) {
    if ($val === '1') return true;
    if ($val === '0') return false;
    return $val;
}
$comparison_rows = $pdo->query('SELECT label, lifestyle, eshop, video FROM comparison ORDER BY position')->fetchAll();
$comparison = [];
foreach ($comparison_rows as $row) {
    $comparison[] = [
        'label'     => $row['label'],
        'lifestyle' => _parseCell($row['lifestyle']),
        'eshop'     => _parseCell($row['eshop']),
        'video'     => _parseCell($row['video']),
    ];
}

/* ─── FAQ ─── */
$faq = $pdo->query('SELECT question AS q, answer AS a FROM faq ORDER BY position')->fetchAll();

/* ─── Process ─── */
$process = $pdo->query(
    'SELECT num, color, label_class, label, title, description AS `desc` FROM process ORDER BY position'
)->fetchAll();

/* ─── About ─── */
$about_row    = $pdo->query('SELECT manifesto, vision_title FROM about LIMIT 1')->fetch();
$vision_texts = $pdo->query('SELECT texte FROM about_vision_texts ORDER BY position')->fetchAll(PDO::FETCH_COLUMN);
$values       = $pdo->query('SELECT icon, color, name, description AS `desc` FROM about_values ORDER BY position')->fetchAll();

/* ─── Home medias ─── */
$home_medias = $pdo->query('SELECT * FROM home_medias ORDER BY position')->fetchAll();

/* ─── Home process ─── */
$home_process = $pdo->query('SELECT num, name, description AS `desc` FROM home_process ORDER BY position')->fetchAll();

/* ─── Assemblage $MIA ─── */
$MIA = [
    'site'         => $site,
    'nav'          => $nav,
    'marquee'      => $marquee,
    'hero'         => [
        'label'    => $hero_row['label'],
        'title'    => $hero_words,
        'subtitle' => $hero_row['subtitle'],
        'cta'      => $hero_cta,
    ],
    'stats'        => [
        'quote' => $stats_row['quote'],
        'items' => $stats_items,
    ],
    'projects'     => $projects,
    'services'     => $services,
    'comparison'   => $comparison,
    'faq'          => $faq,
    'process'      => $process,
    'about'        => [
        'manifesto'    => $about_row['manifesto'],
        'vision_title' => $about_row['vision_title'],
        'vision_texts' => $vision_texts,
        'values'       => $values,
    ],
    'home_process' => $home_process,
    'home_medias'  => $home_medias,
];