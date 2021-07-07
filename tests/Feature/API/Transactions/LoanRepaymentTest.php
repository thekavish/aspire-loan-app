<?php

namespace Tests\Feature\API\Transactions;

use Tests\TestCase;
use App\Models\{User, Loan};
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoanRepaymentTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    private User $user;

    private Loan $loan;

    private $uri;

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

        $loan = [
            'amount' => $this->faker->randomFloat(2, 100, 1000),
            'duration' => $this->faker->numberBetween(11, 100),
            'user_id' => $this->user->id,
            'interest_rate' => config('app.interest_rate'),
            'other_charges' => 250,
            'total_amount_paid' => 0.0,
        ];
        $loan['calculated_interest'] = (config('app.interest_rate') / 100) * $loan['amount'] * $loan['duration'];
        $loan['total_amount'] = $loan['amount'] + $loan['calculated_interest'] + $loan['other_charges'];
        $this->loan = Loan::create($loan);

        $this->uri = __('api/loan/:id/repay', ['id' => $this->loan->id]);
    }

    /**
     * A feature test to check if missing amount fails.
     *
     * @return void
     */
    public function test_missing_amount_fails()
    {
        $this->actingAs($this->user, 'api')
            ->json('post', $this->uri, [])
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
        $payload = ['amount' => $this->faker->word];

        $this->actingAs($this->user, 'api')
            ->json('post', $this->uri, $payload)
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
     * A feature test to check if repayment attempt against paid loan fails.
     *
     * @return void
     */
    public function test_repayment_against_paid_loan_fails()
    {
        $this->loan->update(['status' => false]);
        $payload = [
            'amount' => $this->faker->randomNumber(2),
        ];

        $this->actingAs($this->user, 'api')
            ->json('post', $this->uri, $payload)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn(AssertableJson $json) => $json->has('meta')
                    ->has(
                        'error.0',
                        fn($json) => $json->where('key', "loan_dues")
                            ->where('message', 'This loan has already been paid in full.')
                            ->etc()
                    )
            );
    }

    /**
     * A feature test to check if incorrect amount (excess) value fails.
     *
     * @return void
     */
    public function test_excess_amount_value_fails()
    {
        $payload = [
            'amount' => $this->loan->remaining_amount + $this->faker->randomNumber(2),
            // an amount greater than loan payable
        ];

        $this->actingAs($this->user, 'api')
            ->json('post', $this->uri, $payload)
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson(
                fn(AssertableJson $json) => $json->has('meta')
                    ->has(
                        'error.0',
                        fn($json) => $json->where('key', "amount")
                            ->where(
                                'message',
                                'You have only ' . $this->loan->remaining_amount . ' as dues. Reattempt with exact remaining amount.'
                            )
                            ->etc()
                    )
            );
    }

    /**
     * A feature test to check if guest cannot request for a loan.
     *
     * @return void
     */
    public function test_guest_loan_repayment_fails()
    {
        $payload = [
            'amount' => $this->faker->randomFloat(2, 0, $this->loan->amount),
        ];

        $this->json('post', $this->uri, $payload)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson(["message" => "Unauthenticated."]);

        $this->assertDatabaseMissing('repayments', $payload);
    }

    /**
     * A feature test to check if user can repay for their loan.
     *
     * @return void
     */
    public function test_loan_repayment_succeeds()
    {
        $payload = [
            'amount' => $this->faker->randomFloat(2, 0, $this->loan->amount),
        ];

        $this->actingAs($this->user, 'api')
            ->json('post', $this->uri, $payload)
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(
                fn(AssertableJson $json) => $json->has('meta')
                    ->has('data.remaining')
                    ->where('data.message', 'Repayment made successfully!')
                    ->etc()
            );

        $this->assertDatabaseHas('repayments', $payload);
    }
}
