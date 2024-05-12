<?php

namespace App;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Class Notify (log helper)
 *
 * @param mixed   $arg  object to print_r on
 * @param string  $desc optional description
 * @param boolean $force
 *
 * @package Libraries
 */
class Notify
{
    const INFO     = 'info';
    const DEBUG    = 'debug';
    const ERROR    = 'error';
    const ALERT    = 'alert';
    const NOTICE   = 'notice';
    const WARNING  = 'warning';
    const CRITICAL = 'critical';
    const DEFAULT_TIME_ZONE = 'America/New_York';
    const DEFAULT_SYSTEM_TIME_ZONE    = 'UTC';
    const DEFAULT_DATETIME_FORMAT     = 'Y-m-d H:i:s';

    public static $force = false;

    public static $skip = false;

    /** @var bool override put() and log() instead */
    public static $logOverride = false;
    public static $useMonolog = false;

    protected static $microStart;

    /**
     * @var int Description prefix on messages that lives for the life of the instantiation of the user call.
     *          .. This allows us to track all messages connected to that instance in the log
     *          .. (and not messages from other instances that might have been sent at the same time).
     */
    protected static $instance = 0;

    /** @var Logger */
    public static $logger = null;

    /** @var \PDO */
    public $db = null;

    public static bool $returnOutputOnly = false;

    public static function get(): self
    {
        $self = new static();
        $self::$returnOutputOnly = true;
        return $self;
    }

    /**
     * Put error to standard out
     * - does NOT use $force; will log the error if called
     *
     * @param mixed  $arg      Argument to dump to log
     * @param string $desc     description/string to put to log
     * @param array  $options  Optional options: e.g.: "db" for the DB to use for writing
     * @uses Notify::log($obj, $msg, ['noFileInfo' => 1]) If we do NOT want file info (where the log was called) in the message
     * @uses Notify::log($obj, $msg, ['type'=> ...is either self::ERROR, INFO, DEBUG, or WARNING])
     * @uses Notify::log($obj, $msg, ['origType' => Some::class]) // if need to overwrite the original type of $arg
     * @uses Notify::log($obj, $msg, ['format' => 'pretty']) // format of data; default: "json"; or "pretty" (print_r)
     * @uses Notify::log($obj, $msg, ['showDebug' => 1]) // shows debug backtrace info
     */
    public static function log($arg = '', string $desc = "", array $options = []): void
    {
        $type = !empty($options['type']) ? $options['type']
            : (strtolower(getenv('logger_logging_level')) === 'debug' ? self::DEBUG : self::INFO);
        $origType = empty($options['origType']) ? self::getType($arg) : $options['origType'];
        $data = gettype($arg) == 'boolean' ? ($arg ? 'TRUE' : 'FALSE') : $arg;

        $pre = $post = '';

        if (!empty($options['showDebug'])) {
            $pre = self::showBacktrace('out') . "\n";
        }

        if (!empty($options['noFileInfo'])) {
            $pre = '';
        } else {
            date_default_timezone_set(date_default_timezone_get() ?: self::DEFAULT_TIME_ZONE);
            $x     = debug_backtrace();
            $f     = current($x);
            $index = (stripos($f['file'], 'Notify.php') !== false) ? 1 : 0;
            $index = empty($options['upOne']) ? $index : ++$index;
            $f     = $x[$index];
            $m     = isset($x[$index + 1]['function']) ? $x[$index + 1]['function'] : '';

            $date = '[' . date(self::DEFAULT_DATETIME_FORMAT) . '] ';

            $desc = (basename($f['file']) . ' / ' . $m . ' / ' . $f['line'])
                    . ($desc ? ": $desc: " : '')
                    . '[' . self::getType($origType) . ']';

            $pre .= $date . $desc ;

            if (empty($arg)) {
                $pre .= ' [empty] ';
            }
            // array_unshift($data, [$pre]);
        }

        if (self::$useMonolog) {
            $data = is_array($arg) ? $arg : [$arg]; // data needs to be array
            // array_shift($data); // this would be shifting out part of what we passed, so, not sure why we'd do that
            self::$logger->{$type}($desc, $data);
        } else {
            $query = "INSERT INTO system_log (message, uid, severity, time) VALUES (?, ?, ?, ?)";

            // $data = empty($options['format']) ? json_encode($data) : print_r($data, true);
            // default to "pretty" for php7
            $data = print_r($data, true);
            $data = $pre . $data;
            // $data = substr($data, 0,200);
            $args = [$data, 1, 1, time()];

            DB::statement('SET NAMES utf8mb4');

            DB::statement($query, $args);
        }

        /** * if SERVER_NAME is not set, running command line: put and exit */
        if (!isset($_SERVER['SERVER_NAME'])) {
            // we will echo out if force is set or $type is self::ERROR
            if (!self::$force && $type != self::ERROR) {
                return;
            }

            self::put($arg, $desc);
            echo "\n";
        }
    }

    /**
     * If input is not a string already, serialize to string
     *
     * @param mixed $var - Variable to stringify
     *
     * @return string
     */
    public static function stringify($var)
    {
        if (is_string($var)) {
            return $var;
        }

        if (is_numeric($var)) {
            return $var . '';
        }

        return serialize($var);
    }

    /**
     * Write log by type
     *
     * @param mixed  $arg      Argument to dump to log
     * @param string $desc     description/string to put to log
     * @param string $type     is either self::ERROR, self::INFO, or self::WARNING
     * @param string $origType Original type of $arg
     *
     * @return void
     */
    private static function writeLogByType($arg, $desc, $type, $origType)
    {
        // post fix desc; adding token to track msgs in logs for same instance
        $desc = Notify::getInstance() . ': ' . $desc . ' ';
        DB::statement("INSERT INTO system_log (message, uid, severity, time) VALUES (?, ?, ?, ?)", print_r([self::put($arg), $desc, $type, $origType], true),
                      1,
                      1,
                      time()
        );
        return;
    }

    /**
     * Write log by type using one of the known types.
     *
     * @param mixed  $arg      Argument to dump to log
     * @param string $desc     description/string to put to log
     * @param string $type     is either self::ERROR, self::INFO, or self::WARNING
     * @param string $origType Original type of $arg
     *
     * @return void
     */
    private static function callLogType($arg, $desc, $type, $origType)
    {
        if ($arg === true || $arg === false) {
            Log::$type($desc . ': ' . ($arg ? 'TRUE' : 'FALSE'));
        } elseif (!is_array($arg) && !is_object($arg)) {
            Log::$type($desc . ': ' . $arg);
        } else {
            Log::$type($desc, [is_null($origType) ? self::getType($arg) : $origType]);
            Log::$type((array) $arg);
        }
    }

    /**
     * To have a messages that are connected via instance of Notify, set the instance attribute.
     *
     * @param int $length Length of the random instance value
     *
     * @return string
     */
    public static function getInstance($length = 5)
    {
        return (static::$instance ? static::$instance : (static::$instance = self::getToken($length)));
    }

    /**
     * Use alphanumerics to create random token via openssl_random_pseudo_bytes()
     *
     * @param int  $length      Length of the string to return
     * @param bool $numericOnly Return numeric only (default is FALSE); will return microtime (without the decimal).
     *
     * @return string
     */
    public static function getToken($length = 32, $numericOnly = false)
    {
        if ($numericOnly) {
            return str_replace('.', '', (string) microtime(true));
        }

        $token         = "";
        $codeAlphabet  = "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet .= "0123456789";

        for ($i = 0; $i < $length; $i++) {
            $token .= $codeAlphabet[self::cryptoRandSecure(0, strlen($codeAlphabet))];
        }

        return $token;
    }

    /**
     * Create a random string using openssl_random_pseudo_bytes()
     *
     * @param int $min Min value
     * @param int $max Max value
     *
     * @return mixed
     */
    public static function cryptoRandSecure($min, $max)
    {
        $range = $max - $min;
        if ($range < 0) {
            return $min;
        } // not so random...
        $log    = log($range, 2);
        $bytes  = (int) ($log / 8) + 1; // length in bytes
        $bits   = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);

        return $min + $rnd;
    }

    /**
     * Write log by type; where type is not a recognised type.
     *
     * @param mixed  $arg  Argument to dump to log
     * @param string $desc description/string to put to log
     * @param string $type is either self::ERROR, self::INFO, or self::WARNING
     *
     * @return void
     */
    //    private static function callLogDefault($arg, $desc, $type) {
    //        if (is_object($arg)) {
    //            $arg = (array) $arg;
    //        } elseif (is_string($arg) || !is_array($arg)) {
    //            $arg = [$arg];
    //        }
    //
    //        Log::info(self::stringify($type) . ': ' . $desc, $arg);
    //    }

    /**
     * Override for Connector Log write
     *
     * @param string $str     String for log write
     * @param int    $level   Integer level
     * @param int    $info    info level
     * @param int    $warning warning level
     *
     * @return void
     */
    public static function write($str, $level = 5, $info = 5, $warning = 2)
    {
        if (!is_string($str)) {
            $str = serialize($str);
        }

        switch ($level) {
            case $info:
                $level = self::INFO;
                break;
            case $warning:
                $level = self::WARNING;
                break;
            default:
                $level = self::ERROR;
                break;
        }

        self::log('', $str, ['type' => $level]);
    }

    /**
     * Put error to standard out
     * - checks $force; will only print if $force is true
     *
     * @param mixed  $arg        Argument to dump to log
     * @param string $desc       description/string to put to log
     * @param bool   $localForce flag of to force write or not
     * @param bool   $dateStamp  flag to put time stamp or not
     *
     * @uses $force
     *
     * @return void
     */
    public static function debug($arg, $desc = "", $localForce = true, $dateStamp = true)
    {
        if (!self::$force && !$localForce) {
            return;
        }

        $origType = self::getType($arg);

        // if toArray exists, use it
        if (is_object($arg) && method_exists($arg, 'toArray')) {
            $arg = $arg->toArray();
        }

        if (!isset($_SERVER['SERVER_NAME']) && self::$force) {
            self::put($arg, $desc, $dateStamp);
        } else {
            self::log($arg, $desc, ['origType' => $origType]);
        }
    }

    /**
     * Put error to standard out and store in `private_stash`; for saving log msgs of rare errors
     *
     * @param mixed  $arg  Argument to dump to log
     * @param string $desc Description/string to put to log
     * @param string $type is either self::ERROR, self::INFO, or self::WARNING
     *
     * @return void
     */
    public static function debugAndStash($arg, $desc, $type = self::ERROR)
    {
        self::log($arg, $desc, ['type' => $type]);
    }

    /**
     * If we wish to override $skip (to be able to not have to remove the debugs but skill the output)
     * @param bool $val
     */
    public static function setSkip($val)
    {
        self::$skip = $val;
    }

    public static function dd(...$args)
    {
        if (self::$skip) return;

        if (empty($args)) $args = [null];

        (new Collection($args))->each(function($item) {
            self::put($item);
        });
        exit();
    }

    /**
     * Put error to standard out -- and die (dump and die version)
     * - checks $force; will only print if $force is true
     *
     * @param mixed  $arg          Argument to dump to log
     * @param string $desc         description/string to put to log; sets $explodeArray===TRUE if "-e"
     * @param bool   $explodeArray flag to foreach thru array
     * @param bool   $dateStamp    flag to put time stamp or not
     *
     * @uses $force
     *
     * @return void
     */
    public static function dnd($arg = "\n", $desc = "", $explodeArray = false, $dateStamp = true)
    {
        if (self::$skip) {
            return;
        }

        $explodeArray = $explodeArray ?: $desc === '-e';

        if (!$explodeArray || !is_array($arg)) {
            self::put($arg, ': ' . $desc, $dateStamp);
        } else {
            foreach ($arg as $key => $item) {
                self::put($item, $key, $dateStamp);
            }
        }

        exit();
    }

    public static function sqlFormatted($sql_raw, $sendTo = 'dd', ...$args)
    {
        if (empty($sql_raw) || !is_string($sql_raw))
        {
            return false;
        }

        $sql_reserved_all = array (
            'ACCESSIBLE', 'ACTION', 'ADD', 'AFTER', 'AGAINST', 'AGGREGATE', 'ALGORITHM', 'ALL', 'ALTER', 'ANALYSE', 'ANALYZE', 'AND', 'AS', 'ASC',
            'AUTOCOMMIT', 'AUTO_INCREMENT', 'AVG_ROW_LENGTH', 'BACKUP', 'BEGIN', 'BETWEEN', 'BINLOG', 'BOTH', 'BY', 'CASCADE', 'CASE', 'CHANGE', 'CHANGED',
            'CHARSET', 'CHECK', 'CHECKSUM', 'COLLATE', 'COLLATION', 'COLUMN', 'COLUMNS', 'COMMENT', 'COMMIT', 'COMMITTED', 'COMPRESSED', 'CONCURRENT',
            'CONSTRAINT', 'CONTAINS', 'CONVERT', 'CREATE', 'CROSS', 'CURRENT_TIMESTAMP', 'DATABASE', 'DATABASES', 'DAY', 'DAY_HOUR', 'DAY_MINUTE',
            'DAY_SECOND', 'DEFINER', 'DELAYED', 'DELAY_KEY_WRITE', 'DELETE', 'DESC', 'DESCRIBE', 'DETERMINISTIC', 'DISTINCT', 'DISTINCTROW', 'DIV',
            'DO', 'DROP', 'DUMPFILE', 'DUPLICATE', 'DYNAMIC', 'ELSE', 'ENCLOSED', 'END', 'ENGINE', 'ENGINES', 'ESCAPE', 'ESCAPED', 'EVENTS', 'EXECUTE',
            'EXISTS', 'EXPLAIN', 'EXTENDED', 'FAST', 'FIELDS', 'FILE', 'FIRST', 'FIXED', 'FLUSH', 'FOR', 'FORCE', 'FOREIGN', 'FROM', 'FULL', 'FULLTEXT',
            'FUNCTION', 'GEMINI', 'GEMINI_SPIN_RETRIES', 'GLOBAL', 'GRANT', 'GRANTS', 'GROUP', 'HAVING', 'HEAP', 'HIGH_PRIORITY', 'HOSTS', 'HOUR', 'HOUR_MINUTE',
            'HOUR_SECOND', 'IDENTIFIED', 'IF', 'IGNORE', 'IN', 'INDEX', 'INDEXES', 'INFILE', 'INNER', 'INSERT', 'INSERT_ID', 'INSERT_METHOD', 'INTERVAL',
            'INTO', 'INVOKER', 'IS', 'ISOLATION', 'JOIN', 'KEY', 'KEYS', 'KILL', 'LAST_INSERT_ID', 'LEADING', 'LEFT', 'LEVEL', 'LIKE', 'LIMIT', 'LINEAR',
            'LINES', 'LOAD', 'LOCAL', 'LOCK', 'LOCKS', 'LOGS', 'LOW_PRIORITY', 'MARIA', 'MASTER', 'MASTER_CONNECT_RETRY', 'MASTER_HOST', 'MASTER_LOG_FILE',
            'MASTER_LOG_POS', 'MASTER_PASSWORD', 'MASTER_PORT', 'MASTER_USER', 'MATCH', 'MAX_CONNECTIONS_PER_HOUR', 'MAX_QUERIES_PER_HOUR',
            'MAX_ROWS', 'MAX_UPDATES_PER_HOUR', 'MAX_USER_CONNECTIONS', 'MEDIUM', 'MERGE', 'MINUTE', 'MINUTE_SECOND', 'MIN_ROWS', 'MODE', 'MODIFY',
            'MONTH', 'MRG_MYISAM', 'MYISAM', 'NAMES', 'NATURAL', 'NOT', 'NULL', 'OFFSET', 'ON', 'OPEN', 'OPTIMIZE', 'OPTION', 'OPTIONALLY', 'OR',
            'ORDER', 'OUTER', 'OUTFILE', 'PACK_KEYS', 'PAGE', 'PARTIAL', 'PARTITION', 'PARTITIONS', 'PASSWORD', 'PRIMARY', 'PRIVILEGES', 'PROCEDURE',
            'PROCESS', 'PROCESSLIST', 'PURGE', 'QUICK', 'RAID0', 'RAID_CHUNKS', 'RAID_CHUNKSIZE', 'RAID_TYPE', 'RANGE', 'READ', 'READ_ONLY',
            'READ_WRITE', 'REFERENCES', 'REGEXP', 'RELOAD', 'RENAME', 'REPAIR', 'REPEATABLE', 'REPLACE', 'REPLICATION', 'RESET', 'RESTORE', 'RESTRICT',
            'RETURN', 'RETURNS', 'REVOKE', 'RIGHT', 'RLIKE', 'ROLLBACK', 'ROW', 'ROWS', 'ROW_FORMAT', 'SECOND', 'SECURITY', 'SELECT', 'SEPARATOR',
            'SERIALIZABLE', 'SESSION', 'SET', 'SHARE', 'SHOW', 'SHUTDOWN', 'SLAVE', 'SONAME', 'SOUNDS', 'SQL', 'SQL_AUTO_IS_NULL', 'SQL_BIG_RESULT',
            'SQL_BIG_SELECTS', 'SQL_BIG_TABLES', 'SQL_BUFFER_RESULT', 'SQL_CACHE', 'SQL_CALC_FOUND_ROWS', 'SQL_LOG_BIN', 'SQL_LOG_OFF',
            'SQL_LOG_UPDATE', 'SQL_LOW_PRIORITY_UPDATES', 'SQL_MAX_JOIN_SIZE', 'SQL_NO_CACHE', 'SQL_QUOTE_SHOW_CREATE', 'SQL_SAFE_UPDATES',
            'SQL_SELECT_LIMIT', 'SQL_SLAVE_SKIP_COUNTER', 'SQL_SMALL_RESULT', 'SQL_WARNINGS', 'START', 'STARTING', 'STATUS', 'STOP', 'STORAGE',
            'STRAIGHT_JOIN', 'STRING', 'STRIPED', 'SUPER', 'TABLE', 'TABLES', 'TEMPORARY', 'TERMINATED', 'THEN', 'TO', 'TRAILING', 'TRANSACTIONAL',
            'TRUNCATE', 'TYPE', 'TYPES', 'UNCOMMITTED', 'UNION', 'UNIQUE', 'UNLOCK', 'UPDATE', 'USAGE', 'USE', 'USING', 'VALUES', 'VARIABLES',
            'VIEW', 'WHEN', 'WHERE', 'WITH', 'WORK', 'WRITE', 'XOR', 'YEAR_MONTH'
        );

        $sql_skip_reserved_words = array('AS', 'ON', 'USING');
        $sql_special_reserved_words = array('(', ')');

        $sql_raw = str_replace("\n", " ", $sql_raw);

        $sql_formatted = "";

        $prev_word = "";
        $word = "";

        for( $i=0, $j = strlen($sql_raw); $i < $j; $i++ )
        {
            $word .= $sql_raw[$i];

            $word_trimmed = trim($word);

            if($sql_raw[$i] == " " || in_array($sql_raw[$i], $sql_special_reserved_words))
            {
                $word_trimmed = trim($word);

                $trimmed_special = false;

                if( in_array($sql_raw[$i], $sql_special_reserved_words) )
                {
                    $word_trimmed = substr($word_trimmed, 0, -1);
                    $trimmed_special = true;
                }

                $word_trimmed = strtoupper($word_trimmed);

                if( in_array($word_trimmed, $sql_reserved_all) && !in_array($word_trimmed, $sql_skip_reserved_words) )
                {
                    if(in_array($prev_word, $sql_reserved_all))
                    {
                        $sql_formatted .= '<b>'.strtoupper(trim($word)).'</b>'.'&nbsp;';
                    }
                    else
                    {
                        $sql_formatted .= '<br/>&nbsp;';
                        $sql_formatted .= '<b>'.strtoupper(trim($word)).'</b>'.'&nbsp;';
                    }

                    $prev_word = $word_trimmed;
                    $word = "";
                }
                else
                {
                    $sql_formatted .= trim($word).'&nbsp;';

                    $prev_word = $word_trimmed;
                    $word = "";
                }
            }
        }

        $sql_formatted .= trim($word);

        return self::$sendTo($sql_formatted, ...$args);
    }

    public static function pp(...$args)
    {
        if (empty($args)) $args = [null];

        (new Collection($args))->each(function($item) {
            self::put($item);
        });
    }

    /**
     * Put error to standard out
     * - does NOT use $force; will print if called
     *
     * @param mixed  $arg       Argument to dump to log
     * @param string $desc      description/string to put to log
     * @param bool   $dateStamp flag to put time stamp or not
     *
     * @return string
     */
    public static function put($arg = '', $desc = "", $dateStamp = true, $returnData = false)
    {
        if (self::$skip) {
            return '';
        }
        date_default_timezone_set(date_default_timezone_get() ?: self::DEFAULT_TIME_ZONE);
        $x = debug_backtrace();
        $f = current($x);
        $index = (stripos($f['file'], 'Notify.php') !== false) ? 1 : 0;
        $f = $x[$index];

        if (stripos($f['file'], 'EnumeratesValues.php') !== false) {
            $f = $x[++$index];
            if (stripos($f['file'], 'Notify.php') !== false) $f = $x[++$index];
        }

        $m = isset($x[$index + 1]['function']) ? $x[$index + 1]['function'] : '';

        if (!$dateStamp) {
            $date = '';
        } else {
            $date = '[' . date(self::DEFAULT_DATETIME_FORMAT) . '] ';
        }

        $pre = $post = $darkMode = '';

        if (!isset($_SERVER['HTTP_HOST'])) {
            $post = "";
            if ($arg === 0) {
                $post = "\n";
            }
        } else {
            if (self::useDarkMode()) {
                $darkMode = '<style> body {background-color: black;color: white;} </style> ';
            }
            $pre = '<pre>';
            $post = '</pre>';
        }

        $desc = ($f['file'] . ' / ' . $m . ' / ' . $f['line']) . ($desc ? ": $desc: " : '');

        if (self::badSize($arg, $desc)) {
            return '';
        }

        $type = ' [type:' . self::getType($arg) . '] ';

        ob_start();
        echo $darkMode . $pre . $date . $desc . $type
             . (empty($arg) || empty($desc) || (is_string($arg) && (strlen($arg) < 1)) ? '' : "\n")
             . (gettype($arg) == 'boolean'
                ? ($arg ? 'TRUE' : 'FALSE')
                : (is_null($arg) ? "\n" : print_r($arg, true)))
             . ((empty($arg) && $arg !== false) || (is_string($arg) && (strlen($arg) < 1)) ? '' : "\n") . $post;
        $contents = ob_get_contents();
        ob_end_clean();

        if (!self::$returnOutputOnly) {
            echo $contents;
        }

        return $returnData ? $arg : $date . $contents;
    }

    public static function useDarkMode(): bool
    {
        if (empty(getenv('dark_mode'))) return false;

        $isApiCall = strpos($_SERVER['REQUEST_URI'], '/api') === 0;
        $isBrowser = strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false;

        return $isBrowser || !$isApiCall;
    }

    /**
     * Check if the size will put us over the 8.5Mb limitation
     *
     * @param mixed  $arg  Argument to dump to log
     * @param string $desc description/string to put to log
     *
     * @return bool
     */
    public static function badSize($arg, $desc = '')
    {
        //$current = memory_get_usage();
        $toAdd = self::sizeOfVar($arg);
        $bad = false;
        $pre = $post = '';

        if (!isset($_SERVER['HTTP_HOST'])) {
            $post = "";
            if ($arg === 0) {
                $post = "\n";
            }
        } else {
            if (!self::useDarkMode()) {
                $pre = '<pre>';
            } else {
                $pre = '<style> body {background-color: black;color: white;} </style> <pre> <br>';
            }
            $post = '</pre>';
        }

        // check if will exceed 8.5Mb
        if (($toAdd > 5000) /*|| (($current + $toAdd) > 8500000)*/) {
            $bad = true;
            echo "$pre\n(Too big for auto, just printing - toAdd==$toAdd)\n"
                 . 'desc==' . $desc . ": \n";
            print_r($arg);
            //self::showBacktrace();
            echo "$post\n\n";
        }

        return $bad;
    }

    /**
     * Return the size of the inputted variable
     *
     * @param mixed $var input variable
     *
     * @return int
     */
    public static function sizeOfVar($var)
    {
        $start_memory = memory_get_usage();
        //$tmp = json_decode(json_encode($var));
        // intentionally "unused"; it's to check out the memory size after setting.
        $tmp = print_r($var, true);

        return memory_get_usage() - $start_memory;
    }

    /**
     * Show a backtrace for debugging if $force
     *
     * @param string $displayTo If displaying to "log", "out" (optional) or display (default)
     *
     * @uses $force
     *
     * @return string
     */
    public static function showBacktrace($displayTo = '', $desc = '')
    {
        //if (!self::$force) return;
        $x = debug_backtrace();
        $msg = '************backtrace****************';
        foreach ($x as $v) {
            $msg .= "\n" . (isset($v['file']) ? $v['file'] : "")
                    . "|" . (isset($v['function']) ? $v['function'] : "")
                    . "|" . (isset($v['line']) ? $v['line'] : "");
        }

        if ($displayTo == 'log' && isset($_SERVER['SERVER_NAME'])) {
            return self::log($msg, $desc);
        } elseif ($displayTo == 'out') {
            return $msg;
        } else {
            return self::put($msg, $desc);
        }
    }

    /**
     * Return the backtrace for debugging if $force
     *
     * @param string $displayTo If displaying to log or display
     *
     * @uses $force
     *
     * @return string
     */
    public static function returnBacktrace($displayTo = 'log')
    {
        //if (!self::$force) return;
        $x = debug_backtrace();
        $msg = '************backtrace****************';
        foreach ($x as $v) {
            $msg .= "\n" . (isset($v['file']) ? $v['file'] : "")
                    . "|" . (isset($v['function']) ? $v['function'] : "")
                    . "|" . (isset($v['line']) ? $v['line'] : "");
        }

        return $msg;
    }

    /**
     * For errors that should cause an exit, log the error and put/redirect and exit.
     *
     * @param mixed  $arg  Argument to dump to log
     * @param string $desc description/string to put to log
     *
     * @return void
     */
    public static function logAndExit($arg, $desc = "")
    {
        self::log($arg, $desc);

        exit("Exiting.\n");
    }

    /**
     * For pre tags around a print_r
     *
     * @param mixed $arg  Argument to print_r
     * @param bool  $exit Exit or not; default TRUE.
     *
     * @return void
     */
    public static function prePrint($arg = '', $exit = true)
    {
        if (!self::useDarkMode()) {
            echo '<pre>';
        } else {
            echo '<style> body {background-color: black;color: white;} </style> <pre> <br>';
        }
        print_r(is_bool($arg) ? ($arg ? 'TRUE' : 'FALSE') : $arg);
        echo '</pre>';

        if ($exit) {
            exit('Exiting.');
        }
    }

    /**
     * Put self::ERROR to standard out - does NOT use $force; will log the error if called
     *
     * @param mixed  $arg  Argument to dump to log
     * @param string $desc description/string to put to log
     *
     * @return string Return the resulting error string for Exceptions
     */
    public static function err($arg, $desc = '')
    {
        return self::log($arg, $desc, ['type' => self::ERROR]);
    }

    /**
     * Put self::WARNING to standard out - does NOT use $force; will log the error if called
     *
     * @param mixed  $arg  Argument to dump to log
     * @param string $desc description/string to put to log
     *
     * @return string Return the resulting error string for Exceptions
     */
    public static function warning($arg, $desc = '')
    {
        return self::log($arg, $desc, ['type' => self::WARNING]);
    }

    /**
     * Put self::WARNING to standard out - does NOT use $force; will log the error if called
     *
     * @param mixed  $arg  Argument to dump to log
     * @param string $desc description/string to put to log
     *
     * @return string Return the resulting error string for Exceptions
     */
    public static function info($arg, $desc = '')
    {
        return self::log($arg, $desc);
    }

    /**
     * Put result of log msg to standard out
     *
     * @param mixed  $arg  Argument to dump to log
     * @param string $desc description/string to put to log
     *
     * @return string Return the resulting error string for Exceptions
     */
    public static function putNlog($arg, $desc = '')
    {
        $msg = self::log($arg, $desc);
        echo "$msg<br>\n";

        return $msg;
    }

    /**
     * To check timing, set $microStart to current microtime and log it
     *
     * @param string $desc Description for log file
     *
     * @example // Say you have a routine or some set of code that you want to check how long it runs:
     *          Notify::microStart('Start of code block follows--this is a string to say "start" or whatever);
     *          ... /* code that you want to check
     *          Notify::microEnd('"End" or whatever text you might want to put');
     *
     * @return void
     */
    public static function microStart($desc = 'Start') {
        self::$microStart = microtime(true);
        // self::debug($desc . ': micro start ' . (self::$microStart));
        self::put($desc . ': micro start ' . self::$microStart);
    }

    /**
     * To check timing, get delta of current microtime from $microStart to and log it
     *
     * @param string $desc  Description for log file
     * @param bool   $reset Reset start if TRUE.
     *
     * @return void
     */
    public static function microEnd($desc = 'End', $reset = false) {
        $new = microtime(true);
        // self::debug($desc . ': micro  end  ' . ($new - self::$microStart));
        self::put($desc . ': micro  end  ' . ($new - self::$microStart));

        if ($reset) {
            self::$microStart = $new;
        }
    }

    /**
     * Return the type of the input; if object, try to resolve the base class name
     *
     * @param mixed $arg object which type you want to get
     *
     * @return string
     */
    public static function getType($arg)
    {
        $type = gettype($arg);

        return strcmp($type, 'object') === 0 ? basename(str_replace('\\', '/', get_class($arg))) : $type;
    }

    /**
     * Output a line separator for visual location help.
     *
     * @param string $desc Description prefix
     * @param string $char Character to repeat 77 times
     *
     * @return void
     */
    public static function separator($desc = '', $char = '-')
    {
        self::debug($desc . ': ' . str_repeat($char, 77 - strlen($desc)));
    }

    /**
     * Set the db so that we can print out the SQL statements
     * @param $db
     */
    public static function setDb(&$db, $log = false, $monolog = false)
    {
        if ($log) {
            self::$logOverride = true;
        }

        if ($monolog) {
            self::$useMonolog = true;
        }

        // get an instance of Notify
        $obj = new static();

        // set db to the inputted db
        $obj->db = $db;

        $db = $obj;
    }

    public static function setDbLog(&$db)
    {
        Notify::setDb($db, true);
    }

    /**
     * Override of the prepare so we can dump the SQL and then return the PDO object
     * @param $sql
     *
     * @return bool|\PDOStatement
     */
    public function prepare($sql)
    {
        $msg = 'Calling prepare() with sql';

        if (self::$logOverride) {
            Notify::log($sql, $msg);
        } else {
            Notify::put($sql, $msg);
        }

        return $this->db->prepare($sql);
    }

    /**
     * Override of the query so we can dump the SQL and then return the PDO object
     * @param $args
     *
     * @return bool|\PDOStatement
     */
    public function query()
    {
        $args = func_get_args();
        $sql = $args;

        $msg = 'Calling $db->query()';

        if (self::$logOverride) {
            Notify::log($args, $msg);
        } else {
            Notify::put($sql, $msg);
        }

        return call_user_func_array(array($this->db, 'query'), $args);
    }

    /**
     * Override of the query so we can dump the SQL and then return the PDO object
     * @param $args
     *
     * @return bool|\PDOStatement
     */
    public function insert()
    {
        $args = func_get_args();
        $sql = $args[0];

        $msg = 'Calling $db->insert()';

        if (self::$logOverride) {
            Notify::log($args, $msg);
        } else {
            Notify::put($sql, $msg);
        }

        return call_user_func_array(array($this->db, 'insert'), $args);
    }

    public function lastInsertId($name = null)
    {
        return $this->db->lastInsertId($name);
    }

    public function escape($value)
    {
        return $this->db->escape($value);
    }

    public function count()
    {
        if (self::$logOverride) {
            Notify::log('calling count');
        } else {
            Notify::put('calling count');
        }

        return $this->db->count();
    }
}
