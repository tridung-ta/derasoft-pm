<?php

/*************************************************************************
Class carbattery
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd 
Update: 22/09/2011
Coder: Tran Thi My Xuyen
Checked by: Mai Minh (03/06/2025)
 **************************************************************************/
class CarBatteryInfo
{
	var $id;
	var $status;
	var $store_id;
	var $battery_id;
	var $car_id;
	var $car_type;
	var $battery_note;
	var $properties;


	function __construct($status, $store_id, $battery_id, $car_id, $car_type, $battery_note, $properties, $id = 0)
	{
		$this->id = $id;
		$this->status = $status;
		$this->store_id = $store_id;
		$this->battery_id = $battery_id;
		$this->car_id = $car_id;
		$this->car_type = $car_type;
		$this->battery_note = $battery_note;
		$this->properties = unserialize($properties);
	}

	// Getter and setter for $id
	public function getId()
	{
		return $this->id;
	}
	public function setId($nValue)
	{
		$this->id = $nValue;
	}

	// Getter and setter for $status
	public function getStatus()
	{
		return $this->status;
	}
	public function setStatus($nValue)
	{
		$this->status = $nValue;
	}
	// Getter and setter for $store_id
	public function getStoreId()
	{
		return $this->store_id;
	}
	public function setStoreId($nValue)
	{
		$this->store_id = $nValue;
	}

	// Getter and setter for $battery_id
	public function getBatteryId()
	{
		return $this->battery_id;
	}
	public function setBatteryId($nValue)
	{
		$this->battery_id = $nValue;
	}

	// Getter and setter for $car_id
	public function getCarId()
	{
		return $this->car_id;
	}
	public function setCarId($nValue)
	{
		$this->car_id = $nValue;
	}

	// Getter and setter for $car_type
	public function getCarType()
	{
		return $this->car_type;
	}
	public function setCarType($nValue)
	{
		$this->car_type = $nValue;
	}

	// Getter and setter for $battery_note
	public function getBatteryNote()
	{
		return $this->battery_note;
	}
	public function setBatteryNote($nValue)
	{
		$this->battery_note = $nValue;
	}

	// Getter and setter for $properties
	public function getProperties()
	{
		return $this->properties;
	}
	public function setProperties($nValue)
	{
		$this->properties = $nValue;
	}
}
