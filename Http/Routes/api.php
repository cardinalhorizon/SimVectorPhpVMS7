<?php

Route::group(['middleware' => ['api.auth']], function() {
    Route::post('/ingest', 'ApiController@ingestData');
});