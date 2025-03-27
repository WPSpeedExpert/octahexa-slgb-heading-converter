<?php
/**
 * Plugin Name:       OctaHexa SLGB Heading Converter
 * Plugin URI:        https://octahexa.com/plugins/octahexa-slgb-heading-converter
 * Description:       Converts slgb/h1–h6 blocks to core/heading blocks with proper HTML formatting.
 * Version:           1.0.1
 * Author:            OctaHexa
 * Author URI:        https://octahexa.com
 * Text Domain:       octahexa-slgb-converter
 * Domain Path:       /languages
 * Requires PHP:      7.4
 * Requires at least: 5.6
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: https://github.com/WPSpeedExpert/octahexa-slgb-heading-converter
 * GitHub Branch:     main
 */

if (!defined('ABSPATH')) exit;

/**
 * Run the conversion when triggered via admin URL param.
 */
function oh_convert_slgb_headings() {
    if (!current_user_can('manage_options')) return;

    if (isset($_GET['oh_convert_headings']) && $_GET['oh_convert_headings'] === '1') {
        $posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            's'              => 'wp:slgb/h',
        ]);

        $count = 0;

        foreach ($posts as $post) {
            $original = $post->post_content;
            $updated  = preg_replace_callback(
                '/<!-- wp:slgb\/h([1-6]) {"text":"(.*?)"} \/-->/',
                function ($matches) {
                    $level = $matches[1];
                    $escaped = $matches[2];
                    $decoded = json_decode('"' . $escaped . '"');

                    return sprintf(
                        '<!-- wp:heading {"level":%d} --><h%d>%s</h%d><!-- /wp:heading -->',
                        $level, $level, $decoded, $level
                    );
                },
                $original
            );

            if ($original !== $updated) {
                wp_update_post([
                    'ID' => $post->ID,
                    'post_content' => $updated,
                ]);
                $count++;
            }
        }

        add_action('admin_notices', function () use ($count) {
            echo '<div class="notice notice-success"><p>';
            echo sprintf('SLGB heading blocks converted in %d post(s).', $count);
            echo '</p></div>';
        });
    }
}
add_action('admin_init', 'oh_convert_slgb_headings');

/**
 * Add a page in Tools to trigger the conversion.
 */
function oh_register_slgb_converter_page() {
    add_management_page(
        'Convert SLGB Headings',
        'Convert SLGB Headings',
        'manage_options',
        'oh-slgb-converter',
        'oh_render_slgb_converter_page'
    );
}
add_action('admin_menu', 'oh_register_slgb_converter_page');

/**
 * Render the admin page UI.
 */
function oh_render_slgb_converter_page() {
    $url = admin_url('tools.php?page=oh-slgb-converter&oh_convert_headings=1');
    ?>
    <div class="wrap">
        <h1>Convert SLGB Custom Headings</h1>
        <p>This tool scans all posts and converts <code>slgb/h1–h6</code> blocks to native <code>core/heading</code> blocks.</p>
        <a href="<?php echo esc_url($url); ?>" id="oh-convert-run" class="button button-primary">Run Conversion</a>
        <p id="oh-progress-msg" style="margin-top: 10px;"></p>
    </div>

    <script>
        document.getElementById('oh-convert-run')?.addEventListener('click', function () {
            const msg = document.getElementById('oh-progress-msg');
            msg.textContent = 'Processing… Please wait.';
        });
    </script>
    <?php
}

/**
 * Add "Settings" link in Plugins list
 */
function oh_slgb_plugin_action_links($links) {
    $url = admin_url('tools.php?page=oh-slgb-converter');
    $settings_link = '<a href="' . esc_url($url) . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'oh_slgb_plugin_action_links');
