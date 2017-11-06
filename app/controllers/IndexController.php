<?php

use rsu\models\Streets;
use rsu\models\Houses;
use rsu\models\Cities;
use rsu\models\Regions;
use rsu\service\Utils;
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

    public function getPaymentAction($house_id)
    {
//        echo "<pre>";
//        $house = Houses::findById($house_id);
//        print_r($house->account_id);
        $this->getToken();
//        echo "</pre>";
        exit;
    }

    protected function getToken()
    {
        $ch = curl_init();


        curl_setopt($ch, CURLOPT_URL, "https://api.tochka.com/auth/oauth/token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_POST, TRUE);

        curl_setopt($ch, CURLOPT_POSTFIELDS, "username=" . $this->config->tochka['username'] . "&password="
            . $this->config->tochka['password'] . "&grant_type=password");

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/x-www-form-urlencoded",
            "Authorization: Basic aXBob25lYXBwOnNlY3JldA=="
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
    /**
     *
     * @return string
     */
    public function getHousesAction()
    {
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
            header('Content-Type: application/json');
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
        header('Content-Type: application/json');
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

