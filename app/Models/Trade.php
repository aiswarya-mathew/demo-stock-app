<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Trade extends Model
{
    use HasFactory;
    use SoftDeletes;
    use \Awobaz\Compoships\Compoships;

    public function holding()
    {
        return $this->belongsTo(Holding::class, ['user_id', 'ticker'], ['user_id', 'ticker']);
    }

    public function currentOrPastHolding()
    {
        if ($this->holding) return $this->holding;

        Holding::withTrashed()->where([
            ['user_id', $this->user_id],
            ['ticker', $this->ticker],
        ])->restore();

        $holding = Holding::where([
            ['user_id', $this->user_id],
            ['ticker', $this->ticker],
        ])->first();
        return $holding;
    }
}
