<?php

namespace Statamic\Addons\ActionAddon;

use Statamic\Extend\Tags;
use Statamic\Data\Entries\EntryCollection;
use Statamic\Addons\MysqlManager\Database;

class ActionAddonTags extends Tags
{

    public function getActionsList()
    {
        if(null !== $this->get('archive') ){
            $archive = $this->get('archive');
        }else{
            $archive = 0;
        }

        $locale = $this->get('locale', site_locale());
        $actionsList = $this->api('MysqlManager')->getListOfAllActions($archive);
        
        return [
            'actions' => $actionsList
        ];
    }



    public function getFiltersForActions()
    {
        $locale = $this->get('locale', site_locale());
        $filters = $this->api('MysqlManager')->getFiltersForActions($locale);       

        return $filters;
    }
}