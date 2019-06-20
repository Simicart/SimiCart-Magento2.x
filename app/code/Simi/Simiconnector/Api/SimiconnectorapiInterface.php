<?php
namespace Simi\Simiconnector\Api;

interface SimiconnectorapiInterface
{
    /**
     * Get API
     *
     * @param string $resource
     * @param string $resource_id
     * @return array
     * @throws \Simi\Simiconnector\Helper\SimiException
     */
    public function hasId($resource, $resource_id);

    /**
     * Get API
     *
     * @param string $resource
     * @return array
     * @throws \Simi\Simiconnector\Helper\SimiException
     */
    public function noId($resource);

}
