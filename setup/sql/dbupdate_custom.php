<#1>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#2>
<?php
if (!$ilDB->tableColumnExists("content_object", "for_translation"))
{
	$ilDB->addTableColumn("content_object", "for_translation", array(
		"type" => "integer",
		"notnull" => true,
		"length" => 1,
		"default" => 0));
}
?>
<#3>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#4>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#5>
<?php
$set = $ilDB->query("SELECT * FROM mep_item JOIN mep_tree ON (mep_item.obj_id = mep_tree.child) ".
	" WHERE mep_item.type = ".$ilDB->quote("pg", "text")
	);
while ($rec = $ilDB->fetchAssoc($set))
{
	$q = "UPDATE page_object SET ".
		" parent_id = ".$ilDB->quote($rec["mep_id"], "integer").
		" WHERE parent_type = ".$ilDB->quote("mep", "text").
		" AND page_id = ".$ilDB->quote($rec["obj_id"], "integer");
	//echo "<br>".$q;
	$ilDB->manipulate($q);
}
?>
<#6>
<?php
if (!$ilDB->tableColumnExists("mep_data", "for_translation"))
{
	$ilDB->addTableColumn("mep_data", "for_translation", array(
		"type" => "integer",
		"notnull" => true,
		"length" => 1,
		"default" => 0));
}
?>
<#7>
<?php
if (!$ilDB->tableColumnExists("mep_item", "import_id"))
{
	$ilDB->addTableColumn("mep_item", "import_id", array(
		"type" => "text",
		"notnull" => false,
		"length" => 50));
}
?>
<#8>
<?php
	$ilCtrlStructureReader->getStructure();
?>
