<?php
namespace DevTools\CmsApi\Plugin;

use DevTools\CmsApi\Model\CmsPage;

class CmsPagePlugin
{
    /**
     * After plugin for getPage
     * @param CmsPage $subject
     * @param array $result
     * @param array $body
     * @return array
     */
    public function afterGetPage(CmsPage $subject, array $result, array $body = [])
    {
        // Add a timestamp to the response
        $result['plugin_timestamp'] = date('Y-m-d H:i:s');

        // Example: modify content if content_type contains 'products'
        if (!empty($body['content_type']) && in_array('products', (array)$body['content_type'])) {
            foreach ($result['content'] as &$node) {
                // You can manipulate nodes here
                // e.g., add a new child for demonstration
                if (isset($node['children'])) {
                    $node['children'][] = [
                        'type' => 'text',
                        'content' => 'Added by plugin'
                    ];
                }
            }
        }

        return $result;
    }
}