<?php
/**
 * Plugin Name:       OctaHexa SLGB Block Converter
 * Plugin URI:        https://octahexa.com/plugins/octahexa-slgb-block-converter
 * Description:       Converts SLGB custom blocks to core blocks with proper HTML formatting while preserving classes and styling.
 * Version:           2.2.0
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
 *   3.2. Add Custom CSS for Converted Blocks
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
                    
                    // Extract text content
                    $text = isset($attrs['text']) ? json_decode('"' . $attrs['text'] . '"') : '';
                    
                    // Extract any custom classes - preserve exactly as they are
                    $className = isset($attrs['className']) ? $attrs['className'] : '';
                    $customClasses = !empty($className) ? ' class="' . esc_attr($className) . '"' : '';
                    
                    $heading_count++;
                    
                    // Include the class in the converted heading - unchanged
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
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/p-hints (.*?)-->(.*?)<!-- \/wp:slgb\/p-hints -->/s',
                function ($matches) use (&$hints_count) {
                    $attrString = $matches[1];
                    $content = $matches[2];
                    
                    // Extract existing classes - preserve exactly as they are
                    $existingClasses = '';
                    if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                        $existingClasses = $classMatch[1];
                    }
                    
                    // If no class exists, use slgb-hints for backwards compatibility
                    $className = !empty($existingClasses) ? $existingClasses : 'slgb-hints';
                    
                    // We'll need to keep the original HTML since it's already a table structure
                    // Extract the table content
                    if (preg_match('/<table>(.*?)<\/table>/s', $content, $tableMatch)) {
                        $tableContent = $tableMatch[1];
                        
                        $hints_count++;
                        
                        // Create a core/html block with the table
                        return sprintf(
                            '<!-- wp:html -->' . "\n" .
                            '<table class="%s">%s</table>' . "\n" .
                            '<!-- /wp:html -->',
                            esc_attr($className),
                            $tableContent
                        );
                    }
                    
                    // If extraction fails, return the original
                    return $matches[0];
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
                    }
                    
                    // Join classes and ensure no duplicates
                    $className = implode(' ', array_unique(array_filter($classNames)));
                    
                    // Create a core/quote block with the content
                    $output = sprintf(
                        '<!-- wp:quote {"className":"%s"} -->' . "\n" .
                        '<blockquote class="wp-block-quote %s">' . "\n" .
                        '<p>%s</p>' . "\n",
                        esc_attr($className),
                        esc_attr($className),
                        $text // Text is already decoded properly
                    );
                    
                    // Add citation if author exists
                    if (!empty($author)) {
                        $output .= sprintf('<cite>%s</cite>' . "\n", $author);
                    }
                    
                    $output .= '</blockquote>' . "\n" . '<!-- /wp:quote -->';
                    
                    return $output;
                },
                $updated
            );

        /**
         * 1.1.9. Miniature Block Conversion
         */
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/p-miniature (.*?) -->(.*?)<!-- \/wp:slgb\/p-miniature -->/s',
                function ($matches) use (&$miniature_count) {
                    $attrString = $matches[1];
                    $content = $matches[2];
                    
                    // Extract the post info
                    $postInfo = null;
                    $text = '';
                    $image_src = '';
                    $post_title = '';
                    $post_link = '';
                    
                    if (preg_match('/"text":"(.*?)"/', $attrString, $textMatch)) {
                        $text = json_decode('"' . str_replace('"', '\\"', $textMatch[1]) . '"');
                    }
                    
                    // Extract existing classes - preserve exactly as they are
                    $existingClasses = '';
                    if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                        $existingClasses = $classMatch[1];
                    }
                    
                    // If no class exists, use slgb-miniature for backwards compatibility
                    $className = !empty($existingClasses) ? $existingClasses : 'slgb-miniature';
                    
                    if (preg_match('/"postInfo":(.*?)(,"showText"|})/', $attrString, $postInfoMatch)) {
                        try {
                            $postInfoJson = $postInfoMatch[1];
                            // Fix JSON if needed
                            $postInfoJson = preg_replace('/([{,])(\s*)([a-zA-Z0-9_]+)(\s*):/','$1"$3":', $postInfoJson);
                            $postInfo = json_decode($postInfoJson, true);
                            
                            if (is_array($postInfo)) {
                                if (isset($postInfo['title'])) {
                                    $post_title = $postInfo['title'];
                                }
                                
                                if (isset($postInfo['link'])) {
                                    $post_link = $postInfo['link'];
                                }
                                
                                if (isset($postInfo['img']) && isset($postInfo['img']['src'])) {
                                    $image_src = $postInfo['img']['src'];
                                }
                            }
                        } catch (Exception $e) {
                            // If JSON parsing fails, we'll try to extract from the HTML instead
                        }
                    }
                    
                    // If we couldn't extract from JSON, try to extract from the HTML
                    if (empty($image_src) && preg_match('/<img src="([^"]*)"/', $content, $imgMatch)) {
                        $image_src = $imgMatch[1];
                    }
                    
                    if (empty($post_title) && preg_match('/<strong>(.*?)<\/strong>/', $content, $titleMatch)) {
                        $post_title = $titleMatch[1];
                    }
                    
                    if (empty($post_link) && preg_match('/<a href="([^"]*)"[^>]*><strong>/', $content, $linkMatch)) {
                        $post_link = $linkMatch[1];
                    }
                    
                    $miniature_count++;
                    
                    // Create a media-text block if we have an image, otherwise just use a group
                    if (!empty($image_src)) {
                        return sprintf(
                            '<!-- wp:media-text {"mediaLink":"%s","mediaType":"image","className":"%s"} -->' . "\n" .
                            '<div class="wp-block-media-text alignwide is-stacked-on-mobile %s">' . 
                            '<figure class="wp-block-media-text__media">' .
                            '<img src="%s" alt="%s"/>' .
                            '</figure>' .
                            '<div class="wp-block-media-text__content">' . 
                            '<!-- wp:heading {"level":3} -->' . "\n" .
                            '<h3><a href="%s">%s</a></h3>' . "\n" .
                            '<!-- /wp:heading -->' . "\n\n" .
                            '<!-- wp:paragraph -->' . "\n" .
                            '<p>%s</p>' . "\n" .
                            '<!-- /wp:paragraph -->' . "\n" .
                            '</div></div>' . "\n" .
                            '<!-- /wp:media-text -->',
                            esc_url($post_link),
                            esc_attr($className),
                            esc_attr($className),
                            esc_url($image_src),
                            esc_attr($post_title),
                            esc_url($post_link),
                            esc_html($post_title),
                            esc_html($text)
                        );
                    } else {
                        return sprintf(
                            '<!-- wp:group {"className":"%s"} -->' . "\n" .
                            '<div class="wp-block-group %s">' . "\n" .
                            '<!-- wp:heading {"level":3} -->' . "\n" .
                            '<h3><a href="%s">%s</a></h3>' . "\n" .
                            '<!-- /wp:heading -->' . "\n\n" .
                            '<!-- wp:paragraph -->' . "\n" .
                            '<p>%s</p>' . "\n" .
                            '<!-- /wp:paragraph -->' . "\n" .
                            '</div>' . "\n" .
                            '<!-- /wp:group -->',
                            esc_attr($className),
                            esc_attr($className),
                            esc_url($post_link),
                            esc_html($post_title),
                            esc_html($text)
                        );
                    }
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
                    if (preg_match('/"title":"(.*?)"/', $attrString, $titleMatch)) {
                        $title = json_decode('"' . $titleMatch[1] . '"');
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
                            '<h2 class="has-text-align-center">%s</h2>' . "\n" .
                            '<!-- /wp:heading -->' . "\n\n",
                            esc_html($title)
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
                    
                    // Extract the content from inside the div
                    if (preg_match('/<div class="gb-emph">(.*?)<\/div>/s', $content, $divMatch)) {
                        $divContent = $divMatch[1];
                        $gb_emph_count++;
                        
                        // Create a group with the content and a custom class
                        return sprintf(
                            '<!-- wp:group {"className":"%s"} -->' . "\n" .
                            '<div class="wp-block-group %s">' . "\n" .
                            '%s' . "\n" .
                            '</div>' . "\n" .
                            '<!-- /wp:group -->',
                            esc_attr($className),
                            esc_attr($className),
                            $divContent
                        );
                    }
                    
                    // If extraction fails, return the original
                    return $matches[0];
                },
                $updated
            );

        /**
         * 1.2. New Block Type Handlers
         */

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
                            $processedJson = str_replace(['u0022', 'u003c', 'u003e', 'u003d'], ['"', '<', '>', '='], $cellsMatch[1]);
                            
                            // Double decode to handle escaped JSON
                            $processedJson = json_decode('"' . $processedJson . '"');
                            $cells = json_decode($processedJson, true);
                            
                            if (is_array($cells)) {
                                // Build an HTML table
                                $tableHtml = '<table class="' . esc_attr($className) . '"><thead><tr>' . 
                                             '<th>DOs</th><th>DON\'Ts</th>' . 
                                             '</tr></thead><tbody>';
                                
                                foreach ($cells as $row) {
                                    $do = isset($row['do']) ? $row['do'] : '';
                                    $dont = isset($row['dont']) ? $row['dont'] : '';
                                    
                                    $tableHtml .= sprintf(
                                        '<tr><td>%s</td><td>%s</td></tr>',
                                        $do,
                                        $dont
                                    );
                                }
                                
                                $tableHtml .= '</tbody></table>';
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
                        
                        // Convert to core/embed YouTube block
                        return sprintf(
                            '<!-- wp:embed {"url":"%s","type":"rich","providerNameSlug":"youtube","responsive":true,"className":"wp-embed-aspect-16-9 wp-has-aspect-ratio"} -->' . 
                            '<figure class="wp-block-embed is-type-rich is-provider-youtube wp-block-embed-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio">' . 
                            '<div class="wp-block-embed__wrapper">%s</div></figure>' . 
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
                    preg_match('/"title":"([^"]*)"/', $attrString, $titleMatch);
                    preg_match('/"src":"([^"]*)"/', $attrString, $srcMatch);
                    
                    // Alternatively try to extract from the iframe if available
                    if (empty($srcMatch) && preg_match('/src="([^"]*)"/', $content, $iframeSrcMatch)) {
                        $src = $iframeSrcMatch[1];
                    } else if (!empty($srcMatch)) {
                        $src = $srcMatch[1];
                    } else {
                        // If we can't extract the source, return the original
                        return $matches[0];
                    }
                    
                    $title = !empty($titleMatch) ? json_decode('"' . $titleMatch[1] . '"') : '';
                    
                    $pytube_count++;
                    
                    // Convert to core/embed YouTube block with figure caption if title available
                    $embed = sprintf(
                        '<!-- wp:embed {"url":"%s","type":"rich","providerNameSlug":"youtube","responsive":true,"className":"wp-embed-aspect-16-9 wp-has-aspect-ratio"} -->' . 
                        '<figure class="wp-block-embed is-type-rich is-provider-youtube wp-block-embed-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio">' . 
                        '<div class="wp-block-embed__wrapper">%s</div>',
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
                        '<!-- wp:quote {"className":"%s"} -->' . "\n" .
                        '<blockquote class="wp-block-quote %s">' . "\n" .
                        '<p>%s</p>' . "\n",
                        esc_attr($classAttr),
                        esc_attr($classAttr),
                        $quote_text
                    );
                    
                    // Add citation if author exists
                    if (!empty($author)) {
                        $output .= sprintf('<cite>%s</cite>' . "\n", esc_html($author));
                    }
                    
                    $output .= '</blockquote>' . "\n" . '<!-- /wp:quote -->';
                    
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
                            $rowHtml .= sprintf('<td>%s</td>', trim($cellContent));
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
                    
                    // Extract column titles
                    $leftColumnTitle = '';
                    $rightColumnTitle = '';
                    
                    if (preg_match('/"leftColumnTitle":"(.*?)"/', $attrString, $leftTitleMatch)) {
                        $leftTitle = $leftTitleMatch[1];
                        // Handle HTML entity decoding
                        $leftColumnTitle = str_replace(['u003c', 'u003e', 'u0026', 'u0022'], ['<', '>', '&', '"'], $leftTitle);
                        $leftColumnTitle = html_entity_decode(json_decode('"' . str_replace('"', '\\"', $leftColumnTitle) . '"'));
                    }
                    
                    if (preg_match('/"rightColumnTitle":"(.*?)"/', $attrString, $rightTitleMatch)) {
                        $rightTitle = $rightTitleMatch[1];
                        // Handle HTML entity decoding
                        $rightColumnTitle = str_replace(['u003c', 'u003e', 'u0026', 'u0022'], ['<', '>', '&', '"'], $rightTitle);
                        $rightColumnTitle = html_entity_decode(json_decode('"' . str_replace('"', '\\"', $rightColumnTitle) . '"'));
                    }
                    
                    // Extract existing classes
                    $className = 'slgb-p-comparison';
                    if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                        $className = $classMatch[1];
                    }
                    
                    // Process existing table if it exists in the content
                    $tableContent = '';
                    if (preg_match('/<table.*?>(.*?)<\/table>/s', $content, $tableMatch)) {
                        $tableContent = $tableMatch[1];
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
                                
                                // Extract row title, leftColumn, and rightColumn attributes
                                $rowTitle = '';
                                $leftColumn = '';
                                $rightColumn = '';
                                
                                if (preg_match('/"title":"(.*?)"/', $rowAttrString, $rowTitleMatch)) {
                                    $rowTitle = json_decode('"' . $rowTitleMatch[1] . '"');
                                }
                                
                                if (preg_match('/"leftColumn":"(.*?)"/', $rowAttrString, $leftColMatch)) {
                                    $leftCol = $leftColMatch[1];
                                    // Handle HTML entity decoding
                                    $leftColumn = str_replace(['u003c', 'u003e', 'u0026', 'u0022'], ['<', '>', '&', '"'], $leftCol);
                                    $leftColumn = html_entity_decode(json_decode('"' . str_replace('"', '\\"', $leftColumn) . '"'));
                                }
                                
                                if (preg_match('/"rightColumn":"(.*?)"/', $rowAttrString, $rightColMatch)) {
                                    $rightCol = $rightColMatch[1];
                                    // Handle HTML entity decoding
                                    $rightColumn = str_replace(['u003c', 'u003e', 'u0026', 'u0022'], ['<', '>', '&', '"'], $rightCol);
                                    $rightColumn = html_entity_decode(json_decode('"' . str_replace('"', '\\"', $rightColumn) . '"'));
                                }
                                
                                // Add row to table
                                $tableContent .= '<tr>';
                                $tableContent .= '<td>' . $rowTitle . '</td>';
                                $tableContent .= '<td>' . $leftColumn . '</td>';
                                $tableContent .= '<td>' . $rightColumn . '</td>';
                                $tableContent .= '</tr>';
                            }
                        }
                        
                        $tableContent .= '</tbody>';
                    }
                    
                    $p_comparison_count++;
                    
                    // Create HTML table with appropriate class
                    $tableHtml = '<table class="' . esc_attr($className) . '">' . $tableContent . '</table>';
                    
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
 * 3.1. Add Plugin Settings Link
 */
function oh_slgb_plugin_action_links($links) {
    $url = admin_url('tools.php?page=oh-slgb-converter');
    $settings_link = '<a href="' . esc_url($url) . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'oh_slgb_plugin_action_links');

/**
 * 3.2. Add Custom CSS for Converted Blocks
 */
function oh_add_conversion_css() {
    ?>
    <style>
        /* Basic styling for converted blocks */
        /* Original styling */
        .slgb-table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 1.5em;
        }
        
        .slgb-table th, 
        .slgb-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .slgb-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .slgb-subscribe {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            margin: 1.5em 0;
        }
        
        .slgb-compare {
            margin: 1.5em 0;
        }
        
        .slgb-compare-column {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }
        
        .slgb-hints {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 1.5em;
        }
        
        .slgb-hints th {
            background-color: #f8f9fa;
            font-weight: bold;
            border: 1px solid #ddd;
            padding: 10px;
        }
        
        .slgb-hints td {
            border: 1px solid #ddd;
            padding: 10px;
            vertical-align: top;
        }
        
        .slgb-quote {
            font-style: italic;
            border-left: 4px solid #888;
            padding-left: 1em;
        }
        
        .slgb-cta {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            margin: 1.5em 0;
        }
        
        .slgb-gb-emph,
        .slgb-emph {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #0073aa;
            margin: 1.5em 0;
        }
        
        .slgb-miniature {
            margin: 1.5em 0;
        }
        
        /* New block styles */
        .slgb-dosdont {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 1.5em;
        }
        
        .slgb-dosdont th:first-child {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .slgb-dosdont th:last-child {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .slgb-dosdont th, 
        .slgb-dosdont td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            vertical-align: top;
        }
        
        .slgb-dosdont td:first-child {
            border-left: 3px solid #2e7d32;
            background-color: #f1f8f1;
        }
        
        .slgb-dosdont td:last-child {
            border-left: 3px solid #c62828;
            background-color: #fdf5f5;
        }
        
        .slgb-post-quote {
            font-style: italic;
            border-left: 4px solid #888;
            padding-left: 1em;
            margin: 1.5em 0;
        }
        
        .slgb-post-quote cite {
            display: block;
            font-style: normal;
            font-weight: bold;
            margin-top: 0.5em;
            text-align: right;
        }
        
        .slgb-p-comparison {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 1.5em;
        }
        
        .slgb-p-comparison th, 
        .slgb-p-comparison td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        
        .slgb-p-comparison th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .slgb-p-comparison tr td:first-child {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        
        /* Ensure YouTube embeds are responsive */
        .wp-block-embed.is-type-video iframe {
            max-width: 100%;
            width: 100%;
        }
        
        .wp-embed-aspect-16-9 .wp-block-embed__wrapper {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
        }
        
        .wp-embed-aspect-16-9 .wp-block-embed__wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
    </style>
    <?php
}
add_action('wp_head', 'oh_add_conversion_css');
