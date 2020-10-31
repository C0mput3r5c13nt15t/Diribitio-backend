<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateJWT;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where all of the accessable routes are defined and
| which middlewares protect them.
|
*/

Route::middleware(['api', 'jwt:students'])->group(function () {
    Route::get('/students/self', 'StudentsController@self')->name('students.self');
    Route::get('/students/exchange_requests', 'StudentsController@exchange_requests')->middleware('schedule:exchange,projects')->name('students.exchange_requests');

    Route::middleware(['verified:students'])->group(function () {
        Route::get('/students/friends', 'StudentsController@friends')->name('students.friends');
        Route::get('/students/project', 'StudentsController@project')->name('students.project');
        Route::get('/students/leaded_project', 'StudentsController@leaded_project')->name('students.leaded_project');
        Route::get('/students/exchange', 'StudentsController@exchange')->middleware('schedule:exchange,projects')->name('students.exchange');
        Route::get('/students/exchange/sender', 'StudentsController@exchange_sender')->middleware('schedule:exchange,projects')->name('students.exchange_sender');
        Route::get('/students/exchange/receiver', 'StudentsController@exchange_receiver')->middleware('schedule:exchange,projects')->name('students.exchange_receiver');

        Route::post('/students/id', 'StudentsController@id', function ($request) {})->middleware('schedule:begin,sort_students,exchange,projects')->name('students.id');
        Route::post('/students/store_project', 'StudentsController@store_project', function ($request) {} )->middleware('schedule:begin,control')->name('students.store_project');
        Route::post('/students/store_leaded_project_message', 'StudentsController@store_leaded_project_message', function ($request) {} )->middleware('schedule:begin,end')->name('students.store_leaded_project_message');
        Route::post('/students/store_exchange', 'StudentsController@store_exchange', function ($request) {} )->middleware('schedule:exchange,projects')->name('students.store_exchange');

        Route::put('/students/update_project', 'StudentsController@update_project', function ($request) {} )->middleware('schedule:begin,control')->name('students.update_project');
        Route::put('/students/touch_up_project', 'StudentsController@touch_up_project', function ($request) {} )->middleware('schedule:control,registration')->name('students.touch_up_project');
        Route::put('/students/promote_student/{id}', 'StudentsController@promote_student', function ($id) {} )->middleware('schedule:begin,control')->name('students.promote_student');
        Route::put('/students/suspend_student/{id}', 'StudentsController@suspend_student', function ($id) {} )->middleware('schedule:begin,control')->name('students.suspend_student');
        Route::put('/students/quit_assistant', 'StudentsController@quit_assistant' )->middleware('schedule:begin,control')->name('students.quit_assistant');
        Route::put('/students/confirm_exchange/{id}', 'StudentsController@confirm_exchange', function ($id) {} )->middleware('schedule:exchange,projects')->name('students.confirm_exchange');
        Route::put('/students/self_update', 'StudentsController@self_update', function ($request) {} )->middleware('schedule:registration,sort_students')->name('students.self_update');

        Route::delete('/students/destroy_leaded_project_message/{id}', 'StudentsController@destroy_leaded_project_message', function ($id) {} )->middleware('schedule:begin,end')->name('students.destroy_leaded_project_message');
        Route::delete('/students/destroy_exchange', 'StudentsController@destroy_exchange')->middleware('schedule:exchange,projects')->name('students.destroy_exchange');
    });
});

Route::middleware(['api', 'jwt:leaders'])->group(function () {
    Route::get('/leaders/self', 'LeadersController@self')->name('leaders.self');

    Route::middleware(['verified:leaders'])->group(function () {
        Route::get('/leaders/leaded_project', 'LeadersController@leaded_project')->name('leaders.leaded_project');

        Route::post('/leaders/store_project', 'LeadersController@store_project', function ($request) {} )->middleware('schedule:begin,control')->name('leaders.store_project');
        Route::post('/leaders/store_leaded_project_message', 'LeadersController@store_leaded_project_message', function ($request) {} )->middleware('schedule:begin,end')->name('leaders.store_leaded_project_message');

        Route::put('/leaders/update_project', 'LeadersController@update_project', function ($request) {} )->middleware('schedule:begin,control')->name('leaders.update_project');
        Route::put('/leaders/touch_up_project', 'LeadersController@touch_up_project', function ($request) {} )->middleware('schedule:control,registration')->name('leaders.touch_up_project');

        Route::delete('/leaders/destroy_leaded_project_message/{id}', 'LeadersController@destroy_leaded_project_message', function ($request, $id) {} )->middleware('schedule:begin,end')->name('leaders.destroy_leaded_project_message');
    });
});

Route::middleware(['api', 'jwt:admins'])->group(function () {
    Route::get('/admins/self', 'AdminsController@self')->name('admins.self');

    Route::middleware(['verified:admins'])->group(function () {
        Route::get('/admins/index_students', 'AdminsController@index_students')->name('admins.index_students');
        Route::post('/admins/search_index_students', 'AdminsController@search_index_students', function ($request) {} )->name('admins.search_index_students');
        Route::get('/admins/little_index_students', 'AdminsController@little_index_students')->name('admins.little_index_students');
        Route::get('/admins/index_leaders', 'AdminsController@index_leaders')->name('admins.index_leaders');
        Route::get('/admins/index_exchanges', 'AdminsController@index_exchanges')->name('admins.index_exchanges');
        Route::get('/admins/index_projects', 'AdminsController@index_projects')->name('admins.index_projects');

        Route::get('/admins/project/{id}', 'AdminsController@project', function ($id) {} )->name('admins.project');
        Route::get('/admins/exchange/{id}', 'AdminsController@exchange', function ($id) {} )->name('admins.exchange');

        Route::put('/admins/update_schedule', 'AdminsController@update_schedule', function ($request) {} )->name('admins.update_schedule'); // Middleware?
        Route::put('/admins/toogle_editable/{id}', 'AdminsController@toogle_editable', function ($request, $id) {} )->middleware('schedule:control,registration')->name('admins.toogle_editable');
        Route::put('/admins/toogle_authorized/{id}', 'AdminsController@toogle_authorized', function ($request, $id) {} )->middleware('schedule:control,registration')->name('admins.toogle_authorized');
        Route::put('/admins/accomplish_exchange/{id}', 'AdminsController@accomplish_exchange', function ($id) {} )->middleware('schedule:exchange,projects')->name('admins.accomplish_exchange');

        Route::delete('/admins/destroy_leader/{id}', 'AdminsController@destroy_leader', function ($id) {} )->middleware('schedule:control,registration')->name('admins.destroy_leader');
        Route::delete('/admins/destroy_project/{id}', 'AdminsController@destroy_project', function ($id) {} )->middleware('schedule:control,registration')->name('admins.destroy_project');
        Route::delete('/admins/destroy_exchange/{id}', 'AdminsController@destroy_exchange', function ($id) {} )->middleware('schedule:exchange,projects')->name('admins.destroy_exchange');

        Route::resource('sign_up_emails', 'SignUpEmailsController')->only([
            'index', 'store', 'destroy'
        ])->middleware('schedule:begin,sort_students');

        Route::post('/admins/create_sorting_proposal', 'SortStudentsController@create_sorting_proposal')->middleware('schedule:sort_students,exchange')->name('admins.create_sorting_proposal');
        Route::post('/admins/apply_sorting_proposal', 'SortStudentsController@apply_sorting_proposal')->middleware('schedule:sort_students,exchange')->name('admins.apply_sorting_proposal');
        Route::post('/admins/edit_sorting_proposal', 'SortStudentsController@edit_sorting_proposal', function ($request) {} )->middleware('schedule:sort_students,exchange')->name('admins.edit_sorting_proposal');
        Route::get('/admins/request_sorting_proposal', 'SortStudentsController@request_sorting_proposal')->middleware('schedule:sort_students,exchange')->name('admins.request_sorting_proposal');
    });
});

Route::post('students/register', 'StudentAuth\RegisterController@register', function ($request) {} )->middleware('schedule:begin,control')->name('students.register');
Route::post('leaders/register', 'LeaderAuth\RegisterController@register', function ($request) {} )->middleware('schedule:begin,control')->name('leaders.register');
Route::post('admins/register', 'AdminAuth\RegisterController@register', function ($request) {} )->middleware('schedule:begin,sort_students')->name('admins.register');

Route::middleware(['schedule:begin,end'])->group(function () {
    Route::post('students/login', 'StudentAuth\LoginController@login')->name('students.login');
    Route::post('leaders/login', 'LeaderAuth\LoginController@login')->name('leaders.login');
});

Route::post('admins/login', 'AdminAuth\LoginController@login')->name('admins.login');

Route::post('auth/refresh', 'Auth\RefreshController@refresh')->name('auth.refresh');

Route::post('auth/password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('auth.sendResetLinkEmail');
Route::post('auth/password/reset', 'Auth\ResetPasswordController@reset')->name('auth.reset');

Route::post('/students/email/resend', 'StudentAuth\EMailVerificationController@resend')->name('students.verification.resend');
Route::get('/students/email/verify/{id}/{hash}', 'StudentAuth\EMailVerificationController@verify')->name('students.verification.verify');

Route::post('/leaders/email/resend', 'LeaderAuth\EMailVerificationController@resend')->name('leaders.verification.resend');
Route::get('/leaders/email/verify/{id}/{hash}', 'LeaderAuth\EMailVerificationController@verify')->name('leaders.verification.verify');

Route::post('/admins/email/resend', 'AdminAuth\EMailVerificationController@resend')->name('admins.verification.resend');
Route::get('/admins/email/verify/{id}/{hash}', 'AdminAuth\EMailVerificationController@verify')->name('admins.verification.verify');

Route::middleware(['schedule:begin,end'])->group(function () {
    Route::post('students/logout', 'StudentAuth\LogoutController@logout')->name('students.logout');
    Route::post('leaders/logout', 'LeaderAuth\LogoutController@logout')->name('leaders.logout');
});

Route::post('admins/logout', 'AdminAuth\LogoutController@logout')->name('admins.logout');

Route::get('projects/show_little/{id}', 'ProjectsController@show_little', function ($id) {} )->name('projects.show_little');
Route::resource('projects', 'ProjectsController')->only([
    'show', 'index'
]);

Route::resource('schedule', 'ScheduleController')->only([
    'index', 'show'
]);
