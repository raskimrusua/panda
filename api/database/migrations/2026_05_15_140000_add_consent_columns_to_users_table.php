<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flat consent fast-check columns on users. The full audit trail lives in
 * user_consents (next migration); these columns are the hot path the
 * ConsentGate middleware reads on every authenticated request.
 *
 * All four are nullable so existing users — created before the consent gate
 * existed — keep working until they next hit a non-whitelisted endpoint, at
 * which point the middleware emits 409 TERMS_VERSION_OUTDATED and the PWA
 * routes them to /accept-terms.
 *
 * Kenya DPA 2019 §30 places the burden of proof of lawful basis on the
 * controller; the version string captures *which* policy text the user
 * accepted so future amendments are auditable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('terms_accepted_at')->nullable()->after('is_superuser');
            $table->string('terms_version', 32)->default('')->after('terms_accepted_at');
            $table->timestamp('privacy_accepted_at')->nullable()->after('terms_version');
            $table->string('privacy_version', 32)->default('')->after('privacy_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'terms_accepted_at',
                'terms_version',
                'privacy_accepted_at',
                'privacy_version',
            ]);
        });
    }
};
