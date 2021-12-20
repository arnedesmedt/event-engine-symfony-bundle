<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Persistency;

use PDOStatement;

class PDO extends \PDO
{
    private string $dsn;
    private ?string $username;
    private ?string $password;
    /** @var mixed[]|null */
    private ?array $options;
    /** @var array<int, mixed> */
    protected array $attributes = [];
    protected bool $connected = false;

    /**
     * @param array<mixed> $options
     */
    public function __construct(
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        ?array $options = null
    ) {

        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
    }

    public function enableConnection(): void
    {
        if ($this->connected) {
            return;
        }

        parent::__construct($this->dsn, $this->username, $this->password, $this->options);
        foreach ($this->attributes as $attribute => $value) {
            parent::setAttribute($attribute, $value);
        }

        $this->connected = true;
    }

    public function setAttribute($attribute, $value): bool
    {
        $this->attributes[$attribute] = $value;

        return true;
    }

    /**
     * @param array<mixed> $options
     */
    public function prepare($query, $options = []): PDOStatement|false
    {
        $this->enableConnection();

        return parent::prepare($query, $options);
    }

    public function beginTransaction(): bool
    {
        $this->enableConnection();

        return parent::beginTransaction();
    }

    public function commit(): bool
    {
        $this->enableConnection();

        return parent::commit();
    }

    public function rollBack(): bool
    {
        $this->enableConnection();

        return parent::rollBack();
    }

    public function inTransaction(): bool
    {
        $this->enableConnection();

        return parent::inTransaction();
    }

    public function exec($statement): int|false
    {
        $this->enableConnection();

        return parent::exec($statement);
    }

    public function query($statement, $mode = self::ATTR_DEFAULT_FETCH_MODE, ...$fetch_mode_args): PDOStatement|false
    {
        $this->enableConnection();

        return parent::query($statement, $mode, ...$fetch_mode_args);
    }

    public function lastInsertId($name = null): string|false
    {
        $this->enableConnection();

        return parent::lastInsertId($name);
    }

    public function errorCode(): ?string
    {
        $this->enableConnection();

        return parent::errorCode();
    }

    /**
     * @return array<mixed>
     */
    public function errorInfo(): array
    {
        $this->enableConnection();

        return parent::errorInfo();
    }

    public function getAttribute($attribute): mixed
    {
        $this->enableConnection();

        return parent::getAttribute($attribute);
    }

    public function quote($string, $type = self::PARAM_STR): string|false
    {
        $this->enableConnection();

        return parent::quote($string, $type);
    }

    public function sqliteCreateFunction($function_name, $callback, $num_args = -1, $flags = 0): bool
    {
        $this->enableConnection();

        return parent::sqliteCreateFunction($function_name, $callback, $num_args, $flags);
    }
}
