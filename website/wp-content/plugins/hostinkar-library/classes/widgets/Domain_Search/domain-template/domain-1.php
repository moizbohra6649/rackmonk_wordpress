<?php
$disable_ajax = $disable_ajax_search == 'yes' ? '' : '-off';
$domain_suggestion = $domain_seggestion_on_off == 'yes' ? 'true' : 'false'; ?>

<div id="xhosting-domain-search<?php echo esc_attr( $disable_ajax ) ?>" class="xhosting-domain-search domain__search-wrap" data-suggestion="<?php echo esc_attr($domain_suggestion) ?>">
    <form action="#" id="xhosting_domain_search_form" class="domain__search-form">
        <input type="hidden" class="whmcs_url_hidden" name="whmcs_url" value="">
        <?php do_action( 'wdes_domain_verify_code' ); ?>
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g clip-path="url(#clip0_4017_10)">
                <path d="M6 15.3333C6 16.559 6.24141 17.7727 6.71046 18.905C7.1795 20.0374 7.86699 21.0663 8.73367 21.933C9.60035 22.7997 10.6292 23.4872 11.7616 23.9562C12.894 24.4253 14.1077 24.6667 15.3333 24.6667C16.559 24.6667 17.7727 24.4253 18.905 23.9562C20.0374 23.4872 21.0663 22.7997 21.933 21.933C22.7997 21.0663 23.4872 20.0374 23.9562 18.905C24.4253 17.7727 24.6667 16.559 24.6667 15.3333C24.6667 14.1077 24.4253 12.894 23.9562 11.7616C23.4872 10.6292 22.7997 9.60035 21.933 8.73367C21.0663 7.86699 20.0374 7.1795 18.905 6.71046C17.7727 6.24141 16.559 6 15.3333 6C14.1077 6 12.894 6.24141 11.7616 6.71046C10.6292 7.1795 9.60035 7.86699 8.73367 8.73367C7.86699 9.60035 7.1795 10.6292 6.71046 11.7616C6.24141 12.894 6 14.1077 6 15.3333Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M26 26L22 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </g>
            <defs>
                <clipPath id="clip0_4017_10">
                    <rect width="32" height="32" fill="white" />
                </clipPath>
            </defs>
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
            <button id="xhosting-search" class="tg-btn tg-black-btn" type="submit">
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
    <div class="domain__rs-wrapper">
        <div id="xhosting-domain-results" class="domain-results-box"></div>
        <?php 
        if($domain_seggestion_on_off == 'yes' ){
            echo '<div id="xhosting-domain-suggestions" class="domain-results-suggestions"></div>';
        } ?>
    </div>
    
</div>

<?php if ( $settings['extension_list_show'] == 'yes' ) { ?>
    <div class="domain-info mt-4 d-flex justify-content-center align-items-center">
        <?php
        if( is_array( $extension_list ) ){
            foreach( $extension_list as $list ){ ?>
                <div class="info-box rounded-3 text-center elementor-repeater-item-<?php echo esc_attr( $list['_id'] ) ?>">
                    <?php
                    if ( !empty( $list['extension_domain'] ) ) {
                        echo '<h5 class="primary-text d-none">'. esc_html( $list['extension_domain'] ) .'</h5>';
                    }
                    ?>
                </div>
                <?php
            }
        }
        ?>
    </div>
<?php } ?>