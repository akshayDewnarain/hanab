<?php

use Application\API\Auth\Controllers\AccountSecurityController;
use Application\API\Auth\Controllers\PasswordPolicyController;
use Application\API\Auth\Controllers\PasswordResetController;
use Application\API\Auth\Controllers\PublicUserInvitationController;
use Application\API\Auth\Controllers\TwoFactorChallengeController;
use Application\API\Auth\Controllers\TwoFactorEnrollmentController;
use Application\API\Auth\Controllers\RolesController;
use Application\API\Auth\Controllers\SessionAuthController;
use Application\API\Auth\Controllers\UserInvitationsController;
use Application\API\Auth\Controllers\UsersController;
use Application\API\Auth\Resources\UserResource;
use Application\API\Dashboard\Controllers\DashboardActionsController;
use Application\API\Dashboard\Controllers\DashboardActivityController;
use Application\API\Dashboard\Controllers\PeopleOverviewController;
use Application\API\Employees\Controllers\Actions\DeleteEmployeeImageActionController;
use Application\API\Employees\Controllers\CertificatesController;
use Application\API\Employees\Controllers\EmployeeCertificatesController;
use Application\API\Employees\Controllers\EmployeeLocationsController;
use Application\API\Employees\Controllers\EmployeeRolesController;
use Application\API\Employees\Controllers\EmployeesController;
use Application\API\Employees\Controllers\EmployeeSkillsController;
use Application\API\Employees\Controllers\SkillsController;
use Application\API\Employees\Controllers\OrganizationLevelsController;
use Application\API\Employees\Controllers\OrganogramController;
use Application\API\HelpCenter\Controllers\HelpArticleController;
use Application\API\HelpCenter\Controllers\HelpCatalogController;
use Application\API\HelpCenter\Controllers\HelpCategoryController;
use Application\API\HelpCenter\Controllers\HelpMediaController;
use Application\API\KnowledgeBase\Controllers\KnowledgeArticleController;
use Application\API\KnowledgeBase\Controllers\KnowledgeAttachmentController;
use Application\API\KnowledgeBase\Controllers\KnowledgeCatalogController;
use Application\API\KnowledgeBase\Controllers\KnowledgeCategoryController;
use Application\API\Messages\Controllers\MessagesController;
use Application\API\Support\Controllers\TransfersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return new UserResource($request->user()?->load('roles'));
})->middleware(['auth:sanctum', 'security.complete']);

Route::post('/login', [SessionAuthController::class, 'login'])
    ->middleware(\Illuminate\Session\Middleware\StartSession::class)
    ->name('login');

Route::post('/logout', [SessionAuthController::class, 'logout'])
    ->middleware([\Illuminate\Session\Middleware\StartSession::class, 'auth:sanctum'])
    ->name('logout');

Route::prefix('v1/public')
    ->name('v1.public.')
    ->group(function () {
        Route::post('user-invitations/preview', [PublicUserInvitationController::class, 'preview'])
            ->middleware('throttle:invitation-preview')
            ->name('user-invitations.preview');
        Route::post('user-invitations/accept', [PublicUserInvitationController::class, 'accept'])
            ->middleware('throttle:invitation-accept')
            ->name('user-invitations.accept');
        Route::post('forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])
            ->middleware('throttle:password-forgot')
            ->name('forgot-password');
        Route::post('reset-password', [PasswordResetController::class, 'reset'])
            ->middleware('throttle:password-reset')
            ->name('reset-password');
        Route::get('password-policy', [PasswordPolicyController::class, 'show'])
            ->name('password-policy');
        Route::post('two-factor/challenge', [TwoFactorChallengeController::class, 'store'])
            ->middleware([\Illuminate\Session\Middleware\StartSession::class, 'throttle:mfa-challenge'])
            ->name('two-factor.challenge');
    });

Route::prefix('v1/security')
    ->middleware(['auth:sanctum', \Illuminate\Session\Middleware\StartSession::class])
    ->name('v1.security.')
    ->group(function () {
        Route::post('two-factor', [TwoFactorEnrollmentController::class, 'start'])->name('two-factor.start');
        Route::post('two-factor/confirm', [TwoFactorEnrollmentController::class, 'confirm'])->name('two-factor.confirm');
        Route::post('two-factor/acknowledge', [TwoFactorEnrollmentController::class, 'acknowledge'])->name('two-factor.acknowledge');
    });

Route::prefix('v1')
    ->middleware(['auth:sanctum', 'security.complete'])
    ->name('v1.')
    ->group(function() {
        Route::get('dashboard/people-overview', [PeopleOverviewController::class, 'show'])->name('dashboard.people-overview');
        Route::get('dashboard/actions', [DashboardActionsController::class, 'index'])->name('dashboard.actions');
        Route::get('dashboard/activity', [DashboardActivityController::class, 'index'])->name('dashboard.activity');

        Route::get('organogram', [OrganogramController::class, 'show'])->name('organogram');

        Route::get('knowledge', [KnowledgeCatalogController::class, 'home'])->name('knowledge.home');
        Route::get('knowledge/search', [KnowledgeCatalogController::class, 'search'])->name('knowledge.search');
        Route::get('knowledge/articles/slug/{slug}', [KnowledgeCatalogController::class, 'show'])->name('knowledge.articles.show');
        Route::get('knowledge/maintenance', [KnowledgeArticleController::class, 'maintenance'])->name('knowledge.maintenance');
        Route::get('knowledge/articles', [KnowledgeArticleController::class, 'index'])->name('knowledge.articles.index');
        Route::get('knowledge/articles/{article}', [KnowledgeArticleController::class, 'show'])->name('knowledge.articles.edit-show');
        Route::post('knowledge/articles', [KnowledgeArticleController::class, 'store'])->name('knowledge.articles.store');
        Route::put('knowledge/articles/{article}', [KnowledgeArticleController::class, 'update'])->name('knowledge.articles.update');
        Route::post('knowledge/articles/{article}/publish', [KnowledgeArticleController::class, 'publish'])->name('knowledge.articles.publish');
        Route::post('knowledge/articles/{article}/archive', [KnowledgeArticleController::class, 'archive'])->name('knowledge.articles.archive');
        Route::delete('knowledge/articles/{article}', [KnowledgeArticleController::class, 'destroy'])->name('knowledge.articles.destroy');
        Route::get('knowledge/articles/{article}/revisions', [KnowledgeArticleController::class, 'revisions'])->name('knowledge.articles.revisions');
        Route::get('knowledge/articles/{article}/revisions/{number}', [KnowledgeArticleController::class, 'revision'])->whereNumber('number')->name('knowledge.articles.revisions.show');
        Route::post('knowledge/articles/{article}/revisions/{number}/restore', [KnowledgeArticleController::class, 'restore'])->whereNumber('number')->name('knowledge.articles.revisions.restore');
        Route::post('knowledge/articles/{article}/feedback', [KnowledgeArticleController::class, 'feedback'])->name('knowledge.articles.feedback');
        Route::get('knowledge/categories', [KnowledgeCategoryController::class, 'index'])->name('knowledge.categories.index');
        Route::post('knowledge/categories', [KnowledgeCategoryController::class, 'store'])->name('knowledge.categories.store');
        Route::put('knowledge/categories/{category}', [KnowledgeCategoryController::class, 'update'])->name('knowledge.categories.update');
        Route::delete('knowledge/categories/{category}', [KnowledgeCategoryController::class, 'destroy'])->name('knowledge.categories.destroy');
        Route::post('knowledge/articles/{article}/attachments', [KnowledgeAttachmentController::class, 'store'])->name('knowledge.attachments.store');
        Route::get('knowledge/attachments/{attachment}', [KnowledgeAttachmentController::class, 'download'])->name('knowledge.attachments.download');
        Route::delete('knowledge/attachments/{attachment}', [KnowledgeAttachmentController::class, 'destroy'])->name('knowledge.attachments.destroy');

        Route::get('help', [HelpCatalogController::class, 'home'])->name('help.home');
        Route::get('help/contexts', [HelpCatalogController::class, 'contexts'])->name('help.contexts');
        Route::get('help/search', [HelpCatalogController::class, 'search'])->name('help.search');
        Route::get('help/context', [HelpCatalogController::class, 'context'])->name('help.context');
        Route::get('help/articles/slug/{slug}', [HelpCatalogController::class, 'show'])->name('help.articles.show');
        Route::get('help/articles', [HelpArticleController::class, 'index'])->name('help.articles.index');
        Route::get('help/articles/{article}', [HelpArticleController::class, 'show'])->name('help.articles.edit-show');
        Route::post('help/articles', [HelpArticleController::class, 'store'])->name('help.articles.store');
        Route::put('help/articles/{article}', [HelpArticleController::class, 'update'])->name('help.articles.update');
        Route::post('help/articles/{article}/publish', [HelpArticleController::class, 'publish'])->name('help.articles.publish');
        Route::post('help/articles/{article}/archive', [HelpArticleController::class, 'archive'])->name('help.articles.archive');
        Route::delete('help/articles/{article}', [HelpArticleController::class, 'destroy'])->name('help.articles.destroy');
        Route::get('help/articles/{article}/revisions', [HelpArticleController::class, 'revisions'])->name('help.articles.revisions');
        Route::post('help/articles/{article}/revisions/{number}/restore', [HelpArticleController::class, 'restore'])->whereNumber('number')->name('help.articles.revisions.restore');
        Route::post('help/articles/{article}/feedback', [HelpArticleController::class, 'feedback'])->name('help.articles.feedback');
        Route::get('help/categories', [HelpCategoryController::class, 'index'])->name('help.categories.index');
        Route::post('help/categories', [HelpCategoryController::class, 'store'])->name('help.categories.store');
        Route::put('help/categories/{category}', [HelpCategoryController::class, 'update'])->name('help.categories.update');
        Route::delete('help/categories/{category}', [HelpCategoryController::class, 'destroy'])->name('help.categories.destroy');
        Route::post('help/articles/{article}/screenshots', [HelpMediaController::class, 'store'])->name('help.screenshots.store');
        Route::get('help/screenshots/{attachment}', [HelpMediaController::class, 'download'])->name('help.screenshots.download');
        Route::delete('help/screenshots/{attachment}', [HelpMediaController::class, 'destroy'])->name('help.screenshots.destroy');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('messages', [MessagesController::class, 'index'])->name('messages.index');
            Route::post('messages', [MessagesController::class, 'store'])->name('messages.store');
            Route::put('messages/{message}', [MessagesController::class, 'update'])->name('messages.update');
            Route::delete('messages/{message}', [MessagesController::class, 'destroy'])->name('messages.destroy');
            Route::post('messages/{message}/publish', [MessagesController::class, 'publish'])->name('messages.publish');
            Route::put('messages/{message}/reaction', [MessagesController::class, 'reaction'])->name('messages.reaction');
            Route::get('message-preferences', [MessagesController::class, 'preferences'])->name('messages.preferences');
            Route::put('message-preferences', [MessagesController::class, 'updatePreferences'])->name('messages.preferences.update');
        });

        Route::get('transfers/models', [TransfersController::class, 'models'])->name('transfers.models');
        Route::get('transfers/{transferModel}/fields', [TransfersController::class, 'fields'])->name('transfers.fields');
        Route::get('transfers/{transferModel}/rows', [TransfersController::class, 'rows'])->name('transfers.rows');
        Route::post('transfers/{transferModel}/preview', [TransfersController::class, 'preview'])->name('transfers.preview');
        Route::post('transfers/{transferModel}/commit', [TransfersController::class, 'commit'])->name('transfers.commit');

        Route::get('expertise-search', [EmployeesController::class, 'expertiseSearch'])
            ->name('expertise-search');

        Route::get('employees/locations', [EmployeesController::class, 'locations'])->name('employees.locations');

        Route::apiResource('employees', EmployeesController::class)
            ->parameters([
                'employees' => 'employee',
            ]);
        Route::apiResourceImageActions('employees', 'employee', DeleteEmployeeImageActionController::class);

        Route::apiResource('employee-roles', EmployeeRolesController::class)
            ->parameters([
                'employee-roles' => 'employee_role',
            ]);

        Route::apiResource('organization-levels', OrganizationLevelsController::class)
            ->parameters([
                'organization-levels' => 'organization_level',
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

        Route::get('security/account', [AccountSecurityController::class, 'show'])->name('security.account');
        Route::put('security/password', [AccountSecurityController::class, 'updatePassword'])->name('security.password');
        Route::post('security/recovery-codes', [AccountSecurityController::class, 'regenerate'])->name('security.recovery-codes');
        Route::post('security/two-factor/replace', [AccountSecurityController::class, 'replace'])->name('security.two-factor.replace');
        Route::post('security/two-factor/replace/cancel', [AccountSecurityController::class, 'cancelReplacement'])->name('security.two-factor.replace-cancel');
        Route::post('security/two-factor/replace/confirm', [AccountSecurityController::class, 'confirmReplacement'])->name('security.two-factor.replace-confirm');
        Route::post('users/{user}/two-factor/reset', [AccountSecurityController::class, 'resetForUser'])->name('users.two-factor.reset');
        Route::apiResource('users', UsersController::class);
        Route::post('users/{user}/invitation', [UsersController::class, 'sendInvitation'])->name('users.invitation.send');
        Route::delete('users/{user}/invitation', [UsersController::class, 'revokeInvitation'])->name('users.invitation.revoke');
        Route::apiResource('roles', RolesController::class);
        Route::get('permissions', [RolesController::class, 'permissions'])->name('permissions.index');
        Route::apiResource('user-invitations', UserInvitationsController::class)->only(['index', 'show']);
        Route::post('user-invitations/{user_invitation}/resend', [UserInvitationsController::class, 'resend'])
            ->middleware('throttle:invitation-preview')
            ->name('user-invitations.resend');
        Route::post('user-invitations/{user_invitation}/revoke', [UserInvitationsController::class, 'revoke'])
            ->name('user-invitations.revoke');
    });
