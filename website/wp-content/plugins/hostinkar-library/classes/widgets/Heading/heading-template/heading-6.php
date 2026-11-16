<?php 
    $this->add_render_attribute( 'title', 'class', 'elementor-gt-heading title' );
?>
<div class="row align-items-end">
    <div class="col-lg-7">
        <div class="section__title">
            <?php 
                printf('<%1$s %2$s>%3$s</%1$s>',
                    tag_escape($settings['title_tag']),
                    $this->get_render_attribute_string('title'),
                    nl2br(xhosting_wp_kses($settings['title']))
                ); 
            ?>
            <?php if(!empty($settings['description'])):?>
                <p class="xhosting-elementor-desc">
                    <?php echo xhosting_wp_kses($settings['description']);?>
                </p>
            <?php endif;?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="testimonial__nav-two mb-50">
            <button class="testimonial-button-prev">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.16797 10H15.8346" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M4.16797 10L7.5013 13.3333" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M4.16797 9.99984L7.5013 6.6665" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
            </button>
            <button class="testimonial-button-next">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.16797 10H15.8346" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M12.5 13.3333L15.8333 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M12.5 6.66669L15.8333 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
            </button>
        </div>
    </div>
</div>