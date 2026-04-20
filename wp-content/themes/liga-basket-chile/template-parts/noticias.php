<?php
/**
 * Bloque de noticias.
 *
 * @package LigaBasketChile
 */

$is_home_block = ! empty( $args['home'] );
$news_limit    = absint( liga_get_option( 'news_count', 6 ) );
if ( $news_limit <= 0 ) {
	$news_limit = 6;
}

if ( $is_home_block ) {
	$news_query = new WP_Query(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $news_limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
} else {
	$news_query = $GLOBALS['wp_query'];
}
?>
<section class="liga-section liga-news" data-liga-reveal>
	<div class="liga-container">
		<header class="liga-block-head">
			<h2 class="liga-block-title"><?php esc_html_e( 'Noticias', 'liga-basket-chile' ); ?></h2>
			<a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/noticias' ) ); ?>" class="liga-link-more"><?php esc_html_e( 'Ver todas', 'liga-basket-chile' ); ?></a>
		</header>
		<?php if ( $news_query->have_posts() ) : ?>
			<div class="liga-news-grid">
				<?php while ( $news_query->have_posts() ) : ?>
					<?php $news_query->the_post(); ?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'liga-news-card' ); ?>>
						<a href="<?php the_permalink(); ?>" class="liga-news-image-wrap">
							<?php if ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( 'large', array( 'class' => 'liga-news-image', 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
							<?php else : ?>
								<span class="liga-news-image liga-news-image--fallback" aria-hidden="true"></span>
							<?php endif; ?>
						</a>
						<div class="liga-news-body">
							<?php
							$category_names = wp_get_post_terms( get_the_ID(), 'category', array( 'fields' => 'names' ) );
							$category_label = ! empty( $category_names ) ? $category_names[0] : __( 'Noticia', 'liga-basket-chile' );
							?>
							<span class="liga-badge liga-badge--soft"><?php echo esc_html( $category_label ); ?></span>
							<h3 class="liga-news-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<p class="liga-news-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" class="liga-news-date"><?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?></time>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'No hay noticias publicadas.', 'liga-basket-chile' ); ?></p>
		<?php endif; ?>

		<?php if ( ! $is_home_block ) : ?>
			<?php the_posts_pagination(); ?>
		<?php endif; ?>
	</div>
</section>
<?php
wp_reset_postdata();
