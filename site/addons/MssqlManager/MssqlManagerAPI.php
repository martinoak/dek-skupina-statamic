<?php

namespace Statamic\Addons\MssqlManager;

use Statamic\Extend\API;
use DekApps\MssqlProcedure\Connection;
use DekApps\MssqlProcedure\MssqlException;
use Statamic\Contracts\Forms\Submission;
use Statamic\Addons\CustomForms\Response;

include __DIR__ . '/dek-apps/mssql-sqlsrv-3.5.6/src/Cache/TCacheFilesystem.php';
include __DIR__ . '/dek-apps/mssql-sqlsrv-3.5.6/src/Connection.php';
include __DIR__ . '/dek-apps/mssql-sqlsrv-3.5.6/src/IProcedure.php';
include __DIR__ . '/dek-apps/mssql-sqlsrv-3.5.6/src/Procedure.php';
include __DIR__ . '/dek-apps/mssql-sqlsrv-3.5.6/src/ProcedureResult.php';
include __DIR__ . '/dek-apps/mssql-sqlsrv-3.5.6/src/MssqlException.php';
include __DIR__ . '/dek-apps/mssql-sqlsrv-3.5.6/src/Event/IEvent.php';
include __DIR__ . '/dek-apps/mssql-sqlsrv-3.5.6/src/Event/Event.php';
include __DIR__ . '/dek-apps/mssql-sqlsrv-3.5.6/src/Config/IFieldSet.php';
include __DIR__ . '/dek-apps/mssql-sqlsrv-3.5.6/src/Config/FieldSet.php';



class MssqlManagerAPI extends API
{
    private $connection;
    private $connectionArchiv;

    private $conn;

    const VR_TYPE_SPECIFIC = 0b01;  // flag 1
    const VR_TYPE_REGULAR = 0b10;  // flag 2

    /**
     * The init() function is called when the class is instantiated. 
     * It creates a new Connection object and stores it in the  property. 
     * It also creates a new Connection object and stores it in the  property
     */
    protected function init()
    {
        $server = env('APP_MSSQL_SERVER');
        $user = env('APP_MSSQL_USER');
        $pass = env('APP_MSSQL_PASSWORD');
        $dbname = env('APP_MSSQL_DBNAME');
        $this->connection = new Connection($server, $user, $pass, $dbname);

        // ARCHIV
        $server = env('APP_MSSQL_SERVER_ARCHIV');
        $user = env('APP_MSSQL_USER_ARCHIV');
        $pass = env('APP_MSSQL_PASSWORD_ARCHIV');
        $dbname = env('APP_MSSQL_DBNAME_ARCHIV');
        $this->connectionArchiv = new Connection($server, $user, $pass, $dbname);
    }

    /**
     * Get the line of an inzerat
     * 
     * @param id The ID of the inzerat
     * @param type 1 = text, 2 = image, 3 = video, 4 = audio, 5 = file
     * 
     * @return The procedure returns an array of arrays. The first array is the column names. The second
     * array is the data.
     */
    public function getInzeratLine($id, $type)
    {
        $procedure = $this->connection->getProcedure('czINTRA_VR_CENT_InzeratLINE')
            ->setInput('@IN_IDVR', $id)
            ->setInput('@IN_TYP', $type)
            ->execute();
        return array_keys($procedure->fetchArray('POPIS'));
    }

    //czKARTY_Kraje_SeznamCNT - ciselnik kraju (nova procedura pro ziskani lokality do filtru)
    // ESHOP_KRAJ_ID -> id Regionu (nazev v ciselniku)
    public function getPositionDetail($id): array
    {
        $procedure = $this->connection->getProcedure('czINTRA_VR_CENT_InzeratHDR')
            ->setInput('@IN_IDVR', $id)
            ->execute();

        $detail = $procedure->fetch();
        $detail['divize'] = $this->getDivisionFirst($id);

        return $detail;
    }

    /**
     * It takes the data from the database and returns it as an array
     * 
     * @param locale The locale of the vacancies.
     * @param lang The language of the vacancies.
     * 
     * @return an array with two elements:
     */
    public function getVacanies($locale, $lang = "CZ"): array
    {
        $list = array();
        $locations = array();

        $vacanies = $this->prepareDataVacanies($locale);
        $regionsList = $this->getRegionsList($lang);

        foreach ($vacanies as $vacany) {
            if ($this->isAtelier($vacany['POBOCKA'], $vacany['PODNIK_ID'])) {
                $company = 'e-Atelier DEK';
            } else {
                $prefixCompany = $this->addPrefixCompany($vacany['PODNIK']);
                $company = $prefixCompany . '-' . $vacany['PODNIK'];
            }

            if (str_contains($vacany['KRAJ_ID'], ",")) {
                $regionIDs = explode(",", $vacany['KRAJ_ID']);
                $regionArr = "";
                foreach ($regionIDs as $key => $regionID) {
                    $region = isset($regionsList[$regionID]) ? $regionsList[$regionID] : 'Neuvedeno';
                    if ($key > 0) {
                        $regionArr = $regionArr . "," . $region;
                    } else {
                        $regionArr = $region;
                    }
                }
                $region = "[" . $regionArr . "]";
            } else {
                $regionID = $vacany['KRAJ_ID'];
                $region =  isset($regionsList[$regionID]) ? $regionsList[$regionID] : 'Neuvedeno';
            }

            $locations[$regionID] = $region;

            $typeOfWork = $this->getTypeOfWork($vacany['POPIS'], $lang);

            $divisions = $this->getDivisions($vacany['ID']);

            $list[$company][] = [
                'location' => $region,
                'id' => $vacany['ID'],
                'title' => $vacany['POPIS'],
                'mesto' => $vacany['MESTO'],
                'type' => $typeOfWork,
                'division' => $divisions,
                'company' => $company,
                'locations' => $vacany['KRAJ_ID'],
                'region' => $vacany['REGION']
            ];
        }

        /* Sorting the array in ascending order. */
        ksort($list);

        return ['positions' => $list, 'locations' => $locations];
    }

    /**
     * This function returns an array of vacancies, where each vacancy is an array of key-value pairs. 
     * The key-value pairs are the properties of the vacancies. 
     * The vacancies are grouped by their ID. 
     * If a vacancy has the same ID as another vacancy, then the vacancies are merged. 
     * The vacancies are then returned as an array of key-value pairs
     * 
     * @param locale The locale of the vacancies you want to retrieve.
     * 
     * @return An array of vacancies.
     */
    public function prepareDataVacanies($locale)
    {
        $pozicie = array();
        $subsidiaryVacanies = $this->getListOfAllVacancies(0, $locale, null);
        $centralVacanies = $this->getListOfAllVacancies(1, $locale, null);
        $vacanies = array_merge($centralVacanies, $subsidiaryVacanies);

        foreach ($vacanies as $key => $vacanie) {

            if (!isset($pozicie[$vacanie['ID']])) {
                $pozicie[$vacanie['ID']] = $vacanie;
            } else {
                $pozicie[$vacanie['ID']]['KRAJ_ID'] = $pozicie[$vacanie['ID']]['KRAJ_ID'] . "," . $vacanie['KRAJ_ID'];
            }
        }

        return $pozicie;
    }

    /**
     * This function returns a string of comma separated values of the disciplines of the given school
     * 
     * @param id The ID of the school.
     * 
     * @return The list of disciplines for the given faculty.
     */
    public function getDivisions($id)
    {
        $division = $this->getListOfDisciplines($id);

        $div = '';
        foreach ($division as $d) {
            $div = $div . ',' . $d['POPIS'];
        }
        $div = substr($div, 1);
        $divisions = "[" . $div . "]";
        return $divisions;
    }

    /**
     * This function returns the first discipline of the division
     * 
     * @param id The ID of the competition.
     * 
     * @return The first discipline in the list of disciplines.
     */
    public function getDivisionFirst($id)
    {
        $division = $this->getListOfDisciplines($id);

        $div = '';
        foreach ($division as $d) {
            $div = $d['POPIS'];
            break;
        }

        return $div;
    }

    /**
     * This function returns a list of all regular VRs in the given state
     * 
     * @param state The state of the VR.
     * @param companyid The companyid of the company you want to get the VRs for.
     * 
     * @return An array of VRs.
     */
    public function getRegularVr($state = 'CZ', $companyid = null)
    {
        $procedure = $this->czINTRA_VR_CENT_SeznamVR(self::VR_TYPE_REGULAR, $state, $companyid);
        return $procedure->fetchArray();
    }

    /**
     * Get a list of VRs for a given type and state
     * 
     * @param type The type of VR to return.
     * @param state CZ, SK, EU
     * @param companyid The company ID. If not specified, the current company ID is used.
     * 
     * @return an array of VR IDs.
     */
    public function getVr($type, $state = 'CZ', $companyid = null)
    {
        $reg = $spec = [];
        if ($type & self::VR_TYPE_REGULAR) {
            $reg = $this->czINTRA_VR_CENT_SeznamVR(self::VR_TYPE_REGULAR, $state, $companyid)
                ->fetchArray('ID');
        }
        if ($type & self::VR_TYPE_SPECIFIC) {
            $spec = $this->czINTRA_VR_CENT_SeznamVR(self::VR_TYPE_SPECIFIC, $state, $companyid)
                ->fetchArray('ID');
        }
        return array_merge($reg, $spec);
    }

    private function czINTRA_VR_CENT_SeznamVR($type, $state = 'CZ', $companyid = null)
    {
        return $this->connection->getProcedure('czINTRA_VR_CENT_SeznamVR')
            ->setInput('@IN_ZEME', $state)
            ->setInput('@IN_TYP', $type)  // IN_TYP: 2 = obecné VR, 1 = klas.VR
            ->setInput('@IN_PODNIK', $companyid)
            ->execute();
    }

    public function getListOfAllVacancies($central = 1, $state = 'CZ', $companyid = Null)
    {
        $procedure = $this->connection->getProcedure('proc.Web_CNT_SeznamVR')
            ->setInput('@IN_Zeme', $state)
            ->setInput('@IN_Podnik_id', $companyid)
            ->setInput('@IN_Centrala', $central)
            ->execute();

        return $procedure->fetchArray();
    }

    private function isAtelier($branchoffice, $companyid)
    {
        if ($companyid == 'DEK' || $companyid == 'STAVEBNINY_DEK') {
            $atelier = [
                'STAVEBNINY_DEK' => ['S044', 'S045', 'S04510', 'S04511', 'S04512', 'S048'],
                'DEK' => ['S040', 'S042', 'S044', 'S046', 'S047']
            ];
        } else {
            $atelier = [
                'TEST_STAVEBNINY_DEK' => ['S044', 'S045', 'S04510', 'S04511', 'S04512', 'S048'],
                'TEST_DEK' => ['S040', 'S042', 'S044', 'S046', 'S047']
            ];
        }

        return isset($atelier[$companyid]) and in_array($branchoffice, $atelier[$companyid]);
    }

    /**
     * This function takes a job title and a language code and returns a string that is either "Full time"
     * or "Part time" depending on the job title
     * 
     * @param title The title of the job posting.
     * @param lang The language of the job posting.
     * 
     * @return The type of work.
     */
    private function getTypeOfWork($title, $lang)
    {
        if ($lang == 'cz') {
            if (strpos(strtolower($title), 'zkrácený') !== false  || strpos(strtolower($title), 'poloviční') !== false || strpos(strtolower($title), 'částečný') !== false) {
                return 'Zkrácený úvazek';
            } elseif (strpos(strtolower($title), 'brigáda') !== false  || strpos(strtolower($title), 'student') !== false) {
                return 'Student';
            } else {
                return 'Plný úvazek';
            }
        } elseif ($lang == 'sk') {
            if (strpos(strtolower($title), 'skrátené') !== false  || strpos(strtolower($title), 'polovičný') !== false || strpos(strtolower($title), 'čiastočný') !== false) {
                return 'Čiastočný úväzok';
            } elseif (strpos(strtolower($title), 'brigáda') !== false  || strpos(strtolower($title), 'študent') !== false) {
                return 'Študent';
            } else {
                return 'Plný úväzok';
            }
        }
    }

    /**
     * This function returns a list of VR ciselniky
     * 
     * @param type The type of the cislnik.
     * @param state The state of the VR.
     * 
     * @return An array of associative arrays.
     */
    public function getVRCiselniky($type, $state = 'CZ')
    {
        $procedure = $this->connection->getProcedure('czINTRA_VR_CENT_Ciselniky')
            ->setInput('@IN_ZEME', $state)
            ->setInput('@IN_TYP_CISELNIKU', $type)
            ->execute();
        return $procedure->fetchArray();
    }

    /**
     * This function returns a list of regions in the given language
     * 
     * @param lang The language of the regions list.
     * 
     * @return An array of arrays. Each array contains the region ID and the region name.
     */
    private function getRegionsList($lang)
    {
        $result = array();
        $procedure = $this->connection->getProcedure('proc.czINTRA_Kraje_SeznamCNT')
            ->setInput('@IN_Stat', $lang)
            ->execute();

        $records = $procedure->fetchArray();

        foreach ($records as $record) {
            $result[$record['KRAJ_ID']] = $record['NAZEV']; 
        }

        return $result;
    }

    /**
     * Insert a new candidate into the database
     * 
     * @param firstname First name of the candidate
     * @param lastname The lastname of the candidate
     * @param email email address of the candidate
     * @param phone The phone number of the candidate.
     * @param advertisement The advertisement ID (e.g. "CZ-INTRA-VR-CENT-NovyUchazec-1")
     * @param note This is the note that will be added to the candidate.
     * @param zeme 1 = Česká republika, 2 = Slovensko, 3 = Česká republika + Slovensko
     * 
     * @return The ID of the new candidate.
     */
    public function insertNewCandidate($firstname, $lastname, $email, $phone, $advertisement, $note, $zeme)
    {
        $procedure = $this->connection->getProcedure('czINTRA_VR_CENT_NovyUchazec')
            ->setInput('@IN_JMENO', $firstname)
            ->setInput('@IN_PRIJMENI', $lastname)
            ->setInput('@IN_TITUL_PRED', null)
            ->setInput('@IN_TITUL_ZA', null)
            ->setInput('@IN_DATUM_NAROZENI', null)
            ->setInput('@IN_ULICE', null)
            ->setInput('@IN_MESTO', null)
            ->setInput('@IN_PSC', null)
            ->setInput('@IN_STAT', null)
            ->setInput('@IN_MAIL', $email)
            ->setInput('@IN_TELEFON', $phone)
            ->setInput('@IN_ZDROJ', $advertisement)
            ->setInput('@IN_VZDELANI', null)
            ->setInput('@IN_PRAXE', null)
            ->setInput('@IN_DELKA_PRAXE', null)
            ->setInput('@IN_PRUV_DOPIS', $note)
            ->setInput('@IN_ZEME', $zeme)
            ->setOutput('@O_IDUCHAZECE', 'id', SQLSRV_SQLTYPE_VARCHAR);

        $procedure->execute();

        return trim($procedure->getOutputs()['id']);
    }

    /**
     * Assign a candidate to a VR
     * 
     * @param vrId The ID of the VR to assign the candidate to.
     * @param candidateId The candidate's ID.
     */
    public function assignCandidateToVr($vrId, $candidateId)
    {
        $procedure = $this->connection->getProcedure('czINTRA_VR_CENT_UchazecToVR')
            ->setInput('@IN_IDVR', $vrId)
            ->setInput('@IN_IDUCHAZECE', $candidateId)
            ->setOutput('@O_ErrMsg', 'errMsg', SQLSRV_SQLTYPE_VARCHAR)
            ->execute();

        $errMsg = trim($procedure->getOutputs()['errMsg']);
        if ($errMsg !== '') {
            throw new MssqlException($errMsg);
        }
    }

    /**
     * Uploads a file to the database
     * 
     * @param file The file to upload.
     * @param candidateId The candidate's ID.
     * 
     * @return The ID of the document.
     */
    function uploadCV($file, $candidateId)
    {
        $companyId = $this->getCompanyId();

        //$content = file_get_contents($file);
        $content = fopen($file, 'r');

        $filename = substr(strrchr($file, '/'), 1);
        $ext = strrchr($filename, '.');
        $basename = str_replace($ext, '', $filename);

        $procedure = $this->connectionArchiv->getProcedure('czARCHIV_Vytvorit_Soubor')
            ->setInput('@IN_IDPodniku', $companyId)
            ->setInput('@IN_Oblast', 'UCHAZECI')
            ->setInput('@IN_Cislo', $candidateId)
            ->setInput('@IN_Typ', 0)
            ->setInput('@IN_Koncovka', $ext)
            ->setInput('@IN_Popis', $basename)
            ->setInput('@IN_UserID', 'skupina-dek.cz')
            ->setInput('@IN_Slozka', '')
            ->setInput('@IN_BinaryData', $content, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max'))
            ->setOutput('@O_ChybaID', 'errCode', SQLSRV_SQLTYPE_INT)
            ->setOutput('@O_ChybaText', 'errMsg', SQLSRV_SQLTYPE_VARCHAR)
            ->setOutput('@O_IDDoc', 'idDoc', SQLSRV_SQLTYPE_INT)
            ->execute(false);

        if ($procedure->getOutputs()['errCode'] !== 0) {
            throw new MssqlException($procedure->getOutputs()['errMsg'], $procedure->getOutputs()['errCode']);
        }

        return trim($procedure->getOutputs()['idDoc']);
    }

    /**
     * Get the company ID from the database
     * 
     * @return The company ID.
     */
    public function getCompanyId()
    {
        $procedure = $this->connection->getProcedure('czGLOBAL_Mistni_IDPodniku')
            ->setOutput('@O_IDPodniku', 'idPodniku', SQLSRV_SQLTYPE_VARCHAR)
            ->execute();
        return trim($procedure->getOutputs()['idPodniku']);
    }

    /**
     * This function returns a list of disciplines for a given course
     * 
     * @param id The ID of the discipline.
     * 
     * @return An array of arrays.
     */
    public function getListOfDisciplines($id)
    {
        $procedure = $this->connection->getProcedure('proc.czINTRA_VR_CENT_SeznamOboru')
            ->setInput('@IN_Vybrizeni', $id)
            ->execute();

        return $procedure->fetchArray();
    }

    /**
     * Get a list of all disciplines
     * 
     * @return An array of arrays.
     */
    public function getListOfAllDisciplines()
    {
        $procedure = $this->connection->getProcedure('proc.czINTRA_OborySeznamCNT')
            ->execute();

        return $procedure->fetchArray();
    }

    /**
     * This function returns the image of the inzerat with the given IDVR
     * 
     * @param idvr The ID of the VR.
     * 
     * @return The procedure returns an array of arrays. Each array contains the following fields:
     *         - IDVR
     *         - IDOBR
     *         - IDOBR_TYP
     *         - OBRAZKY
     *         - OBRAZKY_TYP
     *         - OBRAZKY_KOD
     */
    public function czINTRA_VR_CENT_InzeratObrazek($idvr)
    {
        $procedure = $this->connection->getProcedure('[proc].[czINTRA_VR_CENT_InzeratObrazek]')
            ->setInput('@IN_IDVR', $idvr)
            ->execute();

        return $procedure->fetchArray();
    }

    public function addPrefixCompany($podnik)
    {
        switch ($podnik) {
            case 'Stavebniny DEK a.s.':
                $prefix = 'a';
                break;
            case 'ARGOS ELEKTRO, a. s.':
                $prefix = 'b';
                break;
            case 'DEK a.s.':
                $prefix = 'c';
                break;
            case 'DEKMETAL s.r.o.':
                $prefix = 'd';
                break;
            case 'Atelier DEK':
                $prefix = 'e';
                break;
            case 'DEKWOOD s.r.o.':
                $prefix = 'f';
                break;
            case 'DEKPROJEKT s.r.o.':
                $prefix = 'g';
                break;
            case 'ÚRS CZ a.s.':
                $prefix = 'h';
                break;
            case 'G SERVIS CZ, s.r.o.':
                $prefix = 'i';
                break;
            case 'First information systems, s.r.o.':
                $prefix = 'j';
                break;
            default:
                $prefix = 'x';
        }
        return $prefix;
    }
}
