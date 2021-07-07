<?php

namespace App\Http\Controllers\API;

use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Kavish\APIResponse\HasApiResponse;
use Illuminate\Support\Facades\Validator;

class LoanController extends Controller
{
    use HasApiResponse;

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function apply(Request $request): JsonResponse
    {
        $validRequest = Validator::make(
            $request->all(),
            [
                'amount' => 'required|numeric|min:100',
                'duration' => 'required|numeric|min:12',
            ],
            [
                'amount.min' => 'Requested loan amount must exceed $99.',
                'duration.min' => 'Requested loan duration must exceed 11 Weeks.',
            ]
        );

        $user = auth()->user();

        $validRequest->after(
            function ($validRequest) use ($request, $user) {
                if (!!Loan::whereUserId($user->id)->whereStatus(true)->count()) {
                    $validRequest->errors()->add(
                        'loan_dues',
                        'Please clear your existing dues to apply for next loan.'
                    );
                }
            }
        );

        if ($validRequest->fails()) {
            return $this->result(false, [], $validRequest->errors()->messages(), Response::HTTP_FORBIDDEN);
        }

        $data['user_id'] = $user->id;
        $data['amount'] = $request->amount;
        $data['duration'] = $request->duration;
        $data['interest_rate'] = config('app.interest_rate');
        $data['calculated_interest'] = $request->amount * $request->duration * config('app.interest_rate') / 100;
        $data['other_charges'] = 250;
        $data['total_amount'] = $data['amount'] + $data['calculated_interest'] + $data['other_charges'];

        Loan::create($data);
        $message = 'Loan has been applied successfully!';

        return $this->result(true, compact('message'));
    }

    /**
     * @param Request $request
     * @param Loan    $loan
     *
     * @return JsonResponse
     */
    public function repay(Request $request, Loan $loan)
    {
        $validRequest = Validator::make($request->all(), ['amount' => 'required']);

        $validRequest->after(
            function ($validRequest) use ($loan, $request) {
                if (!$loan->status) {
                    $validRequest->errors()->add(
                        'loan_dues',
                        'This loan has already been paid in full.'
                    );
                }
            }
        );

        if ($validRequest->fails()) {
            return $this->result(false, [], $validRequest->errors()->messages(), Response::HTTP_FORBIDDEN);
        }

        DB::beginTransaction();
        try {
            $data['amount'] = $request->amount;
            $data['amount_remaining'] = $loan->total_amount - ($loan->total_amount_paid + $data['amount']);

            if ($loan->remaining_amount < $data['amount']) {
                return $this->result(
                    false,
                    [],
                    ['amount' => 'You have only ' . $loan->remaining_amount . ' as dues. Reattempt with exact remaining amount.'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $updatable = ['total_amount_paid' => $loan->total_amount_paid + $data['amount']];
            if ($loan->remaining_amount === $data['amount']) $updatable['status'] = false;

            $loan->repayments()->create($data);
            $loan->update($updatable);
        } catch (\Exception $exception) {
            DB::rollBack();

            return $this->result(false, [], $exception, Response::HTTP_EXPECTATION_FAILED);
        }
        DB::commit();

        $message = 'Repayment made successfully!';
        $remaining = $loan->remaining_amount;

        return $this->result(true, compact('message', 'remaining'));
    }
}
