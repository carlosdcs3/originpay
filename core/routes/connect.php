<?php
use Illuminate\Support\Facades\Route;

// Appended to user.php or wrapped in a middleware group in web.php
Route::prefix('user/connect')->name('user.connect.')->middleware(['auth', 'account.status.check', 'verified', '2fa', 'block.ip'])->group(function () {
    Route::get('/', 'App\Http\Controllers\Merchant\Connect\ConnectDashboardController@index')->name('dashboard');
    Route::resource('contacts', 'App\Http\Controllers\Merchant\Connect\ConnectContactController');
    Route::resource('segments', 'App\Http\Controllers\Merchant\Connect\ConnectSegmentController');
    Route::post('segments/preview', 'App\Http\Controllers\Merchant\Connect\ConnectSegmentController@preview')->name('segments.preview');
    Route::resource('templates', 'App\Http\Controllers\Merchant\Connect\ConnectTemplateController');
    Route::resource('campaigns', 'App\Http\Controllers\Merchant\Connect\ConnectCampaignController');
    
    // Journeys
    Route::resource('journeys', 'App\Http\Controllers\Merchant\Connect\ConnectJourneyController');
    Route::post('journeys/{id}/publish', 'App\Http\Controllers\Merchant\Connect\ConnectJourneyController@publish')->name('journeys.publish');
    Route::get('journeys/{id}/builder', 'App\Http\Controllers\Merchant\Connect\ConnectJourneyController@builder')->name('journeys.builder');
    
    Route::resource('providers', 'App\Http\Controllers\Merchant\Connect\ConnectProviderController');
    Route::post('providers/{id}/test', 'App\Http\Controllers\Merchant\Connect\ConnectProviderController@testConnection')->name('providers.test');
    
    Route::get('analytics', 'App\Http\Controllers\Merchant\Connect\ConnectAnalyticsController@index')->name('analytics');
    
    Route::get('dlq', 'App\Http\Controllers\Merchant\Connect\ConnectDlqController@index')->name('dlq.index');
    Route::post('dlq/{id}/reprocess', 'App\Http\Controllers\Merchant\Connect\ConnectDlqController@reprocess')->name('dlq.reprocess');
    Route::get('dlq/export', 'App\Http\Controllers\Merchant\Connect\ConnectDlqController@export')->name('dlq.export');
    
    Route::resource('alerts', 'App\Http\Controllers\Merchant\Connect\ConnectAlertController');
    Route::get('settings', 'App\Http\Controllers\Merchant\Connect\ConnectSettingController@index')->name('settings.index');
});
