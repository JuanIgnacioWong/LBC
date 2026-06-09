<?php
/**
 * Plantilla para entradas individuales.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="liga-single-post">
	<div class="liga-container">
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : ?>
				<?php
				the_post();

				$current_post_id   = get_the_ID();
				$categories        = get_the_category();
				$category_label    = ! empty( $categories ) ? $categories[0]->name : __( 'Liga', 'liga-basket-chile' );
				$published_label   = get_the_date( 'd M Y' );
				$published_iso     = get_the_date( 'c' );
				$matches_archive   = get_post_type_archive_link( 'partido' );
				$matches_archive   = $matches_archive ? $matches_archive : home_url( '/partidos' );
				$fixture_link      = add_query_arg( 'estado', 'programado', $matches_archive );
				$latest_news_query = new WP_Query(
					array(
						'post_type'           => 'post',
						'post_status'         => 'publish',
						'posts_per_page'      => 5,
						'post__not_in'        => array( $current_post_id ),
						'ignore_sticky_posts' => true,
						'no_found_rows'       => true,
						'orderby'             => 'date',
						'order'               => 'DESC',
					)
				);
				?>
				<div class="liga-single-post__layout">
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'liga-card liga-single-post__content' ); ?>>
						<?php if ( has_post_thumbnail() ) : ?>
							<figure class="liga-single-post__featured-media">
								<?php the_post_thumbnail( 'full', array( 'class' => 'liga-single-post__featured-image', 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
							</figure>
						<?php endif; ?>

						<div class="liga-single-post__meta">
							<span class="liga-badge liga-single-post__category"><?php echo esc_html( $category_label ); ?></span>
							<time datetime="<?php echo esc_attr( $published_iso ); ?>" class="liga-single-post__date"><?php echo esc_html( $published_label ); ?></time>
						</div>

						<h1 class="liga-single-post__title"><?php the_title(); ?></h1>
						<div class="liga-single-post__entry"><?php the_content(); ?></div>

						<?php
						wp_link_pages(
							array(
								'before'      => '<nav class="liga-single-post__pagination" aria-label="' . esc_attr__( 'Paginacion de entrada', 'liga-basket-chile' ) . '"><span class="liga-single-post__pagination-label">' . esc_html__( 'Paginas:', 'liga-basket-chile' ) . '</span>',
								'after'       => '</nav>',
								'link_before' => '<span class="liga-single-post__page-link">',
								'link_after'  => '</span>',
							)
						);
						?>

						<?php
						$previous_post = get_previous_post();
						$next_post     = get_next_post();
						?>
						<?php if ( $previous_post || $next_post ) : ?>
							<nav class="liga-single-post__nav" aria-label="<?php esc_attr_e( 'Navegacion entre entradas', 'liga-basket-chile' ); ?>">
								<?php if ( $previous_post ) : ?>
									<a class="liga-single-post__nav-link liga-single-post__nav-link--prev" href="<?php echo esc_url( get_permalink( $previous_post ) ); ?>">
										<span class="liga-single-post__nav-label"><?php esc_html_e( 'Entrada anterior', 'liga-basket-chile' ); ?></span>
										<strong class="liga-single-post__nav-title"><?php echo esc_html( get_the_title( $previous_post ) ); ?></strong>
									</a>
								<?php endif; ?>
								<?php if ( $next_post ) : ?>
									<a class="liga-single-post__nav-link liga-single-post__nav-link--next" href="<?php echo esc_url( get_permalink( $next_post ) ); ?>">
										<span class="liga-single-post__nav-label"><?php esc_html_e( 'Siguiente entrada', 'liga-basket-chile' ); ?></span>
										<strong class="liga-single-post__nav-title"><?php echo esc_html( get_the_title( $next_post ) ); ?></strong>
									</a>
								<?php endif; ?>
							</nav>
						<?php endif; ?>
					</article>

					<aside class="liga-single-post__sidebar" aria-label="<?php esc_attr_e( 'Sidebar de entrada', 'liga-basket-chile' ); ?>">
						<?php
						get_template_part(
							'template-parts/resultados',
							null,
							array(
								'posts_per_page' => 4,
								'title'          => __( 'Ultimos partidos', 'liga-basket-chile' ),
							)
						);
						?>

						<?php
						get_template_part(
							'template-parts/fixture',
							null,
							array(
								'posts_per_page' => 4,
								'title'          => __( 'Partidos programados', 'liga-basket-chile' ),
								'show_link'      => true,
								'link_url'       => $fixture_link,
								'link_label'     => __( 'Ver fixture', 'liga-basket-chile' ),
							)
						);
						?>

						<section class="liga-card liga-single-post__widget liga-single-post__latest-news" aria-labelledby="liga-single-post-latest-news-title">
							<div class="liga-section-head">
								<h2 class="liga-section-title" id="liga-single-post-latest-news-title"><?php esc_html_e( 'Ultimas entradas', 'liga-basket-chile' ); ?></h2>
							</div>

							<div class="liga-single-post__latest-list">
								<?php if ( $latest_news_query->have_posts() ) : ?>
									<?php while ( $latest_news_query->have_posts() ) : ?>
										<?php
										$latest_news_query->the_post();
										$news_thumb_html = has_post_thumbnail()
											? get_the_post_thumbnail(
												get_the_ID(),
												'thumbnail',
												array(
													'class'    => 'liga-single-post__latest-thumb-image',
													'loading'  => 'lazy',
													'decoding' => 'async',
												)
											)
											: '';
										?>
										<article class="liga-single-post__latest-item">
											<a class="liga-single-post__latest-link" href="<?php the_permalink(); ?>">
												<figure class="liga-single-post__latest-thumb" aria-hidden="true">
													<?php if ( '' !== $news_thumb_html ) : ?>
														<?php echo $news_thumb_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
													<?php else : ?>
														<span class="liga-single-post__latest-thumb-fallback"></span>
													<?php endif; ?>
												</figure>
												<div class="liga-single-post__latest-content">
													<h3 class="liga-single-post__latest-title"><?php the_title(); ?></h3>
													<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" class="liga-single-post__latest-date"><?php echo esc_html( get_the_date( 'd M Y' ) ); ?></time>
												</div>
											</a>
										</article>
									<?php endwhile; ?>
								<?php else : ?>
									<p class="liga-single-post__latest-empty"><?php esc_html_e( 'No hay entradas recientes disponibles.', 'liga-basket-chile' ); ?></p>
								<?php endif; ?>
							</div>
						</section>
						<?php wp_reset_postdata(); ?>
					</aside>
				</div>
			<?php endwhile; ?>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
