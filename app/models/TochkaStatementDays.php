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

    public static function findByMonthYear($month, $year)
    {
        return self::find([
            'columns' => 'id, date, day_saldo_in, day_saldo_out, UNIX_TIMESTAMP(date) as timestamp',
            'order' => 'date ASC',
            'conditions' => 'MONTH(date) = ?0 AND YEAR(date) = ?1',
            'bind'       => [$month, $year]
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