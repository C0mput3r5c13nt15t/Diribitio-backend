<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DbSetup extends Migration
{
    /**
     * Run the migrations for setting up the entire database and all of it's tables.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('role')->unique();
        });
        Schema::create('sign_up_emails', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->foreignId('created_by');
            $table->timestamps();
        });
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('user_name')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('first_name');
            $table->string('last_name');
            $table->smallInteger('grade');
            $table->string('letter', 1);
            $table->foreignId('exchange_id');
            $table->foreignId('first_friend');
            $table->foreignId('second_friend');
            $table->foreignId('third_friend');
            $table->foreignId('first_wish');
            $table->foreignId('second_wish');
            $table->foreignId('third_wish');
            $table->foreignId('project_id');
            $table->smallInteger('role');
            $table->timestamps();
        });
        Schema::create('leaders', function (Blueprint $table) {
            $table->id();
            $table->string('user_name')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignId('project_id');
            $table->timestamps();
        });
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('user_name')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->timestamps();
        });
        Schema::create('schedule', function (Blueprint $table) {
            $table->id();
            $table->date('begin', 0)->default(Carbon::now());
            $table->date('control', 0)->default(Carbon::now()->add(1, 'day'));
            $table->date('registration', 0)->default(Carbon::now()->add(2, 'day'));
            $table->date('sort_students', 0)->default(Carbon::now()->add(3, 'day'));
            $table->date('exchange', 0)->default(Carbon::now()->add(4, 'day'));
            $table->date('projects', 0)->default(Carbon::now()->add(5, 'day'));
            $table->date('end', 0)->default(Carbon::now()->add(6, 'day'));
            $table->timestamps();
        });
        Schema::create('exchanges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id');
            $table->foreignId('receiver_id');
            $table->boolean('confirmed')->default(0);
            $table->boolean('accomplished')->default(0);
            $table->timestamps();
        });
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->boolean('authorized');
            $table->boolean('editable')->default(false);
            $table->string('title')->unique();
            $table->string('image')->nullable()->default(null);
            $table->mediumText('descr');
            $table->string('leader_name');
            $table->foreignId('leader_id');
            $table->string('leader_type')->default('App\Leader');
            $table->unique(['leader_id', 'leader_type']);
            $table->integer('cost');
            $table->time('first_day_begin', 0);
            $table->time('first_day_end', 0);
            $table->time('second_day_begin', 0);
            $table->time('second_day_end', 0);
            $table->integer('min_grade');
            $table->integer('max_grade');
            $table->integer('min_participants');
            $table->integer('max_participants');
            $table->timestamps();
        });
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id');
            $table->mediumText('message');
            $table->string('sender_name');
            $table->timestamp('created_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
        });
        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamps();
        });
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('roles');
        Schema::dropIfExists('sign_up_tokens');
        Schema::dropIfExists('sign_up_emails');
        Schema::dropIfExists('students');
        Schema::dropIfExists('leaders');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('schedule');
        Schema::dropIfExists('exchanges');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('failed_jobs');
    }
}
