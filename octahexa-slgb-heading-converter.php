<?php
/**
 * Plugin Name:       OctaHexa SLGB Block Converter
 * Plugin URI:        https://octahexa.com/plugins/octahexa-slgb-block-converter
 * Description:       Converts SLGB custom blocks to core blocks with proper HTML formatting while preserving classes and styling.
 * Version:           2.0.2
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
        $table_count = 0;
        $subscribe_count = 0;
        $compare_count = 0;
        $hints_count = 0;
        $quote_count = 0;
        $miniature_count = 0;
        $cta_count = 0;
        $gb_emph_count = 0;

        foreach ($posts as $post) {
            $original = $post->post_content;
            $updated = $original;
            
            // Convert heading blocks
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
                    
                    // Include the class in the converted heading - we don't need to add any marker class here
                    // since headings naturally convert to their core equivalents without needing special styling
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
            
            // Convert emphasis blocks - preserve original classes
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
                        
                        // Preserve original classes and add our marker class
                        $className = !empty($className) ? 
                            $className . ' slgb-emph' : 
                            'slgb-emph';
                            
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

        // Convert image blocks
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

        // Convert table blocks - preserve original classes
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/table (.*?) \/-->/',
                function ($matches) use (&$table_count) {
                    $attrString = $matches[1];
                    
                    // Extract any existing classes
                    $existingClasses = '';
                    if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                        $existingClasses = $classMatch[1];
                    }
                    
                    // Create class string that preserves existing classes
                    $tableClass = !empty($existingClasses) ? 
                        $existingClasses . ' slgb-table-converted' : 
                        'slgb-table-converted';
                    
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

        // Convert gb-subscribe blocks to paragraphs with a form button - preserve original classes
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/gb-subscribe (.*?) \/-->/',
                function ($matches) use (&$subscribe_count) {
                    $attrString = $matches[1];
                    
                    // Extract existing classes
                    $existingClasses = '';
                    if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                        $existingClasses = $classMatch[1];
                    }
                    
                    // Combine existing classes with our marker class
                    $className = !empty($existingClasses) ? 
                        $existingClasses . ' slgb-subscribe-converted' : 
                        'slgb-subscribe-converted';
                    
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
            
            // Convert Compare blocks - preserve original classes
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/p-compare (.*?)-->(.*?)<!-- \/wp:slgb\/p-compare -->/s',
                function ($matches) use (&$compare_count) {
                    $attrString = $matches[1];
                    $content = $matches[2];
                    
                    // Extract existing parent block classes
                    $existingParentClasses = '';
                    if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                        $existingParentClasses = $classMatch[1];
                    }
                    
                    // Combine existing classes with our marker class
                    $parentClassName = !empty($existingParentClasses) ? 
                        $existingParentClasses . ' slgb-compare-converted' : 
                        'slgb-compare-converted';
                    
                    // Extract and convert the individual columns
                    $content = preg_replace_callback(
                        '/<\!-- wp:slgb\/p-compare-column (.*?) -->(.*?)<!-- \/wp:slgb\/p-compare-column -->/s',
                        function ($columnMatches) {
                            $columnAttrs = $columnMatches[1];
                            $columnContent = $columnMatches[2];
                            
                            // Extract existing column classes
                            $existingColumnClasses = '';
                            if (preg_match('/"className":"([^"]*)"/', $columnAttrs, $classMatch)) {
                                $existingColumnClasses = $classMatch[1];
                            }
                            
                            // Combine existing classes with our marker class
                            $columnClassName = !empty($existingColumnClasses) ? 
                                $existingColumnClasses . ' slgb-compare-column' : 
                                'slgb-compare-column';
                            
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

        // Convert Quote blocks - preserve original classes and fix HTML rendering
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/p-quote (.*?) -->(.*?)<!-- \/wp:slgb\/p-quote -->/s',
                function ($matches) use (&$quote_count) {
                    $attrString = $matches[1];
                    $content = $matches[2];
                    
                    // Extract the text
                    $text = '';
                    if (preg_match('/"text":"(.*?)"(?:,|})/', $attrString, $textMatch)) {
                        // First, replace Unicode escape sequences with proper characters
                        $escapedText = $textMatch[1];
                        $escapedText = str_replace(['u003c', 'u003e', 'u0026'], ['<', '>', '&'], $escapedText);
                        
                        // Then decode JSON properly
                        $text = json_decode('"' . str_replace('"', '\\"', $escapedText) . '"');
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
                    
                    // Extract any existing classes
                    $existingClasses = [];
                    if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                        $existingClasses = explode(' ', $classMatch[1]);
                    }
                    
                    $quote_count++;
                    
                    // Create class list preserving existing classes
                    $classNames = $existingClasses;
                    // Add our marker class
                    $classNames[] = 'slgb-quote-converted';
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
                        esc_attr($className)),
                        esc_attr($className

                                 // Add citation if author exists
                    if (!empty($author)) {
                        $output .= sprintf('<cite>%s</cite>' . "\n", $author);
                    }
                    
                    $output .= '</blockquote>' . "\n" . '<!-- /wp:quote -->';
                    
                    return $output;
                },
                $updated
            );
            
            // Convert p-hints blocks to tables - preserve original classes
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/p-hints (.*?)-->(.*?)<!-- \/wp:slgb\/p-hints -->/s',
                function ($matches) use (&$hints_count) {
                    $attrString = $matches[1];
                    $content = $matches[2];
                    
                    // Extract existing classes
                    $existingClasses = '';
                    if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                        $existingClasses = $classMatch[1];
                    }
                    
                    // Combine existing classes with our marker class
                    $className = !empty($existingClasses) ? 
                        $existingClasses . ' slgb-hints-converted' : 
                        'slgb-hints-converted';
                    
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

        // Convert CTA blocks - preserve original classes
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/gb-cta (.*?) \/-->/',
                function ($matches) use (&$cta_count) {
                    $attrString = $matches[1];
                    
                    // Extract existing classes
                    $existingClasses = '';
                    if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                        $existingClasses = $classMatch[1];
                    }
                    
                    // Combine existing classes with our marker class
                    $className = !empty($existingClasses) ? 
                        $existingClasses . ' slgb-cta-converted' : 
                        'slgb-cta-converted';
                    
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

        // Convert Miniature blocks - preserve original classes
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
                    
                    // Extract existing classes
                    $existingClasses = '';
                    if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                        $existingClasses = $classMatch[1];
                    }
                    
                    // Combine existing classes with our marker class
                    $className = !empty($existingClasses) ? 
                        $existingClasses . ' slgb-miniature-converted' : 
                        'slgb-miniature-converted';
                    
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

        // Convert gb-emph blocks - preserve original classes
            $updated = preg_replace_callback(
                '/<\!-- wp:slgb\/gb-emph (.*?)-->(.*?)<!-- \/wp:slgb\/gb-emph -->/s',
                function ($matches) use (&$gb_emph_count) {
                    $attrString = $matches[1];
                    $content = $matches[2];
                    
                    // Extract existing classes
                    $existingClasses = '';
                    if (preg_match('/"className":"([^"]*)"/', $attrString, $classMatch)) {
                        $existingClasses = $classMatch[1];
                    }
                    
                    // Combine existing classes with our marker class
                    $className = !empty($existingClasses) ? 
                        $existingClasses . ' slgb-gb-emph-converted' : 
                        'slgb-gb-emph-converted';
                    
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

            if ($original !== $updated) {
                wp_update_post([
                    'ID' => $post->ID,
                    'post_content' => $updated,
                ]);
                $count++;
            }
        }

        add_action('admin_notices', function () use (
            $count, $heading_count, $emph_count, $image_count, 
            $table_count, $subscribe_count, $compare_count, 
            $hints_count, $quote_count, $miniature_count, 
            $cta_count, $gb_emph_count
        ) {
            echo '<div class="notice notice-success"><p>';
            
            echo sprintf(
                'SLGB blocks converted in %d post(s):<br>', 
                $count
            );
            
            echo '<ul style="list-style-type: disc; margin-left: 20px;">';
            
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
            
            echo '</ul>';
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
            </ul>
            <p><strong>CSS Classes Preserved:</strong> The plugin maintains all custom CSS classes from your original blocks and adds conversion-specific classes to help with styling.</p>
            <p><strong>Important:</strong> Always back up your database before running this conversion.</p>
        </div>
        
        <div class="card" style="max-width: 800px; margin-bottom: 20px; padding: 20px; background-color: #f8f9fa;">
            <h3>CSS Styling Help</h3>
            <p>After conversion, you may need to add custom CSS to your theme to maintain the original appearance. The following classes are added to converted blocks:</p>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li><code>slgb-table-converted</code> - For converted tables</li>
                <li><code>slgb-subscribe-converted</code> - For subscribe forms</li>
                <li><code>slgb-compare-converted</code> - For comparison blocks</li>
                <li><code>slgb-compare-column</code> - For columns within compare blocks</li>
                <li><code>slgb-hints-converted</code> - For hint tables</li>
                <li><code>slgb-quote-converted</code> - For quotes</li>
                <li><code>slgb-miniature-converted</code> - For miniature post blocks</li>
                <li><code>slgb-cta-converted</code> - For CTA blocks</li>
                <li><code>slgb-gb-emph-converted</code> - For emphasis blocks</li>
                <li><code>slgb-emph</code> - For regular emphasis blocks</li>
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
 * Add "Settings" link in Plugins list
 */
function oh_slgb_plugin_action_links($links) {
    $url = admin_url('tools.php?page=oh-slgb-converter');
    $settings_link = '<a href="' . esc_url($url) . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'oh_slgb_plugin_action_links');

/**
 * Add custom CSS for converted blocks
 */
function oh_add_conversion_css() {
    ?>
    <style>
        /* Basic styling for converted blocks */
        .slgb-table-converted {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 1.5em;
        }
        
        .slgb-table-converted th, 
        .slgb-table-converted td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .slgb-table-converted th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .slgb-subscribe-converted {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            margin: 1.5em 0;
        }
        
        .slgb-compare-converted {
            margin: 1.5em 0;
        }
        
        .slgb-compare-column {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }
        
        .slgb-hints-converted {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 1.5em;
        }
        
        .slgb-hints-converted th {
            background-color: #f8f9fa;
            font-weight: bold;
            border: 1px solid #ddd;
            padding: 10px;
        }
        
        .slgb-hints-converted td {
            border: 1px solid #ddd;
            padding: 10px;
            vertical-align: top;
        }
        
        .slgb-quote-converted {
            font-style: italic;
            border-left: 4px solid #888;
            padding-left: 1em;
        }
        
        .slgb-cta-converted {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            margin: 1.5em 0;
        }
        
        .slgb-gb-emph-converted,
        .slgb-emph {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #0073aa;
            margin: 1.5em 0;
        }
        
        .slgb-miniature-converted {
            margin: 1.5em 0;
        }
    </style>
    <?php
}
add_action('wp_head', 'oh_add_conversion_css');
