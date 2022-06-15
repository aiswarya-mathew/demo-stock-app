<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;


class SellTest extends TestCase
{
    use DatabaseMigrations;
    public function test_negative_shares()
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
                '/sell',
                [
                    'user_id' => 1,
                    'ticker' => 'TCS',
                    'price' => 150,
                    'num_shares' => -2
                ]
            );

        $response->assertStatus(422);
    }

    public function test_unauthenticated_sell()
    {
        $response = $this
            ->withHeaders([
                'Accept' => 'application/json',
            ])->post(
                '/sell',
                [
                    'user_id' => 1,
                    'ticker' => 'TCS',
                    'price' => 150,
                    'num_shares' => 1
                ]
            );
        $response->assertStatus(401);
    }

    public function test_selling_shares_before_buying()
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
                '/sell',
                [
                    'user_id' => 1,
                    'ticker' => 'TCS',
                    'price' => 150,
                    'num_shares' => 1
                ]
            );

        $response->assertStatus(400);
    }


    public function test_acceptable_sell()
    {

        $user = new User;
        $user->name = 'Joe';
        $user->email = 'joe@gmail.com';
        $user->password = '12345';
        $user->save();

        // Buy 1 share
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
            ])->post(
                '/sell',
                [
                    'user_id' => $user->id,
                    'ticker' => 'TCS',
                    'price' => 150,
                    'num_shares' => 1
                ]
            );


        $response->assertStatus(200);
    }

    public function test_negative_price()
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
                '/sell',
                [
                    'user_id' => $user->id,
                    'ticker' => 'TCS',
                    'price' => -150,
                    'num_shares' => 1
                ]
            );

        $response->assertStatus(422);
    }
}
