<?php
defined('ABSPATH') || exit;
get_header();
$defaults = Rackmonk_Site_Content::defaults();
$copy = static function ($key) use ($defaults) {
    return esc_html(Rackmonk_Site_Content::text($key, $defaults[$key] ?? ''));
};
$link = static function ($key) use ($defaults) {
    return esc_url(Rackmonk_Site_Content::text($key, $defaults[$key] ?? ''));
};
?>
<main class="rm-about">
    <section class="rm-about-hero">
        <div class="rm-about-wrap">
            <span class="rm-about-label"><?php echo $copy('about_eyebrow'); ?></span>
            <h1><?php echo $copy('about_title'); ?></h1>
            <p><?php echo $copy('about_intro'); ?></p>
            <a class="rm-about-button" href="<?php echo $link('about_contact_url'); ?>"><?php echo $copy('about_contact_button'); ?> <span aria-hidden="true">&#8599;</span></a>
        </div>
    </section>
    <section class="rm-about-wrap rm-about-intro">
        <div><span class="rm-about-label"><?php echo $copy('about_identity_label'); ?></span><h2><?php echo $copy('about_identity_title'); ?></h2></div>
        <div><p><?php echo $copy('about_identity_first'); ?></p><p><?php echo $copy('about_identity_second'); ?></p></div>
    </section>
    <section class="rm-about-mission">
        <div class="rm-about-wrap rm-about-mission-inner">
            <span class="rm-about-label"><?php echo $copy('about_mission_label'); ?></span>
            <h2><?php echo $copy('about_mission_title'); ?></h2>
            <p><?php echo $copy('about_mission_text'); ?></p>
        </div>
    </section>
    <section class="rm-about-wrap rm-about-pillars">
        <span class="rm-about-label"><?php echo $copy('about_pillars_label'); ?></span>
        <h2><?php echo $copy('about_pillars_title'); ?></h2>
        <div class="rm-about-grid">
            <?php foreach (['technology', 'people', 'customers'] as $index => $pillar): ?>
            <article><span><?php echo esc_html(sprintf('%02d', $index + 1)); ?></span><h3><?php echo $copy('about_' . $pillar . '_title'); ?></h3><p><?php echo $copy('about_' . $pillar . '_text'); ?></p></article>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="rm-about-wrap rm-about-cta">
        <div><span class="rm-about-label"><?php echo $copy('about_cta_label'); ?></span><h2><?php echo $copy('about_cta_title'); ?></h2><p><?php echo $copy('about_cta_text'); ?></p></div>
        <a class="rm-about-button" href="<?php echo $link('about_cta_url'); ?>"><?php echo $copy('about_cta_button'); ?> <span aria-hidden="true">&#8599;</span></a>
    </section>
</main>
<?php get_footer(); ?>
