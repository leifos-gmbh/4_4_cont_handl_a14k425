<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/


/**
* Class ilObjSystemFolder
* 
* @author Stefan Meyer <meyer@leifos.com> 
* @version $Id: class.ilObjSystemFolder.php 49370 2014-04-11 09:11:14Z jluetzen $
* 
* @extends ilObject
*/

require_once "./Services/Object/classes/class.ilObject.php";

class ilObjSystemFolder extends ilObject
{
	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjSystemFolder($a_id,$a_call_by_reference = true)
	{
		$this->type = "adm";
		$this->ilObject($a_id,$a_call_by_reference);
	}


	/**
	* delete systemfolder and all related data	
	* DISABLED
	* @access	public
	* @return	boolean	true if all object data were removed; false if only a references were removed
	*/
	function delete()
	{
		// DISABLED
		return false;

		// always call parent delete function first!!
		if (!parent::delete())
		{
			return false;
		}

		// put here systemfolder specific stuff

		// always call parent delete function at the end!!
		return true;
	}

	/**
	* get all translations for header title
	*
	* @access	public
	* @return	array
	*/
	function getHeaderTitleTranslations()
	{
		global $ilDB;
		
		$q = "SELECT * FROM object_translation WHERE obj_id = ".
			$ilDB->quote($this->getId(),'integer')." ORDER BY lang_default DESC";
		$r = $this->ilias->db->query($q);

		$num = 0;

		while ($row = $r->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$data["Fobject"][$num]= array("title"	=> $row->title,
										  "desc"	=> ilUtil::shortenText($row->description,ilObject::DESC_LENGTH,true),
										  "lang"	=> $row->lang_code
										  );
		$num++;
		}

		// first entry is always the default language
		$data["default_language"] = 0;

		return $data ? $data : array();
	}

	// remove all Translations of current category
	function removeHeaderTitleTranslations()
	{
		global $ilDB;
		
		$query = "DELETE FROM object_translation WHERE obj_id= ".
			$ilDB->quote($this->getId(),'integer');
		$res = $ilDB->manipulate($query);
	}

	// add a new translation to current category
	function addHeaderTitleTranslation($a_title,$a_desc,$a_lang,$a_lang_default)
	{
		global $ilDB;
		
		$query = "INSERT INTO object_translation ".
			 "(obj_id,title,description,lang_code,lang_default) ".
			 "VALUES ".
			 "(".$ilDB->quote($this->getId(),'integer').",".
			 $ilDB->quote($a_title,'text').",".
			 $ilDB->quote($a_desc,'text').",".
			 $ilDB->quote($a_lang,'text').",".
			 $ilDB->quote($a_lang_default,'integer').")";
		$res = $ilDB->manipulate($query);

		return true;
	}

	function _getId()
	{
		$q = "SELECT obj_id FROM object_data ".
			"WHERE type = 'adm'";
		$r = $this->ilias->db->query($q);
		$row = $r->fetchRow(DB_FETCHMODE_OBJECT);

		return $row->obj_id;
	}

	function _getHeaderTitle()
	{
		global $ilDB, $ilUser;
		
		$id = ilObjSystemFolder::_getId();

		$q = "SELECT title,description FROM object_translation ".
			"WHERE obj_id = ".$ilDB->quote($id,'integer')." ".
			"AND lang_default = 1";
		$r = $this->ilias->db->query($q);
		$row = $r->fetchRow(DB_FETCHMODE_OBJECT);
		$title = $row->title;

		$q = "SELECT title,description FROM object_translation ".
			"WHERE obj_id = ".$ilDB->quote($id,'integer')." ".
			"AND lang_code = ".
			$ilDB->quote($ilUser->getCurrentLanguage(),'text')." ".
			"AND NOT lang_default = 1";
		$r = $this->ilias->db->query($q);
		$row = $r->fetchRow(DB_FETCHMODE_OBJECT);

		if ($row)
		{
			$title = $row->title;
		}

		return $title;
	}

	function _getHeaderTitleDescription()
	{
		global $ilDB;
		
		$id = ilObjSystemFolder::_getId();

		$q = "SELECT title,description FROM object_translation ".
			"WHERE obj_id = ".$ilDB->quote($id,'integer')." ".
			"AND lang_default = 1";
		$r = $this->ilias->db->query($q);
		$row = $r->fetchRow(DB_FETCHMODE_OBJECT);
		$description = $row->description;

		$q = "SELECT title,description FROM object_translation ".
			"WHERE obj_id = ".$ilDB->quote($id,'integer')." ".
			"AND lang_code = ".
			$ilDB->quote($this->ilias->account->getPref("language"),'text')." ".
			"AND NOT lang_default = 1";
		$r = $this->ilias->db->query($q);
		$row = $r->fetchRow(DB_FETCHMODE_OBJECT);

		if ($row)
		{
			$description = ilUtil::shortenText($row->description,ilObject::DESC_LENGTH,true);
		}

		return $description;
	}

} // END class.ilObjSystemFolder
?>
