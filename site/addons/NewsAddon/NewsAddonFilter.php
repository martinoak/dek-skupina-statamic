<?php

namespace Statamic\Addons\NewsAddon;

use Statamic\Extend\Filter;
use Statamic\Entries\Entry;

class NewsAddonFilter extends Filter
{


    public function filter($collection)
    {
        return $collection->filter(function ($entry) {
            $parameter = $this->get('parameter');
            if (!$parameter) {
                return true;
            }
        });
    }

    private function getTranslates($parameter)
    {
        $yaml = $dictionary = $this->storage->getYaml('types');
        $type = (isset($yaml[$parameter])) ? $yaml[$parameter] : $parameter;
        return $type;
    }
}
/*
    news-appr:
        url:
            en: /en/media?type=appreciation
            cz: /tisk-a-media?type=oceneni
        name:
            en: Appreciation
            cz: Ocenění
    news-ann:
        url:
            en: /en/media?type=annual
            cz: /tisk-a-media?type=vyrocni-zpravy
        name:
            en: Annual Report
            cz: Výroční zprávy
    news-about:
        url:
            en: /en/media?type=about
            cz: /tisk-a-media?type=o-nas
*/