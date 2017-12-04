<?php


namespace rsu\service\tochka;

use Phalcon\Exception;
use rsu\models\Houses;
use rsu\models\TochkaAccess;

use Phalcon\Mvc\User\Component;
use rsu\models\TochkaStatementRecords;
use rsu\models\TochkaStatements;
use rsu\models\TochkaStatementDays;

class TochkaUploader extends Component
{

    public function getStatements($dataStart=null, $dataFinish=null)
    {
        $dayArr = [];
        $dayArr['start_date'] = ($dataStart) ? $dataStart : $this->config->tochka['start_date'];
        $dayArr['end_date'] = ($dataFinish) ? $dataFinish : $this->config->tochka['end_date'];

    	$token = $this->getToken();
	    $token = json_decode($token, TRUE);
	    if ($token == null || isset($token['error'])) {
	    	echo 'Ошибка получения токена. ' . $token['error_description'] . PHP_EOL;
	    }
	    $res = [];
//	    $token['access_token'] = '62390ded-f676-434f-bf27-008bf0be42ca';
	    $tochkaAccess = new TochkaAccess();
	    $res = $tochkaAccess->find([
		    'conditions' => 'access_token = ?0',
		    'bind' =>   [$token['access_token']]
	    ])->toArray();
	    if (!count($res)) {
	    	echo 'Новый токен  ' . $token['access_token'] . '  будет записан' . PHP_EOL;
	    	$tochkaAccess->access_token = $token['access_token'];
	    	$tochkaAccess->token_type = $token['token_type'];
	    	$tochkaAccess->refresh_token = $token['refresh_token'];
	    	$tochkaAccess->expires_in = $token['expires_in'];
		    try {
		    	$tochkaAccess->create();
		    } catch (\PDOException $exception) {

		    	echo 'Ошибка! Токен не удалось записать' . PHP_EOL;
			    echo $exception->getMessage() . PHP_EOL;
		    }
	    }

	    $listHousesIds = Houses::find(['columns' => 'id, account_id, number'])->toArray();

	    foreach ($listHousesIds as $house) {
	    	if($house['account_id'] != 0) {
                $statement = TochkaStatements::findByAccountId($house['account_id']);
			    $tochkaId = $this->getTochkaAccountId($token['access_token'], $house['account_id'], $dayArr);
			    $tochkaStatement = $this->getTochkaAccount($tochkaId, $token['access_token']);
                foreach ($tochkaStatement as $k => $v) {
//                    $timeStatement = ($k == '@attributes') ? $v['time'] : '';
                    if($k == 'data') {
//                            echo $tochkaStatement['data']['statement_response_v1']['start_date'] . PHP_EOL;
                        $statement->date_start = $tochkaStatement['data']['statement_response_v1']['start_date'];
                        $statement->date_end = $tochkaStatement['data']['statement_response_v1']['end_date'];
                        $statement->saldo_in = $tochkaStatement['data']['statement_response_v1']['saldo_in'];
                        $statement->saldo_out = $tochkaStatement['data']['statement_response_v1']['saldo_out'];
                        $statement->turn_over_dt = $tochkaStatement['data']['statement_response_v1']['turn_over_dt'];
                        $statement->turn_over_kt = $tochkaStatement['data']['statement_response_v1']['turn_over_kt'];
                        $statement->timestamp = date('Y-m-d H:i:s');

                        //@todo add exception to create
                        if($statement->update()) {
                            foreach ($tochkaStatement['data']['statement_response_v1']['days'] as $arrData) {

                                foreach ($arrData as $day) {
                                    $tochkaStatementDaysResult = TochkaStatementDays::findFirst(
                                        ['conditions' => 'date = ?0', 'bind'=> [$day['@attributes']['date']]]);

                                    if ($tochkaStatementDaysResult) {
//                                            echo $day['@attributes']['date'] . PHP_EOL;
                                    } else {
                                        echo 'Записываем дату ' . $day['@attributes']['date'] . PHP_EOL;
                                        $tochkaStatementDay = new TochkaStatementDays();
                                        $tochkaStatementDay->tochka_statement_id = $statement->id;
                                        $tochkaStatementDay->date = $day['@attributes']['date'];
                                        $tochkaStatementDay->day_saldo_out = $day['@attributes']['day_saldo_out'];
                                        $tochkaStatementDay->day_saldo_in = $day['@attributes']['day_saldo_in'];
                                        $tochkaStatementDay->day_turn_over_dt = $day['@attributes']['day_turn_over_dt'];
                                        $tochkaStatementDay->day_turn_over_kt = $day['@attributes']['day_turn_over_kt'];
                                        $tochkaStatementDay->total_records = count($day['records']['record']);
                                        if ($tochkaStatementDay->create()) {
                                            if(isset($day['records']['record']['@attributes'])) {
                                                $day['records']['record'][]['@attributes'] =  $day['records']['record']['@attributes'];
                                                unset($day['records']['record']['@attributes']);
                                            }
                                            foreach($day['records']['record'] as $record) {
                                                $tochkaStatementRecord = new TochkaStatementRecords();
                                                $tochkaStatementRecord->days_id = $tochkaStatementDay->id;
                                                $tochkaStatementRecord->debit = ($record['@attributes']['debit'] == 'true') ? 1 : 0;
                                                $tochkaStatementRecord->purpose = $record['@attributes']['purpose'];
                                                $tochkaStatementRecord->sum = $record['@attributes']['sum'];
                                                $tochkaStatementRecord->counterparty = $record['@attributes']['related_name'];
                                                $tochkaStatementRecord->create();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
		    } else {
	    		echo 'Дом ' . $house['number'] .  ' еще не имеет счета в Точке' . PHP_EOL;
		    }
	    }
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

        $response = simplexml_load_string(curl_exec($ch));
        curl_close($ch);

//        $response = simplexml_load_string(file_get_contents('respons.txt'));

        $json = json_encode((array)$response);
        $statementsArr = json_decode($json,TRUE);

        return $statementsArr;
    }

	/**
	 * @param houses $accountId
	 * @param $access_token
	 * @return mixed
	 */
    protected function getTochkaAccountId($access_token, $accountId, $dateArr = [])
    {
        if(count($dateArr) == 0) {
            $dateArr['start_date'] = '2014-01-01T00:00:00+03:00';
            $dateArr['end_date'] = '2020-12-01T00:00:00+03:00';
        }
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://api.tochka.com/ws/do/R0100");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_POST, TRUE);

        curl_setopt($ch, CURLOPT_POSTFIELDS, "<message_v1 xmlns=\"http://www.anr.ru/types\" type=\"request\">
        <data trn_code=\"R0100\">
        <statement_request_v1 xmlns=\"http://www.anr.ru/types\" 
            account_id=\"" . $accountId . "\" 
            account_bic=\"044525999\" 
            start_date=\"" . $dateArr['start_date'] . "\" 
            end_date=\"" . $dateArr['end_date'] . "\"></statement_request_v1>
        </data>
        </message_v1>");

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/xml",
            "Authorization: Bearer " . $access_token,
            "Accept: application/xml;"
        ));

        $response = curl_exec($ch);
        curl_close($ch);
	    $xmlTochkaId = new \SimpleXMLElement($response);
	    foreach ($xmlTochkaId->attributes() as $nameAttr => $valAttr) {
		    if ((string) $nameAttr == 'int_id') {
			    $int_id = (string) $valAttr;
		    }
	    }
        return $int_id;
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
}