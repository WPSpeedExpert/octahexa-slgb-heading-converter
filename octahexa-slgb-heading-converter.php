<?php
/**
 * Plugin Name:       OctaHexa SLGB Block Converter
 * Plugin URI:        https://octahexa.com/plugins/octahexa-slgb-block-converter
 * Description:       Converts slgb/h1-h6, slgb/emph, and slgb/image blocks to core blocks with proper HTML formatting while preserving classes and styling.
 * Version:           1.2.0
 * Author:            OctaHexa
 * Author URI:        https://octahexa.com
 * Text Domain:       octahexa-slgb-converter
 * Domain Path:       /languages
 * Requires PHP:      7.4
 * Requires at least: 5.6
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: https://github.com/WPSpeedExpert/octahexa-slgb-block-converter
 * GitHub Branch:     main
 */

if (!defined('ABSPATH')) exit;

/**
 * Run the conversion when triggered via admin URL param.
 */
function oh_convert_slgb_blocks() {
    if (!current_user_can('manage_options')) return;

    if (isset($_GET['oh_convert_blocks']) && $_GET['oh_convert_blocks'] === '1') {
        $posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            's'              => 'wp:slgb/',
        ]);

        $count = 0;
        $heading_count = 0;
        $emph_count = 0;
        $image_count = 0;

        foreach ($posts as $post) {
            $original = $post->post_content;
            
            // Convert heading blocks - now with class preservation
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/h([1-6]) (.*?) \/-->/',
                function ($matches) use (&$heading_count) {
                    $level = $matches[1];
                    $attrs = json_decode('{' . preg_replace('/^\{(.*)\}$/', '$1', $matches[2]) . '}', true);
                    
                    // Extract text content
                    $text = isset($attrs['text']) ? json_decode('"' . $attrs['text'] . '"') : '';
                    
                    // Extract any custom classes
                    $className = isset($attrs['className']) ? $attrs['className'] : '';
                    $customClasses = !empty($className) ? ' class="' . esc_attr($className) . '"' : '';
                    
                    $heading_count++;
                    
                    // Include the class in the converted heading
                    return sprintf(
                        '<!-- wp:heading {"level":%d%s} --><h%d%s>%s</h%d><!-- /wp:heading -->',
                        $level,
                        !empty($className) ? ',"className":"' . esc_attr($className) . '"' : '',
                        $level, 
                        $customClasses,
                        $text, 
                        $level
                    );
                },
                $original
            );
            
            // Convert emphasis blocks to paragraphs - now with class preservation
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/emph (.*?) \/-->/',
                function ($matches) use (&$emph_count) {
                    // Parse the JSON attributes safely
                    $attrString = $matches[1];
                    preg_match('/"title":"(.*?)","text":"(.*?)"/', $attrString, $contentMatches);
                    
                    if (!empty($contentMatches) && count($contentMatches) >= 3) {
                        $title = json_decode('"' . $contentMatches[1] . '"');
                        $text = json_decode('"' . $contentMatches[2] . '"');
                        
                        // Extract custom classes if they exist
                        $className = '';
                        if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                            $className = $classMatch[1];
                        }
                        
                        // Add a default class if none exists
                        $className = !empty($className) ? $className : 'slgb-emph';
                        $customClasses = ' class="' . esc_attr($className) . '"';
                        
                        $emph_count++;
                        
                        // Format: Strong title followed by paragraph content, preserving classes
                        return sprintf(
                            '<!-- wp:paragraph {"className":"%s"} --><p%s><strong>%s</strong> %s</p><!-- /wp:paragraph -->',
                            esc_attr($className),
                            $customClasses,
                            $title, 
                            $text
                        );
                    }
                    
                    // If pattern doesn't match exactly, return original to prevent data loss
                    return $matches[0];
                },
                $updated
            );
            
            // Convert image blocks to core/image blocks
            $updated = preg_replace_callback(
                '/<!-- wp:slgb\/image (.*?) -->\s*<img (.*?)\/>\s*<!-- \/wp:slgb\/image -->/',
                function ($matches) use (&$image_count) {
                    $attrString = $matches[1];
                    $imgHtml = $matches[2];
                    
                    // Extract required attributes from the image HTML
                    preg_match('/src="([^"]*)"/', $imgHtml, $srcMatch);
                    preg_match('/width="([^"]*)"/', $imgHtml, $widthMatch);
                    preg_match('/height="([^"]*)"/', $imgHtml, $heightMatch);
                    preg_match('/alt="([^"]*)"/', $imgHtml, $altMatch);
                    
                    if (!empty($srcMatch)) {
                        $src = $srcMatch[1];
                        $width = !empty($widthMatch) ? (int)$widthMatch[1] : 0;
                        $height = !empty($heightMatch) ? (int)$heightMatch[1] : 0;
                        $alt = !empty($altMatch) ? $altMatch[1] : '';
                        
                        // Extract id from data if available
                        preg_match('/"id":"(\d+)"/', $attrString, $idMatch);
                        $id = !empty($idMatch) ? (int)$idMatch[1] : 0;
                        
                        // Extract link if available
                        $link = '';
                        $linkOpensInNewTab = false;
                        
                        if (preg_match('/"link":"([^"]*)"/', $attrString, $linkMatch)) {
                            $link = $linkMatch[1];
                        }
                        
                        if (preg_match('/"openInNewTab":(true|false)/', $attrString, $newTabMatch)) {
                            $linkOpensInNewTab = $newTabMatch[1] === 'true';
                        }
                        
                        // Extract CSS classes
                        $className = '';
                        if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                            $className = $classMatch[1];
                        }
                        
                        // Check if image has alignment
                        $align = '';
                        if (preg_match('/"align":"([^"]*)"/', $attrString, $alignMatch)) {
                            $align = $alignMatch[1];
                        }
                        
                        // Check if image has caption
                        $caption = '';
                        if (preg_match('/"caption":"([^"]*)"/', $attrString, $captionMatch)) {
                            $caption = json_decode('"' . $captionMatch[1] . '"');
                        }
                        
                        $image_count++;
                        
                        // Build the new block attributes
                        $blockAttrs = array(
                            'id' => $id,
                            'sizeSlug' => 'full',
                        );
                        
                        if (!empty($className)) {
                            $blockAttrs['className'] = $className;
                        }
                        
                        if (!empty($align)) {
                            $blockAttrs['align'] = $align;
                        }
                        
                        if (!empty($width) && !empty($height)) {
                            $blockAttrs['width'] = $width;
                            $blockAttrs['height'] = $height;
                        }
                        
                        // Convert to core/image block
                        $blockAttrString = json_encode($blockAttrs);
                        
                        // If it should be a linked image
                        if (!empty($link)) {
                            $targetAttr = $linkOpensInNewTab ? ' target="_blank" rel="noreferrer noopener"' : '';
                            
                            $output = sprintf(
                                '<!-- wp:image %s --><figure class="wp-block-image size-full"><a href="%s"%s><img src="%s" alt="%s" class="wp-image-%d"%s/></a>%s</figure><!-- /wp:image -->',
                                $blockAttrString,
                                esc_url($link),
                                $targetAttr,
                                esc_url($src),
                                esc_attr($alt),
                                $id,
                                !empty($width) && !empty($height) ? sprintf(' width="%d" height="%d"', $width, $height) : '',
                                !empty($caption) ? sprintf('<figcaption class="wp-element-caption">%s</figcaption>', $caption) : ''
                            );
                        } else {
                            $output = sprintf(
                                '<!-- wp:image %s --><figure class="wp-block-image size-full"><img src="%s" alt="%s" class="wp-image-%d"%s/>%s</figure><!-- /wp:image -->',
                                $blockAttrString,
                                esc_url($src),
                                esc_attr($alt),
                                $id,
                                !empty($width) && !empty($height) ? sprintf(' width="%d" height="%d"', $width, $height) : '',
                                !empty($caption) ? sprintf('<figcaption class="wp-element-caption">%s</figcaption>', $caption) : ''
                            );
                        }
                        
                        return $output;
                    }
                    
                    // If we couldn't extract the necessary information, return the original
                    return $matches[0];
                },
                $updated
            );

            if ($original !== $updated) {
                wp_update_post([
                    'ID' => $post->ID,
                    'post_content' => $updated,
                ]);
                $count++;
            }
        }

        add_action('admin_notices', function () use ($count, $heading_count, $emph_count, $image_count) {
            echo '<div class="notice notice-success"><p>';
            echo sprintf('SLGB blocks converted in %d post(s): %d heading(s), %d emphasis block(s), and %d image block(s).', 
                $count, $heading_count, $emph_count, $image_count);
            echo '</p></div>';
        });
    }
}
add_action('admin_init', 'oh_convert_slgb_blocks');

/**
 * Add a page in Tools to trigger the conversion.
 */
function oh_register_slgb_converter_page() {
    add_management_page(
        'Convert SLGB Blocks',
        'Convert SLGB Blocks',
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
    $url = admin_url('tools.php?page=oh-slgb-converter&oh_convert_blocks=1');
    ?>
    <div class="wrap">
        <h1>Convert SLGB Custom Blocks</h1>
        
        <div class="card" style="max-width: 800px; margin-bottom: 20px; padding: 20px;">
            <h2>About This Tool</h2>
            <p>This tool scans all posts and converts custom SLGB blocks to native WordPress core blocks:</p>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li><code>slgb/h1</code> through <code>slgb/h6</code> → <code>core/heading</code> with proper heading level</li>
                <li><code>slgb/emph</code> → <code>core/paragraph</code> with preserved formatting</li>
                <li><code>slgb/image</code> → <code>core/image</code> with preserved attributes, links, and captions</li>
            </ul>
            <p><strong>CSS Classes Preserved:</strong> The plugin maintains all custom CSS classes from your original blocks. For emphasis blocks without classes, a default <code>slgb-emph</code> class is added to help with styling.</p>
            <p><strong>Important:</strong> Always back up your database before running this conversion.</p>
        </div>
        
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
