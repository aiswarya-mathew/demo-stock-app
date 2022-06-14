<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use App\Models\Holding;
use App\Models\User;
use App\Helpers\TradeModification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;


class TradeController extends Controller
{
    public function show(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => 'required|exists:users,id'
            ],
            [
                'required' => 'The :attribute field is required',
                'exists' => 'The :attribute must exist in table',
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'status' => 'Validation error',
                'message' => $validator->errors()->getMessages()
            ], 422);
        }
        $validated_input = $validator->validate();

        $user = User::find($validated_input['user_id']);
        $trades = $user->trades();
        if ($trades->count()) {
            return response()->json([
                'status' => 'Success',
                'data' => $trades,
            ]);
        }

        return response()->json([
            'status' => 'Fail',
            'message' => 'User has no trades',
        ]);
    }

    public function buy(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'price' => 'required|gt:0|regex:/^\d*(\.\d{2})?$/',
                'user_id' => 'required|exists:users,id',
                'ticker' => 'required',
                'num_shares' => 'required|int|gt:0'
            ],
            [
                'required' => 'The :attribute field is required',
                'gt' => 'The :attribute field must be greater than 0',

            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'Validation error',
                'message' => $validator->errors()->getMessages()
            ], 422);
        }

        $validated_input = $validator->validate();

        $trade = new Trade;
        $trade->user_id = $validated_input['user_id'];
        $trade->trade_type = 'buy';
        $trade->ticker = strtoupper($validated_input['ticker']);
        $trade->price = $validated_input['price'];
        $trade->num_shares = $validated_input['num_shares'];
        $trade->save();

        $holding = $trade->holding;

        if ($holding == null) {
            $holding = new Holding;
            $holding->user_id = $trade->user_id;
            $holding->ticker = $trade->ticker;
            $holding->num_shares = $trade->num_shares;
            $holding->save();
        } else {
            $holding->num_shares += $trade->num_shares;
        }

        $holding->updateAvgBuyPrice();

        return response()->json([
            'result' => 'Added a buy trade'
        ]);
    }

    public function sell(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'price' => 'required|gt:0|regex:/^\d*(\.\d{2})?$/',
                'user_id' => 'required|exists:users,id',
                'ticker' => 'required',
                'num_shares' => 'required|int|gt:0'
            ],
            [
                'required' => 'The :attribute field is required',
                'gt' => 'The :attribute field must be greater than 0',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'Validation error',
                'message' => $validator->errors()->getMessages()
            ], 422);
        }

        $validated_input = $validator->validate();

        $holding = Holding::where([
            ['user_id', $validated_input['user_id']],
            ['ticker', $validated_input['ticker']]
        ])->first();

        if ($holding == null || ($holding->num_shares - $validated_input['num_shares']) < 0) {
            return response()->json([
                'result' => 'Cannot sell more than you own'
            ], 400);
        }

        $trade = new Trade;
        $trade->user_id = $validated_input['user_id'];
        $trade->trade_type = 'sell';
        $trade->ticker = $validated_input['ticker'];
        $trade->price = $validated_input['price'];
        $trade->num_shares = $validated_input['num_shares'];
        $trade->save();

        $holding->decreaseShares($trade);
        $holding->save();

        return response()->json([
            'result' => 'Successfully sold.'
        ]);
    }

    public function remove(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'trade_id' => 'required|exists:trades,id'
            ],
            [
                'required' => 'The :attribute field is required',
                'exists' => 'The :attribute must exist in table',
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'status' => 'Validation error',
                'message' => $validator->errors()->getMessages()
            ], 422);
        }
        $validated_input = $validator->validate();

        $trade = Trade::find($validated_input['trade_id']);
        if (empty($trade)) {
            return response()->json([
                'result' => 'Trade does not exist'
            ], 400);
        }

        $holding = $trade->currentOrPastHolding();

        $reverse_success = $holding->revertTrade($trade);
        if ($reverse_success) {
            return response()->json([
                'result' => 'Successfully removed trade_id'
            ]);
        }

        return response()->json([
            'result' => 'Removal will result in negative shareholding'
        ], 400);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trade_id' => 'required|exists:trades,id',
            'field' => 'required',
            'value' => 'required'
        ], []);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'Validation error',
                'message' => $validator->errors()->getMessages()
            ], 422);
        }

        $validated_input = $validator->validate();
        if (!Schema::hasColumn('trades', $validated_input['field'])) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Column does not exist in the table'
            ], 400);
        }

        $function_map = [
            'trade_type' => 'editTradeType',
            'num_shares' => 'editNumShares',
            'price' => 'editPrice',
            'ticker' => 'editTicker',
            'user_id' => 'editUserId'
        ];

        $func = $function_map[$validated_input['field']];

        $trade = Trade::find($validated_input['trade_id']);
        if ($trade == null) {
            return response()->json([
                'result' => 'Trade does not exist'
            ], 400);
        }
        $result = TradeModification::$func($trade, $validated_input['value']);

        if (!$result) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Update will result in negative shareholding'
            ], 400);
        }

        return response()->json([
            'status' => 'Success',
            'message' => 'Trade updated successfully'
        ], 400);
    }

    public function portfolio(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => 'required|exists:users,id'
            ],
            [
                'required' => 'The :attribute field is required',
                'exists' => 'The :attribute must exist in table',
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'status' => 'Validation error',
                'message' => $validator->errors()->getMessages()
            ], 422);
        }
        $validated_input = $validator->validate();

        $user = User::find($validated_input['user_id']);
        $holdings = $user->holdings;
        if (count($holdings)) {
            return response()->json([
                'status' => 'Success',
                'portfolio' => $holdings->pluck('ticker', 'num_shares', 'avg_buy_price')
            ]);
        }

        return response()->json([
            'status' => 'Fail',
            'message' => 'User has no holdings'
        ]);
    }

    public function returns(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => 'required|exists:users,id'
            ],
            [
                'required' => 'The :attribute field is required',
                'exists' => 'The :attribute must exist in table',
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'status' => 'Validation error',
                'message' => $validator->errors()->getMessages()
            ], 422);
        }
        $validated_input = $validator->validate();

        $returns = 0;
        $user = User::find($validated_input['user_id']);
        $holdings = $user->holdings;

        if (!count($holdings)) {
            return response()->json([
                'status' => 'Failed',
                'info' => 'User has no holdings'
            ], 400);
        }

        foreach ($holdings as $holding) {
            $returns += (Config::get('constants.current_price') - $holding->avg_buy_price) * $holding->num_shares;
        }

        $returns = round($returns, 2);

        return response()->json([
            'status' => 'Success',
            'message' => $returns
        ]);
    }
}
