<?php
/*
Template Name: Community Submit Post
*/
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>

	<style>
		.community-submit-wrapper {
			background: #ffffff;
			font-family: var(--wp--preset--font-family--outfit);
			font-size: var(--wp--preset--font-size--body);
			color: var(--wp--preset--color--ink, #333333);
		}

		.community-submit-wrapper,
		.community-submit-wrapper p,
		.community-submit-wrapper span,
		.community-submit-wrapper li,
		.community-submit-wrapper label,
		.community-submit-wrapper input,
		.community-submit-wrapper select,
		.community-submit-wrapper textarea,
		.community-submit-wrapper button,
		.community-submit-wrapper .submit-type-toggle__btn {
			font-size: var(--wp--preset--font-size--body);
			font-family: var(--wp--preset--font-family--outfit);
			line-height: 1.5;
		}

		.submit-card {
			border-radius: 24px;
			background: #ffffff;
			box-shadow: 0 12px 30px rgba(0, 0, 0, 0.06);
		}

		.submit-title {
			font-size: var(--wp--preset--font-size--h-2);
			font-weight: 700;
			color: #fd593c;
			margin: 0 0 0.35rem;
		}

		.submit-subtitle {
			font-size: var(--wp--preset--font-size--body);
			color: #777777;
			margin: 0 0 1rem;
		}

		label.form-label {
			font-weight: 600;
			color: #fd593c;
		}

		input.form-control,
		select.form-select,
		textarea.form-control {
			border-radius: 14px;
			border: 1px solid #ddd;
			padding: 0.65rem 0.85rem;
			font-size: var(--wp--preset--font-size--body);
			font-family: var(--wp--preset--font-family--outfit);
		}

		.btn-submit {
			background: #fd593c;
			border-radius: 50px;
			color: #fff;
			min-width: 140px;
			border: 2px solid #fd593c;
			font-weight: 600;
			font-size: var(--wp--preset--font-size--body);
			font-family: var(--wp--preset--font-family--outfit);
		}

		.btn-submit:hover {
			background: #FDCD3B;
			color: #fff;
			border: 2px solid #FDCD3B;
		}

		.btn-draft {
			background: #fff;
			border-radius: 50px;
			color: #fd593c;
			min-width: 140px;
			border: 1px solid #fd593c;
			font-weight: 600;
			font-size: var(--wp--preset--font-size--body);
			font-family: var(--wp--preset--font-family--outfit);
		}

		.btn-draft:hover {
			background: #fff;
			color: #fd593c;
			border: 2px solid #fd593c;
		}

		.submit-type-toggle {
			border-radius: 999px;
			background: #fff3b8;
			padding: 2px;
		}

		.submit-type-toggle__btn {
			border: none;
			background: transparent;
			padding: 0.25rem 0.9rem;
			border-radius: 999px;
			font-size: calc(var(--wp--preset--font-size--body) * 0.85);
			font-weight: 600;
			color: #fd593c;
			cursor: pointer;
		}

		.submit-type-toggle__btn--active {
			background: #ffd84a;
			color: #16324f;
		}
	</style>
</head>

<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>

	<?php
	// Header template part
	if (function_exists('do_blocks')) {
		echo do_blocks('<!-- wp:template-part {"slug":"header-community","tagName":"header"} /-->');
	}

	$submit_copy = [
		'image' => [
			'h2' => 'Submit New Image Post',
			'p' => 'Upload your favorite travel images and let others experience the journey through your perspective. Whether it’s culture, landscapes, or food, your photo could inspire someone’s next adventure, one meaningful frame at a time. Every image has a story.',
		],
		'blog' => [
			'h2' => 'Submit New Post',
			'p' => 'Have a tip, guide, or experience others should know about? Submit your post and help fellow travelers explore smarter. Whether it’s a hidden spot, travel hack, or cultural insight, your story could inspire the next adventure. All voices welcome just keep it real, helpful, and travel-focused.',
		],
	];

	$submit_error = isset($_GET['submit-error']) ? sanitize_text_field($_GET['submit-error']) : '';

	// If they got a "missing_fields" error, they probably tried a blog post — open the page in blog mode.
	$default_variant = ($submit_error === 'missing_fields') ? 'blog' : 'image';

	$h2 = $submit_copy[$default_variant]['h2'];
	$p = $submit_copy[$default_variant]['p'];
	?>

	<main class="community-submit-wrapper py-5">
		<div class="container">
			<div class="row justify-content-center">
				<div class="col-lg-10 col-xl-9">

					<div class="submit-card p-4 p-md-5">

						<h2 id="submit_h2" class="submit-title mb-3"><?php echo esc_html($h2); ?></h2>
						<p id="submit_p" class="submit-subtitle mb-4"><?php echo esc_html($p); ?></p>

						<!-- TOGGLE: IMAGE vs BLOG -->
						<div class="mb-4 d-inline-flex submit-type-toggle">
							<button type="button" id="toggle_image_post"
								class="submit-type-toggle__btn <?php echo ($default_variant === 'image') ? 'submit-type-toggle__btn--active' : ''; ?>">
								Image Post
							</button>
							<button type="button" id="toggle_blog_post"
								class="submit-type-toggle__btn <?php echo ($default_variant === 'blog') ? 'submit-type-toggle__btn--active' : ''; ?>">
								Blog Post
							</button>
						</div>

						<?php if ($submit_error): ?>
							<?php
							$message = 'There was an error submitting your post. Please check your inputs.';

							if ($submit_error === 'missing_image') {
								$message = 'For image posts, please upload a featured image.';
							} elseif ($submit_error === 'missing_fields') {
								$message = 'For blog posts, title and content are required.';
							} elseif ($submit_error === 'insert_failed') {
								$message = 'Something went wrong while saving your post. Please try again.';
							}
							?>
							<div class="alert alert-danger mb-4">
								<?php echo esc_html($message); ?>
							</div>
						<?php endif; ?>

						<form id="community-submit-form" method="post"
							action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data"
							novalidate>

							<input type="hidden" name="action" value="community_submit_post">
							<input type="hidden" name="post_action" id="post_action" value="submit">
							<input type="hidden" name="post_variant" id="post_variant"
								value="<?php echo esc_attr($default_variant); ?>">

							<?php
							// Generates a nonce field named "community_post_nonce" + a referer field.
							// Your PHP expects $_POST['community_post_nonce'].
							wp_nonce_field('community_submit_post', 'community_post_nonce');
							?>

							<!-- FEATURED IMAGE -->
							<div class="mb-3">
								<label class="form-label" for="featured_image">Featured Image</label>
								<input type="file" class="form-control" name="featured_image" id="featured_image"
									accept="image/*">
							</div>

							<!-- TITLE -->
							<div class="mb-3">
								<label class="form-label" for="post_title">
									Title <span class="small text-muted">(required for blog posts)</span>
								</label>
								<input id="post_title" class="form-control" name="post_title"
									placeholder="Enter your post title">
							</div>

							<!-- CONTENT / DESCRIPTION -->
							<div class="mb-3" id="content_group">
								<label class="form-label" id="content_label">Content</label>
								<?php
								wp_editor(
									'',
									'post_content',
									[
										'textarea_name' => 'post_content',
										'media_buttons' => true,
										'teeny' => false,
										'quicktags' => true,
									]
								);
								?>
							</div>

							<!-- CATEGORY -->
							<div class="mb-3" id="category_group">
								<label class="form-label">Category</label>
								<?php
								$exclude_cats = [];
								$img_cat_term = get_category_by_slug('imagepost');
								if ($img_cat_term) {
									$exclude_cats[] = $img_cat_term->term_id;
								}

								wp_dropdown_categories([
									'taxonomy' => 'category',
									'hide_empty' => false,
									'name' => 'post_category',
									'class' => 'form-select',
									'show_option_all' => 'Select a category',
									'exclude' => $exclude_cats,
								]);
								?>
							</div>

							<!-- TAGS -->
							<div class="mb-3">
								<label class="form-label" for="post_tags">Tags (comma separated)</label>
								<input type="text" class="form-control" id="post_tags" name="post_tags"
									placeholder="e.g. beaches, adventure, food">
							</div>

							<!-- EXCERPT -->
							<div class="mb-3">
								<label class="form-label" for="post_excerpt">Excerpt</label>
								<textarea class="form-control" id="post_excerpt" name="post_excerpt" rows="3"
									placeholder="Write a short summary..."></textarea>
							</div>

							<div class="d-flex gap-3 mt-4">
								<button type="submit" class="btn btn-draft"
									onclick="document.getElementById('post_action').value='draft'">
									Save as Draft
								</button>

								<button type="submit" class="btn btn-submit"
									onclick="document.getElementById('post_action').value='submit'">
									Submit Post
								</button>
							</div>

						</form>

					</div>

				</div>
			</div>
		</div>
	</main>

	<?php
	// Footer template part
	if (function_exists('block_template_part')) {
		block_template_part('footer');
	}
	?>

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const submitCopy = <?php echo wp_json_encode($submit_copy); ?>;

			const form = document.getElementById('community-submit-form');
			const h2El = document.getElementById('submit_h2');
			const pEl = document.getElementById('submit_p');
			const btnImage = document.getElementById('toggle_image_post');
			const btnBlog = document.getElementById('toggle_blog_post');
			const variantInput = document.getElementById('post_variant');

			const contentGroup = document.getElementById('content_group');
			const categoryGroup = document.getElementById('category_group');

			function setVariant(variant) {
				if (!submitCopy[variant]) return;

				variantInput.value = variant;

				btnImage.classList.toggle('submit-type-toggle__btn--active', variant === 'image');
				btnBlog.classList.toggle('submit-type-toggle__btn--active', variant === 'blog');

				h2El.textContent = submitCopy[variant].h2;
				pEl.textContent = submitCopy[variant].p;

				// Always show content, but change label
				if (contentGroup) {
					contentGroup.style.display = '';
					const label = document.getElementById('content_label');
					if (label) {
						label.textContent = (variant === 'image') ? 'Description' : 'Content';
					}
				}

				if (categoryGroup) categoryGroup.style.display = (variant === 'blog') ? '' : 'none';
			}

			btnImage.addEventListener('click', () => setVariant('image'));
			btnBlog.addEventListener('click', () => setVariant('blog'));

			// Init
			setVariant(variantInput.value || 'image');

			// Manual validation (novalidate is enabled to avoid the "post_content not focusable" issue with TinyMCE)
			form.addEventListener('submit', function (e) {
				const variant = variantInput.value || 'image';

				if (variant === 'image') {
					const file = document.getElementById('featured_image')?.files?.[0];
					if (!file) {
						e.preventDefault();
						alert('For image posts, please upload a featured image.');
						document.getElementById('featured_image')?.focus();
					}
					return;
				}

				// BLOG validation
				if (window.tinymce) {
					tinymce.triggerSave(); // ensure textarea gets updated for PHP
				}

				const title = (document.getElementById('post_title')?.value || '').trim();
				if (!title) {
					e.preventDefault();
					alert('Title is required for blog posts.');
					document.getElementById('post_title')?.focus();
					return;
				}

				const editor = window.tinymce ? tinymce.get('post_content') : null;
				const text = editor
					? editor.getContent({ format: 'text' }).trim()
					: (document.getElementById('post_content')?.value || '').trim();

				if (!text) {
					e.preventDefault();
					alert('Content is required for blog posts.');
					if (editor) editor.focus();
					return;
				}
			});
		});
	</script>

	<?php wp_footer(); ?>
</body>

</html>