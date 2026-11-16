<div class="testimonial__inner-wrap">
    <div class="row gx-0">
        <div class="col-lg-6">
            <div class="testimonial__item-wrap">
                <div class="testimonial-active fix">
                    <?php foreach($settings['testimonials'] as $item):?>
                        <div class="single-slide">
                            <div class="testimonial__item-two">
                                <div class="testimonial__content-two">
                                    <?php \Elementor\Icons_Manager::render_icon( $item['quote'], [ 'aria-hidden' => 'true' ] ); ?>
                                    <?php if(!empty($item['feedback'])):?>
                                        <p><?php echo xhosting_wp_kses($item['feedback']);?></p>
                                    <?php endif;?>
                                </div>
                                <div class="testimonial__author testimonial__author-two">
                                    <div class="content">
                                        <?php if(!empty($item['name'])):?>
                                            <h2 class="title"><?php echo xhosting_wp_kses($item['name']);?></h2>
                                        <?php endif;?>
                                        <?php if(!empty($item['designation'])):?>
                                            <span><?php echo xhosting_wp_kses($item['designation']);?></span>
                                        <?php endif;?>
                                    </div>
                                </div>
                                <svg class="shape" width="158" height="24" viewBox="0 0 158 24" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M69.7142 0.0317909C69.4741 0.0498856 68.6882 0.107126 67.9677 0.158902C53.4981 1.19988 34.1707 5.85317 21.0452 11.4561C9.81998 16.2478 -0.151768 22.0278 0.00174959 23.6538C0.036767 24.0241 0.388425 24.03 1.50042 23.679C2.49951 23.3636 2.43332 23.3196 1.31241 23.5539C0.196222 23.7872 0.154568 23.7417 0.85841 23.0576C3.79498 20.2033 18.3601 13.2228 28.3442 9.8847C44.6053 4.44797 60.6901 1.44434 73.7749 1.40098C78.7808 1.38441 80.0623 1.60549 80.0623 2.48568C80.0623 3.6793 75.0054 7.01195 71.7227 7.98181C61.7857 10.9175 48.8795 15.9927 47.4536 17.5254C46.9788 18.0358 47.2131 18.7167 47.8636 18.7167C49.1436 18.7167 59.1241 14.9004 67.2255 11.3132C70.0471 10.0639 70.4362 9.94553 78.5777 7.86168C90.9303 4.70004 97.3086 3.65879 105.299 3.49943C108.587 3.43395 108.916 3.50606 108.442 4.18855C107.487 5.56411 100.384 9.0906 88.1399 14.2674C85.9769 15.1819 85.4413 15.8101 86.1793 16.5671L86.4457 16.8403L87.38 16.7934C88.0872 16.7579 88.8848 16.6139 90.6589 16.2015C96.6864 14.8001 108.106 12.4839 113.639 11.5406C123.048 9.93639 133.353 8.81649 147.914 7.81564C150.085 7.66649 151.792 7.49818 152.979 7.31651C153.963 7.16584 155.496 6.97191 156.384 6.88565L158 6.7287L156.908 6.6634C156.308 6.62748 155.031 6.48917 154.07 6.35606C149.741 5.75616 142.692 6.14072 127.262 7.8186C117.316 8.90007 105.433 10.9882 94.6183 13.5546C93.9069 13.7235 93.2585 13.8603 93.1775 13.8586C93.0964 13.8569 93.9732 13.4408 95.1259 12.9338C108.252 7.16037 111.235 5.14542 109.991 2.89146C109.489 1.98368 108.637 1.7721 105.474 1.77058C97.8514 1.76682 89.901 3.033 79.0626 5.97688C77.4871 6.40479 77.4915 6.40694 78.5001 5.69838C82.2859 3.03846 82.1052 0.939927 78.0256 0.187026C77.3427 0.0610802 70.9178 -0.0590409 69.7142 0.0317909Z"/>
                                </svg>
                            </div>
                        </div>
                    <?php endforeach;?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="testimonial__slider-thumb">
                <div class="testimonial-nav">
                    <?php foreach($settings['testimonials'] as $item):?>
                        <div class="single-slide">
                            <img src="<?php echo esc_url($item['authore']['url']);?>" alt="<?php if(!empty($item['authore']['alt'])){ echo esc_attr($item['authore']['alt']);}else{esc_attr_e('List', 'xhosting-plugin');}?>">
                        </div>
                    <?php endforeach;?>
                </div>
                <div class="testimonial__nav"></div>
            </div>
        </div>
    </div>
</div>