<?php

namespace Aternos\Model;

use Aternos\Model\Driver\DriverFactory;

/**
 * Class GenericModel
 *
 * Generic model using all drivers optionally, enable/disable them by
 * overwriting the protected static properties. Good start for small to
 * medium complexity.
 *
 * @author Matthias Neid
 * @package Aternos\Model
 */
abstract class GenericModel extends BaseModel
{
    /**
     * Should the model registry be used for this model
     *
     * @var bool
     */
    protected static $registry = true;

    /**
     * Is this model cacheable and if yes, for how long (seconds)
     *
     * @var bool|int
     */
    protected static $cache = false;

    /**
     * Should this model be saved in a nosql database
     *
     * @var bool
     */
    protected static $nosql = false;

    /**
     * Should this model be saved in a relational database
     *
     * @var bool
     */
    protected static $relational = true;

    /**
     * Is this model searchable and if yes, which fields
     *
     * @var bool|array
     */
    protected static $search = false;

    /**
     * Get the driver factory for the current model
     *
     * @return Driver\DriverFactory
     */
    protected static function getDriverFactory()
    {
        $driverFactory = DriverFactory::getInstance();

        return $driverFactory;
    }

    /**
     * Get a model by id
     *
     * @param string $id
     * @param bool $update
     * @return GenericModel|bool
     */
    public static function get(string $id, bool $update = false)
    {
        $class = get_called_class();
        $registry = ModelRegistry::getInstance();
        $factory = self::getDriverFactory();

        /**
         * @var GenericModel $model
         */
        $model = new $class($id);

        // try to get the model from the registry
        if ($class::$registry) {
            if ($registryModel = $registry->get($class::getName(), $id)) {
                $model = $registryModel;
                if (!$update) {
                    return $model;
                }
            }
        }

        // try to get the model from cache
        if ($class::$cache && !$update && $factory->assembleCacheDriver()->get($model)) {
            if ($class::$registry) {
                $registry->save($model);
            }

            return $model;
        }

        // try to get the model from nosql database
        if ($class::$nosql && $factory->assembleNoSQLDriver()->get($model)) {
            if ($class::$registry) {
                $registry->save($model);
            }

            if ($class::$cache) {
                $factory->assembleCacheDriver()->save($model);
            }

            return $model;
        }

        // try to get the model from relational database
        if ($class::$relational && $factory->assembleRelationalDriver()->get($model)) {
            if ($class::$registry) {
                $registry->save($model);
            }

            if ($class::$cache) {
                $factory->assembleCacheDriver()->save($model);
            }

            if ($class::$nosql) {
                $factory->assembleNoSQLDriver()->save($model);
            }

            return $model;
        }

        return false;
    }

    /**
     * Save the model changes
     *
     * @return bool
     */
    public function save(): bool
    {
        $class = get_called_class();
        $factory = self::getDriverFactory();

        // new model, generate id and save in registry
        if (!$this->getId()) {
            $this->generateId();

            if ($class::$registry) {
                ModelRegistry::getInstance()->save($this);
            }
        }

        // save in relational database
        if ($class::$relational) {
            if (!$factory->assembleRelationalDriver()->save($this)) {
                return false;
            }
        }

        // save in nosql database
        if ($class::$nosql) {
            if (!$factory->assembleNoSQLDriver()->save($this)) {
                return false;
            }
        }

        // save in search database
        if ($class::$search) {
            if (!$factory->assembleSearchDriver()->save($this)) {
                return false;
            }
        }

        // save in cache
        if ($class::$cache) {
            if (!$factory->assembleCacheDriver()->save($this)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Delete the model
     *
     * @return bool
     */
    public function delete(): bool
    {
        $class = get_called_class();
        $factory = self::getDriverFactory();
        $success = true;

        // delete in relational database
        if ($class::$relational) {
            if (!$factory->assembleRelationalDriver()->delete($this)) {
                $success = false;
            }
        }

        // delete in nosql database
        if ($class::$nosql) {
            if (!$factory->assembleNoSQLDriver()->delete($this)) {
                $success = false;
            }
        }

        // delete in search database
        if ($class::$search) {
            if (!$factory->assembleSearchDriver()->delete($this)) {
                $success = false;
            }
        }

        // delete in cache
        if ($class::$cache) {
            if (!$factory->assembleCacheDriver()->delete($this)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Return the cache time
     *
     * @return int
     */
    public function getCacheTime(): int
    {
        $class = get_called_class();
        return $class::$cache ?: 0;
    }
}