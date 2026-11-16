<?php 
if ( ! empty( $settings['btn_link']['url'] ) ) {
    $this->add_link_attributes( 'btn_link', $settings['btn_link'] );
}
?>
<header class="transparent-header">
    <div id="header-fixed-height"></div>
    <div id="sticky-header" class="tg-header__area tg-header__area-four tg-header__area-five">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="tgmenu__wrap">
                        <nav class="tgmenu__nav">
                            <div class="logo">
                                <a href="<?php echo esc_url(home_url());?>" aria-label="name">
                                    <img class="logo_site-size" src="<?php echo esc_url($settings['rzlogo']['url']);?>" alt="<?php if(!empty($settings['rzlogo']['alt'])){ echo esc_attr($settings['rzlogo']['alt']);}?>">
                                </a>
                            </div>
                            <div class="tgmenu__navbar-wrap tgmenu__main-menu d-none d-lg-flex">
                                <?php
                                    wp_nav_menu( array(
                                        'menu' => !empty($settings['choose-menu']) ? $settings['choose-menu'] : 'menu-1',
                                        'menu_id'        =>'navigation',
                                        'menu_class'        =>'navigation',
                                        'container'=>false,
                                        'fallback_cb'    => 'Navwalker_Class::fallback',
                                        'walker'         => class_exists( 'Rs_Mega_Menu_Walker' ) ? new \Rs_Mega_Menu_Walker : '',
                                    ));
                                ?>
                            </div>
                            <div class="tgmenu__action tgmenu__action-three">
                                    <ul class="list-wrap">
                                    <?php if($settings['help_enable'] === 'yes'):?>
                                        <li class="header-dropdown-wrap dropdown">
                                            <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><?php echo xhosting_wp_kses($settings['help_text']);?>
                                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <g clip-path="url(#clip0_464_4901)">
                                                        <path d="M4 7L8 11L12 7" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </g>
                                                    <defs>
                                                        <clipPath id="clip0_464_4901">
                                                            <rect width="16" height="16" fill="white" />
                                                        </clipPath>
                                                    </defs>
                                                </svg>
                                            </button>
                                            <div class="dropdown-menu">
                                                <?php if(!empty($settings['hp_title'])):?>
                                                    <h3 class="title"><?php echo xhosting_wp_kses($settings['hp_title']);?></h3>
                                                <?php endif;?>
                                                <?php if(!empty($settings['hp_desc'])):?>
                                                    <p><?php echo xhosting_wp_kses($settings['hp_desc']);?></p>
                                                <?php endif;?>
                                                <?php if(!empty($settings['phone']) || !empty($settings['email'])):?>
                                                    <div class="header-dropdown-questions">
                                                        <?php if(!empty($settings['ct_title'])):?>
                                                            <span><?php echo xhosting_wp_kses($settings['ct_title']);?></span>
                                                        <?php endif;?>
                                                        <div class="header-dropdown-questions-inner">
                                                            <a href="tel:<?php echo xhosting_wp_kses($settings['phone']);?>" class="phone"><?php echo xhosting_wp_kses($settings['phone']);?></a>
                                                            <a href="mailto:<?php echo xhosting_wp_kses($settings['email']);?>" class="mail"><?php echo xhosting_wp_kses($settings['email']);?></a>
                                                        </div>
                                                    </div>
                                                <?php endif;?>
                                                <?php if(!empty($settings['hp_btn_label'])):?>
                                                    <a <?php echo $this->get_render_attribute_string( 'top_btn_link' ); ?> class="tg-btn tg-btn-two">
                                                        <?php if($settings['hp_btn_icon'] === 'yes'):?>
                                                        <?php \Elementor\Icons_Manager::render_icon( $settings['hp_bt_icon'], [ 'aria-hidden' => 'true' ] ); ?>
                                                        <?php endif;?>
                                                        <?php echo xhosting_wp_kses($settings['hp_btn_label']);?>
                                                    </a>
                                                <?php endif;?>
                                                <?php if(!empty($settings['hp_contact_title']) || !empty($settings['hp_ct_btn'])):?>
                                                    <div class="header-dropdown-contact">
                                                        <?php if(!empty($settings['hp_contact_title'])):?>
                                                        <span class="title"><?php echo xhosting_wp_kses($settings['hp_contact_title']);?></span>
                                                        <?php endif;?>
                                                        <?php if(!empty($settings['hp_ct_btn'])):?>
                                                            <a <?php echo $this->get_render_attribute_string( 'hp_ct_link' ); ?>><?php echo xhosting_wp_kses($settings['hp_ct_btn']);?>
                                                                <svg width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M12.75 5.75L5.25 13.25" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                                                    <path d="M6 5.75H12.75V12.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                                                </svg>
                                                            </a>
                                                        <?php endif;?>
                                                    </div>
                                                <?php endif;?>
                                                <?php if(!empty($settings['socials'])):?>
                                                    <div class="header-dropdown-social">
                                                        <?php if(!empty($settings['social_title'])):?>
                                                            <span class="title"><?php echo xhosting_wp_kses($settings['social_title']);?></span>
                                                        <?php endif;?>
                                                        <ul class="list-wrap">
                                                            <?php foreach($settings['socials'] as $item):?>
                                                                <li>
                                                                    <a target="<?php echo esc_attr( $item['link']['is_external'] ? '_blank' : '_self' ); ?>" rel="<?php echo esc_attr( $item['link']['nofollow'] ? 'nofollow' : '' ); ?>" href="<?php echo $item['link']['url'] ? esc_url($item['link']['url']) : ''; ?>">
                                                                        <?php \Elementor\Icons_Manager::render_icon( $item['icon'], [ 'aria-hidden' => 'true' ] ); ?>
                                                                    </a>
                                                                </li>
                                                            <?php endforeach;?>
                                                        </ul>
                                                    </div>
                                                <?php endif;?>
                                            </div>
                                        </li>
                                    <?php endif;?>
                                    <?php if(!empty($settings['btn_label'])):?>
                                        <li class="header-btn">
                                            <a <?php echo $this->get_render_attribute_string( 'btn_link' ); ?> class="tg-btn">
                                                <?php if($settings['btn_icon'] === 'yes'):?>
                                                <?php \Elementor\Icons_Manager::render_icon( $settings['bt_icon'], [ 'aria-hidden' => 'true' ] ); ?>
                                                <?php endif;?>
                                                <?php echo xhosting_wp_kses($settings['btn_label']); ?>
                                            </a>
                                        </li>
                                    <?php endif;?>
                                </ul>
                                </div>
                            <div class="mobile-nav-toggler"><i class="tg-flaticon-menu-1"></i></div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

         <!-- Mobile Menu  -->
    <div class="tgmobile__menu">
        <nav class="tgmobile__menu-box">
            <div class="close-btn"><i class="tg-flaticon-close-1"></i></div>
            <div class="nav-logo">
               <a href="<?php echo esc_url(home_url('/')); ?>">
                <img 
                    class="logo_site-m-size" 
                    src="<?php echo esc_url($settings['rzmlogo']['url']); ?>" 
                    alt="<?php echo esc_attr(!empty($settings['rzmlogo']['alt']) ? $settings['rzmlogo']['alt'] : get_bloginfo('name')); ?>"
                >
                </a>

            </div>
            <?php if($settings['mobile_search_enable'] === 'yes'):?>
                <div class="tgmobile__search">
                    <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                        <input type="text" name="s" placeholder="Search here..." value="<?php echo get_search_query(); ?>">
                        <button aria-label="Search" type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            <?php endif;?>

            <div class="tgmobile__menu-outer">
                <!--Here Menu Will Come Automatically Via Javascript / Same Menu as in Header-->
            </div>
            <?php if(!empty($settings['msocials'])):?>
                <div class="social-links">
                    <ul class="list-wrap">

                        <?php foreach($settings['msocials'] as $item):?>
                            <li>
                                <a target="<?php echo esc_attr( $item['link']['is_external'] ? '_blank' : '_self' ); ?>" rel="<?php echo esc_attr( $item['link']['nofollow'] ? 'nofollow' : '' ); ?>" href="<?php echo $item['link']['url'] ? esc_url($item['link']['url']) : ''; ?>">
                                    <?php \Elementor\Icons_Manager::render_icon( $item['icon'], [ 'aria-hidden' => 'true' ] ); ?>
                                </a>
                            </li>
                        <?php endforeach;?>

                    </ul>
                </div>
            <?php endif;?>
        </nav>
    </div>
    <div class="tgmobile__menu-backdrop"></div>
    <!-- End Mobile Menu -->

</header>