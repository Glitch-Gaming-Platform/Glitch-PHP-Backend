<?php

namespace App\Http\Controllers;

use App\Facades\AuthenticationFacade;
use App\Http\Resources\UserFullResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {

        $input = $request->all();

        $user = new User();

        $valid = $user->validate($input);

        if(!$valid){
            return response()->json($user->getValidationErrors(), 422);
        }

        $input['password'] = Hash::make($input['password']);

        $user = User::create($input);

        $token = auth()->login($user);

        $user->token = $this->respondWithToken($token);

        $resource = UserFullResource::make($user);

        $resource['token'] = $this->respondWithToken($token);
        $resource['email'] = $user->email;
        
        return $resource;
    }

    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Invalid Login Credentials'], 401);
        }

        $input = $request->all();

        $user = User::where('email', $input['email'])->first();

        $user->token = $this->respondWithToken($token);

        $resource = UserFullResource::make($user);

        $resource['token'] = $this->respondWithToken($token);
        $resource['email'] = $user->email;

        return $resource;
    }

    public function oneTimeLoginToken(Request $request) {

        $input = $request->all();

        if(!isset($input['token'])) {
            return response()->json('No Token Supplied', 422);
        }

        $user = AuthenticationFacade::useOneTimeLoginToken($input['token']);

        if(!$user){
            return response()->json('Unable to authenticate with provided token', 422);
        }

        $token = auth()->login($user);

        $user->token = $this->respondWithToken($token);

        $resource = UserFullResource::make($user);

        $resource['token'] = $this->respondWithToken($token);
        $resource['email'] = $user->email;

        return $resource;

    }

    protected function respondWithToken($token)
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * env('JWT_TTL', 500)
        ];
    }
}
