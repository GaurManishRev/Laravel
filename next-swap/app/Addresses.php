<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Addresses extends Model
{
   /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'addresses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['address_user_id', 'address_address', 'address_payment_id', 'address_payment_id_type', 'address_coin', 'address_name', 'address_type', 'pk', 'salt', 'IV'];

    /**
     * The attributes that have default values.
     *
     * @var array
     */
    protected $attributes = [
        'address_name' => 'Wallet'
    ];
    
    /**
     * Get the token address associated with the user.
     */
    public function user()
    {
        return $this->hasOne('App\User', 'id', 'address_user_id');
    }

    /**
     * Get the coin associated with the address.
     */
    public function coin()
    {
        return $this->hasOne('App\Coins', 'coin_id', 'address_coin');
    }

}
