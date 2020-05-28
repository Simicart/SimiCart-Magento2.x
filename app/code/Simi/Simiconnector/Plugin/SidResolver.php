<?php
namespace Simi\Simiconnector\Plugin;

class SidResolver
{
    private $simiObjectManager;
    private $request;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->simiObjectManager = $simiObjectManager;
        $this->request = $request;
    }
    //add session id to continue session with graphql
    public function afterGetSid($sidResolver, $result)
    {
        $contents            = $this->request->getContent();
        $contents_array      = [];
        if ($simiSessId = $this->request->getParam('simiSessId')) {
            if ($simiSessId != '') {
                return $simiSessId;
            }
        }
        if ($contents && ($contents != '')) {
            $contents_parser = urldecode($contents);
            $contents_array = json_decode($contents_parser, true);
        }
        if ($contents_array && isset($contents_array['variables']['simiSessId'])) {
            $simiSessId = $contents_array['variables']['simiSessId'];
            if ($simiSessId && $simiSessId != '') {
                return $simiSessId;
            }
        }
        //in case of GET graphQL
        $graphQLVariables = $this->request->getParam('variables');
        if ($graphQLVariables) {
            $graphQLVariables = json_decode($graphQLVariables, true);
            if ($graphQLVariables && is_array($graphQLVariables)) {
                if (isset($graphQLVariables['simiSessId']) && $graphQLVariables['simiSessId'])
                    return $graphQLVariables['simiSessId'];
            }
        }
        return $result;
    }
}