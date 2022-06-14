<?php

namespace App\Helpers;

use App\Models\Trade;
use App\Models\Holding;
use App\Models\User;
use Illuminate\Support\Facades\Log;



class TradeModification
{

    public static function editTradeType($trade, $new_value)
    {
        if ($new_value == 'sell' && ($trade->holding->num_shares < (2 * $trade->num_shares))) {
            return false;
        }

        $trade->trade_type = $new_value;
        $trade->save();
        $holding  = $trade->currentOrPastHolding();
        $holding->recalibrate();
        return true;
    }

    public function editNumShares($trade, $new_value)
    {
        if ($trade->type == 'sell' && $trade->holding->num_shares < $new_value) {
            return false;
        }

        $trade->num_shares = $new_value;
        $trade->save();
        $trade->holding->recalibrate();
        return true;
    }

    public function editPrice($trade, $new_value)
    {
        $trade->price = $new_value;
        $trade->save();
        if ($trade->trade_type == 'buy') {
            $trade->holding->updateAvgBuyPrice();
        }
        return true;
    }

    public function editTicker($trade, $new_value)
    {
        $old_ticker = $trade->ticker;
        $trade->ticker = $new_value;
        $trade->save();
        $trade->holding->recalibrate();

        $old_ticker_holding = Holding::where([
            ['user_id' => $trade->user_id],
            ['ticker' => $old_ticker]
        ])->first();

        if ($old_ticker_holding != null) {
            $old_ticker_holding->recalibrate();
        }
        return true;
    }

    public function editUserId($trade, $new_value)
    {
        $old_user_id = $trade->user_id;
        $trade->ticker = $new_value;
        $trade->save();
        $trade->holding->recalibrate();

        $new_user = User::find($new_value);
        if (empty($new_user))
            return false;

        $old_user_holding = Holding::where([
            ['user_id' => $old_user_id],
            ['ticker' => $trade->ticker]
        ])->first();

        if ($old_user_holding != null) {
            $old_user_holding->recalibrate();
        }
        return true;
    }
}
