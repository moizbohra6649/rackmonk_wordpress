<?php if(!empty($settings['hide_my'] === 'yes')):?>
    <div class="pricing-tab pricing-tab-three">
        <?php if(!empty($settings['m_label'])):?>
            <span class="tab-btn monthly_tab_title"><?php echo xhosting_wp_kses($settings['m_label']); ?></span>
        <?php endif;?>
        <span class="pricing-tab-switcher"></span>
        <?php if(!empty($settings['y_label'])):?>
            <span class="tab-btn annual_tab_title"><?php echo xhosting_wp_kses($settings['y_label']); ?></span>
        <?php endif;?>
    </div>
<?php endif;?>
<div class="pricing__item-wrap-two">
    <div class="row gutter-y-30 justify-content-center">
        <?php foreach($settings['pricings'] as $item):?>
            <div class="col-xl-3 col-lg-4 col-sm-6">
                <div class="pricing__box-four">
                    <div class="pricing__box-top">
                        <div class="pricing__plan">
                            <?php if(!empty($item['title'])):?>
                                <h4 class="title"><?php echo xhosting_wp_kses($item['title']);?> 
                                    <?php if(!empty($item['d_text'])):?>
                                        <span class="price-off <?php if($item['is_populer'] === 'yes'){echo esc_attr('active');}?>"><?php echo xhosting_wp_kses($item['d_text']);?></span>
                                    <?php endif;?>
                                 </h4>
                            <?php endif;?>
                            <?php if(!empty($item['text'])):?>
                                <p><?php echo xhosting_wp_kses($item['text']);?></p>
                            <?php endif;?>
                        </div>
                        <div class="pricing__price">
                            <?php if(!empty($item['m_price'])):?>
                                <h2 class="price monthly_price"><?php echo xhosting_wp_kses($item['m_price']);?> <?php if(!empty($item['m_period'])):?><span><?php echo xhosting_wp_kses($item['m_period']);?> </span><?php endif;?></h2>
                            <?php endif;?>
                            <?php if(!empty($item['y_price'])):?>
                                <h2 class="price annual_price"><?php echo xhosting_wp_kses($item['y_price']);?> <?php if(!empty($item['y_period'])):?><span><?php echo xhosting_wp_kses($item['y_period']);?> </span><?php endif;?></h2>
                            <?php endif;?>
                            <?php if(!empty($item['p_text'])):?>
                            <p><?php echo xhosting_wp_kses($item['p_text']);?></p>
                            <?php endif;?>
                        </div>
                        <?php if(!empty($item['btn_label'])):?>
                            <div class="pricing__btn">
                                <a target="<?php echo esc_attr( $item['link']['is_external'] ? '_blank' : '_self' ); ?>" rel="<?php echo esc_attr( $item['link']['nofollow'] ? 'nofollow' : '' ); ?>" href="<?php echo esc_url($item['link']['url']);?>"  class="tg-btn">
                                    <?php echo xhosting_wp_kses($item['btn_label']);?>
                                    <svg width="21" height="20" viewBox="0 0 21 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M4.66797 10H16.3346" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M13 13.3333L16.3333 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M13 6.66663L16.3333 9.99996" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </a>
                            </div>
                        <?php endif;?>
                    </div>
                    <div class="pricing__box-bottom">
                    <?php if(!empty($item['lists'])):?>
                        <div class="pricing__list">
                            <ul class="list-wrap">
                                <?php foreach($item['lists'] as $list):?>
                                    <li>
                                        <div class="icon">
                                            <?php \Elementor\Icons_Manager::render_icon( $list['icon'], [ 'aria-hidden' => 'true' ] ); ?>
                                        </div>
                                        <?php if(!empty($list['title'])):?>
                                        <div class="content">
                                            <span><?php echo xhosting_wp_kses($list['title']);?></span>
                                            <?php if($list['marked_hide'] === 'yes'):?>
                                            <svg data-toggle="tooltip" data-placement="right" title="<?php echo xhosting_wp_kses($list['info_title']);?>" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M2 8C2 8.78793 2.15519 9.56815 2.45672 10.2961C2.75825 11.0241 3.20021 11.6855 3.75736 12.2426C4.31451 12.7998 4.97595 13.2417 5.7039 13.5433C6.43185 13.8448 7.21207 14 8 14C8.78793 14 9.56815 13.8448 10.2961 13.5433C11.0241 13.2417 11.6855 12.7998 12.2426 12.2426C12.7998 11.6855 13.2417 11.0241 13.5433 10.2961C13.8448 9.56815 14 8.78793 14 8C14 6.4087 13.3679 4.88258 12.2426 3.75736C11.1174 2.63214 9.5913 2 8 2C6.4087 2 4.88258 2.63214 3.75736 3.75736C2.63214 4.88258 2 6.4087 2 8Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M8 6H8.00833" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M7.33203 8H7.9987V10.6667H8.66536" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            <?php endif;?>
                                        </div>
                                        <?php endif;?>
                                    </li>
                                <?php endforeach;?>
                            </ul>
                        </div>
                    <?php endif;?>
                    </div>
                </div>
            </div>
        <?php endforeach;?>
    </div>
</div>
