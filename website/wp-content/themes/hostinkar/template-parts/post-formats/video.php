<?php
   
   if(get_post_meta(get_the_ID(), 'xhosting_post_format_meta', true)) {
    $post_video_meta = get_post_meta(get_the_ID(), 'xhosting_post_format_meta', true);
} else {
    $post_video_meta = array();
}

if( array_key_exists( 'video_link', $post_video_meta )) {
    $video_link = $post_video_meta['video_link'];
} else {
    $video_link = '';
} 

?>
<div class="gt-blog-img position-relative">
    <div class="item-img">
    <?php the_post_thumbnail( 'xhosting-img-size-1', ['class' => 'img-fluid'] );?>
    </div>
    <div class="play-icon">
        <a href="<?php echo esc_url($video_link);?>"><i class="fas fa-play"></i></a>
    </div>
</div>