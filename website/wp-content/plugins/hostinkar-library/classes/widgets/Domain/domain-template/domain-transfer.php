 <?php 
    if ( ! empty( $settings['btn_link']['url'] ) ) {
        $this->add_link_attributes( 'btn_link', $settings['btn_link'] );
    }
    if ( ! empty( $settings['tr_btn_link']['url'] ) ) {
        $this->add_link_attributes( 'tr_btn_link', $settings['tr_btn_link'] );
    }
?>
<section class="domain__search-area-two domain__search-area-three has-animation domain__search-bg" <?php if(!empty($settings['bg_img']['url'])):?> data-background="<?php echo esc_url($settings['bg_img']['url']);?>" <?php endif;?>>
    <div class="container">
        <div class="domain__inner-wrap-two">
            <svg class="shape" width="158" height="24" viewBox="0 0 158 24" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M69.7142 0.0317909C69.4741 0.0498856 68.6882 0.107126 67.9677 0.158902C53.4981 1.19988 34.1707 5.85317 21.0452 11.4561C9.81998 16.2478 -0.151768 22.0278 0.00174959 23.6538C0.036767 24.0241 0.388425 24.03 1.50042 23.679C2.49951 23.3636 2.43332 23.3196 1.31241 23.5539C0.196222 23.7872 0.154568 23.7417 0.85841 23.0576C3.79498 20.2033 18.3601 13.2228 28.3442 9.8847C44.6053 4.44797 60.6901 1.44434 73.7749 1.40098C78.7808 1.38441 80.0623 1.60549 80.0623 2.48568C80.0623 3.6793 75.0054 7.01195 71.7227 7.98181C61.7857 10.9175 48.8795 15.9927 47.4536 17.5254C46.9788 18.0358 47.2131 18.7167 47.8636 18.7167C49.1436 18.7167 59.1241 14.9004 67.2255 11.3132C70.0471 10.0639 70.4362 9.94553 78.5777 7.86168C90.9303 4.70004 97.3086 3.65879 105.299 3.49943C108.587 3.43395 108.916 3.50606 108.442 4.18855C107.487 5.56411 100.384 9.0906 88.1399 14.2674C85.9769 15.1819 85.4413 15.8101 86.1793 16.5671L86.4457 16.8403L87.38 16.7934C88.0872 16.7579 88.8848 16.6139 90.6589 16.2015C96.6864 14.8001 108.106 12.4839 113.639 11.5406C123.048 9.93639 133.353 8.81649 147.914 7.81564C150.085 7.66649 151.792 7.49818 152.979 7.31651C153.963 7.16584 155.496 6.97191 156.384 6.88565L158 6.7287L156.908 6.6634C156.308 6.62748 155.031 6.48917 154.07 6.35606C149.741 5.75616 142.692 6.14072 127.262 7.8186C117.316 8.90007 105.433 10.9882 94.6183 13.5546C93.9069 13.7235 93.2585 13.8603 93.1775 13.8586C93.0964 13.8569 93.9732 13.4408 95.1259 12.9338C108.252 7.16037 111.235 5.14542 109.991 2.89146C109.489 1.98368 108.637 1.7721 105.474 1.77058C97.8514 1.76682 89.901 3.033 79.0626 5.97688C77.4871 6.40479 77.4915 6.40694 78.5001 5.69838C82.2859 3.03846 82.1052 0.939927 78.0256 0.187026C77.3427 0.0610802 70.9178 -0.0590409 69.7142 0.0317909Z"/>
            </svg>
            <?php if(!empty($settings['title'])):?>
                <div class="section__title text-center mb-40">
                    <h2 class="title"><?php echo xhosting_wp_kses($settings['title']); ?></h2>
                </div>
            <?php endif;?>
            <div class="domain__search-wrap domain__search-wrap-two">
                <form action="#" class="domain__search-form domain__search-form-two">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 15.3333C6 16.559 6.24141 17.7727 6.71046 18.905C7.1795 20.0374 7.86699 21.0663 8.73367 21.933C9.60035 22.7997 10.6292 23.4872 11.7616 23.9562C12.894 24.4253 14.1077 24.6667 15.3333 24.6667C16.559 24.6667 17.7727 24.4253 18.905 23.9562C20.0374 23.4872 21.0663 22.7997 21.933 21.933C22.7997 21.0663 23.4872 20.0374 23.9562 18.905C24.4253 17.7727 24.6667 16.559 24.6667 15.3333C24.6667 14.1077 24.4253 12.894 23.9562 11.7616C23.4872 10.6292 22.7997 9.60035 21.933 8.73367C21.0663 7.86699 20.0374 7.1795 18.905 6.71046C17.7727 6.24141 16.559 6 15.3333 6C14.1077 6 12.894 6.24141 11.7616 6.71046C10.6292 7.1795 9.60035 7.86699 8.73367 8.73367C7.86699 9.60035 7.1795 10.6292 6.71046 11.7616C6.24141 12.894 6 14.1077 6 15.3333Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M26 26L22 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <input type="text" placeholder="Enter domain name you want to transfer">
                    <div class="domain__search-action">
                        <?php if(!empty($settings['extension_list'])):?>
                            <select class="select">
                                <?php foreach($settings['extension_list'] as $list):?>
                                    <option><?php echo xhosting_wp_kses($list['extension_domain']); ?></option>
                                <?php endforeach;?>
                            </select>
                        <?php endif;?>
                        <?php if(!empty($settings['search_btn'])):?>
                            <a <?php echo $this->get_render_attribute_string( 'tr_btn_link' ); ?> class="tg-btn tg-btn-two"><?php echo xhosting_wp_kses($settings['search_btn']); ?></a>
                        <?php endif;?>
                    </div>
                </form>
            </div>
            <?php if(!empty($settings['btn_label']) || !empty($settings['text'])):?>
                <div class="domain__bottom-content">
                    <p><?php echo xhosting_wp_kses($settings['text']); ?></p>
                    <?php if(!empty($settings['btn_label'])):?>
                        <a <?php echo $this->get_render_attribute_string( 'btn_link' ); ?> class="tg-link-btn">
                            <span class="link-effect">
                                <span class="effect-1"><?php echo xhosting_wp_kses($settings['btn_label']); ?></span>
                                <span class="effect-1"><?php echo xhosting_wp_kses($settings['btn_label']); ?></span>
                            </span>
                        </a>
                    <?php endif;?>
                </div>
            <?php endif;?>
        </div>
    </div>
    <div class="domain__shape domain__shape-two">
        <?php if(!empty($settings['shape']['url'])):?>
            <img src="<?php echo esc_url($settings['shape']['url']); ?>" alt="<?php if(!empty($settings['shape']['alt'])){ echo esc_attr($settings['shape']['alt']);}?>" data-aos="fade-right" data-aos-delay="200">
         <?php endif;?>
        <?php if(!empty($settings['shape2']['url'])):?>
            <img src="<?php echo esc_url($settings['shape2']['url']); ?>" alt="<?php if(!empty($settings['shape2']['alt'])){ echo esc_attr($settings['shape2']['alt']);}?>" data-aos="fade-left" data-aos-delay="200">
        <?php endif;?>
        <?php if(!empty($settings['shape3']['url'])):?>
            <img src="<?php echo esc_url($settings['shape3']['url']); ?>" alt="<?php if(!empty($settings['shape3']['alt'])){ echo esc_attr($settings['shape3']['alt']);}?>" data-aos="fade-down-right" data-aos-delay="200">
        <?php endif;?>
    </div>
</section>