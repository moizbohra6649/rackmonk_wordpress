<?php
/**
 * Template part for displaying posts dynamically
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package xhosting
 */
$categories = get_the_category();
$blog_btn_text = cs_get_option('blog_btn_text');
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('blog__post-item bytf__post-main'); ?>>

	<?php if ( has_post_thumbnail() ) : ?>
		<div class="blog__post-thumb">
			<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('full'); ?></a>

			<?php if ( ! empty( $categories ) ) : ?>
				<a href="<?php echo esc_url( get_category_link( $categories[0]->term_id ) ); ?>" class="blog__post-tag">
					<?php echo esc_html( $categories[0]->name ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="blog__post-content">
		<h2 class="blog__post-title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h2>

		<p><?php echo wp_trim_words( get_the_excerpt(), 30 ); ?></p>

		<div class="blog__post-meta">
			<ul class="list-wrap">
				<li class="blog__post-author">
					<?php echo get_avatar( get_the_author_meta( 'ID' ), 32 ); ?>
					<a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
						<?php the_author(); ?>
					</a>
				</li>
				<li><?php echo get_the_date(); ?></li>
			</ul>
		</div>
	</div>

</article><!-- #post-<?php the_ID(); ?> -->
