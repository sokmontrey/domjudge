<?php 
// $Id: lib.database.php 3589 2004-04-17 20:21:13Z kink $

/******************************************************************************
*    Info                                                                     *
*******************************************************************************

Levert classes om querys uit te voeren. 

Features:
- transparant arrays en strings opslaan in database, zonder je druk te hoeven
  maken over escapen enzo
- autmatisch verbinding maken
- in ��n commando een bepaalde waarde uitlezen
- eenvoudig queries bouwen
- transparant meerdere databases faken (handig als je een zuinige 
  opdrachtgever hebt en per db moet betalen :)
- transparante multi-application support

Gebruik:

[hier q() documenteren]

Alle andere query-functies als select(), insert() ed zijn deprecated.

/******************************************************************************
*    TODO                                                                     *
*******************************************************************************

- support joins in an easy way
- Some kind of view-faking

/******************************************************************************
*    Licence                                                                  *
*******************************************************************************

Copyright (C) 2001-2004 Jeroen van Wolffelaar <jeroen@php.net>

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 2 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program; if not, write to the Free Software Foundation, Inc., 59 Temple
Place, Suite 330, Boston, MA 02111-1307 USA

/******************************************************************************
*    Initialisation                                                           *
******************************************************************************/

if (!@define('INCLUDED_LIB_DATABASE',true)) return;

define('DB_EQ'    , '='        );
define('DB_NEQ'   , '!='       );
define('DB_LIKE'  , 'like'     );
// bevat...:
define('DB_CONT'  , 'cont'     );
define('DB_NLIKE' , 'not like' );
//define('DB_NCONT' , 'ncont'    );
define('DB_REGEX' , 'regex'    );
define('DB_NREGEX', 'not regex');

// Speciale sentinel voor set, want die moet altijd '=' hebben, en nooit 'is'
// (bijv bij null)
define('DB_SET', 'DB_SET');

/******************************************************************************
*    Hulpfuncties                                                             *
******************************************************************************/

function db_vw($kolom,$waarde,$mode=NULL)
{
	if (!$mode) $mode = DB_EQ; // staat niet direct zo in de header, want
				// je mag ook expliciet NULL opgeven
	
	if ($waarde === NULL) {
		switch ($mode) {
			case DB_EQ: $mode = 'is'; break;
			case DB_NEQ: $mode = 'is not'; break;
		}
	}

	// als je set, dan escapen
	if ($mode === DB_SET) {
		$mode='=';
		$waarde = db__val2sql($waarde);
	// like
	} elseif ($mode === DB_CONT) {
		$waarde = db__val2sql((string)$waarde);
		$waarde = '"%'.substr($waarde,1, -1).'%"';
		$mode = 'LIKE';
	// dit is ook een voorwaarde
	} elseif ( is_array($waarde) ) {
		$mode = 'in';
		$waarde = array_map('db__val2sql', $waarde);
		$waarde = '('. implode(',', $waarde) . ')';
	// anders gewoon escapen, want is voorwaarde
	} else {
		$waarde = db__val2sql($waarde);
	}

	return "$kolom $mode $waarde";
}

function db_and()
{
	return db__andor(func_get_args(),'AND','1');
}

function db_or() 
{
	// default '00', omdat de check of er uberhaupt een where moet komen soms
	// de boolean waarde van de where clause bekijken, en '0' evalueert naar
	// false, en dan komt er dus geen where, terwijl een empty set zou moeten
	// komen natuurlijk... dit is een workaround voor die slechte checks dus
	// (maar laat maar staan, want die fout is makkelijk gemaakt...)
	return db__andor(func_get_args(),'OR','00');
}

/******************************************************************************
*    Internal functions                                                       *
******************************************************************************/
// syntax:
// [1] db_and( [string cond [, string cond [, string cond [...] ] ] ] )
// [2] db_and( array conditions )
// [3] db_and( string column, array values [, string compare_mode])
// mode: one of DB_EQ (default), DB_NEQ , DB_LIKE

function db__andor($params,$op,$default)
{
	// check for case [3]:
	if (is_array(@$params[1])) {
		@list ($col,$values,$mode) = $params;
		if (!$values) return $default;
		return implode(" $op ", array_map(
			create_function('$value',
			"return db_vw('$col',\$value,'$mode');"),$values));
	}
	
	// let args be array-of-arguments, depending on [1] or [2]
	$args = is_array(@$params[0]) ? $params[0] : $params;
	return $args ? '('.implode(") $op (",$args).')' : $default;
}

/******************************************************************************
*    (internal) Connection handling                                           *
******************************************************************************/

// connects to a db-server if not yet connected
function db__connect($database,$host,$user,$pass,$persist=TRUE)
{
	$con = $persist ? 'mysql_pconnect' : 'mysql_connect';
	
	$db__connection = $con($host,$user,$pass)
		or error("Could not connect to database server ".
			"(host=$host,user=$user,password=".ereg_replace('.','*',$pass).")" );
	mysql_select_db($database,$db__connection)
			or error("Could not select database '$database': ".
				mysql_error($db__connection) );
	return $db__connection;
}


/******************************************************************************
*    (internal) Type conversion                                               *
******************************************************************************/
// zet een php-variabele om in een vorm
// die rechtstreeks in een query geplaatst
// kan worden
function db__val2sql($val, $mode='.')
{
	if (isset($GLOBALS['MODE'])) {
		$mode = $GLOBALS['MODE'];
	}
	if (!isset($val)) return 'null';
	switch ($mode) {
		case 'f': return (float)$val;
		case 'i': return (int)$val;
		case 's': return '"'.mysql_escape_string($val).'"';
		case 'c': return '"%'.mysql_escape_string($val).'%"';
		case '.': break;
		default: 
			error("Unknown mode: $mode");
	}

	switch (gettype($val))
	{
		case 'boolean':
			return (int) $val;
		case 'integer':
		case 'double':
			return $val;
		case 'string':
			return '"'.mysql_escape_string($val).'"';
		case 'array':
		case 'object':
			return '"'.mysql_escape_string(serialize($val)).'"';
		case 'resource':
			error('Cannot store a resource in database');
			/* break missing intentionally */
	}
	error('Case failed in lib.database');
}

function db__sql2val($val)
{
	$t = @unserialize($val);
	return $t !== false ? $t : $val;
}

/* gebruik:
 * - $wat is een string, "<table>", met $db de database
 * - $db is een db_result
 *
 * $result[]:
 *   [0]["table"]  table name
 *   [0]["name"]   field name
 *   [0]["type"]   field type
 *   [0]["len"]    field length
 *   [0]["flags"]  field flags
 */
function db__metadata(&$db,$table=null)
{
    $count = 0;
    $id    = 0;
    $res   = array();

	if($table)
	{
		$id=@mysql_list_fields($db->database,$table);
	}
	else
	{
		$id=$db->_result;
	}
 
	$count = @mysql_num_fields($id);

    // made this IF due to performance (one if is faster than $count if's)
	for ($i=0; $i<$count; $i++) {
		$res[$i]["table"] = @mysql_field_table ($id, $i);
		$res[$i]["name"]  = @mysql_field_name  ($id, $i);
		$res[$i]["type"]  = @mysql_field_type  ($id, $i);
		$res[$i]["len"]   = @mysql_field_len   ($id, $i);
		$res[$i]["flags"] = @mysql_field_flags ($id, $i);
	}

	// free the result only if we were called on a table
	if ($table) @mysql_free_result($id);
	return $res;
}
			
/*
 * Zowel met als zonder constructor te gebruiken.  Zonder constructor is
 * een eenvoudige extend mogelijk:
 *
 * class fake_db extends db
 * {
 * 		function my_db()
 * 		{
 * 			$this->db('dilithium','localhost','nobody','<password>',TRUE);
 *			// voor het faken van een andere db
 *			$this->setprefix('fake');
 *		}
 * }
 * Zo wordt de echte database 'dilithium' gebruikt om een database 'fake'
 * te faken.  In 'dilithium' hebben de tables uit 'fake' het prefix 'fake_'.
 * Dus wil je een table 'mytable' uit 'fake' faken:
 * 		$fake_db->insert('mytable',array('name'=>'me, myself and I'));
 * dan wordt dat gemapt op de volgende query in 'dilithium':
 * 		INSERT fake_mytable SET name='me, myself and I';
 */
class db
{
	var $host;
	var $database;
	var $user;
	var $password;
	var $persist;

	var $_connection=FALSE;
	var $_prefix = '';
	var $_cached_metadata;

	function db($database,$host,$user,$password,$persist=TRUE)
	{
		$this->database=$database;
		$this->host=$host;
		$this->user=$user;
		$this->password=$password;
		$this->persist=$persist;

		/*
		$this->_connection=db__connect($database,$host,$user,$password,
									   $persist);
		*/
	}

	function setprefix($prefix)
	{
		$this->_prefix=$prefix;
	}

	function metadata($table)
	{
		if(!@$this->_cached_metadata[$table])
			$this->_cached_metadata[$table]=db__metadata($this,$table);
		return $this->_cached_metadata[$table];
	}

	// voer een query uit mbv speciale formatting 
	/* syntax:
		%%: literal %
		%.: auto-detect
		%s: string met quotes en escaping
		%c: string met quotes en escaping, en procentjes eromheen voor in een
			like
		%i: integer
		%f: floating point
		%A?: array van ?, gescheiden door komma's
		%S: array van key => ., wordt key=., gescheiden door komma's

		ook verschillende keywords om het gedrag te veranderen van wat
		gereturned wordt
	*/
	function q() // queryf
	{
		$argv = func_get_args();
		$format = array_shift($argv);
		list($key) = explode(' ', $format, 2);
		$key = strtolower($key);
		$maybe = false;
		switch ($key) {

			// modifying commando's; eerst keywords, dan normale
			case 'returnid':
			case 'returnaffected':
				$format = substr($format,strlen($key)+1);
			case 'insert':
			case 'update':
			case 'replace':
			case 'delete':
				$type = 'update';
				break;

			// selecting commando's; eerst keywords, dan normale
			case 'maybetuple':
			case 'maybevalue':
				$maybe = true;
				$key = substr($key,5,5);
				// LET OP: De substr verderop zal als keylength de nieuwe key
				// nemen, daarom moeten we vast de lengte van VALUE/TUPLE van
				// de format afhalen. Gelukkig zijn BEIDE 5
				$format = substr($format,5);
			case 'column':
			case 'table':
			case 'keytable':
			case 'tuple':
			case 'value':
				$format = substr($format,strlen($key)+1);
			case 'select':
			case 'describe':
			case 'show':
				$type = 'select';
				break;

			default:
				error("SQL command/lib keyword '$key' unknown!");
		}

		$parts = explode('%', $format);
		$literal = false;
		foreach ($parts as $part) {
			if ($literal) {
				$literal = false;
				$query .= $part;
				continue;
			}
			if (!isset($query)) {
				// eerste part
				$query = $part;
				continue;
			}
			if (!$part) {
				// literal %%
				$query .= '%';
				$literal=true;
				continue;
			}
			switch ($part{0}) {
				case 'A':
					$val = array_shift($argv);
					if (!is_array($val) || !$val) {
						error("Met %A in \$DATABASE->q() moet een "
							."niet-lege array corresponderen, het is nu een "
							."'$val'!" );
					}
					$GLOBALS['MODE'] = $part{1};
					$query .= implode(', ', array_map('db__val2sql', $val));
					unset($GLOBALS['MODE']);
					$query .= substr($part,2);
					break;
				case 'S':
					$val = array_shift($argv);
					$query .= implode(', ', array_map(create_function(
						'$key,$value', 'return db_vw($key, $value,
						DB_SET);'),array_keys($val),$val));
					$query .= substr($part,1);
					break;
				case 's':
				case 'c':
				case 'i':
				case 'f':
				case '.':
					$val = array_shift($argv);
					$query .= db__val2sql($val, $part{0});
					$query .= substr($part,1);
					break;
			}

		}

		$res = $this->_execute($query);

		if ($type == 'update') {
			if ($key == 'returnid') {
				return mysql_insert_id($this->_connection);
			}
			if ($key == 'returnaffected') {
				return mysql_affected_rows($this->_connection);
			}
			return;
		}

		$res = new db_result($res);

		if ($key == 'tuple' || $key == 'value') {
			if ($res->count() < 1) {
				if ($maybe) return NULL;
				log_mysql_error(time()." $this->database: $key $query; ".
					"Query did not return any rows");
				error("$this->database query error ($key $query".
					"): Query did not return any rows");
			}
			if ($res->count() > 1) {
				log_mysql_error(time()." $this->database: $key $query; ".
					"Query did return too many rows (".$res->count().")");
				error("$this->database query error ($key $query".
					"): Query did return too many rows (".$res->count().")");
			}
			$row = $res->next();
			if ($key == 'value') {
				return array_shift($row);
			}
			return $row;
		}

		if ($key == 'table') {
			return $res->gettable();
		}
		if ($key == 'keytable') {
			return $res->getkeytable('ARRAYKEY');
		}
		if ($key == 'column') {
			return $res->getcolumn();
		}

		return $res;
	}

	function select($table,$where = NULL,$select = NULL, $orderby=NULL, $limit=NULL, $groupby = NULL, $having = NULL)
	{
		if (!$select) $select = '*';

		$query = "SELECT $select FROM {$this->_prefix}$table ";

		if ($where)
			$query .= " WHERE ($where) ";
		if ($groupby)
			$query .= " GROUP BY $groupby ";
		if ($having)
			$query .= " HAVING $having ";
		if ($orderby)
			$query .= " ORDER BY $orderby ";

		if ($limit)
			$query .= " LIMIT $limit ";

		return new db_result($this->_execute($query));
	}


	// vraag 1 rij op. default stopt executie als er <>1 rijen geselecteerd zijn,
	// override met $fatal=FALSE, dan slechts warning en false gereturned
	function select_tuple($tabel,$voorwaarde = null,$wat = null, $orderby=NULL, $limit=NULL, $groupby = NULL, $having = NULL, $fatal = TRUE)
	{
		$query =& $this->select($tabel,$voorwaarde,$wat, $orderby, $limit, $groupby, $having);
		
		if ($query->count() != 1)
		{
			if($fatal) {
				error('select_tuple returned wrong number of rows
					('.$query->count().' != 1)');
			}
			return FALSE;
		}
		
		return $query->next();
	}

	// vraag 1 value op. default stopt executie als er <>1 rijen geselecteerd
	// zijn, of het aantal kolommen klopt niet. Override met $fatal=FALSE;
	function select_value($tabel,$voorwaarde=null,$wat=null, $fatal = TRUE)
	{
		if (($res = $this->select_tuple($tabel,$voorwaarde,$wat,null,null,null,null,$fatal)) === FALSE)
		{
			if($fatal) {
				error('select_value: returned too many/few rows');
			}
			return false;
		}
		
		if (isset($res[1])) {
			if($fatal) {
				error('select_value: too many cols' );
			}
			return false;
		}
		
		return $res[0];
	}

	// geeft de eerste kolom van een resultaat in een array
	function select_column($tabel,$voorwaarde=null,$wat=null,$order=null)
	{
		if (FALSE === ($res = $this->select($tabel,$voorwaarde,$wat,$order))) {
			return FALSE;
		}

		$ret = array();
		while ($n = $res->next()) {
			$ret[] = $n[0];
		}

		return $ret;
	}

	function insert($tabel,$wat=array(),$returnid=FALSE)
	{
		$this->_modify('INSERT',$tabel,$wat,NULL);

		/*
		 * mysql specifiek, in andere db's zal de primary key moeten worden
		 * opgezocht.  Dan $this->_execute("SELECT max(<pri key>) FROM ...");
		 * etcetera.
		 */
		if($returnid)
			return mysql_insert_id($this->_connection);
	}

	// insert meerdere tuples in 1 keer. Zoals ook mogelijk is met
	// SQLInsert1
	
	// Syntax: insertmany(<tabel>, array ( <wat1> , <wat2> ) )
	// waarbij <wat1> etc dezelfde syntax heeft als wat bij insert()
	
	// Dit doet nu gewoon meerdere inserts achter elkaar; je zou ze
	// kunnen groeperen in een query. Dat is dan wel minder portable.
	// Het is dus de vraag of je dat wilt. Dit werkt in ieder geval goed.
	
	function insertmany($tabel,$wats,$returnids=FALSE)
	{
		$ret = array();
		foreach ($wats as $wat)
		{
			$ret[] = $this->insert($tabel,$wat,$returnids);
		}
		if ($returnids) {
			return $ret;
		}
	}

	function replace($tabel,$wat)
	{
		$this->_modify('REPLACE',$tabel,$wat,NULL);
	}

	function replacemany($tabel,$wats)
	{
		foreach ($wats as $wat)
		{
			$this->replace($tabel,$wat);
		}
	}

	function update($tabel,$wat,$voorw=null)
	{
		$this->_modify('UPDATE',$tabel,$wat,$voorw);
	}

	function delete($tabel,$voorw=null)
	{
		if(!$voorw)
			error("Delete entire table disabled for security reasons");
		$this->_modify('DELETE FROM',$tabel,'nvt',$voorw);
	}

	// show databases, tables, index, status, ...
	function show($wat)
	{
		return new db_result($this->_execute('SHOW '.$wat));
	}

	function _modify($op,$tabel,$wat,$where)
	{
		$query = " $op {$this->_prefix}$tabel ";
		if ($wat!='nvt')
		{
			if($wat)
			{
				$query .= ' SET '.implode(', ', array_map(create_function(
					'$key,$value', 'return db_vw($key, $value,
					DB_SET);'),array_keys($wat),$wat));
			}
			else
			{
				$query.=' VALUES () ';
			}
		}
		$query .= $where ? " WHERE $where " : '';
		$this->_execute($query);
	}

	function _execute($query)
	{
		if(!$this->_connection)
		{
			$this->_connection=db__connect($this->database,$this->host,
										   $this->user,$this->password,$this->persist);
		}

		// selecteer opnieuw de db, want hij zou door brakke php/mysql
		// implementatie gechanged kunnen zijn
		mysql_select_db($this->database,$this->_connection);

		list($micros, $secs) = explode(' ',microtime());
		$res = @mysql_query($query,$this->_connection);
		list($micros2, $secs2) = explode(' ',microtime());
		$elapsed_ms = round(1000*(($secs2 - $secs) + ($micros2 - $micros)));

//		log_mysql("$this->database: $query ({$elapsed_ms}ms)");

		if (!$res)
		{
			log_mysql_error(time()." $this->database: $query; ".
				mysql_error($this->_connection));

			// switch error message afhankelijk van errornr.
			switch(mysql_errno($this->_connection)) {
				case 1062:	// duplicate key
				error("Item with this key already exists.\n".
					mysql_error($this->_connection) );
				default:
				error("SQL syntax-error ($query). Error#".
					mysql_errno($this->_connection).": ".
					mysql_error($this->_connection) );
			}
		}

		return $res;
	}
}

class db_result
{
	var $_result = FALSE;
	var $_count = 0;
	var $_tuple;
	var $_nextused = FALSE;

	function db_result($res)
	{
		$this->_result=$res;
		$this->_count=mysql_num_rows($res);
	}

	function free()
	{
		return @mysql_free_result($this->_result);
	}

	// geef een assoc array die een resultaatrij is
	function next()
	{
		// er is al te vaak hieroverheen genext. Error.
		if(!isset($this->_result)) {
			error('Result does not contain a valid resource.');
		}  
		$this->tuple = mysql_fetch_array($this->_result);
		$this->_nextused = TRUE;
		if ($this->tuple === FALSE)
		{
			// laat de gc zijn werk doen
			$this->_result = null; //(Geeft de volgende keer een foutmelding)
			return FALSE;
		}
		return $this->tuple = array_map('db__sql2val',$this->tuple);
	}

	function field($field)
	{
                $this->next();

		if($this->tuple===FALSE)
			return FALSE;
		return $this->tuple[$field];
	}

	function getcolumn($field=NULL)
	{
		if($this->_nextused) {
			error('Getcolumn werkt niet als je al eens genext() hebt over het result!');
		}
		$col = array();
		while($this->next())
		{
			$col[]=$field?$this->tuple[$field]:current($this->tuple);
		}
		return $col;
	}

	// geeft een 2-dim array die het resultaat voorstelt
	function gettable()
	{
		if($this->_nextused) {
			error('Gettable werkt niet als je al eens genext() hebt over het result!');
		}
		$tabel = array();
		while ($this->next())
		{
			$tabel[] = $this->tuple;
		}
		return $tabel;
	}

	// geeft een 2-dim array die het resultaat voorstelt, met een kolom als
	// key (aparte functie ivm performance, hoef je niet de loop te iffen
	function getkeytable($key)
	{
		if($this->_nextused) {
			error('Gettable werkt niet als je al eens genext() hebt over het result!');
		}
		$tabel = array();
		while ($this->next()) {
			$tabel[$this->tuple[$key]] = $this->tuple;
		}
		return $tabel;
	}

	function count()
	{
		return $this->_count;
	}

	function seek($i)
	{
		return mysql_data_seek($this->_result, $i);
	}

	function metadata()
	{
		return db__metadata($this);
	}
}

// vim: ts=4 sw=4 smartindent tw=78
