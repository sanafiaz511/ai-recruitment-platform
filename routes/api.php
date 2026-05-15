<?php

use App\Http\Controllers\API\ApplicationController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\JobController;
use App\Http\Controllers\API\CompanyController;
use App\Http\Controllers\API\ResumeController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->get('/profile', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/jobs', [JobController::class, 'index']);
    Route::post('/jobs', [JobController::class, 'store']);

    Route::post('/companies', [CompanyController::class, 'store']);
    Route::get('/my-company', [CompanyController::class, 'myCompany']);
    Route::put('/companies', [CompanyController::class, 'update']);
    
    Route::post('/apply', [ApplicationController::class, 'apply']);
    Route::get('/my-applications', [ApplicationController::class, 'myApplications']);
    Route::get('/jobs/{id}/applications', [ApplicationController::class, 'jobApplicants']);
    Route::patch('/applications/{id}/status', [ApplicationController::class, 'updateStatus']);

    Route::post('/resume/upload', [ResumeController::class, 'upload']);
    Route::get('/resume', [ResumeController::class, 'myResume']);
});

