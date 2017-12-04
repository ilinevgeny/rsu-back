<?php

namespace rsu\controllers;

use rsu\models\Streets;
use rsu\models\Houses;
use rsu\models\Cities;
use rsu\models\Regions;
use rsu\service\Utils;
use rsu\models\TochkaAccess;
use rsu\models\TochkaStatements;
use rsu\models\TochkaStatementDays;
use rsu\models\TochkaStatementRecords;
use  rsu\service\search\HousesSearch;
use rsu\models\Category;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Digit as DigitValidator;
use Phalcon\Validation\Validator\StringLength as StringLength;

class IndexController extends ControllerBase
{
    const NAME_OK = 'OK';
    const NAME_NOT_FOUND = 'Not Found';
    const NAME_INTERNAL_ERROR = 'Internal Server Error';
    const NAME_BAD_RQUEST = 'Bad request';
    const NAME_BAD_FIELD = 'Bad field';
    const STATUS_CODE_OK = '200';
    const STATUS_CODE_NOT_FOUND = '404';
    const STATUS_CODE_SERVER_ERROR = '500';
    const STATUS_CODE_BAD_REQUEST = '401';
    const STATUS_CODE_BAD_FIELD = '499';
    const MAIL_SUBJECT_QUESTION = 'Вопрос по транзакции';
    const MAIL_RESPOND_QUESTION = 'Спасибо за ваш вопрос. В ближайшее время мы с вами свяжемся.';

    public $code = self::STATUS_CODE_NOT_FOUND;
    public $name = self::NAME_NOT_FOUND;

    public function indexAction()
    {

    }

    public function sendQuestionAction()
    {
        $id = $this->request->get('id', null, 0);
        $name = $this->request->get('name', null, 0);
        $email = $this->request->get('email', null, 0);
        $question = $this->request->get('q', null, 0);

        $to = $this->config->mail->to;
        $subject = self::MAIL_SUBJECT_QUESTION;

        $jsonArr = [];
        $jsonArr['code'] = self::STATUS_CODE_OK;
        $jsonArr['name'] = self::NAME_OK;
        $jsonArr['result'] = self::MAIL_RESPOND_QUESTION;


        $validation = new Validation();

        $validation->add('name', new PresenceOf(
            [
                'message' => 'Имя необходимо указать'
            ]
        ));

        $validation->add('email', new PresenceOf(
            [
                'message' => "Email необходимо указать"
            ]
        ));

        $validation->add('q', new PresenceOf(
            [
                'message' => "Укажите ваш вопрос"
            ]
        ));
        $messageValidation = $validation->validate($_REQUEST);

        $fieldsArr = [];

        if (count($messageValidation)) {
            foreach ($messageValidation as $messageVal) {
                $fieldsArr[$messageVal->getField()] = $messageVal->getMessage();
            }

            $jsonArr['code'] = self::STATUS_CODE_BAD_FIELD;
            $jsonArr['name'] = self::NAME_BAD_FIELD;
            $jsonArr['result'] = $fieldsArr;
        } else {
            $transactionArr = TochkaStatementRecords::findById($id)->toArray();

            $datetime = TochkaStatementDays::findById($transactionArr['days_id']);
            $transactionArr['datetime'] = date('d-m-Y', time($datetime->date));
            $type = ($transactionArr['debit']) ? 'Расходы' : 'Доходы';
            $message = "<p><strong>Транзакция:</strong>
			<p><strong>Дата:</strong> " . $transactionArr['datetime'] . "</p> 
			<p><strong>Категория: </strong> " . $type . "</p> 
			<p><strong>Описание: </strong> " . $transactionArr['purpose'] . "</p> 
			<p><strong>Сумма</strong> " . $transactionArr['sum'] . "</p> 
		    </p> <p><strong>Имя:</strong> $name</p><p><strong>Email:</strong> $email</p><p><strong>Вопрос:</strong> $question</p>";
            try {
                $this->mailer->sendMail($to, $subject, $message);

            } catch (Exception $exception) {
                $jsonArr['code'] = self::STATUS_CODE_SERVER_ERROR;
                $jsonArr['name'] = self::NAME_INTERNAL_ERROR;
                $jsonArr['result'] = '';

            }
        }

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Type: application/json');
        return json_encode($jsonArr, JSON_UNESCAPED_UNICODE);
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
            header("Access-Control-Allow-Origin: *");
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Type: application/json');
            return json_encode($jsonArr, JSON_UNESCAPED_UNICODE);
        }
        foreach ($houses as $item) {
            $house = Houses::findById($item);
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
        header("Access-Control-Allow-Origin: *");
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Type: application/json');
        return json_encode($jsonArr, JSON_UNESCAPED_UNICODE);
    }

    public function getHouseInfoAction($house_id)
    {
        $result = $jsonArr = [];
        $house = Houses::findById($house_id);
	    $jsonArr['code'] = self::STATUS_CODE_OK;
	    $jsonArr['name'] = self::NAME_OK;
        if($house->account_id == 0) {
        	$jsonArr['result'] = null;
	        header('Content-Type: text/html; charset=utf-8');
	        header('Content-Type: application/json');
	        return json_encode($jsonArr, JSON_UNESCAPED_UNICODE);
        }
        $street = Streets::findById($house->street_id);


        $daysArr = [];
        $currMonth = date('m');
        $currYear = date('Y');
	    $tochkaStatement = TochkaStatements::findFirst(
		    ['conditions'=>'tochka_account = ?0', 'bind' => [$house->account_id]]);
        $statementDays = TochkaStatementDays::findByMonthYear($currMonth, $currYear, $tochkaStatement->id);
        $days = [];
        if ($statementDays->count() == 0) {
	        do {
		        $statementDays = TochkaStatementDays::findByMonthYear(--$currMonth, $currYear, $tochkaStatement->id);
	        } while($statementDays->count() == 0);
        }

        foreach ($statementDays as $k=>$day) {
            $daysArr['day'] = date('d', $day->timestamp);
            $daysArr['saldo_in'] = $day->day_saldo_in;
            $daysArr['saldo_out'] = $day->day_saldo_out;
//            $daysArr['сounterparty'] = '';
//            $daysArr['category'] = '';
            $daysArr['transactions'] = $this->getTochkaRecords($day->id, $day->date);
            $days[] = $daysArr;
        }
        $jsonArr['actual'] = date('d.m.Y', strtotime($tochkaStatement->timestamp));

        $yearsArr = ['2017'];
        $statMonths = TochkaStatementDays::find(['columns'=>array('month'=>'distinct (MONTH(date))'), 'order'=>'date DESC'])->toArray();
        foreach ($statMonths as $val) {

            if ($val['month'] == $currMonth) {
                $monthsArr[] = ['month'=>$val['month'], 'days'=>$days];
            } else {
                $monthsArr[] = ['month'=>$val['month'], 'days'=>null];
            }

        }

        $bills = [];
        foreach ($yearsArr as $year) {
            $bills[] = ['year' => $year, 'months'=>$monthsArr];
        }
        $jsonArr['result'] = [
            'id' => $house->id,
            'address' => $street->name . ', ' . $house->number,
            'img' => [
                'front' =>  'http://' . $this->config->common->front . '/' . $this->config->common->img . '/' . $house->photo_url
            ],
            'bills' => $bills,
            'actual' => $jsonArr['actual']
            ];

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Type: application/json');
        return json_encode($jsonArr, JSON_UNESCAPED_UNICODE);
    }

    public function getTransactionAction($house_id, $year, $month)
    {
        $daysArr = $jsonArr = [];
        $jsonArr['code'] = self::STATUS_CODE_OK;
        $jsonArr['name'] = self::NAME_OK;
        $jsonArr['result']['days'] = [];
        $house = Houses::findById($house_id);
	    $tochkaStatement = TochkaStatements::findFirst(
		    ['conditions'=>'tochka_account = ?0', 'bind' => [$house->account_id]]);
        $statementDays = TochkaStatementDays::findByMonthYear($month, $year, $tochkaStatement->id);
        $listDays = [];
        foreach ($statementDays as $day) {
            $daysArr['day'] = date('d', $day->timestamp);
            $daysArr['saldo_in'] = $day->day_saldo_in;
            $daysArr['saldo_out'] = $day->day_saldo_out;
            $daysArr['transactions'] = $this->getTochkaRecords($day->id, $day->date);
            $listDays[] = $daysArr;
        }
        $jsonArr['result']['days']= $listDays;

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Type: application/json');
        return json_encode($jsonArr, JSON_UNESCAPED_UNICODE);
    }

    public function getTochkaRecords($day_id, $datetime)
    {
        $result = [];
        $records =  TochkaStatementRecords::find([
                'conditions' => 'days_id = ?0',
                'bind'       => [$day_id]
            ])->toArray();
        foreach ($records as $n => $v) {
            $tmpArr['id'] = $v['id'];
            $tmpArr['type'] = ($v['debit']) ? 'debit' : 'credit';
            $tmpArr['sum'] = $v['sum'];
            $tmpArr['datetime'] = $datetime;
            $tmpArr['purpose'] = $v['purpose'];
            $tmpArr['counterparty'] = $v['counterparty'];
            $tmpArr['category'] = $this->getCategory($v['purpose']);

            $result[] = $tmpArr;
        }

        return $result;

    }

    protected function getCategory($purpose)
    {
        $tmpCategoryArr = [
            'электроэнергию' => 'Электроэнергия',
            'теплоэнергию' => 'Отопление',
            'плата тарифного плана "Эконом"' => 'Услуги расчетного центра',
            'Сборы с населения' => 'Доходы '
        ];
        $catecory = 'Общие расходы';
        foreach ($tmpCategoryArr as $key => $val) {
            if(strpos( $purpose, $key)) {
                $catecory = $val;
                break;
            }
        }
        return $catecory;
    }

    public function sendInvitationAction()
    {
        $name = $this->request->get('name', null, 0);
        $phone = $this->request->get('phone', null, 0);
        $address = $this->request->get('address', null, 0);

        $to = $this->config->mail->to;
        $subject = $this->config->mail->subject;

        $jsonArr = [];
        $jsonArr['code'] = self::STATUS_CODE_OK;
        $jsonArr['name'] = self::NAME_OK;
        $jsonArr['result'] = $this->config->mail->message;


        $validation = new Validation();

        $validation->add('name', new PresenceOf(
                [
                    'message' => 'Имя необходимо указать'
                ]
            ));

        $validation->add('phone', new PresenceOf(
                [
                    'message' => "Телефон необходимо указать"
                ]
            ));

        $validation->add('phone', new StringLength([
            'max' => 11,
            'min' => 5,
            "messageMaximum" => "Пожалуйста, укажите 11 цифр телефона",
            "messageMinimum" => "Пожалуйста, укажите 11 цифр телефона"
        ]));

        $validation->add('phone',
            new DigitValidator(
                [
                    'message' => "Телефон должен быть только числами"
                ]
            ));

        $validation->add('address', new PresenceOf(
                [
                    'message' => "Адрес необходимо указать"
                ]
            ));
        $messageValidation = $validation->validate($_REQUEST);

        $fieldsArr = [];

        if (count($messageValidation)) {
            foreach ($messageValidation as $messageVal) {
                $fieldsArr[$messageVal->getField()] = $messageVal->getMessage();
            }

            $jsonArr['code'] = self::STATUS_CODE_BAD_FIELD;
            $jsonArr['name'] = self::NAME_BAD_FIELD;
            $jsonArr['result'] = $fieldsArr;
        } else {
            $message = "<p><strong>Имя:</strong> $name</p><p><strong>Телефон:</strong> $phone</p><p><strong>Адрес дома:</strong> $address</p>";
            try {
                $this->mailer->sendMail($to, $subject, $message);

            } catch (Exception $exception) {
                $jsonArr['code'] = self::STATUS_CODE_SERVER_ERROR;
                $jsonArr['name'] = self::NAME_INTERNAL_ERROR;
                $jsonArr['result'] = '';

            }
        }

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

    public function createCategoryAction()
    {
        $categoryArr = [
            'Отопление',
            'ГВС',
            'ХВС',
            'Электроэнергия',
            'Услуги по управлению МКД',
            'Услуги расчетного центра',
            'Паспортный учет',
            'Услуги Call-центра',
            'Содержание придомовой территории',
            'Содержание подъездов и МОП                 ',
            'Инженерные системы Техническое обслуживание',
            'Инженерные системы Аварийное обслуживание',
            'Лифты Техническое обслуживание',
            'Лифты Аварийное обслуживание',
            'ИТП Техническое обслуживание',
            'Текущий ремонт общего имущества',
            'Организация вывоза ТБО',
            'Обслуживание домофонов',
            'Обслуживание видеонаблюдения',
            'Оплаты жителей',
            'Оплаты провайдеров'
        ];

        foreach ($categoryArr as $val) {
            echo hash("crc32", $val) . "<br>";
            $catecory = new Category();
            $catecory->name = mb_convert_encoding($val, 'UTF-8');
            $catecory->hash = hash("crc32", $val);
            $catecory->create();
        }
    }
}

