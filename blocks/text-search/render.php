<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$title          = isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : '';
$intro          = isset( $attributes['intro'] ) ? sanitize_text_field( $attributes['intro'] ) : '';
$btn_label      = isset( $attributes['btn-placeholder'] ) ? sanitize_text_field( $attributes['btn-placeholder'] ) : 'Search';
$publish_url    = isset( $attributes['publishUrl'] ) ? esc_url_raw( $attributes['publishUrl'] ) : '/community/publish-content/';
$discover_url   = isset( $attributes['discoverUrl'] ) ? esc_url_raw( $attributes['discoverUrl'] ) : '/community/discover/';
$cutoff_days    = isset( $attributes['cutoffDays'] ) ? (int) $attributes['cutoffDays'] : 7;
$cutoff_days    = $cutoff_days > 0 ? $cutoff_days : 7;
$instance_id    = 'text_search_' . wp_generate_uuid4();
$rest_posts_url = rest_url( 'wp/v2/posts' );

$image_cat = get_category_by_slug( 'imagepost' );
$image_cat_id = ( $image_cat && ! is_wp_error( $image_cat ) ) ? (int) $image_cat->term_id : 0;

$aria_label = $title ? $title : __( 'Community search', 'child' );
?>
<section id="<?php echo esc_attr( $instance_id ); ?>"
         class="text-search-block alignwide"
         aria-label="<?php echo esc_attr( $aria_label ); ?>"
         data-rest-url="<?php echo esc_url( $rest_posts_url ); ?>"
         data-publish-url="<?php echo esc_url( $publish_url ); ?>"
         data-discover-url="<?php echo esc_url( $discover_url ); ?>"
         data-cutoff-days="<?php echo esc_attr( $cutoff_days ); ?>"
         data-exclude-cat="<?php echo esc_attr( $image_cat_id ); ?>">

	<div class="text-search__inner">
		<div class="text-search__content">
			<?php if ( $title ) : ?>
				<h2 class="text-search__title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<?php if ( $intro ) : ?>
				<p class="text-search__intro"><?php echo esc_html( $intro ); ?></p>
			<?php endif; ?>
		</div>

		<form class="text-search__form" action="" method="get" novalidate>
			<label class="screen-reader-text" for="<?php echo esc_attr( $instance_id ); ?>_input">
				<?php esc_html_e( 'Search community posts', 'child' ); ?>
			</label>
			<input id="<?php echo esc_attr( $instance_id ); ?>_input"
			       class="text-search__input"
			       type="search"
			       name="q"
			       placeholder="<?php esc_attr_e( 'Search posts…', 'child' ); ?>"
			       autocomplete="off">
			<button class="text-search__button" type="submit">
				<?php echo esc_html( $btn_label ); ?>
			</button>
			<span class="text-search__status" role="status" aria-live="polite"></span>
		</form>
	</div>
</section>

<script>
(function() {
  const root = document.getElementById('<?php echo esc_js( $instance_id ); ?>');
  if (!root) return;

  const form = root.querySelector('.text-search__form');
  const input = root.querySelector('.text-search__input');
  const status = root.querySelector('.text-search__status');

  const restUrl = root.dataset.restUrl || '';
  const publishUrl = root.dataset.publishUrl || '/community/publish-content/';
  const discoverUrl = root.dataset.discoverUrl || '/community/discover/';
  const cutoffDays = parseInt(root.dataset.cutoffDays || '7', 10) || 7;
  const excludeCat = parseInt(root.dataset.excludeCat || '0', 10) || 0;

  function setStatus(message) {
    if (!status) return;
    status.textContent = message;
  }

  function buildUrl(base, term) {
    const glue = base.indexOf('?') === -1 ? '?' : '&';
    return base + glue + 'q=' + encodeURIComponent(term);
  }

  async function hasPosts(params) {
    if (!restUrl) return false;
    const url = new URL(restUrl, window.location.origin);

    Object.keys(params).forEach(function(key) {
      if (params[key] !== null && params[key] !== '') {
        url.searchParams.set(key, params[key]);
      }
    });

    try {
      const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
      if (!res.ok) return false;
      const data = await res.json();
      return Array.isArray(data) && data.length > 0;
    } catch (err) {
      return false;
    }
  }

  if (!form || !input) return;

  form.addEventListener('submit', async function(e) {
    e.preventDefault();

    const term = (input.value || '').trim();
    if (!term) {
      input.focus();
      return;
    }

    form.classList.add('is-loading');
    setStatus('Searching...');

    const cutoff = new Date();
    cutoff.setDate(cutoff.getDate() - cutoffDays);
    const cutoffIso = cutoff.toISOString();

    const baseParams = {
      search: term,
      per_page: 1,
      status: 'publish'
    };

    if (excludeCat) {
      baseParams.categories_exclude = String(excludeCat);
    }

    const hasNew = await hasPosts(Object.assign({}, baseParams, { after: cutoffIso }));
    if (hasNew) {
      window.location.href = buildUrl(publishUrl, term);
      return;
    }

    const hasOld = await hasPosts(Object.assign({}, baseParams, { before: cutoffIso }));
    if (hasOld) {
      window.location.href = buildUrl(discoverUrl, term);
      return;
    }

    window.location.href = buildUrl(discoverUrl, term);
  });
})();
</script>
