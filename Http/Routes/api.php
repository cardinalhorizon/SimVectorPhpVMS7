<?php
Route::get('/airports', 'ApiController@getAirports');
Route::get('/checksums', 'ApiController@getChecksums');

Route::group(['middleware' => ['api.auth']], function() {
    Route::get('/token', 'ApiController@getNewSessionToken');
    Route::post('/ingest', 'ApiController@ingestData');
    Route::get('/flights', 'FlightsController@search');
    Route::post('/bids', 'FlightsController@book');
    Route::delete('/bids', 'FlightsController@unbook');
});