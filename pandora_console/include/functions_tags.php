<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage TAGS
 */

 /**
 * Get critical agents by using the status code in modules by filtering by id_tag.
 * 
 * @param int $id_tag Id of the tag to search module with critical state
 * 
 * @return mixed Returns count of agents in critical status or false if they aren't.
 */
function tags_agent_critical ($id_tag) {

	return db_get_sql ("SELECT COUNT(*) FROM tagente, tagente_modulo, ttag_module 
						WHERE tagente.id_agente = tagente_modulo.id_agente
						AND tagente.disabled=0
						AND tagente_modulo.id_agente_modulo = ttag_module.id_agente_modulo
						AND ttag_module.id_tag = $id_tag
						AND critical_count>0");
}

 /**
 * Get unknown agents by using the status code in modules by filtering by id_tag.
 * 
 * @param int $id_tag Id of the tag to search module with unknown state
 * 
 * @return mixed Returns count of agents in unknown status or false if they aren't.
 */
function tags_agent_unknown ($id_tag) {

	return db_get_sql ("SELECT COUNT(*) FROM tagente, tagente_modulo, ttag_module 
						WHERE tagente.id_agente = tagente_modulo.id_agente
						AND tagente.disabled=0
						AND tagente_modulo.id_agente_modulo = ttag_module.id_agente_modulo
						AND ttag_module.id_tag = $id_tag
						AND critical_count=0 AND warning_count=0 AND unknown_count>0");
}

/**
 * Get total agents filtering by id_tag.
 * 
 * @param int $id_tag Id of the tag to search total agents
 * 
 * @return mixed Returns count of agents with this tag or false if they aren't.
 */
function tags_total_agents ($id_tag) {
	
		// Avoid mysql error
		if (empty($id_tag))
			return;
	
		$total_agents = "SELECT COUNT(DISTINCT tagente.id_agente) 
						FROM tagente, tagente_modulo, ttag_module 
						WHERE tagente.id_agente = tagente_modulo.id_agente
						AND tagente_modulo.id_agente_modulo = ttag_module.id_agente_modulo
						AND ttag_module.id_tag = " . $id_tag;
						
		return db_get_sql ($total_agents);	
}

 /**
 * Get normal agents by using the status code in modules by filtering by id_tag.
 * 
 * @param int $id_tag Id of the tag to search module with normal state
 * 
 * @return mixed Returns count of agents in normal status or false if they aren't.
 */
function tags_agent_ok ($id_tag) {

	return db_get_sql ("SELECT COUNT(*) FROM tagente, tagente_modulo, ttag_module 
						WHERE tagente.id_agente = tagente_modulo.id_agente
						AND tagente.disabled=0
						AND tagente_modulo.id_agente_modulo = ttag_module.id_agente_modulo
						AND ttag_module.id_tag = $id_tag
						AND normal_count=total_count");
}

 /**
 * Get warning agents by using the status code in modules by filtering by id_tag.
 * 
 * @param int $id_tag Id of the tag to search module with warning state
 * 
 * @return mixed Returns count of agents in warning status or false if they aren't.
 */
function tags_agent_warning ($id_tag) {
	 
	return db_get_sql ("SELECT COUNT(*) FROM tagente, tagente_modulo, ttag_module 
						WHERE tagente.id_agente = tagente_modulo.id_agente
						AND tagente.disabled=0
						AND tagente_modulo.id_agente_modulo = ttag_module.id_agente_modulo
						AND ttag_module.id_tag = $id_tag
						AND critical_count=0 AND warning_count>0");
}
 
 /**
 * Find a tag searching by tag's or description name. 
 * 
 * @param string $tag_name_description Name or description of the tag that it's currently searched. 
 * @param array $filter Array with pagination parameters. 
 * @param bool $only_names Whether to return only names or all fields.
 * 
 * @return mixed Returns an array with the tag selected by name or false.
 */
function tags_search_tag ($tag_name_description = false, $filter = false, $only_names = false) {
	global $config;
	
	if ($tag_name_description) {
		switch ($config["dbtype"]) {
			case "mysql":
				$sql = 'SELECT *
					FROM ttag
					WHERE ((name COLLATE utf8_general_ci LIKE "%'. $tag_name_description .'%") OR 
						(description COLLATE utf8_general_ci LIKE "%'. $tag_name_description .'%"))';
				break;
			case "postgresql":
				$sql = 'SELECT *
					FROM ttag
					WHERE ((name COLLATE utf8_general_ci LIKE \'%'. $tag_name_description .'%\') OR
						(description COLLATE utf8_general_ci LIKE \'%'. $tag_name_description .'%\'))';
				break;
			case "oracle":
				$sql = 'SELECT *
					FROM ttag
					WHERE (UPPER(name) LIKE UPPER (\'%'. $tag_name_description .'%\') OR
						UPPER(dbms_lob.substr(description, 4000, 1)) LIKE UPPER (\'%'. $tag_name_description .'%\'))';
				break;
		}
	}
	else{
		$sql = 'SELECT * FROM ttag';
	}
	if ($filter !== false) {
		switch ($config["dbtype"]) {
			case "mysql":
				$result = db_get_all_rows_sql ($sql . ' LIMIT ' . $filter['offset'] . ',' . $filter['limit']);
				break;
			case "postgresql":
				$result = db_get_all_rows_sql ($sql . ' OFFSET ' . $filter['offset'] . ' LIMIT ' . $filter['limit']);
				break;
			case "oracle":
				$result = oracle_recode_query ($sql, $filter, 'AND', false);
				if ($components != false) {
					for ($i=0; $i < count($components); $i++) {
						unset($result[$i]['rnum']);
					}
				}
				break;
		}
	}
	else {
		$result = db_get_all_rows_sql ($sql);
	}
	
	if ($result === false)
		$result = array();
	
	if ($only_names) {
		$result_tags = array();
		foreach ($result as $tag) {
			$result_tags[$tag['id_tag']] = $tag['name'];
		}
		$result = $result_tags;
	}
	
	return $result;
}

/**
 * Create a new tag. 
 * 
 * @param array $values Array with all values to insert. 
 *
 * @return mixed Tag id or false.
 */
function tags_create_tag($values) {
	if (empty($values)){
		return false;
	}
	
	return db_process_sql_insert('ttag',$values);
}

/**
 * Search tag by id. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed Array with the seleted tag or false.
 */
function tags_search_tag_id($id) {
	return db_get_row ('ttag', 'id_tag', $id);
}

/**
 * Get tag name. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed String with tag name or false.
 */
function tags_get_name($id){
	return db_get_value_filter ('name', 'ttag', array('id_tag' => $id));
}

/**
 * Get tag id given the tag name. 
 * 
 * @param string Tag name.
 *
 * @return int Tag id.
 */
function tags_get_id($name){
	return db_get_value_filter ('id_tag', 'ttag', array('name' => $name));
}

/**
 * Get tag description. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed String with tag description or false.
 */
function tags_get_description($id){
		return db_get_value_filter('description', 'ttag', array('id_tag' => $id));
}

/**
 * Get tag url. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed String with tag url or false.
 */
function tags_get_url($id){
		return db_get_value_filter('description', 'ttag', array('id_tag' => $id));
}

/**
 * Get tag's module count. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed Int with the tag's count or false.
 */
function tags_get_modules_count($id){
	$num_modules = (int)db_get_value_filter('count(*)', 'ttag_module', array('id_tag' => $id));
	$num_policy_modules = (int)db_get_value_filter('count(*)', 'ttag_policy_module', array('id_tag' => $id));

	return $num_modules + $num_policy_modules;
}

/**
 * Get tag's local module count. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed Int with the tag's count or false.
 */
function tags_get_local_modules_count($id){
	$num_modules = (int)db_get_value_filter('count(*)', 'ttag_module', array('id_tag' => $id));

	return $num_modules;
}

/**
 * Get tag's local module count. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed Int with the tag's count or false.
 */
function tags_get_modules_tag_count($id){
	$num_modules = (int)db_get_value_filter('count(*)', 'ttag_module', array('id_agente_modulo' => $id));

	return $num_modules;
}

/**
 * Get tag's policy module count. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed Int with the tag's count or false.
 */
function tags_get_policy_modules_count($id){
	$num_policy_modules = (int)db_get_value_filter('count(*)', 'ttag_policy_module', array('id_tag' => $id));

	return $num_policy_modules;
}



/**
 * Updates a tag by id. 
 * 
 * @param array $id Int with tag id info. 
 * @param string $where Where clause to update record.
 *
 * @return bool True or false if something goes wrong.
 */
function tags_update_tag($values, $where){
	return db_process_sql_update ('ttag', $values, $where);
}

/**
 * Delete a tag by id. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return bool True or false if something goes wrong.
 */
function tags_delete_tag ($id_tag){
	$errn = 0;
	
	$result_tag = db_process_delete_temp ('ttag', 'id_tag', $id_tag);
	if ($result_tag === false)
		$errn++;
	
	$result_module = db_process_delete_temp ('ttag_module', 'id_tag', $id_tag);
	if ($result_module === false)
		$errn++;

	$result_policy = db_process_delete_temp ('ttag_policy_module', 'id_tag', $id_tag);
	if ($result_policy === false)
		$errn++;
		
	if ($errn == 0){
			db_process_sql_commit();
			return true;
	}
	else{
			db_process_sql_rollback();
			return false;
	}

}

/**
 * Get tag's total count.  
 *
 * @return mixed Int with the tag's count.
 */
function tags_get_tag_count(){
	return (int)db_get_value('count(*)', 'ttag');
}

/**
 * Inserts tag's array of a module. 
 * 
 * @param int $id_agent_module Module's id.
 * @param array $tags Array with tags to associate to the module. 
 *
 * @return bool True or false if something goes wrong.
 */
function tags_insert_module_tag ($id_agent_module, $tags){
	$errn = 0;
	
	$values = array();
	
	if($tags == false) {
		$tags = array();
	}
	
	foreach ($tags as $tag){
		//Protect against default insert
		if (empty($tag))
			continue;
		
		$values['id_tag'] = $tag;
		$values['id_agente_modulo'] = $id_agent_module;
		$result_tag = db_process_sql_insert('ttag_module', $values);
		if ($result_tag === false)
			$errn++;		
	}
	
/*	if ($errn > 0){
		db_process_sql_rollback();
		return false;
	}
	else{
		db_process_sql_commit();
		return true;
	}*/
}

/**
 * Inserts tag's array of a policy module. 
 * 
 * @param int $id_agent_module Policy module's id.
 * @param array $tags Array with tags to associate to the module. 
 *
 * @return bool True or false if something goes wrong.
 */
function tags_insert_policy_module_tag ($id_agent_module, $tags){
	$errn = 0;
	
	db_process_sql_begin();
	
	$values = array();
	foreach ($tags as $tag){
		//Protect against default insert
		if (empty($tag))
			continue;
		
		$values['id_tag'] = $tag;
		$values['id_policy_module'] = $id_agent_module;
		$result_tag = db_process_sql_insert('ttag_policy_module', $values, false);
		if ($result_tag === false)
			$errn++;		
	}

	if ($errn > 0){
		db_process_sql_rollback();
		return false;
	}
	else{
		db_process_sql_commit();
		return true;
	}
}

/**
 * Updates tag's array of a module. 
 * 
 * @param int $id_agent_module Module's id.
 * @param array $tags Array with tags to associate to the module. 
 * @param bool $autocommit Whether to do automatical commit or not.
 * 
 * @return bool True or false if something goes wrong.
 */
function tags_update_module_tag ($id_agent_module, $tags, $autocommit = false){
	$errn = 0;

	if (empty($tags))
		$tags = array();
	
	/* First delete module tag entries */
	$result_tag = db_process_sql_delete ('ttag_module', array('id_agente_modulo' => $id_agent_module));

	$values = array();
	foreach ($tags as $tag){
		//Protect against default insert
		if (empty($tag))
			continue;		
		
		$values['id_tag'] = $tag;
		$values['id_agente_modulo'] = $id_agent_module;
		$result_tag = db_process_sql_insert('ttag_module', $values, false);
		if ($result_tag === false)
			$errn++;		
	}
	
}

/**
 * Updates tag's array of a policy module. 
 * 
 * @param int $id_policy_module Policy module's id.
 * @param array $tags Array with tags to associate to the module. 
 * @param bool $autocommit Whether to do automatical commit or not.
 * 
 * @return bool True or false if something goes wrong.
 */
function tags_update_policy_module_tag ($id_policy_module, $tags, $autocommit = false){
	$errn = 0;

	if (empty($tags))
		$tags = array();
	
	/* First delete module tag entries */
	$result_tag = db_process_sql_delete ('ttag_policy_module', array('id_policy_module' => $id_policy_module));

	$values = array();
	foreach ($tags as $tag) {
		//Protect against default insert
		if (empty($tag))
			continue;
		
		$values['id_tag'] = $tag;
		$values['id_policy_module'] = $id_policy_module;
		$result_tag = db_process_sql_insert('ttag_policy_module', $values, false);
		if ($result_tag === false)
			$errn++;
	}
	
}

/**
 * Select all tags of a module. 
 * 
 * @param int $id_agent_module Module's id.
 *
 * @return mixed Array with module tags or false if something goes wrong.
 */
function tags_get_module_tags ($id_agent_module){
	if (empty($id_agent_module))
		return false;
	
	$tags = db_get_all_rows_filter('ttag_module', array('id_agente_modulo' => $id_agent_module), false);
	
	if ($tags === false)
		return false;
	
	$return = array();
	foreach ($tags as $tag){
		$return[] = $tag['id_tag'];
	}
	
	return $return;
}

/**
 * Select all tags of a policy module. 
 * 
 * @param int $id_policy_module Policy module's id.
 *
 * @return mixed Array with module tags or false if something goes wrong.
 */
function tags_get_policy_module_tags ($id_policy_module){
	if (empty($id_policy_module))
		return false;
	
	$tags = db_get_all_rows_filter('ttag_policy_module', array('id_policy_module' => $id_policy_module), false);
	
	if ($tags === false)
		return false;
	
	$return = array();
	foreach ($tags as $tag){
		$return[] = $tag['id_tag'];
	}
	
	return $return;
}

/**
 * Select all tags.
 *
 * @return mixed Array with tags.
 */
function tags_get_all_tags () {
	$tags = db_get_all_fields_in_table('ttag', 'name');
	
	if ($tags === false)
		return false;
	
	$return = array();
	foreach ($tags as $id => $tag) {
		$return[$id] = $tag['name'];
	}
	
	return $return;
}
?>
