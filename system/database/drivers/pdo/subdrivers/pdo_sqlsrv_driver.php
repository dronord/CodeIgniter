<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.2.4 or newer
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Open Software License version 3.0
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is
 * bundled with this package in the files license.txt / license.rst.  It is
 * also available through the world wide web at this URL:
 * http://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to obtain it
 * through the world wide web, please send an email to
 * licensing@ellislab.com so we can send you a copy immediately.
 *
 * @package		CodeIgniter
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2012, EllisLab, Inc. (http://ellislab.com/)
 * @license		http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link		http://codeigniter.com
 * @since		Version 3.0.0
 * @filesource
 */

/**
 * PDO SQLSRV Database Adapter Class
 *
 * Note: _DB is an extender class that the app controller
 * creates dynamically based on whether the query builder
 * class is being used or not.
 *
 * @package		CodeIgniter
 * @subpackage	Drivers
 * @category	Database
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/user_guide/database/
 */
class CI_DB_pdo_sqlsrv_driver extends CI_DB_pdo_driver {

	public $subdriver = 'sqlsrv';

	protected $_random_keyword = ' NEWID()';

	protected $_quoted_identifier;

	/**
	 * Constructor
	 *
	 * Builds the DSN if not already set.
	 *
	 * @param	array
	 * @return	void
	 */
	public function __construct($params)
	{
		parent::__construct($params);

		if (empty($this->dsn))
		{
			$this->dsn = 'sqlsrv:Server='.(empty($this->hostname) ? '127.0.0.1' : $this->hostname);

			empty($this->port) OR $this->dsn .= ','.$this->port;
			empty($this->database) OR $this->dsn .= ';Database='.$this->database;

			// Some custom options

			if (isset($this->QuotedId))
			{
				$this->dsn .= ';QuotedId='.$this->QuotedId;
				$this->_quoted_identifier = (bool) $this->QuotedId;
			}

			if (isset($this->ConnectionPooling))
			{
				$this->dsn .= ';ConnectionPooling='.$this->ConnectionPooling;
			}

			if ($this->encrypt === TRUE)
			{
				$this->dsn .= ';Encrypt=1';
			}

			if (isset($this->TraceOn))
			{
				$this->dsn .= ';TraceOn='.$this->TraceOn;
			}

			if (isset($this->TrustServerCertificate))
			{
				$this->dsn .= ';TrustServerCertificate='.$this->TrustServerCertificate;
			}

			empty($this->APP) OR $this->dsn .= ';APP='.$this->APP;
			empty($this->Failover_Partner) OR $this->dsn .= ';Failover_Partner='.$this->Failover_Partner;
			empty($this->LoginTimeout) OR $this->dsn .= ';LoginTimeout='.$this->LoginTimeout;
			empty($this->MultipleActiveResultSets) OR $this->dsn .= ';MultipleActiveResultSets='.$this->MultipleActiveResultSets;
			empty($this->TraceFile) OR $this->dsn .= ';TraceFile='.$this->TraceFile;
			empty($this->WSID) OR $this->dsn .= ';WSID='.$this->WSID;
		}
		elseif (preg_match('/QuotedId=(0|1)/', $this->dsn, $match))
		{
			$this->_quoted_identifier = (bool) $match[1];
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Non-persistent database connection
	 *
	 * @param	bool
	 * @return	object
	 */
	public function db_connect($persistent = FALSE)
	{
		if ( ! empty($this->char_set) && preg_match('/utf[^8]*8/i', $this->char_set))
		{
			$this->options[PDO::SQLSRV_ENCODING_UTF8] = 1;
		}

		$this->conn_id = parent::db_connect($persistent);

		if ( ! is_object($this->conn_id) OR is_bool($this->_quoted_identifier))
		{
			return $this->conn_id;
		}

		// Determine how identifiers are escaped
		$query = $this->query('SELECT CASE WHEN (@@OPTIONS | 256) = @@OPTIONS THEN 1 ELSE 0 END AS qi');
		$query = $query->row_array();
		$this->_quoted_identifier = empty($query) ? FALSE : (bool) $query['qi'];
		$this->_escape_char = ($this->_quoted_identifier) ? '"' : array('[', ']');

		return $this->conn_id;
	}

	// --------------------------------------------------------------------

	/**
	 * Show table query
	 *
	 * Generates a platform-specific query string so that the table names can be fetched
	 *
	 * @param	bool
	 * @return	string
	 */
	protected function _list_tables($prefix_limit = FALSE)
	{
		return 'SELECT '.$this->escape_identifiers('name')
			.' FROM '.$this->escape_identifiers('sysobjects')
			.' WHERE '.$this->escape_identifiers('type')." = 'U'";

		if ($prefix_limit === TRUE && $this->dbprefix !== '')
		{
			$sql .= ' AND '.$this->escape_identifiers('name')." LIKE '".$this->escape_like_str($this->dbprefix)."%' "
				.sprintf($this->_like_escape_str, $this->_like_escape_chr);
		}

		return $sql.' ORDER BY '.$this->escape_identifiers('name');
	}

	// --------------------------------------------------------------------

	/**
	 * Show column query
	 *
	 * Generates a platform-specific query string so that the column names can be fetched
	 *
	 * @param	string	the table name
	 * @return	string
	 */
	protected function _list_columns($table = '')
	{
		return 'SELECT '.$this->escape_identifiers('column_name')
			.' FROM '.$this->escape_identifiers('information_schema.columns')
			.' WHERE '.$this->escape_identifiers('table_name').' = '.$this->escape($table);
	}

	// --------------------------------------------------------------------

	/**
	 * Update statement
	 *
	 * Generates a platform-specific update string from the supplied data
	 *
	 * @param	string	the table name
	 * @param	array	the update data
	 * @return	string
         */
	protected function _update($table, $values)
	{
		$this->qb_limit = FALSE;
		$this->qb_orderby = array();
		return parent::_update($table, $values);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete statement
	 *
	 * Generates a platform-specific delete string from the supplied data
	 *
	 * @param	string	the table name
	 * @return	string
	 */
	protected function _delete($table)
	{
		if ($this->qb_limit)
		{
			return 'WITH ci_delete AS (SELECT TOP '.$this->qb_limit.' * FROM '.$table.$this->_compile_wh('qb_where').') DELETE FROM ci_delete';
		}

		return parent::_delete($table);
	}

	// --------------------------------------------------------------------

	/**
	 * Limit string
	 *
	 * Generates a platform-specific LIMIT clause
	 *
	 * @param	string	the sql query string
	 * @return	string
	 */
	protected function _limit($sql)
	{
		// As of SQL Server 2012 (11.0.*) OFFSET is supported
		if (version_compare($this->version(), '11', '>='))
		{
			return $sql.' OFFSET '.(int) $this->qb_offset.' ROWS FETCH NEXT '.$this->qb_limit.' ROWS ONLY';
		}

		$limit = $this->qb_offset + $this->qb_limit;

		// An ORDER BY clause is required for ROW_NUMBER() to work
		if ($this->qb_offset && ! empty($this->qb_orderby))
		{
			$orderby = $this->_compile_order_by();

			// We have to strip the ORDER BY clause
			$sql = trim(substr($sql, 0, strrpos($sql, $orderby)));

			// Get the fields to select from our subquery, so that we can avoid CI_rownum appearing in the actual results
			if (count($this->qb_select) === 0)
			{
				$select = '*'; // Inevitable
			}
			else
			{
				// Use only field names and their aliases, everything else is out of our scope.
				$select = array();
				$field_regexp = ($this->_quoted_identifier)
					? '("[^\"]+")' : '(\[[^\]]+\])';
				for ($i = 0, $c = count($this->qb_select); $i < $c; $i++)
				{
					$select[] = preg_match('/(?:\s|\.)'.$field_regexp.'$/i', $this->qb_select[$i], $m)
						? $m[1] : $this->qb_select[$i];
				}
				$select = implode(', ', $select);
			}

			return 'SELECT '.$select." FROM (\n\n"
				.preg_replace('/^(SELECT( DISTINCT)?)/i', '\\1 ROW_NUMBER() OVER('.trim($orderby).') AS '.$this->escape_identifiers('CI_rownum').', ', $sql)
				."\n\n) ".$this->escape_identifiers('CI_subquery')
				."\nWHERE ".$this->escape_identifiers('CI_rownum').' BETWEEN '.($this->qb_offset + 1).' AND '.$limit;
		}

		return preg_replace('/(^\SELECT (DISTINCT)?)/i','\\1 TOP '.$limit.' ', $sql);
	}

}

/* End of file pdo_sqlsrv_driver.php */
/* Location: ./system/database/drivers/pdo/subdrivers/pdo_sqlsrv_driver.php */