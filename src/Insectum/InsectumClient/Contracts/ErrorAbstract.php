<?php

namespace Insectum\InsectumClient\Contracts;


/**
 * Class ErrorAbstract
 * @package Insectum\InsectumClient\Contracts
 */
abstract class ErrorAbstract
{
    /**
     * @var \Exception
     */
    protected $exception;

    /**
     * @param \Exception $exception
     */
    function __construct(\Exception $exception)
    {

        $this->exception = $exception;
    }

    /**
     * @return array
     */
    abstract public function getPayload($clientId, $clientType = 'g');

}