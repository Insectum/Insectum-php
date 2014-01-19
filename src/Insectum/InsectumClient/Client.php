<?php


namespace Insectum\InsectumClient;

use Carbon\Carbon;
use Insectum\Helpers\Arr;


/**
 * Class Client
 * @package Insectum\InsectumClient
 */
class Client
{
    /**
     * @var bool
     */
    protected static $enabled = true;
    /**
     * @var callable
     */
    protected $customClientIdGenerator;
    /**
     * @var callable|null
     */
    protected $previousErrorHandler;
    /**
     * @var callable|null
     */
    protected $previousExceptionHandler;
    /**
     * @var string
     */
    protected $stage;
    /**
     * @var string
     */
    protected $clientIdCookieName;
    /**
     * @var string
     */
    protected $clientTypeCookieName;
    /**
     * @var string
     */
    protected $clientId;
    /**
     * @var string
     */
    protected $clientType;
    /**
     * @var \Insectum\Insectum\Log|Remote
     */
    protected $logger;
    /**
     * @var array
     */
    protected $config;
    /**
     * @var callable[]
     */
    protected $customExceptionHandlers;

    /**
     * @param array $config
     */
    function __construct(array $config = null)
    {
        $this->config = $config;

        $this->stage = Arr::get($config, 'stage', 'production');

        $server = Arr::get($config, 'server');

        if (is_null($server) || $server == 'local') {
            $this->logger = $this->getLogger($config);
        } else {
            $this->logger = new Remote($config);
        }

        $this->clientIdCookieName = Arr::get($config, 'client_id_cookie_name', '_uuid');
        $this->clientTypeCookieName = Arr::get($config, 'client_type_cookie_name', '_uuid-t');

        $this->getClientId();
    }

    /**
     * @param array $config
     * @return \Insectum\Insectum\Log
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    protected function getLogger(array $config)
    {

        if (!class_exists('Insectum\\Insectum\\Log')) {
            throw new \RuntimeException('Package insectum/insectum is not installed');
        }

        $storageConfig = Arr::get($config, 'storage', array('type' => 'mongo'));

        $storageType = Arr::get($storageConfig, 'type', '');
        $storageType = ucfirst(strtolower($storageType));
        $storageClass = 'Insectum\\Insectum\\Storage\\' . $storageType;

        if (!class_exists($storageClass)) {
            throw new \InvalidArgumentException('Unknown storage type');
        }

        $storageParams = Arr::except($storageConfig, 'type', array());
        $reflect = new \ReflectionClass($storageClass);
        $storage = $reflect->newInstanceArgs($storageParams);

        $logger = new \Insectum\Insectum\Log($config, $storage);

        return $logger;

    }

    /**
     * Get unique client id and store it
     * @return string
     */
    public function getClientId()
    {
        if ($this->clientId) {
            return $this->clientId;
        }

        // Get the unique client id
        // First check the stored in cookie id
        $storedClientId = Arr::get($_COOKIE, $this->clientIdCookieName);
        $storedClientType = Arr::get($_COOKIE, $this->clientTypeCookieName, 'g');

        // And get generated id
        if (is_callable($this->customClientIdGenerator)) {
            $gen = call_user_func($this->customClientIdGenerator);
        } elseif (isset($this->config['client_id_generator']) && is_callable($this->config['client_id_generator'])) {
            $gen = call_user_func($this->config['client_id_generator']);
        } else {
            $gen = $this->clientIdGenerator();
        }

        // Normalize
        if (!is_array($gen) && !is_object($gen)) {
            $gen = array($gen);
        }

        $genClientId = Arr::get($gen, 'id');
        $genClientType = Arr::get($gen, 'type', 'g');

        // If there is no stored id, or client type has changed (first letter if id)
        // Then we take generated id, set it to cookie and store for a month
        if (empty($storedClientId) || $storedClientType != $genClientType) {
            $this->clientId = $genClientId;
            $this->clientType = $genClientType;

            setcookie($this->clientIdCookieName, $this->clientId, time() + 60 * 60 * 24 * 30, '/');
            setcookie($this->clientTypeCookieName, $this->clientType, time() + 60 * 60 * 24 * 30, '/');
        } // Else we use stored id
        else {
            $this->clientId = $storedClientId;
            $this->clientType = $storedClientType;
        }

        return array('id' => $this->clientId, 'type' => $this->clientType);
    }

    /**
     * Default client ID generator
     * Any generator should return array with id and type fields
     * @return array
     */
    protected function clientIdGenerator()
    {
        return array(
            'id' => \Rhumsaa\Uuid\Uuid::uuid1(),
            'type' => 'g'
        );
    }

    /**
     * Manually enable logger
     */
    public static function enable()
    {
        static::$enabled = true;
    }

    /**
     * Manually disable logger
     */
    public static function disable()
    {
        static::$enabled = false;
    }

    /**
     * Set custom client id generator
     * @param callable $func
     */
    public function setClientIdGenerator(callable $func)
    {
        $this->customClientIdGenerator = $func;
    }

    /**
     * @return string
     */
    public function getStage()
    {
        return $this->stage;
    }

    /**
     * @param string $stage
     */
    public function setStage($stage)
    {
        $this->stage = $stage;
    }

    /**
     * Register deferred handlers
     */
    public function deferred()
    {
        // TODO
    }

    /**
     * Register handlers
     */
    public function register()
    {
        $this->registerErrorHandler();

        $this->registerFatalErrorHandler();

        $this->registerExceptionHandler();

    }

    /**
     * Standart error handler
     */
    public function registerErrorHandler()
    {
        $this->previousErrorHandler = set_error_handler(array($this, 'errorHandler'));
    }

    /**
     * Handler for fatals
     */
    public function registerFatalErrorHandler()
    {
        register_shutdown_function(array($this, 'fatalErrorHandler'));
    }

    /**
     * Handler for exceptions
     */
    public function registerExceptionHandler()
    {
        $this->previousExceptionHandler = set_exception_handler(array($this, 'exceptionHandler'));
    }

    /**
     * Add custom exception handlers, that will be stacked and called after insectum execution
     * @param callable $handler
     */
    public function setCustomExceptionHandler(callable $handler)
    {
        $this->customExceptionHandlers[] = $handler;
    }

    /**
     * Handle error and create an exception
     * @param $errno
     * @param $str
     * @param $file
     * @param $line
     * @param null $context
     * @return bool
     */
    public function errorHandler($errno, $str, $file, $line, $context = null)
    {
        $class = $this->getErrorExceptionClass($errno);
        $e = new $class($str, $errno, 1, $file, $line, null, $context);
        $this->exceptionHandler($e);

        if ($this->previousErrorHandler && is_callable($this->previousErrorHandler)) {
            call_user_func($this->previousErrorHandler, $errno, $str, $file, $line, $context);
        }

        return false;
    }

    /**
     * Get class name from error num
     * @param int $errno
     * @return string
     */
    protected function getErrorExceptionClass($errno)
    {
        $class = '';
        switch ($errno) {
            case E_ERROR:
                $class = "Error";
                break;
            case E_WARNING:
                $class = "Warning";
                break;
            case E_PARSE:
                $class = "ParseError";
                break;
            case E_NOTICE:
                $class = "Notice";
                break;
            case E_CORE_ERROR:
                $class = "CoreError";
                break;
            case E_CORE_WARNING:
                $class = "CoreWarning";
                break;
            case E_COMPILE_ERROR:
                $class = "CompileError";
                break;
            case E_COMPILE_WARNING:
                $class = "CompileWarning";
                break;
            case E_USER_ERROR:
                $class = "UserError";
                break;
            case E_USER_WARNING:
                $class = "UserWarning";
                break;
            case E_USER_NOTICE:
                $class = "UserNotice";
                break;
            case E_STRICT:
                $class = "StrictNotice";
                break;
            case E_RECOVERABLE_ERROR:
                $class = "RecoverableError";
                break;
            default:
                $class = "Error";
                break;
        }

        $class = "Insectum\\InsectumClient\\Exceptions\\{$class}Exception";

        return $class;
    }

    /**
     * Log exceptions
     * @param \Exception $e
     * @param string $kind
     * @return bool
     */
    public function exceptionHandler(\Exception $e, $kind = 'backend')
    {
        if (!static::$enabled) {
            return false;
        }

        $errorClass = $this->getErrorClassByKind($kind);
        $err = new $errorClass($e);

        $this->logger->write($kind, $err->getPayload($this->clientId, $this->clientType), $this->stage);

        if (is_array($this->customExceptionHandlers)) {
            foreach ($this->customExceptionHandlers as $hadler) {
                call_user_func($hadler, $e);
            }
        }

        if ($this->previousExceptionHandler && is_callable($this->previousExceptionHandler)) {
            call_user_func($this->previousExceptionHandler, $e);
        } else {
            if (ini_get('display_errors') == "1") {
                die($e);
            }
        }

        return false;
    }

    /**
     * Check for fatal errors
     */
    public function fatalErrorHandler()
    {

        if (null === $error = error_get_last()) {
            return false;
        }

        if (!in_array($error['type'], array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE))) {
            return false;
        }

        if ($error && $error["type"] == E_ERROR) {
            $e = new Exceptions\FatalErrorException(
                $error["message"],
                $error["type"],
                1,
                $error["file"],
                $error["line"],
                null,
                null
            );
            $this->exceptionHandler($e);
        }

        return false;

    }

    protected function getErrorClassByKind($kind) {

        $class = 'Insectum\\InsectumClient\\Errors\\' . ucfirst(strtolower($kind));

        if ( ! class_exists($class) ) {
            throw new \InvalidArgumentException('Unknown error kind');
        }

        return $class;

    }

} 