<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();


class CarList extends CBitrixComponent {
  protected $startTime;
  protected $finishTime;

  
  public function executeComponent()
  {
    $this->setTime();

    $employeeStatus = $this->getEmployeeStatus();
    
    $comfortCategory = $this->getComfortCategory($employeeStatus);
    $comfortID = $comfortCategory['ID'];
    $comfortName = $comfortCategory['NAME'];

    $carsList = $this->getCarsList($comfortID);
    $this->checkAvailableCars($carsList);
    $this->setDrivers($carsList);
    
    $carsList['COMFORT'] = $comfortName;

    echo "Дата при передаче в URL должна соответстаовать след. формату: ?start=*year*-*month*-*day*&finish=*year*-*month*-*day*";

    $this->arResult['CARS_LIST'] = $carsList;
  }


  // Function to set time either from component parameters or from get params
  public function setTime() {
    if(!isset($_GET['start']) || !isset($_GET['finish'])) {
      $this->startTime = $this->arParams['TRIP_START'];
      $this->finishTime = $this->arParams['TRIP_FINISH'];
    } else {
      $this->startTime = $_GET['start'];
      $this->finishTime = $_GET['finish'];
    }
  }


  // Function returns the status of current user/employee
  public function getEmployeeStatus() {
    global $USER;
    $userID = $USER->GetID();
    $userData = CUser::GetByID($userID)->Fetch();

    $statusID = $userData['UF_STATUS'];
    
    $userField = CUserFieldEnum::GetList([], [
      'ID' => $statusID,
    ]);

    $data = $userField->Fetch();

    return $data['VALUE'];
  }


  // Function returns comfort category corresponding for the current user
  public function getComfortCategory(string $employeeStatus) {
    $arFilter = ['IBLOCK_CODE' => 'comfort'];

    if($employeeStatus == 'Senior') {
      $arFilter['NAME'] = 'Executive';
    } else if ($employeeStatus == 'Middle') {
      $arFilter['NAME'] = 'Business';
    } else {
      $arFilter['NAME'] = 'Regular';
    }

    $result = CIBlockElement::GetList([], $arFilter);
    $data = $result->Fetch();

    return [
      'ID' => $data['ID'],
      'NAME' => $data['NAME'],
    ];
  }

  // Function returns all the cars in specified comfort category
  public function getCarsList(int $comfortID) {
    $carList = [];

    $result = CIBlockElement::GetList([], [
      'PROPERTY_COMFORT_ID' => $comfortID,
    ]);

    while($ob = $result->GetNextElement()) {
      $fields = $ob->GetFields();
      $props = $ob->GetProperties();

      $carID = $fields['ID'];

      $carList[$carID] = [
        'ID' => $fields['ID'],
        'NAME' => $fields['NAME'],
        'DRIVER_ID' => $props['DRIVER_ID']['VALUE'],
      ];
    }

    return $carList;
  }

  // Function determines all the available cars for specified date
  public function checkAvailableCars(array &$carsList) {
    $carIDs = [];

    foreach($carsList as $car) {
      $carIDs[] = $car['ID'];
    }

    $result = CIBlockElement::GetList([], [
      'IBLOCK_CODE' => 'trips',
      'PROPERTY_CAR_ID' => $carIDs,
    ]);

    while($ob = $result->GetNextElement()) {
      $props = $ob->GetProperties();
      $propCarID = $props['CAR_ID'];

      if(in_array($propCarID['VALUE'], $carIDs)) {
        $bookedTripStart = strtotime($props['TRIP_START']['VALUE']);
        $bookedTripFinish = strtotime($props['TRIP_FINISH']['VALUE']);

        if(!$this->checkTime($bookedTripStart, $bookedTripFinish)) {
          unset($carsList[$propCarID["VALUE"]]);
        }
      }
    }
  }

  // Function sets drivers for their corresponding cars
  public function setDrivers(array &$carsList) {
    $drivers = [];

    $result = CIBlockElement::GetList([], [
      'IBLOCK_CODE' => 'drivers',
    ]);

    while($ob = $result->GetNextElement()) {
      $fields = $ob->GetFields();
      $driverID = $fields['ID'];

      $drivers[$driverID] = $fields['NAME'];
    }

    
    foreach($carsList as &$car) {
      if(key_exists($car['DRIVER_ID'], $drivers)) {
        $key = $car['DRIVER_ID'];
        $car['DRIVER'] = $drivers[$key];
      }
    }
  }

  protected function checkTime($bookedTripStart, $bookedTripFinish) {
    $startTime = strtotime($this->startTime);
    $finishTime = strtotime($this->finishTime);

    if (($startTime >= $bookedTripStart) and ($startTime <= $bookedTripFinish) 
      or ($finishTime >= $bookedTripStart) and ($finishTime <= $bookedTripFinish) 
      or ($startTime <= $bookedTripStart) and ($finishTime >= $bookedTripFinish))
    {
      return false;
    }
    return true;
  }
}