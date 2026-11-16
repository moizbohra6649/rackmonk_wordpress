<?php 
    $this->add_render_attribute( 'title', 'class', 'elementor-gt-heading title' );

    $disable_ajax = $disable_ajax_search == 'yes' ? '' : '-off';
    $domain_suggestion = $domain_seggestion_on_off == 'yes' ? 'true' : 'false';
    if ( ! empty( $settings['btn_link']['url'] ) ) {
        $this->add_link_attributes( 'btn_link', $settings['btn_link'] );
    }
?>
<section class="banner__area-four">
    <div class="container custom-container">
        <div class="banner__bg" <?php if(!empty($settings['bg_img']['url'])):?> data-background="<?php echo esc_url($settings['bg_img']['url']);?>" <?php endif;?>>
            <div class="row justify-content-center">
                <div class="col-xl-9 col-lg-10">
                    <div class="banner__content-four">
                        <?php if(!empty($settings['sub_title'])):?>
                        <span class="sub-title"><svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.5 10L10.5 6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M6.83333 6.66667C7.01743 6.66667 7.16667 6.51743 7.16667 6.33333C7.16667 6.14924 7.01743 6 6.83333 6C6.64924 6 6.5 6.14924 6.5 6.33333C6.5 6.51743 6.64924 6.66667 6.83333 6.66667Z" fill="currentColor" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M10.1654 10C10.3495 10 10.4987 9.85077 10.4987 9.66668C10.4987 9.48258 10.3495 9.33334 10.1654 9.33334C9.98127 9.33334 9.83203 9.48258 9.83203 9.66668C9.83203 9.85077 9.98127 10 10.1654 10Z" fill="currentColor" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M3.83169 4.8C3.83169 4.41102 3.98621 4.03797 4.26126 3.76291C4.53632 3.48786 4.90937 3.33334 5.29835 3.33334H5.96502C6.35229 3.33311 6.72375 3.17974 6.99835 2.90667L7.46502 2.44C7.60132 2.30294 7.76337 2.19416 7.94185 2.11994C8.12033 2.04572 8.31172 2.00751 8.50502 2.00751C8.69832 2.00751 8.88971 2.04572 9.06819 2.11994C9.24667 2.19416 9.40872 2.30294 9.54502 2.44L10.0117 2.90667C10.2863 3.17974 10.6578 3.33311 11.045 3.33334H11.7117C12.1007 3.33334 12.4737 3.48786 12.7488 3.76291C13.0238 4.03797 13.1784 4.41102 13.1784 4.8V5.46667C13.1786 5.85393 13.332 6.2254 13.605 6.5L14.0717 6.96667C14.2088 7.10297 14.3175 7.26502 14.3917 7.4435C14.466 7.62198 14.5042 7.81337 14.5042 8.00667C14.5042 8.19997 14.466 8.39136 14.3917 8.56984C14.3175 8.74832 14.2088 8.91037 14.0717 9.04667L13.605 9.51334C13.332 9.78794 13.1786 10.1594 13.1784 10.5467V11.2133C13.1784 11.6023 13.0238 11.9754 12.7488 12.2504C12.4737 12.5255 12.1007 12.68 11.7117 12.68H11.045C10.6578 12.6802 10.2863 12.8336 10.0117 13.1067L9.54502 13.5733C9.40872 13.7104 9.24667 13.8192 9.06819 13.8934C8.88971 13.9676 8.69832 14.0058 8.50502 14.0058C8.31172 14.0058 8.12033 13.9676 7.94185 13.8934C7.76337 13.8192 7.60132 13.7104 7.46502 13.5733L6.99835 13.1067C6.72375 12.8336 6.35229 12.6802 5.96502 12.68H5.29835C4.90937 12.68 4.53632 12.5255 4.26126 12.2504C3.98621 11.9754 3.83169 11.6023 3.83169 11.2133V10.5467C3.83147 10.1594 3.67809 9.78794 3.40502 9.51334L2.93835 9.04667C2.80129 8.91037 2.69252 8.74832 2.61829 8.56984C2.54407 8.39136 2.50586 8.19997 2.50586 8.00667C2.50586 7.81337 2.54407 7.62198 2.61829 7.4435C2.69252 7.26502 2.80129 7.10297 2.93835 6.96667L3.40502 6.5C3.67809 6.2254 3.83147 5.85393 3.83169 5.46667V4.8Z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg> <?php echo xhosting_wp_kses($settings['sub_title']);?></span>
                    <?php endif;?>
                        <?php 
                            printf('<%1$s %2$s>%3$s</%1$s>',
                                tag_escape($settings['title_tag']),
                                $this->get_render_attribute_string('title'),
                                nl2br(xhosting_wp_kses($settings['title']))
                            ); 
                        ?>
                        <div id="xhosting-domain-search<?php echo esc_attr( $disable_ajax ) ?>" class="xhosting-domain-search domain__search-wrap domain__search-wrap-three" data-suggestion="<?php echo esc_attr($domain_suggestion) ?>">
                        <form id="xhosting_domain_search_form" action="#" class="domain__search-form domain__search-form-three">
                            <input type="hidden" class="whmcs_url_hidden" name="whmcs_url" value="">
                            <?php do_action( 'wdes_domain_verify_code' ); ?>
                            <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6 15.3333C6 16.559 6.24141 17.7727 6.71046 18.905C7.1795 20.0374 7.86699 21.0663 8.73367 21.933C9.60035 22.7997 10.6292 23.4872 11.7616 23.9562C12.894 24.4253 14.1077 24.6667 15.3333 24.6667C16.559 24.6667 17.7727 24.4253 18.905 23.9562C20.0374 23.4872 21.0663 22.7997 21.933 21.933C22.7997 21.0663 23.4872 20.0374 23.9562 18.905C24.4253 17.7727 24.6667 16.559 24.6667 15.3333C24.6667 14.1077 24.4253 12.894 23.9562 11.7616C23.4872 10.6292 22.7997 9.60035 21.933 8.73367C21.0663 7.86699 20.0374 7.1795 18.905 6.71046C17.7727 6.24141 16.559 6 15.3333 6C14.1077 6 12.894 6.24141 11.7616 6.71046C10.6292 7.1795 9.60035 7.86699 8.73367 8.73367C7.86699 9.60035 7.1795 10.6292 6.71046 11.7616C6.24141 12.894 6 14.1077 6 15.3333Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M26 26L22 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <input id="xhosting-domain" type="text" placeholder="<?php echo esc_attr( $placeholder_input ) ?>">
                            <div class="domain__search-action">
                            <?php
                                if ( $settings['extension_dropdown_list_show'] == 'yes' ) {
                                    $i = 1;
                                    if ( is_array( $extension_list ) && !empty( $extension_list ) ) {
                                        echo '<select id="domainext" class="select">';
                                        foreach ( $extension_list as $list ) {
                                            $select_class = $i == 1 ? ' selected' : '';
                                            $extension_domain = $list['extension_domain'];
                                            $replace_ext_dot = ltrim( $extension_domain, '.');
                                            ?>
                                            <option value="<?php echo esc_attr($replace_ext_dot) ?>"<?php echo esc_attr($select_class) ?>>
                                                <?php echo esc_html($extension_domain) ?>
                                            </option>
                                            <?php
                                            ++$i;
                                        }
                                        echo '</select>';
                                    }
                                }
                                ?>
                            
                                <?php
                                
                                ?>
                                <?php 
                                if( $disable_ajax_search == 'yes' ){ ?>
                                    <button id="xhosting-search" class="tg-btn tg-btn-two tg-btn-six" type="submit">
                                        <?php echo esc_attr( ! empty( $settings['search_btn_label'] ) ? $settings['search_btn_label'] : __('Search Now', 'xhosting-core') ) ?>
                                    </button>
                                    <?php
                                }
                                else{
                                    echo '<a ' . esc_attr(xhostingg_Core_Helper()->the_button($whmcs_url, false))  . ' class="template-btn primary-btn btn-small flex-shrink-0 border-0">'. esc_attr(!empty($settings['search_btn_label']) ? $settings['search_btn_label'] : __('Search Now', 'xhosting-core') ) .'</a>';
                                }
                                wp_nonce_field( 'check-domain-nonce', 'security', true, false ); ?>
                                </div>
                        </form>
                        <div id="xhosting-domain-results" class="domain-results-box"></div>
                        <?php 
                        if($domain_seggestion_on_off == 'yes' ){
                            echo '<div id="xhosting-domain-suggestions" class="domain-results-suggestions"></div>';
                        } ?>
                    </div>
                    <?php if ( $settings['extension_list_show'] == 'yes' ) { ?>
                    <div class="domain-info domain__list domain__list-two">
                        <ul class="list-wrap">
                            <?php
                            if( is_array( $extension_list ) ){
                                foreach( $extension_list as $list ){ ?>
                                    <li class="info-box rounded-3 text-center elementor-repeater-item-<?php echo esc_attr( $list['_id'] ) ?>">
                                        <?php
                                        if ( !empty( $list['extension_domain'] ) ) {
                                            echo '<h5>'. esc_html( $list['extension_domain'] ) .'</h5>';
                                        }
                                        ?>
                                    </li>
                                    <?php
                                }
                            }
                            ?>
                        </ul>
                    
                    </div>
                <?php } ?>
                        <?php if(!empty($settings['btn_label'])):?>
                    <div class="domain__btn">
                        <a <?php echo $this->get_render_attribute_string( 'btn_link' ); ?> class="tg-link-btn">
                            <span class="link-effect">
                                <span class="effect-1"><?php echo xhosting_wp_kses($settings['btn_label']); ?></span>
                                <span class="effect-1"><?php echo xhosting_wp_kses($settings['btn_label']); ?></span>
                            </span>
                            <svg width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12.75 5.75L5.25 13.25" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M6 5.75H12.75V12.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                    </div>
                    <?php endif;?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </section>