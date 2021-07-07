<?php

namespace Tests\Feature\API\Transactions;

use Tests\TestCase;
use App\Models\User;
use App\Models\Loan;
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoanRequestTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    private User $user;

    public function setUpFaker()
    {
        $this->faker = $this->makeFaker();

        $this->user = User::create(
            [
                'name' => $this->faker->name,
                'email' => $this->faker->email,
                'password' => bcrypt($this->faker->password),
            ]
        );
    }

    /**
     * A feature test to check if guest cannot request for a loan.
     *
     * @return void
     */
    public function test_guest_loan_request_fails()
    {
        $payload = [
            'amount' => $this->faker->numberBetween(100, 10000),
            'duration' => $this->faker->numberBetween(11, 400),
        ];

        $this->json('post', 'api/loan/apply', $payload)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson(["message" => "Unauthenticated."]);

        $this->assertDatabaseMissing('loans', $payload);
    }

    /**
     * A feature test to check if missing amount fails.
     *
     * @return void
     */
    public function test_missing_amount_fails()
    {
        $payload = [
            'duration' => $this->faker->numberBetween(11, 400),
        ];

        $this->actingAs($this->user, 'api')
            ->json('post', 'api/loan/apply', $payload)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn(AssertableJson $json) => $json->has('meta')
                    ->has(
                        'error.0',
                        fn($json) => $json->where('key', "amount")
                            ->where('message', 'The amount field is required.')
                            ->etc()
                    )
            );
    }

    /**
     * A feature test to check if incorrect amount data type fails.
     *
     * @return void
     */
    public function test_incorrect_amount_data_type_fails()
    {
        $payload = [
            'amount' => $this->faker->word,
            'duration' => $this->faker->numberBetween(11, 400),
        ];

        $this->actingAs($this->user, 'api')
            ->json('post', 'api/loan/apply', $payload)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn(AssertableJson $json) => $json->has('meta')
                    ->has(
                        'error.0',
                        fn($json) => $json->where('key', "amount")
                            ->where('message', 'The amount must be a number.')
                            ->etc()
                    )
            );
    }

    /**
     * A feature test to check if incorrect amount value fails.
     *
     * @return void
     */
    public function test_incorrect_amount_value_fails()
    {
        $payload = [
            'amount' => $this->faker->numberBetween(0, 99),
            'duration' => $this->faker->numberBetween(11, 400),
        ];

        $this->actingAs($this->user, 'api')
            ->json('post', 'api/loan/apply', $payload)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn(AssertableJson $json) => $json->has('meta')
                    ->has(
                        'error.0',
                        fn($json) => $json->where('key', "amount")
                            ->where('message', 'Requested loan amount must exceed $99.')
                            ->etc()
                    )
            );
    }

    /**
     * A feature test to check if missing duration fails.
     *
     * @return void
     */
    public function test_missing_duration_fails()
    {
        $payload = [
            'amount' => $this->faker->numberBetween(100, 10000),
        ];

        $this->actingAs($this->user, 'api')
            ->json('post', 'api/loan/apply', $payload)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn(AssertableJson $json) => $json->has('meta')
                    ->has(
                        'error.0',
                        fn($json) => $json->where('key', "duration")
                            ->where('message', 'The duration field is required.')
                            ->etc()
                    )
            );
    }

    /**
     * A feature test to check if incorrect duration data type fails.
     *
     * @return void
     */
    public function test_incorrect_duration_data_type_fails()
    {
        $payload = [
            'amount' => $this->faker->numberBetween(100, 10000),
            'duration' => $this->faker->word,
        ];

        $this->actingAs($this->user, 'api')
            ->json('post', 'api/loan/apply', $payload)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn(AssertableJson $json) => $json->has('meta')
                    ->has(
                        'error.0',
                        fn($json) => $json->where('key', "duration")
                            ->where('message', 'The duration must be a number.')
                            ->etc()
                    )
            );
    }

    /**
     * A feature test to check if incorrect duration value fails.
     *
     * @return void
     */
    public function test_incorrect_duration_value_fails()
    {
        $payload = [
            'amount' => $this->faker->numberBetween(100, 10000),
            'duration' => $this->faker->numberBetween(0, 11),
        ];

        $this->actingAs($this->user, 'api')
            ->json('post', 'api/loan/apply', $payload)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn(AssertableJson $json) => $json->has('meta')
                    ->has(
                        'error.0',
                        fn($json) => $json->where('key', "duration")
                            ->where('message', 'Requested loan duration must exceed 11 Weeks.')
                            ->etc()
                    )
            );
    }

    /**
     * A feature test to check if request gets rejected
     * for any existing dues on earlier loan.
     *
     * @return void
     */
    public function test_existing_loan_due_hence_fails()
    {
        // Existing loan
        $data = [
            'amount' => $this->faker->numberBetween(100, 10000),
            'duration' => $this->faker->numberBetween(11, 400),
            'user_id' => $this->user->id,
            'interest_rate' => config('app.interest_rate'),
            'other_charges' => 250,
        ];
        $data['calculated_interest'] = (config('app.interest_rate') / 100) * $data['amount'] * $data['duration'];
        $data['total_amount'] = $data['amount'] + $data['calculated_interest'] + $data['other_charges'];

        Loan::create($data);

        $payload = [
            'amount' => $this->faker->numberBetween(100, 10000),
            'duration' => $this->faker->numberBetween(11, 400),
        ];

        $this->actingAs($this->user, 'api')
            ->json('post', 'api/loan/apply', $payload)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn(AssertableJson $json) => $json->has('meta')
                    ->has(
                        'error.0',
                        fn($json) => $json->where('key', "loan_dues")
                            ->where('message', 'Please clear your existing dues to apply for next loan.')
                            ->etc()
                    )
            );
    }

    /**
     * A feature test to check if user can successfully request for a loan.
     *
     * @return void
     */
    public function test_loan_requested_succeeds()
    {
        $payload = [
            'amount' => $this->faker->numberBetween(100, 10000),
            'duration' => $this->faker->numberBetween(11, 400),
        ];

        $this->actingAs($this->user, 'api')
            ->json('post', 'api/loan/apply', $payload)
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(
                fn(AssertableJson $json) => $json->has('meta')
                    ->where('data.message', 'Loan has been applied successfully!')
                    ->etc()
            );

        $payload['status'] = true;
        $this->assertDatabaseHas('loans', $payload);
    }
}
