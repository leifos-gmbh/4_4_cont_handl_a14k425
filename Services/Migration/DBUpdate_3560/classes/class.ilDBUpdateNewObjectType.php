<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Helper class to create new object types (object_data, RBAC) 
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * $Id: class.ilObjFolderGUI.php 25134 2010-08-13 14:22:11Z smeyer $
 *
 * @ingroup ServicesMigration
 */
class ilDBUpdateNewObjectType
{	
	const RBAC_OP_EDIT_PERMISSIONS = 1;
	const RBAC_OP_VISIBLE = 2;
	const RBAC_OP_READ = 3;
	const RBAC_OP_WRITE = 4;
	const RBAC_OP_DELETE = 6;
	const RBAC_OP_COPY = 99;
	
	/**
	 * Add new type to object data
	 * 
	 * @param string $a_type_id
	 * @param string $a_type_title 
	 * @return int insert id
	 */
	public static function addNewType($a_type_id, $a_type_title)
	{
		global $ilDB;
		
		// check if it already exists
		$type_id = self::getObjectTypeId($a_type_id);
		if($type_id)
		{
			return $type_id;
		}
		
		$type_id = $ilDB->nextId('object_data');
		
		$fields = array(
			'obj_id' => array('integer', $type_id),
			'type' => array('text', 'typ'),
			'title' => array('text', $a_type_id),
			'description' => array('text', $a_type_title),
			'owner' => array('integer', -1),
			'create_date' => array('timestamp', ilUtil::now()),
			'last_update' => array('timestamp', ilUtil::now())
		);	
		$ilDB->insert('object_data', $fields);
		
		return $type_id;
	}
	
	/**
	 * Add RBAC operations for type
	 * 
	 * @param int $a_type_id
	 * @param array $a_operations 
	 */
	public static function addRBACOperations($a_type_id, array $a_operations)
	{		
		foreach($a_operations as $ops_id)
		{						
			if(self::isValidRBACOperation($ops_id))
			{			
				if($ops_id == self::RBAC_OP_COPY)
				{
					$ops_id = self::getCustomRBACOperationId('copy');					
				}
				
				self::addRBACOperation($a_type_id, $ops_id);
			}
		}		
	}
	
	/**
	 * Add RBAC operation
	 * 
	 * @param int $a_type_id
	 * @param int $a_ops_id 
	 */
	public static function addRBACOperation($a_type_id, $a_ops_id)
	{
		global $ilDB;
		
		// check if it already exists
		$set = $ilDB->query('SELECT * FROM rbac_ta'.
			' WHERE typ_id = '.$ilDB->quote($a_type_id, 'integer').
			' AND ops_id = '.$ilDB->quote($a_ops_id, 'integer'));
		if($ilDB->numRows($set))
		{			
			return;
		}		
		
		$fields = array(
			'typ_id' => array('integer', $a_type_id),
			'ops_id' => array('integer', $a_ops_id)
		);
		return $ilDB->insert('rbac_ta', $fields);
	}

	/**
	 * Delete rbac operation
	 * 
	 * @param int $a_type
	 * @param int $a_ops_id 
	 */
	public static function deleteRBACOperation($a_type, $a_ops_id)
	{
		global $ilDB;
		
		if(!$a_type || !$a_ops_id)
		{
			return;
		}
		
		$type_id = self::getObjectTypeId($a_type);
		if(!$type_id)
		{
			return;
		}

		$query = 'DELETE FROM rbac_ta WHERE '.
			'typ_id = '.$ilDB->quote($type_id,'integer').' AND '.
			'ops_id = '.$ilDB->quote($a_ops_id,'integer');		
		$GLOBALS['ilLog']->write(__METHOD__.': '.$query);
		$ilDB->manipulate($query);		
		
		self::deleteRBACTemplateOperation($a_type, $a_ops_id);
	}
	
	/**
	 * Delete operation for type in templates
	 * 
	 * @param string $a_type
	 * @param int $a_ops_id
	 */
	public static function deleteRBACTemplateOperation($a_type, $a_ops_id)
	{
		global $ilDB;
		
		if(!$a_type || !$a_ops_id)
		{
			return;
		}

		$query = 'DELETE FROM rbac_templates WHERE '.
			'type = '.$ilDB->quote($a_type,'text').' AND '.
			'ops_id = '.$ilDB->quote($a_ops_id,'integer');	
		$GLOBALS['ilLog']->write(__METHOD__.': '.$query);
		$ilDB->manipulate($query);	
	}

	/**
	 * Check if given RBAC operation id is valid
	 * 
	 * @param int $a_ops_id 
	 * @return bool
	 */
	protected static function isValidRBACOperation($a_ops_id)
	{
		$valid = array(
			self::RBAC_OP_EDIT_PERMISSIONS,
			self::RBAC_OP_VISIBLE,
			self::RBAC_OP_READ,
			self::RBAC_OP_WRITE,
			self::RBAC_OP_DELETE,
			self::RBAC_OP_COPY				
		);
		if(in_array($a_ops_id, $valid))
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Get id of RBAC operation
	 * 
	 * @param string $a_operation
	 * @return int
	 */
	public static function getCustomRBACOperationId($a_operation)
	{
		global $ilDB;
		
		$sql = 'SELECT ops_id'.
			' FROM rbac_operations'.
			' WHERE operation = '.$ilDB->quote($a_operation, 'text');
		$res = $ilDB->query($sql);
		$row = $ilDB->fetchAssoc($res);
		return $row['ops_id'];
	}
	
	/**
	 * Add custom RBAC operation
	 * 
	 * @param string $a_id
	 * @param string $a_title 
	 * @param string $a_class 
	 * @param string $a_pos 
	 * @return int ops_id
	 */
	public static function addCustomRBACOperation($a_id, $a_title, $a_class, $a_pos)
	{
		global $ilDB;
		
		// check if it already exists
		$ops_id = self::getCustomRBACOperationId($a_id);
		if($ops_id)
		{
			return $ops_id;
		}
		
		if(!in_array($a_class, array('create', 'object', 'general')))
		{
			return;
		}
		if($a_class == 'create')
		{
			$a_pos = 9999;
		}
		
		$ops_id = $ilDB->nextId('rbac_operations');
		
		$fields = array(
			'ops_id' => array('integer', $ops_id),
			'operation' => array('text', $a_id),
			'description' => array('text', $a_title),
			'class' => array('text', $a_class),
			'op_order' => array('integer', $a_pos),
		);		
		$ilDB->insert('rbac_operations', $fields);
		
		return $ops_id;
	}	

	/**
	 * Get id for object data type entry
	 * 
	 * @param string $a_type
	 * @return int
	 */
	public static function getObjectTypeId($a_type)
	{
		global $ilDB;
		
		$sql = 'SELECT obj_id FROM object_data'.
			' WHERE type = '.$ilDB->quote('typ', 'text').
			' AND title = '.$ilDB->quote($a_type, 'text');
		$res = $ilDB->query($sql);
		$row = $ilDB->fetchAssoc($res);
		return $row['obj_id'];
	}
	
	/**
	 * Add create RBAC operations for parent object types
	 * 
	 * @param string  $a_id
	 * @param string $a_title
	 * @param array $a_parent_types 
	 */
	public static function addRBACCreate($a_id, $a_title, array $a_parent_types)
	{		
		$ops_id = self::addCustomRBACOperation($a_id, $a_title, 'create', 9999);
		
		foreach($a_parent_types as $type)
		{
			$type_id = self::getObjectTypeId($type);		
			if($type_id)
			{
				self::addRBACOperation($type_id, $ops_id);
			}
		}		
	}	
	
	/**
	 * Change order of operations
	 * 
	 * @param string $a_operation
	 * @param int $a_pos 
	 */
	public static function updateOperationOrder($a_operation, $a_pos)
	{
		global $ilDB;
		
		$ilDB->update('rbac_operations', 
			array('op_order' => array('integer', $a_pos)),
			array('operation' => array('text', $a_operation))		
		);
	}
	
	/**
	 * Create new admin object node
	 * 
	 * @param string $a_id
	 * @param string $a_title
	 */
	public static function addAdminNode($a_obj_type, $a_title)
	{
		global $ilDB, $tree;
		
		if(self::getObjectTypeId($a_obj_type))
		{
			return;
		}
		
		$obj_type_id = self::addNewType($a_obj_type, $a_title);

		$obj_id = $ilDB->nextId('object_data');
		$ilDB->manipulate("INSERT INTO object_data ".
			"(obj_id, type, title, description, owner, create_date, last_update) VALUES (".
			$ilDB->quote($obj_id, "integer").",".
			$ilDB->quote($a_obj_type, "text").",".
			$ilDB->quote($a_title, "text").",".
			$ilDB->quote($a_title, "text").",".
			$ilDB->quote(-1, "integer").",".
			$ilDB->now().",".
			$ilDB->now().
			")");

		$ref_id = $ilDB->nextId('object_reference');
		$ilDB->manipulate("INSERT INTO object_reference ".
			"(obj_id, ref_id) VALUES (".
			$ilDB->quote($obj_id, "integer").",".
			$ilDB->quote($ref_id, "integer").
			")");

		// put in tree
		$tree = new ilTree(ROOT_FOLDER_ID);
		$tree->insertNode($ref_id, SYSTEM_FOLDER_ID);

		$rbac_ops = array(
			self::RBAC_OP_EDIT_PERMISSIONS,
			self::RBAC_OP_VISIBLE,
			self::RBAC_OP_READ,
			self::RBAC_OP_WRITE
		);
		self::addRBACOperations($obj_type_id, $rbac_ops);
	}
	
	/**
	 * Migrate varchar column to text/clob
	 * 
	 * @param string $a_table_name
	 * @param string $a_column_name
	 * @return bool
	 */
	public static function varchar2text($a_table_name, $a_column_name)
	{
		global $ilDB;
		
		$tmp_column_name = $a_column_name."_tmp_clob";
		
		if(!$ilDB->tableColumnExists($a_table_name, $a_column_name) ||
			$ilDB->tableColumnExists($a_table_name, $tmp_column_name))
		{
			return false;
		}
		
		// oracle does not support ALTER TABLE varchar2 to CLOB
	
		$ilDB->lockTables(array(
			array('name'=> $a_table_name, 'type'=>ilDB::LOCK_WRITE)
		));

		$def = array(
			'type'    => 'clob',
			'notnull' => false
		);
		$ilDB->addTableColumn($a_table_name, $tmp_column_name, $def);	

		$ilDB->manipulate('UPDATE '.$a_table_name.' SET '.$tmp_column_name.' = '.$a_column_name);

		$ilDB->dropTableColumn($a_table_name, $a_column_name);

		$ilDB->renameTableColumn($a_table_name, $tmp_column_name, $a_column_name);

		$ilDB->unlockTables();
		
		return true;
	}
}

?>