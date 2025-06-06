<?php
/**
 * Plugin Name:       OctaHexa SLGB Block Converter
 * Plugin URI:        https://octahexa.com/plugins/octahexa-slgb-block-converter
 * Description:       Converts SLGB custom blocks to core blocks with proper HTML formatting while preserving classes and styling.
 * Version:           2.2.21
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
 * Table of Contents
 * 
 * 1. Main Conversion Function
 *   1.1. Original Block Type Handlers
 *     1.1.1. Heading Block Conversion
 *     1.1.2. Emphasis Block Conversion
 *     1.1.3. Image Block Conversion
 *     1.1.4. Table Block Conversion
 *     1.1.5. Subscribe Block Conversion
 *     1.1.6. Compare Block Conversion
 *     1.1.7. Hints Block Conversion
 *     1.1.8. Quote Block Conversion
 *     1.1.9. Miniature Block Conversion
 *     1.1.10. CTA Block Conversion
 *     1.1.11. GB Emphasis Block Conversion
 *   1.2. New Block Type Handlers
 *     1.2.1. Postimage Block Conversion
 *     1.2.2. Dos-Donts Block Conversion
 *     1.2.3. YouTube Block Conversion
 *     1.2.4. P-YouTube Block Conversion
 *     1.2.5. P-Btns Block Conversion
 *     1.2.6. Post-Quote Block Conversion
 *     1.2.7. P-Hints-Row and P-Hints-Cell Block Conversion
 *     1.2.8. P-Comparison and P-Comparison-Row Block Conversion
 *   1.3. Conversion Results Display
 * 2. Admin Interface
 *   2.1. Register Admin Page
 *   2.2. Render Admin Page UI
 * 3. Plugin Actions and Styles
 *   3.1. Add Plugin Settings Link
 */

/**
 * 1. Main Conversion Function
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
        $table_count = 0;
        $subscribe_count = 0;
        $compare_count = 0;
        $hints_count = 0;
        $quote_count = 0;
        $miniature_count = 0;
        $cta_count = 0;
        $gb_emph_count = 0;
        
        // New block type counters
        $postimage_count = 0;
        $dosdont_count = 0;
        $youtube_count = 0;
        $pytube_count = 0;
        $pbtns_count = 0;
        $post_quote_count = 0;
        $p_hints_count = 0;
        $p_comparison_count = 0;

        foreach ($posts as $post) {
            $original = $post->post_content;
            $updated = $original;

        /**
         * 1.1. Original Block Type Handlers
         */
            
        /**
         * 1.1.1. Heading Block Conversion
         */
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/h([1-6]) (.*?) \/-->/',
                function ($matches) use (&$heading_count) {
                    $level = $matches[1];
                    $attrs = json_decode('{' . preg_replace('/^\{(.*)\}$/', '$1', $matches[2]) . '}', true);
                    
                    // Extract text content and handle URL-encoded entities
                    $text = '';
                    if (isset($attrs['text'])) {
                        // First replace common URL-encoded characters
                        $decoded_text = str_replace(['u003c', 'u003e', 'u0026', 'u0022', 'u0027', 'u003d'], ['<', '>', '&', '"', "'", '='], $attrs['text']);
                        
                        // Handle more complex unicode escape sequences
                        $decoded_text = preg_replace('/u([0-9a-fA-F]{4})/', '\\u$1', $decoded_text);
                        
                        // Try to decode as JSON string with proper handling of escapes
                        $json_decoded = @json_decode('"' . $decoded_text . '"');
                        if ($json_decoded !== null) {
                            $decoded_text = $json_decoded;
                        }
                        
                        // Final HTML entity decode pass
                        $text = html_entity_decode($decoded_text);
                    }
                    
                    // Extract any custom classes - preserve exactly as they are
                    $className = isset($attrs['className']) ? $attrs['className'] : '';
                    $customClasses = !empty($className) ? ' class="' . esc_attr($className) . '"' : '';
                    
                    // Extract chapter_prefix if it exists
                    $chapter_prefix = isset($attrs['chapter_prefix']) ? $attrs['chapter_prefix'] : '';
                    
                    // Check if chapter attributes exist
                    $chapter_title = isset($attrs['chapter_title']) && $attrs['chapter_title'] ? true : false;
                    $anchor = isset($attrs['anchor']) ? $attrs['anchor'] : '';
                    
                    // Build attributes string for the heading block
                    $heading_attrs = sprintf('"level":%d', $level);
                    
                    if (!empty($className)) {
                        $heading_attrs .= sprintf(',"className":"%s"', esc_attr($className));
                    }
                    
                    if (!empty($anchor)) {
                        $heading_attrs .= sprintf(',"anchor":"%s"', esc_attr($anchor));
                    }
                    
                    // Only include chapter attribute if it exists
                    if ($chapter_title) {
                        $heading_attrs .= ',"chapter":true';
                    }
                    
                    $heading_count++;
                    
                    // Prepend chapter_prefix to the text content if it exists
                    $display_text = $text;
                    if (!empty($chapter_prefix)) {
                        $display_text = $chapter_prefix . ' ' . $display_text;
                    }
                    
                    // Include the class and chapter attributes in the converted heading
                    return sprintf(
                        '<!-- wp:heading {%s} --><h%d%s>%s</h%d><!-- /wp:heading -->',
                        $heading_attrs,
                        $level, 
                        $customClasses,
                        $display_text, 
                        $level
                    );
                },
                $updated
            );
            
        /**
         * 1.1.2. Emphasis Block Conversion
         */
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/emph (.*?) \/-->/',
                function ($matches) use (&$emph_count) {
                    // Parse the JSON attributes safely
                    $attrString = $matches[1];
                    preg_match('/"title":"(.*?)","text":"(.*?)"/', $attrString, $contentMatches);
                    
                    if (!empty($contentMatches) && count($contentMatches) >= 3) {
                        $title = json_decode('"' . $contentMatches[1] . '"');
                        $text = json_decode('"' . $contentMatches[2] . '"');
                        
                        // Extract custom classes if they exist and preserve exactly
                        $className = '';
                        if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                            $className = $classMatch[1];
                        }
                        
                        // If no class, use slgb-emph for backwards compatibility
                        if (empty($className)) {
                            $className = 'slgb-emph';
                        }
                            
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

        /**
         * 1.1.3. Image Block Conversion
         */
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
                        
                        // Extract source text if available
                        $source = '';
                        $showSource = false;
                        
                        if (preg_match('/"source":"(.*?)"/', $attrString, $sourceMatch)) {
                            // Handle unicode-escaped HTML entities in source
                            $encodedSource = $sourceMatch[1];
                            $encodedSource = str_replace(['u003c', 'u003e', 'u0026', 'u0022', 'u003d'], ['<', '>', '&', '"', '='], $encodedSource);
                            $source = $encodedSource; // Keep the escaped HTML to preserve links
                        }
                        
                        if (preg_match('/"showSource":(true|false)/', $attrString, $showSourceMatch)) {
                            $showSource = $showSourceMatch[1] === 'true';
                        } else {
                            // Default to showing source if it exists but showSource is not specified
                            $showSource = !empty($source);
                        }
                        
                        // Extract CSS classes - preserve exactly as they are
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
                        
                        // Handle caption and source attribution
                        $captionContent = '';
                        
                        // If we have both caption and source, combine them
                        if (!empty($caption) && $showSource && !empty($source)) {
                            $captionContent = $caption . ' ' . $source;
                        }
                        // If we only have caption
                        else if (!empty($caption)) {
                            $captionContent = $caption;
                        }
                        // If we only have source
                        else if ($showSource && !empty($source)) {
                            $captionContent = $source;
                        }
                        
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
                                !empty($captionContent) ? sprintf('<figcaption class="wp-element-caption">%s</figcaption>', $captionContent) : ''
                            );
                        } else {
                            $output = sprintf(
                                '<!-- wp:image %s --><figure class="wp-block-image size-full"><img src="%s" alt="%s" class="wp-image-%d"%s/>%s</figure><!-- /wp:image -->',
                                $blockAttrString,
                                esc_url($src),
                                esc_attr($alt),
                                $id,
                                !empty($width) && !empty($height) ? sprintf(' width="%d" height="%d"', $width, $height) : '',
                                !empty($captionContent) ? sprintf('<figcaption class="wp-element-caption">%s</figcaption>', $captionContent) : ''
                            );
                        }
                        
                        return $output;
                    }
                    
                    // If we couldn't extract the necessary information, return the original
                    return $matches[0];
                },
                $updated
            );

        /**
         * 1.1.4. Table Block Conversion
         */
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/table (.*?) \/-->/',
                function ($matches) use (&$table_count) {
                    $attrString = $matches[1];
                    
                    // Extract any existing classes - preserve exactly as they are
                    $existingClasses = '';
                    if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                        $existingClasses = $classMatch[1];
                    }
                    
                    // If no class exists, use slgb-table for backwards compatibility
                    $tableClass = !empty($existingClasses) ? $existingClasses : 'slgb-table';
                    
                    // Try to extract the cells data
                    if (preg_match('/"cells":"(.*?)"/', $attrString, $cellsMatch)) {
                        try {
                            // Replace unicode escape sequences with actual quote characters 
                            $processedJson = str_replace('u0022', '"', $cellsMatch[1]);
                            
                            // Double decode to handle escaped JSON
                            $processedJson = json_decode('"' . $processedJson . '"');
                            $cells = json_decode($processedJson, true);
                            
                            if (is_array($cells)) {
                                // Build a standard HTML table
                                $tableHtml = '<table class="' . esc_attr($tableClass) . '"><tbody>';
                                
                                foreach ($cells as $rowIndex => $row) {
                                    $tableHtml .= '<tr>';
                                    
                                    foreach ($row as $cell) {
                                        // Get cell content and decode escaped HTML
                                        $content = isset($cell['content']) ? $cell['content'] : '';
                                        // No need to double decode here as we already handled it above
                                        
                                        // Apply colspan and rowspan if needed
                                        $attrs = '';
                                        if (isset($cell['cols']) && $cell['cols'] > 1) {
                                            $attrs .= sprintf(' colspan="%d"', $cell['cols']);
                                        }
                                        if (isset($cell['rows']) && $cell['rows'] > 1) {
                                            $attrs .= sprintf(' rowspan="%d"', $cell['rows']);
                                        }
                                        
                                        // Use th for header cells (first row)
                                        $tag = $rowIndex === 0 ? 'th' : 'td';
                                        $tableHtml .= sprintf('<%s%s>%s</%s>', $tag, $attrs, $content, $tag);
                                    }
                                    
                                    $tableHtml .= '</tr>';
                                }
                                
                                $tableHtml .= '</tbody></table>';
                                $table_count++;
                                
                                // Create a core/html block with the table
                                return sprintf(
                                    '<!-- wp:html -->' . "\n" . '%s' . "\n" . '<!-- /wp:html -->',
                                    $tableHtml
                                );
                            }
                        } catch (Exception $e) {
                            // Log error for debugging
                            error_log('SLGB Table Conversion Error: ' . $e->getMessage());
                            
                            // Fallback method for problematic tables
                            try {
                                // Direct approach to extract table structure
                                $cellsRaw = $cellsMatch[1];
                                // Replace unicode escapes manually
                                $cellsRaw = str_replace(['u0022cols', 'u0022rows', 'u0022content'], ['"cols', '"rows', '"content'], $cellsRaw);
                                
                                // Try to manually build the table by parsing the structure
                                preg_match_all('/\{(.*?)\}/', $cellsRaw, $cellMatches);
                                
                                if (!empty($cellMatches[0])) {
                                    $rows = [];
                                    $currentRow = [];
                                    $rowCount = 0;
                                    
                                    // Count number of columns by checking the first row structure
                                    $numCols = 0;
                                    $pattern = '/content/';
                                    $matches = preg_match_all($pattern, $cellsRaw, $colMatches);
                                    if ($matches) {
                                        // Try to determine columns by analyzing the pattern
                                        $content = $cellsRaw;
                                        $rowDelimiters = ["]", "["];
                                        $rows = explode($rowDelimiters[0] . $rowDelimiters[1], $content);
                                        
                                        if (count($rows) < 2) {
                                            // If we couldn't split by row delimiters, try another approach
                                            $cellPattern = '/content(.*?):(.*?)(?:,|$)/';
                                            preg_match_all($cellPattern, $cellMatches[0][0] . $cellMatches[0][1] . $cellMatches[0][2], $firstRowCells);
                                            $numCols = count($firstRowCells[0]);
                                        }
                                    }
                                    
                                    // Default to 3 columns if we couldn't determine
                                    $numCols = $numCols > 0 ? $numCols : 3;
                                    
                                    foreach ($cellMatches[0] as $cellData) {
                                        // Extract cell content
                                        preg_match('/content(.*?):(.*?)(?:,|$)/', $cellData, $contentMatch);
                                        $cellContent = !empty($contentMatch[2]) ? trim($contentMatch[2], '"u0022') : '';
                                        
                                        // Add to current row
                                        $currentRow[] = $cellContent;
                                        
                                        // If we have numCols cells, start a new row
                                        if (count($currentRow) === $numCols) {
                                            $rows[] = $currentRow;
                                            $currentRow = [];
                                            $rowCount++;
                                        }
                                    }
                                    
                                    // Add any remaining cells
                                    if (!empty($currentRow)) {
                                        $rows[] = $currentRow;
                                    }
                                    
                                    // Build table HTML manually with preserved class
                                    $tableHtml = '<table class="' . esc_attr($tableClass) . '"><tbody>';
                                    
                                    foreach ($rows as $rowIndex => $row) {
                                        $tableHtml .= '<tr>';
                                        
                                        foreach ($row as $cellContent) {
                                            // Use th for header cells (first row)
                                            $tag = $rowIndex === 0 ? 'th' : 'td';
                                            $tableHtml .= sprintf('<%s>%s</%s>', $tag, $cellContent, $tag);
                                        }
                                        
                                        $tableHtml .= '</tr>';
                                    }
                                    
                                    $tableHtml .= '</tbody></table>';
                                    $table_count++;
                                    
                                    // Create a core/html block with the table
                                    return sprintf(
                                        '<!-- wp:html -->' . "\n" . '%s' . "\n" . '<!-- /wp:html -->',
                                        $tableHtml
                                    );
                                }
                            } catch (Exception $innerE) {
                                // Final fallback - just return original if all else fails
                                error_log('SLGB Table Fallback Conversion Error: ' . $innerE->getMessage());
                            }
                        }
                    }
                    
                    // If we couldn't extract cells data, return the original
                    return $matches[0];
                },
                $updated
            );

        /**
         * 1.1.5. Subscribe Block Conversion
         */
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/gb-subscribe (.*?) \/-->/',
                function ($matches) use (&$subscribe_count) {
                    $attrString = $matches[1];
                    
                    // Extract existing classes - preserve exactly as they are
                    $existingClasses = '';
                    if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                        $existingClasses = $classMatch[1];
                    }
                    
                    // If no class exists, use slgb-subscribe for backwards compatibility
                    $className = !empty($existingClasses) ? $existingClasses : 'slgb-subscribe';
                    
                    // Extract the title
                    $title = '';
                    if (preg_match('/"title":"(.*?)"/', $attrString, $titleMatch)) {
                        $title = json_decode('"' . $titleMatch[1] . '"');
                    }
                    
                    $subscribe_count++;
                    
                    // Create a paragraph with the title and a button
                    return sprintf(
                        '<!-- wp:group {"className":"%s"} -->' . "\n" .
                        '<div class="wp-block-group %s">' . "\n" .
                        '<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"500"}}} -->' . "\n" .
                        '<p class="has-text-align-center" style="font-weight:500">%s</p>' . "\n" .
                        '<!-- /wp:paragraph -->' . "\n\n" .
                        '<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->' . "\n" .
                        '<div class="wp-block-buttons">' . "\n" .
                        '<!-- wp:button {"className":"is-style-fill"} -->' . "\n" .
                        '<div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button">Subscribe</a></div>' . "\n" .
                        '<!-- /wp:button -->' . "\n" .
                        '</div>' . "\n" .
                        '<!-- /wp:buttons -->' . "\n" .
                        '</div>' . "\n" .
                        '<!-- /wp:group -->',
                        esc_attr($className),
                        esc_attr($className),
                        esc_html($title)
                    );
                },
                $updated
            );
            
        /**
         * 1.1.6. Compare Block Conversion
         */
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/p-compare (.*?)-->(.*?)<!-- \/wp:slgb\/p-compare -->/s',
                function ($matches) use (&$compare_count) {
                    $attrString = $matches[1];
                    $content = $matches[2];
                    
                    // Extract existing parent block classes - preserve exactly as they are
                    $existingParentClasses = '';
                    if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                        $existingParentClasses = $classMatch[1];
                    }
                    
                    // If no class exists, use slgb-compare for backwards compatibility
                    $parentClassName = !empty($existingParentClasses) ? $existingParentClasses : 'slgb-compare';
                    
                    // Extract and convert the individual columns
                    $content = preg_replace_callback(
                        '/<\!-- wp:slgb\/p-compare-column (.*?) -->(.*?)<!-- \/wp:slgb\/p-compare-column -->/s',
                        function ($columnMatches) {
                            $columnAttrs = $columnMatches[1];
                            $columnContent = $columnMatches[2];
                            
                            // Extract existing column classes - preserve exactly as they are
                            $existingColumnClasses = '';
                            if (preg_match('/"className":"([^"]*)"/', $columnAttrs, $classMatch)) {
                                $existingColumnClasses = $classMatch[1];
                            }
                            
                            // If no class exists, use slgb-compare-column for backwards compatibility
                            $columnClassName = !empty($existingColumnClasses) ? $existingColumnClasses : 'slgb-compare-column';
                            
                            // Extract the title
                            $title = '';
                            if (preg_match('/"title":"(.*?)"/', $columnAttrs, $titleMatch)) {
                                $title = json_decode('"' . $titleMatch[1] . '"');
                            }
                            
                            // Process the column content - find the existing div and its content
                            preg_match('/<div class="gb-compare__column">.*?<h4.*?>(.*?)<\/h4>.*?<div class="gb-compare__content">(.*?)<\/div><\/div>/s', 
                                $columnContent, 
                                $contentParts
                            );
                            
                            $columnTitle = isset($contentParts[1]) ? $contentParts[1] : $title;
                            $columnBody = isset($contentParts[2]) ? $contentParts[2] : $columnContent;
                            
                            // Create a core/column block with the column content
                            return sprintf(
                                '<!-- wp:column {"className":"%s"} -->' . "\n" .
                                '<div class="wp-block-column %s">' . "\n" .
                                '<!-- wp:heading {"level":4} -->' . "\n" .
                                '<h4>%s</h4>' . "\n" .
                                '<!-- /wp:heading -->' . "\n" .
                                '%s' . "\n" .
                                '</div>' . "\n" .
                                '<!-- /wp:column -->',
                                esc_attr($columnClassName),
                                esc_attr($columnClassName),
                                $columnTitle,
                                $columnBody
                            );
                        },
                        $content
                    );
                    
                    $compare_count++;
                    
                    // Create a core/columns block with the converted columns
                    return sprintf(
                        '<!-- wp:columns {"className":"%s"} -->' . "\n" .
                        '<div class="wp-block-columns %s">' . "\n" .
                        '%s' . "\n" .
                        '</div>' . "\n" .
                        '<!-- /wp:columns -->',
                        esc_attr($parentClassName),
                        esc_attr($parentClassName),
                        $content
                    );
                },
                $updated
            );

        /**
         * 1.1.7. Hints Block Conversion
         */
            // Define esc_attr and esc_html functions if not defined (for non-WordPress environments)
            if (!function_exists('esc_attr')) {
                function esc_attr($text) {
                    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
                }
            }
            
            if (!function_exists('esc_html')) {
                function esc_html($text) {
                    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
                }
            }
            
            if (!function_exists('esc_url')) {
                function esc_url($url) {
                    return filter_var($url, FILTER_SANITIZE_URL);
                }
            }
            
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/p-hints (.*?)-->(.*?)<!-- \/wp:slgb\/p-hints -->/s',
                function ($matches) use (&$p_hints_count) {
                    $attrs = isset($matches[1]) ? $matches[1] : '';
                    $content = $matches[2];

                    // Extract existing classes
                    $className = 'slgb-p-hints';
                    if (preg_match('/{"className":"([^"]*)"/', $attrs, $classMatch)) {
                        $className = $classMatch[1];
                    }
                    
                    // Process existing table if it exists in the content
                    $tableContent = '';
                    
                    if (preg_match('/<table.*?>(.*?)<\/table>/s', $content, $tableMatch)) {
                        $tableContent = $tableMatch[1];
                        
                        // Make sure table content has decoded HTML entities
                        $tableContent = str_replace('&lt;', '<', $tableContent);
                        $tableContent = str_replace('&gt;', '>', $tableContent);
                        $tableContent = str_replace('&amp;', '&', $tableContent);
                        $tableContent = str_replace('&quot;', '"', $tableContent);
                        
                        // Update row and cell classes in the content
                        $tableContent = preg_replace('/<tr\s+class=""/', '<tr class="slgb-p-hints-row"', $tableContent);
                        $tableContent = preg_replace('/<td\s+class=""/', '<td class="slgb-p-hints-cell"', $tableContent);
                    }
                    
                    $p_hints_count++;
                    
                    // Create HTML table with appropriate classes
                    $tableHtml = '';
                    if (!empty($tableContent)) {
                        $tableHtml = '<table class="' . esc_attr($className) . '">' . $tableContent . '</table>';
                    } else {
                        // If no table content, create a basic DO/DON'T table structure
                        $tableHtml = '<table class="' . esc_attr($className) . '">';
                        $tableHtml .= '<thead><tr><th>DO</th><th>DO NOT</th></tr></thead><tbody><tr class="slgb-p-hints-row"><td class="slgb-p-hints-cell"></td><td class="slgb-p-hints-cell"></td></tr></tbody>';
                        $tableHtml .= '</table>';
                    }
                    
                    // Return as a core/html block
                    return sprintf(
                        '<!-- wp:html -->' . "\n" . 
                        '%s' . "\n" . 
                        '<!-- /wp:html -->',
                        $tableHtml
                    );
                },
                $updated
            );

      /**
 * 1.1.8. Quote Block Conversion
 */
    $updated = preg_replace_callback(
        '/<\!-- wp:slgb\/p-quote (.*?) -->(.*?)<!-- \/wp:slgb\/p-quote -->/s',
        function ($matches) use (&$quote_count) {
            $attrString = $matches[1];
            $content = $matches[2];
            
            // Extract the text
            $text = '';
            if (preg_match('/"text":"(.*?)"(?:,|})/', $attrString, $textMatch)) {
                // First replace all possible Unicode escape sequences with proper characters 
                $escapedText = $textMatch[1];
                
                // Handle common HTML entity escapes in Unicode format
                $escapedText = str_replace(
                    ['u003c', 'u003e', 'u0026', 'u0022', 'u0027', 'u003d', 'u0020'],
                    ['<', '>', '&', '"', "'", '=', ' '],
                    $escapedText
                );
                
                // Additional cleaning for Unicode sequences
                $escapedText = preg_replace('/u([0-9a-fA-F]{4})/', '\\u$1', $escapedText);
                
                // Properly decode all HTML entities
                $text = html_entity_decode(json_decode('"' . str_replace('"', '\\"', $escapedText) . '"'));
            }
            
            // Extract the author
            $author = '';
            if (preg_match('/"author":"(.*?)"/', $attrString, $authorMatch)) {
                $author = json_decode('"' . $authorMatch[1] . '"');
            }
            
            // Extract featured status
            $featured = false;
            if (preg_match('/"featured":(true|false)/', $attrString, $featuredMatch)) {
                $featured = $featuredMatch[1] === 'true';
            }
            
            // Extract photo information if it exists (NEW CODE)
            $photoData = null;
            if (preg_match('/"photo":\{(.*?)\}/', $attrString, $photoMatch)) {
                $photoJson = '{' . $photoMatch[1] . '}';
                $photoData = json_decode($photoJson, true);
            }
            
            // Also try to extract image from the HTML content if photo data wasn't in attributes (NEW CODE)
            if (empty($photoData) && preg_match('/<img[^>]*width="([^"]+)"[^>]*height="([^"]+)"[^>]*alt="([^"]+)"[^>]*src="([^"]+)"[^>]*\/>/', $content, $imgMatch)) {
                $photoData = [
                    'width' => $imgMatch[1],
                    'height' => $imgMatch[2],
                    'alt' => $imgMatch[3],
                    'src' => $imgMatch[4]
                ];
            }
            
            // Extract content from blockquote if available and text is empty
            if (empty($text) && preg_match('/<blockquote><p>(.*?)<\/p><\/blockquote>/s', $content, $contentMatch)) {
                $text = $contentMatch[1];
                
                // Convert HTML entities back to characters
                $text = html_entity_decode($text);
            }
            
            // Extract any existing classes - preserve exactly as they are
            $existingClasses = [];
            if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                $existingClasses = explode(' ', $classMatch[1]);
            }
            
            $quote_count++;
            
            // Create class list preserving existing classes
            $classNames = $existingClasses;
            
            // If no class exists, use slgb-quote for backwards compatibility
            if (empty($classNames)) {
                $classNames[] = 'slgb-quote';
            }
            
            // Add featured style if needed
            if ($featured) {
                $classNames[] = 'is-style-large';
            } else {
                $classNames[] = 'is-style-normal';
            }
            
            // Join classes and ensure no duplicates
            $className = implode(' ', array_unique(array_filter($classNames)));
            
            // Create a core/quote block with the content
            $output = sprintf(
                '<!-- wp:quote {"className":"wp-block-quote %s"} -->' . "\n" .
                '<blockquote class="wp-block-quote %s">',
                htmlspecialchars($className, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($className, ENT_QUOTES, 'UTF-8')
            );
            
            // Add image if photo data exists (NEW CODE) - with proper wrapper
            if (isset($photoData) && isset($photoData['src'])) {
                // Extract image attributes
                $width = isset($photoData['width']) ? $photoData['width'] : '';
                $height = isset($photoData['height']) ? $photoData['height'] : '';
                $alt = isset($photoData['alt']) ? $photoData['alt'] : '';
                $src = $photoData['src'];
                
                // Add the image with photo wrapper div
                $output .= "<div class=\"gb-quote__photo\">\n";
                $output .= sprintf(
                    '<img width="%s" height="%s" alt="%s" src="%s"/>' . "\n",
                    htmlspecialchars($width, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($height, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'),
                    $src
                );
                $output .= "</div>\n";
            }
            
            // Add content wrapper for text and citation
            $output .= "<div class=\"gb-quote__content\">";
            
            // Add the text content
            $output .= sprintf('<p>%s</p>', $text);
            
            // Add citation if author exists
            if (!empty($author)) {
                $output .= sprintf('<footer><cite>%s</cite></footer>' . "\n", $author);
            }
            
            // Close content wrapper
            $output .= "</div>\n";
            
            $output .= '</blockquote>
<!-- /wp:quote -->';
            
            return $output;
        },
        $updated
    );

        /**
         * 1.1.9. Miniature Block Conversion
         */
            // First pattern with self-closing tag
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/p-miniature (.*?) \/-->/',
                function ($matches) use (&$miniature_count) {
                    $attrString = $matches[1];
                    $attrs = json_decode('{' . preg_replace('/^\{(.*)\}$/', '$1', $attrString) . '}', true);
                    
                    // Extract text content, postId and postInfo
                    $text = isset($attrs['text']) ? $attrs['text'] : '';
                    $postId = isset($attrs['postId']) ? $attrs['postId'] : '';
                    $postInfo = isset($attrs['postInfo']) ? $attrs['postInfo'] : null;
                    
                    // Decode HTML entities in text
                    $text = str_replace(['u003c', 'u003e', 'u0026', 'u0022', '\\'], ['<', '>', '&', '"', ''], $text);
                    $text = html_entity_decode(json_decode('"' . str_replace('"', '\\"', $text) . '"'));
                    
                    // Extract existing classes - preserve exactly as they are
                    $existingClasses = '';
                    if (isset($attrs['className'])) {
                        $existingClasses = $attrs['className'];
                    }
                    
                    // If no class exists, use gb-mini for backward compatibility
                    $className = !empty($existingClasses) ? $existingClasses : 'gb-mini';
                    
                    $miniature_count++;
                    
                    // Default values in case we can't fetch post data
                    $post_title = '';
                    $post_url = '';
                    $post_image_url = '';
                    $post_image_width = '';
                    $post_image_height = '';
                    $author_name = '';
                    $author_url = '';
                    $category_name = '';
                    $category_url = '';
                    
                    // First try to extract data from postInfo if available
                    if (!empty($postInfo) && is_array($postInfo)) {
                        // Direct array access for postInfo
                        if (isset($postInfo['title'])) {
                            $post_title = $postInfo['title'];
                        }
                        if (isset($postInfo['link'])) {
                            $post_url = $postInfo['link'];
                        }
                        
                        // Handle category
                        if (isset($postInfo['category']) && is_array($postInfo['category'])) {
                            if (isset($postInfo['category']['title'])) {
                                $category_name = $postInfo['category']['title'];
                            }
                            if (isset($postInfo['category']['link'])) {
                                $category_url = $postInfo['category']['link'];
                            }
                        }
                        
                        // Handle author
                        if (isset($postInfo['author']) && is_array($postInfo['author'])) {
                            if (isset($postInfo['author']['name'])) {
                                $author_name = $postInfo['author']['name'];
                            }
                            if (isset($postInfo['author']['link'])) {
                                $author_url = $postInfo['author']['link'];
                            }
                        }
                        
                        // Handle image
                        if (isset($postInfo['img']) && is_array($postInfo['img'])) {
                            if (isset($postInfo['img']['src'])) {
                                $post_image_url = $postInfo['img']['src'];
                            }
                            if (isset($postInfo['img']['width'])) {
                                $post_image_width = $postInfo['img']['width'];
                            }
                            if (isset($postInfo['img']['height'])) {
                                $post_image_height = $postInfo['img']['height'];
                            }
                        }
                    }
                    // If postInfo is a string (JSON), try to decode it
                    else if (!empty($postInfo) && is_string($postInfo)) {
                        // Attempt to fix common JSON escaping issues
                        $postInfo = str_replace(['u003c', 'u003e', 'u0026', 'u0022', '\\'], ['<', '>', '&', '"', '\\'], $postInfo);
                        
                        try {
                            $postInfoData = json_decode($postInfo, true);
                            
                            if ($postInfoData && is_array($postInfoData)) {
                                // Extract title and link
                                if (isset($postInfoData['title'])) {
                                    $post_title = $postInfoData['title'];
                                }
                                if (isset($postInfoData['link'])) {
                                    $post_url = $postInfoData['link'];
                                }
                                
                                // Handle category/tag
                                if (isset($postInfoData['tag'])) {
                                    if (is_array($postInfoData['tag'])) {
                                        if (!empty($postInfoData['tag']) && isset($postInfoData['tag'][0])) {
                                            if (isset($postInfoData['tag'][0]['title'])) {
                                                $category_name = $postInfoData['tag'][0]['title'];
                                            }
                                            if (isset($postInfoData['tag'][0]['link'])) {
                                                $category_url = $postInfoData['tag'][0]['link'];
                                            }
                                        }
                                    } else if (isset($postInfoData['tag']['title'])) {
                                        $category_name = $postInfoData['tag']['title'];
                                        if (isset($postInfoData['tag']['link'])) {
                                            $category_url = $postInfoData['tag']['link'];
                                        }
                                    }
                                } else if (isset($postInfoData['category']) && is_array($postInfoData['category'])) {
                                    if (isset($postInfoData['category']['title'])) {
                                        $category_name = $postInfoData['category']['title'];
                                    }
                                    if (isset($postInfoData['category']['link'])) {
                                        $category_url = $postInfoData['category']['link'];
                                    }
                                }
                                
                                // Handle author
                                if (isset($postInfoData['author']) && is_array($postInfoData['author'])) {
                                    if (isset($postInfoData['author']['name'])) {
                                        $author_name = $postInfoData['author']['name'];
                                    }
                                    if (isset($postInfoData['author']['link'])) {
                                        $author_url = $postInfoData['author']['link'];
                                    }
                                }
                                
                                // Handle image
                                if (isset($postInfoData['img']) && is_array($postInfoData['img'])) {
                                    if (isset($postInfoData['img']['src'])) {
                                        $post_image_url = $postInfoData['img']['src'];
                                    }
                                    if (isset($postInfoData['img']['width'])) {
                                        $post_image_width = $postInfoData['img']['width'];
                                    }
                                    if (isset($postInfoData['img']['height'])) {
                                        $post_image_height = $postInfoData['img']['height'];
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            // Failed to decode JSON, continue with what we have
                        }
                    }
                    
                    // If postId exists, try to get post data
                    if (!empty($postId) && function_exists('get_post')) {
                        $post = get_post($postId);
                        
                        if ($post) {
                            // Get post title
                            $post_title = $post->post_title;
                            
                            // Get post URL
                            if (function_exists('get_permalink')) {
                                $post_url = get_permalink($post);
                            } else {
                                // Fallback if get_permalink is not available
                                $post_url = home_url('?p=' . $postId);
                            }
                            
                            // Get post featured image
                            if (function_exists('get_the_post_thumbnail_url')) {
                                $post_image_url = get_the_post_thumbnail_url($post, 'full');
                            }
                            
                            // Get author data
                            if (function_exists('get_the_author_meta')) {
                                $author_id = $post->post_author;
                                $author_name = get_the_author_meta('display_name', $author_id);
                                $author_url = get_author_posts_url($author_id);
                            }
                            
                            // Get post category
                            if (function_exists('get_the_category')) {
                                $categories = get_the_category($postId);
                                if (!empty($categories)) {
                                    $category = $categories[0];
                                    $category_name = $category->name;
                                    $category_url = get_category_link($category->term_id);
                                }
                            }
                        }
                    }
                    
                    // Create enhanced HTML structure based on the provided template
                    $miniatureHtml = '<div class="gb-mini">';
                    
                    // Add image section if available
                    if (!empty($post_image_url)) {
                        $miniatureHtml .= '<div class="gb-mini__image p-blog__image-shadow" aria-hidden="true">';
                        $miniatureHtml .= '<a href="' . htmlspecialchars($post_url, ENT_QUOTES, 'UTF-8') . '" target="_blank" tabindex="-1" class="gb-mini__image-link p-blog__image-link">';
                        $miniatureHtml .= '<span class="visually-hidden">' . htmlspecialchars($post_title, ENT_QUOTES, 'UTF-8') . ' (opens in new tab)</span>';
                        $miniatureHtml .= '<picture>';
                        
                        // Get domain and path from URL for proper image path
                        $image_path_parts = parse_url($post_image_url);
                        $image_domain = '';
                        if (isset($image_path_parts['host'])) {
                            $image_domain = $image_path_parts['scheme'] . '://' . $image_path_parts['host'];
                        }
                        
                        // If image is from direct URL, use it as is, otherwise try to build WordPress media URL
                        $image_url = $post_image_url;
                        if (strpos($post_image_url, 'wp-content/uploads') === false && strpos($post_image_url, '/img/') !== false) {
                            // If it's an 'img' path, use it as is
                            $image_url = $post_image_url;
                        } else if (strpos($post_image_url, 'wp-content/uploads') === false) {
                            // Try to build WordPress media path
                            $image_filename = basename($post_image_url);
                            $image_url = $image_domain . '/wp-content/uploads/' . date('Y/m') . '/' . $image_filename;
                        }
                        
                        $miniatureHtml .= '<source srcset="' . htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') . ' 1x" type="image/png" media="(max-width: 499px)">';
                        $miniatureHtml .= '<source srcset="' . htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') . ' 1x" type="image/png" media="(min-width: 500px)">';
                        
                        // Add width and height if available, include srcset attribute
                        if (!empty($post_image_width) && !empty($post_image_height)) {
                            $miniatureHtml .= '<img loading="lazy" decoding="async" width="' . htmlspecialchars($post_image_width, ENT_QUOTES, 'UTF-8') . '" height="' . htmlspecialchars($post_image_height, ENT_QUOTES, 'UTF-8') . '" src="' . htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') . '" srcset="' . htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') . ' 1x" alt="Post thumbnail" class="gb-mini__img">';
                        } else {
                            $miniatureHtml .= '<img loading="lazy" decoding="async" src="' . htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') . '" srcset="' . htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') . ' 1x" alt="Post thumbnail" class="gb-mini__img">';
                        }
                        
                        $miniatureHtml .= '</picture>';
                        $miniatureHtml .= '</a></div>';
                    }
                    
                    // Add content section
                    $miniatureHtml .= '<div class="gb-mini__content">';
                    
                    // Add category if available
                    if (!empty($category_name)) {
                        $miniatureHtml .= '<a href="' . htmlspecialchars($category_url, ENT_QUOTES, 'UTF-8') . '" target="_blank" class="gb-mini__tag">' . htmlspecialchars($category_name, ENT_QUOTES, 'UTF-8') . '</a>';
                    }
                    
                    // Add title with link
                    if (!empty($post_title)) {
                        $miniatureHtml .= '<a href="' . htmlspecialchars($post_url, ENT_QUOTES, 'UTF-8') . '" target="_self" class="gb-mini__title">' . htmlspecialchars($post_title, ENT_QUOTES, 'UTF-8') . '</a>';
                        
                        // Add text as description
                        if (!empty($text)) {
                            $miniatureHtml .= '<p class="gb-mini__text">' . $text . '</p>';
                        }
                    } else {
                        // Use text as title if no post title
                        $miniatureHtml .= '<a href="' . htmlspecialchars($post_url, ENT_QUOTES, 'UTF-8') . '" target="_blank" class="gb-mini__title">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</a>';
                    }
                    
                    // Add author if available
                    if (!empty($author_name)) {
                        $miniatureHtml .= '<a href="' . htmlspecialchars($author_url, ENT_QUOTES, 'UTF-8') . '" target="_blank" class="gb-mini__author">' . htmlspecialchars($author_name, ENT_QUOTES, 'UTF-8') . '</a>';
                    }
                    
                    $miniatureHtml .= '</div></div>';
                    
                    // Return as a core/html block to preserve structure
                    return '<!-- wp:html -->' . $miniatureHtml . '<!-- /wp:html -->';
                },
                $updated
            );

        /**
         * 1.1.9.0. Non-self-closing p-miniature Block Conversion
         */
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/p-miniature (.*?) -->(.*?)<\!-- \/wp:slgb\/p-miniature -->/s',
                function ($matches) use (&$miniature_count) {
                    $attrString = $matches[1];
                    $blockContent = $matches[2];
                    $attrs = json_decode('{' . preg_replace('/^\{(.*)\}$/', '$1', $attrString) . '}', true);
                    
                    // Extract text content, postId and postInfo
                    $text = isset($attrs['text']) ? $attrs['text'] : '';
                    $postId = isset($attrs['postId']) ? $attrs['postId'] : '';
                    $postInfo = isset($attrs['postInfo']) ? $attrs['postInfo'] : null;
                    
                    // Decode HTML entities in text
                    $text = str_replace(['u003c', 'u003e', 'u0026', 'u0022', '\\'], ['<', '>', '&', '"', '\\'], $text);
                    if (!empty($text)) {
                        try {
                            $text = html_entity_decode(json_decode('"' . str_replace('"', '\\"', $text) . '"'));
                        } catch (Exception $e) {
                            // If JSON decoding fails, continue with text as is
                        }
                    }
                    
                    // Default values
                    $post_title = '';
                    $post_url = '';
                    $post_image_url = '';
                    $post_image_width = '';
                    $post_image_height = '';
                    $author_name = '';
                    $author_url = '';
                    $category_name = '';
                    $category_url = '';
                    
                    // Extract image from blockContent if available
                    if (preg_match('/<img[^>]*src=["\']([^"\']*)["\']/i', $blockContent, $img_matches)) {
                        $post_image_url = $img_matches[1];
                        
                        // Try to get width and height
                        if (preg_match('/width=["\']([^"\']*)["\']/i', $blockContent, $width_matches)) {
                            $post_image_width = $width_matches[1];
                        }
                        if (preg_match('/height=["\']([^"\']*)["\']/i', $blockContent, $height_matches)) {
                            $post_image_height = $height_matches[1];
                        }
                    }
                    
                    // Extract category link
                    if (preg_match('/<a[^>]*href=["\']([^"\']*)["\'][^>]*>([^<]*)<\/a>/i', $blockContent, $cat_matches)) {
                        $category_url = $cat_matches[1];
                        $category_name = strip_tags($cat_matches[2]);
                    }
                    
                    // Extract title with link
                    if (preg_match('/<a[^>]*href=["\']([^"\']*)["\'][^>]*><strong>([^<]*)<\/strong><\/a>/i', $blockContent, $title_matches)) {
                        $post_url = $title_matches[1];
                        $post_title = strip_tags($title_matches[2]);
                    }
                    
                    // Extract author
                    if (preg_match('/<a[^>]*href=["\']([^"\']*author\/[^"\']*)["\'][^>]*>([^<]*)<\/a>/i', $blockContent, $author_matches)) {
                        $author_url = $author_matches[1];
                        $author_name = strip_tags($author_matches[2]);
                    }
                    
                    // Extract paragraph content
                    if (preg_match('/<p>([^<]*)<\/p>/i', $blockContent, $para_matches)) {
                        $text = $para_matches[1];
                    }
                    
                    // Try to process postInfo if it's available
                    if (!empty($postInfo)) {
                        if (is_string($postInfo)) {
                            try {
                                $postInfoData = json_decode($postInfo, true);
                                if (is_array($postInfoData)) {
                                    // Fill in from postInfo data
                                    if (empty($post_title) && isset($postInfoData['title'])) {
                                        $post_title = $postInfoData['title'];
                                    }
                                    if (empty($post_url) && isset($postInfoData['link'])) {
                                        $post_url = $postInfoData['link'];
                                    }
                                    
                                    // Handle category
                                    if (empty($category_name) && isset($postInfoData['category'])) {
                                        if (isset($postInfoData['category']['title'])) {
                                            $category_name = $postInfoData['category']['title'];
                                        }
                                        if (isset($postInfoData['category']['link'])) {
                                            $category_url = $postInfoData['category']['link'];
                                        }
                                    }
                                    
                                    // Handle image
                                    if (empty($post_image_url) && isset($postInfoData['img'])) {
                                        if (isset($postInfoData['img']['src'])) {
                                            $post_image_url = $postInfoData['img']['src'];
                                        }
                                        if (isset($postInfoData['img']['width'])) {
                                            $post_image_width = $postInfoData['img']['width'];
                                        }
                                        if (isset($postInfoData['img']['height'])) {
                                            $post_image_height = $postInfoData['img']['height'];
                                        }
                                    }
                                    
                                    // Handle author
                                    if (empty($author_name) && isset($postInfoData['author'])) {
                                        if (isset($postInfoData['author']['name'])) {
                                            $author_name = $postInfoData['author']['name'];
                                        }
                                        if (isset($postInfoData['author']['link'])) {
                                            $author_url = $postInfoData['author']['link'];
                                        }
                                    }
                                }
                            } catch (Exception $e) {
                                // Failed to parse JSON, continue with what we have
                            }
                        } else if (is_array($postInfo)) {
                            // Direct array access
                            if (empty($post_title) && isset($postInfo['title'])) {
                                $post_title = $postInfo['title'];
                            }
                            if (empty($post_url) && isset($postInfo['link'])) {
                                $post_url = $postInfo['link'];
                            }
                            
                            // Handle category
                            if (empty($category_name) && isset($postInfo['category'])) {
                                if (isset($postInfo['category']['title'])) {
                                    $category_name = $postInfo['category']['title'];
                                }
                                if (isset($postInfo['category']['link'])) {
                                    $category_url = $postInfo['category']['link'];
                                }
                            }
                            
                            // Handle image
                            if (empty($post_image_url) && isset($postInfo['img'])) {
                                if (isset($postInfo['img']['src'])) {
                                    $post_image_url = $postInfo['img']['src'];
                                }
                                if (isset($postInfo['img']['width'])) {
                                    $post_image_width = $postInfo['img']['width'];
                                }
                                if (isset($postInfo['img']['height'])) {
                                    $post_image_height = $postInfo['img']['height'];
                                }
                            }
                            
                            // Handle author
                            if (empty($author_name) && isset($postInfo['author'])) {
                                if (isset($postInfo['author']['name'])) {
                                    $author_name = $postInfo['author']['name'];
                                }
                                if (isset($postInfo['author']['link'])) {
                                    $author_url = $postInfo['author']['link'];
                                }
                            }
                        }
                    }
                    
                    // Extract existing classes - preserve exactly as they are
                    $existingClasses = '';
                    if (isset($attrs['className'])) {
                        $existingClasses = $attrs['className'];
                    }
                    
                    // If no class exists, use gb-mini for backward compatibility
                    $className = !empty($existingClasses) ? $existingClasses : 'gb-mini';
                    
                    // Create the HTML output with the proper gb-mini structure
                    $miniatureHtml = '<div class="' . $className . '">';
                    
                    // Add image section if available
                    if (!empty($post_image_url)) {
                        $miniatureHtml .= '<div class="gb-mini__image p-blog__image-shadow" aria-hidden="true">';
                        $miniatureHtml .= '<a href="' . htmlspecialchars($post_url, ENT_QUOTES, 'UTF-8') . '" target="_blank" tabindex="-1" class="gb-mini__image-link p-blog__image-link">';
                        $miniatureHtml .= '<span class="visually-hidden">' . htmlspecialchars($post_title, ENT_QUOTES, 'UTF-8') . ' (opens in new tab)</span>';
                        $miniatureHtml .= '<picture>';
                        
                        // Initialize with existing image URL
                        $image_url = $post_image_url;
                        
                        // Try to use the image from postInfo data if available (from the block attributes JSON)
                        if (isset($postInfo['img']) && isset($postInfo['img']['src'])) {
                            $image_url = $postInfo['img']['src'];  // This is the most reliable source
                            
                            // Also get dimensions if available
                            if (isset($postInfo['img']['width'])) {
                                $post_image_width = $postInfo['img']['width'];
                            }
                            if (isset($postInfo['img']['height'])) {
                                $post_image_height = $postInfo['img']['height'];
                            }
                        }
                        
                        // Only attempt to use WordPress functions if we have a post ID
                        if (!empty($postId) && is_numeric($postId)) {
                            // Use our safe wrapper function that handles WordPress function availability
                            $wp_image_result = oh_safe_get_wp_image((int)$postId);
                            
                            if (!empty($wp_image_result['url'])) {
                                $image_url = $wp_image_result['url'];
                                
                                // Update dimensions if available from WordPress
                                if (!empty($wp_image_result['width'])) {
                                    $post_image_width = $wp_image_result['width'];
                                }
                                if (!empty($wp_image_result['height'])) {
                                    $post_image_height = $wp_image_result['height'];
                                }
                            }
                        }
                        
                        // If we're not in WordPress or couldn't get the featured image, handle the URL differently
                        if ($image_url === $post_image_url) {
                            // If URL contains '/img/' or already has WordPress uploads path, keep it as is
                            if (strpos($post_image_url, '/img/') !== false || strpos($post_image_url, '/wp-content/uploads/') !== false) {
                                // Keep the original URL as is - no modifications needed
                            } else if (!empty($post_image_url)) {
                                // Get image directly from the postInfo attribute if available
                                // The URL is neither in /img/ nor in /wp-content/uploads/, so we keep it unchanged
                            }
                        }
                        
                        $miniatureHtml .= '<source srcset="' . htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') . ' 1x" type="image/png" media="(max-width: 499px)">';
                        $miniatureHtml .= '<source srcset="' . htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') . ' 1x" type="image/png" media="(min-width: 500px)">';
                        
                        if (!empty($post_image_width) && !empty($post_image_height)) {
                            $miniatureHtml .= '<img loading="lazy" decoding="async" width="' . htmlspecialchars($post_image_width, ENT_QUOTES, 'UTF-8') . '" height="' . htmlspecialchars($post_image_height, ENT_QUOTES, 'UTF-8') . '" src="' . htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') . '" srcset="' . htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') . ' 1x" alt="Post thumbnail" class="gb-mini__img">';
                        } else {
                            $miniatureHtml .= '<img loading="lazy" decoding="async" src="' . htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') . '" srcset="' . htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') . ' 1x" alt="Post thumbnail" class="gb-mini__img">';
                        }
                        
                        $miniatureHtml .= '</picture>';
                        $miniatureHtml .= '</a></div>';
                    }
                    
                    // Add content section
                    $miniatureHtml .= '<div class="gb-mini__content">';
                    
                    // Add category if available
                    if (!empty($category_name)) {
                        $miniatureHtml .= '<a href="' . htmlspecialchars($category_url, ENT_QUOTES, 'UTF-8') . '" target="_blank" class="gb-mini__tag">' . htmlspecialchars($category_name, ENT_QUOTES, 'UTF-8') . '</a>';
                    }
                    
                    // Add title with link
                    if (!empty($post_title)) {
                        $miniatureHtml .= '<a href="' . htmlspecialchars($post_url, ENT_QUOTES, 'UTF-8') . '" target="_blank" class="gb-mini__title">' . htmlspecialchars($post_title, ENT_QUOTES, 'UTF-8') . '</a>';
                        
                        // Add text as description
                        if (!empty($text)) {
                            $miniatureHtml .= '<p class="gb-mini__text">' . $text . '</p>';
                        }
                    } else if (!empty($text)) {
                        // Use text as standalone paragraph if no title
                        $miniatureHtml .= '<p class="gb-mini__text">' . $text . '</p>';
                    }
                    
                    // Add author if available
                    if (!empty($author_name)) {
                        $miniatureHtml .= '<a href="' . htmlspecialchars($author_url, ENT_QUOTES, 'UTF-8') . '" target="_blank" class="gb-mini__author">' . htmlspecialchars($author_name, ENT_QUOTES, 'UTF-8') . '</a>';
                    }
                    
                    $miniatureHtml .= '</div></div>';
                    
                    $miniature_count++;
                    
                    // Return as a core/html block to preserve structure
                    return '<!-- wp:html -->' . $miniatureHtml . '<!-- /wp:html -->';
                },
                $updated
            );

        /**
         * 1.1.9.1. Miniature Block Conversion (new format)
         */
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/miniature (.*?) (\/-->|\/>)/',
                function ($matches) use (&$miniature_count) {
                    $attrString = $matches[1];
                    $attrs = json_decode('{' . preg_replace('/^\{(.*)\}$/', '$1', $attrString) . '}', true);
                    
                    // Extract the necessary data from attributes
                    $title = isset($attrs['title']) ? $attrs['title'] : '';
                    $description = isset($attrs['description']) ? $attrs['description'] : '';
                    $descr = isset($attrs['descr']) ? $attrs['descr'] : '';
                    $image = isset($attrs['image']) ? $attrs['image'] : null;
                    $url = isset($attrs['url']) ? $attrs['url'] : '';
                    $altText = isset($attrs['altText']) ? $attrs['altText'] : '';
                    $linkTarget = isset($attrs['linkTarget']) ? $attrs['linkTarget'] : '_self';
                    $category = isset($attrs['category']) ? $attrs['category'] : '';
                    $categoryUrl = isset($attrs['categoryUrl']) ? $attrs['categoryUrl'] : '';
                    $author = isset($attrs['author']) ? $attrs['author'] : '';
                    $authorUrl = isset($attrs['authorUrl']) ? $attrs['authorUrl'] : '';
                    
                    // Handle post_id if it exists
                    $postId = isset($attrs['post_id']) ? $attrs['post_id'] : '';
                    
                    // Handle post_info if it exists - this is a JSON encoded string with post details
                    if (isset($attrs['post_info']) && !empty($attrs['post_info'])) {
                        $postInfo = $attrs['post_info'];
                        // Clean up unicode escape sequences in the JSON string
                        $postInfo = str_replace(['u003c', 'u003e', 'u0026', 'u0022', '\\'], ['<', '>', '&', '"', ''], $postInfo);
                        
                        try {
                            // Try to decode the JSON - using a more robust technique for the complex structure
                            $postInfoData = json_decode($postInfo, true);
                            if (!$postInfoData) {
                                // Try alternate approach if the first decode fails
                                $cleanedJson = '{' . preg_replace('/^\{(.*)\}$/', '$1', $postInfo) . '}';
                                $postInfoData = json_decode($cleanedJson, true);
                            }
                            
                            // Extract relevant information from post_info if decode successful
                            if ($postInfoData && is_array($postInfoData)) {
                                // Title
                                if (empty($title) && isset($postInfoData['title'])) {
                                    $title = $postInfoData['title'];
                                }
                                
                                // Link/URL
                                if (empty($url) && isset($postInfoData['link'])) {
                                    $url = $postInfoData['link'];
                                }
                                
                                // Description
                                if (empty($description) && empty($descr) && isset($postInfoData['descr'])) {
                                    $description = $postInfoData['descr'];
                                } else if (empty($description) && !empty($descr)) {
                                    $description = $descr;
                                }
                                
                                // Image
                                if (empty($image) && isset($postInfoData['img'])) {
                                    $imageData = $postInfoData['img'];
                                    if (is_array($imageData)) {
                                        $imageUrl = isset($imageData['src']) ? $imageData['src'] : '';
                                        $imageWidth = isset($imageData['width']) ? $imageData['width'] : '';
                                        $imageHeight = isset($imageData['height']) ? $imageData['height'] : '';
                                        $image = array('url' => $imageUrl, 'width' => $imageWidth, 'height' => $imageHeight);
                                    }
                                }
                                
                                // Category/Tag
                                if (empty($category) && isset($postInfoData['tag'])) {
                                    if (is_array($postInfoData['tag']) && !empty($postInfoData['tag'])) {
                                        if (isset($postInfoData['tag'][0]) && isset($postInfoData['tag'][0]['title'])) {
                                            $category = $postInfoData['tag'][0]['title'];
                                            if (isset($postInfoData['tag'][0]['link'])) {
                                                $categoryUrl = $postInfoData['tag'][0]['link'];
                                            }
                                        }
                                    } else if (is_array($postInfoData['tag']) && empty($postInfoData['tag'])) {
                                        // Tag array is empty, do nothing
                                    } else if (is_object($postInfoData['tag'])) {
                                        if (isset($postInfoData['tag']->title)) {
                                            $category = $postInfoData['tag']->title;
                                            if (isset($postInfoData['tag']->link)) {
                                                $categoryUrl = $postInfoData['tag']->link;
                                            }
                                        }
                                    }
                                }
                                
                                // Author
                                if (empty($author) && isset($postInfoData['author'])) {
                                    $authorData = $postInfoData['author'];
                                    if (is_array($authorData)) {
                                        $author = isset($authorData['name']) ? $authorData['name'] : '';
                                        $authorUrl = isset($authorData['link']) ? $authorData['link'] : '';
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            // If JSON decode fails, continue with what we have
                        }
                    }
                    
                    // Special handling for the block format seen in the test file
                    if (empty($title) && empty($url) && isset($attrs['post_id']) && !empty($attrs['post_id'])) {
                        $postId = $attrs['post_id'];
                        
                        // Try to get post data directly if postId exists and WP functions are available
                        if (function_exists('get_post')) {
                            $post = get_post($postId);
                            if ($post) {
                                $title = $post->post_title;
                                if (function_exists('get_permalink')) {
                                    $url = get_permalink($post);
                                }
                                if (function_exists('get_the_post_thumbnail_url')) {
                                    $imageUrl = get_the_post_thumbnail_url($post, 'full');
                                }
                                // Try to get author info
                                if (function_exists('get_the_author_meta')) {
                                    $author_id = $post->post_author;
                                    $author = get_the_author_meta('display_name', $author_id);
                                    if (function_exists('get_author_posts_url')) {
                                        $authorUrl = get_author_posts_url($author_id);
                                    }
                                }
                                // Try to get category
                                if (function_exists('get_the_category')) {
                                    $categories = get_the_category($postId);
                                    if (!empty($categories)) {
                                        $category = $categories[0]->name;
                                        if (function_exists('get_category_link')) {
                                            $categoryUrl = get_category_link($categories[0]->term_id);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    // Process image data if it exists
                    $imageUrl = '';
                    $imageWidth = '';
                    $imageHeight = '';
                    
                    if ($image && is_array($image)) {
                        $imageUrl = isset($image['url']) ? $image['url'] : '';
                        $imageWidth = isset($image['width']) ? $image['width'] : '';
                        $imageHeight = isset($image['height']) ? $image['height'] : '';
                    } elseif (is_string($image)) {
                        // Sometimes image might be a string URL
                        $imageUrl = $image;
                    }
                    
                    // If we have post_info in a special format with title/link encoded in JSON
                    if (isset($attrs['post_info']) && !empty($attrs['post_info'])) {
                        // This is a special case where post_info contains a JSON string with post metadata
                        // First clean up the string to make it valid JSON
                        $post_info = $attrs['post_info'];
                        
                        // Replace unicode escapes with actual characters
                        $post_info = str_replace(
                            ['u003c', 'u003e', 'u0026', 'u0022', '\\'],
                            ['<', '>', '&', '"', '\\'],
                            $post_info
                        );
                        
                        // Clean up start/end braces if they exist
                        $post_info = preg_replace('/^\{(.*)\}$/', '$1', $post_info);
                        
                        // Try with a much simpler approach - extract known fields directly with regex
                        if (empty($title)) {
                            if (preg_match('/"title":"([^"]+)"/', $post_info, $matches)) {
                                $title = $matches[1];
                            }
                        }
                        
                        if (empty($url)) {
                            if (preg_match('/"link":"([^"]+)"/', $post_info, $matches)) {
                                $url = $matches[1];
                            }
                        }
                        
                        if (empty($description) && preg_match('/"descr":"([^"]+)"/', $post_info, $matches)) {
                            $description = $matches[1];
                        }
                        
                        // Check if there's author information
                        if (empty($author) && preg_match('/"author":\{([^\}]+)\}/', $post_info, $matches)) {
                            $author_info = $matches[1];
                            if (preg_match('/"name":"([^"]+)"/', $author_info, $name_matches)) {
                                $author = $name_matches[1];
                            }
                            if (preg_match('/"link":"([^"]+)"/', $author_info, $link_matches)) {
                                $authorUrl = $link_matches[1];
                            }
                        }
                        
                        // Check for image information
                        if (empty($imageUrl) && preg_match('/"img":\{([^\}]+)\}/', $post_info, $matches)) {
                            $img_info = $matches[1];
                            if (preg_match('/"src":"([^"]+)"/', $img_info, $src_matches)) {
                                $imageUrl = $src_matches[1];
                            }
                            if (preg_match('/"width":"?([0-9]+)"?/', $img_info, $width_matches)) {
                                $imageWidth = $width_matches[1];
                            }
                            if (preg_match('/"height":"?([0-9]+)"?/', $img_info, $height_matches)) {
                                $imageHeight = $height_matches[1];
                            }
                        }
                    }
                    
                    // Decode HTML entities in text fields
                    $title = str_replace(['u003c', 'u003e', 'u0026', 'u0022', '\\'], ['<', '>', '&', '"', ''], $title);
                    $title = html_entity_decode(json_decode('"' . str_replace('"', '\\"', $title) . '"'));
                    
                    $description = str_replace(['u003c', 'u003e', 'u0026', 'u0022', '\\'], ['<', '>', '&', '"', ''], $description);
                    $description = html_entity_decode(json_decode('"' . str_replace('"', '\\"', $description) . '"'));
                    
                    $category = str_replace(['u003c', 'u003e', 'u0026', 'u0022', '\\'], ['<', '>', '&', '"', ''], $category);
                    $category = html_entity_decode(json_decode('"' . str_replace('"', '\\"', $category) . '"'));
                    
                    $author = str_replace(['u003c', 'u003e', 'u0026', 'u0022', '\\'], ['<', '>', '&', '"', ''], $author);
                    $author = html_entity_decode(json_decode('"' . str_replace('"', '\\"', $author) . '"'));
                    
                    // Extract existing classes - preserve exactly as they are
                    $existingClasses = '';
                    if (isset($attrs['className'])) {
                        $existingClasses = $attrs['className'];
                    }
                    
                    // If no class exists, use gb-mini for backward compatibility
                    $className = !empty($existingClasses) ? $existingClasses : 'gb-mini';
                    
                    $miniature_count++;
                    
                    // Create enhanced HTML structure based on the provided template
                    $miniatureHtml = '<div class="gb-mini">';
                    
                    // Add image section if available
                    if (!empty($imageUrl)) {
                        $miniatureHtml .= '<div class="gb-mini__image p-blog__image-shadow" aria-hidden="true">';
                        $miniatureHtml .= '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="' . htmlspecialchars($linkTarget, ENT_QUOTES, 'UTF-8') . '" tabindex="-1" class="gb-mini__image-link p-blog__image-link">';
                        $miniatureHtml .= '<span class="visually-hidden">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . ($linkTarget === '_blank' ? ' (opens in new tab)' : '') . '</span>';
                        $miniatureHtml .= '<picture>';
                        $miniatureHtml .= '<source srcset="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . ' 1x" type="image/png" media="(max-width: 499px)">';
                        $miniatureHtml .= '<source srcset="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . ' 1x" type="image/png" media="(min-width: 500px)">';
                        $miniatureHtml .= '<img loading="lazy" decoding="async" src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($altText, ENT_QUOTES, 'UTF-8') . '" class="gb-mini__img"';
                        if (!empty($imageWidth) && !empty($imageHeight)) {
                            $miniatureHtml .= ' width="' . intval($imageWidth) . '" height="' . intval($imageHeight) . '"';
                        }
                        $miniatureHtml .= '>';
                        $miniatureHtml .= '</picture>';
                        $miniatureHtml .= '</a></div>';
                    }
                    
                    // Add content section
                    $miniatureHtml .= '<div class="gb-mini__content">';
                    
                    // Add category if available
                    if (!empty($category)) {
                        $categoryLinkHtml = '<span class="gb-mini__tag">' . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . '</span>';
                        if (!empty($categoryUrl)) {
                            $miniatureHtml .= '<a href="' . htmlspecialchars($categoryUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" class="gb-mini__tag">' . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . '</a>';
                        } else {
                            $miniatureHtml .= '<span class="gb-mini__tag">' . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . '</span>';
                        }
                    }
                    
                    // Add title with link
                    if (!empty($title)) {
                        if (!empty($url)) {
                            $miniatureHtml .= '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" class="gb-mini__title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</a>';
                        } else {
                            $miniatureHtml .= '<span class="gb-mini__title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</span>';
                        }
                    }
                    
                    // Add description if available
                    if (!empty($description)) {
                        $miniatureHtml .= '<p class="gb-mini__text">' . $description . '</p>';
                    }
                    
                    // Add author if available
                    if (!empty($author)) {
                        $authorLinkHtml = '<span class="gb-mini__author">' . htmlspecialchars($author, ENT_QUOTES, 'UTF-8') . '</span>';
                        if (!empty($authorUrl)) {
                            $authorLinkHtml = '<a href="' . htmlspecialchars($authorUrl, ENT_QUOTES, 'UTF-8') . '" target="' . htmlspecialchars($linkTarget, ENT_QUOTES, 'UTF-8') . '" class="gb-mini__author">' . htmlspecialchars($author, ENT_QUOTES, 'UTF-8') . '</a>';
                        }
                        $miniatureHtml .= $authorLinkHtml;
                    }
                    
                    $miniatureHtml .= '</div></div>';
                    
                    // Return as a core/html block to preserve structure
                    return '<!-- wp:html -->' . $miniatureHtml . '<!-- /wp:html -->';
                },
                $updated
            );

        /**
         * 1.1.9.2. Post-Quote Block Conversion
         */
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/post-quote (.*?) \/-->/',
                function ($matches) use (&$quote_count) {
                    $attrString = $matches[1];
                    $attrs = json_decode('{' . preg_replace('/^\{(.*)\}$/', '$1', $attrString) . '}', true);
                    
                    // Extract content from the attributes
                    $content = isset($attrs['content']) ? $attrs['content'] : '';
                    $author = isset($attrs['author']) ? $attrs['author'] : '';
                    $featured = isset($attrs['featured']) && $attrs['featured'] === true ? true : false;
                    $hasPhoto = isset($attrs['photo']) && $attrs['photo'] === true ? true : false;
                    
                    // Decode HTML entities in content
                    $content = str_replace(['u003c', 'u003e', 'u0026', 'u0022', '\\'], ['<', '>', '&', '"', ''], $content);
                    $content = html_entity_decode(json_decode('"' . str_replace('"', '\\"', $content) . '"'));
                    
                    // Decode HTML entities in author
                    $author = str_replace(['u003c', 'u003e', 'u0026', 'u0022', '\\'], ['<', '>', '&', '"', ''], $author);
                    $author = html_entity_decode(json_decode('"' . str_replace('"', '\\"', $author) . '"'));
                    
                    // Determine className
                    $className = 'slgb-quote';
                    if (isset($attrs['className'])) {
                        $className .= ' ' . $attrs['className'];
                    }
                    
                    // Add style class based on featured status
                    $styleClass = $featured ? 'is-style-large' : 'is-style-normal';
                    
                    // Create quote block HTML with the proper structure
                    $quoteHtml = sprintf(
                        '<!-- wp:quote {"className":"wp-block-quote slgb-quote %s"} -->' .
                        '<blockquote class="wp-block-quote slgb-quote %s">' .
                        '<div class="gb-quote__content">' .
                        '<p>%s</p>' .
                        '</div>',
                        htmlspecialchars($styleClass, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($styleClass, ENT_QUOTES, 'UTF-8'),
                        $content
                    );
                    
                    // Add citation if author exists
                    if (!empty($author)) {
                        $quoteHtml .= sprintf('<cite>%s</cite>', $author);
                    }
                    
                    // Close the quote block
                    $quoteHtml .= '</blockquote><!-- /wp:quote -->';
                    
                    $quote_count++;
                    
                    return $quoteHtml;
                },
                $updated
            );
            
        /**
         * 1.1.10. CTA Block Conversion
         */
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/gb-cta (.*?) \/-->/',
                function ($matches) use (&$cta_count) {
                    $attrString = $matches[1];
                    
                    // Extract existing classes - preserve exactly as they are
                    $existingClasses = '';
                    if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                        $existingClasses = $classMatch[1];
                    }
                    
                    // If no class exists, use slgb-cta for backwards compatibility
                    $className = !empty($existingClasses) ? $existingClasses : 'slgb-cta';
                    
                    // Extract the title
                    $title = '';
                    if (preg_match('/"id="h-%s" tabindex="-1"(.*?)"/', $attrString, $titleMatch)) {
                        $title = json_decode('"' . $titleMatch[1] . '"');
                        // Convert escaped HTML entities back to HTML tags
                        $title = str_replace(['\\u003c', '\\u003e'], ['<', '>'], $title);
                    }
                    
                    // Extract the description
                    $description = '';
                    if (preg_match('/"description":"(.*?)"/', $attrString, $descMatch)) {
                        $description = json_decode('"' . $descMatch[1] . '"');
                    }
                    
                    // Extract button info
                    $btn_text = 'Learn More';
                    $btn_link = '#';
                    $btn_blank = false;
                    $btn2_text = '';
                    $btn2_link = '';
                    $btn2_blank = false;
                    
                    if (preg_match('/"btn_text":"(.*?)"/', $attrString, $btnTextMatch)) {
                        $btn_text = json_decode('"' . $btnTextMatch[1] . '"');
                    }
                    
                    if (preg_match('/"btn_link":"(.*?)"/', $attrString, $btnLinkMatch)) {
                        $btn_link = $btnLinkMatch[1];
                    }
                    
                    if (preg_match('/"btn_blank":(true|false)/', $attrString, $btnBlankMatch)) {
                        $btn_blank = $btnBlankMatch[1] === 'true';
                    }
                    
                    // Check for second button
                    $has_second_btn = false;
                    if (preg_match('/"btn2_text":"(.*?)"/', $attrString, $btn2TextMatch)) {
                        $btn2_text = json_decode('"' . $btn2TextMatch[1] . '"');
                        $has_second_btn = !empty($btn2_text);
                    }
                    
                    if (preg_match('/"btn2_link":"(.*?)"/', $attrString, $btn2LinkMatch)) {
                        $btn2_link = $btn2LinkMatch[1];
                    }
                    
                    if (preg_match('/"btn2_blank":(true|false)/', $attrString, $btn2BlankMatch)) {
                        $btn2_blank = $btn2BlankMatch[1] === 'true';
                    }
                    
                    $cta_count++;
                    
                    // Create a group with heading, paragraph, and buttons
                    $output = sprintf(
                        '<!-- wp:group {"className":"%s"} -->' . "\n" .
                        '<div class="wp-block-group %s">' . "\n",
                        esc_attr($className),
                        esc_attr($className)
                    );
                    
                    // Add title if exists
                    if (!empty($title)) {
                        $output .= sprintf(
                            '<!-- wp:heading {"textAlign":"center"} -->' . "\n" .
                            '<h2 class="wp-block-heading has-text-align-center">%s</h2>' . "\n" .
                            '<!-- /wp:heading -->' . "\n\n",
                            $title // Do not escape HTML here to preserve tags
                        );
                    }
                    
                    // Add description if exists
                    if (!empty($description)) {
                        $output .= sprintf(
                            '<!-- wp:paragraph {"align":"center"} -->' . "\n" .
                            '<p class="has-text-align-center">%s</p>' . "\n" .
                            '<!-- /wp:paragraph -->' . "\n\n",
                            esc_html($description)
                        );
                    }
                    
                    // Add buttons
                    $output .= '<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->' . "\n" .
                               '<div class="wp-block-buttons">' . "\n";
                    
                    // Add primary button
                    $target_attr = $btn_blank ? ' target="_blank" rel="noreferrer noopener"' : '';
                    $output .= sprintf(
                        '<!-- wp:button {"className":"is-style-fill"} -->' . "\n" .
                        '<div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button" href="%s"%s>%s</a></div>' . "\n" .
                        '<!-- /wp:button -->' . "\n",
                        esc_url($btn_link),
                        $target_attr,
                        esc_html($btn_text)
                    );
                    
                    // Add secondary button if exists
                    if ($has_second_btn) {
                        $target_attr2 = $btn2_blank ? ' target="_blank" rel="noreferrer noopener"' : '';
                        $output .= sprintf(
                            '<!-- wp:button {"className":"is-style-outline"} -->' . "\n" .
                            '<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="%s"%s>%s</a></div>' . "\n" .
                            '<!-- /wp:button -->' . "\n",
                            esc_url($btn2_link),
                            $target_attr2,
                            esc_html($btn2_text)
                        );
                    }
                    
                    $output .= '</div>' . "\n" .
                               '<!-- /wp:buttons -->' . "\n" .
                               '</div>' . "\n" .
                               '<!-- /wp:group -->';
                    
                    return $output;
                },
                $updated
            );

        /**
         * 1.1.11. GB Emphasis Block Conversion
         */
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/gb-emph (.*?)-->(.*?)<!-- \/wp:slgb\/gb-emph -->/s',
                function ($matches) use (&$gb_emph_count) {
                    $attrString = $matches[1];
                    $content = $matches[2];
                    
                    // Extract existing classes - preserve exactly as they are
                    $existingClasses = '';
                    if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                        $existingClasses = $classMatch[1];
                    }
                    
                    // If no class exists, use slgb-gb-emph for backwards compatibility
                    $className = !empty($existingClasses) ? $existingClasses : 'slgb-gb-emph';
                    
                    // First look for all WordPress blocks within the content, including slgb/image
                    $allBlocks = [];
                    preg_match_all('/<!-- wp:([\w\/\-]+)(.*?)-->(.*?)<!-- \/wp:\1 -->/s', $content, $blockMatches, PREG_SET_ORDER);
                    
                    // Process all the blocks we found
                    foreach ($blockMatches as $blockMatch) {
                        $blockType = $blockMatch[1];
                        $blockContent = $blockMatch[0];
                        $position = strpos($content, $blockContent);
                        
                        if ($position !== false) {
                            $allBlocks[$position] = [
                                'type' => $blockType,
                                'content' => $blockContent
                            ];
                        }
                    }
                    
                    // Sort blocks by their position in the original content
                    ksort($allBlocks);
                    
                    // Extract the content from inside the div to preserve other HTML elements
                    $divContent = '';
                    $hasDiv = false;
                    
                    if (preg_match('/<div class="gb-emph">(.*?)<\/div>/s', $content, $divMatch)) {
                        $divContent = $divMatch[1];
                        $hasDiv = true;
                    }
                    
                    // If we found blocks, we'll explicitly include them in our output
                    $processedContent = '';
                    if (!empty($allBlocks)) {
                        // Build content with all blocks in order
                        foreach ($allBlocks as $block) {
                            $processedContent .= $block['content'] . "\n";
                        }
                    } else if ($hasDiv) {
                        // If no blocks but we have a div, use its content
                        $processedContent = $divContent;
                    } else {
                        // Fallback to original content
                        $processedContent = $content;
                    }
                    
                    $gb_emph_count++;
                    
                    // Create a group block with the div class="gb-emph" structure to maintain the distinction from regular emph blocks
                    return sprintf(
                        '<!-- wp:group {"className":"%s"} -->' . "\n" .
                        '<div class="wp-block-group %s">' . "\n" .
                        '<div class="gb-emph">%s</div>' . "\n" .
                        '</div>' . "\n" .
                        '<!-- /wp:group -->',
                        esc_attr($className),
                        esc_attr($className),   
                        $processedContent
                    );
                },
                $updated
            );

        /**
         * 1.2. New Block Type Handlers
         */

        /**
         * 1.2.0. Miniature Block Conversion (different from p-miniature)
         */
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/miniature (.*?) \/-->/',
                function ($matches) use (&$miniature_count) {
                    $attrString = $matches[1];
                    
                    // Extract post_id and post_info
                    preg_match('/."post_id":"([^"]*)"./', $attrString, $postIdMatch);
                    preg_match('/."post_info":"([^"]*)"./', $attrString, $postInfoMatch);
                    
                    $post_id = !empty($postIdMatch) ? $postIdMatch[1] : '';
                    $post_info_encoded = !empty($postInfoMatch) ? $postInfoMatch[1] : '';
                    
                    // Handle description field
                    $descr = '';
                    $show_descr = false;
                    
                    if (preg_match('/."descr":"([^"]*)"./', $attrString, $descrMatch)) {
                        $descr = str_replace(['u003c', 'u003e', 'u0026', 'u0022', 'u003d'], ['<', '>', '&', '"', '='], $descrMatch[1]);
                    }
                    
                    if (preg_match('/."show_descr":(true|false)./', $attrString, $showDescrMatch)) {
                        $show_descr = $showDescrMatch[1] === 'true';
                    }
                    
                    // Extract existing classes
                    $className = 'slgb-miniature';
                    if (preg_match('/."className":"([^"]*)"./', $attrString, $classMatch)) {
                        $className = $classMatch[1];
                    }
                    
                    // Wrap the original shortcode in a paragraph to preserve all attributes and structure
                    $miniature_count++;
                    
                    return sprintf('<!-- wp:paragraph --><p><!-- wp:slgb/miniature %s /--></p><!-- /wp:paragraph -->', $attrString);
                },
                $updated
            );

        /**
         * 1.2.1. Postimage Block Conversion
         */
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/postimage (.*?) \/-->/',
                function ($matches) use (&$postimage_count) {
                    $attrString = $matches[1];
                    
                    // Extract the required attributes
                    preg_match('/"id":"(\d+)"/', $attrString, $idMatch);
                    preg_match('/"url":"([^"]*)"/', $attrString, $urlMatch);
                    preg_match('/"alt":"([^"]*)"/', $attrString, $altMatch);
                    preg_match('/"width":"([^"]*)"/', $attrString, $widthMatch);
                    preg_match('/"height":"([^"]*)"/', $attrString, $heightMatch);
                    
                    // Check if we have the necessary data
                    if (!empty($urlMatch)) {
                        $id = !empty($idMatch) ? (int)$idMatch[1] : 0;
                        $url = $urlMatch[1];
                        $alt = !empty($altMatch) ? json_decode('"' . $altMatch[1] . '"') : '';
                        $width = !empty($widthMatch) ? (int)$widthMatch[1] : 0;
                        $height = !empty($heightMatch) ? (int)$heightMatch[1] : 0;
                        
                        // Extract any existing classes - preserve exactly as they are
                        $className = '';
                        if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                            $className = $classMatch[1];
                        }
                        
                        $postimage_count++;
                        
                        // Convert to core/image block
                        $blockAttrs = array(
                            'id' => $id,
                            'sizeSlug' => 'full',
                        );
                        
                        if (!empty($className)) {
                            $blockAttrs['className'] = $className;
                        }
                        
                        if (!empty($width) && !empty($height)) {
                            $blockAttrs['width'] = $width;
                            $blockAttrs['height'] = $height;
                        }
                        
                        $blockAttrString = json_encode($blockAttrs);
                        
                        return sprintf(
                            '<!-- wp:image %s --><figure class="wp-block-image size-full"><img src="%s" alt="%s" class="wp-image-%d"%s/></figure><!-- /wp:image -->',
                            $blockAttrString,
                            esc_url($url),
                            esc_attr($alt),
                            $id,
                            !empty($width) && !empty($height) ? sprintf(' width="%d" height="%d"', $width, $height) : ''
                        );
                    }
                    
                    // If we can't extract the necessary data, return the original
                    return $matches[0];
                },
                $updated
            );
            
        /**
         * 1.2.2. Dos-Donts Block Conversion
         */
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/dos-donts (.*?) \/-->/',
                function ($matches) use (&$dosdont_count) {
                    $attrString = $matches[1];
                    
                    // Extract existing classes - preserve exactly
                    $existingClasses = '';
                    if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                        $existingClasses = $classMatch[1];
                    }
                    
                    // Default class if none exists
                    $className = !empty($existingClasses) ? $existingClasses : 'slgb-dosdont';
                    
                    // Try to extract the cells data
                    if (preg_match('/"cells":"(.*?)"/', $attrString, $cellsMatch)) {
                        try {
                            // Replace unicode escape sequences with actual characters
                            $processedJson = str_replace(
                                ['u0022', 'u003c', 'u003e', 'u003d', '\\u0022'],
                                ['"', '<', '>', '=', '"'],
                                $cellsMatch[1]
                            );
                            
                            // Debug log
                            error_log('Processed JSON before decode: ' . $processedJson);
                            
                            // Different approach for decoding
                            $cells = json_decode($processedJson, true);
                            
                            if (is_array($cells)) {
                                // Get the header values from the attributes if available
                                $dosHeader = '<span>DO 👍</span>';
                                $dontsHeader = '<span>DO NOT 👎</span>';
                                
                                // Extract dos header if available
                                if (preg_match('/"dos":"(.*?)"/', $attrString, $dosHeaderMatch)) {
                                    $headerText = $dosHeaderMatch[1];
                                    // Replace unicode escapes
                                    $headerText = str_replace(
                                        ['u003c', 'u003e', 'u003d', 'u0022', '\\u003c', '\\u003e', '\\u003d', '\\u0022'],
                                        ['<', '>', '=', '"', '<', '>', '=', '"'],
                                        $headerText
                                    );
                                    
                                    // Try to decode if needed
                                    $decodedHeader = json_decode('"' . str_replace('"', '\\"', $headerText) . '"', true);
                                    if ($decodedHeader) {
                                        $dosHeader = $decodedHeader;
                                    } else {
                                        $dosHeader = $headerText;
                                    }
                                    
                                    // If it's not already wrapped in a span, wrap it
                                    if (strpos($dosHeader, '<span') === false) {
                                        $dosHeader = '<span>' . $dosHeader . '</span>';
                                    }
                                    
                                    // Add thumbs up emoji if not already present
                                    if (strpos($dosHeader, '👍') === false) {
                                        // Remove the closing span tag
                                        $dosHeader = str_replace('</span>', '', $dosHeader);
                                        // Append emoji and closing tag
                                        $dosHeader .= ' 👍</span>';
                                    }
                                }
                                
                                // Extract donts header if available
                                if (preg_match('/"donts":"(.*?)"/', $attrString, $dontsHeaderMatch)) {
                                    $headerText = $dontsHeaderMatch[1];
                                    // Replace unicode escapes
                                    $headerText = str_replace(
                                        ['u003c', 'u003e', 'u003d', 'u0022', '\\u003c', '\\u003e', '\\u003d', '\\u0022'],
                                        ['<', '>', '=', '"', '<', '>', '=', '"'],
                                        $headerText
                                    );
                                    
                                    // Try to decode if needed
                                    $decodedHeader = json_decode('"' . str_replace('"', '\\"', $headerText) . '"', true);
                                    if ($decodedHeader) {
                                        $dontsHeader = $decodedHeader;
                                    } else {
                                        $dontsHeader = $headerText;
                                    }
                                    
                                    // If it's not already wrapped in a span, wrap it
                                    if (strpos($dontsHeader, '<span') === false) {
                                        $dontsHeader = '<span>' . $dontsHeader . '</span>';
                                    }
                                    
                                    // Add thumbs down emoji if not already present
                                    if (strpos($dontsHeader, '👎') === false) {
                                        // Remove the closing span tag
                                        $dontsHeader = str_replace('</span>', '', $dontsHeader);
                                        // Append emoji and closing tag
                                        $dontsHeader .= ' 👎</span>';
                                    }
                                }
                                
                                // Build a custom dos-donts HTML structure based on the image
                                $tableHtml = '<div class="gb-dos-dr gb-dos-thumbs">' . "\n";
                                
                                // Add table with proper structure and classes
                                $tableHtml .= '<table class="gb-dos-gb-dos-thumbs ' . htmlspecialchars($className, ENT_QUOTES, 'UTF-8') . '">' . "\n";
                                
                                // Add headers
                                $tableHtml .= '<thead>' . "\n" . '<tr>' . "\n";
                                $tableHtml .= '<th class="gb-dos-th gb-dos-th-thumbs">' . "\n";
                                $tableHtml .= '<div class="gb-dos-heading">' . "\n";
                                $tableHtml .= $dosHeader . "\n";
                                $tableHtml .= '</div>' . "\n" . '</th>' . "\n";
                                
                                $tableHtml .= '<th class="gb-dos-td gb-dos-th-thumbs">' . "\n";
                                $tableHtml .= '<div class="gb-dos-heading">' . "\n";
                                $tableHtml .= $dontsHeader . "\n";
                                $tableHtml .= '</div>' . "\n" . '</th>' . "\n";
                                
                                $tableHtml .= '</tr>' . "\n" . '</thead>' . "\n";
                                
                                // Add body
                                $tableHtml .= '<tbody>' . "\n";
                                
                                foreach ($cells as $row) {
                                    // Extract do and dont, handling both possible key formats
                                    $do = '';
                                    if (isset($row['do'])) {
                                        $do = $row['do'];
                                    } elseif (isset($row['dou0022'])) {
                                        $do = $row['dou0022'];
                                    }
                                    
                                    $dont = '';
                                    if (isset($row['dont'])) {
                                        $dont = $row['dont'];
                                    } elseif (isset($row['dontu0022'])) {
                                        $dont = $row['dontu0022'];
                                    }
                                    
                                    // Additional clean-up of HTML entities
                                    $do = str_replace(['u003cp', 'pu003e'], ['<p', 'p>'], $do);
                                    $dont = str_replace(['u003cp', 'pu003e'], ['<p', 'p>'], $dont);
                                    
                                    $tableHtml .= '<tr>' . "\n";
                                    $tableHtml .= '<td class="gb-dos-td gb-dos-td-thumbs">' . $do . '</td>' . "\n";
                                    $tableHtml .= '<td class="gb-dos-td gb-dos-td-thumbs">' . $dont . '</td>' . "\n";
                                    $tableHtml .= '</tr>' . "\n";
                                }
                                
                                $tableHtml .= '</tbody>' . "\n" . '</table>' . "\n" . '</div>';
                                $dosdont_count++;
                                
                                // Create a core/html block with the table
                                return sprintf(
                                    '<!-- wp:html -->' . "\n" . '%s' . "\n" . '<!-- /wp:html -->',
                                    $tableHtml
                                );
                            }
                        } catch (Exception $e) {
                            // Log error for debugging
                            error_log('SLGB DosDonts Conversion Error: ' . $e->getMessage());
                        }
                    }
                    
                    // If extraction fails, return the original
                    return $matches[0];
                },
                $updated
            );
            
        /**
         * 1.2.3. YouTube Block Conversion
         */
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/youtube (.*?) \/-->/',
                function ($matches) use (&$youtube_count) {
                    $attrString = $matches[1];
                    
                    // Extract the src URL
                    preg_match('/"src":"([^"]*)"/', $attrString, $srcMatch);
                    
                    if (!empty($srcMatch)) {
                        $src = $srcMatch[1];
                        $youtube_count++;
                        
                        // Fix the URL to ensure it's in the format that WordPress embed expects
                        // If URL contains /embed/, convert to standard YouTube URL
                        if (strpos($src, '/embed/') !== false) {
                            // Extract video ID by splitting at /embed/
                            $parts = explode('/embed/', $src);
                            if (!empty($parts[1])) {
                                // Remove any query parameters
                                $videoId = explode('?', $parts[1])[0];
                                // Use standard YouTube URL format for embeds
                                $src = 'https://www.youtube.com/embed/' . $videoId;
                            }
                        }
                        
                        // Convert to core/embed YouTube block with iframe
                        return sprintf(
                            '<!-- wp:embed {"url":"%s","type":"rich","providerNameSlug":"youtube","responsive":true,"className":"wp-embed-aspect-16-9 wp-has-aspect-ratio"} -->' . 
                            '<figure class="wp-block-embed is-type-rich is-provider-youtube wp-block-embed-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio">' . 
                            '<div class="wp-block-embed__wrapper"><iframe width="560" height="315" src="%s" frameborder="0" allowfullscreen></iframe></div></figure>' . 
                            '<!-- /wp:embed -->',
                            esc_url($src),
                            esc_url($src)
                        );
                    }
                    
                    // If extraction fails, return the original
                    return $matches[0];
                },
                $updated
            );
            
        /**
         * 1.2.4. P-YouTube Block Conversion
         */
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/p-youtube (.*?) -->(.*?)<!-- \/wp:slgb\/p-youtube -->/s',
                function ($matches) use (&$pytube_count) {
                    $attrString = $matches[1];
                    $content = $matches[2];
                    
                    // Extract title and src
                    preg_match('/."title":"([^"]*)"/', $attrString, $titleMatch);
                    preg_match('/."src":"([^"]*)"/', $attrString, $srcMatch);
                    
                    // Extract cover image data if available
                    $coverImageId = 0;
                    $coverImageSrc = '';
                    $coverImageAlt = '';
                    
                    if (preg_match('/."cover":\{([^\}]*)\}/', $attrString, $coverMatch)) {
                        $coverData = '{' . $coverMatch[1] . '}';
                        $coverData = str_replace(['u0022', 'u003a', 'u002c'], ['"', ':', ','], $coverData);
                        
                        // Try to decode the cover data
                        try {
                            $coverJson = json_decode($coverData, true);
                            if (is_array($coverJson)) {
                                if (isset($coverJson['id'])) {
                                    $coverImageId = intval($coverJson['id']);
                                }
                                if (isset($coverJson['src'])) {
                                    $coverImageSrc = $coverJson['src'];
                                }
                                if (isset($coverJson['alt'])) {
                                    $coverImageAlt = $coverJson['alt'];
                                }
                            }
                        } catch (Exception $e) {
                            // Fail silently and continue without cover image
                        }
                    }
                    
                    // Alternatively try to extract from the iframe if available
                    if (empty($srcMatch) && preg_match('/src="([^"]*)"/', $content, $iframeSrcMatch)) {
                        $src = $iframeSrcMatch[1];
                    } else if (!empty($srcMatch)) {
                        $src = $srcMatch[1];
                    } else {
                        // If we can't extract the source, return the original
                        return $matches[0];
                    }
                    
                    // Fix the YouTube URL format if needed (similar to slgb/youtube handler)
                    if (strpos($src, '/embed/') !== false) {
                        $parts = explode('/embed/', $src);
                        if (!empty($parts[1])) {
                            $videoId = explode('?', $parts[1])[0];
                            $src = 'https://www.youtube.com/embed/' . $videoId;
                        }
                    }
                    
                    $title = !empty($titleMatch) ? json_decode('"' . $titleMatch[1] . '"') : '';
                    
                    $pytube_count++;
                    
                    // Convert to core/embed YouTube block with iframe and figure caption if title available
                    $embed = sprintf(
                        '<!-- wp:embed {"url":"%s","type":"rich","providerNameSlug":"youtube","responsive":true,"className":"wp-embed-aspect-16-9 wp-has-aspect-ratio"} -->' . 
                        '<figure class="wp-block-embed is-type-rich is-provider-youtube wp-block-embed-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio">' . 
                        '<div class="wp-block-embed__wrapper"><iframe width="560" height="315" src="%s" frameborder="0" allowfullscreen></iframe></div>',
                        esc_url($src),
                        esc_url($src)
                    );
                    
                    // Add caption if title exists
                    if (!empty($title)) {
                        $embed .= sprintf(
                            '<figcaption class="wp-element-caption">%s</figcaption>',
                            esc_html($title)
                        );
                    }
                    
                    $embed .= '</figure>' . '<!-- /wp:embed -->';
                    
                    return $embed;
                },
                $updated
            );
            
        /**
         * 1.2.5. P-Btns Block Conversion
         */
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/p-btns -->(.*?)<!-- \/wp:slgb\/p-btns -->/s',
                function ($matches) use (&$pbtns_count) {
                    $content = $matches[1];
                    
                    // Extract link URL and target from the content
                    preg_match('/<a href="([^"]*)"([^>]*)>/', $content, $linkMatch);
                    
                    if (!empty($linkMatch)) {
                        $url = $linkMatch[1];
                        $attributes = $linkMatch[2];
                        $isNewTab = strpos($attributes, 'target="_blank"') !== false;
                        
                        $pbtns_count++;
                        
                        // Create button block
                        $buttonAttrs = array(
                            'url' => $url,
                        );
                        
                        if ($isNewTab) {
                            $buttonAttrs['opensInNewTab'] = true;
                        }
                        
                        // Find any button text
                        $buttonText = 'Click Here'; // Default text
                        if (preg_match('/<a[^>]*>(.*?)<\/a>/', $content, $textMatch)) {
                            if (!empty($textMatch[1])) {
                                $buttonText = $textMatch[1];
                            }
                        }
                        
                        return sprintf(
                            '<!-- wp:buttons -->' . "\n" .
                            '<div class="wp-block-buttons">' . "\n" .
                            '<!-- wp:button %s -->' . "\n" .
                            '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="%s"%s>%s</a></div>' . "\n" .
                            '<!-- /wp:button -->' . "\n" .
                            '</div>' . "\n" .
                            '<!-- /wp:buttons -->',
                            json_encode($buttonAttrs),
                            esc_url($url),
                            $isNewTab ? ' target="_blank" rel="noreferrer noopener"' : '',
                            $buttonText
                        );
                    }
                    
                    // If extraction fails, fall back to a simple button
                    $pbtns_count++;
                    return '<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"className":"is-style-fill"} --><div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button">Button</a></div><!-- /wp:button --></div><!-- /wp:buttons -->';
                },
                $updated
            );

        /**
         * 1.2.6. Post-Quote Block Conversion
         */
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/post-quote (.*?) -->(.*?)<!-- \/wp:slgb\/post-quote -->/s',
                function ($matches) use (&$post_quote_count) {
                    $attrString = $matches[1];
                    $content = isset($matches[2]) ? $matches[2] : '';
                    
                    // Extract featured status
                    $featured = false;
                    if (preg_match('/"featured":(true|false)/', $attrString, $featuredMatch)) {
                        $featured = $featuredMatch[1] === 'true';
                    }
                    
                    // Extract quote content
                    $quote_text = '';
                    if (preg_match('/"content":"(.*?)"(?:,|})/s', $attrString, $contentMatch)) {
                        // Decode unicode and HTML entities
                        $encodedContent = $contentMatch[1];
                        $encodedContent = str_replace(['u003c', 'u003e', 'u0026', 'u0022', 'u003d', 'u0020'], 
                                                    ['<', '>', '&', '"', '=', ' '], 
                                                    $encodedContent);
                        $quote_text = html_entity_decode(json_decode('"' . str_replace('"', '\\"', $encodedContent) . '"'));
                    } else if (!empty($content)) {
                        $quote_text = $content;
                    }
                    
                    // Extract author
                    $author = '';
                    if (preg_match('/"author":"(.*?)"/', $attrString, $authorMatch)) {
                        $author = json_decode('"' . $authorMatch[1] . '"');
                    }
                    
                    // Extract CSS classes
                    $className = 'slgb-post-quote';
                    if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                        $className = $classMatch[1];
                    }
                    
                    // Add featured style if needed
                    $styles = [];
                    if ($featured) {
                        $styles[] = 'is-style-large';
                    }
                    
// Create class attribute with all classes
                    $classNames = array_merge([$className], $styles);
                    $classAttr = implode(' ', array_unique(array_filter($classNames)));
                    
                    $post_quote_count++;
                    
                    // Create a core/quote block
                    $output = sprintf(
                        '<!-- wp:quote {"className":"wp-block-quote slgb-quote %s"} -->' . "\n" .
                        '<blockquote class="wp-block-quote slgb-quote %s">' . "\n" .
                        '<div class="gb-quote__content"><p>%s</p></div>',
                        esc_attr($classAttr),
                        esc_attr($classAttr),
                        $quote_text
                    );
                    
                    // Add citation if author exists
                    if (!empty($author)) {
                        $output .= sprintf('<cite>%s</cite>' . "\n", esc_html($author));
                    }
                    
                    $output .= '</blockquote>
<!-- /wp:quote -->';
                    
                    return $output;
                },
                $updated
            );

        /**
         * 1.2.7. P-Hints-Row and P-Hints-Cell Block Conversion
         */
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/p-hints-row -->(.*?)<!-- \/wp:slgb\/p-hints-row -->/s',
                function ($matches) use (&$p_hints_count) {
                    $content = $matches[1];
                    
                    // Extract cells data - p-hints-cell blocks are nested in p-hints-row
                    preg_match_all('/<\!-- wp:slgb\/p-hints-cell -->(.*?)<!-- \/wp:slgb\/p-hints-cell -->/s', $content, $cellMatches);
                    
                    if (!empty($cellMatches[1])) {
                        $cellContents = $cellMatches[1];
                        
                        // Convert to a table row
                        $rowHtml = '<tr>';
                        foreach ($cellContents as $cellContent) {
                            // Keep the existing td content structure without adding extra td tags
                            $rowHtml .= $cellContent;
                        }
                        $rowHtml .= '</tr>';
                        
                        $p_hints_count++;
                        
                        return $rowHtml;
                    }
                    
                    // If extraction fails, return the original content in a single row
                    $p_hints_count++;
                    return '<tr><td>' . $content . '</td></tr>';
                },
                $updated
            );

        /**
         * 1.2.8. P-Comparison and P-Comparison-Row Block Conversion
         */
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/p-comparison (.*?) -->(.*?)<!-- \/wp:slgb\/p-comparison -->/s',
                function ($matches) use (&$p_comparison_count) {
                    $attrString = $matches[1];
                    $content = $matches[2];
                    
                    // Parse attributes properly
                    $attrs = json_decode('{' . preg_replace('/^\{(.*)\}$/', '$1', $attrString) . '}', true);
                    
                    // Extract column titles
                    $leftColumnTitle = isset($attrs['leftColumnTitle']) ? $attrs['leftColumnTitle'] : '';
                    $rightColumnTitle = isset($attrs['rightColumnTitle']) ? $attrs['rightColumnTitle'] : '';
                    
                    // Improved HTML entity decoding for column titles
                    if (!empty($leftColumnTitle)) {
                        $leftColumnTitle = str_replace(['u003c', 'u003e', 'u0026', 'u0022', '\\'], ['<', '>', '&', '"', ''], $leftColumnTitle);
                        $leftColumnTitle = html_entity_decode($leftColumnTitle);
                    }
                    
                    if (!empty($rightColumnTitle)) {
                        $rightColumnTitle = str_replace(['u003c', 'u003e', 'u0026', 'u0022', '\\'], ['<', '>', '&', '"', ''], $rightColumnTitle);
                        $rightColumnTitle = html_entity_decode($rightColumnTitle);
                    }
                    
                    // Extract existing classes or set default
                    $className = isset($attrs['className']) ? $attrs['className'] : 'slgb-p-comparison';
                    
                    // Process existing table if it exists in the content
                    $tableContent = '';
                    if (preg_match('/<table.*?>(.*?)<\/table>/s', $content, $tableMatch)) {
                        $tableContent = $tableMatch[1];
                        
                        // Make sure table content has decoded HTML entities
                        $tableContent = str_replace('&lt;', '<', $tableContent);
                        $tableContent = str_replace('&gt;', '>', $tableContent);
                        $tableContent = str_replace('&amp;', '&', $tableContent);
                        $tableContent = str_replace('&quot;', '"', $tableContent);
                    } else {
                        // Build table content from scratch if no table found
                        $tableContent = '<thead><tr><th></th>';
                        
                        if (!empty($leftColumnTitle)) {
                            $tableContent .= '<th>' . $leftColumnTitle . '</th>';
                        } else {
                            $tableContent .= '<th>Column 1</th>';
                        }
                        
                        if (!empty($rightColumnTitle)) {
                            $tableContent .= '<th>' . $rightColumnTitle . '</th>';
                        } else {
                            $tableContent .= '<th>Column 2</th>';
                        }
                        
                        $tableContent .= '</tr></thead><tbody>';
                        
                        // Handle nested p-comparison-row blocks 
                        preg_match_all('/<\!-- wp:slgb\/p-comparison-row (.*?) -->(.*?)<!-- \/wp:slgb\/p-comparison-row -->/s', $content, $rowMatches);
                        
                        if (!empty($rowMatches[0])) {
                            foreach ($rowMatches[1] as $index => $rowAttrString) {
                                $rowContent = $rowMatches[2][$index];
                                $rowAttrs = json_decode('{' . preg_replace('/^\{(.*)\}$/', '$1', $rowAttrString) . '}', true);
                                
                                // Extract row title, leftColumn, and rightColumn attributes
                                $rowTitle = isset($rowAttrs['title']) ? $rowAttrs['title'] : '';
                                $leftColumn = isset($rowAttrs['leftColumn']) ? $rowAttrs['leftColumn'] : '';
                                $rightColumn = isset($rowAttrs['rightColumn']) ? $rowAttrs['rightColumn'] : '';
                                
                                // Improved HTML entity decoding
                                $leftColumn = str_replace(['u003c', 'u003e', 'u0026', 'u0022', '\\'], ['<', '>', '&', '"', ''], $leftColumn);
                                $leftColumn = html_entity_decode($leftColumn);
                                
                                $rightColumn = str_replace(['u003c', 'u003e', 'u0026', 'u0022', '\\'], ['<', '>', '&', '"', ''], $rightColumn);
                                $rightColumn = html_entity_decode($rightColumn);
                                
                                // Add row to table with proper class
                                $tableContent .= '<tr class="slgb-p-comparison-row">';
                                $tableContent .= '<td class="slgb-p-comparison-cell">' . $rowTitle . '</td>';
                                $tableContent .= '<td class="slgb-p-comparison-cell">' . $leftColumn . '</td>';
                                $tableContent .= '<td class="slgb-p-comparison-cell">' . $rightColumn . '</td>';
                                $tableContent .= '</tr>';
                            }
                        }
                        
                        $tableContent .= '</tbody>';
                    }
                    
                    $p_comparison_count++;
                    
                    // Create HTML table with appropriate class
                    $tableHtml = '<table class="' . $className . '">' . $tableContent . '</table>';
                    
                    // Return as a core/html block
                    return sprintf(
                        '<!-- wp:html -->' . "\n" . 
                        '%s' . "\n" . 
                        '<!-- /wp:html -->',
                        $tableHtml
                    );
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

        /**
         * 1.3. Conversion Results Display
         */
        add_action('admin_notices', function () use (
            $count, $heading_count, $emph_count, $image_count, 
            $table_count, $subscribe_count, $compare_count, 
            $hints_count, $quote_count, $miniature_count, 
            $cta_count, $gb_emph_count,
            // New counters 
            $postimage_count, $dosdont_count, $youtube_count, $pytube_count,
            $pbtns_count, $post_quote_count, $p_hints_count, $p_comparison_count
        ) {
            echo '<div class="notice notice-success"><p>';
            
            echo sprintf(
                'SLGB blocks converted in %d post(s):<br>', 
                $count
            );
            
            echo '<ul style="list-style-type: disc; margin-left: 20px;">';
            
            // Original block types
            if ($heading_count > 0) {
                echo sprintf('<li>%d heading blocks</li>', $heading_count);
            }
            
            if ($emph_count > 0) {
                echo sprintf('<li>%d emphasis blocks</li>', $emph_count);
            }
            
            if ($image_count > 0) {
                echo sprintf('<li>%d image blocks</li>', $image_count);
            }
            
            if ($table_count > 0) {
                echo sprintf('<li>%d table blocks</li>', $table_count);
            }
            
            if ($subscribe_count > 0) {
                echo sprintf('<li>%d subscribe blocks</li>', $subscribe_count);
            }
            
            if ($compare_count > 0) {
                echo sprintf('<li>%d compare blocks</li>', $compare_count);
            }
            
            if ($hints_count > 0) {
                echo sprintf('<li>%d hints blocks</li>', $hints_count);
            }
            
            if ($quote_count > 0) {
                echo sprintf('<li>%d quote blocks</li>', $quote_count);
            }
            
            if ($miniature_count > 0) {
                echo sprintf('<li>%d miniature blocks</li>', $miniature_count);
            }
            
            if ($cta_count > 0) {
                echo sprintf('<li>%d CTA blocks</li>', $cta_count);
            }
            
            if ($gb_emph_count > 0) {
                echo sprintf('<li>%d gb-emph blocks</li>', $gb_emph_count);
            }
            
            // New block types
            if ($postimage_count > 0) {
                echo sprintf('<li>%d postimage blocks</li>', $postimage_count);
            }
            
            if ($dosdont_count > 0) {
                echo sprintf('<li>%d dos-donts blocks</li>', $dosdont_count);
            }
            
            if ($youtube_count > 0) {
                echo sprintf('<li>%d youtube blocks</li>', $youtube_count);
            }
            
            if ($pytube_count > 0) {
                echo sprintf('<li>%d p-youtube blocks</li>', $pytube_count);
            }
            
            if ($pbtns_count > 0) {
                echo sprintf('<li>%d p-btns blocks</li>', $pbtns_count);
            }
            
            if ($post_quote_count > 0) {
                echo sprintf('<li>%d post-quote blocks</li>', $post_quote_count);
            }
            
            if ($p_hints_count > 0) {
                echo sprintf('<li>%d p-hints blocks</li>', $p_hints_count);
            }
            
            if ($p_comparison_count > 0) {
                echo sprintf('<li>%d p-comparison blocks</li>', $p_comparison_count);
            }
            
            echo '</ul>';
            echo '</p></div>';
        });
    }
}
add_action('admin_init', 'oh_convert_slgb_blocks');

/**
 * 2. Admin Interface
 */

/**
 * 2.1. Register Admin Page
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
 * 2.2. Render Admin Page UI
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
                <li><code>slgb/h1-h6</code> → <code>core/heading</code> blocks</li>
                <li><code>slgb/emph</code> → <code>core/paragraph</code> blocks</li>
                <li><code>slgb/image</code> → <code>core/image</code> blocks</li>
                <li><code>slgb/table</code> → <code>core/html</code> blocks (with HTML tables)</li>
                <li><code>slgb/gb-subscribe</code> → <code>core/group</code> with paragraph and button</li>
                <li><code>slgb/p-compare</code> → <code>core/columns</code> blocks</li>
                <li><code>slgb/p-hints</code> → <code>core/html</code> blocks (with HTML tables)</li>
                <li><code>slgb/p-quote</code> → <code>core/quote</code> blocks</li>
                <li><code>slgb/p-miniature</code> → <code>core/media-text</code> or <code>core/group</code> blocks</li>
                <li><code>slgb/gb-cta</code> → <code>core/group</code> with heading and buttons</li>
                <li><code>slgb/gb-emph</code> → <code>core/group</code> with preserved content</li>
                <li><code>slgb/postimage</code> → <code>core/image</code> blocks</li>
                <li><code>slgb/dos-donts</code> → <code>core/html</code> blocks (with DOs and DON'Ts tables)</li>
                <li><code>slgb/youtube</code> and <code>slgb/p-youtube</code> → <code>core/embed</code> blocks</li>
                <li><code>slgb/p-btns</code> → <code>core/buttons</code> blocks</li>
                <li><code>slgb/post-quote</code> → <code>core/quote</code> blocks</li>
                <li><code>slgb/p-hints-row</code> and <code>slgb/p-hints-cell</code> → <code>core/html</code> table rows and cells</li>
                <li><code>slgb/p-comparison</code> and <code>slgb/p-comparison-row</code> → <code>core/html</code> comparison tables</li>
            </ul>
            <p><strong>CSS Classes Preserved:</strong> The plugin maintains all custom CSS classes from your original blocks to ensure your styling continues to work correctly.</p>
            <p><strong>Important:</strong> Always back up your database before running this conversion.</p>
        </div>
        
        <div class="card" style="max-width: 800px; margin-bottom: 20px; padding: 20px; background-color: #f8f9fa;">
            <h3>CSS Styling Help</h3>
            <p>If you need to add CSS styling, the following classes are commonly used in the converted blocks:</p>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <!-- Original styles -->
                <li><code>slgb-table</code> - For converted tables</li>
                <li><code>slgb-subscribe</code> - For subscribe forms</li>
                <li><code>slgb-compare</code> - For comparison blocks</li>
                <li><code>slgb-compare-column</code> - For columns within compare blocks</li>
                <li><code>slgb-hints</code> - For hint tables</li>
                <li><code>slgb-quote</code> - For quotes</li>
                <li><code>slgb-miniature</code> - For miniature post blocks</li>
                <li><code>slgb-cta</code> - For CTA blocks</li>
                <li><code>slgb-gb-emph</code> - For emphasis blocks</li>
                <li><code>slgb-emph</code> - For regular emphasis blocks</li>
                
                <!-- New styles -->
                <li><code>slgb-dosdont</code> - For DOs and DON'Ts tables</li>
                <li><code>slgb-post-quote</code> - For post quotes</li>
                <li><code>slgb-p-comparison</code> - For comparison tables</li>
            </ul>
        </div>
        
        <a href="<?php echo esc_url($url); ?>" id="oh-convert-run" class="button button-primary">Run Conversion</a>
        <p id="oh-progress-msg" style="margin-top: 10px;"></p>
    </div>

    <script>
        document.getElementById('oh-convert-run')?.addEventListener('click', function() {
            const msg = document.getElementById('oh-progress-msg');
            msg.textContent = 'Processing… This may take a while for sites with many posts. Please do not close this window.';
        });
    </script>
    <?php
}

/**
 * 3. Plugin Actions and Styles
 */

/**
 * Safely get WordPress image data by post ID, handling environments where WP functions may not be available
 * 
 * @param int $post_id The WordPress post ID to get image data for
 * @return array Image data with url, width, height keys; empty array if unavailable
 */
function oh_safe_get_wp_image($post_id) {
    $result = array(
        'url' => '',
        'width' => '',
        'height' => ''
    );
    
    // Only proceed if we're in a WordPress environment and required functions exist
    if (defined('ABSPATH') && function_exists('get_post_thumbnail_id') && function_exists('wp_get_attachment_image_src')) {
        try {
            $thumbnail_id = get_post_thumbnail_id($post_id);
            
            if ($thumbnail_id) {
                $image_data = wp_get_attachment_image_src($thumbnail_id, 'full');
                
                if ($image_data && !empty($image_data[0])) {
                    $result['url'] = $image_data[0];
                    
                    if (!empty($image_data[1])) {
                        $result['width'] = $image_data[1];
                    }
                    
                    if (!empty($image_data[2])) {
                        $result['height'] = $image_data[2];
                    }
                }
            }
        } catch (Exception $e) {
            // Silently handle any errors, returning empty array
        }
    }
    
    return $result;
}

/**
 * 3.1. Add Plugin Settings Link
 */
function oh_slgb_plugin_action_links($links) {
    $url = admin_url('tools.php?page=oh-slgb-converter');
    $settings_link = '<a href="' . esc_url($url) . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'oh_slgb_plugin_action_links');

