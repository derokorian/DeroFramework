<?php

/**
 * Blog Model
 * @author Ryan Pallas
 * @package SampleSite
 * @namespace App\Model
 * @since 2013-12-07
 */
namespace App\Model;

use Dero\Core\RetVal;
use Dero\Data\DataException;
use Dero\Data\Factory;
use Dero\Data\Parameter;


class UserModel extends \Dero\Data\BaseModel
{
    protected static $TABLE_NAME = 'users';

    protected static $COLUMNS = [
        'user_id' => [
            COL_TYPE => COL_TYPE_INTEGER,
            KEY_TYPE => KEY_TYPE_PRIMARY,
            'required' => false,
            'extra' => [
                DB_AUTO_INCREMENT
            ]
        ],
        'username' => [
            COL_TYPE => COL_TYPE_STRING,
            KEY_TYPE => KEY_TYPE_UNIQUE,
            'col_length' => 25,
            'required' => true
        ],
        'email' => [
            COL_TYPE => COL_TYPE_STRING,
            KEY_TYPE => KEY_TYPE_UNIQUE,
            'col_length' => 100,
            'required' => true
        ],
        'first_name' => [
            COL_TYPE => COL_TYPE_STRING,
            'col_length' => 50,
            'required' => false,
            'extra' => [
                DB_NULLABLE
            ]
        ],
        'last_name' => [
            COL_TYPE => COL_TYPE_STRING,
            'col_length' => 50,
            'required' => false,
            'extra' => [
                DB_NULLABLE
            ]
        ],
        'password' => [
            COL_TYPE => COL_TYPE_FIXED_STRING,
            'col_length' => 128,
            'required' => true
        ],
        'salt' => [
            COL_TYPE => COL_TYPE_FIXED_STRING,
            'col_length' => 128,
            'required' => false
        ],
        'active' => [
            COL_TYPE => COL_TYPE_BOOLEAN,
            'required' => false
        ],
        'created' => [
            COL_TYPE => COL_TYPE_DATETIME,
            'required' => false
        ],
        'modified' => [
            COL_TYPE => COL_TYPE_DATETIME,
            'required' => false
        ]
    ];

    /**
     * Constructor
     */
    public function __construct($db = null)
    {
        if( !$db instanceof \Dero\Data\DataInterface )
            $db = Factory::GetDataInterface('default');
        parent::__construct($db);
    }

    public function addUser($strUser, $strEmail, $strPass, $strFName = '', $strLName = '')
    {

    }

    public function checkLogin($strUser, $strPass)
    {
        $oRetVal = new RetVal();
        $oParam = new Parameter('username', $strUser, DB_PARAM_STR);
        $strSql = 'SELECT password, salt FROM ' . static::$TABLE_NAME;
        $strSql .= ' WHERE username = :username AND active = 1';
        try
        {
            $oRetVal->Set(
                $this->DB
                     ->Prepare($strSql)
                     ->BindParam($oParam)
                     ->Execute()
                     ->Get()
            );
        } catch (DataException $e) {
            $oRetVal->AddError('Unable to query database', $e);
            return $oRetVal;
        }

    }

    private function hashPassword($pass, $salt)
    {
        return hash('sha512', substr($salt, 0, 64) . $pass . substr($salt, 64));
    }

    private function generateSalt()
    {
        return hash('sha512', mt_rand());
    }
}