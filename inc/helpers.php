<?php
function trim_html_words($html, $limit = 50, $ellipsis = '...') {
    // Handle empty or null input
    if (empty($html)) {
        return '';
    }
    
    // Load HTML
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    
    // Wrap content to ensure proper structure
    $dom->loadHTML('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>');
    libxml_clear_errors();

    $body = $dom->getElementsByTagName('body')->item(0);
    
    // Additional safety check
    if (!$body || !$body->childNodes) {
        return '';
    }
    
    $word_count = 0;
    $fragments = [];

    foreach ($body->childNodes as $node) {
        $fragment = trim_html_node($node, $limit, $word_count);
        if ($fragment !== '') {
            $fragments[] = $fragment;
        }
        if ($word_count >= $limit) break;
    }

    // Inject the ellipsis before the last closing tag
    $output = implode('', $fragments);
    $output = insert_ellipsis_before_closing_tag($output, $ellipsis);

    return $output;
}

function trim_html_node($node, $limit, &$word_count) {
    if ($word_count >= $limit) return '';

    if ($node->nodeType === XML_TEXT_NODE) {
        $words = preg_split('/\s+/', trim($node->textContent));
        $words = array_filter($words);
        $remaining = $limit - $word_count;
        $slice = array_slice($words, 0, $remaining);
        $word_count += count($slice);
        return implode(' ', $slice) . (count($slice) < count($words) ? ' ' : '');
    }

    if ($node->nodeType === XML_ELEMENT_NODE) {
        $html = '<' . $node->nodeName;

        // Add attributes
        if ($node->hasAttributes()) {
            foreach ($node->attributes as $attr) {
                $html .= ' ' . $attr->nodeName . '="' . htmlspecialchars($attr->nodeValue, ENT_QUOTES) . '"';
            }
        }

        $html .= '>';

        // Check if node has childNodes before iterating
        if ($node->childNodes) {
            foreach ($node->childNodes as $child) {
                $html .= trim_html_node($child, $limit, $word_count);
                if ($word_count >= $limit) break;
            }
        }

        $html .= '</' . $node->nodeName . '>';
        return $html;
    }

    return '';
}

function insert_ellipsis_before_closing_tag($html, $ellipsis = '...') {
    // Find the last closing tag
    if (preg_match('/(<\/\w+>\s*)$/', $html, $matches)) {
        return preg_replace('/(<\/\w+>\s*)$/', $ellipsis . '$1', $html);
    }
    return $html . $ellipsis;
}
