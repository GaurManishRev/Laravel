<?php

namespace App;

use Exception;
use Unirest\Request as UnirestAPI;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * Create a new model instance.
     *
     * @return void
     */
    public function __construct()
    {
        // basic auth
        UnirestAPI::auth(config('env.next_username'), config('env.next_password'));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid', 'name', 'email', 'password', 'session_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'uuid', 'password', 'remember_token', 'session_id'
    ];

    /**
     * The attributes that have default values.
     *
     * @var array
     */
    protected $attributes = [
        
    ]; 

    /**
     * Get the token address associated with the user.
     */
    public function addresses()
    {
        return $this->hasMany('App\Addresses', 'address_user_id', 'id');
    }

    /**
     * Get the ETH token address associated with the user.
     * It will always return ETH token on 0TH index
     */
    public function nextToken()
    {
        return $this->addresses()
            ->whereHas('coin', function($q) {
                $q->where('coin_coin', 'ETH');
            });
    }

    /**
     * Get the NEXTX coin address associated with the user.
     * It will always return NEXTX address on 0TH index
     */
    public function nextCoin()
    {
        return $this->addresses()
            ->whereHas('coin', function($q) {
                $q->where('coin_coin', 'NEXTX');
            });
    }

    /**
     * Loads the wallet balance against a given endpoint 
     */
    public function loadWalletBalance($end_point)
    {
        return UnirestAPI::get($end_point)->body->data->balance;
    }

    /**
     * Check transaction status
     */
    public static function checkTransactionByHash($hash = null)
    {
        throw_if(!$hash, new Exception(trans('messages.INVALID_TXN_ID')));
        $end_point = str_replace('TXN_ID', $hash, config('env.verify_token_transfer_ep'));
        return UnirestAPI::get($end_point);
    }

    /**
     * Swap tokens with coins
     * 
     * @return String ['Success', 'Failed', 'Pending']
     */
    public function swap(Addresses $token_address, Addresses $coin_address, $token_amount)
    {
        try {
            throw_if(!$token_address->address_address, new Exception(trans('messages.INVALID_TOKEN_ADDRESS')));
            throw_if(!$coin_address->address_address, new Exception(trans('messages.INVALID_COIN_ADDRESS')));
            throw_if($token_amount < config('env.minimum_transferable_token_balance'), new Exception(trans('messages.INSUFFICIENT_TOKEN_BALANCE')));
            
            $exchange_rate = config('env.token_to_coin_exchange_rate');
            $transferable_coin_amount = (float)($exchange_rate * $token_amount);
            
            /**
             * Payload
             */
            $body = [
                'pk' => $token_address->pk,
                'fromAddress' => $token_address->address_address,
                'toAddress' => config('env.intermediate_token_address'),
                'p_amount' => $token_amount,
                'contractAddress' => config('env.contract_address')
            ];

            $transfer_token_resp = UnirestAPI::post(config('env.transfer_token_ep'), ['Content-Type' => 'application/json'], json_encode($body));
            throw_if(!$transfer_token_resp->body->status, new Exception(trans('messages.SWAPPING_FAILURE')));
            
            /**
             * Record the Token Transaction in db
             */
                //code ..

            sleep(config('env.crowler_sleep_time'));
            $resp = self::checkTransactionByHash($transfer_token_resp->body->data->txid);
            throw_if(!$resp->body->status, new Exception($resp->body->message));

            return $resp->body->message;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
