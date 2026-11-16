<?php 
    $this->add_render_attribute( 'title', 'class', 'elementor-gt-heading title' );
?>
<div class="hosting__content-wrap prthalign">
    <div class="section__title mb-15">
        <?php if(!empty($settings['sub_title'])):?>
            <span class="sub-title"><?php echo xhosting_wp_kses($settings['sub_title']);?></span>
        <?php endif;?>
        <?php 
            printf('<%1$s %2$s>%3$s</%1$s>',
                tag_escape($settings['title_tag']),
                $this->get_render_attribute_string('title'),
                nl2br(xhosting_wp_kses($settings['title']))
            ); 
        ?>
    </div>
    <?php if(!empty($settings['description'])):?>
        <p class="xhosting-elementor-desc">
            <?php echo xhosting_wp_kses($settings['description']);?>
        </p>
    <?php endif;?>
</div>