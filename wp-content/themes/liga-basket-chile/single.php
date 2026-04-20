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
<section class="liga-section">
	<div class="liga-container">
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : ?>
				<?php the_post(); ?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'liga-article' ); ?>>
					<h1 class="liga-article__title"><?php the_title(); ?></h1>
					<div class="liga-article__content"><?php the_content(); ?></div>
				</article>
			<?php endwhile; ?>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
