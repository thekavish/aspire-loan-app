<?php

namespace Tests\Feature\API\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserLoginTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    private User $user;

    private string $password;

    public function setUpFaker()
    {
        $this->faker = $this->makeFaker();

        $this->password = $this->faker->password;
        $this->user = User::create(
            [
                'name' => $this->faker->name,
                'email' => $this->faker->email,
                'password' => bcrypt($this->password),
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
            'password' => $this->faker->password,
        ];

        $this->json('post', 'api/login', $payload)
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
    public function test_email_exists()
    {
        $this->assertDatabaseHas('users', ['email' => $this->user->email]);
    }

    /**
     * A feature test to check unique email validation.
     *
     * @return void
     */
    public function test_email_exists_validation()
    {
        $payload = [
            'email' => $this->faker->email,
            'password' => $this->password,
        ];

        $this->json('post', 'api/login', $payload)
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
                            "message" => "The selected email is invalid.",
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
            'email' => $this->user->email,
        ];
        $this->json('post', 'api/login', $payload)
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
     * A feature test to check if user can sign up successfully.
     *
     * @return void
     */
    public function test_user_login_success()
    {
        $payload = [
            'email' => $this->user->email,
            'password' => $this->password,
        ];

        $this->json('post', 'api/login', $payload)
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
    }

    /**
     * A feature test to check if user can sign up successfully.
     *
     * @return void
     */
    public function test_user_login_failure()
    {
        $payload = [
            'email' => $this->user->email,
            'password' => $this->faker->password,
        ];

        $this->json('post', 'api/login', $payload)
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertExactJson(
                [
                    "meta" => [
                        "status" => "FAILED",
                        "status_code" => 404,
                        "current_page" => 1,
                        "total_page" => 1,
                    ],
                    "error" => [
                        [
                            "key" => "message",
                            "message" => "These credentials do not match our records.",
                        ],
                    ],
                ]
            );
    }
}
