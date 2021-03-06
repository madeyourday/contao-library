<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Library
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\Database\Mysql;

use Contao\Database\Statement as AbstractStatement;


/**
 * MySQL-specific database statement class
 *
 * @package   Library
 * @author    Leo Feyer <https://github.com/leofeyer>
 * @copyright Leo Feyer 2005-2014
 */
class Statement extends AbstractStatement
{

	/**
	 * Prepare a query string and return it
	 *
	 * @param string $strQuery The query string
	 *
	 * @return string The modified query string
	 */
	protected function prepare_query($strQuery)
	{
		return $strQuery;
	}


	/**
	 * Escape a string
	 *
	 * @param string $strString The unescaped string
	 *
	 * @return string The escaped string
	 */
	protected function string_escape($strString)
	{
		return "'" . mysql_real_escape_string($strString, $this->resConnection) . "'";
	}


	/**
	 * Add limit and offset to the query string
	 *
	 * @param int $intRows   The maximum number of rows
	 * @param int $intOffset The number of rows to skip
	 */
	protected function limit_query($intRows, $intOffset)
	{
		if (strncasecmp($this->strQuery, 'SELECT', 6) === 0)
		{
			$this->strQuery .= ' LIMIT ' . $intOffset . ',' . $intRows;
		}
		else
		{
			$this->strQuery .= ' LIMIT ' . $intRows;
		}
	}


	/**
	 * Execute the query
	 *
	 * @return resource The result resource
	 */
	protected function execute_query()
	{
		return mysql_query($this->strQuery, $this->resConnection);
	}


	/**
	 * Return the last error message
	 *
	 * @return string The error message
	 */
	protected function get_error()
	{
		return mysql_error($this->resConnection);
	}


	/**
	 * Return the number of affected rows
	 *
	 * @return int The number of affected rows
	 */
	protected function affected_rows()
	{
		return mysql_affected_rows($this->resConnection);
	}


	/**
	 * Return the last insert ID
	 *
	 * @return int The last insert ID
	 */
	protected function insert_id()
	{
		return mysql_insert_id($this->resConnection);
	}


	/**
	 * Explain the current query
	 *
	 * @return array The information array
	 */
	protected function explain_query()
	{
		return mysql_fetch_assoc(mysql_query('EXPLAIN ' . $this->strQuery, $this->resConnection));
	}


	/**
	 * Create a Result object
	 *
	 * @param resource $resResult The database result
	 * @param string   $strQuery  The query string
	 *
	 * @return Result The result object
	 */
	protected function createResult($resResult, $strQuery)
	{
		return new Result($resResult, $strQuery);
	}
}
