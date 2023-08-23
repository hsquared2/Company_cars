<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$defaultStartDate = date('Y-m-d');
$defaultFinishDate = date('Y-m-d', time() + (3600 * 24));
  
$arComponentParameters = array(
    "PARAMETERS" => array(
        "TRIP_START" => array(
            "PARENT" => "BASE",
            "NAME" => "Начало",
            "TYPE" => "STRING",
            "DEFAULT" => $defaultStartDate,
        ),
        "TRIP_FINISH" => array(
            "PARENT" => "BASE",
            "NAME" => "Конец",
            "TYPE" => "STRING",
            "DEFAULT" => $defaultFinishDate,
        ),
    ),
);
