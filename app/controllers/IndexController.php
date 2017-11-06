<?php

use rsu\models\Streets;
use rsu\models\Houses;
use rsu\models\Cities;
use rsu\models\Regions;
use rsu\service\Utils;
use rsu\models\TochkaAccess;
use rsu\models\TochkaStatements;
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
        $house = Houses::findById($house_id);
//        print_r($house->account_id);


//        $token = json_decode($this->getToken());
//        $token = $token->access_token;
//        print_r($token);
//        $account = $this->getTochkaAccountId($house->account_id);
        $xmlTochkaId = new SimpleXMLElement($this->getTochkaAccountId($house->account_id));
        foreach ($xmlTochkaId->attributes() as $nameAttr => $valAttr) {
            if ((string) $nameAttr == 'int_id') {
                $int_id = (string) $valAttr;
            }
        }
        $token = TochkaAccess::findById(1);
        $xmlTochkaAccount = new SimpleXMLElement($this->getTochkaAccount($int_id, $token->access_token));
        echo $this->getTochkaAccount($int_id, $token->access_token);
//        $xmlTochkaAccount;
//
//        print_r();
//        echo "</pre>";
        exit;
    }

    protected function getTochkaAccount($int_id, $access_token)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://api.tochka.com/ws/do/R0101");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_POST, TRUE);

        curl_setopt($ch, CURLOPT_POSTFIELDS,     "<message_v1 type=\"request\" int_id=\"" . $int_id . "\">
        <data trn_code=\"R0101\"></data>
        </message_v1>");

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/xml",
        "Authorization: Bearer " . $access_token,
        "Accept: application/xml;"
    ));

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    protected function getTochkaAccountId($account_id)
    {
        $ch = curl_init();
        $token = TochkaAccess::findById(1);
//        print_r($token->access_token); exit;
//        print_r($account_id); exit;
        curl_setopt($ch, CURLOPT_URL, "https://api.tochka.com/ws/do/R0100");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_POST, TRUE);

        curl_setopt($ch, CURLOPT_POSTFIELDS, "<message_v1 xmlns=\"http://www.anr.ru/types\" type=\"request\">
        <data trn_code=\"R0100\">
        <statement_request_v1 xmlns=\"http://www.anr.ru/types\" account_id=\"" . $account_id . "\" account_bic=\"044525999\"
        start_date=\"2014-01-01T00:00:00+03:00\" end_date=\"2017-12-01T00:00:00+03:00\">
        </statement_request_v1>
        </data>
        </message_v1>");

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/xml",
          "Authorization: Bearer " . $token->access_token,
          "Accept: application/xml;"
        ));

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;

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
        if (count($houses) == 0 && $getsearch != null ) {
            $jsonArr['code'] = self::STATUS_CODE_OK;
            $jsonArr['name'] = self::NAME_OK;
            $jsonArr['result']['found'] = count($houses);
            $jsonArr['result']['total'] = $total;
            $jsonArr['result']['list'] = [];
            header('Content-Type: text/html; charset=utf-8');
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
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Type: application/json');
        return json_encode($jsonArr, JSON_UNESCAPED_UNICODE);
    }

    public function getHouseInfoAction($house_id)
    {
        $result = $jsonArr = [];
        $house = Houses::findById($house_id);
        $street = Streets::findById($house->street_id);
        $statements = TochkaStatements::findByAccountId($house->account_id);

        foreach ($statements as $key=>$val) {
//            print_r($val->tochka_account);
        }
        $jsonArr['code'] = self::STATUS_CODE_OK;
        $jsonArr['name'] = self::NAME_OK;

        $daysArr = [];
        $jsonArr['result'] = [
            'id' => $house->id,
            'address' => $street->name . ', ' . $house->number,
            'img' => [
                'front' =>  'http://' . $this->config->common->front . '/' . $this->config->common->img . '/' . $house->photo_url
            ],
            'bills' => [
                '2017' => [
                    '09' => $daysArr
                ]
            ]
        ];

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Type: application/json');
        return json_encode($jsonArr, JSON_UNESCAPED_UNICODE);
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

