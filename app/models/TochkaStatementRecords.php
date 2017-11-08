<?php


namespace rsu\models;


class TochkaStatementRecords extends AbstractModel
{
    public $id;
    public $days_id;
    public $debit;
    public $purpose;
    public $sum;

    public static function findRecordsByIds($idArr)
    {
        return self::find(
            [
                'conditions' => 'days_id in (' . implode(',',$idArr). ')'
            ]
        );
    }
}