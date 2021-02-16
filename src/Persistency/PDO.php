<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Persistency;

class PDO extends \PDO
{
    /** @var array<mixed> */
    protected array $connectionParameters = [];
    /** @var array<int, mixed> */
    protected array $attributes = [];
    protected bool $connected = false;

    /**
     * @param array<mixed> $options
     */
    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null)
    {
        $this->connectionParameters = [
            $dsn,
            $username,
            $password,
            $options,
        ];
    }

    public function enableConnection()
    {
        if ($this->connected) {
            return;
        }

        parent::__construct(...$this->connectionParameters);
        foreach ($this->attributes as $attribute => $value) {
            parent::setAttribute($attribute, $value);
        }

        $this->connected = true;
    }

    public function setAttribute($attribute, $value)
    {
        $this->attributes[$attribute] = $value;

        return true;
    }

    public function prepare($query, $options = null)
    {
        $this->enableConnection();
        parent::prepare($query, $options);
    }

    public function beginTransaction()
    {
        $this->enableConnection();
        parent::beginTransaction();
    }

    public function commit()
    {
        $this->enableConnection();
        parent::commit();
    }

    public function rollBack()
    {
        $this->enableConnection();
        parent::rollBack();
    }

    public function inTransaction()
    {
        $this->enableConnection();
        parent::inTransaction();
    }

    public function exec($statement)
    {
        $this->enableConnection();
        parent::exec($statement);
    }

    public function query($statement, $mode = self::ATTR_DEFAULT_FETCH_MODE, $arg3 = null, array $ctorargs = [])
    {
        $this->enableConnection();
        parent::query($statement, $mode, $arg3, $ctorargs);
    }

    public function lastInsertId($name = null)
    {
        $this->enableConnection();
        parent::lastInsertId($name);
    }

    public function errorCode()
    {
        $this->enableConnection();
        parent::errorCode();
    }

    public function errorInfo()
    {
        $this->enableConnection();
        parent::errorInfo();
    }

    public function getAttribute($attribute)
    {
        $this->enableConnection();
        parent::getAttribute($attribute);
    }

    public function quote($string, $type = self::PARAM_STR)
    {
        $this->enableConnection();
        parent::quote($string, $type);
    }

    public function sqliteCreateFunction($function_name, $callback, $num_args = -1, $flags = 0)
    {
        $this->enableConnection();
        parent::sqliteCreateFunction($function_name, $callback, $num_args, $flags);
    }
}
