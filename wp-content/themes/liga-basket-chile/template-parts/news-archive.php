<?php
/**
 * Archivo editorial de noticias.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$queried_object_id = (int) get_queried_object_id();
$current_page      = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$posts_per_page    = (int) get_option( 'posts_per_page', 10 );
$news_post_types   = array( 'post' );

if ( post_type_exists( 'noticia' ) ) {
	$news_post_types[] = 'noticia';
}

if ( $posts_per_page <= 0 ) {
	$posts_per_page = 10;
}

$news_query        = new WP_Query(
	array(
		'post_type'           => array_values( array_unique( $news_post_types ) ),
		'post_status'         => 'publish',
		'posts_per_page'      => $posts_per_page,
		'paged'               => $current_page,
		'ignore_sticky_posts' => true,
		'orderby'             => 'date',
		'order'               => 'DESC',
	)
);

$has_news_posts = $news_query->have_posts();

$archive_title = post_type_archive_title( '', false );
if ( '' === $archive_title && $queried_object_id > 0 ) {
	$archive_title = get_the_title( $queried_object_id );
}
if ( '' === $archive_title ) {
	$posts_page_id = (int) get_option( 'page_for_posts' );
	$archive_title = $posts_page_id > 0 ? get_the_title( $posts_page_id ) : '';
}
if ( '' === $archive_title ) {
	$archive_title = __( 'Actualidad de la Liga', 'liga-basket-chile' );
}

$matches_archive = get_post_type_archive_link( 'partido' );
if ( ! $matches_archive ) {
	$matches_archive = home_url( '/partidos' );
}

$total_pages  = max( 1, (int) $news_query->max_num_pages );

$get_category_label = static function ( $post_id ) {
	if ( taxonomy_exists( 'categoria_noticia_liga' ) ) {
		$custom_terms = wp_get_post_terms( $post_id, 'categoria_noticia_liga', array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $custom_terms ) && ! empty( $custom_terms ) ) {
			return $custom_terms[0];
		}
	}

	$wp_categories = get_the_category( $post_id );
	if ( ! empty( $wp_categories ) ) {
		return $wp_categories[0]->name;
	}

	$category_terms = wp_get_post_terms( $post_id, 'category', array( 'fields' => 'names' ) );
	if ( ! is_wp_error( $category_terms ) && ! empty( $category_terms ) ) {
		return $category_terms[0];
	}

	return __( 'Noticia', 'liga-basket-chile' );
};

?>
<main class="liga-news-archive">
	<section class="liga-news-archive__hero" aria-labelledby="liga-news-archive-title">
		<div class="liga-container">
			<nav class="liga-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'liga-basket-chile' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Inicio', 'liga-basket-chile' ); ?></a>
				<span aria-hidden="true">/</span>
				<span><?php echo esc_html( $archive_title ); ?></span>
			</nav>
			<p class="liga-section-kicker"><?php esc_html_e( 'Noticias', 'liga-basket-chile' ); ?></p>
			<h1 id="liga-news-archive-title" class="liga-news-archive__title"><?php esc_html_e( 'Actualidad de la Liga', 'liga-basket-chile' ); ?></h1>
			<p class="liga-news-archive__subtitle"><?php esc_html_e( 'Resultados, historias, programacion y novedades de la Liga de Basquetbol Concepcion.', 'liga-basket-chile' ); ?></p>
		</div>
	</section>

	<section class="liga-news-archive__body">
		<div class="liga-container liga-news-archive__layout">
			<div class="liga-news-archive__main">
				<?php if ( $has_news_posts ) : ?>
					<?php
					$news_query->the_post();
					$featured_category = $get_category_label( get_the_ID() );
					$featured_author   = trim( (string) get_the_author() );
					?>
					<section class="liga-news-featured" aria-labelledby="liga-news-featured-title">
						<article id="post-<?php the_ID(); ?>" <?php post_class( 'liga-news-featured__article' ); ?>>
							<a href="<?php the_permalink(); ?>" class="liga-news-featured__media-link" aria-hidden="true" tabindex="-1">
								<figure class="liga-news-featured__media">
									<?php if ( has_post_thumbnail() ) : ?>
										<?php the_post_thumbnail( 'large', array( 'class' => 'liga-news-featured__image', 'decoding' => 'async' ) ); ?>
									<?php else : ?>
										<img class="liga-news-featured__image" src="<?php echo liga_escape_image_src( liga_svg_placeholder( get_the_title(), 1200, 675, '0b2a66', 'ffffff' ) ); ?>" alt="<?php echo esc_attr( wp_strip_all_tags( get_the_title() ) ); ?>">
									<?php endif; ?>
								</figure>
							</a>
							<div class="liga-news-featured__content">
								<div class="liga-news-featured__meta" aria-label="<?php esc_attr_e( 'Metadatos de noticia', 'liga-basket-chile' ); ?>">
									<span class="liga-news-featured__category"><?php echo esc_html( $featured_category ); ?></span>
									<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'd M Y' ) ); ?></time>
									<?php if ( '' !== $featured_author ) : ?>
										<span><?php echo esc_html( sprintf( __( 'Por %s', 'liga-basket-chile' ), $featured_author ) ); ?></span>
									<?php endif; ?>
								</div>
								<h2 id="liga-news-featured-title" class="liga-news-featured__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
								<p class="liga-news-featured__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 38, '...' ) ); ?></p>
								<a class="liga-news-featured__cta" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Leer noticia', 'liga-basket-chile' ); ?></a>
							</div>
						</article>
					</section>

					<section class="liga-news-grid" aria-label="<?php esc_attr_e( 'Listado de noticias', 'liga-basket-chile' ); ?>">
						<?php if ( $news_query->have_posts() ) : ?>
							<?php while ( $news_query->have_posts() ) : ?>
								<?php
								$news_query->the_post();
								$card_category = $get_category_label( get_the_ID() );
								?>
								<article id="post-<?php the_ID(); ?>" <?php post_class( 'liga-news-card' ); ?>>
									<a href="<?php the_permalink(); ?>" class="liga-news-card__media-link" aria-hidden="true" tabindex="-1">
										<figure class="liga-news-card__media">
											<?php if ( has_post_thumbnail() ) : ?>
												<?php the_post_thumbnail( 'large', array( 'class' => 'liga-news-card__image', 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
											<?php else : ?>
												<img class="liga-news-card__image" src="<?php echo liga_escape_image_src( liga_svg_placeholder( get_the_title(), 1200, 675, '0b2a66', 'ffffff' ) ); ?>" alt="<?php echo esc_attr( wp_strip_all_tags( get_the_title() ) ); ?>" loading="lazy" decoding="async">
											<?php endif; ?>
										</figure>
									</a>
									<div class="liga-news-card__content">
										<div class="liga-news-card__meta">
											<span class="liga-news-card__category"><?php echo esc_html( $card_category ); ?></span>
											<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'd M Y' ) ); ?></time>
										</div>
										<h3 class="liga-news-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
										<p class="liga-news-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 21, '...' ) ); ?></p>
										<a class="liga-news-card__link" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Leer noticia', 'liga-basket-chile' ); ?></a>
									</div>
								</article>
							<?php endwhile; ?>
						<?php else : ?>
							<p class="liga-news-archive__empty"><?php esc_html_e( 'Aun no hay mas noticias para mostrar.', 'liga-basket-chile' ); ?></p>
						<?php endif; ?>
					</section>
				<?php else : ?>
					<p class="liga-news-archive__empty"><?php esc_html_e( 'No hay noticias publicadas por el momento.', 'liga-basket-chile' ); ?></p>
				<?php endif; ?>

				<?php if ( $total_pages > 1 ) : ?>
					<nav class="liga-pagination" aria-label="<?php esc_attr_e( 'Paginacion de noticias', 'liga-basket-chile' ); ?>">
						<?php
							echo wp_kses_post(
								paginate_links(
									array(
										'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
										'current'   => $current_page,
										'total'     => $total_pages,
										'mid_size'  => 1,
										'prev_text' => __( 'Anterior', 'liga-basket-chile' ),
										'next_text' => __( 'Siguiente', 'liga-basket-chile' ),
										'type'      => 'list',
									)
								)
							);
						?>
					</nav>
				<?php endif; ?>
			</div>

			<aside class="liga-news-sidebar" aria-label="<?php esc_attr_e( 'Sidebar de noticias', 'liga-basket-chile' ); ?>">
				<?php
				get_template_part(
					'template-parts/fixture',
					null,
					array(
						'posts_per_page' => 4,
						'title'          => __( 'Proximos partidos', 'liga-basket-chile' ),
						'show_link'      => true,
						'link_label'     => __( 'Ver fixture', 'liga-basket-chile' ),
						'link_url'       => add_query_arg( 'estado', 'programado', $matches_archive ),
					)
				);
				?>

				<?php
				get_template_part(
					'template-parts/resultados',
					null,
					array(
						'posts_per_page' => 4,
						'title'          => __( 'Ultimos resultados', 'liga-basket-chile' ),
					)
				);
				?>
			</aside>
		</div>
	</section>
</main>
<?php wp_reset_postdata(); ?>
