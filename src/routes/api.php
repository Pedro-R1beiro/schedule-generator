<?php

use App\Http\Controllers\Api\FixedPairController;
use App\Http\Controllers\Api\PublisherController;
use App\Http\Controllers\Api\PublisherPairPreferenceController;
use App\Http\Controllers\Api\PublisherPairRestrictionController;
use App\Http\Controllers\Api\TimeSlotController;
use App\Http\Controllers\Api\WeekdayController;
use App\Http\Controllers\Api\WeekdayTimeSlotController;
use Illuminate\Support\Facades\Route;

// Rotas para Time Slots
Route::prefix('time-slots')->group(function () {
    Route::get('/', [TimeSlotController::class, 'index'])->name('time-slots.index');
    Route::post('/', [TimeSlotController::class, 'store'])->name('time-slots.store');
    Route::get('/available', [TimeSlotController::class, 'available'])->name('time-slots.available');
    Route::get('/{id}', [TimeSlotController::class, 'show'])->name('time-slots.show');
    Route::put('/{id}', [TimeSlotController::class, 'update'])->name('time-slots.update');
    Route::patch('/{id}', [TimeSlotController::class, 'update'])->name('time-slots.update.patch');
    Route::delete('/{id}', [TimeSlotController::class, 'destroy'])->name('time-slots.destroy');
    Route::delete('/{id}/force', [TimeSlotController::class, 'forceDestroy'])->name('time-slots.force-destroy');
    Route::post('/{id}/activate', [TimeSlotController::class, 'activate'])->name('time-slots.activate');
    Route::post('/{id}/deactivate', [TimeSlotController::class, 'deactivate'])->name('time-slots.deactivate');
    Route::get('/{id}/check-delete', [TimeSlotController::class, 'checkDelete'])->name('time-slots.check-delete');
});

// Rotas para Weekdays (READ-ONLY)
Route::prefix('weekdays')->group(function () {
    Route::get('/', [WeekdayController::class, 'index'])->name('weekdays.index');
    Route::get('/active', [WeekdayController::class, 'active'])->name('weekdays.active');
    Route::get('/formatted', [WeekdayController::class, 'formatted'])->name('weekdays.formatted');
    Route::get('/with-counts', [WeekdayController::class, 'withCounts'])->name('weekdays.with-counts');
    Route::get('/by-name/{name}', [WeekdayController::class, 'byName'])->name('weekdays.by-name');
    Route::get('/{id}/check-used', [WeekdayController::class, 'checkUsed'])->name('weekdays.check-used');
    Route::get('/{id}', [WeekdayController::class, 'show'])->name('weekdays.show');
});

// Rotas para WeekdayTimeSlots
Route::prefix('weekday-time-slots')->group(function () {
    Route::get('/', [WeekdayTimeSlotController::class, 'index'])->name('weekday-time-slots.index');
    Route::post('/', [WeekdayTimeSlotController::class, 'store'])->name('weekday-time-slots.store');
    Route::get('/{id}', [WeekdayTimeSlotController::class, 'show'])->name('weekday-time-slots.show');
    Route::delete('/{id}', [WeekdayTimeSlotController::class, 'destroy'])->name('weekday-time-slots.destroy');
    
    // Rotas extras
    Route::get('/by-weekday/{weekdayId}', [WeekdayTimeSlotController::class, 'getByWeekday'])->name('weekday-time-slots.by-weekday');
    Route::get('/by-time-slot/{timeSlotId}', [WeekdayTimeSlotController::class, 'getByTimeSlot'])->name('weekday-time-slots.by-time-slot');
    Route::get('/available/{weekdayId}', [WeekdayTimeSlotController::class, 'getAvailableTimeSlots'])->name('weekday-time-slots.available');
    Route::get('/{id}/check-delete', [WeekdayTimeSlotController::class, 'checkDelete'])->name('weekday-time-slots.check-delete');
    Route::post('/check-pair', [WeekdayTimeSlotController::class, 'checkPair'])->name('weekday-time-slots.check-pair');
});

// Rotas para FixedPairs
Route::prefix('fixed-pairs')->group(function () {
    Route::get('/', [FixedPairController::class, 'index'])->name('fixed-pairs.index');
    Route::post('/', [FixedPairController::class, 'store'])->name('fixed-pairs.store');
    Route::get('/{id}', [FixedPairController::class, 'show'])->name('fixed-pairs.show');
    Route::put('/{id}', [FixedPairController::class, 'update'])->name('fixed-pairs.update');
    Route::patch('/{id}', [FixedPairController::class, 'update'])->name('fixed-pairs.update.patch');
    Route::delete('/{id}', [FixedPairController::class, 'destroy'])->name('fixed-pairs.destroy');
    
    // Rotas extras
    Route::get('/by-weekday-time-slot/{weekdayTimeSlotId}', [FixedPairController::class, 'getByWeekdayTimeSlot'])->name('fixed-pairs.by-weekday-time-slot');
    Route::get('/by-publisher/{publisherId}', [FixedPairController::class, 'getByPublisher'])->name('fixed-pairs.by-publisher');
    Route::get('/available-publishers/{weekdayTimeSlotId}', [FixedPairController::class, 'getAvailablePublishers'])->name('fixed-pairs.available-publishers');
    Route::post('/check-restrictions', [FixedPairController::class, 'checkRestrictions'])->name('fixed-pairs.check-restrictions');
    Route::post('/check-pair', [FixedPairController::class, 'checkPair'])->name('fixed-pairs.check-pair');
});

// Rotas para PublisherPairRestrictions
Route::prefix('restrictions')->group(function () {
    Route::get('/', [PublisherPairRestrictionController::class, 'index'])->name('restrictions.index');
    Route::post('/', [PublisherPairRestrictionController::class, 'store'])->name('restrictions.store');
    Route::get('/{id}', [PublisherPairRestrictionController::class, 'show'])->name('restrictions.show');
    Route::delete('/{id}', [PublisherPairRestrictionController::class, 'destroy'])->name('restrictions.destroy');
    
    // Rotas extras
    Route::get('/by-requester/{publisherId}', [PublisherPairRestrictionController::class, 'getByRequester'])->name('restrictions.by-requester');
    Route::get('/by-restricted/{publisherId}', [PublisherPairRestrictionController::class, 'getByRestricted'])->name('restrictions.by-restricted');
    Route::get('/check/{publisherOneId}/{publisherTwoId}', [PublisherPairRestrictionController::class, 'checkRestriction'])->name('restrictions.check');
    Route::get('/summary/{publisherId}', [PublisherPairRestrictionController::class, 'getRestrictionsSummary'])->name('restrictions.summary');
    Route::get('/publishers-that-restrict/{publisherId}', [PublisherPairRestrictionController::class, 'getPublishersThatRestrictMe'])->name('restrictions.publishers-that-restrict');
    Route::get('/restricted-publishers/{publisherId}', [PublisherPairRestrictionController::class, 'getRestrictedPublishers'])->name('restrictions.restricted-publishers');
    Route::get('/{id}/check-delete', [PublisherPairRestrictionController::class, 'checkDelete'])->name('restrictions.check-delete');
});

// Rotas para PublisherPairPreferences
Route::prefix('preferences')->group(function () {
    Route::get('/', [PublisherPairPreferenceController::class, 'index'])->name('preferences.index');
    Route::post('/', [PublisherPairPreferenceController::class, 'store'])->name('preferences.store');
    Route::get('/{id}', [PublisherPairPreferenceController::class, 'show'])->name('preferences.show');
    Route::delete('/{id}', [PublisherPairPreferenceController::class, 'destroy'])->name('preferences.destroy');
    
    // Rotas extras
    Route::get('/by-requester/{publisherId}', [PublisherPairPreferenceController::class, 'getByRequester'])->name('preferences.by-requester');
    Route::get('/by-preferred/{publisherId}', [PublisherPairPreferenceController::class, 'getByPreferred'])->name('preferences.by-preferred');
    Route::get('/check/{publisherOneId}/{publisherTwoId}', [PublisherPairPreferenceController::class, 'checkPreference'])->name('preferences.check');
    Route::get('/summary/{publisherId}', [PublisherPairPreferenceController::class, 'getPreferencesSummary'])->name('preferences.summary');
    Route::get('/by-mode/{mode}', [PublisherPairPreferenceController::class, 'getByMode'])->name('preferences.by-mode');
    Route::get('/publishers-that-prefer/{publisherId}', [PublisherPairPreferenceController::class, 'getPublishersThatPreferMe'])->name('preferences.publishers-that-prefer');
    Route::get('/preferred-publishers/{publisherId}', [PublisherPairPreferenceController::class, 'getPreferredPublishers'])->name('preferences.preferred-publishers');
    Route::get('/statistics', [PublisherPairPreferenceController::class, 'getStatistics'])->name('preferences.statistics');
    Route::get('/{id}/check-delete', [PublisherPairPreferenceController::class, 'checkDelete'])->name('preferences.check-delete');
});

// Rotas para Publishers
Route::prefix('publishers')->group(function () {
    Route::get('/', [PublisherController::class, 'index'])->name('publishers.index');
    Route::post('/', [PublisherController::class, 'store'])->name('publishers.store');
    Route::get('/active', [PublisherController::class, 'getActive'])->name('publishers.active');
    Route::get('/pioneers', [PublisherController::class, 'getPioneers'])->name('publishers.pioneers');
    Route::get('/available', [PublisherController::class, 'getAvailable'])->name('publishers.available');
    Route::get('/statistics', [PublisherController::class, 'getStatistics'])->name('publishers.statistics');
    Route::get('/{id}', [PublisherController::class, 'show'])->name('publishers.show');
    Route::put('/{id}', [PublisherController::class, 'update'])->name('publishers.update');
    Route::patch('/{id}', [PublisherController::class, 'update'])->name('publishers.update.patch');
    Route::delete('/{id}', [PublisherController::class, 'destroy'])->name('publishers.destroy');
    
    // Rotas extras
    Route::get('/{id}/summary', [PublisherController::class, 'getSummary'])->name('publishers.summary');
    Route::get('/{id}/restrictions', [PublisherController::class, 'getRestrictions'])->name('publishers.restrictions');
    Route::get('/{id}/preferences', [PublisherController::class, 'getPreferences'])->name('publishers.preferences');
    Route::get('/{id}/fixed-pairs', [PublisherController::class, 'getFixedPairs'])->name('publishers.fixed-pairs');
    Route::get('/{id}/check-availability', [PublisherController::class, 'checkAvailability'])->name('publishers.check-availability');
    Route::post('/{id}/activate', [PublisherController::class, 'activate'])->name('publishers.activate');
    Route::post('/{id}/deactivate', [PublisherController::class, 'deactivate'])->name('publishers.deactivate');
});