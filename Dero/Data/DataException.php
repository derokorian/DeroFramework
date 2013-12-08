<?php

namespace Dero\Data\Exception;

/**
 * Different exception types for the data layer
 * @author Ryan Pallas
 */
class DataException extends \Exception {}

class DataAccessException extends \Exception {}

class DataReadException extends DataAccessException {}

class DataWriteException extends DataAccessException {}


?>