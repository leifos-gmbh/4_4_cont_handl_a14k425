<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Export/classes/class.ilXmlExporter.php");

/**
 * Exporter class for html learning modules
 *
 * @author Stefan Meyer <meyer@leifos.com>
 * @version $Id: $
 * @ingroup ModulesLearningModule
 */
class ilLearningModuleExporter extends ilXmlExporter
{
	private $ds;
	private $config;

	/**
	 * Initialisation
	 */
	function init()
	{
		include_once("./Modules/LearningModule/classes/class.ilLearningModuleDataSet.php");
		$this->ds = new ilLearningModuleDataSet();
		$this->ds->setExportDirectories($this->dir_relative, $this->dir_absolute);
		$this->ds->setDSPrefix("ds");
		$this->config = $this->getExport()->getConfig("Modules/LearningModule");
		if ($this->config->getMasterLanguageOnly())
		{
			$conf = $this->getExport()->getConfig("Services/COPage");
			$conf->setMasterLanguageOnly(true);
			$this->ds->setMasterLanguageOnly(true);
		}
	}

	/**
	 * Get tail dependencies
	 *
	 * @param		string		entity
	 * @param		string		target release
	 * @param		array		ids
	 * @return		array		array of array with keys "component", entity", "ids"
	 */
	function getXmlExportTailDependencies($a_entity, $a_target_release, $a_ids)
	{
		include_once("./Modules/LearningModule/classes/class.ilLMObject.php");
		include_once("./Modules/LearningModule/classes/class.ilLMPageObject.php");

		$md_ids = array();

		// lm related ids
		foreach ($a_ids as $id)
		{
			$md_ids[] = $id.":0:lm";
		}

		// chapter related ids
		foreach ($a_ids as $id)
		{
			$chaps = ilLMObject::getObjectList($id, "st");
			foreach ($chaps as $c)
			{
				$md_ids[] = $id.":".$c["obj_id"].":st";
			}
		}

		// page related ids
		$pg_ids = array();
		foreach ($a_ids as $id)
		{
			$pages = ilLMPageObject::getPageList($id);
			foreach ($pages as $p)
			{
				$pg_ids[] = "lm:".$p["obj_id"];
				$md_ids[] = $id.":".$p["obj_id"].":pg";
			}
		}

		// style, multilang, metadata per page/chap?

		$deps = array (
			array(
				"component" => "Services/COPage",
				"entity" => "pg",
				"ids" => $pg_ids),
			array(
				"component" => "Services/MetaData",
				"entity" => "md",
				"ids" => $md_ids),
		);

		if (!$this->config->getMasterLanguageOnly())
		{
			$deps[] = array(
				"component" => "Services/Object",
				"entity" => "transl",
				"ids" => $md_ids);
		}

		return $deps;
	}



	/**
	 * Get xml representation
	 *
	 * @param	string		entity
	 * @param	string		target release
	 * @param	string		id
	 * @return	string		xml string
	 */
	public function getXmlRepresentation($a_entity, $a_schema_version, $a_id)
	{
		return $this->ds->getXmlRepresentation($a_entity, $a_schema_version, $a_id, "", true, true);

		/*include_once './Modules/LearningModule/classes/class.ilObjLearningModule.php';
		$lm = new ilObjLearningModule($a_id,false);

		include_once './Modules/LearningModule/classes/class.ilContObjectExport.php';
		$exp = new ilContObjectExport($lm);
		$zip = $exp->buildExportFile();*/
		
	}

	/**
	 * Returns schema versions that the component can export to.
	 * ILIAS chooses the first one, that has min/max constraints which
	 * fit to the target release. Please put the newest on top.
	 *
	 * @return
	 */
	function getValidSchemaVersions($a_entity)
	{
		return array (
			"4.4.0" => array(
				"namespace" => "http://www.ilias.de/Modules/LearningModule/lm/4_4",
				"xsd_file" => "ilias_lm_4_4.xsd",
				"uses_dataset" => true,
				"min" => "4.4.0",
				"max" => ""),
			"4.1.0" => array(
				"namespace" => "http://www.ilias.de/Modules/LearningModule/lm/4_1",
				"xsd_file" => "ilias_lm_4_1.xsd",
				"uses_dataset" => false,
				"min" => "4.1.0",
				"max" => "")
		);
	}

}

?>