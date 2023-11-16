<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Persistency;

use PDOStatement;

class PDO extends \PDO
{
    /** @var array<int, mixed> */
    protected array $attributes = [];
    protected bool $connected = false;

    /** @param array<mixed> $options */
    public function __construct(
        private string $dsn,
        private string|null $username = null,
        private string|null $password = null,
        private array|null $options = null,
    ) {
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

    public function setAttribute(int $attribute, mixed $value): bool
    {
        $this->attributes[$attribute] = $value;

        return true;
    }

    /** @param array<mixed> $options */
    public function prepare(string $query, array $options = []): PDOStatement|false
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

    public function exec(string $statement): int|false
    {
        $this->enableConnection();

        return parent::exec($statement);
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    public function query( // @phpstan-ignore-line
        $statement,
        $mode = self::ATTR_DEFAULT_FETCH_MODE,
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
        ...$fetch_mode_args,
    ): PDOStatement|false {
        $this->enableConnection();

        return parent::query($statement, $mode);
    }

    public function lastInsertId(string|null $name = null): string|false
    {
        $this->enableConnection();

        return parent::lastInsertId($name);
    }

    public function errorCode(): string|null
    {
        $this->enableConnection();

        return parent::errorCode();
    }

    /** @return array<mixed> */
    public function errorInfo(): array
    {
        $this->enableConnection();

        return parent::errorInfo();
    }

    public function getAttribute(int $attribute): mixed
    {
        $this->enableConnection();

        return parent::getAttribute($attribute);
    }

    public function quote(string $string, int $type = self::PARAM_STR): string|false
    {
        $this->enableConnection();

        return parent::quote($string, $type);
    }

    // phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint,Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    public function sqliteCreateFunction($function_name, $callback, $num_args = -1, $flags = 0): bool
    {
        $this->enableConnection();

        return parent::sqliteCreateFunction($function_name, $callback, $num_args, $flags);
    }

    // phpcs:enable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint,Squiz.NamingConventions.ValidVariableName.NotCamelCaps
}
