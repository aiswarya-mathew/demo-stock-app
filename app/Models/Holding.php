<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Trade;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;


class Holding extends Model
{
    use HasFactory;
    use SoftDeletes;
    use \Awobaz\Compoships\Compoships;


    protected $fillable = ['user_id', 'ticker'];

    /*
    Calculate avg_buy_price based on trades 
    */
    public function updateAvgBuyPrice()
    {
        $trades = $this->trades->filter(function ($value, $key) {
            return $value->trade_type == 'buy';
        });
        $total_cost = 0;
        $total_shares = 0;
        foreach ($trades as $trade) {
            $total_cost += $trade->price * $trade->num_shares;
            $total_shares += $trade->num_shares;
        }

        $this->avg_buy_price = round((float) ($total_cost / $total_shares), 2);
        $this->save();
    }

    public function trades()
    {
        return $this->hasMany(Trade::class, ['user_id', 'ticker'], ['user_id', 'ticker']);
    }

    /*
    Return to the state before this trade was made
    */
    public function revertTrade($trade)
    {
        if ($trade->trade_type == 'buy') {
            if ($trade->num_shares > $this->num_shares) {
                return false;
            }
            $this->decreaseShares($trade);
            $trade->delete();
            if (!$this->trashed()) {
                $this->updateAvgBuyPrice();
            }
        } else {
            $this->increaseShares($trade);
            $trade->delete();
        }
        return true;
    }

    /*
    Decrease the shareholding based on this trade. 
    If no shares, delete holding
    */
    public function decreaseShares($trade)
    {
        if ($trade->num_shares > $this->num_shares) {
            return false;
        }

        if ($this->num_shares == $trade->num_shares) {
            $this->delete();
        }

        $this->num_shares -= $trade->num_shares;
        $this->save();
    }

    public function increaseShares($trade)
    {
        $this->num_shares += $trade->num_shares;
        $this->save();
    }

    /*
    Recalculate shares and avg_buy_price based on this holding's trades
    */
    public function recalibrate()
    {
        $bought = $this->trades->where('trade_type', '=', 'buy')->sum('num_shares');
        $sold = $this->trades->where('trade_type', '=', 'sell')->sum('num_shares');
        $this->num_shares = $bought - $sold;
        $this->save();
        if ($this->num_shares == 0) {
            $this->delete();
        } else {
            $this->updateAvgBuyPrice();
        }
    }

    /*
    Find user to whom this holding belongs 
    */
    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'user_id');
    }
}
