<?php

//Based on CodeIgniter

class FormValidation {

	protected $_field_data			= array();
	protected $_config_rules		= array();
	protected $_error_array			= array();
	protected $_error_messages		= array();
	protected $_error_prefix		= '<p>';
	protected $_error_suffix		= '</p>';
	protected $error_string			= '';
	protected $_safe_form_data		= FALSE;
	protected $_xss_hash			= '';

	protected $_never_allowed_str = array(
		'document.cookie'	=> '[removed]',
		'document.write'	=> '[removed]',
		'.parentNode'		=> '[removed]',
		'.innerHTML'		=> '[removed]',
		'window.location'	=> '[removed]',
		'-moz-binding'		=> '[removed]',
		'<!--'				=> '&lt;!--',
		'-->'				=> '--&gt;',
		'<![CDATA['			=> '&lt;![CDATA[',
		'<comment>'			=> '&lt;comment&gt;'
	);

	protected $_never_allowed_regex = array(
		'javascript\s*:',
		'expression\s*(\(|&\#40;)', // CSS and IE
		'vbscript\s*:', // IE, surprise!
		'Redirect\s+302',
		"([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?"
	);

	protected $lang = array(
		'required'				=> "The %s field is required.",
		'isset'					=> "The %s field must have a value.",
		'valid_email'			=> "The %s field must contain a valid email address.",
		'valid_emails'			=> "The %s field must contain all valid email addresses.",
		'valid_url'				=> "The %s field must contain a valid URL.",
		'valid_ip'				=> "The %s field must contain a valid IP.",
		'min_length'			=> "The %s field must be at least %s characters in length.",
		'max_length'			=> "The %s field can not exceed %s characters in length.",
		'exact_length'			=> "The %s field must be exactly %s characters in length.",
		'alpha'					=> "The %s field may only contain alphabetical characters.",
		'alpha_numeric'			=> "The %s field may only contain alpha-numeric characters.",
		'alpha_dash'			=> "The %s field may only contain alpha-numeric characters, underscores, and dashes.",
		'numeric'				=> "The %s field must contain only numbers.",
		'is_numeric'			=> "The %s field must contain only numeric characters.",
		'integer'				=> "The %s field must contain an integer.",
		'regex_match'			=> "The %s field is not in the correct format.",
		'matches'				=> "The %s field does not match the %s field.",
		'is_unique' 			=> "The %s field must contain a unique value.",
		'is_natural'			=> "The %s field must contain only positive numbers.",
		'is_natural_no_zero'	=> "The %s field must contain a number greater than zero.",
		'decimal'				=> "The %s field must contain a decimal number.",
		'less_than'				=> "The %s field must contain a number less than %s.",
		'greater_than'			=> "The %s field must contain a number greater than %s."
	);

	/**
	 * Constructor
	 */
	public function __construct($rules = array())
	{
		// Validation rules can be stored in a config file.
		$this->_config_rules = $rules;

		$this->data_to_validate = &$_POST;

	}

	// --------------------------------------------------------------------

	// Jake's Function : return nice array of errors
	public function error_array(){
		return $this->_error_array;
	}

	// --------------------------------------------------------------------

	public function get_field_data(){
		return $this->_field_data;
	}

	/**
	 * Set Rules
	 *
	 * This function takes an array of field names and validation
	 * rules as input, validates the info, and stores it
	 */
	public function set_data(&$data){

		$this->data_to_validate = $data;

	}

	public function set_rules($field, $label = '', $rules = '')
	{
		//If you ran tests with this validator and are adding new rules you probably don't want old errors.
		$this->_clear_errors();

		// No reason to set rules if we have no POST data
		if (count($this->data_to_validate) == 0)
		{
			return $this;
		}

		// If an array was passed via the first parameter instead of indidual string
		// values we cycle through it and recursively call this function.
		if (is_array($field))
		{
			foreach ($field as $row)
			{
				// Houston, we have a problem...
				if ( ! isset($row['field']) OR ! isset($row['rules']))
				{
					continue;
				}

				// If the field label wasn't passed we use the field name
				$label = ( ! isset($row['label'])) ? $row['field'] : $row['label'];

				// Here we go!
				$this->set_rules($row['field'], $label, $row['rules']);
			}
			return $this;
		}

		// No fields? Nothing to do...
		if ( ! is_string($field) OR  ! is_string($rules) OR $field == '')
		{
			return $this;
		}

		// If no rule was passed then we assume label is the rule
		if($rules == ""){
			$rules = $label;
			$label = "";
		}

		// If the field label wasn't passed we use the field name
		$label = ($label == '') ? $field : $label;



		// Is the field name an array?  We test for the existence of a bracket "[" in
		// the field name to determine this.  If it is an array, we break it apart
		// into its components so that we can fetch the corresponding POST data later
		if (strpos($field, '[') !== FALSE AND preg_match_all('/\[(.*?)\]/', $field, $matches))
		{
			// Note: Due to a bug in current() that affects some versions
			// of PHP we can not pass function call directly into it
			$x = explode('[', $field);
			$indexes[] = current($x);

			for ($i = 0; $i < count($matches['0']); $i++)
			{
				if ($matches['1'][$i] != '')
				{
					$indexes[] = $matches['1'][$i];
				}
			}

			$is_array = TRUE;
		}
		else
		{
			$indexes	= array();
			$is_array	= FALSE;
		}

		// Build our master array
		$this->_field_data[$field] = array(
			'field'				=> $field,
			'label'				=> $label,
			'rules'				=> $rules,
			'is_array'			=> $is_array,
			'keys'				=> $indexes,
			'postdata'			=> NULL,
			'error'				=> ''
		);

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Error Message
	 *
	 * Lets users set their own error messages on the fly.  Note:  The key
	 * name has to match the  function name that it corresponds to.
	 *
	 */
	public function set_message($lang, $val = '')
	{
		if ( ! is_array($lang))
		{
			$lang = array($lang => $val);
		}

		$this->_error_messages = array_merge($this->_error_messages, $lang);

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set The Error Delimiter
	 *
	 * Permits a prefix/suffix to be added to each error message
	 *
	 */
	public function set_error_delimiters($prefix = '<p>', $suffix = '</p>')
	{
		$this->_error_prefix = $prefix;
		$this->_error_suffix = $suffix;

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Error Message
	 *
	 * Gets the error message associated with a particular field
	 *
	 */
	public function error($field = '', $prefix = '', $suffix = '')
	{
		if ( ! isset($this->_field_data[$field]['error']) OR $this->_field_data[$field]['error'] == '')
		{
			return '';
		}

		if ($prefix == '')
		{
			$prefix = $this->_error_prefix;
		}

		if ($suffix == '')
		{
			$suffix = $this->_error_suffix;
		}

		return $prefix.$this->_field_data[$field]['error'].$suffix;
	}

	// --------------------------------------------------------------------

	/**
	 * Error String
	 *
	 * Returns the error messages as a string, wrapped in the error delimiters
	 *
	 */
	public function error_string($prefix = '', $suffix = '')
	{
		// No errrors, validation passes!
		if (count($this->_error_array) === 0)
		{
			return '';
		}

		if ($prefix == '')
		{
			$prefix = $this->_error_prefix;
		}

		if ($suffix == '')
		{
			$suffix = $this->_error_suffix;
		}

		// Generate the error string
		$str = '';
		foreach ($this->_error_array as $val)
		{
			if ($val != '')
			{
				$str .= $prefix.$val.$suffix."\n";
			}
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Run the Validator
	 *
	 * This function does all the work.
	 *
	 */
	public function run($group = '')
	{
		// Do we even have any data to process?  Mm?
		if (count($this->data_to_validate) == 0)
		{
			return FALSE;
		}

		// Does the _field_data array containing the validation rules exist?
		// If not, we look to see if they were assigned via a config file
		if (count($this->_field_data) == 0)
		{
			// No validation rules?  We're done...
			if (count($this->_config_rules) == 0)
			{
				return FALSE;
			}

			// Is there a validation rule for the particular URI being accessed?
			$uri = ($group == '') ? trim($this->ruri_string(), '/') : $group;

			if ($uri != '' AND isset($this->_config_rules[$uri]))
			{
				$this->set_rules($this->_config_rules[$uri]);
			}
			else
			{
				$this->set_rules($this->_config_rules);
			}

			// We're we able to set the rules correctly?
			if (count($this->_field_data) == 0)
			{
				return FALSE;
			}
		}

		// Cycle through the rules for each field, match the
		// corresponding $this->data_to_validate item and test for errors
		foreach ($this->_field_data as $field => $row)
		{
			// Fetch the data from the corresponding $this->data_to_validate array and cache it in the _field_data array.
			// Depending on whether the field name is an array or a string will determine where we get it from.

			if ($row['is_array'] == TRUE)
			{
				$this->_field_data[$field]['postdata'] = $this->_reduce_array($this->data_to_validate, $row['keys']);
			}
			else
			{
				if (isset($this->data_to_validate[$field]) AND $this->data_to_validate[$field] != "")
				{
					$this->_field_data[$field]['postdata'] = $this->data_to_validate[$field];
				}
			}

			$this->_execute($row, explode('|', $row['rules']), $this->_field_data[$field]['postdata']);
		}

		// Did we end up with any errors?
		$total_errors = count($this->_error_array);

		if ($total_errors > 0)
		{
			$this->_safe_form_data = TRUE;
		}

		// Now we need to re-set the POST data with the new, processed data
		$this->_reset_post_array();

		// No errors, validation passes!
		if ($total_errors == 0)
		{
			return TRUE;
		}

		// Validation fails
		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Traverse a multidimensional $this->data_to_validate array index until the data is found
	 *
	 */
	protected function _reduce_array($array, $keys, $i = 0)
	{
		if (is_array($array))
		{
			if (isset($keys[$i]))
			{
				if (isset($array[$keys[$i]]))
				{
					$array = $this->_reduce_array($array[$keys[$i]], $keys, ($i+1));
				}
				else
				{
					return NULL;
				}
			}
			else
			{
				return $array;
			}
		}

		return $array;
	}

	// --------------------------------------------------------------------

	/**
	 * Re-populate the _POST array with our finalized and processed data
	 *
	 */
	protected function _reset_post_array()
	{
		foreach ($this->_field_data as $field => $row)
		{
			if ( ! is_null($row['postdata']))
			{
				if ($row['is_array'] == FALSE)
				{
					if (isset($this->data_to_validate[$row['field']]))
					{
						$this->data_to_validate[$row['field']] = $this->prep_for_form($row['postdata']);
					}
				}
				else
				{
					// start with a reference
					$post_ref =& $this->data_to_validate;

					// before we assign values, make a reference to the right POST key
					if (count($row['keys']) == 1)
					{
						$post_ref =& $post_ref[current($row['keys'])];
					}
					else
					{
						foreach ($row['keys'] as $val)
						{
							$post_ref =& $post_ref[$val];
						}
					}

					if (is_array($row['postdata']))
					{
						$array = array();
						foreach ($row['postdata'] as $k => $v)
						{
							$array[$k] = $this->prep_for_form($v);
						}

						$post_ref = $array;
					}
					else
					{
						$post_ref = $this->prep_for_form($row['postdata']);
					}
				}
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Executes the Validation routines
	 *
	 */
	protected function _execute($row, $rules, $postdata = NULL, $cycles = 0)
	{
		// If the $this->data_to_validate data is an array we will run a recursive call
		if (is_array($postdata))
		{
			foreach ($postdata as $key => $val)
			{
				$this->_execute($row, $rules, $val, $cycles);
				$cycles++;
			}

			return;
		}

		// --------------------------------------------------------------------

		// If the field is blank, but NOT required, no further tests are necessary
		$callback = FALSE;
		if ( ! in_array('required', $rules) AND is_null($postdata))
		{
			// Before we bail out, does the rule contain a callback?
			if (preg_match("/(callback_\w+(\[.*?\])?)/", implode(' ', $rules), $match))
			{
				$callback = TRUE;
				$rules = (array('1' => $match[1]));
			}
			else
			{
				return;
			}
		}

		// --------------------------------------------------------------------

		// Isset Test. Typically this rule will only apply to checkboxes.
		if (is_null($postdata) AND $callback == FALSE)
		{
			if (in_array('isset', $rules, TRUE) OR in_array('required', $rules))
			{
				// Set the message type
				$type = (in_array('required', $rules)) ? 'required' : 'isset';

				if ( ! isset($this->_error_messages[$type]))
				{
					if (FALSE === ($line = $this->getLangLine($type)))
					{
						$line = 'The field was not set';
					}
				}
				else
				{
					$line = $this->_error_messages[$type];
				}

				// Build the error message
				$message = sprintf($line, $this->_translate_fieldname($row['label']));

				// Save the error message
				$this->_field_data[$row['field']]['error'] = $message;

				if ( ! isset($this->_error_array[$row['field']]))
				{
					$this->_error_array[$row['field']] = $message;
				}
			}

			return;
		}

		// --------------------------------------------------------------------

		// Cycle through each rule and run it
		foreach ($rules As $rule)
		{
			$_in_array = FALSE;

			// We set the $postdata variable with the current data in our master array so that
			// each cycle of the loop is dealing with the processed data from the last cycle
			if ($row['is_array'] == TRUE AND is_array($this->_field_data[$row['field']]['postdata']))
			{
				// We shouldn't need this safety, but just in case there isn't an array index
				// associated with this cycle we'll bail out
				if ( ! isset($this->_field_data[$row['field']]['postdata'][$cycles]))
				{
					continue;
				}

				$postdata = $this->_field_data[$row['field']]['postdata'][$cycles];
				$_in_array = TRUE;
			}
			else
			{
				$postdata = $this->_field_data[$row['field']]['postdata'];
			}

			// --------------------------------------------------------------------

			// Is the rule a callback?
			/*
			$callback = FALSE;
			if (substr($rule, 0, 9) == 'callback_')
			{
				$rule = substr($rule, 9);
				$callback = TRUE;
			}
			*/

			// Strip the parameter (if exists) from the rule
			// Rules can contain a parameter: max_length[5]
			$param = FALSE;
			if (preg_match("/(.*?)\[(.*)\]/", $rule, $match))
			{
				$rule	= $match[1];
				$param	= $match[2];
			}
			/*
			// Call the function that corresponds to the rule
			if ($callback === TRUE)
			{
				if ( ! method_exists($this->CI, $rule))
				{
					continue;
				}

				// Run the function and grab the result
				$result = $this->CI->$rule($postdata, $param);

				// Re-assign the result to the master data array
				if ($_in_array == TRUE)
				{
					$this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
				}
				else
				{
					$this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
				}

				// If the field isn't required and we just processed a callback we'll move on...
				if ( ! in_array('required', $rules, TRUE) AND $result !== FALSE)
				{
					continue;
				}
			}
			else
			{*/
				if ( ! method_exists($this, $rule))
				{
					// If our own wrapper function doesn't exist we see if a native PHP function does.
					// Users can use any native PHP function call that has one param.
					if (function_exists($rule))
					{
						$result = $rule($postdata);

						if ($_in_array == TRUE)
						{
							$this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
						}
						else
						{
							$this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
						}
					}
					else
					{
						$result = FALSE;
						$this->_error_array[$row['field']] = "Function ".$rule." not found!";
					}

					continue;
				}

				$result = $this->$rule($postdata, $param);

				if ($_in_array == TRUE)
				{
					$this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
				}
				else
				{
					$this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
				}
			//}

			// Did the rule test negatively?  If so, grab the error.
			if ($result === FALSE)
			{
				if ( ! isset($this->_error_messages[$rule]))
				{
					if (FALSE === ($line = $this->getLangLine($rule)))
					{
						$line = 'Unable to access an error message corresponding to your field name.';
					}
				}
				else
				{
					$line = $this->_error_messages[$rule];
				}

				// Is the parameter we are inserting into the error message the name
				// of another field?  If so we need to grab its "field label"
				if (isset($this->_field_data[$param]) AND isset($this->_field_data[$param]['label']))
				{
					$param = $this->_translate_fieldname($this->_field_data[$param]['label']);
				}

				// Build the error message
				$message = sprintf($line, $this->_translate_fieldname($row['label']), $param);

				// Save the error message
				$this->_field_data[$row['field']]['error'] = $message;

				if ( ! isset($this->_error_array[$row['field']]))
				{
					$this->_error_array[$row['field']] = $message;
				}

				return;
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Translate a field name
	 *
	 */
	protected function _translate_fieldname($fieldname)
	{
		// Do we need to translate the field name?
		// We look for the prefix lang: to determine this
		if (substr($fieldname, 0, 5) == 'lang:')
		{
			// Grab the variable
			$line = substr($fieldname, 5);

			// Were we able to translate the field name?  If not we use $line
			if (FALSE === ($fieldname = $this->getLangLine($line)))
			{
				return $line;
			}
		}

		return $fieldname;
	}

	// --------------------------------------------------------------------

	/**
	 * Get the value from a form
	 *
	 * Permits you to repopulate a form field with the value it was submitted
	 * with, or, if that value doesn't exist, with the default
	 *
	 */
	public function set_value($field = '', $default = '')
	{
		if ( ! isset($this->_field_data[$field]))
		{
			return $default;
		}

		// If the data is an array output them one at a time.
		//     E.g: form_input('name[]', set_value('name[]');
		if (is_array($this->_field_data[$field]['postdata']))
		{
			return array_shift($this->_field_data[$field]['postdata']);
		}

		return $this->_field_data[$field]['postdata'];
	}

	// --------------------------------------------------------------------

	/**
	 * Set Select
	 *
	 * Enables pull-down lists to be set to the value the user
	 * selected in the event of an error
	 *
	 */
	public function set_select($field = '', $value = '', $default = FALSE)
	{
		if ( ! isset($this->_field_data[$field]) OR ! isset($this->_field_data[$field]['postdata']))
		{
			if ($default === TRUE AND count($this->_field_data) === 0)
			{
				return ' selected="selected"';
			}
			return '';
		}

		$field = $this->_field_data[$field]['postdata'];

		if (is_array($field))
		{
			if ( ! in_array($value, $field))
			{
				return '';
			}
		}
		else
		{
			if (($field == '' OR $value == '') OR ($field != $value))
			{
				return '';
			}
		}

		return ' selected="selected"';
	}

	// --------------------------------------------------------------------

	/**
	 * Set Radio
	 *
	 * Enables radio buttons to be set to the value the user
	 * selected in the event of an error
	 *
	 */
	public function set_radio($field = '', $value = '', $default = FALSE)
	{
		if ( ! isset($this->_field_data[$field]) OR ! isset($this->_field_data[$field]['postdata']))
		{
			if ($default === TRUE AND count($this->_field_data) === 0)
			{
				return ' checked="checked"';
			}
			return '';
		}

		$field = $this->_field_data[$field]['postdata'];

		if (is_array($field))
		{
			if ( ! in_array($value, $field))
			{
				return '';
			}
		}
		else
		{
			if (($field == '' OR $value == '') OR ($field != $value))
			{
				return '';
			}
		}

		return ' checked="checked"';
	}

	// --------------------------------------------------------------------

	/**
	 * Set Checkbox
	 *
	 * Enables checkboxes to be set to the value the user
	 * selected in the event of an error
	 *
	 */
	public function set_checkbox($field = '', $value = '', $default = FALSE)
	{
		if ( ! isset($this->_field_data[$field]) OR ! isset($this->_field_data[$field]['postdata']))
		{
			if ($default === TRUE AND count($this->_field_data) === 0)
			{
				return ' checked="checked"';
			}
			return '';
		}

		$field = $this->_field_data[$field]['postdata'];

		if (is_array($field))
		{
			if ( ! in_array($value, $field))
			{
				return '';
			}
		}
		else
		{
			if (($field == '' OR $value == '') OR ($field != $value))
			{
				return '';
			}
		}

		return ' checked="checked"';
	}

	// --------------------------------------------------------------------

	/**
	 * Required
	 *
	 */
	public function required($str)
	{
		if ( ! is_array($str))
		{
			return (trim($str) == '') ? FALSE : TRUE;
		}
		else
		{
			return ( ! empty($str));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Performs a Regular Expression match test.
	 *
	 */
	public function regex_match($str, $regex)
	{
		if ( ! preg_match($regex, $str))
		{
			return FALSE;
		}

		return  TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Match one field to another
	 *
	 */
	public function matches($str, $field)
	{
		if ( ! isset($this->data_to_validate[$field]))
		{
			return FALSE;
		}

		$field = $this->data_to_validate[$field];

		return ($str !== $field) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Minimum Length
	 *
	 */
	public function min_length($str, $val)
	{
		if (preg_match("/[^0-9]/", $val))
		{
			return FALSE;
		}

		if (function_exists('mb_strlen'))
		{
			return (mb_strlen($str) < $val) ? FALSE : TRUE;
		}

		return (strlen($str) < $val) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Max Length
	 *
	 */
	public function max_length($str, $val)
	{
		if (preg_match("/[^0-9]/", $val))
		{
			return FALSE;
		}

		if (function_exists('mb_strlen'))
		{
			return (mb_strlen($str) > $val) ? FALSE : TRUE;
		}

		return (strlen($str) > $val) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Exact Length
	 *
	 */
	public function exact_length($str, $val)
	{
		if (preg_match("/[^0-9]/", $val))
		{
			return FALSE;
		}

		if (function_exists('mb_strlen'))
		{
			return (mb_strlen($str) != $val) ? FALSE : TRUE;
		}

		return (strlen($str) != $val) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Valid Email
	 *
	 */
	public function valid_email($str)
	{
		return ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $str)) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Valid Emails
	 *
	 */
	public function valid_emails($str)
	{
		if (strpos($str, ',') === FALSE)
		{
			return $this->valid_email(trim($str));
		}

		foreach (explode(',', $str) as $email)
		{
			if (trim($email) != '' && $this->valid_email(trim($email)) === FALSE)
			{
				return FALSE;
			}
		}

		return TRUE;
	}


	// --------------------------------------------------------------------

	//Validate IP Address
	public function valid_ip($ip, $which = '')
	{
		$which = strtolower($which);

		// First check if filter_var is available
		if (is_callable('filter_var'))
		{
			switch ($which) {
				case 'ipv4':
					$flag = FILTER_FLAG_IPV4;
					break;
				case 'ipv6':
					$flag = FILTER_FLAG_IPV6;
					break;
				default:
					$flag = '';
					break;
			}

			return (bool) filter_var($ip, FILTER_VALIDATE_IP, $flag);
		}

		if ($which !== 'ipv6' && $which !== 'ipv4')
		{
			if (strpos($ip, ':') !== FALSE)
			{
				$which = 'ipv6';
			}
			elseif (strpos($ip, '.') !== FALSE)
			{
				$which = 'ipv4';
			}
			else
			{
				return FALSE;
			}
		}

		$func = '_valid_'.$which;
		return $this->$func($ip);
	}

	// --------------------------------------------------------------------

	/**
	* Validate IPv4 Address
	*
	* Updated version suggested by Geert De Deckere
	*/
	protected function _valid_ipv4($ip)
	{
		$ip_segments = explode('.', $ip);

		// Always 4 segments needed
		if (count($ip_segments) !== 4)
		{
			return FALSE;
		}
		// IP can not start with 0
		if ($ip_segments[0][0] == '0')
		{
			return FALSE;
		}

		// Check each segment
		foreach ($ip_segments as $segment)
		{
			// IP segments must be digits and can not be
			// longer than 3 digits or greater then 255
			if ($segment == '' OR preg_match("/[^0-9]/", $segment) OR $segment > 255 OR strlen($segment) > 3)
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	* Validate IPv6 Address
	*/
	protected function _valid_ipv6($str)
	{
		// 8 groups, separated by :
		// 0-ffff per group
		// one set of consecutive 0 groups can be collapsed to ::

		$groups = 8;
		$collapsed = FALSE;

		$chunks = array_filter(
			preg_split('/(:{1,2})/', $str, NULL, PREG_SPLIT_DELIM_CAPTURE)
		);

		// Rule out easy nonsense
		if (current($chunks) == ':' OR end($chunks) == ':')
		{
			return FALSE;
		}

		// PHP supports IPv4-mapped IPv6 addresses, so we'll expect those as well
		if (strpos(end($chunks), '.') !== FALSE)
		{
			$ipv4 = array_pop($chunks);

			if ( ! $this->_valid_ipv4($ipv4))
			{
				return FALSE;
			}

			$groups--;
		}

		while ($seg = array_pop($chunks))
		{
			if ($seg[0] == ':')
			{
				if (--$groups == 0)
				{
					return FALSE;	// too many groups
				}

				if (strlen($seg) > 2)
				{
					return FALSE;	// long separator
				}

				if ($seg == '::')
				{
					if ($collapsed)
					{
						return FALSE;	// multiple collapsed
					}

					$collapsed = TRUE;
				}
			}
			elseif (preg_match("/[^0-9a-f]/i", $seg) OR strlen($seg) > 4)
			{
				return FALSE; // invalid segment
			}
		}

		return $collapsed OR $groups == 1;
	}

	/**
	 * Alpha
	 *
	 */
	public function alpha($str)
	{
		return ( ! preg_match("/^([a-z])+$/i", $str)) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Alpha-numeric
	 *
	 */
	public function alpha_numeric($str)
	{
		return ( ! preg_match("/^([a-z0-9])+$/i", $str)) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Alpha-numeric with underscores and dashes
	 *
	 */
	public function alpha_dash($str)
	{
		return ( ! preg_match("/^([-a-z0-9_-])+$/i", $str)) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Numeric
	 * No scientific notation allowed apparently
	 */
	public function numeric($str)
	{
		return (bool)preg_match( '/^[\-+]?[0-9]*\.?[0-9]+$/', $str);

	}

	// --------------------------------------------------------------------

	/**
	 * Is Numeric
	 *
	 */
	public function is_numeric($str)
	{
		return ( ! is_numeric($str)) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Integer
	 *
	 */
	public function integer($str)
	{
		return (bool) preg_match('/^[\-+]?[0-9]+$/', $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Decimal number
	 *
	 */
	public function decimal($str)
	{
		return (bool) preg_match('/^[\-+]?[0-9]+\.[0-9]+$/', $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Greather than
	 *
	 */
	public function greater_than($str, $min)
	{
		if ( ! is_numeric($str))
		{
			return FALSE;
		}
		return $str > $min;
	}

	// --------------------------------------------------------------------

	/**
	 * Less than
	 *
	 */
	public function less_than($str, $max)
	{
		if ( ! is_numeric($str))
		{
			return FALSE;
		}
		return $str < $max;
	}

	// --------------------------------------------------------------------

	/**
	 * Is a Natural number  (0,1,2,3, etc.)
	 *
	 */
	public function is_natural($str)
	{
		return (bool) preg_match( '/^[0-9]+$/', $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Is a Natural number, but not a zero  (1,2,3, etc.)
	 *
	 */
	public function is_natural_no_zero($str)
	{
		if ( ! preg_match( '/^[0-9]+$/', $str))
		{
			return FALSE;
		}

		if ($str == 0)
		{
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Valid Base64
	 *
	 * Tests a string for characters outside of the Base64 alphabet
	 * as defined by RFC 2045 http://www.faqs.org/rfcs/rfc2045
	 *
	 */
	public function valid_base64($str)
	{
		return (bool) ! preg_match('/[^a-zA-Z0-9\/\+=]/', $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Prep data for form
	 *
	 * This function allows HTML to be safely shown in a form.
	 * Special characters are converted.
	 *
	 */
	public function prep_for_form($data = '')
	{
		if (is_array($data))
		{
			foreach ($data as $key => $val)
			{
				$data[$key] = $this->prep_for_form($val);
			}

			return $data;
		}

		if ($this->_safe_form_data == FALSE OR $data === '')
		{
			return $data;
		}

		return str_replace(array("'", '"', '<', '>'), array("&#39;", "&quot;", '&lt;', '&gt;'), stripslashes($data));
	}

	// --------------------------------------------------------------------

	//Prep URL
	public function prep_url($str = '')
	{
		if ($str == 'http://' OR $str == '')
		{
			return '';
		}

		if (substr($str, 0, 7) != 'http://' && substr($str, 0, 8) != 'https://')
		{
			$str = 'http://'.$str;
		}

		return $str;
	}

	// --------------------------------------------------------------------

	//Strip Image Tags
	public function strip_image_tags($str)
	{
		$str = preg_replace("#<img\s+.*?src\s*=\s*[\"'](.+?)[\"'].*?\>#", "\\1", $str);
		$str = preg_replace("#<img\s+.*?src\s*=\s*(.+?).*?\>#", "\\1", $str);
		return $str;
	}

	// --------------------------------------------------------------------

	//XSS Clean
	public function xss_clean($str , $is_image = FALSE)
	{
		/*
		 * Is the string an array?
		 *
		 */
		if (is_array($str))
		{
			while (list($key) = each($str))
			{
				$str[$key] = $this->xss_clean($str[$key]);
			}

			return $str;
		}

		/*
		 * Remove Invisible Characters
		 */
		$str = $this->remove_invisible_characters($str);

		// Validate Entities in URLs
		$str = $this->_validate_entities($str);

		/*
		 * URL Decode
		 *
		 * Just in case stuff like this is submitted:
		 *
		 * <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
		 *
		 * Note: Use rawurldecode() so it does not remove plus signs
		 *
		 */
		$str = rawurldecode($str);

		/*
		 * Convert character entities to ASCII
		 *
		 * This permits our tests below to work reliably.
		 * We only convert entities that are within tags since
		 * these are the ones that will pose security problems.
		 *
		 */

		$str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si", array($this, '_convert_attribute'), $str);

		$str = preg_replace_callback("/<\w+.*?(?=>|<|$)/si", array($this, '_decode_entity'), $str);

		/*
		 * Remove Invisible Characters Again!
		 */
		$str = $this->remove_invisible_characters($str);

		/*
		 * Convert all tabs to spaces
		 *
		 * This prevents strings like this: ja	vascript
		 * NOTE: we deal with spaces between characters later.
		 * NOTE: preg_replace was found to be amazingly slow here on
		 * large blocks of data, so we use str_replace.
		 */

		if (strpos($str, "\t") !== FALSE)
		{
			$str = str_replace("\t", ' ', $str);
		}

		/*
		 * Capture converted string for later comparison
		 */
		$converted_string = $str;

		// Remove Strings that are never allowed
		$str = $this->_do_never_allowed($str);

		/*
		 * Makes PHP tags safe
		 *
		 * Note: XML tags are inadvertently replaced too:
		 *
		 * <?xml
		 *
		 * But it doesn't seem to pose a problem.
		 */
		if ($is_image === TRUE)
		{
			// Images have a tendency to have the PHP short opening and
			// closing tags every so often so we skip those and only
			// do the long opening tags.
			$str = preg_replace('/<\?(php)/i', "&lt;?\\1", $str);
		}
		else
		{
			$str = str_replace(array('<?', '?'.'>'),  array('&lt;?', '?&gt;'), $str);
		}

		/*
		 * Compact any exploded words
		 *
		 * This corrects words like:  j a v a s c r i p t
		 * These words are compacted back to their correct state.
		 */
		$words = array(
			'javascript', 'expression', 'vbscript', 'script', 'base64',
			'applet', 'alert', 'document', 'write', 'cookie', 'window'
		);

		foreach ($words as $word)
		{
			$temp = '';

			for ($i = 0, $wordlen = strlen($word); $i < $wordlen; $i++)
			{
				$temp .= substr($word, $i, 1)."\s*";
			}

			// We only want to do this when it is followed by a non-word character
			// That way valid stuff like "dealer to" does not become "dealerto"
			$str = preg_replace_callback('#('.substr($temp, 0, -3).')(\W)#is', array($this, '_compact_exploded_words'), $str);
		}

		/*
		 * Remove disallowed Javascript in links or img tags
		 * We used to do some version comparisons and use of stripos for PHP5,
		 * but it is dog slow compared to these simplified non-capturing
		 * preg_match(), especially if the pattern exists in the string
		 */
		do
		{
			$original = $str;

			if (preg_match("/<a/i", $str))
			{
				$str = preg_replace_callback("#<a\s+([^>]*?)(>|$)#si", array($this, '_js_link_removal'), $str);
			}

			if (preg_match("/<img/i", $str))
			{
				$str = preg_replace_callback("#<img\s+([^>]*?)(\s?/?>|$)#si", array($this, '_js_img_removal'), $str);
			}

			if (preg_match("/script/i", $str) OR preg_match("/xss/i", $str))
			{
				$str = preg_replace("#<(/*)(script|xss)(.*?)\>#si", '[removed]', $str);
			}
		}
		while($original != $str);

		unset($original);

		// Remove evil attributes such as style, onclick and xmlns
		$str = $this->_remove_evil_attributes($str, $is_image);

		/*
		 * Sanitize naughty HTML elements
		 *
		 * If a tag containing any of the words in the list
		 * below is found, the tag gets converted to entities.
		 *
		 * So this: <blink>
		 * Becomes: &lt;blink&gt;
		 */
		$naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
		$str = preg_replace_callback('#<(/*\s*)('.$naughty.')([^><]*)([><]*)#is', array($this, '_sanitize_naughty_html'), $str);

		/*
		 * Sanitize naughty scripting elements
		 *
		 * Similar to above, only instead of looking for
		 * tags it looks for PHP and JavaScript commands
		 * that are disallowed.  Rather than removing the
		 * code, it simply converts the parenthesis to entities
		 * rendering the code un-executable.
		 *
		 * For example:	eval('some code')
		 * Becomes:		eval&#40;'some code'&#41;
		 */
		$str = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str);


		// Final clean up
		// This adds a bit of extra precaution in case
		// something got through the above filters
		$str = $this->_do_never_allowed($str);

		/*
		 * Images are Handled in a Special Way
		 * - Essentially, we want to know that after all of the character
		 * conversion is done whether any unwanted, likely XSS, code was found.
		 * If not, we return TRUE, as the image is clean.
		 * However, if the string post-conversion does not matched the
		 * string post-removal of XSS, then it fails, as there was unwanted XSS
		 * code found and removed/changed during processing.
		 */

		if ($is_image === TRUE)
		{
			return ($str == $converted_string) ? TRUE: FALSE;
		}

		return $str;
	}

	// ----------------------------------------------------------------------

	/**
	 * Do Never Allowed
	 *
	 * A utility function for xss_clean()
	 */
	protected function _do_never_allowed($str)
	{
		$str = str_replace(array_keys($this->_never_allowed_str), $this->_never_allowed_str, $str);

		foreach ($this->_never_allowed_regex as $regex)
		{
			$str = preg_replace('#'.$regex.'#is', '[removed]', $str);
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Validate URL entities
	 *
	 * Called by xss_clean()
	 */
	protected function _validate_entities($str)
	{
		/*
		 * Protect GET variables in URLs
		 */

		 // 901119URL5918AMP18930PROTECT8198

		$str = preg_replace('|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-]+)|i', $this->xss_hash()."\\1=\\2", $str);

		/*
		 * Validate standard character entities
		 *
		 * Add a semicolon if missing.  We do this to enable
		 * the conversion of entities to ASCII later.
		 *
		 */
		$str = preg_replace('#(&\#?[0-9a-z]{2,})([\x00-\x20])*;?#i', "\\1;\\2", $str);

		/*
		 * Validate UTF16 two byte encoding (x00)
		 *
		 * Just as above, adds a semicolon if missing.
		 *
		 */
		$str = preg_replace('#(&\#x?)([0-9A-F]+);?#i',"\\1\\2;",$str);

		/*
		 * Un-Protect GET variables in URLs
		 */
		$str = str_replace($this->xss_hash(), '&', $str);

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Remove Invisible Characters
	 *
	 * This prevents sandwiching null characters
	 * between ascii characters, like Java\0script.
	 */
	protected function remove_invisible_characters($str, $url_encoded = TRUE)
	{
		$non_displayables = array();

		// every control character except newline (dec 10)
		// carriage return (dec 13), and horizontal tab (dec 09)
		
		if ($url_encoded)
		{
			$non_displayables[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
			$non_displayables[] = '/%1[0-9a-f]/';	// url encoded 16-31
		}
		
		$non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

		do
		{
			$str = preg_replace($non_displayables, '', $str, -1, $count);
		}
		while ($count);

		return $str;
	}

	// --------------------------------------------------------------------

	/*
	 * Remove Evil HTML Attributes (like evenhandlers and style)
	 *
	 * It removes the evil attribute and either:
	 * 	- Everything up until a space
	 *		For example, everything between the pipes:
	 *		<a |style=document.write('hello');alert('world');| class=link>
	 * 	- Everything inside the quotes
	 *		For example, everything between the pipes:
	 *		<a |style="document.write('hello'); alert('world');"| class="link">
	 */
	protected function _remove_evil_attributes($str, $is_image)
	{
		// All javascript event handlers (e.g. onload, onclick, onmouseover), style, and xmlns
		$evil_attributes = array('on\w*', 'style', 'xmlns', 'formaction');

		if ($is_image === TRUE)
		{
			/*
			 * Adobe Photoshop puts XML metadata into JFIF images, 
			 * including namespacing, so we have to allow this for images.
			 */
			unset($evil_attributes[array_search('xmlns', $evil_attributes)]);
		}

		do {
			$count = 0;
			$attribs = array();

			// find occurrences of illegal attribute strings with quotes (042 and 047 are octal quotes)
			preg_match_all('/('.implode('|', $evil_attributes).')\s*=\s*(\042|\047)([^\\2]*?)(\\2)/is', $str, $matches, PREG_SET_ORDER);

			foreach ($matches as $attr)
			{
				$attribs[] = preg_quote($attr[0], '/');
			}

			// find occurrences of illegal attribute strings without quotes
			preg_match_all('/('.implode('|', $evil_attributes).')\s*=\s*([^\s>]*)/is', $str, $matches, PREG_SET_ORDER);

			foreach ($matches as $attr)
			{
				$attribs[] = preg_quote($attr[0], '/');
			}

			// replace illegal attribute strings that are inside an html tag
			if (count($attribs) > 0)
			{
				$str = preg_replace('/(<?)(\/?[^><]+?)([^A-Za-z<>\-])(.*?)('.implode('|', $attribs).')(.*?)([\s><]?)([><]*)/i', '$1$2 $4$6$7$8', $str, -1, $count);
			}

		} while ($count);

		return $str;
	}

	// --------------------------------------------------------------------

	//Convert PHP tags to entities
	public function encode_php_tags($str)
	{
		return str_replace(array('<?php', '<?PHP', '<?', '?>'),  array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), $str);
	}

	// --------------------------------------------------------------------

	//Fetch the entire Re-routed URI string
	protected function ruri_string()
	{
		return '/'.implode('/', $this->rsegment_array());
	}

	// --------------------------------------------------------------------

	//Random Hash for protecting URLs
	public function xss_hash()
	{
		if ($this->_xss_hash == '')
		{
			mt_srand();
			$this->_xss_hash = md5(time() + mt_rand(0, 1999999999));
		}

		return $this->_xss_hash;
	}

	// --------------------------------------------------------------------

	//Attribute Conversion
	//Used as a callback for XSS Clean
	protected function _convert_attribute($match)
	{
		return str_replace(array('>', '<', '\\'), array('&gt;', '&lt;', '\\\\'), $match[0]);
	}

	// --------------------------------------------------------------------

	//HTML Entity Decode Callback
	//Used as a callback for XSS Clean
	protected function _decode_entity($match)
	{
		return $this->entity_decode($match[0], strtoupper('UTF-8'));
	}

	// --------------------------------------------------------------------

	/**
	 * HTML Entities Decode
	 *
	 * This function is a replacement for html_entity_decode()
	 *
	 * The reason we are not using html_entity_decode() by itself is because
	 * while it is not technically correct to leave out the semicolon
	 * at the end of an entity most browsers will still interpret the entity
	 * correctly.  html_entity_decode() does not convert entities without
	 * semicolons, so we are left with our own little solution here. Bummer.
	 *
	 */
	public function entity_decode($str, $charset='UTF-8')
	{
		if (stristr($str, '&') === FALSE)
		{
			return $str;
		}

		$str = html_entity_decode($str, ENT_COMPAT, $charset);
		$str = preg_replace('~&#x(0*[0-9a-f]{2,5})~ei', 'chr(hexdec("\\1"))', $str);
		return preg_replace('~&#([0-9]{2,4})~e', 'chr(\\1)', $str);
	}

	// ----------------------------------------------------------------

	/*
	 * Compact Exploded Words
	 *
	 * Callback function for xss_clean() to remove whitespace from
	 * things like j a v a s c r i p t
	 *
	 */
	protected function _compact_exploded_words($matches)
	{
		return preg_replace('/\s+/s', '', $matches[1]).$matches[2];
	}

		// --------------------------------------------------------------------

	/**
	 * Sanitize Naughty HTML
	 *
	 * Callback function for xss_clean() to remove naughty HTML elements
	 */
	protected function _sanitize_naughty_html($matches)
	{
		// encode opening brace
		$str = '&lt;'.$matches[1].$matches[2].$matches[3];

		// encode captured opening or closing brace to prevent recursive vectors
		$str .= str_replace(array('>', '<'), array('&gt;', '&lt;'),
							$matches[4]);

		return $str;
	}

	// --------------------------------------------------------------------

	protected function getLangLine($line){
		if(array_key_exists($line,$this->lang)){
			return $this->lang[$line];
		}else{
			return false;
		}
	}

	private function _clear_errors()
	{
		$this->_error_array = array();
		$this->_error_messages = array();
	}

}