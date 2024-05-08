<?php

namespace Statamic\Addons\MainAddon;

use Statamic\Extend\Filter;

class MainAddonFilter extends Filter
{

    /**
     * Perform filtering on a collection
     *
     * @return \Illuminate\Support\Collection
     */

    //projede vsechny karusely v dane kolekci a prozene funkci filterTiles   
    public function filter($collection)
    {

        return $collection->filter(function ($entry) {
            $tiles    = [];
            $position = $this->get('position');
            if ($entry->get('title') == 'Karusel ' . $position || $entry->get('title') == 'Karusel-' . $position) {
                $tiles = $entry->get('tiles');
                return $entry->set('tiles', $this->filterTiles($tiles));
            }
        });
    }

    //projede vsechno v dane kolekci rozdeli karusely do dvou poli 
    //videoTiles = ty co obsahuji videa
    //pokud je dlazdice s videem ma se vybirat prednostne a pri kazdem nacteni nahodne
    private function filterTiles($tiles)
    {
        $videoTiles = [];
        foreach ($tiles as $tile) {
            
            if (isset($tile['video'])) {
                $videoTiles[] = $tile;
            }
        }
        
        if ($videoTiles) {
            return $videoTiles[array_rand($videoTiles)];
        } else {
            return $tiles[array_rand($tiles)];
        }
    }
}
