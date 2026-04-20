<?php
/**
 * Fallback principal del tema.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="liga-section liga-section--default">
	<div class="liga-container">
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : ?>
				<?php the_post(); ?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'liga-article' ); ?>>
					<h1 class="liga-article__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
					<div class="liga-article__excerpt"><?php the_excerpt(); ?></div>
				</article>
			<?php endwhile; ?>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'No hay contenido disponible.', 'liga-basket-chile' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
