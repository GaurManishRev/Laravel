<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Coins extends Model
{
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'coin_id';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'coins';
}
