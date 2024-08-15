<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{

   

    public function login(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'avatar' => 'string|required',
                    'name' => 'string|required',
                    'type' => 'integer|required',
                    'openId' => 'string|required',
                    'email' => 'string|max:50',
                    'phone' => 'nullable|max:11'
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'code' => -1,
                    'data' => 'no valid data',
                    'msg' => $validator->errors()->first(),
                ]);
            }

            $validated = $validator->validated();

            // type နဲ့ openId ကို သတ်မှတ်ပြီး user ကို ရှာဖွေ (Select specific columns)
            $user = User::select('id', 'avatar', 'name', 'type', 'token', 'accessToken', 'online')
                ->where('type', $validated['type'])
                ->where('openId', $validated['openId'])
                ->first();

            if (!$user) {
                // user မရှိဘူးဆိုရင် အသစ်ထည့် (Create)
                $validated['token'] = md5(uniqid() . rand(10000, 99999));
                $validated['created_at'] = Carbon::now();
                $validated['accessToken'] = md5(uniqid() . rand(1000000, 9999999));
                $validated['expireDate'] = Carbon::now()->addDays(30);
                $validated['password'] = bcrypt('default_password'); // Add a default password

                try {
                    $user = User::create($validated); // Create operation
                    Log::info('New user created:', $user->toArray());

                    return response()->json([
                        'code' => 0,
                        'data' => $user,
                        'msg' => 'User has been created',
                    ]);
                } catch (Exception $e) {
                    Log::error('Inserting new user failed:', ['exception' => $e]);
                    return response()->json([
                        'code' => 500,
                        'data' => null,
                        'msg' => 'Failed to create user',
                    ], 500);
                }
            } else {
                // user ရှိပြီးသားဆိုရင် accessToken နဲ့ expireDate ကို update လုပ်မယ် (Update)
                $user->accessToken = md5(uniqid() . rand(1000000, 9999999));
                $user->expireDate = Carbon::now()->addDays(30);
                $user->save(); // Update operation

                Log::info('User updated with new accessToken and expireDate:', $user->toArray());

                return response()->json([
                    'code' => 1,
                    'data' => $user,
                    'msg' => 'User already exists and has been updated',
                ]);
            }
        } catch (Exception $e) {
            Log::error('Error in login method:', ['exception' => $e]);

            return response()->json([
                'code' => 500,
                'data' => null,
                'msg' => 'Internal Server Error' + $e,
            ], 500);
        }
    }

}
