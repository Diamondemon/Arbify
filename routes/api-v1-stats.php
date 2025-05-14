<?php

Route::get('/projects/{project}/statistics', 'Project\StatisticsController@index')
    ->name('projects.statistics');
