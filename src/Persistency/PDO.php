<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Persistency;

class PDO extends \PDO
{
    /** @var array<string, mixed> */
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
            'dsn' => $dsn,
            'username' => $username,
            'password' => $password,
            'options' => $options,
        ];
    }

    public function enableConnection(): void
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

    /**
     * @param mixed $value
     */
    public function setAttribute(int $attribute, $value): bool
    {
        $this->attributes[$attribute] = $value;

        return true;
    }

    /**
     * @param array<mixed> $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        $this->enableConnection();

        return $this->{$name}(...$arguments);
    }
}
