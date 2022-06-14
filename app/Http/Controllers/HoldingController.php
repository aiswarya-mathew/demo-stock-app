<?php

namespace App\Http\Controllers;

use App\Models\Holding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class HoldingController extends Controller
{
    public function fetchPortfolio(Request $request)
    {
        $validated_input = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ], [
            'required' => 'The :attribute field is required',
            'exists' => 'The :attribute field does not exist',
        ])->validate();

        $holdings = Holding::where('user_id', $validated_input['user_id'])->get();
    }
}
