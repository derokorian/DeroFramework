<?php

use Dero\Core\Retval;
use Dero\Data\DataException;

class BaseModelExtensionTest extends PHPUnit_Framework_TestCase
{
    /** @var Dero\Data\PDOMysql */
    private $oDataInterface;

    /** @var BaseModelExtension */
    private $oModel;

    public function setUp()
    {
        $this->oDataInterface = $this->getMock(
            'Dero\Data\PDOMysql',
            ['Query'],
            ['default']
        );
        $this->oModel = new BaseModelExtension(
            $this->oDataInterface
        );
    }

    public function testInstantiates()
    {
        $this->assertNotNull($this->oModel);
    }

    public function testValidateSuccess()
    {
        $aVars = [
            'test_id' => 1,
            'test_int' => 1234,
            'test_bool' => true,
            'test_dec' => '-1.234',
            'test_nullable' => null,
            'test_string' => 'string',
            'test_fixed' => 'fixed'
        ];
        $oRet = $this->oModel->Validate($aVars);
        $this->assertFalse($oRet->HasFailure());
    }

    /**
     * @dataProvider provideValidateFailures
     * @param $aVars
     * @param $strError
     */
    public function testValidateFailure($aVars, $strError)
    {
        $oRet = $this->oModel->Validate($aVars);
        $this->assertTrue($oRet->HasFailure());
        $this->assertEquals(
            $strError,
            $oRet->GetError()
        );
    }

    public function provideValidateFailures()
    {
        return [
            'int' => [
                [
                    'test_id' => 1,
                    'test_int' => 'not int',
                    'test_bool' => true,
                    'test_dec' => '-1.234',
                    'test_nullable' => null,
                    'test_string' => 'string',
                    'test_fixed' => 'fixed'
                ],
                'test_int must be a valid integer.'
            ],
            'bool' => [
                [
                    'test_id' => 1,
                    'test_int' => '123',
                    'test_bool' => 'not true',
                    'test_dec' => '-1.234',
                    'test_nullable' => null,
                    'test_string' => 'string',
                    'test_fixed' => 'fixed'
                ],
                'test_bool must be a valid boolean.'
            ],
            'dec' => [
                [
                    'test_id' => 1,
                    'test_int' => '123',
                    'test_bool' => true,
                    'test_dec' => '-1.fail',
                    'test_nullable' => null,
                    'test_string' => 'string',
                    'test_fixed' => 'fixed'
                ],
                'test_dec must be a valid decimal.'
            ],
            'string_reg' => [
                [
                    'test_id' => 1,
                    'test_int' => 123,
                    'test_bool' => true,
                    'test_dec' => '-1.234',
                    'test_nullable' => null,
                    'test_string' => null, // required, null is not set!
                    'test_fixed' => 'fixed'
                ],
                'test_string is required.'
            ],
            'string_pat' => [
                [
                    'test_id' => 1,
                    'test_int' => 123,
                    'test_bool' => true,
                    'test_dec' => '-1.234',
                    'test_nullable' => null,
                    'test_string' => 'hello world', // no spaces allowed
                    'test_fixed' => 'fixed'
                ],
                'test_string did not validate.'
            ],
            'string_len' => [
                [
                    'test_id' => 1,
                    'test_int' => 123,
                    'test_bool' => true,
                    'test_dec' => '-1.234',
                    'test_nullable' => null,
                    'test_string' => 'thisIsWayTooLong',
                    'test_fixed' => 'fixed'
                ],
                'test_string is longer than max length (15).'
            ],
            'string_fix' => [
                [
                    'test_id' => 1,
                    'test_int' => 123,
                    'test_bool' => true,
                    'test_dec' => '-1.234',
                    'test_nullable' => null,
                    'test_string' => 'string',
                    'test_fixed' => 'blah'
                ],
                'test_fixed must be fixed length (5).'
            ]
        ];
    }

    public function testGenerateCreateTable()
    {
        $this->assertEquals(
            "CREATE TABLE IF NOT EXISTS `test` (\n\t".
            "`test_id` INT NOT NULL auto_increment  PRIMARY KEY,\n\t".
            "`test_int` INT NOT NULL  ,\n\t\t".
            "FOREIGN KEY test_test_id (test_int)\n\t\t\t".
            "REFERENCES `test` (test_id),\n\t".
            "`test_bool` TINYINT(1) NOT NULL  ,\n\t".
            "`test_dec` DECIMAL(10, 3) NOT NULL  ,\n\t".
            "`test_nullable` INT NULL  ,\n\t".
            "`test_string` VARCHAR(15) NOT NULL  UNIQUE,\n\t".
            "`test_fixed` CHAR(5) NOT NULL  ,\n\t".
            "`created` DATETIME NOT NULL  ,\n\t".
            "`modified` DATETIME NOT NULL  \n".
            ") Engine=InnoDB",
            $this->oModel->GetCreateTable()
        );
    }

    private function setQueryException()
    {
        $this->oDataInterface
            ->method('Query')
            ->will(
                $this->throwException(new DataException)
            );
    }

    public function setQuerySuccess($mRet)
    {
        $oRetval = new Retval();
        $oRetval->Set($mRet);
        $this->oDataInterface
            ->method('Query')
            ->willReturn($oRetval);
        return $oRetval;
    }

    public function testVerifyModelDefinitionFailedShow()
    {
        $this->setQueryException();
        $oRet = $this->oModel->VerifyModelDefinition();
        $this->assertTrue($oRet->HasFailure());
        $this->assertEquals('Unable to query database', $oRet->GetError());
    }

    public function testVerifyModelDefinitionFailedDescribe()
    {
        $oRet1 = new QueryRet();
        $oRet1->Set(['table exists']);
        $oRet2 = new QueryRet();
        $oRet2->Set(QueryRet::THROW_EXCEPTION);

        $this->oDataInterface
            ->method('Query')
            ->will($this->onConsecutiveCalls($oRet1, $oRet2));

        $oRet = $this->oModel->VerifyModelDefinition();
        $this->assertTrue($oRet->HasFailure());
        $this->assertEquals('Unable to query database', $oRet->GetError());
    }

    public function testVerifyModelDefinitionSuccessCreate()
    {
        $oRet1 = new QueryRet();
        $oRet1->Set([]);
        $oRet2 = new Retval();
        $oRet2->Set('Created Table');

        $this->oDataInterface
            ->method('Query')
            ->will($this->onConsecutiveCalls($oRet1, $oRet2));

        $oRet = $this->oModel->VerifyModelDefinition();
        $this->assertFalse($oRet->HasFailure());
        $this->assertEquals(['test' => 'Table successfully created'], $oRet->Get());
    }

    public function testVerifyModelDefinitionSuccessAlter()
    {
        $oRet1 = new QueryRet();
        $oRet1->Set(['table exists']);
        $oRet2 = new QueryRet();
        $oRet2->Set([[
            'Field' => 'test_id',
            'Type' => 'int(10) unsigned',
            'Null' => 'No',
            'Key' => 'Pri',
            'Default' => null,
            'Extra' => null
        ]]);
        $oRet3 = new QueryRet();
        $oRet3->Set(['table updated']);

        $this->oDataInterface
            ->method('Query')
            ->will($this->onConsecutiveCalls($oRet1, $oRet2, $oRet3));

        $oRet = $this->oModel->VerifyModelDefinition();
        $this->assertFalse($oRet->HasFailure());
        $this->assertEquals(['test' => [
            'Updating column test_id',
            'Adding column test_int',
            'Adding column test_bool',
            'Adding column test_dec',
            'Adding column test_nullable',
            'Adding column test_string',
            'Adding column test_fixed',
            'Adding column created',
            'Adding column modified',
            'test has been updated'
        ]], $oRet->Get());
    }
}

class QueryRet
{
    const THROW_EXCEPTION = 'THROW_EXCEPTION';
    private $mRet;
    public function Set($mVal) {
        $this->mRet = $mVal;
    }
    public function GetAll()
    {
        if( $this->mRet === self::THROW_EXCEPTION )
        {
            throw new DataException;
        }
        return $this->mRet;
    }
}

class BaseModelExtension extends \Dero\Data\BaseModel
{
    const TABLE_NAME = 'test';

    const COLUMNS = [
        'test_id' => [
            COL_TYPE => COL_TYPE_INTEGER,
            KEY_TYPE => KEY_TYPE_PRIMARY,
            DB_REQUIRED => false,
            DB_EXTRA => [
                DB_AUTO_INCREMENT
            ]
        ],
        'test_int' => [
            COL_TYPE => COL_TYPE_INTEGER,
            KEY_TYPE => KEY_TYPE_FOREIGN,
            FOREIGN_TABLE => 'test',
            FOREIGN_COLUMN => 'test_id',
            DB_REQUIRED => true
        ],
        'test_bool' => [
            COL_TYPE => COL_TYPE_BOOLEAN,
            DB_REQUIRED => true
        ],
        'test_dec' => [
            COL_TYPE => COL_TYPE_DECIMAL,
            COL_LENGTH => 10,
            'scale' => 3,
            DB_REQUIRED => false
        ],
        'test_nullable' => [
            COL_TYPE => COL_TYPE_INTEGER,
            DB_REQUIRED => false,
            DB_EXTRA => [
                DB_NULLABLE
            ]
        ],
        'test_string' => [
            COL_TYPE => COL_TYPE_STRING,
            KEY_TYPE => KEY_TYPE_UNIQUE,
            COL_LENGTH => 15,
            DB_REQUIRED => true,
            DB_VALIDATION => '/^[a-z][a-z0-9]+$/i'
        ],
        'test_fixed' => [
            COL_TYPE => COL_TYPE_FIXED_STRING,
            COL_LENGTH => 5,
            DB_REQUIRED => true,
            DB_VALIDATION => '/^[a-z]+$/i'
        ],
        'created' => [
            COL_TYPE => COL_TYPE_DATETIME,
            DB_REQUIRED => false
        ],
        'modified' => [
            COL_TYPE => COL_TYPE_DATETIME,
            DB_REQUIRED => false
        ]
    ];

    public function getCreateTable() {
        return $this->GenerateCreateTable();
    }
}
