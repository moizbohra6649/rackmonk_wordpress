<?php
/**
 * Template part for displaying single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package xhosting
 */
$categories = get_the_category();
?>
<div id="post-<?php the_ID(); ?>" <?php post_class(''); ?>>
    <div class="blog__details-inner">
        <?php if( has_post_thumbnail()):?>
            <div class="blog__details-thumb">
                <?php the_post_thumbnail('full');?>
            </div>
        <?php endif;?>
        <div class="blog__details-content">
            <div class="blog__details-content-top">
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
            <?php
            $categories = get_the_category();
            if ( ! empty( $categories ) ) :
                $first_category = $categories[0];
                ?>
                <a href="<?php echo esc_url( get_category_link( $first_category->term_id ) ); ?>" class="blog__post-tag blog__post-tag-two">
                    <?php echo esc_html( $first_category->name ); ?>
                </a>
            <?php endif; ?>
        </div>

        <?php
            the_content(
                sprintf(
                    wp_kses(
                        /* translators: %s: Name of current post. Only visible to screen readers */
                        __( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'hostinkar' ),
                        array(
                            'span' => array(
                                'class' => array(),
                            ),
                        )
                    ),
                    wp_kses_post( get_the_title() )
                )
            );

            wp_link_pages(
                array(
                    'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'hostinkar' ),
                    'after'  => '</div>',
                )
            );
        ?>
        <div class="blog__details-content-bottom">
            <!-- Post Tags -->
            <?php
            $post_tags = get_the_tags();
            if ( $post_tags ) :
                ?>
                <div class="blog__details-tag">
                    <ul class="list-wrap">
                        <?php foreach ( $post_tags as $tag ) : ?>
                            <li>
                                <a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>" class="blog__post-tag blog__post-tag-two">
                                    <?php echo esc_html( $tag->name ); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if(function_exists('xhosting_post_share')){
                echo xhosting_post_share();
            }?>
        </div>

        <div class="gt-client-review-form mt-40">
            <?php if (comments_open() || get_comments_number()):
                    comments_template();
                endif;?>
        </div>
        </div>
        
        
    </div>  
</div>
