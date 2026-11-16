<?php 
    $this->add_render_attribute( 'title', 'class', 'elementor-gt-heading title' );
?>
<section class="data__center-area<?php if($settings['style'] == 2){ echo esc_attr('-two'); }?> section-py-120">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section__title white-title text-center mb-40">
                    <?php 
                        printf('<%1$s %2$s>%3$s</%1$s>',
                            tag_escape($settings['title_tag']),
                            $this->get_render_attribute_string('title'),
                            nl2br(xhosting_wp_kses($settings['title']))
                        ); 
                    ?>
                    <?php if(!empty($settings['description'])){
                        echo xhosting_wp_kses(wpautop($settings['description']));
                    }?>
                </div>
            </div>
        </div>
        <div class="data__center-inner-wrap">
            <div class="data__center-tab <?php if($settings['style'] == 2){ echo esc_attr('data__center-tab-two'); }?>">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <?php
                        foreach( $settings['tabs'] as $id => $item ) :

                        $active = ($id == 1) ? 'active' : '';
                        $aria_selected = ($id == 0) ? 'true' : 'false';

                    ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo esc_attr($active); ?>" id="itemOne-tab<?php echo esc_attr($id); ?>" data-bs-toggle="tab" data-bs-target="#itemOne-tab-pane<?php echo esc_attr($id); ?>" type="button" role="tab" aria-controls="itemOne-tab-pane<?php echo esc_attr($id); ?>" aria-selected="true"><?php echo xhosting_wp_kses($item['title']);?></button>
                        </li>
                    <?php endforeach;?>
                </ul>
            </div>
            <div class="data__center-map-wrap <?php if($settings['style'] == 2){ echo esc_attr('data__center-map-wrap-two'); }?>">
                <div class="tab-content" id="myTabContent">
                    <?php
                        foreach( $settings['tabs'] as $id => $item ) :
                        $active = ($id == 1) ? 'show active' : '';
                    ?>
                        <div class="tab-pane fade <?php echo esc_attr($active); ?>" id="itemOne-tab-pane<?php echo esc_attr($id); ?>" role="tabpanel" aria-labelledby="itemOne-tab<?php echo esc_attr($id); ?>" tabindex="0">
                            <div class="data__center-map-item">
                                <?php if(!empty($settings['map_img']['url'])):?>
                                    <img src="<?php echo esc_url($settings['map_img']['url']); ?>" alt="<?php if(!empty($settings['map_img']['alt'])){ echo esc_attr($settings['map_img']['alt']);}?>">
                                <?php endif;?>
                                <?php if(!empty($item['countries'])):?>
                                    <ul class="list-wrap">
                                        <?php foreach($item['countries'] as $ct):?>
                                            <li class="tooltip-item <?php if($ct['active'] === 'yes'):?> active <?php endif;?>">
                                                <div class="tooltip-btn"></div>
                                                <div class="tooltip-location">
                                                    <div class="icon">
                                                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M6.75 8.25C6.75 8.84674 6.98705 9.41903 7.40901 9.84099C7.83097 10.2629 8.40326 10.5 9 10.5C9.59674 10.5 10.169 10.2629 10.591 9.84099C11.0129 9.41903 11.25 8.84674 11.25 8.25C11.25 7.65326 11.0129 7.08097 10.591 6.65901C10.169 6.23705 9.59674 6 9 6C8.40326 6 7.83097 6.23705 7.40901 6.65901C6.98705 7.08097 6.75 7.65326 6.75 8.25Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                            <path d="M13.2428 12.4925L10.0605 15.6748C9.77924 15.9558 9.39794 16.1136 9.00038 16.1136C8.60282 16.1136 8.22151 15.9558 7.94025 15.6748L4.75725 12.4925C3.91817 11.6534 3.34675 10.5843 3.11527 9.42043C2.88378 8.25655 3.00262 7.05017 3.45676 5.95383C3.91089 4.85749 4.67993 3.92044 5.66661 3.26116C6.6533 2.60189 7.81333 2.25 9 2.25C10.1867 2.25 11.3467 2.60189 12.3334 3.26116C13.3201 3.92044 14.0891 4.85749 14.5433 5.95383C14.9974 7.05017 15.1162 8.25655 14.8847 9.42043C14.6533 10.5843 14.0818 11.6534 13.2428 12.4925Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </div>
                                                    <span><?php echo xhosting_wp_kses($ct['countrie']);?></span>
                                                </div>
                                            </li>
                                        <?php endforeach;?>
                                    </ul>
                                <?php endif;?>
                                <?php if(!empty($item['btn_label'])):?>
                                    <div class="data__center-price text-center">
                                        <a target="<?php echo esc_attr( $item['btn_link']['is_external'] ? '_blank' : '_self' ); ?>" rel="<?php echo esc_attr( $item['btn_link']['nofollow'] ? 'nofollow' : '' ); ?>" href="<?php echo esc_url($item['btn_link']['url']);?>"><?php echo xhosting_wp_kses($item['btn_label'])?>
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M14.1654 5.83398L5.83203 14.1673" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M6.66797 5.83398H14.168V13.334" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </a>
                                    </div>
                                <?php endif;?>
                            </div>
                        </div>
                    <?php endforeach;?>
                </div>
            </div>
        </div>
    </div>
</section>