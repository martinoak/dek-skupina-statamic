<?php

namespace Statamic\Addons\MainAddon;

use Statamic\Extend\Tags;

class MainAddonTags extends Tags
{

    const DEFAULT_LANG = 'cz';

    public function translate()
    {
        $lang       = $this->get('lang');
        $page       = $this->get('page');
        $type       = $this->get('type');
        $dictionary = $this->storage->getYaml('routes')['pagesTranslate'];

        $default = $dictionary[$page][$type][self::DEFAULT_LANG];
        return $dictionary[$page][$type][$lang] ?? $default;
    }
    
    public function translateUrl()
    {
        $lang       = $this->get('lang');
        $page       = $this->get('page');
        $dictionary = $this->storage->getYaml('routes')['pagesTranslate'];

        $default = $dictionary[$page]['url'][self::DEFAULT_LANG];
        return $dictionary[$page]['url'][$lang] ?? $default;
    }
    
    public function translateName()
    {
        $lang       = $this->get('lang');
        $page       = $this->get('page');
        $dictionary = $this->storage->getYaml('routes')['pagesTranslate'];

        $default = $dictionary[$page]['name'][self::DEFAULT_LANG];
        return $dictionary[$page]['name'][$lang] ?? $default;
    }
    
    public function isUrlActive()
    {
        $lang       = $this->get('lang');
        $page       = $this->get('page');
        $class      = $this->get('class');
        $ask        = trim(array_get($this->storage->getYaml('routes'), "pagesTranslate.$page.url.$lang", ''), '\/');
        $requestUri = trim(request()->getRequestUri(), '\/');
        if (str_contains($requestUri, '?')){
            $requestUri = substr($requestUri, 0, strpos($requestUri, "?"));
        }
        return ($requestUri === $ask) ? $class : '';
    }

    public function translateText()
    {
        $lang       = $this->get('lang');
        $text       = $this->get('text');
        $page       = $this->get('page');
        $dictionary = $this->storage->getYaml('content')[$page];

        $default = $dictionary[$text][self::DEFAULT_LANG];
        return $dictionary[$text][$lang] ?? $default;
    }

    public function getParallax()
    {
        $index = $this->get('index');
        $data  = [-30, 60, -150, 40];
        return (isset($data[$index])) ? $data[$index] : 0;
    }
    
    
    
}