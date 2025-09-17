<?php

namespace DevTools\CmsApi\Model;

use DevTools\CmsApi\Api\CmsPageInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\HTTP\Client\Curl;

class CmsPage implements CmsPageInterface
{
    protected $cache;
    protected $curl;

    const CACHE_TAG = 'CMS_API';
    const GRAPHQL_ENDPOINT = 'https://172.20.0.9:8443/graphql';

    public function __construct(
        CacheInterface $cache,
        Curl           $curl
    )
    {
        $this->cache = $cache;
        $this->curl = $curl;
    }

    public function getPage($body = []): array
    {
        $body = is_array($body) ? $body : [];

        $identifier = $body['identifier'] ?? null;
        $type = $body['type'] ?? null;
        $format = $body['format'] ?? 'json';
        $contentType = $body['content_type'] ?? null;

        if (!$identifier) {
            return ['error' => true, 'message' => 'Identifier is required'];
        }
        if (!$type || !in_array($type, ['page', 'block'])) {
            return ['error' => true, 'message' => 'Type must be either page or block'];
        }

        // Cache
        $skipCache = $_GET['no_cache'] ?? false;
        $cacheKey = 'cmsapi_' . md5($identifier . $type . $format . json_encode($contentType));
        $cached = $this->cache->load($cacheKey);
        if (!$skipCache && $cached) {
            return json_decode($cached, true);
        }

        // GraphQL query
        if ($type === 'page') {
            $query = <<<GQL
            {
                cmsPage(identifier: "$identifier") {
                    title
                    content
                }
            }
            GQL;
        } else {
            $query = <<<GQL
            {
                cmsBlocks(identifiers: "$identifier") {
                    items {
                        title
                        content
                    }
                }
            }
            GQL;
        }

        // Execute GraphQL query
        try {
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->addHeader("Host", "magento.test");
            $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);
            $this->curl->post(self::GRAPHQL_ENDPOINT, json_encode(['query' => $query]));
            $responseBody = $this->curl->getBody();
            $response = json_decode($responseBody, true);
        } catch (\Exception $e) {
            return ['error' => true, 'message' => 'Failed to fetch GraphQL response: ' . $e->getMessage()];
        }

        if (!empty($response['errors'])) {
            return ['error' => true, 'message' => $response['errors'][0]['message']];
        }

        $html = '';
        if ($type === 'page' && isset($response['data']['cmsPage']['content'])) {
            $html = $response['data']['cmsPage']['content'];
        }
        if ($type === 'block' && !empty($response['data']['cmsBlocks']['items'])) {
            foreach ($response['data']['cmsBlocks']['items'] as $block) {
                $html .= $block['content'] ?? '';
            }
        }

        // Return HTML if requested
        if ($format === 'html') {
            $result = [
                'identifier' => $identifier,
                'type' => $type,
                'format' => $format,
                'content' => $html
            ];
            $this->cache->save(json_encode($result), $cacheKey, [self::CACHE_TAG], 3600);
            return $result;
        }

        // Parse HTML → PageBuilder JSON
        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_convert_encoding("<html><body>$html</body></html>", 'HTML-ENTITIES', 'UTF-8'));
        $bodyNode = $doc->getElementsByTagName('body')->item(0);

        $content = $bodyNode ? $this->domNodeToPageBuilderArray($bodyNode) : [];

        // Flatten top-level wrapper
        if (isset($content['children'])) {
            $content = $content['children'];
        }

        //Apply content-type filter only here, before returning
        if ($contentType) {
            $content = $this->filterByContentType($content, $contentType);
        }

        $result = [
            'identifier' => $identifier,
            'type' => $type,
            'format' => $format,
            'content' => $content
        ];

        $this->cache->save(json_encode($result), $cacheKey, [self::CACHE_TAG], 3600);

        return $result;
    }

    /**
     * Convert DOM nodes to PageBuilder-structured array
     */
    protected function domNodeToPageBuilderArray($node)
    {
        if (!($node instanceof \DOMNode)) return null;

        if ($node->nodeType === XML_TEXT_NODE) {
            $text = trim($node->nodeValue);
            return $text === '' ? null : ['type' => 'text', 'content' => $text];
        }

        $output = ['type' => strtolower($node->nodeName)];

        // Extract attributes
        if ($node->hasAttributes()) {
            foreach ($node->attributes as $attr) {
                $name = strtolower($attr->nodeName);
                $value = html_entity_decode($attr->nodeValue, ENT_QUOTES);

                switch ($name) {
                    case 'class':
                        $output['classes'] = explode(' ', $value);
                        break;
                    case 'data-content-type':
                        $output['content-type'] = $value;
                        break;
                    case 'data-slide-name':
                        $output['slide-name'] = $output['name'] = $value;
                        break;
                    case 'data-appearance':
                        $output['appearance'] = $value;
                        break;
                    case 'data-element':
                        $output['element'] = $value;
                        break;
                    case 'data-pb-style':
                        $output['pb-style'] = $value;
                        break;
                    case 'data-autoplay':
                        $output['autoplay'] = $value === 'true';
                        break;
                    case 'data-autoplay-speed':
                        $output['autoplaySpeed'] = (int)$value;
                        break;
                    case 'data-fade':
                        $output['fade'] = $value;
                        break;
                    case 'data-infinite-loop':
                        $output['infiniteLoop'] = $value === 'true';
                        break;
                    case 'data-show-arrows':
                        $output['showArrows'] = $value === 'true';
                        break;
                    case 'data-show-dots':
                        $output['showDots'] = $value === 'true';
                        break;
                    case 'data-enable-parallax':
                        $output['enable-parallax'] = $value;
                        break;
                    case 'data-parallax-speed':
                        $output['parallax-speed'] = $value;
                        break;
                    case 'data-background-images':
                        $decoded = json_decode(stripslashes($value), true);
                        $output['backgroundImages'] = $decoded ?: [];
                        break;
                    case 'data-background-type':
                        $output['background-type'] = $value;
                        break;
                    case 'data-video-loop':
                        $output['video-loop'] = $value;
                        break;
                    case 'data-video-play-only-visible':
                        $output['video-play-only-visible'] = $value;
                        break;
                    case 'data-video-lazy-load':
                        $output['video-lazy-load'] = $value;
                        break;
                    case 'data-video-fallback-src':
                        $output['video-fallback-src'] = $value;
                        break;
                    default:
                        $output[$name] = $value;
                }
            }
        }

        // Recursively process children
        $children = [];
        foreach ($node->childNodes as $child) {
            $childArray = $this->domNodeToPageBuilderArray($child);
            if ($childArray === null) continue;

            $type = $childArray['type'] ?? '';
            $contentType = $childArray['content-type'] ?? '';

            $isEmptyWrapperDiv = $type === 'div' && empty($contentType) && empty($childArray['classes']);
            $isEmptyWrapperP = $type === 'p' && empty($contentType) && empty($childArray['classes']);

            // Keep meaningful nodes intact
            $skipFlatten = in_array($contentType, ['slider', 'slide', 'product', 'row', 'products']);

            if (($isEmptyWrapperDiv || $isEmptyWrapperP) && !$skipFlatten && !empty($childArray['children'])) {
                // Flatten empty wrapper
                foreach ($childArray['children'] as $grandChild) {
                    $children[] = $grandChild;
                }
            } else {
                $children[] = $childArray;
            }
        }

        if (!empty($children)) {
            $output['children'] = $children;
        }

        return $output;
    }

    /**
     * Recursively filter nodes by content-type
     */
    protected function filterByContentType(array $nodes, $contentTypes): array
    {
        if (!$contentTypes) return $nodes;
        if (is_string($contentTypes)) $contentTypes = [$contentTypes];

        $filtered = [];
        foreach ($nodes as $node) {
            $matches = isset($node['content-type']) && in_array($node['content-type'], $contentTypes);

            // If parent matches content-type, keep it even if children are empty
            if ($matches) {
                $filtered[] = $node;
                continue;
            }

            // Otherwise, filter children recursively
            if (!empty($node['children'])) {
                $node['children'] = $this->filterByContentType($node['children'], $contentTypes);
                if (!empty($node['children'])) {
                    $filtered[] = $node; // keep parent if any child matches
                }
            }

        }

        return $filtered;
    }
}