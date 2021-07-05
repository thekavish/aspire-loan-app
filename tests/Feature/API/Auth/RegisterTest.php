<?php

namespace Tests\Feature\API\Auth;

use Tests\TestCase;
use Illuminate\Http\Response;

class RegisterTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_user_is_created_successfully()
    {
        $faker = \Faker\Factory::create();
        $payload = [
            'name' => $faker->name,
            'email' => $faker->email,
            'password' => $faker->password,
        ];
        $this->json('post', 'api/signup', $payload)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(
                [
                    "meta" => [
                        "status",
                        "status_code",
                        "current_page",
                        "total_page",
                    ],
                    "data" => [
                        "message",
                        "token",
                    ],
                ]
            );

        unset($payload['password']);
        $this->assertDatabaseHas('users', $payload);
    }
}
