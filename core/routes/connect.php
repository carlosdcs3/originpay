<?php

use App\Http\Middleware\Connect\EnsureConnectEnabled;
use App\Http\Middleware\Connect\EnsureConnectSubscriptionActive;
use Illuminate\Support\Facades\Route;

Route::prefix('user/connect')->name('user.connect.')->middleware([
    'auth',
    'account.status.check',
    'verified',
    '2fa',
    'block.ip',
    EnsureConnectEnabled::class,
    EnsureConnectSubscriptionActive::class,
])->group(function () {
    Route::get('/', 'App\Http\Controllers\Merchant\Connect\ConnectDashboardController@index')->name('dashboard');
    Route::get('contacts', 'App\Http\Controllers\Merchant\Connect\ConnectContactController@index')->name('contacts.index');
    Route::get('contacts/create', 'App\Http\Controllers\Merchant\Connect\ConnectContactController@create')->name('contacts.create');
    Route::get('contacts/{id}/edit', 'App\Http\Controllers\Merchant\Connect\ConnectContactController@edit')->name('contacts.edit');
    Route::get('segments', 'App\Http\Controllers\Merchant\Connect\ConnectSegmentController@index')->name('segments.index');
    Route::get('segments/create', 'App\Http\Controllers\Merchant\Connect\ConnectSegmentController@create')->name('segments.create');
    Route::get('templates', 'App\Http\Controllers\Merchant\Connect\ConnectTemplateController@index')->name('templates.index');
    Route::get('campaigns', 'App\Http\Controllers\Merchant\Connect\ConnectCampaignController@index')->name('campaigns.index');
    Route::get('journeys', 'App\Http\Controllers\Merchant\Connect\ConnectJourneyController@index')->name('journeys.index');
    Route::get('providers', 'App\Http\Controllers\Merchant\Connect\ConnectProviderController@index')->name('providers.index');
    Route::get('analytics', 'App\Http\Controllers\Merchant\Connect\ConnectAnalyticsController@index')->name('analytics');
    Route::get('dlq', 'App\Http\Controllers\Merchant\Connect\ConnectDlqController@index')->name('dlq.index');
    Route::get('dlq/export', 'App\Http\Controllers\Merchant\Connect\ConnectDlqController@export')->name('dlq.export');
    Route::get('alerts', 'App\Http\Controllers\Merchant\Connect\ConnectAlertController@index')->name('alerts.index');
    Route::get('settings', 'App\Http\Controllers\Merchant\Connect\ConnectSettingController@index')->name('settings.index');
});
