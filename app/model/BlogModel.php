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
            COL_TYPE => COL_TYPE_INTEGER,
            KEY_TYPE => KEY_TYPE_PRIMARY,
            'required' => false,
            'extra' => [
                DB_AUTO_INCREMENT
            ]
        ],
        'user_id' => [
            COL_TYPE => COL_TYPE_INTEGER,
            KEY_TYPE => KEY_TYPE_FOREIGN,
            'required' => true,
            'foreign_table' => 'users',
            'foreign_column' => 'user_id'
        ],
        'title' => [
            COL_TYPE => COL_TYPE_STRING,
            'col_length' => 100,
            'required' => true
        ],
        'body' => [
            COL_TYPE => COL_TYPE_TEXT,
            'required' => true
        ],
        'published' => [
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
        $sql = 'SELECT title, body, p.created, p.modified, u.username '
             . 'FROM ' . self::$TABLE_NAME . ' p '
             . 'JOIN users u ON u.user_id = p.user_id '
             . $this->GenerateCriteria($oParams, $aOpts);
        try {
            $oRetVal->Set(
                $this->DB
                     ->Prepare($sql)
                     ->BindParams($oParams)
                     ->Execute()
                     ->GetAll()
            );
        } catch (DataException $e) {
            $oRetVal->AddError('Unable to query database', $e);
        }
        return $oRetVal;
    }

    /**
     * Gets a post count as specified by options
     * @param array $aOpts
     * @return \Dero\Core\RetVal
     */
    public function getPostCount(Array $aOpts)
    {
        $oRetVal = new RetVal();
        $oParams = new ParameterCollection();
        $sql = 'SELECT count(1) '
            . 'FROM ' . self::$TABLE_NAME . ' '
            . $this->GenerateCriteria($oParams, $aOpts);
        try {
            $oRetVal->Set(
                $this->DB
                     ->Prepare($sql)
                     ->BindParams($oParams)
                     ->Execute()
                     ->GetScalar()
            );
        } catch (DataException $e) {
            $oRetVal->AddError('Unable to query database', $e);
        }
        return $oRetVal;
    }
}