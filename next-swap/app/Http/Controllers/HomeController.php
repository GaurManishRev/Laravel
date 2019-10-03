<?php

namespace App\Http\Controllers;

use trans;
use Config;
use App\User;
use App\Coins;
use Exception;
use App\Addresses;
use Illuminate\Http\Request;
use Unirest\Request as UnirestAPI;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {}

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $result = User::where('id', auth()->user()->id)->with('nextToken', 'nextCoin')->first();

        /**
         * API Call
         */
        $next_token_address = $result->nextToken[0]->address_address;
        $load_next_token_balance_ep = str_replace('NEXT_TOKEN_ADDRESS', $next_token_address, config('env.next_token_balance_ep'));
        $next_token_balance = auth()->user()->loadWalletBalance($load_next_token_balance_ep);
        
        $next_coin_address = null;
        $next_coin_balance = 0;

        if ($result->nextCoin) {
            $next_coin_address = $result->nextCoin[0]->address_address;
            $load_next_coin_balance_ep = str_replace('NEXT_COIN_ADDRESS', $next_coin_address, config('env.next_coin_balance_ep'));
            $next_coin_balance = auth()->user()->loadWalletBalance($load_next_coin_balance_ep);
        };

        return view('welcome')->with([
            'next_token_address' => $next_token_address,
            'next_token_balance' => $next_token_balance,
            'next_coin_address' => $next_coin_address,
            'next_coin_balance' => $next_coin_balance
        ]);
    }

    public function storeNextWalletAddress(Request $request)
    {
        $this->validate($request, [
            'pk' => 'required|string',
            'walletAddress' => 'required|string'
        ]);

        throw_if(!($coin = Coins::where(['coin_coin' => 'NEXTX'])->first()), new Exception(trans('COIN_NOT_FOUND')));

        $result = auth()->user()->addresses()->create([
            'address_address' => $request->walletAddress,
            'address_coin' => $coin->coin_id,
            'address_type' => 'NEXTX',
            'pk' => $request->pk
        ]);
        
        throw_if(!$result, new Exception(trans('messages.NEXT_WALLET_ADDRESS_CREATE_FAILURE')));

        try {
            return response()->json([
                'status' => true,
                'message' => trans('messages.NEXT_WALLET_ADDRESS_CREATE_SUCCESS'),
                'data' => []
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    public function swapTokens()
    {
        try {
            $result = User::where('id', auth()->user()->id)->with('nextToken', 'nextCoin')->first();
            $token_send_from = $result->nextToken[0]->address_address;
            $load_next_token_balance_ep = str_replace('NEXT_TOKEN_ADDRESS', $token_send_from, config('env.next_token_balance_ep'));
            $next_token_balance = auth()->user()->loadWalletBalance($load_next_token_balance_ep);

            
            $resp = auth()->user()->swap($result->nextToken[0], $result->nextCoin[0], config('env.test_transferable_token_balance'));
            throw_if($resp === 'Failed', new Exception(trans('messages.SWAPPING_FAILURE')));

            return response()->json([
                'status'  => true,
                'message' => $resp === 'Success' ? trans('messages.SWAPPING_SUCCESS') : trans('messages.SWAPPING_PENDING')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 200);
        }
    }
}
