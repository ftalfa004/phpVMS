<?php
/**
 * phpVMS - Virtual Airline Administration Software
 * Copyright (c) 2008 Nabeel Shahzad
 * For more information, visit www.phpvms.net
 *	Forums: http://www.phpvms.net/forum
 *	Documentation: http://www.phpvms.net/docs
 *
 * phpVMS is licenced under the following license:
 *   Creative Commons Attribution Non-commercial Share Alike (by-nc-sa)
 *   View license.txt in the root, or visit http://creativecommons.org/licenses/by-nc-sa/3.0/
 *
 * @author Nabeel Shahzad 
 * @copyright Copyright (c) 2008, Nabeel Shahzad
 * @link http://www.phpvms.net
 * @license http://creativecommons.org/licenses/by-nc-sa/3.0/
 * @package phpvms
 * @subpackage custom_fields_data
 */ 

class SettingsData
{
	function GetAllSettings()
	{
		return DB::get_results('SELECT * FROM ' . TABLE_PREFIX.'settings');
	}
	
	/**
	 * Return all of the custom fields data
	 */
	function GetAllFields()
	{
		$ret =  DB::get_results('SELECT * FROM '.TABLE_PREFIX.'customfields');
		
		return $ret;
	}
	
	/** 
	 * Add a custom field to be used in a profile
	 */
	function AddField($title, $fieldtype, $public, $showinregistration)
	{
		/*$fieldname = Vars::POST('fieldname');
		$fieldtype = Vars::POST('fieldtype');
		$public = Vars::POST('public');
		$showinregistration = Vars::POST('showinregistration');*/
		
		$fieldname = str_replace(' ', '_', $title);
		$fieldname = strtoupper($fieldname);
		
		//Check, set up like this on purpose to default "safe" values
		if($public == true)
			$public = 1;
		else
			$public = 0;
		
		if($showinregistration == true)
			$showinregistration = 1;
		else
			$showinregistration = 0;
		
		$sql = "INSERT INTO " . TABLE_PREFIX ."customfields (title, fieldname, type, public, showonregister)
					VALUES ('$title', '$fieldname', '$fieldtype', $public, $showinregistration)";
		
		$res = DB::query($sql);	
		
		if(!$res && DB::$errno !=0)
		{			
			return false;
		}
		
		return true;		
	}
	/**
	 * Save site settings
	 *
	 * @param string $name Setting name. Must be unique
	 * @param string $value Value of the setting
	 * @param boolean $core Whether it's "vital" to the engine or not. Bascially blocks deletion
	 */
	function SaveSetting($name, $value, $descrip='', $core=false)
	{		
		if(is_bool($value))
		{
			if($value == true)
			{
				$value = 'true';
			}
			elseif($value == false)
			{
				$value = 'false';
			}
		}
		
		//see if it's an update
		if($core == true)
			$core = 't';
		else	
			$core = 'f';
			
		$name = strtoupper(DB::escape($name));
		$value = DB::escape($value);
		$descrip = DB::escape($descrip);
		
		/*$sql = 'INSERT INTO ' . TABLE_PREFIX . 'settings (name, value, descrip, core) 
					VALUES (\''.$name.'\', \''.$value.'\', \''.$descrip.'\', \''. $core.'\')';
		
		$res = DB::query($sql);
		
		if(DB::$errno == 1062 || !$res)
		{*/
			//update
			// don't change CORE status on update
			$sql = 'UPDATE ' . TABLE_PREFIX . 'settings 
						SET value=\''.$value.'\' WHERE name=\''.$name.'\'';
			
			$res = DB::query($sql);			
		//}		
		
		if(!$res && DB::$errno !=0)
		{			
			return false;
		}
		
		return true;			
	}

	/**
	 * See if the setting is part of the core
	 */	
	function IsCoreSetting($setting_name)
	{
		$sql = 'SELECT core FROM ' . TABLE_PREFIX .'settings WHERE name=\''.$setting_name.'\'';
		$res = DB::get_row($sql);
		
		if(!$res)
			return false;
			
			
		if($res->core == 't')
		{
			return true;
		}
		
		return false;		
	}
	
	function DeleteField($id)
	{
		$sql = 'DELETE FROM '.TABLE_PREFIX.'customfields WHERE fieldid='.$id;

		$res = DB::query($sql);
		
		if(!$res && DB::$errno !=0)
		{			
			return false;
		}

		return true;	

		//TODO: delete all of the field values! 
		//$sql = 'DELETE FROM '.TABLE_PREFIX.'
	}
	
	/**
	 * Delete a setting
		
	function DeleteSetting($setting_name)
	{
		$sql = 'DELETE FROM ' . TABLE_PREFIX . 'settings WHERE name=\''.$setting_name.'\'';
		$res = DB::query($sql);
		
		if($res || $this->db->errno == 0)
		{
			return true;
		}
		
		return false;
	}
	 */
}
?>