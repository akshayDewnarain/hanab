<?php

use Application\API\Auth\Controllers\SessionAuthController;
use Application\API\Auth\Resources\UserResource;
use Application\API\Employees\Controllers\Actions\DeleteEmployeeImageActionController;
use Application\API\Employees\Controllers\CertificatesController;
use Application\API\Employees\Controllers\EmployeeCertificatesController;
use Application\API\Employees\Controllers\EmployeeLocationsController;
use Application\API\Employees\Controllers\EmployeeRolesController;
use Application\API\Employees\Controllers\EmployeesController;
use Application\API\Employees\Controllers\EmployeeSkillsController;
use Application\API\Employees\Controllers\SkillsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return new UserResource($request->user()?->load('roles'));
})->middleware('auth:sanctum');

Route::post('/login', [SessionAuthController::class, 'login'])
    ->name('login');

Route::post('/logout', [SessionAuthController::class, 'logout'])
    ->name('logout');

Route::prefix('v1')
    ->name('v1.')
    ->group(function() {
        Route::apiResource('employees', EmployeesController::class)
            ->parameters([
                'employees' => 'employee',
            ]);
        Route::apiResourceImageActions('employees', 'employee', DeleteEmployeeImageActionController::class);

        Route::apiResource('employee-roles', EmployeeRolesController::class)
            ->parameters([
                'employee-roles' => 'employee_role',
            ]);

        Route::apiResource('employee-locations', EmployeeLocationsController::class)
            ->parameters([
                'employee-locations' => 'employee_location',
            ]);

        Route::apiResource('skills', SkillsController::class)
            ->parameters([
                'skills' => 'skill',
            ]);

        Route::apiResource('certificates', CertificatesController::class)
            ->parameters([
                'certificates' => 'certificate',
            ]);

        Route::apiResource('employee-skills', EmployeeSkillsController::class)
            ->parameters([
                'employee-skills' => 'employee_skill',
            ]);

        Route::apiResource('employee-certificates', EmployeeCertificatesController::class)
            ->parameters([
                'employee-certificates' => 'employee_certificate',
            ]);
    });
