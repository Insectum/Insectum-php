<?php

/*
 * Your script can also be terminated by the built-in script timer.
 * When the timer expires the script will be aborted and as with the above client disconnect case,
 * if a shutdown function has been registered it will be called. Within this shutdown function
 * you can check to see if a timeout caused the shutdown function to be called
 * by calling the connection_status() function.
 * This function will return 2 if a timeout caused the shutdown function to be called.
 */

namespace Insectum\InsectumClient\Exceptions;

class TimeoutException extends StatefulException {}