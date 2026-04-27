<?php
if (!defined('ABSPATH')) {
  exit;
}

/** Helpers */
if (!function_exists('cb_map_terms_to_ids')) {
  function cb_map_terms_to_ids($maybe_terms, $taxonomy)
  {
    if (!is_array($maybe_terms) || empty($maybe_terms))
      return [];
    $ids = [];
    foreach ($maybe_terms as $t) {
      $raw = trim((string) $t);
      if ($raw === '') {
        continue;
      }

      if (is_numeric($t)) {
        $ids[] = intval($t);
        continue;
      }

      // Accept both slugs and human-readable names (e.g. "South Korea").
      $term = get_term_by('slug', sanitize_title($raw), $taxonomy);
      if (!$term || is_wp_error($term)) {
        $term = get_term_by('name', $raw, $taxonomy);
      }

      if ((!$term || is_wp_error($term)) && taxonomy_exists($taxonomy)) {
        $fallback = get_terms([
          'taxonomy' => $taxonomy,
          'hide_empty' => false,
          'name__like' => $raw,
          'number' => 1,
          'fields' => 'ids',
        ]);
        if (!is_wp_error($fallback) && !empty($fallback)) {
          $ids[] = intval($fallback[0]);
          continue;
        }
      }

      if ($term && !is_wp_error($term)) {
        $ids[] = intval($term->term_id);
      }
    }
    return array_values(array_unique(array_filter($ids)));
  }
}
if (!function_exists('cb_render_dotlist')) {
  function cb_render_dotlist($uid, $ids, $per_view, $suffix)
  {
    $n = count($ids);
    $per = max(1, intval($per_view));
    $pages = (int) ceil($n / $per);
    if ($pages < 1)
      return;
    echo '<ol class="carousel-blog__dots carousel-blog__dots--' . esc_attr($suffix) . '" role="tablist" aria-label="Select slide">';
    for ($p = 0; $p < $pages; $p++) {
      $center_index = min($n - 1, $p * $per + (int) floor(($per - 1) / 2));
      $target_id = $ids[$center_index];
      echo '<li><a class="carousel-blog__dot" role="tab" href="#' . esc_attr($target_id) . '" aria-label="' . esc_attr(sprintf(__('Go to page %d', 'childtheme'), $p + 1)) . '"></a></li>';
    }
    echo '</ol>';
  }
}

/** Attributes */
$uid = 'cb-' . wp_unique_id();
$heading = (string) ($attributes['heading'] ?? '');
$subheading = (string) ($attributes['subheading'] ?? '');
$align_class = isset($attributes['align']) ? 'align' . $attributes['align'] : '';

$source = $attributes['source'] ?? 'query';
$postsToShow = intval($attributes['postsToShow'] ?? 6);
$order = in_array(($attributes['order'] ?? 'DESC'), ['ASC', 'DESC'], true) ? $attributes['order'] : 'DESC';
$orderBy = in_array(($attributes['orderBy'] ?? 'date'), ['date', 'modified', 'title', 'rand'], true) ? $attributes['orderBy'] : 'date';
$imageSize = (string) ($attributes['imageSize'] ?? 'large');

$per_m = max(1, intval($attributes['perViewMobile'] ?? 1));
$per_t = max(1, intval($attributes['perViewTablet'] ?? 2));
$per_d = max(1, intval($attributes['perViewDesktop'] ?? 3));

$imageIcon = esc_url((string) ($attributes['imageIcon'] ?? ''));
$cardBackgroundColor = sanitize_hex_color_no_hash($attributes['cardBackgroundColor'] ?? ''); // Use sanitize_hex_color for safety, assuming it's a hex code
$background_style = $cardBackgroundColor ? 'style="--card-bg: #' . $cardBackgroundColor . ';"' : '';

/** Build slides */
$slides = [];
if ($source === 'manual') {
  $items = is_array($attributes['items'] ?? null) ? $attributes['items'] : [];
  foreach ($items as $it) {
    $slides[] = [
      'title' => (string) ($it['title'] ?? ''),
      'excerpt' => (string) ($it['excerpt'] ?? ''),
      'image' => esc_url_raw((string) ($it['imageUrl'] ?? '')),
      'url' => esc_url_raw((string) ($it['linkUrl'] ?? '#')),
    ];
  }
} else {
  $cat_ids = cb_map_terms_to_ids($attributes['categories'] ?? [], 'category');
  $tag_ids = cb_map_terms_to_ids($attributes['tags'] ?? [], 'post_tag');
  $args = [
    'post_type' => 'post',
    'posts_per_page' => max(1, $postsToShow),
    'ignore_sticky_posts' => true,
    'no_found_rows' => true,
    'orderby' => $orderBy,
    'order' => $order,
  ];
  if ($cat_ids)
    $args['category__in'] = $cat_ids;
  if ($tag_ids)
    $args['tag__in'] = $tag_ids;

  $q = new WP_Query($args);
  if ($q->have_posts()) {
    foreach ($q->posts as $p) {
      $slides[] = [
        'title' => get_the_title($p),
        'excerpt' => wp_strip_all_tags(get_the_excerpt($p)),
        'image' => get_the_post_thumbnail_url($p, $imageSize) ?: '',
        'url' => get_permalink($p),
      ];
    }
  }
  wp_reset_postdata();
}
$count = count($slides);
if ($count === 0) {
  return '';
}

/** Wrapper */
$wrapper_attributes = get_block_wrapper_attributes([
  'class' => 'carousel-blog ' . $align_class . ' child-block',
  'id' => $uid,
]);

// Precompute slide IDs
$ids = [];
for ($i = 0; $i < $count; $i++)
  $ids[$i] = $uid . '-s' . ($i + 1);
?>
<div <?php echo $wrapper_attributes; ?> <?php echo $background_style; ?>>

  <?php if ($heading || $subheading): ?>
    <div class="carousel-blog__header">
      <?php if ($heading): ?>
        <h2 class="carousel-blog__title"><?php echo esc_html($heading); ?></h2><?php endif; ?>
      <?php if ($subheading): ?>
        <p class="carousel-blog__sub"><?php echo wp_kses_post($subheading); ?></p><?php endif; ?>
    </div>
  <?php endif; ?>

  <?php // --- NEW: Image Icon Display (placed inside the main wrapper) --- ?>
  <?php if ($imageIcon): ?>
    <img class="carousel-blog__icon" src="<?php echo $imageIcon; ?>" alt="" loading="lazy" decoding="async"
      aria-hidden="true" />
  <?php endif; ?>
  <?php // ----------------------------------------------------------------- ?>

  <div class="carousel-blog__viewport" role="region" aria-label="<?php esc_attr_e('Blog Carousel', 'childtheme'); ?>">
    <div class="carousel-blog__track">
      <?php foreach ($slides as $i => $s):
        $slide_id = $ids[$i];
        ?>
        <article id="<?php echo esc_attr($slide_id); ?>" class="carousel-blog__slide" role="group"
          aria-roledescription="slide" aria-label="<?php echo esc_attr(($i + 1) . ' of ' . $count); ?>">
          <?php if (!empty($s['image'])): ?>
            <img class="carousel-blog__img" src="<?php echo esc_url($s['image']); ?>" alt="" loading="lazy"
              decoding="async" />
          <?php else: ?>
            <div class="carousel-blog__img carousel-blog__img--placeholder" aria-hidden="true"></div>
          <?php endif; ?>

          <div class="carousel-blog__overlay">
            <?php if (!empty($s['title'])): ?>
              <h3 class="carousel-blog__card-title"><?php echo esc_html($s['title']); ?></h3><?php endif; ?>
            <?php if (!empty($s['excerpt'])): ?>
              <p class="carousel-blog__excerpt"><?php echo esc_html(wp_trim_words($s['excerpt'], 24)); ?></p><?php endif; ?>
            <?php
            $slide_url = isset($s['url']) ? trim((string) $s['url']) : '';
            if ($slide_url !== '' && $slide_url !== '#'):
              ?>
              <p class="carousel-blog__cta">
                <?php
                echo render_block([
                  'blockName' => 'ilegiants/cta-wave-card',
                  'attrs' => [
                    'text' => __('Read More', 'childtheme'),
                    'url' => $slide_url,
                    'context' => 'on-image',
                    'size' => 'md',
                    'accent' => '#FD593C'
                  ],
                  'innerBlocks' => [],
                  'innerHTML' => '',
                  'innerContent' => []
                ]);
                ?>
              </p>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>

  <?php /* Global Navset (replacing dots) */ ?>
  <div class="carousel-blog__controls">
    <button class="carousel-blog__arrow carousel-blog__arrow--prev"
      aria-label="<?php esc_attr_e('Previous slide', 'childtheme'); ?>" disabled></button>
    <button class="carousel-blog__arrow carousel-blog__arrow--next"
      aria-label="<?php esc_attr_e('Next slide', 'childtheme'); ?>"></button>
  </div>

  <script>
    (function () {
      const root = document.getElementById(<?php echo wp_json_encode($uid); ?>);
      if (!root) return;

      const track = root.querySelector('.carousel-blog__track');
      const slides = Array.from(root.querySelectorAll('.carousel-blog__slide'));
      const prevBtn = root.querySelector('.carousel-blog__arrow--prev');
      const nextBtn = root.querySelector('.carousel-blog__arrow--next');

      if (!track || !slides.length) return;

      const vCenter = () => { const r = track.getBoundingClientRect(); return r.left + r.width / 2; };
      const sCenter = el => { const r = el.getBoundingClientRect(); return r.left + r.width / 2; };

      function nearestIndex() {
        const vc = vCenter(); let best = 0, dist = Infinity;
        slides.forEach((s, i) => { const d = Math.abs(sCenter(s) - vc); if (d < dist) { dist = d; best = i; } });
        return best;
      }

      function updateButtons(idx) {
        if (prevBtn) prevBtn.disabled = (idx <= 0);
        if (nextBtn) nextBtn.disabled = (idx >= slides.length - 1);
      }

      function scrollToMap(idx) {
        idx = Math.max(0, Math.min(slides.length - 1, idx));
        const el = slides[idx];
        const tr = track.getBoundingClientRect();
        const sl = el.getBoundingClientRect();
        const delta = (sl.left - tr.left) - (tr.width / 2 - sl.width / 2);

        track.scrollBy({ left: delta, behavior: 'smooth' });
        // Update state momentarily
        setTimeout(() => updateButtons(idx), 300);
      }

      // Initial check
      updateButtons(nearestIndex());

      // Click Handlers
      if (prevBtn) {
        prevBtn.addEventListener('click', (e) => {
          e.preventDefault();
          const cur = nearestIndex();
          if (cur > 0) scrollToMap(cur - 1);
        });
      }

      if (nextBtn) {
        nextBtn.addEventListener('click', (e) => {
          e.preventDefault();
          const cur = nearestIndex();
          if (cur < slides.length - 1) scrollToMap(cur + 1);
        });
      }

      // Scroll listener to update buttons state on manual scroll
      let t;
      track.addEventListener('scroll', function () {
        clearTimeout(t);
        t = setTimeout(() => updateButtons(nearestIndex()), 100);
      }, { passive: true });

      window.addEventListener('resize', () => updateButtons(nearestIndex()));
    })();
  </script>
</div>