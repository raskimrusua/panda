<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new farm + farm owner. Tenant + first User are committed
     * in a single transaction so a half-finished signup never persists.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        [$user, $tenant] = DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'name' => $data['farm_name'],
                'slug' => $this->uniqueSlug($data['farm_name']),
                'county' => $data['county'],
                'sub_county' => $data['sub_county'] ?? null,
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            return [$user, $tenant];
        });

        $token = $user->createToken('first-device')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load('tenant')),
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        /** @var User|null $user */
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken($data['device_name'] ?? 'api')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load('tenant')),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('tenant'));
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 0;
        while (Tenant::where('slug', $slug)->exists()) {
            $i++;
            $slug = $base.'-'.Str::lower(Str::random(4));
            if ($i > 5) {
                $slug = $base.'-'.Str::ulid();
                break;
            }
        }

        return $slug;
    }
}
