<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Log;



class RemoveTest extends TestCase
{
    use DatabaseMigrations;

    public function test_unauthenticated_update()
    {
        $response = $this
            ->withHeaders([
                'Accept' => 'application/json',
            ])->delete(
                '/remove',
                [
                    'trade_id' => 1,
                    'field' => 'trade_type',
                    'value' => 'sell'

                ]
            );
        $response->assertStatus(401);
    }

    public function test_remove_buy_negative_shareholding()
    {
        $user = new User;
        $user->name = 'Joe';
        $user->email = 'joe@gmail.com';
        $user->password = '12345';
        $user->save();

        // Buy 1 share
        $response = $this->actingAs($user)
            ->withHeaders([
                'Accept' => 'application/json',
            ])->post(
                '/buy',
                [
                    'user_id' => $user->id,
                    'ticker' => 'TCS',
                    'price' => 150,
                    'num_shares' => 1
                ]
            );
        $trade = Trade::first();

        $response = $this->actingAs($user)
            ->withHeaders([
                'Accept' => 'application/json',
            ])->post(
                '/sell',
                [
                    'user_id' => $user->id,
                    'ticker' => 'TCS',
                    'price' => 150,
                    'num_shares' => 1
                ]
            );

        $response = $this->actingAs($user)
            ->withHeaders([
                'Accept' => 'application/json',
            ])->delete(
                '/remove',
                [
                    'trade_id' => $trade->id
                ]
            );
        $response->assertStatus(400);
    }

    public function test_remove_acceptable()
    {
        $user = new User;
        $user->name = 'Joe';
        $user->email = 'joe@gmail.com';
        $user->password = '12345';
        $user->save();

        // Buy 1 share
        $response = $this->actingAs($user)
            ->withHeaders([
                'Accept' => 'application/json',
            ])->post(
                '/buy',
                [
                    'user_id' => $user->id,
                    'ticker' => 'TCS',
                    'price' => 150,
                    'num_shares' => 1
                ]
            );
        $trade = Trade::first();

        $response = $this->actingAs($user)
            ->withHeaders([
                'Accept' => 'application/json',
            ])->delete(
                '/remove',
                [
                    'trade_id' => $trade->id
                ]
            );
        $response->assertStatus(200);
    }
}
