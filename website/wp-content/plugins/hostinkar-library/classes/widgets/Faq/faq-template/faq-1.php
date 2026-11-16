<?php 
    $xhostingrand__id = rand( 23142345324, 564548574 );
?>
<div class="faq__wrap">
    <div class="accordion" id="accordionExample<?php echo esc_attr( $xhostingrand__id );?>">
        <?php foreach($settings['faqs'] as $id => $item):
            $collapsed_tab   = ($id == 0) ? '' : 'collapsed';
            $area_expanded   = ($id == 0) ? 'true' : 'false';
            $active_show_tab = ($id == 0) ? 'show' : '';
            $active_show_bg  = ($id == 0) ? 'faq_active' : '';
        ?>
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button <?php echo esc_attr($collapsed_tab);?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo esc_attr( $xhostingrand__id );?>-<?php echo esc_attr($item['_id'] ); ?>" aria-expanded="true" aria-controls="collapse<?php echo esc_attr( $xhostingrand__id );?>-<?php echo esc_attr($item['_id'] ); ?>">
                    <?php echo xhosting_wp_kses($item['title']);?>
                </button>
            </h2>
            <div id="collapse<?php echo esc_attr( $xhostingrand__id );?>-<?php echo esc_attr($item['_id'] ); ?>" class="accordion-collapse collapse <?php echo esc_attr($active_show_tab);?>" data-bs-parent="#accordionExample<?php echo esc_attr( $xhostingrand__id );?>">
                <div class="accordion-body">
                    <?php echo xhosting_wp_kses(wpautop($item['description']));?>
                </div>
            </div>
        </div>
        <?php endforeach;?>

    </div>
</div>