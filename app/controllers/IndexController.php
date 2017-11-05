<?php

use rsu\Models\Streets;
use rsu\Models\Houses;
use rsu\Models\Cities;
use rsu\Models\Regions;
use rsu\Service\Utils;
use  rsu\service\search\HousesSearch;

class IndexController extends ControllerBase
{
    const NAME_OK = 'OK';
    const NAME_NOT_FOUND = 'Not Found';
    const NAME_INTERNAL_ERROR = 'Internal Server Error';
    const NAME_BAD_RQUEST = 'Bad request';
    const STATUS_CODE_OK = '200';
    const STATUS_CODE_NOT_FOUND = '404';
    const STATUS_CODE_SERVER_ERROR = '500';
    const STATUS_CODE_BAD_REQUEST = '401';


    public $code = self::STATUS_CODE_NOT_FOUND;
    public $name = self::NAME_NOT_FOUND;

    public function indexAction()
    {

    }

    /**
     *
     * @return string
     */
    public function getHousesAction()
    {
        echo "<pre/>";
        $config = $this->getDI()->getShared('config');
        $offset = $this->request->get('offset', null, 0);
        $limit = $this->request->get('limit', null, 9);
        $getsearch = $this->request->get('search', null, '');
        $result = $jsonArr = [];
        $search = new HousesSearch();
        $houses = $search->find(mb_convert_encoding($getsearch, 'UTF-8'), $offset, $limit);
        $total = $search->count('');
        if (count($houses) == 0) {
            $jsonArr['code'] = $this->code;
            $jsonArr['name'] = $this->name;
            $jsonArr['result'] = null;
            return json_encode($jsonArr, JSON_UNESCAPED_UNICODE);
        }
        foreach ($houses as $house) {
            $house = Houses::findById($house);
            $street = Streets::findById($house->street_id);
            $city = Cities::findById($house->city_id);
            $region = Regions::findById($city->region_id);
            $result[] = [
                    'id' => $house->id,
                    'short_address'=> $street->name . ', ' . $house->number,
                    'address' =>$region->name . ', ' . $city->name . ', ' . $street->name . ', ' . $house->number,
                    'imgs' => [
                        'front' =>  'http://' . $config->common->front . '/' . $config->common->img . '/' . $house->photo_url
                    ]
            ];
        }
        $jsonArr['code'] = self::STATUS_CODE_OK;
        $jsonArr['name'] = self::NAME_OK;
        $jsonArr['result']['found'] = count($houses);
        $jsonArr['result']['total'] = $total;
        $jsonArr['result']['list'] = $result;
        return json_encode($jsonArr, JSON_UNESCAPED_UNICODE);
    }

    public function getHouseInfoAction($house_id)
    {

    }

    /**
     * @param $name
     * @param $id
     * @return string
     */
    private function getUniqueCode($name, $id = null)
    {
        $code = strtolower(Utils::getTranslate(preg_replace('/\s/ui', '-',
            preg_replace('/\.|\,|\s\+|\+/ui', '', Utils::trimmingText(trim($name))))));
        if (Houses::findFirst(['conditions' => 'photo_url = ?0', 'bind' => [$code]])) {
            $code = $code . '-' . $id;
        }
        return $code;
    }

}

