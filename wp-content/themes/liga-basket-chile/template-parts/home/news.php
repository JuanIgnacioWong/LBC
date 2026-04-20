<?php
/**
 * Home news section.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$news_query = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 3,
		'ignore_sticky_posts' => true,
	)
);
?>
<section class="liga-news" aria-labelledby="liga-news-title">
	<div class="liga-container">
		<div class="liga-section-head">
			<h2 class="liga-section-title" id="liga-news-title"><?php esc_html_e( 'Ultimas noticias', 'liga-basket-chile' ); ?></h2>
			<a class="liga-section-link" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/noticias' ) ); ?>"><?php esc_html_e( 'Ver noticias', 'liga-basket-chile' ); ?></a>
		</div>

		<div class="liga-grid liga-news-grid">
			<?php if ( $news_query->have_posts() ) : ?>
				<?php while ( $news_query->have_posts() ) : ?>
					<?php
					$news_query->the_post();
					$categories    = get_the_category();
					$category_name = ! empty( $categories ) ? $categories[0]->name : __( 'Liga', 'liga-basket-chile' );
					$image_src     = get_the_post_thumbnail_url( get_the_ID(), 'large' );
					if ( ! $image_src ) {
						$image_src = liga_svg_placeholder( get_the_title(), 1200, 675, '111827', 'f7931e' );
					}
					?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'liga-card liga-news-card' ); ?>>
						<a class="liga-news-card-link" href="<?php the_permalink(); ?>">
							<figure class="liga-news-card-media">
								<img class="liga-news-card-image" src="<?php echo liga_escape_image_src( $image_src ); ?>" alt="<?php echo esc_attr( wp_strip_all_tags( get_the_title() ) ); ?>">
							</figure>
							<div class="liga-news-card-content">
								<p class="liga-news-card-category"><?php echo esc_html( $category_name ); ?></p>
								<h3 class="liga-news-card-title"><?php the_title(); ?></h3>
								<p class="liga-news-card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18, '...' ) ); ?></p>
								<time class="liga-news-card-date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'd M Y' ) ); ?></time>
							</div>
						</a>
					</article>
				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>
			<?php else : ?>
				<?php
				$fallback_news = array(
					array(
						'category' => 'Primera Division',
						'title'    => 'Los Alamos se queda con el clasico penquista',
						'excerpt'  => 'Un gran marco de publico acompano la victoria del cuadro azul por 78-65.',
						'date'     => '2026-06-12',
					),
					array(
						'category' => 'La Liga',
						'title'    => 'Se sortea el fixture de la temporada 2025',
						'excerpt'  => 'Conoce como sera el calendario de partidos para este nuevo ano competitivo.',
						'date'     => '2026-06-10',
					),
					array(
						'category' => 'Formacion',
						'title'    => 'Convocatoria abierta: Seleccion U18 de Concepcion',
						'excerpt'  => 'Inscripciones abiertas hasta el 30 de junio para jovenes promesas de la ciudad.',
						'date'     => '2026-06-08',
					),
				);
				?>
				<?php foreach ( $fallback_news as $item ) : ?>
					<article class="liga-card liga-news-card">
						<a class="liga-news-card-link" href="<?php echo esc_url( home_url( '/noticias' ) ); ?>">
							<figure class="liga-news-card-media">
								<img class="liga-news-card-image" src="<?php echo liga_escape_image_src( liga_svg_placeholder( $item['category'], 1200, 675, '111827', 'f7931e' ) ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>">
							</figure>
							<div class="liga-news-card-content">
								<p class="liga-news-card-category"><?php echo esc_html( $item['category'] ); ?></p>
								<h3 class="liga-news-card-title"><?php echo esc_html( $item['title'] ); ?></h3>
								<p class="liga-news-card-excerpt"><?php echo esc_html( $item['excerpt'] ); ?></p>
								<time class="liga-news-card-date" datetime="<?php echo esc_attr( $item['date'] ); ?>"><?php echo esc_html( gmdate( 'd M Y', strtotime( $item['date'] ) ) ); ?></time>
							</div>
						</a>
					</article>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</section>
