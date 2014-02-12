<?php

/**
 * Blog Model
 * @author Ryan Pallas
 * @package SampleSite
 * @namespace App\Model
 * @since 2013-12-07
 */
namespace App\Model;

use \Dero\Data\Factory;

class UserModel extends \Dero\Data\BaseModel
{
    protected static $TABLE_NAME = 'users';

    protected static $COLUMNS = [
        'user_id' => [
            'col_type' => COL_TYPE_INTEGER,
            'required' => false,
            'key' => KEY_TYPE_PRIMARY,
            'extra' => [
                DB_AUTO_INCREMENT
            ]
        ],
        'username' => [
            'col_type' => COL_TYPE_STRING,
            'col_length' => 25,
            'required' => true,
            'key' => KEY_TYPE_UNIQUE
        ],
        'email' => [
            'col_type' => COL_TYPE_STRING,
            'col_length' => 100,
            'required' => true,
            'key' => KEY_TYPE_UNIQUE
        ],
        'first_name' => [
            'col_type' => COL_TYPE_STRING,
            'col_length' => 50,
            'required' => false,
            'extra' => [
                'nullable'
            ]
        ],
        'last_name' => [
            'col_type' => COL_TYPE_STRING,
            'col_length' => 50,
            'required' => false,
            'extra' => [
                DB_NULLABLE
            ]
        ],
        'password' => [
            'col_type' => COL_TYPE_FIXED_STRING,
            'col_length' => 128,
            'required' => true
        ],
        'salt' => [
            'col_type' => COL_TYPE_FIXED_STRING,
            'col_length' => 128,
            'required' => false
        ],
        'active' => [
            'col_type' => COL_TYPE_BOOLEAN,
            'required' => false
        ],
        'created' => [
            'col_type' => COL_TYPE_DATETIME,
            'required' => false
        ],
        'modified' => [
            'col_type' => COL_TYPE_DATETIME,
            'required' => false
        ]
    ];

    /**
     * Constructor
     */
    public function __construct($db = null)
    {
        if( !$db instanceof DataInterface )
            $db =Factory::GetDataInterface('default');
        parent::__construct($db);
    }
}