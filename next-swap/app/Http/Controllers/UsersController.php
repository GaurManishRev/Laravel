<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function generateWalletAddress(Request $request)
    {

    }

    /**
     * Get the ETH Token Address associated with the user.
     */
    public function tokenAddress()
    {
        return $this->hasOne('App\Phone');
    }

    
}
