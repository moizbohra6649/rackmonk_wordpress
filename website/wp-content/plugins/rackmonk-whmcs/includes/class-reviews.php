<?php
/**
 * Customer reviews and the website rating.
 *
 * - Signed-in customers rate Rackmonk (1-5 stars + a short review) from My Account or the
 *   [rackmonk_review_form] shortcode. One review per customer; editing it sends it back for approval.
 * - Reviews wait as "Pending" under Customer Reviews in wp-admin until approved.
 * - Website rating = base rating/count (Customer Reviews → Rating Settings) combined with approved reviews.
 *   Every rating widget on the site (Rating, Banner, Service) shows that one number, so pages always match.
 */
if (!defined('ABSPATH')) exit;

final class Rackmonk_Reviews {
    const POST_TYPE = 'rackmonk_review';
    const SETTINGS = 'rackmonk_rating_settings';
    const SUMMARY = 'rackmonk_rating_summary';
    const META_RATING = '_rackmonk_rating';
    const NONCE = 'rackmonk-review';
    const MIN_LENGTH = 10;
    const MAX_LENGTH = 1000;
    const WIDGETS = ['xhosting-rating', 'xhosting-banner', 'xhosting-service'];
    const SLIDER_LIMIT = 12; // Newest approved reviews shown in testimonial sliders.
    /** A container made only of these widgets is a "testimonials section" and is hidden while there are no reviews. */
    const SECTION_WIDGETS = ['go-testimonial', 'xhosting-heading', 'heading', 'xhosting-rating', 'text-editor', 'spacer', 'divider', 'button'];

    private static $error = '';
    private static $posted = [];
    private static $approved = null;

    public static function boot() {
        add_action('init', [__CLASS__, 'register']);
        add_action('wp_loaded', [__CLASS__, 'handle_submit'], 20);
        add_action('wp_enqueue_scripts', [__CLASS__, 'assets'], 40);
        add_action('woocommerce_account_dashboard', [__CLASS__, 'account_card'], 20);
        add_shortcode('rackmonk_review_form', [__CLASS__, 'shortcode']);
        add_shortcode('rackmonk_rating', [__CLASS__, 'rating_shortcode']);

        // wp-admin.
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [__CLASS__, 'columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [__CLASS__, 'column'], 10, 2);
        add_filter('post_row_actions', [__CLASS__, 'row_actions'], 10, 2);
        add_action('admin_post_rackmonk_review_status', [__CLASS__, 'quick_status']);
        add_action('add_meta_boxes_' . self::POST_TYPE, [__CLASS__, 'meta_box']);
        add_action('save_post_' . self::POST_TYPE, [__CLASS__, 'save_meta'], 10, 2);
        add_action('admin_notices', [__CLASS__, 'admin_notice']);

        // Keep the cached total up to date.
        add_action('transition_post_status', [__CLASS__, 'status_changed'], 10, 3);
        add_action('deleted_post', [__CLASS__, 'deleted'], 10, 2);
        add_action('update_option_' . self::SETTINGS, [__CLASS__, 'refresh']);
        add_action('add_option_' . self::SETTINGS, [__CLASS__, 'refresh']);

        // Rating widgets: always show the live rating, never a cached copy.
        add_action('elementor/frontend/widget/before_render', [__CLASS__, 'widget']);
        add_filter('elementor/widget/render_content', [__CLASS__, 'widget_content'], 15, 2);
        add_filter('elementor/element/is_dynamic_content', static function ($dynamic, $data) {
            return $dynamic || in_array($data['widgetType'] ?? '', self::WIDGETS, true);
        }, 10, 2);

        // Testimonial sliders: approved customer reviews instead of the theme's demo testimonials.
        add_filter('elementor/frontend/builder_content_data', [__CLASS__, 'testimonials'], 20, 2);
    }

    public static function register() {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Customer Reviews', 'singular_name' => 'Customer Review', 'menu_name' => 'Customer Reviews',
                'all_items' => 'All Reviews', 'edit_item' => 'Edit Review', 'view_item' => 'View Review', 'search_items' => 'Search Reviews',
                'not_found' => 'No reviews yet.', 'not_found_in_trash' => 'No reviews in Trash.',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => false,
            'menu_position' => 26,
            'menu_icon' => 'dashicons-star-filled',
            'supports' => ['title', 'editor'],
            'capability_type' => 'post',
            'capabilities' => ['create_posts' => 'do_not_allow'], // Reviews only come from customers.
            'map_meta_cap' => true,
            'rewrite' => false,
            'query_var' => false,
        ]);
    }

    /* ------------------------------------------------------------------ rating */

    public static function settings() {
        $saved = get_option(self::SETTINGS);
        $defaults = [
            // Starting point taken from the homepage hero ("Rated 4.9 out of 5 from 16K+"); replace with your real numbers.
            'base_rating' => 4.9,
            'base_count' => 16000,
            'sentence' => '{rating} out of 5 stars based on {count} reviews',
        ];
        return is_array($saved) ? array_merge($defaults, $saved) : $defaults;
    }

    public static function refresh() {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) AS total, COALESCE(SUM(CAST(m.meta_value AS UNSIGNED)), 0) AS points
             FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = %s
             WHERE p.post_type = %s AND p.post_status = 'publish' AND m.meta_value BETWEEN '1' AND '5'",
            self::META_RATING,
            self::POST_TYPE
        ));
        update_option(self::SUMMARY, ['count' => (int) ($row->total ?? 0), 'points' => (int) ($row->points ?? 0)], true);
        self::$approved = null;
        // Pages cache their Elementor output; drop it so testimonial sliders show the new set of reviews.
        delete_post_meta_by_key(class_exists('\Elementor\Core\Base\Document') ? \Elementor\Core\Base\Document::CACHE_META_KEY : '_elementor_element_cache');
    }

    /** Combined website rating: ['rating' => 4.8, 'count' => 16012, 'reviews' => approved form reviews]. */
    public static function summary() {
        $approved = get_option(self::SUMMARY);
        if (!is_array($approved)) {
            self::refresh();
            $approved = (array) get_option(self::SUMMARY);
        }
        $settings = self::settings();
        $base_count = max(0, (int) $settings['base_count']);
        $base_rating = min(5, max(0, (float) $settings['base_rating']));
        $count = $base_count + (int) ($approved['count'] ?? 0);
        $points = $base_rating * $base_count + (int) ($approved['points'] ?? 0);
        return [
            'rating' => $count ? round($points / $count, 1) : 0.0,
            'count' => $count,
            'reviews' => (int) ($approved['count'] ?? 0),
        ];
    }

    private static function rating_text($rating) {
        return number_format_i18n((float) $rating, 1);
    }

    /** 16000 → "16K+", 250 → "250". */
    private static function compact_count($count) {
        if ($count >= 1000000) return floor($count / 1000000) . 'M+';
        if ($count >= 1000) return floor($count / 1000) . 'K+';
        return number_format_i18n($count);
    }

    private static function sentence(array $summary) {
        $text = (string) self::settings()['sentence'];
        if ($summary['count'] === 1) $text = preg_replace('/\breviews\b/', 'review', $text);
        return strtr($text, ['{rating}' => self::rating_text($summary['rating']), '{count}' => number_format_i18n($summary['count'])]);
    }

    public static function status_changed($new, $old, $post) {
        if ($post instanceof WP_Post && $post->post_type === self::POST_TYPE && ($new === 'publish' || $old === 'publish')) self::refresh();
    }

    public static function deleted($id, $post = null) {
        if ($post instanceof WP_Post && $post->post_type === self::POST_TYPE) self::refresh();
    }

    /* ---------------------------------------------------------- rating widgets */

    public static function widget($widget) {
        if (!in_array($widget->get_name(), self::WIDGETS, true)) return;
        $settings = $widget->get_settings();
        if (!isset($settings['rating']) || $settings['rating'] === '' || $settings['rating'] === null) return; // Widget without a rating line.
        $summary = self::summary();
        if ($summary['count'] < 1) return;
        $widget->set_settings('rating', self::rating_text($summary['rating']));
        if (!empty($settings['total_rating'])) $widget->set_settings('total_rating', self::compact_count($summary['count']));
        // "4.6 out of 5 stars based on 120,456 reviews" style sentences.
        if (!empty($settings['review_text']) && preg_match('/\d[\d.,]*\s*(out of|\/)\s*5/i', wp_strip_all_tags((string) $settings['review_text']))) {
            $widget->set_settings('review_text', self::sentence($summary));
        }
        if (!empty($settings['review_logo']) && is_array($settings['review_logo'])) {
            $logo = $settings['review_logo'];
            $logo['alt'] = self::rating_text($summary['rating']) . ' out of 5 stars';
            $widget->set_settings('review_logo', $logo);
        }
        $widget->reset_render_state();
    }

    /** No base rating and no approved reviews yet: hide the rating instead of showing "0 out of 5". */
    public static function widget_content($content, $widget) {
        if (!in_array($widget->get_name(), self::WIDGETS, true) || self::summary()['count'] > 0) return $content;
        $settings = $widget->get_settings();
        if (!isset($settings['rating']) || $settings['rating'] === '') return $content;
        if ($widget->get_name() === 'xhosting-rating') return '';
        return self::remove_block($content, 'banner__review');
    }

    /** Removes the <div> whose class contains $class, including nested <div>s. */
    private static function remove_block($html, $class) {
        if (!preg_match('/<div\b[^>]*class="[^"]*\b' . preg_quote($class, '/') . '\b[^"]*"[^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE)) return $html;
        $start = $m[0][1];
        $pos = $start + strlen($m[0][0]);
        $depth = 1;
        while ($depth > 0 && preg_match('/<(\/?)div\b[^>]*>/i', $html, $tag, PREG_OFFSET_CAPTURE, $pos)) {
            $depth += $tag[1][0] === '/' ? -1 : 1;
            $pos = $tag[0][1] + strlen($tag[0][0]);
        }
        return $depth === 0 ? substr($html, 0, $start) . substr($html, $pos) : $html;
    }

    /* ------------------------------------------------------- testimonial sliders */

    /** Newest approved reviews: [['id', 'name', 'text', 'rating', 'author'], ...]. */
    public static function approved_reviews() {
        if (self::$approved !== null) return self::$approved;
        $posts = get_posts(['post_type' => self::POST_TYPE, 'post_status' => 'publish', 'numberposts' => self::SLIDER_LIMIT, 'orderby' => 'date', 'order' => 'DESC', 'suppress_filters' => true]);
        self::$approved = [];
        foreach ($posts as $post) {
            $rating = (int) get_post_meta($post->ID, self::META_RATING, true);
            if ($rating < 1 || $rating > 5 || trim($post->post_content) === '') continue;
            self::$approved[] = ['id' => $post->ID, 'name' => $post->post_title, 'text' => $post->post_content, 'rating' => $rating, 'author' => (int) $post->post_author];
        }
        return self::$approved;
    }

    private static function star_image($rating) {
        return RACKMONK_WHMCS_URL . 'assets/img/stars-' . max(1, min(5, (int) $rating)) . '.svg';
    }

    /** Elementor page data → testimonial widgets show approved reviews, or their section is removed when there are none. */
    public static function testimonials($data, $post_id = 0) {
        if (!is_array($data) || strpos((string) wp_json_encode($data), '"go-testimonial"') === false) return $data;
        $single = 0;
        return self::testimonial_elements($data, self::approved_reviews(), $single);
    }

    private static function testimonial_elements(array $elements, array $reviews, &$single) {
        $out = [];
        foreach ($elements as $element) {
            if (!is_array($element)) { $out[] = $element; continue; }
            if (($element['elType'] ?? '') === 'widget') {
                if (($element['widgetType'] ?? '') === 'go-testimonial') {
                    $element = self::fill_testimonial($element, $reviews, $single);
                    if ($element === null) continue;
                }
                $out[] = $element;
                continue;
            }
            if (!$reviews && self::is_testimonial_section($element)) continue; // Heading + empty slider would look broken.
            if (!empty($element['elements']) && is_array($element['elements'])) {
                $element['elements'] = self::testimonial_elements($element['elements'], $reviews, $single);
            }
            $out[] = $element;
        }
        return $out;
    }

    private static function widget_types(array $element) {
        $types = [];
        foreach ((array) ($element['elements'] ?? []) as $child) {
            if (!is_array($child)) continue;
            if (($child['elType'] ?? '') === 'widget') $types[] = (string) ($child['widgetType'] ?? '');
            $types = array_merge($types, self::widget_types($child));
        }
        return $types;
    }

    private static function is_testimonial_section(array $element) {
        $types = self::widget_types($element);
        return $types && count($types) <= 8 && in_array('go-testimonial', $types, true) && !array_diff($types, self::SECTION_WIDGETS);
    }

    private static function review_item(array $item, array $review) {
        $avatar = get_avatar_url($review['author'], ['size' => 160, 'default' => 'mystery']);
        $item['_id'] = 'rmr' . $review['id'];
        $item['feedback'] = esc_html($review['text']);
        $item['name'] = esc_html($review['name']);
        $item['designation'] = 'Verified customer';
        $item['rating'] = $review['rating'];
        $item['rating_logo'] = ['url' => self::star_image($review['rating']), 'id' => '', 'alt' => $review['rating'] . ' out of 5 stars', 'source' => 'library'];
        $item['authore'] = ['url' => $avatar ?: '', 'id' => '', 'alt' => $review['name'], 'source' => 'library'];
        return $item;
    }

    /** Slider styles get the review list; one-testimonial styles get the next review in page order (or are removed). */
    private static function fill_testimonial(array $widget, array $reviews, &$single) {
        $settings = is_array($widget['settings'] ?? null) ? $widget['settings'] : [];
        $style = (string) ($settings['style'] ?? '1');
        if (in_array($style, ['3', '5'], true)) {
            if (!$reviews) return null;
            $template = is_array($settings['testimonials'][0] ?? null) ? $settings['testimonials'][0] : [];
            $settings['testimonials'] = array_map(static function ($review) use ($template) { return self::review_item($template, $review); }, $reviews);
        } else {
            $review = $reviews[$single++] ?? null;
            if (!$review) return null;
            $settings = self::review_item($settings, $review);
            $settings['rating_img'] = ['url' => self::star_image(1), 'id' => '', 'alt' => 'star', 'source' => 'library'];
        }
        $widget['settings'] = $settings;
        return $widget;
    }

    /** [rackmonk_rating] → "4.9", [rackmonk_rating show="count"] → "16,012", show="sentence" → full sentence. */
    public static function rating_shortcode($atts) {
        $atts = shortcode_atts(['show' => 'rating'], $atts, 'rackmonk_rating');
        $summary = self::summary();
        switch ($atts['show']) {
            case 'count': return esc_html(number_format_i18n($summary['count']));
            case 'short_count': return esc_html(self::compact_count($summary['count']));
            case 'sentence': return esc_html(self::sentence($summary));
            default: return esc_html(self::rating_text($summary['rating']));
        }
    }

    /* ------------------------------------------------------------ customer form */

    public static function assets() {
        wp_register_style('rackmonk-reviews', RACKMONK_WHMCS_URL . 'assets/css/reviews.css', [], filemtime(RACKMONK_WHMCS_DIR . 'assets/css/reviews.css'));
        if (function_exists('is_account_page') && is_account_page()) wp_enqueue_style('rackmonk-reviews');
    }

    private static function own_review($user_id) {
        $ids = get_posts(['post_type' => self::POST_TYPE, 'post_status' => ['pending', 'publish', 'draft'], 'author' => (int) $user_id, 'numberposts' => 1, 'fields' => 'ids', 'orderby' => 'date', 'order' => 'DESC', 'suppress_filters' => true]);
        return $ids ? get_post((int) $ids[0]) : null;
    }

    private static function is_staff() {
        return current_user_can('edit_posts');
    }

    public static function handle_submit() {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !isset($_POST['rm_review_action'])) return;
        self::$error = '';
        self::$posted = [
            'rating' => absint($_POST['rm_review_rating'] ?? 0),
            'text' => sanitize_textarea_field(wp_unslash((string) ($_POST['rm_review_text'] ?? ''))),
        ];
        if (!is_user_logged_in()) { self::$error = 'Please sign in to your Rackmonk account to leave a review.'; return; }
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash((string) ($_POST['rm_review_nonce'] ?? ''))), self::NONCE)) { self::$error = 'Your session expired. Reload the page and try again.'; return; }
        if (self::is_staff()) { self::$error = 'Staff accounts cannot leave customer reviews.'; return; }
        $rating = self::$posted['rating'];
        $text = trim(self::$posted['text']);
        if ($rating < 1 || $rating > 5) { self::$error = 'Choose a rating from 1 to 5 stars.'; return; }
        $length = mb_strlen($text);
        if ($length < self::MIN_LENGTH) { self::$error = 'Please write at least ' . self::MIN_LENGTH . ' characters about your experience.'; return; }
        if ($length > self::MAX_LENGTH) { self::$error = 'Please keep your review under ' . self::MAX_LENGTH . ' characters.'; return; }

        $user = wp_get_current_user();
        $existing = self::own_review($user->ID);
        $data = [
            'post_type' => self::POST_TYPE,
            'post_status' => 'pending',
            'post_title' => self::public_name($user),
            'post_content' => $text,
            'post_author' => $user->ID,
        ];
        if ($existing) $data['ID'] = $existing->ID;
        $id = $existing ? wp_update_post(wp_slash($data), true) : wp_insert_post(wp_slash($data), true);
        if (is_wp_error($id) || !$id) { self::$error = 'Your review could not be saved. Please try again.'; return; }
        update_post_meta($id, self::META_RATING, $rating);
        if ($existing && $existing->post_status === 'publish') self::refresh();
        self::notify_admin((int) $id, $rating, $text, (bool) $existing);

        $back = wp_get_referer() ?: (function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/'));
        wp_safe_redirect(add_query_arg('rm_review', 'sent', remove_query_arg('rm_review', $back)) . '#rackmonk-review');
        exit;
    }

    /** "Rahul Sharma" → "Rahul S." */
    private static function public_name(WP_User $user) {
        $first = trim((string) $user->first_name);
        $last = trim((string) $user->last_name);
        if ($first !== '') return $first . ($last !== '' ? ' ' . mb_strtoupper(mb_substr($last, 0, 1)) . '.' : '');
        $name = trim((string) $user->display_name);
        return $name !== '' && strpos($name, '@') === false ? $name : 'Rackmonk customer';
    }

    private static function notify_admin($id, $rating, $text, $updated) {
        $to = get_option('admin_email');
        if (!$to) return;
        $subject = sprintf('[%s] %s review waiting for approval (%d/5)', wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES), $updated ? 'Updated' : 'New', $rating);
        $body = $text . "\n\nApprove or edit it: " . admin_url('post.php?post=' . (int) $id . '&action=edit');
        wp_mail($to, $subject, $body);
    }

    public static function account_card() {
        echo self::form_html(true); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
    }

    public static function shortcode() {
        wp_enqueue_style('rackmonk-reviews');
        return self::form_html(false);
    }

    private static function stars($rating) {
        $rating = max(0, min(5, (int) $rating));
        return '<span class="rm-review-stars-text" aria-label="' . esc_attr($rating . ' out of 5 stars') . '">' . str_repeat('★', $rating) . '<span>' . str_repeat('★', 5 - $rating) . '</span></span>';
    }

    private static function form_html($in_account) {
        $html = '<section class="rm-review' . ($in_account ? ' rm-acct-card' : '') . '" id="rackmonk-review">';
        $html .= '<h3 class="rm-review-title' . ($in_account ? ' rm-acct-subtitle' : '') . '">Rate your Rackmonk experience</h3>';

        if (!is_user_logged_in()) {
            $login = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url();
            return $html . '<p class="rm-review-intro">Reviews come from Rackmonk customers. <a href="' . esc_url($login) . '">Sign in to your account</a> to rate us.</p></section>';
        }
        if (self::is_staff()) {
            return $html . '<p class="rm-review-intro">You are signed in with a staff account. Customer reviews can only be written from a customer account.</p></section>';
        }

        $existing = self::own_review(get_current_user_id());
        $rating = self::$posted['rating'] ?? ($existing ? (int) get_post_meta($existing->ID, self::META_RATING, true) : 0);
        $text = self::$posted['text'] ?? ($existing ? $existing->post_content : '');

        $html .= '<p class="rm-review-intro">Tell other customers how hosting with Rackmonk has been. Your first name and last initial are shown with your review after we approve it.</p>';
        if (isset($_GET['rm_review']) && $_GET['rm_review'] === 'sent' && !self::$error) {
            $html .= '<div class="rm-review-notice rm-review-notice--ok" role="status">Thank you! Your review was sent and will appear on the website once it is approved.</div>';
        }
        if (self::$error) $html .= '<div class="rm-review-notice rm-review-notice--error" role="alert">' . esc_html(self::$error) . '</div>';
        if ($existing && !self::$error && !isset($_GET['rm_review'])) {
            $status = $existing->post_status === 'publish' ? 'is live on the website' : 'is waiting for approval';
            $html .= '<div class="rm-review-notice" role="status">Your review ' . self::stars((int) get_post_meta($existing->ID, self::META_RATING, true)) . ' ' . esc_html($status) . '. Updating it sends it for approval again.</div>';
        }

        $html .= '<form method="post" class="rm-review-form" action="' . esc_url(remove_query_arg('rm_review')) . '#rackmonk-review">';
        $html .= '<fieldset class="rm-review-rating"><legend>Your rating <span aria-hidden="true">*</span></legend><div class="rm-review-star-input">';
        for ($i = 5; $i >= 1; $i--) {
            $html .= '<input type="radio" id="rm_review_rating_' . $i . '" name="rm_review_rating" value="' . $i . '"' . checked($rating, $i, false) . ($i === 5 ? ' required' : '') . '>'
                . '<label for="rm_review_rating_' . $i . '" title="' . esc_attr($i . ' ' . ($i === 1 ? 'star' : 'stars')) . '"><span class="screen-reader-text">' . esc_html($i . ' ' . ($i === 1 ? 'star' : 'stars')) . '</span></label>';
        }
        $html .= '</div></fieldset>';
        $html .= '<p class="rm-review-field' . ($in_account ? ' rm-acct-field' : '') . '"><label for="rm_review_text">Your review <span aria-hidden="true">*</span></label>'
            . '<textarea id="rm_review_text" name="rm_review_text" rows="4" minlength="' . self::MIN_LENGTH . '" maxlength="' . self::MAX_LENGTH . '" required placeholder="What do you like about Rackmonk? Speed, support, pricing…">' . esc_textarea($text) . '</textarea></p>';
        $html .= '<input type="hidden" name="rm_review_action" value="submit">' . wp_nonce_field(self::NONCE, 'rm_review_nonce', true, false);
        $html .= '<button type="submit" class="rm-review-submit' . ($in_account ? ' rm-acct-button' : '') . '">' . esc_html($existing ? 'Update review' : 'Submit review') . '</button>';
        return $html . '</form></section>';
    }

    /* ------------------------------------------------------------------ wp-admin */

    public static function admin_menu() {
        add_submenu_page('edit.php?post_type=' . self::POST_TYPE, 'Rating Settings', 'Rating Settings', 'manage_options', 'rackmonk-rating-settings', [__CLASS__, 'settings_page']);
        // Pending count bubble, like Comments.
        $pending = (int) (wp_count_posts(self::POST_TYPE)->pending ?? 0);
        if (!$pending) return;
        global $menu;
        foreach ((array) $menu as $i => $item) {
            if (($item[2] ?? '') === 'edit.php?post_type=' . self::POST_TYPE) {
                $menu[$i][0] .= ' <span class="awaiting-mod count-' . $pending . '"><span class="pending-count">' . number_format_i18n($pending) . '</span></span>';
                break;
            }
        }
    }

    public static function register_settings() {
        register_setting('rackmonk_rating', self::SETTINGS, ['type' => 'array', 'sanitize_callback' => [__CLASS__, 'sanitize_settings']]);
    }

    public static function sanitize_settings($input) {
        $input = is_array($input) ? $input : [];
        $sentence = sanitize_text_field((string) ($input['sentence'] ?? ''));
        return [
            'base_rating' => round(min(5, max(0, (float) str_replace(',', '.', (string) ($input['base_rating'] ?? 0)))), 1),
            'base_count' => max(0, (int) preg_replace('/\D/', '', (string) ($input['base_count'] ?? 0))),
            'sentence' => $sentence !== '' ? $sentence : '{rating} out of 5 stars based on {count} reviews',
        ];
    }

    public static function settings_page() {
        if (!current_user_can('manage_options')) return;
        $s = self::settings();
        $summary = self::summary();
        $pending = (int) (wp_count_posts(self::POST_TYPE)->pending ?? 0);
        ?>
        <div class="wrap">
            <h1>Rating Settings</h1>
            <div class="notice notice-info inline"><p>
                <strong>Website rating now: <?php echo esc_html(self::rating_text($summary['rating'])); ?> out of 5 from <?php echo esc_html(number_format_i18n($summary['count'])); ?> reviews</strong>
                — <?php echo esc_html(number_format_i18n((int) $s['base_count'])); ?> base reviews at <?php echo esc_html(self::rating_text($s['base_rating'])); ?>
                + <?php echo esc_html(number_format_i18n($summary['reviews'])); ?> approved customer reviews.
                <?php if ($pending): ?><a href="<?php echo esc_url(admin_url('edit.php?post_status=pending&post_type=' . self::POST_TYPE)); ?>"><?php echo esc_html(sprintf(_n('%s review is waiting for approval.', '%s reviews are waiting for approval.', $pending), number_format_i18n($pending))); ?></a><?php endif; ?>
            </p><p>Every rating on the website (homepage, hosting pages, testimonials) shows this number. The rating and review count typed into Elementor widgets are replaced by it.</p></div>
            <form method="post" action="options.php">
                <?php settings_fields('rackmonk_rating'); ?>
                <table class="form-table" role="presentation">
                    <tr><th scope="row"><label for="rm_base_rating">Base rating</label></th>
                        <td><input id="rm_base_rating" name="<?php echo esc_attr(self::SETTINGS); ?>[base_rating]" type="number" min="0" max="5" step="0.1" value="<?php echo esc_attr($s['base_rating']); ?>" class="small-text"> out of 5
                        <p class="description">Your rating from before website reviews, e.g. your Google or Trustpilot rating. The starting value 4.9 came from the old homepage text — replace it with your real rating.</p></td></tr>
                    <tr><th scope="row"><label for="rm_base_count">Base number of reviews</label></th>
                        <td><input id="rm_base_count" name="<?php echo esc_attr(self::SETTINGS); ?>[base_count]" type="number" min="0" step="1" value="<?php echo esc_attr($s['base_count']); ?>" class="regular-text">
                        <p class="description">How many reviews the base rating comes from. Use 0 to show only reviews written on this website. (Starting value 16,000 came from the old "16K+" homepage text.)</p></td></tr>
                    <tr><th scope="row"><label for="rm_sentence">Long rating sentence</label></th>
                        <td><input id="rm_sentence" name="<?php echo esc_attr(self::SETTINGS); ?>[sentence]" type="text" value="<?php echo esc_attr($s['sentence']); ?>" class="large-text">
                        <p class="description">Used under testimonials. <code>{rating}</code> = average, <code>{count}</code> = number of reviews.</p></td></tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Collecting reviews</h2>
            <p>Signed-in customers see a <strong>Rate your Rackmonk experience</strong> form on their <a href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/')); ?>" target="_blank">My Account</a> page. To add the form to any other page, place the shortcode <code>[rackmonk_review_form]</code> there (Elementor: Shortcode widget). New reviews arrive as <em>Pending</em> and count only after you approve them.</p>
            <p>The "Feedback from Real Users" testimonial sliders show the newest <?php echo (int) self::SLIDER_LIMIT; ?> approved reviews with the customer's own star rating. Until the first review is approved, that section is hidden on every page. The demo testimonials typed into those Elementor widgets are no longer shown.</p>
        </div>
        <?php
    }

    public static function columns($columns) {
        $out = [];
        foreach ($columns as $key => $label) {
            if ($key === 'title') {
                $out['title'] = 'Name shown';
                $out['rm_rating'] = 'Rating';
                $out['rm_review'] = 'Review';
                $out['rm_customer'] = 'Customer account';
                continue;
            }
            $out[$key] = $label;
        }
        return $out;
    }

    public static function column($column, $id) {
        $post = get_post($id);
        if ($column === 'rm_rating') echo self::stars((int) get_post_meta($id, self::META_RATING, true)) . ($post->post_status === 'pending' ? '<br><strong style="color:#b26200">Waiting for approval</strong>' : ''); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in stars().
        if ($column === 'rm_review') echo esc_html(wp_trim_words($post->post_content, 30));
        if ($column === 'rm_customer') {
            $user = get_userdata((int) $post->post_author);
            echo $user ? '<a href="' . esc_url(get_edit_user_link($user->ID)) . '">' . esc_html($user->user_email) . '</a>' : '—';
        }
    }

    public static function row_actions($actions, $post) {
        if ($post->post_type !== self::POST_TYPE || !current_user_can('edit_post', $post->ID)) return $actions;
        unset($actions['inline hide-if-no-js']);
        $make = static function ($status, $label) use ($post) {
            $url = wp_nonce_url(admin_url('admin-post.php?action=rackmonk_review_status&status=' . $status . '&post=' . $post->ID), 'rackmonk_review_status_' . $post->ID);
            return '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        };
        if ($post->post_status === 'publish') $actions = ['rm_unapprove' => $make('pending', 'Unapprove')] + $actions;
        elseif ($post->post_status !== 'trash') $actions = ['rm_approve' => '<strong>' . $make('publish', 'Approve') . '</strong>'] + $actions;
        return $actions;
    }

    public static function quick_status() {
        $id = absint($_GET['post'] ?? 0);
        $status = sanitize_key($_GET['status'] ?? '');
        if (!$id || !in_array($status, ['publish', 'pending'], true) || get_post_type($id) !== self::POST_TYPE) wp_die('Invalid review.');
        check_admin_referer('rackmonk_review_status_' . $id);
        if (!current_user_can($status === 'publish' ? 'publish_posts' : 'edit_post', $id) || !current_user_can('edit_post', $id)) wp_die('You are not allowed to change this review.');
        wp_update_post(['ID' => $id, 'post_status' => $status]);
        wp_safe_redirect(add_query_arg('rm_review_updated', $status, wp_get_referer() ?: admin_url('edit.php?post_type=' . self::POST_TYPE)));
        exit;
    }

    public static function admin_notice() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== self::POST_TYPE || $screen->base !== 'edit') return;
        if (isset($_GET['rm_review_updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . ($_GET['rm_review_updated'] === 'publish' ? 'Review approved — it now counts towards the website rating.' : 'Review moved back to pending.') . '</p></div>';
        }
        $summary = self::summary();
        echo '<div class="notice notice-info"><p>Website rating: <strong>' . esc_html(self::rating_text($summary['rating'])) . ' out of 5 from ' . esc_html(number_format_i18n($summary['count'])) . ' reviews</strong>. Only approved reviews count. <a href="' . esc_url(admin_url('edit.php?post_type=' . self::POST_TYPE . '&page=rackmonk-rating-settings')) . '">Rating Settings</a></p></div>';
    }

    public static function meta_box() {
        add_meta_box('rackmonk_review_rating', 'Rating', [__CLASS__, 'meta_box_html'], self::POST_TYPE, 'side', 'high');
    }

    public static function meta_box_html($post) {
        $rating = (int) get_post_meta($post->ID, self::META_RATING, true);
        wp_nonce_field('rackmonk_review_meta', 'rackmonk_review_meta_nonce');
        echo '<p><label for="rm_review_rating_admin" class="screen-reader-text">Rating</label><select id="rm_review_rating_admin" name="rm_review_rating_admin">';
        for ($i = 5; $i >= 1; $i--) echo '<option value="' . $i . '"' . selected($rating, $i, false) . '>' . str_repeat('★', $i) . ' (' . $i . ')</option>';
        echo '</select></p>';
        $user = get_userdata((int) $post->post_author);
        if ($user) echo '<p>Customer: <a href="' . esc_url(get_edit_user_link($user->ID)) . '">' . esc_html($user->user_email) . '</a></p>';
        echo '<p class="description">Click <strong>Publish</strong> to approve. Only published reviews count towards the website rating.</p>';
    }

    public static function save_meta($id, $post) {
        if (!isset($_POST['rackmonk_review_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rackmonk_review_meta_nonce'])), 'rackmonk_review_meta')) return;
        if (!current_user_can('edit_post', $id) || wp_is_post_revision($id)) return;
        $rating = absint($_POST['rm_review_rating_admin'] ?? 0);
        if ($rating < 1 || $rating > 5) return;
        update_post_meta($id, self::META_RATING, $rating);
        if ($post->post_status === 'publish') self::refresh();
    }
}
