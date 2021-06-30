<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Kavish\APIResponse\HasApiResponse;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use HasApiResponse;

    /**
     * Register user and issue a PAT
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function signup(Request $request): JsonResponse
    {
        $validRequest = Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6',
            ]
        );

        if ($validRequest->fails()) {
            return $this->result(false, [], $validRequest->errors()->messages(), Response::HTTP_FORBIDDEN);
        }

        $data = $request->only(['name', 'email', 'password']);
        $data['password'] = bcrypt($data['password']);

        $user = User::create($data);
        $token = $user->createToken('mobile', ['*'])->plainTextToken;

        return $this->result(true, ['message' => 'Account created successfully!', 'token' => $token]);
    }

    /**
     * Authenticate user and issue a PAT
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validRequest = Validator::make(
            $request->all(),
            [
                'email' => 'required|email|exists:users',
                'password' => 'required',
            ]
        );

        if ($validRequest->fails()) {
            return $this->result(false, [], $validRequest->errors()->messages(), Response::HTTP_FORBIDDEN);
        }

        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            return $this->result(false, [], ['message' => __('auth.failed')], Response::HTTP_NOT_FOUND);
        }

        $user = Auth::user();
        $token = $user->createToken('mobile', ['*'])->plainTextToken;

        return $this->result(true, ['message' => 'Logged in successfully!', 'token' => $token]);
    }
}
