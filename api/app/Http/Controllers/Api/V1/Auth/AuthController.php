<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserConsent;
use Carbon\Carbon;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Register a new farm + farm owner. Tenant + first User are committed
     * in a single transaction so a half-finished signup never persists.
     * A verification email is dispatched fire-and-forget; if delivery
     * fails the user can re-request via `sendVerification`.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Captured outside the transaction so Carbon::now() is single-valued
        // across both UserConsent rows + the flat User columns. IP + UA come
        // from the request — UA truncated to 2 KB so a malicious client can't
        // bloat the row.
        $now = Carbon::now();
        $ip = $request->ip();
        $ua = mb_substr((string) $request->userAgent(), 0, 2000);
        $termsV = (string) config('legal.terms_version');
        $privacyV = (string) config('legal.privacy_version');

        [$user, $tenant] = DB::transaction(function () use ($data, $now, $ip, $ua, $termsV, $privacyV) {
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
                // Flat fast-check columns — ConsentGate reads these per-request.
                'terms_accepted_at' => $now,
                'terms_version' => $termsV,
                'privacy_accepted_at' => $now,
                'privacy_version' => $privacyV,
            ]);

            // DPA 2019 §30 audit trail. One row per policy; the unique
            // constraint catches accidental double-submits from a flaky
            // PWA connection without breaking the transaction.
            UserConsent::create([
                'user_id' => $user->id,
                'policy_type' => UserConsent::POLICY_TERMS,
                'version' => $termsV,
                'ip_address' => $ip,
                'user_agent' => $ua,
                'accepted_at' => $now,
            ]);
            UserConsent::create([
                'user_id' => $user->id,
                'policy_type' => UserConsent::POLICY_PRIVACY,
                'version' => $privacyV,
                'ip_address' => $ip,
                'user_agent' => $ua,
                'accepted_at' => $now,
            ]);

            return [$user, $tenant];
        });

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);
        }

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

    /**
     * Re-send the verification email for the authenticated user.
     */
    public function sendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], Response::HTTP_NO_CONTENT);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent.']);
    }

    /**
     * Confirm an email verification link. Hit by the user via the link in
     * their inbox — the signature is validated by the `signed` middleware
     * on the route. On success the user is redirected to the PWA
     * `/verified` page; on failure to `/verified?error=invalid`.
     */
    public function verifyEmail(Request $request, string $id, string $hash): RedirectResponse
    {
        $frontend = rtrim((string) config('app.frontend_url'), '/');

        /** @var User|null $user */
        $user = User::find($id);

        if (! $user || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return redirect()->away("{$frontend}/verified?error=invalid");
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return redirect()->away("{$frontend}/verified");
    }

    /**
     * Trigger a password-reset email. Always responds 200 to prevent
     * email-enumeration — the message is identical whether the email
     * exists or not.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->validated());

        return response()->json([
            'message' => 'If the email exists, a reset link has been sent.',
        ]);
    }

    /**
     * Reset the password using a valid token (delivered to the user via
     * the reset email). Token + email are validated by Laravel's
     * PasswordBroker; mismatches return 422.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['message' => 'Password has been reset.']);
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
