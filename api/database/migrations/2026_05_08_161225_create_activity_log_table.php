<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogTable extends Migration
{
    public function up()
    {
        Schema::connection(config('activitylog.database_connection'))->create(config('activitylog.table_name'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            // Panda models use ULIDs (skill-laravel-eloquent-model §1). Switch from
            // the default bigint morphs to string-compatible nullableUlidMorphs.
            // Postgres is strict about bigint vs string; SQLite is typeless (which is
            // why local tests passed but CI exposed it on PR #2).
            $table->nullableUlidMorphs('subject', 'subject');
            $table->nullableUlidMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->timestamps();
            $table->index('log_name');
        });
    }

    public function down()
    {
        Schema::connection(config('activitylog.database_connection'))->dropIfExists(config('activitylog.table_name'));
    }
}
