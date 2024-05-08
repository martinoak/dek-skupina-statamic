<?php

namespace Statamic\Addons\NewsAddon;

use Statamic\Extend\Tags;

class NewsAddonTags extends Tags
{

    public function getType()
    {
        $parameter = (isset($_GET['type'])) ? $_GET['type'] : true;
        $yaml = $dictionary = $this->storage->getYaml('types');
        $type = (isset($yaml[$parameter])) ? $yaml[$parameter] : $parameter;
        return $type;
    }

    public function setAnchorHref()
    {
        $url = $this->get('url');
        $parameter = (isset($_GET['type'])) ? '?type=' . $_GET['type'] : '';
        $href =  $url . $parameter;
        return $href;
    }
}
