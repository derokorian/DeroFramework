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
use \Dero\Data\DataException;
use Dero\Data\DataInterface;
use \Dero\Data\Factory;
use \Dero\Data\ParameterCollection;

class BlogModel extends \Dero\Data\BaseModel
{
    protected static $TABLE_NAME = 'blog_post';

    protected static $COLUMNS = [
        'post_id' => [
            'col_type' => COL_TYPE_INTEGER,
            'required' => false,
            'key' => KEY_TYPE_PRIMARY,
            'extra' => [
                'auto_increment'
            ]
        ],
        'user_id' => [
            'col_type' => COL_TYPE_INTEGER,
            'required' => true,
            'key' => KEY_TYPE_FOREIGN,
            'foreign_table' => 'users',
            'foreign_column' => 'user_id'
        ],
        'title' => [
            'col_type' => COL_TYPE_STRING,
            'col_length' => 100,
            'required' => true
        ],
        'body' => [
            'col_type' => COL_TYPE_TEXT,
            'required' => true
        ],
        'published' => [
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
            $db = Factory::GetDataInterface('default');
        parent::__construct($db);
    }

    /**
     * Gets posts as specified by options
     * @param array $aOpts
     * @return \Dero\Core\RetVal
     */
    public function getPosts(Array $aOpts)
    {
        $oRetVal = new RetVal();
        $oParams = new ParameterCollection();
        if( !isset($aOpts['order_by']) )
            $aOpts['order_by'] = 'post_date DESC';
        $sql = 'SELECT * FROM ' . self::$TABLE_NAME . ' ';
        $sql .= $this->GenerateCriteria($oParams, $aOpts);
        try {
            $oRetVal->Set(
                $this->DB->Prepare($sql)
                    ->BindParams($oParams)
                    ->Execute()
                    ->GetAll());
        } catch (DataException $e) {
            $oRetVal->SetError('Unable to query database', $e);
        }
        return $oRetVal;
    }
}