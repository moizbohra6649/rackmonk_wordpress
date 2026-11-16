<div class="blog__post-item-wrap mb-40">
    <?php if ( $query->have_posts() ) : ?>
        <?php while ( $query->have_posts() ) : $query->the_post(); ?>
            <?php
                $post_id        = get_the_ID();
                $title          = get_the_title();
                $permalink      = get_permalink();
                $excerpt        = get_the_excerpt();
                $author_id      = get_the_author_meta('ID');
                $author_name    = get_the_author();
                $author_url     = get_author_posts_url($author_id);
                $author_avatar  = get_avatar_url($author_id, ['size' => 32]);
                $post_date      = get_the_date('d M, Y');
                $categories     = get_the_category();
                $first_cat_name = !empty($categories) ? $categories[0]->name : '';
                $first_cat_url  = !empty($categories) ? get_category_link($categories[0]->term_id) : '#';
                $thumbnail      = get_the_post_thumbnail_url($post_id, 'full') ?: 'https://via.placeholder.com/600x400?text=No+Image';
            ?>
            <div class="blog__post-item-three">
                <div class="blog__post-thumb-three">
                    <a href="<?php echo esc_url($permalink); ?>">
                        <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($title); ?>">
                    </a>
                </div>
                <div class="blog__post-content-three">
                    <?php if ( $first_cat_name ) : ?>
                        <a href="<?php echo esc_url($first_cat_url); ?>" class="blog__post-tag blog__post-tag-two">
                            <?php echo esc_html($first_cat_name); ?>
                        </a>
                    <?php endif; ?>
                    <h2 class="blog__post-title">
                        <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
                    </h2>
                    <div class="blog__post-meta">
                        <ul class="list-wrap">
                            <li class="blog__post-author">
                                <img src="<?php echo esc_url($author_avatar); ?>" alt="<?php echo esc_attr($author_name); ?>">
                                <a href="<?php echo esc_url($author_url); ?>"><?php echo esc_html($author_name); ?></a>
                            </li>
                            <li><?php echo esc_html($post_date); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
        <?php wp_reset_postdata(); ?>
    <?php else : ?>
        <div class="col-12">
            <p><?php esc_html_e('No posts found.', 'xhosting-plugin'); ?></p>
        </div>
    <?php endif; ?>
</div>
