<?php


namespace rsu\models;


class TochkaStatements extends AbstractModel
{
    public $id;
    public $tochka_account;
    public $date_start;
    public $date_end;
    public $saldo_in;
    public $saldo_out;
    public $turn_over_dt;
    public $turn_over_kt;


    public static function findByAccountId($account_id)
    {
        $model = parent::find([
            'conditions' => 'tochka_account = ?0',
            'bind'       => [$account_id]
        ]);

        return $model;
    }

}