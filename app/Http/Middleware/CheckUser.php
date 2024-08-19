<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Carbon\Carbon;

class CheckUser
{
    public function handle($request, Closure $next)
    {
        $authorization = $request->header('Authorization');

        if (empty($authorization)) {
            return response()->json([
                'code' => 401,
                'message' => 'Authentication failed'
            ], 401);
        }

        $access_token = trim(ltrim($authorization, 'Bearer '));

        // Use Eloquent to query the user
        $user = User::where('accessToken', $access_token)
            ->select('id', 'avatar', 'name', 'token', 'type', 'accessToken', 'expireDate')
            ->first();

        if (empty($user)) {
            return response()->json([
                'code' => 401,
                'message' => 'User does not exist'
            ], 401);
        }

        if (empty($user->expireDate)) {
            return response()->json([
                'code' => 401,
                'message' => 'You must login again'
            ], 401);
        }

        if ($user->expireDate < Carbon::now()) {
            return response()->json([
                'code' => 401,
                'message' => 'Your token has expired. You must login again'
            ], 401);
        }

        $addTime = Carbon::now()->addDays(5);
        if ($user->expireDate < $addTime) {
            $user->expireDate = Carbon::now()->addDays(30);
            $user->save();
        }

        // Add user information to the request
        $request->merge([
            'user_id' => $user->id,
            'user_type' => $user->type,
            'user_name' => $user->name,
            'user_avatar' => $user->avatar,
            'user_token' => $user->token,
        ]);

        return $next($request);
    }
}
