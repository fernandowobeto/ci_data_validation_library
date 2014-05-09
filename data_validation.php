<?php

if(!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		Fernando Wobeto
 * @copyright	Copyright (c) 2014
 * @license
 * @link		http://www.fernandowobeto.com
 */
/*

  VALIDATIONS ALLOWED

  'type'=>'integer',
  'type'=>'bigint',
  'type'=>'digit',
  'type'=>'varchar',
  'type'=>'text',
  'type'=>'numeric',
  'type'=>'alpha',
  'type'=>'alpha_numeric',
  'type'=>'date',
  'type'=>'boolean',
  'type'=>'email',
  'type'=>'emails',
  'type'=>'ip',
  'type'=>'url',

  'regex_match'=>'/^(ablubla)$/',
  'maxlength'=>25,
  'minlength'=>5,
  'exactlength'=>7,
  'greaterthan'=>5,
  'lessthan'=>30,
  'inlist'=>array(5,4,'N'),
  'required'=>true

 */

class Data_validation{

	private $_validation_lang_file = 'form_validation';
	private $_crud_type = 'insert';
	private $_rules;
	private $_original_data;
	private $_errors = array();
	private $_order_to_validate = array('required','type','minlength','maxlength','exactlength','lessthan','greaterthan');

	public function __construct(){
		$this->CI = & get_instance();
	}

	public function set_crud_type($type){
		$allowed = array('insert','update');

		if(!in_array($type,$allowed)){
			return false;
		}
		$this->_crud_type = $type;
		return true;
	}

	public function set_rules(Array $validate){
		$this->_rules	= $validate;
	}

	public function validate(Array $data = array()){

		if(!isset($this->_rules)){
			throw new Exception('no rules');
		}

		$this->_original_data = $data;

		// load validation lang file
		$this->CI->lang->load($this->_validation_lang_file);

		foreach($this->_rules AS $field=> $definitions){
			$this->_execute($field,$definitions);
		}
		// error?
		if(count($this->_errors)){
			//yes 
			return false;
		}
		// no
		return true;
	}

	public function get_validation_errors(){
		return $this->_errors;
	}

	private function _execute($field,$definitions){
		$field_rules	= $definitions['rules'];
		$merged			= array_merge(array_flip($this->_order_to_validate),$field_rules);
		$field_rules	= array_diff_assoc($merged,array_flip($this->_order_to_validate));

		$field_value = isset($this->_original_data[$field])?$this->_original_data[$field]:NULL;
		//se o campo não é obrigatorio e o valor está setado como null não efetua acao alguma
		if(!isset($field_rules['required'])||$field_rules['required']===FALSE){
			if(is_null($field_value))
				return;
		}
		//se for um update e o campo não tiver sido setado não efetua acao alguma
		if($this->_crud_type=='update'&&!isset($field_value))
			return;
		//analiza as demais regras
		foreach($field_rules AS $rule_name=> $param){

			$rule_name = $rule_name=='type'?$param:$rule_name;

			if(method_exists($this,$rule_name)){

				$result = $this->$rule_name($field_value,$param);
				if(!$result){
					$this->_set_error($field,$rule_name,$definitions['label'],$param);
					return;
				}
			}
		}
	}

	private function _set_error($field,$rule_name,$label,$param){
		//resgata o tipo padrao de chave do array definido no form_validation_lang
		$type = $this->_get_key_lang($rule_name);
		//verifica se existe mensagem de erro no arquivo de lang de acordo com o type
		if(FALSE===($error_message = $this->CI->lang->line($type))){
			$error_message = 'No message error to '.$type;
		}
		// Constroi a mensagem de erro
		$message = sprintf($error_message,$label,$param);
		//seta mensagem de erro no array de erros encontrados
		$this->_errors[$field] = $message;
		return true;
	}

	private function _get_key_lang($type){
		$options = array(
			 'integer'=>'integer',
			 'numeric'=>'numeric',
			 'alpha'=>'alpha',
			 'alpha_numeric'=>'alpha_numeric',
			 'bigint'=>'integer',
			 'date'=>'date',
			 'boolean'=>'boolean',
			 'email'=>'valid_email',
			 'emails'=>'valid_emails',
			 'ip'=>'valid_ip',
			 'url'=>'valid_url',
			 'regex_match'=>'regex_match',
			 'maxlength'=>'max_length',
			 'minlength'=>'min_length',
			 'exactlength'=>'exact_length',
			 'greaterthan'=>'greater_than',
			 'lessthan'=>'less_than',
			 'inlist'=>'inlist',
			 'required'=>'required',
			 'digit'=>'digit',
			 'timestamp'=>'timestamp'
		);
		return isset($options[$type])?$options[$type]:$type;
	}

	// ------------------------------------------------------------------------
	// REGRAS DE VALIDAÇÃO
	// ------------------------------------------------------------------------

	/**
	 * Required
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function required($str,$param){
		if($str===FALSE)
			return TRUE;

		return (trim($str)=='')?FALSE:TRUE;
	}
	/**
	 * Performs a Regular Expression match test.
	 *
	 * @access	public
	 * @param	string
	 * @param	regex
	 * @return	bool
	 */
	function regex_match($str,$regex){
		if(!preg_match($regex,$str)){
			return FALSE;
		}
		return TRUE;
	}
	/**
	 * boolean
	 *
	 * @access	public
	 * @param	boolean / string boolean
	 * @return	bool
	 */
	function boolean($str){
		if($str === TRUE || $str === FALSE)
			return TRUE;

		if(in_array(strtoupper($str),array('TRUE','FALSE','T','F')))
			return TRUE;

		return FALSE;
	}
	/**
	 * digit
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function digit($str){
		return preg_match('/^\d+$/',$str);
	}
	/**
	 * timestamp
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function timestamp($str){
		return is_timestamp($str);
	}
	/**
	 * Minimum Length
	 *
	 * @access	public
	 * @param	string
	 * @param	integer
	 * @return	bool
	 */
	function minlength($str,$length){
		if(preg_match("/[^0-9]/",$length)){
			return FALSE;
		}
		if(function_exists('mb_strlen')){
			return (mb_strlen($str)<$length)?FALSE:TRUE;
		}
		return (strlen($str)<$val)?FALSE:TRUE;
	}
	/**
	 * Max Length
	 *
	 * @access	public
	 * @param	string
	 * @param	integer
	 * @return	bool
	 */
	function maxlength($str,$length){
		if(preg_match("/[^0-9]/",$length)){
			return FALSE;
		}
		if(function_exists('mb_strlen')){
			return (mb_strlen($str)>$length)?FALSE:TRUE;
		}
		return (strlen($str)>$length)?FALSE:TRUE;
	}
	/**
	 * Exact Length
	 *
	 * @access	public
	 * @param	string
	 * @param	integer
	 * @return	bool
	 */
	function exactlength($str,$length){
		if($str=='')
			return TRUE;
		if(preg_match("/[^0-9]/",$length))
			return FALSE;
		if(function_exists('mb_strlen'))
			return (mb_strlen($str)!=$length)?FALSE:TRUE;

		return (strlen($str)!=$length)?FALSE:TRUE;
	}
	/**
	 * Valid Email
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function email($str){
		return (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]+$/ix",$str))?FALSE:TRUE;
	}
	/**
	 * Valid Emails
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function emails($str){
		if(strpos($str,',')===FALSE){
			return $this->email(trim($str));
		}
		foreach(explode(',',$str) as $email){
			if(trim($email)!=''&& $this->email(trim($email))===FALSE){
				return FALSE;
			}
		}
		return TRUE;
	}
	/**
	 * Validate IP Address
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function ip($str){
		return $this->CI->input->valid_ip($ip);
	}
	/**
	 * Alpha
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function alpha($str){
		return (!preg_match("/^([a-z])+$/i",$str))?FALSE:TRUE;
	}
	/**
	 * Alpha-numeric
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function alpha_numeric($str){
		return (!preg_match("/^([a-z0-9])+$/i",$str))?FALSE:TRUE;
	}
	/**
	 * Numeric
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function numeric($str){
		return (bool)preg_match('/^[\-+]?[0-9]*\.?[0-9]+$/',$str);
	}
	/**
	 * Integer
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function integer($str){
		if(!preg_match('/^[\-+]?[0-9]+$/',$str))
			return FALSE;

		if($str < -2147483648 || $str > 2147483647) // max size de integer
			return FALSE;

		return TRUE;
	}
	/**
	 * Integer
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function bigint($str){
		if(!preg_match('/^[\-+]?[0-9]+$/',$str))
			return FALSE;

		if($str < -9223372036854775808 || $str > 9223372036854775807) // min e max size de bigint
			return FALSE;

		return TRUE;
	}
	/**
	 * inlist
	 *
	 * @access	public
	 * @param	string
	 * @param	array
	 * @return	bool
	 */
	function inlist($str,$list){
		return in_array($str,$list);
	}
	/**
	 * Nonnegative
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function nonnegative($str){
		if($str==='-0'){
			return FALSE;
		}
		return $this->greater_than($str,-1);
	}
	/**
	 * Greather than
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function greaterthan($str,$min){
		if(!is_numeric($str)){
			return FALSE;
		}
		return ($str > $min);
	}
	/**
	 * Less than
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function lessthan($str,$max){
		if(!is_numeric($str)){
			return FALSE;
		}
		return ($str < $max);
	}
	/**
	 * Is a Natural number  (0,1,2,3, etc.)
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function is_natural($str){
		return (bool)preg_match('/^[0-9]+$/',$str);
	}

	/**
	 * Is a Natural number, but not a zero  (1,2,3, etc.)
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function is_natural_no_zero($str){
		if(!preg_match('/^[0-9]+$/',$str)){
			return FALSE;
		}
		if($str==0){
			return FALSE;
		}
		return TRUE;
	}
	/**
	 * Validate Date
	 *
	 * Tests a string for a valid date in provided format
	 * Currently only accepts dates in the following formats:
	 * yyyy-mm-dd
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function date($str){
		$first_test = preg_match('/^\d{4}\-\d{1,2}\-\d{1,2}$/',$str);

		if(!$first_test){
			return FALSE;
		}
		list($yyyy,$mm,$dd) = explode("-",$str); // split the array
		return checkdate($mm,$dd,$yyyy);
	}
}
/* End of file data_validation.php */
/* Location: ./application/libraries/sg/data_validation.php */