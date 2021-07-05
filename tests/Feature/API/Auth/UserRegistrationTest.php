<?php

namespace Tests\Feature\API\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserRegistrationTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    /**
     * A feature test to check if user can sign up successfully.
     *
     * @return void
     */
    public function test_user_is_created_successfully()
    {
        $payload = [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'password' => $this->faker->password,
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

    /**
     * A feature test to check required name validation.
     *
     * @return void
     */
    public function test_name_required_validation()
    {
        $payload = [
            'email' => $this->faker->email,
            'password' => $this->faker->password,
        ];

        $this->json('post', 'api/signup', $payload)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertExactJson(
                [
                    "meta" => [
                        "status" => "FAILED",
                        "status_code" => 403,
                        "current_page" => 1,
                        "total_page" => 1,
                    ],
                    "error" => [
                        [
                            "key" => "name",
                            "message" => "The name field is required.",
                        ],
                    ],
                ]
            );
    }

    /**
     * A feature test to check required email validation.
     *
     * @return void
     */
    public function test_email_required_validation()
    {
        $payload = [
            'name' => $this->faker->name,
            'password' => $this->faker->password,
        ];

        $this->json('post', 'api/signup', $payload)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertExactJson(
                [
                    "meta" => [
                        "status" => "FAILED",
                        "status_code" => 403,
                        "current_page" => 1,
                        "total_page" => 1,
                    ],
                    "error" => [
                        [
                            "key" => "email",
                            "message" => "The email field is required.",
                        ],
                    ],
                ]
            );
    }

    /**
     * A feature test to check unique email validation.
     *
     * @return void
     */
    public function test_email_unique_validation()
    {
        $email = $this->faker->email;
        User::create(
            [
                'name' => $this->faker->name,
                'email' => $email,
                'password' => bcrypt($this->faker->password),
            ]
        );

        $payload = [
            'name' => $this->faker->name,
            'email' => $email,
            'password' => $this->faker->password,
        ];

        $this->json('post', 'api/signup', $payload)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertExactJson(
                [
                    "meta" => [
                        "status" => "FAILED",
                        "status_code" => 403,
                        "current_page" => 1,
                        "total_page" => 1,
                    ],
                    "error" => [
                        [
                            "key" => "email",
                            "message" => "The email has already been taken.",
                        ],
                    ],
                ]
            );
    }

    /**
     * A feature test to check required password validation.
     *
     * @return void
     */
    public function test_password_required_validation()
    {
        $payload = [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
        ];
        $this->json('post', 'api/signup', $payload)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertExactJson(
                [
                    "meta" => [
                        "status" => "FAILED",
                        "status_code" => 403,
                        "current_page" => 1,
                        "total_page" => 1,
                    ],
                    "error" => [
                        [
                            "key" => "password",
                            "message" => "The password field is required.",
                        ],
                    ],
                ]
            );
    }

    /**
     * A feature test to check password minimum length validation.
     *
     * @return void
     */
    public function test_password_minimum_length_validation()
    {
        $payload = [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'password' => substr($this->faker->word, 0, 4),
        ];
        $this->json('post', 'api/signup', $payload)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertExactJson(
                [
                    "meta" => [
                        "status" => "FAILED",
                        "status_code" => 403,
                        "current_page" => 1,
                        "total_page" => 1,
                    ],
                    "error" => [
                        [
                            "key" => "password",
                            "message" => "The password must be at least 6 characters.",
                        ],
                    ],
                ]
            );
    }
}
