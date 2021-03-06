<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once("./Services/Search/classes/class.ilRepositorySearchGUI.php");
require_once("class.ilOrgUnitStaffTableGUI.php");
require_once("class.ilOrgUnitOtherRolesTableGUI.php");
/**
 * Class ilOrgUnitStaffGUI
 *
 * @author            Oskar Truffer <ot@studer-raimann.ch>
 * @author            Martin Studer <ms@studer-raimann.ch>
 * Date: 4/07/13
 * Time: 1:09 PM
 *
 * @ilCtrl_Calls ilOrgUnitStaffGUI: ilRepositorySearchGUI
 */
class ilOrgUnitStaffGUI {

	/**
	 * @var ilTabsGUI
	 */
	protected $tabs_gui;
	/**
	 * @var ilToolbarGUI
	 */
	protected $toolbar;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;
	/**
	 * @var ilObjOrgUnitGUI
	 */
	protected $parent_gui;
	/**
	 * @var ilObjOrgUnit
	 */
	protected $parent_obj;
	/**
	 * @var ilLanguage
	 */
	protected $lng;
	/**
	 * @var ilAccessHandler
	 */
	protected $ilAccess;
	/**
	 * @var ilRbacReview
	 */
	protected $rbacreview;



	/**
	 * @param ilObjOrgUnitGUI $parent_gui
	 */
	function __construct(ilObjOrgUnitGUI $parent_gui) {
		global $tpl, $ilCtrl, $ilTabs, $lng, $ilAccess, $ilToolbar, $rbacreview;

		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->parent_gui = $parent_gui;
		$this->parent_object = $parent_gui->object;
		$this->tabs_gui = $this->parent_gui->tabs_gui;
		$this->toolbar = $ilToolbar;
		$this->lng = $lng;
		$this->ilAccess = $ilAccess;
		$this->toolbar = $ilToolbar;
		$this->rbacreview = $rbacreview;

		$this->tabs_gui->setTabActive("orgu_staff");
		$this->setTabs();
	}

	/**
	 * @return bool
	 */
	public function executeCommand()
	{
		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		switch ($next_class) {
			case 'ilrepositorysearchgui':
				switch ($cmd) {
					case 'addUserFromAutoComplete':
						if ($_GET['addusertype'] == "staff") {
							$this->addStaff();
						} elseif ($_GET['addusertype'] == "other") {
							$this->addOtherRoles();
						}
						break;
					default:
						$repo = new ilRepositorySearchGUI();
						$this->ctrl->forwardCommand($repo);
						break;
				}
				break;
			default:
				switch ($cmd) {
					case 'showStaff':
						$this->tabs_gui->activateSubTab("show_staff");
						$this->showStaff();
						break;
					case 'showOtherRoles':
						$this->tabs_gui->activateSubTab("show_other_roles");
						$this->showOtherRoles();
						break;
					case 'showStaffRec':
						$this->tabs_gui->activateSubTab("show_staff_rec");
						$this->showStaffRec();
						break;
					case 'confirmRemoveFromRole':
					case 'confirmRemoveFromEmployees':
					case 'confirmRemoveFromSuperiors':
						$this->confirmRemoveUser($cmd);
						break;
					default:
						$this->$cmd();
						break;
				}
			break;
		}


		return true;
	}

	public function showStaff() {
		if(!ilObjOrgUnitAccess::_checkAccessStaff($this->parent_object->getRefId()))
		{
			ilUtil::sendFailure($this->lng->txt("permission_denied"), true);
			$this->ctrl->redirect($this->parent_gui, "");
		}
		if ($this->ilAccess->checkAccess("write", "", $this->parent_object->getRefId())) {
			$this->addStaffToolbar();
		}
		$this->ctrl->setParameter($this, "recursive", false);
		$this->tpl->setContent($this->getStaffTableHTML(false, "showStaff"));
	}


	public function showOtherRoles() {
		if (!$this->ilAccess->checkAccess("write", "", $this->parent_object->getRefId())) {
			ilUtil::sendFailure($this->lng->txt("permission_denied"), true);
			$this->ctrl->redirect($this->parent_gui, "");
		}
		if ($this->ilAccess->checkAccess("write", "", $this->parent_object->getRefId())) {
			$this->addOtherRolesToolbar();
		}
		$this->tpl->setContent($this->getOtherRolesTableHTML());
	}


	public function showStaffRec() {
		if(!ilObjOrgUnitAccess::_checkAccessStaffRec($this->parent_object->getRefId()))
		{
			ilUtil::sendFailure($this->lng->txt("permission_denied"), true);
			$this->ctrl->redirect($this->parent_gui, "");
		}
		$this->ctrl->setParameter($this, "recursive", true);
		$this->tpl->setContent($this->getStaffTableHTML(true, "showStaffRec"));
	}


	protected function addStaffToolbar() {
		$types = array(
			"employee" => $this->lng->txt("employee"),
			"superior" => $this->lng->txt("superior")
		);
		$this->ctrl->setParameterByClass('ilRepositorySearchGUI', 'addusertype', 'staff');
		ilRepositorySearchGUI::fillAutoCompleteToolbar($this, $this->toolbar, array(
			'auto_complete_name' => $this->lng->txt('user'),
			'user_type' => $types,
			'submit_name' => $this->lng->txt('add')
		));
	}


	protected function addOtherRolesToolbar() {
		$arrLocalRoles = $this->rbacreview->getLocalRoles($this->parent_object->getRefId());
		$types = array();
		foreach ($arrLocalRoles as $role_id) {
			$ilObjRole = new ilObjRole($role_id);
			if (! preg_match("/il_orgu_/", $ilObjRole->getUntranslatedTitle())) {
				$types[$role_id] = $ilObjRole->getPresentationTitle();
			}
		}
		$this->ctrl->setParameterByClass('ilRepositorySearchGUI', 'addusertype', 'other');
		ilRepositorySearchGUI::fillAutoCompleteToolbar($this, $this->toolbar, array(
			'auto_complete_name' => $this->lng->txt('user'),
			'user_type' => $types,
			'submit_name' => $this->lng->txt('add')
		));
	}


	public function addStaff() {
		if (!$this->ilAccess->checkAccess("write", "", $this->parent_object->getRefId())) {
			ilUtil::sendFailure($this->lng->txt("permission_denied"), true);
			$this->ctrl->redirect($this->parent_gui, "");
		}

		$users = explode(',', $_POST['user_login']);
		$user_ids = array();
		foreach ($users as $user) {
			$user_id = ilObjUser::_lookupId($user);
			if ($user_id) {
				$user_ids[] = $user_id;
			}
		}

		if(!count($user_ids)) {
			ilUtil::sendFailure($this->lng->txt("user_not_found"), true);
			$this->ctrl->redirect($this,"showStaff");
		}

		$user_type = isset($_POST['user_type']) ? $_POST['user_type'] : 0;
		if ($user_type == "employee") {
			$this->parent_object->assignUsersToEmployeeRole($user_ids);
		} elseif ($user_type == "superior") {
			$this->parent_object->assignUsersToSuperiorRole($user_ids);
		} else {
			throw new Exception("The post request didn't specify wether the user_ids should be assigned to the employee or the superior role.");
		}
		ilUtil::sendSuccess($this->lng->txt("users_successfuly_added"), true);
		$this->ctrl->redirect($this,"showStaff");
	}


	public function addOtherRoles() {
		global $rbacreview, $lng, $rbacadmin;
		if (!$this->ilAccess->checkAccess("write", "", $this->parent_object->getRefId())) {
			ilUtil::sendFailure($this->lng->txt("permission_denied"), true);
			$this->ctrl->redirect($this->parent_gui, "");
		}

		$users = explode(',', $_POST['user_login']);
		$user_ids = array();
		foreach ($users as $user) {
			$user_id = ilObjUser::_lookupId($user);
			if ($user_id) {
				$user_ids[] = $user_id;
			}
		}
		$user_type = isset($_POST['user_type']) ? $_POST['user_type'] : 0;
		$arrLocalRoles = $rbacreview->getLocalRoles($this->parent_object->getRefId());
		if (in_array($user_type, $arrLocalRoles)) {
			foreach ($user_ids as $user_id) {
				$rbacadmin->assignUser($user_type, $user_id);
			}
		} else {
			ilUtil::sendFailure($lng->txt("no_permission"));
		}
		ilUtil::sendSuccess($this->lng->txt("users_successfuly_added"), true);
		$this->ctrl->redirect($this,"showOtherRoles");
	}


	/**
	 * @param bool $recursive
	 * @param string $table_cmd
	 *
	 * @return string the tables html.
	 */
	public function getStaffTableHTML($recursive = false, $table_cmd = "showStaff") {
		global $lng, $rbacreview;
		$superior_table = new ilOrgUnitStaffTableGUI($this, $table_cmd, "superior", $recursive);
		$superior_table->parseData();
		$superior_table->setTitle($lng->txt("il_orgu_superior"));
		$employee_table = new ilOrgUnitStaffTableGUI($this, $table_cmd, "employee", $recursive);
		$employee_table->parseData();
		$employee_table->setTitle($lng->txt("il_orgu_employee"));

		return $superior_table->getHTML() . $employee_table->getHTML();
	}


	public function getOtherRolesTableHTML() {
		global $lng, $rbacreview;
		$arrLocalRoles = $rbacreview->getLocalRoles($this->parent_object->getRefId());
		$html = "";
		foreach ($arrLocalRoles as $role_id) {
			$ilObjRole = new ilObjRole($role_id);
			if (! preg_match("/il_orgu_/", $ilObjRole->getUntranslatedTitle())) {
				$other_roles_table = new ilOrgUnitOtherRolesTableGUI($this, 'other_role_' . $role_id, $role_id);
				$other_roles_table->readData();
				$html .= $other_roles_table->getHTML() . "<br/>";
			}
		}
		if (! $html) {
			$html = $lng->txt("no_roles");
		}

		return $html;
	}

	public function fromSuperiorToEmployee() {
		if (!$this->ilAccess->checkAccess("write", "", $this->parent_object->getRefId())) {
			ilUtil::sendFailure($this->lng->txt("permission_denied"), true);
			$this->ctrl->redirect($this->parent_gui, "");
		}
		$this->parent_object->deassignUserFromSuperiorRole($_GET["obj_id"]);
		$this->parent_object->assignUsersToEmployeeRole(array( $_GET["obj_id"] ));
		ilUtil::sendSuccess($this->lng->txt("user_changed_successful"), true);
		$this->ctrl->redirect($this, "showStaff");
	}


	public function fromEmployeeToSuperior() {
		if (!$this->ilAccess->checkAccess("write", "", $this->parent_object->getRefId())) {
			ilUtil::sendFailure($this->lng->txt("permission_denied"), true);
			$this->ctrl->redirect($this->parent_gui, "");
		}
		$this->parent_object->deassignUserFromEmployeeRole($_GET["obj_id"]);
		$this->parent_object->assignUsersToSuperiorRole(array( $_GET["obj_id"] ));
		ilUtil::sendSuccess($this->lng->txt("user_changed_successful"), true);
		$this->ctrl->redirect($this, "showStaff");
	}

	function confirmRemoveUser($cmd) {
		if (!$this->ilAccess->checkAccess("write", "", $this->parent_object->getRefId())) {
			ilUtil::sendFailure($this->lng->txt("permission_denied"), true);
			$this->ctrl->redirect($this->parent_gui, "");
		}
		switch ($cmd) {
			case "confirmRemoveFromRole":
				$this->tabs_gui->activateSubTab("show_other_roles");
				$nextcmd = "removeFromRole";
				$paramname = "obj_id-role_id";
				$param = $_GET["obj_id"] . '-' . $_GET["role_id"];
				break;
			case "confirmRemoveFromSuperiors":
				$this->tabs_gui->activateSubTab("show_staff");
				$nextcmd = "removeFromSuperiors";
				$paramname = "obj_id";
				$param = $_GET["obj_id"];
				break;
			case "confirmRemoveFromEmployees":
				$this->tabs_gui->activateSubTab("show_staff");
				$nextcmd = "removeFromEmployees";
				$paramname = "obj_id";
				$param = $_GET["obj_id"];
				break;
		}
		include_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');
		$confirm = new ilConfirmationGUI();
		$confirm->setFormAction($this->ctrl->getFormAction($this, $nextcmd));
		$confirm->setHeaderText($this->lng->txt('orgu_staff_deassign'));
		$confirm->setConfirm($this->lng->txt('confirm'), $nextcmd);
		$confirm->setCancel($this->lng->txt('cancel'), 'showStaff');
		$arrUser = ilObjUser::_lookupName($_GET["obj_id"]);
		$confirm->addItem($paramname, $param,
			$arrUser['lastname'] . ', ' . $arrUser['firstname'] . ' [' . $arrUser['login']
			. ']', ilUtil::getImagePath('icon_usr.png'));
		$this->tpl->setContent($confirm->getHTML());
	}

	public function removeFromSuperiors() {
		if (!$this->ilAccess->checkAccess("write", "", $this->parent_object->getRefId())) {
			ilUtil::sendFailure($this->lng->txt("permission_denied"), true);
			$this->ctrl->redirect($this->parent_gui, "");
		}
		$this->parent_object->deassignUserFromSuperiorRole($_POST["obj_id"]);
		ilUtil::sendSuccess($this->lng->txt("deassign_user_successful"), true);
		$this->ctrl->redirect($this, "showStaff");
	}


	public function removeFromEmployees() {
		if (!$this->ilAccess->checkAccess("write", "", $this->parent_object->getRefId())) {
			ilUtil::sendFailure($this->lng->txt("permission_denied"), true);
			$this->ctrl->redirect($this->parent_gui, "");
		}
		$this->parent_object->deassignUserFromEmployeeRole($_POST["obj_id"]);
		ilUtil::sendSuccess($this->lng->txt("deassign_user_successful"), true);
		$this->ctrl->redirect($this, "showStaff");
	}


	public function removeFromRole() {
		if (!$this->ilAccess->checkAccess("write", "", $this->parent_object->getRefId())) {
			ilUtil::sendFailure($this->lng->txt("permission_denied"), true);
			$this->ctrl->redirect($this->parent_gui, "");
		}
		global $rbacadmin;
		$arrObjIdRolId = explode("-", $_POST["obj_id-role_id"]);
		$rbacadmin->deassignUser($arrObjIdRolId[1], $arrObjIdRolId[0]);
		ilUtil::sendSuccess($this->lng->txt("deassign_user_successful"), true);
		$this->ctrl->redirect($this, "showOtherRoles");
	}


	public function setTabs() {
		$this->tabs_gui->addSubTab("show_staff", sprintf($this->lng->txt("local_staff"), $this->parent_object->getTitle()), $this->ctrl->getLinkTarget($this, "showStaff"));
		if ($this->ilAccess->checkAccess("view_learning_progress_rec", "", $this->parent_object->getRefId())) {
			$this->tabs_gui->addSubTab("show_staff_rec", sprintf($this->lng->txt("rec_staff"), $this->parent_object->getTitle()), $this->ctrl->getLinkTarget($this, "showStaffRec"));
		}
		if ($this->ilAccess->checkAccess("write", "", $this->parent_object->getRefId())) {
			$this->tabs_gui->addSubTab("show_other_roles", sprintf($this->lng->txt("local_other_roles"), $this->parent_object->getTitle()), $this->ctrl->getLinkTarget($this, "showOtherRoles"));
		}
	}

}
?>