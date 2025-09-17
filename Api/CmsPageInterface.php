<?php
namespace DevTools\CmsApi\Api;

interface CmsPageInterface
{
    /**
     * Get CMS page or block content
     *
     * @param mixed $body
     * @return array
     */
    public function getPage($body): array;
}