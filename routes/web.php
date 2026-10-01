<?php

use App\Http\Controllers\Auth\AcceptInvitationController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CRM\AiCopilotController;
use App\Http\Controllers\CRM\AutomationController;
use App\Http\Controllers\CRM\CampaignController;
use App\Http\Controllers\CRM\DashboardController;
use App\Http\Controllers\CRM\IntegrationController;
use App\Http\Controllers\CRM\MetaCapiController;
use App\Http\Controllers\CRM\LeadController;
use App\Http\Controllers\CRM\OpportunityController;
use App\Http\Controllers\CRM\ReportController;
use App\Http\Controllers\CRM\SearchController;
use App\Http\Controllers\CRM\SettingsController;
use App\Http\Controllers\CRM\TaskController;
use App\Http\Controllers\CRM\TeamController;
use App\Http\Controllers\CRM\UserController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\Super\DashboardController as SuperDashboardController;
use App\Http\Controllers\Super\OrganizationController as SuperOrganizationController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->homeRouteName());
    }

    return view('landing', [
        'appName' => config('crm.name', config('app.name')),
        'contactEmail' => config('crm.privacy_email'),
    ]);
})->name('home');

Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

Route::get('/contact-us', function () {
    return view('contact-us', [
        'appName' => config('crm.name', config('app.name')),
        'contactEmail' => config('crm.privacy_email'),
    ]);
})->name('contact-us');

Route::get('/Privacy-policies', function () {

    // dd(Hash::make('QW@456Ytn'));
    return view('privacy-policies', [
        'appName' => config('crm.name', config('app.name')),
        'contactEmail' => config('crm.privacy_email'),
    ]);
})->name('privacy-policies');

Route::get('/privacy-policy', [LegalController::class, 'privacy'])->name('privacy-policy');
Route::get('/terms', [LegalController::class, 'terms'])->name('terms');
Route::get('/data-deletion', [LegalController::class, 'dataDeletion'])->name('data-deletion');

// Public aliases so Meta dashboard URLs under /crm/ work without login.
// These must stay outside the authenticated crm. route group.
Route::get('/crm/privacy-policy', [LegalController::class, 'privacy']);
Route::get('/crm/terms', [LegalController::class, 'terms']);
Route::get('/crm/data-deletion', [LegalController::class, 'dataDeletion']);

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
    Route::get('/invitations/{token}', [AcceptInvitationController::class, 'show'])->name('invitation.accept');
    Route::post('/invitations/{token}', [AcceptInvitationController::class, 'store'])->name('invitation.accept.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'last.active', 'super.admin'])->prefix('super')->name('super.')->group(function () {
    Route::get('/', fn () => redirect()->route('super.dashboard'));
    Route::get('/dashboard', [SuperDashboardController::class, 'index'])->name('dashboard');
    Route::get('/organizations', [SuperOrganizationController::class, 'index'])->name('organizations.index');
    Route::get('/organizations/create', [SuperOrganizationController::class, 'create'])->name('organizations.create');
    Route::post('/organizations', [SuperOrganizationController::class, 'store'])->name('organizations.store');
    Route::get('/organizations/{organization}', [SuperOrganizationController::class, 'show'])->name('organizations.show');
    Route::put('/organizations/{organization}/status', [SuperOrganizationController::class, 'updateStatus'])->name('organizations.status');
});

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])->name('verification.send');
});

Route::middleware(['auth', 'last.active', 'organization.member'])->prefix('crm')->name('crm.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/create', [LeadController::class, 'create'])->name('leads.create');
    Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
    Route::get('/leads/duplicates', [LeadController::class, 'duplicates'])->name('leads.duplicates');
    Route::get('/leads/export', [LeadController::class, 'export'])->name('leads.export');
    Route::post('/leads/bulk', [LeadController::class, 'bulk'])->name('leads.bulk');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::get('/leads/{lead}/activity', [LeadController::class, 'activityShow'])->name('leads.activity');
    Route::get('/leads/{lead}/edit', [LeadController::class, 'edit'])->name('leads.edit');
    Route::put('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
    Route::post('/leads/{lead}/notes', [LeadController::class, 'note'])->name('leads.notes');
    Route::post('/leads/{lead}/activities', [LeadController::class, 'activity'])->name('leads.activities');
    Route::post('/leads/{lead}/merge', [LeadController::class, 'merge'])->name('leads.merge');

    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/campaigns/export', [CampaignController::class, 'export'])->name('campaigns.export');
    Route::post('/campaigns/{campaign}/assign', [CampaignController::class, 'assign'])->name('campaigns.assign');
    Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
    Route::get('/campaigns/{campaign}/edit', [CampaignController::class, 'edit'])->name('campaigns.edit');
    Route::put('/campaigns/{campaign}', [CampaignController::class, 'update'])->name('campaigns.update');
    Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');
    Route::get('/campaigns/{campaign}/import', [CampaignController::class, 'importForm'])->name('campaigns.import');
    Route::post('/campaigns/{campaign}/import', [CampaignController::class, 'import'])->name('campaigns.import.store');

    Route::get('/ai-copilot', [AiCopilotController::class, 'index'])->name('ai.index');
    Route::post('/ai-copilot/ask', [AiCopilotController::class, 'ask'])->name('ai.ask');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::get('/profile', [UserController::class, 'profile'])->name('settings.profile');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('settings.profile.update');

    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::put('/teams/{team}', [TeamController::class, 'update'])->name('teams.update');

    Route::get('/automations', [AutomationController::class, 'index'])->name('automations.index');
    Route::post('/automations', [AutomationController::class, 'store'])->name('automations.store');
    Route::put('/automations/{automation}', [AutomationController::class, 'update'])->name('automations.update');
    Route::delete('/automations/{automation}', [AutomationController::class, 'destroy'])->name('automations.destroy');

    Route::get('/integrations', [IntegrationController::class, 'index'])->name('integrations.index');
    Route::post('/integrations', [IntegrationController::class, 'store'])->name('integrations.store');
    Route::get('/integrations/meta/connect', [IntegrationController::class, 'connectMeta'])->name('integrations.meta.connect');
    Route::get('/integrations/meta/callback', [IntegrationController::class, 'callbackMeta'])->name('integrations.meta.callback');
    Route::get('/integrations/meta/businesses', [IntegrationController::class, 'businessesMeta'])->name('integrations.meta.businesses');
    Route::post('/integrations/meta/businesses', [IntegrationController::class, 'saveBusinessesMeta'])->name('integrations.meta.businesses.save');
    Route::post('/integrations/meta/sync', [IntegrationController::class, 'syncMeta'])->name('integrations.meta.sync');
    Route::post('/integrations/meta/disconnect', [IntegrationController::class, 'disconnectMeta'])->name('integrations.meta.disconnect');
    Route::get('/integrations/meta/capi', [MetaCapiController::class, 'edit'])->name('integrations.meta.capi');
    Route::post('/integrations/meta/capi', [MetaCapiController::class, 'update'])->name('integrations.meta.capi.update');
    Route::post('/integrations/meta/capi/test', [MetaCapiController::class, 'test'])->name('integrations.meta.capi.test');
    Route::get('/integrations/meta/capi/logs', [MetaCapiController::class, 'logs'])->name('integrations.meta.capi.logs');
    Route::post('/integrations/meta/capi/logs/{metaCapiEvent}/retry', [MetaCapiController::class, 'retry'])->name('integrations.meta.capi.retry');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/organization', [SettingsController::class, 'updateOrganization'])->name('settings.organization');
    Route::post('/settings/pipelines', [SettingsController::class, 'storePipeline'])->name('settings.pipelines');
    Route::post('/settings/pipelines/{pipeline}/stages', [SettingsController::class, 'storeStage'])->name('settings.stages');
    Route::post('/settings/fields', [SettingsController::class, 'storeField'])->name('settings.fields');
    Route::post('/settings/tags', [SettingsController::class, 'storeTag'])->name('settings.tags');
    Route::put('/settings/roles/{role}', [SettingsController::class, 'updateRole'])->name('settings.roles');
    Route::put('/settings/duplicates', [SettingsController::class, 'updateDuplicates'])->name('settings.duplicates');
    Route::put('/settings/routing', [SettingsController::class, 'updateRouting'])->name('settings.routing');
    Route::put('/settings/automations/{automation}/toggle', [SettingsController::class, 'toggleAutomation'])->name('settings.automations.toggle');

    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
    Route::post('/opportunities', [OpportunityController::class, 'store'])->name('opportunities.store');
    Route::put('/opportunities/{opportunity}', [OpportunityController::class, 'update'])->name('opportunities.update');
});
