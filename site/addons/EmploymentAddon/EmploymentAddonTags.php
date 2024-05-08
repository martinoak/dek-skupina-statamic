<?php

namespace Statamic\Addons\EmploymentAddon;

use Statamic\Extend\Tags;
use Statamic\Data\Entries\EntryCollection;
use Statamic\Addons\MysqlManager\Database;
use Statamic\Addons\MssqlManager\MssqlManagerAPI;

class EmploymentAddonTags extends Tags
{

    public function getPositionsList()
    {

        $locale = $this->get('locale', site_locale());

        $positionsList = $this->api('MssqlManager')->getVacanies($locale, $locale);

        return [
            'division' => $this->getDivision($locale),
            'jobType' => $this->getJobType($locale),
            'locations' => $this->getLocations($positionsList['locations']),
            'positions' => $positionsList['positions']
        ];
    }

    public function getRegularPositionsList()
    {
        $locale = $this->get('locale', site_locale());
        $positionsList = $this->api('MssqlManager')->getRegularVr($locale);
        return $this->parseLoop($positionsList);
    }

    public function getDivision($locale)
    {
        if ($locale == 'cz') {
            $jobType = $this->api('MssqlManager')->getListOfAllDisciplines();
            $jobType = array_column($jobType, 'POPIS');
        } elseif ($locale == 'sk') {
            $jobType = [
                "Administratíva",
                "IT / IS",
                "Financie a účtovníctvo",
                "Logistika a doprava",
                "Marketing a PR",
                "Obchod a predaj",
                "Personalistika a HR",
                "Výroba",
                "Iné",
                "Veda a výskum",
                "Projektovanie a dozor",
                "Manažérske pozície",
                "Výstavba a správa majetku"
            ];
        }

        return json_encode($jobType);
    }

    public function getAdvertisements()
    {
        $a = [
            ['value' => "www.prace.cz"],
            ['value' => "www.jobs.cz"],
            ['value' => "www.hotjobs.cz"],
            ['value' => "www.volnamista.cz"],
            ['value' => "www.indeed.com"],
            ['value' => "www.dek.cz"],
            ['value' => "Facebook"],
            ['value' => "LinkedIn"],
            ['value' => "Úřad práce"],
            ['value' => "Nevyplněno/Jiné"],
        ];
        return $this->parseLoop($a);
    }

    /**
     * Returns a JSON encoded array of job types
     * 
     * @param locale The locale of the language you want to get.
     * 
     * @return The job type options for the dropdown menu.
     */
    public function getJobType($locale)
    {
        if ($locale == 'sk') {
            return json_encode([
                "Nevybrané",
                "Plný úväzok",
                "Čiastočný úväzok",
                "Študent"
            ]);
        } else {
            return json_encode([
                "Nevybráno",
                "Plný úvazek",
                "Zkrácený úvazek",
                "Student"
            ]);
        }
    }

    public function getLocations($locations)
    {
        return json_encode(array_values(array_merge(["Nevybráno"], $locations)));
    }

    /**
     * This function returns the total number of positions in the database
     * 
     * @return The number of positions in the database.
     */
    public function getPositionsCount()
    {
        $result = 0;
        $locale = $this->get('locale', site_locale());
        //ak je jazyk EN tak vyberiem vsetky pozicie z DB pre CZ pretoze pre EN je to nula
        if($locale === 'en'){
            $locale = 'cz';
        }
        $companies = $this->api('MssqlManager')->getVacanies($locale)['positions'];
        foreach ($companies as $company) {
            $result += sizeof($company);
        }
        return $result;
    }

    public function getDescription()
    {
        $id = $this->get('id');

        $requirments = $this->api('MssqlManager')->getInzeratLine($id, 'POZADAVKY');
        $offers = $this->api('MssqlManager')->getInzeratLine($id, 'NABIDKA');
        $proccess = $this->api('MssqlManager')->getInzeratLine($id, 'PRACE');

        $details = $this->api('MssqlManager')->getPositionDetail($id);

        if (!isset($details['POPISPOZICE'])) {

            return [
                'requirments' => null,
                'offers' => null,
                'proccess' => null,
                'location' => null,
                'hdr' => null,
                'position' => null,
                'divize' => null
            ];
        }

        $img = array(
            "administrativa",
            "finance-a-ucetnictvi",
            "itis",
            "logistika-a-doprava",
            "manazerske-pozice",
            "marketing-a-pr",
            "obchod-a-prodej",
            "personalistika-a-hr",
            "projektovani-a-dozor",
            "veda-a-vyzkum",
            "vyroba",
            "vystavba-a-sprava-majetku"
        );

        $showImg = false;
        if (in_array(str_slug($details['divize']), $img)) {
            $showImg = true;
        }


        return [
            'requirments' => $requirments, 'offers' => $offers, 'proccess' => $proccess, 'location' => $details['MESTO_PRAC'], 'hdr' => $details['INZERAT_HDR'], 'position' => $details['POPISPOZICE'], 'divize' => $details['divize'], 'inz_pat' => $details['INZERAT_PAT'], 'spodek' => $details['INZERAT_SPODEK'], 'showImg' => $showImg
        ];
    }

    public function getImage()
    {
        $id = $this->get('id');
        $data = $this->api('MssqlManager')->czINTRA_VR_CENT_InzeratObrazek($id);

        if (isset($data[0]['POUZITO']) && $data[0]['POUZITO'] == 1 && isset($data[0]['OBRAZEK'])) {
            if ($data[0]['OBRAZEK']) {
                $result = base64_encode($data[0]['OBRAZEK']);
                $type = $data[0]['POUZITO'];
            } else {
                $result = $type = 0;
            }
        } elseif (isset($data[0]['POUZITO']) && $data[0]['POUZITO'] == 2 && isset($data[0]['KOD_YOUTUBE'])) {
            if ($data[0]['KOD_YOUTUBE']) {
                $result = 'https://www.youtube.com/embed/' . $data[0]['KOD_YOUTUBE'];
                $type = $data[0]['POUZITO'];
            } else {
                $result = $type = 0;
            }
        } else {
            $result = $type = 0;
        }

        return ['type' => $type, 'result' => $result];
    }
}
