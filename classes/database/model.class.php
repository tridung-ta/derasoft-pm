<?php
/*************************************************************************
Class Model
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025
Coder: Mai Minh
**************************************************************************/
class Model {
	var $_db;
	var $table = '';
	var $fields = array();
	
/*-----------------------------------------------------------------------*
* Function: Constructor
* Parameter: DB , Table, Fields
* Return: No return
*-----------------------------------------------------------------------*/	
	function __construct($db = '', $table = '', $fields = array()) {
		$this->_db = $db;
		$this->table = $table;
		$this->fields = $fields;
	}

	

/*-----------------------------------------------------------------------*
* Function: Select
* Parameter: Condition, Order, Limit, Value
* Return: Return an array
*-----------------------------------------------------------------------*/	
	function select($fields = '*', $condition = '1>0', $sort = array(), $start = '0', $limit = 0){
		$sql = 'SELECT '.$fields.' FROM `'.$this->table.'` WHERE '.$condition;
		$order_sql = '';
		if($sort) {
			$order_sql = ' ORDER BY ';
			$i = 0;
			foreach($sort as $field => $order) {
				$order_sql .= "`$field` $order".($i < count($sort) - 1?',':'');
				$i++;
			}
		}
		$sql .= $order_sql;
		if ($limit != 0){
			$sql = $sql." LIMIT $start,$limit";
		}
		#echo $sql.'<br>';
		if(SHOW_QUERY) echo $sql.'<br>';
		$result = $this->_db->query($sql); 
		if($result) {
			$data = array();
			while($row = $this->_db->fetchArray($result)) {
				$data[] = $row;
			}
			$this->_db->freeResult($result);
			return $data;
		}
		return 0;	
	}


/*-----------------------------------------------------------------------*
* Function: Add
* Parameter: fields, primary key, primary value (pass -1 to use the value from fields array
* Return: New insert ID if OK, 0 if failed
*-----------------------------------------------------------------------*/
function add($fields = '', $pk = 'id', $pkValue = 'NULL') {
	if(!$fields) $fields = $this->fields;
	$numFields = count($fields);
	if($numFields){
		$sql = "INSERT INTO `".$this->table."`";
		$fieldList = '';
		$valueList = '';
		$i = 0;
		foreach($fields as $fieldName => $fieldValue) {
			$fieldList .= "`$fieldName`";
			// ===== Xử lý giá trị =====
			if ($pk == $fieldName && $pkValue != -1) {
				// Nếu là primary key và không phải auto increment
				$value = $pkValue;
			} else {
				if ($fieldValue === null) {
					$value = "NULL";
				} else {
					$value = "'" . addslashes((string)$fieldValue) . "'";
				}
			}
			$valueList .= $value;
			if($i < $numFields - 1) { 
				$fieldList .= ',';
				$valueList .= ',';
			}
			$i++;
		}
		$sql .= " ($fieldList) VALUES ($valueList)";
		if(SHOW_QUERY) echo $sql;
		if($this->_db->query($sql)) 
			return $this->_db->getInsertId();
	}
	return 0;
}

/*-----------------------------------------------------------------------*
* Function: Delete
* Parameter: condition
* Return: 1 if OK, 0 if failed
*-----------------------------------------------------------------------*/		
	function delete($condition = '1<0') {
	 	$sql = "DELETE FROM `".$this->table."` WHERE $condition";
		if(SHOW_QUERY) echo $sql;
		if($this->_db->query($sql)) return 1;
		return 0;
	}

/*-----------------------------------------------------------------------*
* Function: Update
* Parameter: fields and condition
* Return: 1 if OK, 0 if failed
*-----------------------------------------------------------------------*/	
function update($fields = '', $condition = '1<0') {
    if (!$fields) $fields = $this->fields;

    $numFields = count($fields);

    if ($numFields) {
        $sql = "UPDATE `" . $this->table . "` SET ";

        $i = 0;
        foreach ($fields as $fieldName => $fieldValue) {

            if ($fieldValue === null) {
                $value = "NULL";
            } else {
                $value = "'" . addslashes((string)$fieldValue) . "'";
            }

            $sql .= "`$fieldName` = $value" . ($i < $numFields - 1 ? ',' : '');
            $i++;
        }

        $sql .= " WHERE $condition";

        if (SHOW_QUERY) echo $sql;

        if ($this->_db->query($sql)) return 1;
    }

    return 0;
}

/*-----------------------------------------------------------------------*
* Function: Get number of records and pages
* Parameter: pk, condition and items per page
* Return: pages, rows
*-----------------------------------------------------------------------*/		
	function getNumItems($pk = 'id', $condition = '1>0', $items_per_page = DEFAULT_ADMIN_ROWS_PER_PAGE) {
		$rows = 0;
		$pages = 1;
		$return = array();
		$sql = "SELECT COUNT(`$pk`) FROM `".$this->table."` WHERE $condition";
		if(SHOW_QUERY) echo $sql;
		if($this->_db->query($sql)) $rows = $this->_db->fetchRow();
		if($rows) {
			$pages = ceil($rows[0]/$items_per_page);
			$return = array('rows'=>$rows[0],'pages'=>$pages);
			return $return;			
		}
		return 0;
	}

	function countItems($pk = 'id', $condition = '1>0') {
		$return = array();
		$sql = "SELECT COUNT(`$pk`) FROM `".$this->table."` WHERE $condition";
		if(SHOW_QUERY) echo $sql;
		if($this->_db->query($sql)) $rows = $this->_db->fetchRow();
		if($rows) {
			$return = $rows[0];
			return $return;			
		}
		return 0;
	}

	// PO
	function query($sql) {
		$result = $this->_db->query($sql);
		if($result) {
			$data = array();
			while($row = $this->_db->fetchArray($result)) {
				$data[] = $row;
			}
			$this->_db->freeResult($result);
			return $data;
		}
		return 0;
	}
	
	
}	
?>
