<?php

/**
 * Blog Model
 * @author Ryan Pallas
 * @package SampleSite
 * @namespace App\Model
 * @since 2013-12-07
 */
namespace App\Model;

use \Dero\Data\DataException;
use \Dero\Data\Factory;
use \Dero\Data\Parameter;
use \Dero\Data\ParameterCollection;

class BlogModel extends \Dero\Data\BaseModel
{
    const TABLE_NAME = 'blog_post';

    private static $COLUMNS = [
        'post_id' => [
            'col_type' => COL_TYPE_INTEGER,
            'required' => false,
            'nullable' => false,
            'key' => KEY_TYPE_PRIMARY
        ],
        'user_id' => [
            'col_type' => COL_TYPE_INTEGER,
            'required' => true,
            'nullable' => false,
            'key' => KEY_TYPE_FOREIGN
        ],
        'title' => [
            'col_type' => COL_TYPE_TEXT,
            'required' => true,
            'nullable' => false,
            'key' => null
        ],
        'body' => [
            'col_type' => COL_TYPE_TEXT,
            'required' => true,
            'nullable' => false,
            'key' => null
        ],
        'published' => [
            'col_type' => COL_TYPE_BOOLEAN,
            'required' => false,
            'nullable' => false,
            'key' => null
        ],
        'created' => [
            'col_type' => COL_TYPE_DATETIME,
            'required' => false,
            'nullable' => false,
            'key' => null
        ],
        'modified' => [
            'col_type' => COL_TYPE_DATETIME,
            'required' => false,
            'nullable' => true,
            'key' => null
        ]
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $db = Factory::GetDataInterface('default');
        parent::__construct($db);
    }

    /**
     * Gets posts as specified by options
     * @param array $aOpts
     * @return bool|array
     */
    public function getPosts(Array $aOpts)
    {
        $oParams = new ParameterCollection();
        $this->where(true);
        $sql = 'SELECT * FROM ' . self::TABLE_NAME;
        foreach( self::$COLUMNS as $name => $def)
        {
            if( isset($aOpts[$name]) )
            {
                $type = $this->getParamTypeFromColType($aOpts[$name], $def);
                if( $type === DB_PARAM_NULL )
                    $sql .= $this->where() . sprintf('%s IS :%s ', $name, $name);
                else
                    $sql .= $this->where() . sprintf('%s=:%s ', $name, $name);
                $oParams->Add(new Parameter($name, $aOpts[$name], $type));
            }
        }

        $sql .= 'ORDER BY ';
        if( isset($aOpts['order_by']) )
            $sql .= $aOpts['order_by'];
        else
            $sql .= 'post_date DESC ';

        if( isset($aOpts['rows']) || isset($aOpts['skip']) )
        {
            if(  isset($aOpts['rows']) && isset($aOpts['skip']) )
            {
                $sql .= 'LIMIT :rows OFFSET :skip';
                $oParams->Add(new Parameter('rows', $aOpts['rows']));
                $oParams->Add(new Parameter('skip', $aOpts['skip']));
            }
            elseif( isset($aOpts['rows']) )
            {
                $sql .= 'LIMIT :rows';
                $oParams->Add(new Parameter('rows', $aOpts['rows']));
            }
            else
            {
                $sql .= 'OFFSET :skip';
                $oParams->Add(new Parameter('skip', $aOpts['skip']));
            }
        }

        try {
            return $this->DB->Prepare($sql)
                    ->BindParams($oParams)
                    ->Execute()
                    ->GetAll();
        } catch (DataException $e) {
            return false;
        }
    }
}