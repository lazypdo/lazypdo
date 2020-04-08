<?php

namespace LazyPDO;

use PDO;
use RuntimeException;
use Serializable;

/**
 * LazyPDO does not instantiate real PDO until it is really needed
 */
class LazyPDO extends PDODecorator implements Serializable
{
    private string $dsn;
    private ?string $user;
    private ?string $password;
    private array $options = array();

    private $pdo = null;

    /**
     * __construct
     *
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @param array $options
     */
    public function __construct($dsn, $user = null, $password = null, array $options = array())
    {
        $this->dsn = $dsn;
        $this->user = $user;
        $this->password = $password;
        $this->options = $options;
    }

    /**
     * serialize
     *
     * @return string
     */
    public function serialize()
    {
        if ($this->inTransaction()) {
            throw new RuntimeException('Can not serialize in transaction');
        }
        return serialize(array(
            $this->dsn,
            $this->user,
            $this->password,
            $this->options,
        ));
    }

    /**
     * Checks if inside a transaction
     *
     * @return bool
     */
    public function inTransaction()
    {
        // Do not call parent method if there is no pdo object
        return $this->pdo && parent::inTransaction();
    }

    /**
     * unserialize
     *
     * @param string $serialized
     * @return void
     */
    public function unserialize($serialized)
    {
        list($this->dsn, $this->user, $this->password, $this->options) = unserialize($serialized);
    }

    /**
     * setAttribute
     *
     * @param int $attribute
     * @param mixed $value
     * @return boolean
     */
    public function setAttribute($attribute, $value)
    {
        if (parent::setAttribute($attribute, $value)) {
            $this->options[$attribute] = $value;
            return true;
        }
        return false;
    }

    /**
     * Get PDO object. Cache the result
     *
     * @return PDO
     */
    protected function getPDO()
    {
        if (null === $this->pdo) {
            $this->pdo = new PDO($this->dsn, $this->user, $this->password, $this->options);
        }
        return $this->pdo;
    }
}
