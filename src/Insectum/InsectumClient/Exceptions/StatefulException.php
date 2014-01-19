<?php

namespace Insectum\InsectumClient\Exceptions;

use ErrorException;

/**
 * Class StatefulException
 * @package Insectum\InsectumClient\Exceptions
 * Exceptions that can contain their context
 */
abstract class StatefulException extends ErrorException
{

    /**
     * @var array|null
     */
    protected $context;


    /**
     * Context should be set with get_defined_vars() in point of throwing
     * Or with $context param in custom error handler
     *
     * @param string $message
     * @param int $code
     * @param int $severity
     * @param string $filename
     * @param int $lineno
     * @param \Exception $previous
     * @param array $context
     */
    public function __construct(
        $message = "",
        $code = 0,
        $severity = 1,
        $filename = __FILE__,
        $lineno = __LINE__,
        \Exception $previous = null,
        array $context = null
    )
    {
        parent::__construct($message, $code, $severity, $filename, $lineno, $previous);
        $this->setContext($context);
    }

    /**
     * @param $context
     * @return array|null
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param array $context
     * @return $this
     */
    public function setContext(array $context = null)
    {
        $this->context = is_array($context) ? $context : array();
        return $this;
    }


} 