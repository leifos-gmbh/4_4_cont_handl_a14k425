<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* This class represents a single choice wizard property in a property form.
*
* @author Helmut Schottmüller <ilias@aurealis.de> 
* @version $Id: class.ilAnswerWizardInputGUI.php 56134 2014-12-09 13:22:18Z mbecker $
* @ingroup	ServicesForm
*/
class ilAnswerWizardInputGUI extends ilTextInputGUI
{
	protected $values = array();
	protected $allowMove = false;
	protected $singleline = true;
	protected $qstObject = null;
	protected $minvalue = false;
	protected $minvalueShouldBeGreater = false;

	protected $disable_actions;
	protected $disable_text;

	/**
	 * @param mixed $disable_actions
	 *
	 * @return $this
	 */
	public function setDisableActions($disable_actions)
	{
		$this->disable_actions = $disable_actions;
		return $this;
	}

	/**
	 * @param mixed $disable_text
	 *
	 * @return $this
	 */
	public function setDisableText($disable_text)
	{
		$this->disable_text = $disable_text;
		return $this;
	}

	/**
	* Constructor
	*
	* @param	string	$a_title	Title
	* @param	string	$a_postvar	Post Variable
	*/
	function __construct($a_title = "", $a_postvar = "")
	{
		parent::__construct($a_title, $a_postvar);
		$this->setSize('25');
		$this->validationRegexp = "";
	}

	/**
	* Set Value.
	*
	* @param	string	$a_value	Value
	*/
	function setValue($a_value)
	{
		$this->values = array();
		if (is_array($a_value))
		{
			if (is_array($a_value['answer']))
			{
				foreach ($a_value['answer'] as $index => $value)
				{
					include_once "./Modules/TestQuestionPool/classes/class.assAnswerBinaryStateImage.php";
					$answer = new ASS_AnswerBinaryStateImage($value, $a_value['points'][$index], $index, 1, $a_value['imagename'][$index]);
					array_push($this->values, $answer);
				}
			}
		}
	}

	/**
	* Set Values
	*
	* @param	array	$a_value	Value
	*/
	function setValues($a_values)
	{
		$this->values = $a_values;
	}

	/**
	* Get Values
	*
	* @return	array	Values
	*/
	function getValues()
	{
		return $this->values;
	}

	/**
	* Set singleline
	*
	* @param	boolean	$a_value	Value
	*/
	function setSingleline($a_value)
	{
		$this->singleline = $a_value;
	}

	/**
	* Get singleline
	*
	* @return	boolean	Value
	*/
	function getSingleline()
	{
		return $this->singleline;
	}

	/**
	* Set question object
	*
	* @param	object	$a_value	test object
	*/
	function setQuestionObject($a_value)
	{
		$this->qstObject =& $a_value;
	}

	/**
	* Get question object
	*
	* @return	object	Value
	*/
	function getQuestionObject()
	{
		return $this->qstObject;
	}

	/**
	* Set allow move
	*
	* @param	boolean	$a_allow_move Allow move
	*/
	function setAllowMove($a_allow_move)
	{
		$this->allowMove = $a_allow_move;
	}

	/**
	* Get allow move
	*
	* @return	boolean	Allow move
	*/
	function getAllowMove()
	{
		return $this->allowMove;
	}

	/**
	 * Set minvalueShouldBeGreater
	 *
	 * @param	boolean	$a_bool	true if the minimum value should be greater than minvalue
	 */
	function setMinvalueShouldBeGreater($a_bool)
	{
		$this->minvalueShouldBeGreater = $a_bool;
	}

	/**
	 * Get minvalueShouldBeGreater
	 *
	 * @return	boolean	true if the minimum value should be greater than minvalue
	 */
	function minvalueShouldBeGreater()
	{
		return $this->minvalueShouldBeGreater;
	}
	/**
	 * Set Minimum Value.
	 *
	 * @param	float	$a_minvalue	Minimum Value
	 */
	function setMinValue($a_minvalue)
	{
		$this->minvalue = $a_minvalue;
	}

	/**
	 * Get Minimum Value.
	 *
	 * @return	float	Minimum Value
	 */
	function getMinValue()
	{
		return $this->minvalue;
	}
	/**
	* Check input, strip slashes etc. set alert, if input is not ok.
	*
	* @return	boolean		Input ok, true/false
	*/	
	function checkInput()
	{
		global $lng;
		if (is_array($_POST[$this->getPostVar()])) $_POST[$this->getPostVar()] = ilUtil::stripSlashesRecursive($_POST[$this->getPostVar()]);
		$foundvalues = $_POST[$this->getPostVar()];
		if (is_array($foundvalues))
		{
			// check answers
			if (is_array($foundvalues['answer']))
			{
				foreach ($foundvalues['answer'] as $aidx => $answervalue)
				{
					if ((strlen($answervalue)) == 0)
					{
						$this->setAlert($lng->txt("msg_input_is_required"));
						return FALSE;
					}
				}
			}
			// check points
			$max = 0;
			if (is_array($foundvalues['points']))
			{
				foreach ($foundvalues['points'] as $points)
				{
					if ($points > $max) $max = $points;
					if (((strlen($points)) == 0) || (!is_numeric($points))) 
					{
						$this->setAlert($lng->txt("form_msg_numeric_value_required"));
						return FALSE;
					}
					if ($this->minvalueShouldBeGreater())
					{
						if (trim($points) != "" &&
							$this->getMinValue() !== false &&
							$points <= $this->getMinValue())
						{
							$this->setAlert($lng->txt("form_msg_value_too_low"));

							return false;
						}
					}
					else
					{
						if (trim($points) != "" &&
							$this->getMinValue() !== false &&
							$points < $this->getMinValue())
						{
							$this->setAlert($lng->txt("form_msg_value_too_low"));

							return false;

						}
					}
				}
			}
			if ($max == 0)
			{
				$this->setAlert($lng->txt("enter_enough_positive_points"));
				return false;
			}
		}
		else
		{
			$this->setAlert($lng->txt("msg_input_is_required"));
			return FALSE;
		}
		
		return $this->checkSubItemsInput();
	}

	/**
	* Insert property html
	*
	* @return	int	Size
	*/
	function insert(&$a_tpl)
	{
		global $lng;
		
		$tpl = new ilTemplate("tpl.prop_answerwizardinput.html", true, true, "Modules/TestQuestionPool");
		$i = 0;
		foreach ($this->values as $value)
		{
			if ($this->getSingleline())
			{
				if (is_object($value))
				{
					$tpl->setCurrentBlock("prop_text_propval");
					$tpl->setVariable("PROPERTY_VALUE", ilUtil::prepareFormOutput($value->getAnswertext()));
					$tpl->parseCurrentBlock();
					$tpl->setCurrentBlock("prop_points_propval");
					$tpl->setVariable("PROPERTY_VALUE", ilUtil::prepareFormOutput($value->getPoints()));
					$tpl->parseCurrentBlock();
				}
				$tpl->setCurrentBlock('singleline');
				$tpl->setVariable("SIZE", $this->getSize());
				$tpl->setVariable("SINGLELINE_ID", $this->getPostVar() . "[answer][$i]");
				$tpl->setVariable("SINGLELINE_ROW_NUMBER", $i);
				$tpl->setVariable("SINGLELINE_POST_VAR", $this->getPostVar());
				$tpl->setVariable("MAXLENGTH", $this->getMaxLength());
				if ($this->getDisabled() || $this->disable_text)
				{
					$tpl->setVariable("DISABLED_SINGLELINE", " disabled=\"disabled\"");
				}
				$tpl->parseCurrentBlock();
			}
			else if (!$this->getSingleline())
			{
				if (is_object($value))
				{
					$tpl->setCurrentBlock("prop_points_propval");
					$tpl->setVariable("PROPERTY_VALUE", ilUtil::prepareFormOutput($value->getPoints()));
					$tpl->parseCurrentBlock();
				}
				$tpl->setCurrentBlock('multiline');
				$tpl->setVariable("PROPERTY_VALUE", $this->qstObject->prepareTextareaOutput($value->getAnswertext()));
				$tpl->setVariable("MULTILINE_ID", $this->getPostVar() . "[answer][$i]");
				$tpl->setVariable("MULTILINE_ROW_NUMBER", $i);
				$tpl->setVariable("MULTILINE_POST_VAR", $this->getPostVar());
				if ($this->getDisabled())
				{
					$tpl->setVariable("DISABLED_MULTILINE", " disabled=\"disabled\"");
				}
				$tpl->parseCurrentBlock();
			}
			if ($this->getAllowMove())
			{
				$tpl->setCurrentBlock("move");
				$tpl->setVariable("CMD_UP", "cmd[up" . $this->getFieldId() . "][$i]");
				$tpl->setVariable("CMD_DOWN", "cmd[down" . $this->getFieldId() . "][$i]");
				$tpl->setVariable("MOVE_ID", $this->getPostVar() . "[$i]");
				$tpl->setVariable("UP_BUTTON", ilUtil::getImagePath('a_up.png'));
				$tpl->setVariable("DOWN_BUTTON", ilUtil::getImagePath('a_down.png'));
				$tpl->parseCurrentBlock();
			}
			$tpl->setCurrentBlock("row");
			$class = ($i % 2 == 0) ? "even" : "odd";
			if ($i == 0) $class .= " first";
			if ($i == count($this->values)-1) $class .= " last";
			$tpl->setVariable("ROW_CLASS", $class);
			$tpl->setVariable("POST_VAR", $this->getPostVar());
			$tpl->setVariable("ROW_NUMBER", $i);
			if(!$this->disable_actions)
			{
				$tpl->setVariable( "ID", $this->getPostVar() . "[answer][$i]" );
				$tpl->setVariable( "POINTS_ID", $this->getPostVar() . "[points][$i]" );
				$tpl->setVariable( "CMD_ADD", "cmd[add" . $this->getFieldId() . "][$i]" );
				$tpl->setVariable( "CMD_REMOVE", "cmd[remove" . $this->getFieldId() . "][$i]" );
			}
			if ($this->getDisabled())
			{
				$tpl->setVariable("DISABLED_POINTS", " disabled=\"disabled\"");
			}
			if(!$this->disable_actions)
			{
				$tpl->setVariable( "ADD_BUTTON", ilUtil::getImagePath( 'edit_add.png' ) );
				$tpl->setVariable( "REMOVE_BUTTON", ilUtil::getImagePath( 'edit_remove.png' ) );
			}
			$tpl->parseCurrentBlock();
			$i++;
		}

		$tpl->setVariable("ELEMENT_ID", $this->getPostVar());
		$tpl->setVariable("ANSWER_TEXT", $lng->txt('answer_text'));
		$tpl->setVariable("POINTS_TEXT", $lng->txt('points'));
		if(!$this->disable_actions)
		{
			$tpl->setVariable("COMMANDS_TEXT", $lng->txt('actions'));
		}

		$a_tpl->setCurrentBlock("prop_generic");
		$a_tpl->setVariable("PROP_GENERIC", $tpl->get());
		$a_tpl->parseCurrentBlock();
		
		global $tpl;
		include_once "./Services/YUI/classes/class.ilYuiUtil.php";
		ilYuiUtil::initDomEvent();
		$tpl->addJavascript("./Modules/TestQuestionPool/templates/default/answerwizard.js");
	}
}
