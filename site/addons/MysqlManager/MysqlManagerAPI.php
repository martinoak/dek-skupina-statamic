<?php

namespace Statamic\Addons\MysqlManager;

use Statamic\Extend\API;
use Statamic\Contracts\Forms\Submission;
use Statamic\Addons\CustomForms\Response;
use Illuminate\Database\MySqlConnection;
use Statamic\API\Request;



class MysqlManagerAPI extends API
{
    private $connection;

    /**
     * The init() function is called when the class is instantiated. 
     * It creates a new Connection object and stores it in the  property. 
     * It also creates a new Connection object and stores it in the  property
     */
    protected function init()
    {
        //$server = env('ACTION_DB_HOST');
        //$user = env('ACTION_DB_USERNAME');
        //$pass = env('ACTION_DB_PASSWORD');
        //$dbname = env('ACTION_DB_DATABASE');

//        $this->connection = mysqli_connect($server, $user, $pass, $dbname);
//
//        if (!$this->connection) {
//            die("Connection failed: " . mysqli_connect_error());
//        }

    }

    public function getListOfAllActions($archive){
        $params = Request::all();
        $apiUrl = env('API_URL');

        $url = $apiUrl . '/actions?archive='.$archive;

        if(isset($params['online'])){
            $url .= '&online=' . $params['online'];
        }

        if(isset($params['sector'])){
            $url .= '&sector=' . $params['sector'];
        }

        if(isset($params['page'])){
            $url .= '&page=' . $params['page'];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($json, true);

        foreach ($data as $key => $value) {
            //skontroluj active a ak je true tak zmen na 1 inak na 0
            if($value['active'] == true){
                $data[$key]['active'] = 1;
            }else{
                $data[$key]['active'] = 0;
            }
            //skontroluj online a ak je true tak zmen na 1 inak na 0
            if($value['online'] == true){
                $data[$key]['online'] = 1;
            }else{
                $data[$key]['online'] = 0;
            }
        }

        return $data;

        
    }

    public function getFiltersForActions(?string $lang){
        $apiUrl = env('API_URL');
        
        $url = $apiUrl . '/filters/' . $lang;

        $json = file_get_contents($url);
        $data = json_decode($json, true);

        return $data;

    }
    
}
