<div class="faq__tab-wrap">
    <ul class="nav nav-tabs" id="myTabTwo" role="tablist">
        <?php foreach( $settings['faqs'] as $ids => $item ) :
            $active = ($ids == 0) ? 'active' : '';
            $aria_selected = ($ids == 0) ? 'true' : 'false';
        ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo esc_attr($active); ?>" id="general-tab<?php echo esc_attr($ids); ?>" data-bs-toggle="tab" data-bs-target="#general-tab-pane<?php echo esc_attr($ids); ?>" type="button" role="tab" aria-controls="general-tab-pane<?php echo esc_attr($ids); ?>" aria-selected="<?php echo esc_attr($aria_selected); ?>">
                <?php echo xhosting_wp_kses($item['title']);?>
            </button>
        </li>
        <?php endforeach;?>
    </ul>
</div>

<div class="tab-content" id="myTabContentTwo">
    <?php foreach( $settings['faqs'] as $ids => $item ) :
        $active = ($ids == 0) ? 'show active' : '';
        $accordion_id = 'accordionExample' . $ids;
    ?>
    <div class="tab-pane fade <?php echo esc_attr($active); ?>" id="general-tab-pane<?php echo esc_attr($ids); ?>" role="tabpanel" aria-labelledby="general-tab<?php echo esc_attr($ids); ?>" tabindex="0">
        <div class="faq__wrap faq__wrap-two faq__wrap-three">
            <div class="accordion" id="<?php echo esc_attr($accordion_id); ?>">
                <?php foreach($item['faqs_items'] as $id => $faq):
                    $collapsed_tab   = ($id == 0) ? '' : 'collapsed';
                    $area_expanded   = ($id == 0) ? 'true' : 'false';
                    $active_show_tab = ($id == 0) ? 'show' : '';
                    $active_show_bg  = ($id == 0) ? 'active' : '';
                    $collapse_id = 'collapse-' . esc_attr($accordion_id) . '-' . esc_attr($faq['_id']);
                ?>
                <div class="accordion-item <?php echo esc_attr($active_show_bg); ?>">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?php echo esc_attr($collapsed_tab); ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo esc_attr($collapse_id); ?>" aria-expanded="<?php echo esc_attr($area_expanded); ?>" aria-controls="<?php echo esc_attr($collapse_id); ?>">
                            <?php echo xhosting_wp_kses($faq['title']);?>
                        </button>
                    </h2>
                    <div id="<?php echo esc_attr($collapse_id); ?>" class="accordion-collapse collapse <?php echo esc_attr($active_show_tab); ?>" data-bs-parent="#<?php echo esc_attr($accordion_id); ?>">
                        <div class="accordion-body">
                            <?php echo xhosting_wp_kses(wpautop($faq['desc']));?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
