<?php

/** @var $this \Illuminate\Routing\Router */
$this->group(['namespace' => 'Guide', 'prefix' => 'Guide', 'as' => 'guide.'], function () {
    $this->get('{guide}-{slug}', ['uses' => 'ShowController@index']);
    $this->post('Submit/{guide}', ['uses' => 'SubmitController@index', 'middleware' => ['auth']]);
});