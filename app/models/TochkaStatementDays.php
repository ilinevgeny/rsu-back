<?php

namespace rsu\models;

class TochkaStatementDays extends AbstractModel
{
    public $id;
    public $tochka_statement_id;
    public $date;
    public $day_saldo_in;
    public $day_saldo_out;
    public $day_turn_over_dt;
    public $day_turn_over_kt;
    public $total_records;

    public static function findByMonthYear($month, $year, $tochkaStatementId)
    {
        return self::find([
            'columns' => 'id, tochka_statement_id, date, day_saldo_in, day_saldo_out, UNIX_TIMESTAMP(date) as timestamp',
            'order' => 'date ASC',
            'conditions' => 'MONTH(date) = ?0 AND YEAR(date) = ?1 AND tochka_statement_id = ?2',
            'bind'       => [$month, $year, $tochkaStatementId]
        ]);
    }

    public static function findByDate($date)
    {
        $model = parent::find([
            'conditions' => 'date = ?0',
            'bind'       => [$date]
        ]);

        return $model;
    }
}