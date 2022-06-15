<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Log;


class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    public function test_unauthenticated_update()
    {
        $response = $this
            ->withHeaders([
                'Accept' => 'application/json',
            ])->patch(
                '/update',
                [
                    'trade_id' => 1,
                    'field' => 'trade_type',
                    'value' => 'sell'

                ]
            );
        $response->assertStatus(401);
    }

    public function test_invalid_column()
    {
        $user = new User;
        $user->name = 'Joe';
        $user->email = 'joe@gmail.com';
        $user->password = '12345';
        $user->save();

        $this->actingAs($user)
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

        $response = $this->actingAs($user)
            ->withHeaders([
                'Accept' => 'application/json',
            ])->patch(
                '/update',
                [
                    'trade_id' => 1,
                    'field' => 'trade_person',
                    'value' => 'sell'
                ]
            );

        $response->assertStatus(400);
    }

    public function test_invalid_sell_update()
    {
        $user = new User;
        $user->name = 'Joe';
        $user->email = 'joe@gmail.com';
        $user->password = '12345';
        $user->save();

        $this->actingAs($user)
            ->withHeaders([
                'Accept' => 'application/json',
            ])->post(
                '/buy',
                [
                    'user_id' => $user->id,
                    'ticker' => 'TCS',
                    'price' => 150,
                    'num_shares' => 5
                ]
            );

        $response = $this->actingAs($user)
            ->withHeaders([
                'Accept' => 'application/json',
            ])->patch(
                '/update',
                [
                    'trade_id' => 1,
                    'field' => 'trade_type',
                    'value' => 'sell'
                ]
            );

        $response->assertStatus(400);
    }

    public function test_valid_sell_update()
    {
        $user = new User;
        $user->name = 'Joe';
        $user->email = 'joe@gmail.com';
        $user->password = '12345';
        $user->save();

        $response = $this->actingAs($user)
            ->withHeaders([
                'Accept' => 'application/json',
            ])->post(
                '/buy',
                [
                    'user_id' => $user->id,
                    'ticker' => 'TCS',
                    'price' => 150,
                    'num_shares' => 5
                ]
            );

        $response->assertStatus(200);

        $trade = Trade::first();


        $response = $this->actingAs($user)
            ->withHeaders([
                'Accept' => 'application/json',
            ])->post(
                '/buy',
                [
                    'user_id' => $user->id,
                    'ticker' => 'TCS',
                    'price' => 150,
                    'num_shares' => 5
                ]
            );

        $response->assertStatus(200);


        $response = $this->actingAs($user)
            ->withHeaders([
                'Accept' => 'application/json',
            ])->patch(
                '/update',
                [
                    'trade_id' => $trade->id,
                    'field' => 'trade_type',
                    'value' => 'sell'
                ]
            );
        $response->assertStatus(200);
    }
}
