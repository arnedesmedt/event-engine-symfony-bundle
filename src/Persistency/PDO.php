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

    public function prepare($query, $options = [])
    {
        $this->enableConnection();

        return parent::prepare($query, $options);
    }

    public function beginTransaction()
    {
        $this->enableConnection();

        return parent::beginTransaction();
    }

    public function commit()
    {
        $this->enableConnection();

        return parent::commit();
    }

    public function rollBack()
    {
        $this->enableConnection();

        return parent::rollBack();
    }

    public function inTransaction()
    {
        $this->enableConnection();

        return parent::inTransaction();
    }

    public function exec($statement)
    {
        $this->enableConnection();

        return parent::exec($statement);
    }

    public function query($statement, $mode = self::ATTR_DEFAULT_FETCH_MODE, $arg3 = null, array $ctorargs = [])
    {
        $this->enableConnection();

        return parent::query($statement, $mode, $arg3, $ctorargs);
    }

    public function lastInsertId($name = null)
    {
        $this->enableConnection();

        return parent::lastInsertId($name);
    }

    public function errorCode()
    {
        $this->enableConnection();

        return parent::errorCode();
    }

    public function errorInfo()
    {
        $this->enableConnection();

        return parent::errorInfo();
    }

    public function getAttribute($attribute)
    {
        $this->enableConnection();

        return parent::getAttribute($attribute);
    }

    public function quote($string, $type = self::PARAM_STR)
    {
        $this->enableConnection();

        return parent::quote($string, $type);
    }

    public function sqliteCreateFunction($function_name, $callback, $num_args = -1, $flags = 0)
    {
        $this->enableConnection();

        return parent::sqliteCreateFunction($function_name, $callback, $num_args, $flags);
    }
}
