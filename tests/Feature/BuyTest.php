<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class BuyTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
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
                '/buy',
                [
                    'user_id' => 1,
                    'ticker' => 'TCS',
                    'price' => 150,
                    'num_shares' => -2
                ]
            );

        $response->assertStatus(422);
    }

    public function test_unauthenticated_buy()
    {
        $response = $this
            ->withHeaders([
                'Accept' => 'application/json',
            ])->post(
                '/buy',
                [
                    'user_id' => 1,
                    'ticker' => 'TCS',
                    'price' => 150,
                    'num_shares' => 1
                ]
            );
        $response->assertStatus(401);
    }

    public function test_acceptable_buy()
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
                '/buy',
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
