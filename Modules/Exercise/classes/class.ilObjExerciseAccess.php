<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Object/classes/class.ilObjectAccess.php");

/**
* Class ilObjExerciseAccess
*
*
* @author 		Alex Killing <alex.killing@gmx.de>
* @version $Id: class.ilObjExerciseAccess.php 46851 2013-12-16 08:47:31Z jluetzen $
*
* @ingroup ModulesExercise
*/
class ilObjExerciseAccess extends ilObjectAccess
{

	/**
	 * get commands
	 * 
	 * this method returns an array of all possible commands/permission combinations
	 * 
	 * example:	
	 * $commands = array
	 *	(
	 *		array("permission" => "read", "cmd" => "view", "lang_var" => "show"),
	 *		array("permission" => "write", "cmd" => "edit", "lang_var" => "edit"),
	 *	);
	 */
	function _getCommands()
	{
		$commands = array
		(
			array("permission" => "read", "cmd" => "showOverview", "lang_var" => "show",
				"default" => true),
			array("permission" => "write", "cmd" => "listAssignments", "lang_var" => "edit_assignments"),
			array("permission" => "write", "cmd" => "edit", "lang_var" => "settings")
		);
		
		return $commands;
	}
	
	function _lookupRemainingWorkingTimeString($a_obj_id)
	{
		global $ilDB, $lng;
		
		$q = "SELECT MIN(time_stamp) mtime, COUNT(*) cnt FROM exc_assignment WHERE exc_id = ".
			$ilDB->quote($a_obj_id, "integer").
			" AND time_stamp > ".$ilDB->quote(time(), "integer");
		$set = $ilDB->query($q);
		$rec = $ilDB->fetchAssoc($set);
					
		if ($rec["mtime"] > 0)
		{
			$time_diff = ilUtil::int2array($rec["mtime"] - time(), null);
			$rec["mtime"] = ilUtil::timearray2string($time_diff);
		}
		return $rec;
	}
	
	/**
	* check whether goto script will succeed
	*/
	function _checkGoto($a_target)
	{
		global $ilAccess;
		
		$t_arr = explode("_", $a_target);

		if ($t_arr[0] != "exc" || ((int) $t_arr[1]) <= 0)
		{
			return false;
		}

		if ($ilAccess->checkAccess("read", "", $t_arr[1]))
		{
			return true;
		}
		return false;
	}
}

?>
