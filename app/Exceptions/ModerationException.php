<?php

namespace App\Exceptions;

use RuntimeException;

/** Levée quand une transition de modération viole une règle éditoriale bloquante. */
class ModerationException extends RuntimeException {}
