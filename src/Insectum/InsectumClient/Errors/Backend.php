<?php

namespace Insectum\InsectumClient\Errors;

use Insectum\InsectumClient\Contracts\ErrorAbstract;
use Insectum\InsectumClient\Exceptions\StatefulException;
use Carbon\Carbon;

class Backend extends ErrorAbstract {

    /**
     * @return array
     */
    public function getPayload($clientId, $clientType = 'g')
    {
        $e = $this->exception;

        $payload = array(
            'type' => get_class($e),
            'code' => $e->getCode(),
            'msg' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'method' => $_SERVER['REQUEST_METHOD'],
            'url' => $_SERVER['SERVER_PROTOCOL'] . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            'server_name' => gethostname(),
            'server_ip' => $_SERVER['SERVER_ADDR'],
            'backtrace' => $e->getTraceAsString(),
            'context' => null,
            'session' => isset($_SESSION) ? $_SESSION : null,
            'client_ip' => $_SERVER['REMOTE_ADDR']
        );

        $payload['client_type'] = $clientType;
        $payload['client_id'] = $clientId;
        $payload['occurred_at'] = new Carbon();

        if ($e instanceof StatefulException) {
            $payload['context'] = $e->getContext();
        }

        return $payload;
    }


} 