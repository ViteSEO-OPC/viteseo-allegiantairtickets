<?php
/*
Template Name: Community Submit Post KO
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
	if (function_exists('do_blocks')) {
		echo do_blocks('<!-- wp:template-part {"slug":"header-community","tagName":"header"} /-->');
	}

	$submit_copy = [
		'image' => [
			'h2' => '새 이미지 게시물 등록',
			'p' => '좋아하는 여행 사진을 업로드하고 당신의 시선으로 여행의 순간을 공유해 보세요. 문화, 풍경, 음식 어떤 주제든 한 장의 사진이 누군가의 다음 여행에 큰 영감을 줄 수 있습니다.',
		],
		'blog' => [
			'h2' => '새 게시물 등록',
			'p' => '다른 여행자에게 도움이 될 팁, 가이드, 경험이 있나요? 게시물을 등록해 함께 더 똑똑한 여행을 만들어 보세요. 숨은 명소, 여행 해킹, 문화 인사이트 등 당신의 이야기가 누군가의 다음 모험을 바꿀 수 있습니다.',
		],
	];

	$submit_error = isset($_GET['submit-error']) ? sanitize_text_field($_GET['submit-error']) : '';
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

						<div class="mb-4 d-inline-flex submit-type-toggle">
							<button type="button" id="toggle_image_post"
								class="submit-type-toggle__btn <?php echo ($default_variant === 'image') ? 'submit-type-toggle__btn--active' : ''; ?>">
								이미지 게시물
							</button>
							<button type="button" id="toggle_blog_post"
								class="submit-type-toggle__btn <?php echo ($default_variant === 'blog') ? 'submit-type-toggle__btn--active' : ''; ?>">
								블로그 게시물
							</button>
						</div>

						<?php if ($submit_error): ?>
							<?php
							$message = '게시물 등록 중 오류가 발생했습니다. 입력 내용을 확인해 주세요.';

							if ($submit_error === 'missing_image') {
								$message = '이미지 게시물은 대표 이미지를 업로드해야 합니다.';
							} elseif ($submit_error === 'missing_fields') {
								$message = '블로그 게시물은 제목과 본문이 필요합니다.';
							} elseif ($submit_error === 'insert_failed') {
								$message = '저장 중 문제가 발생했습니다. 다시 시도해 주세요.';
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

							<?php wp_nonce_field('community_submit_post', 'community_post_nonce'); ?>

							<div class="mb-3">
								<label class="form-label" for="featured_image">대표 이미지</label>
								<input type="file" class="form-control" name="featured_image" id="featured_image"
									accept="image/*">
							</div>

							<div class="mb-3">
								<label class="form-label" for="post_title">
									제목 <span class="small text-muted">(블로그 게시물은 필수)</span>
								</label>
								<input id="post_title" class="form-control" name="post_title"
									placeholder="게시물 제목을 입력하세요">
							</div>

							<div class="mb-3" id="content_group">
								<label class="form-label" id="content_label">본문</label>
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

							<div class="mb-3" id="category_group">
								<label class="form-label">카테고리</label>
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
									'show_option_all' => '카테고리 선택',
									'exclude' => $exclude_cats,
								]);
								?>
							</div>

							<div class="mb-3">
								<label class="form-label" for="post_tags">태그 (쉼표로 구분)</label>
								<input type="text" class="form-control" id="post_tags" name="post_tags"
									placeholder="예: beaches, adventure, food">
							</div>

							<div class="mb-3">
								<label class="form-label" for="post_excerpt">요약</label>
								<textarea class="form-control" id="post_excerpt" name="post_excerpt" rows="3"
									placeholder="짧은 요약을 입력하세요..."></textarea>
							</div>

							<div class="d-flex gap-3 mt-4">
								<button type="submit" class="btn btn-draft"
									onclick="document.getElementById('post_action').value='draft'">
									임시 저장
								</button>

								<button type="submit" class="btn btn-submit"
									onclick="document.getElementById('post_action').value='submit'">
									게시물 등록
								</button>
							</div>

						</form>

					</div>

				</div>
			</div>
		</div>
	</main>

	<?php
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

				if (contentGroup) {
					contentGroup.style.display = '';
					const label = document.getElementById('content_label');
					if (label) {
						label.textContent = (variant === 'image') ? '설명' : '본문';
					}
				}

				if (categoryGroup) categoryGroup.style.display = (variant === 'blog') ? '' : 'none';
			}

			btnImage.addEventListener('click', () => setVariant('image'));
			btnBlog.addEventListener('click', () => setVariant('blog'));

			setVariant(variantInput.value || 'image');

			form.addEventListener('submit', function (e) {
				const variant = variantInput.value || 'image';

				if (variant === 'image') {
					const file = document.getElementById('featured_image')?.files?.[0];
					if (!file) {
						e.preventDefault();
						alert('이미지 게시물은 대표 이미지를 업로드해 주세요.');
						document.getElementById('featured_image')?.focus();
					}
					return;
				}

				if (window.tinymce) {
					tinymce.triggerSave();
				}

				const title = (document.getElementById('post_title')?.value || '').trim();
				if (!title) {
					e.preventDefault();
					alert('블로그 게시물은 제목이 필요합니다.');
					document.getElementById('post_title')?.focus();
					return;
				}

				const editor = window.tinymce ? tinymce.get('post_content') : null;
				const text = editor
					? editor.getContent({ format: 'text' }).trim()
					: (document.getElementById('post_content')?.value || '').trim();

				if (!text) {
					e.preventDefault();
					alert('블로그 게시물은 본문이 필요합니다.');
					if (editor) editor.focus();
					return;
				}
			});
		});
	</script>

	<?php wp_footer(); ?>
</body>

</html>
