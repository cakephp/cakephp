<?php
declare(strict_types=1);

namespace Cake\Controller;

/**
 * @mixin \Cake\Controller\Controller
 */
trait RequestParamTrait
{
    /**
     * Provides a safe accessor for retrieving an integer value (int|null) from the Request->query.
     * Validates value as int, and converts to int on success.
     * Returns null on failure or if the value is missing.
     *
     * String values are trimmed using trim().
     * Allows you to use Hash::get() compatible paths.
     *
     * @param string $paramName Dot separated name of the value to read.
     * @return int|null
     */
    public function getQueryInt(string $paramName): ?int
    {
        return $this->filterInt($this->request->getQuery($paramName, ''));
    }

    /**
     * Provides a safe accessor for retrieving an integer value (int|null) from the Request->data.
     * Validates value as int, and converts to int on success.
     * Returns null on failure or if the value is missing.
     *
     * String values are trimmed using trim().
     * Allows you to use Hash::get() compatible paths.
     *
     * @param string $paramName Dot separated name of the value to read.
     * @return int|null
     */
    public function getDataInt(string $paramName): ?int
    {
        return $this->filterInt($this->request->getData($paramName, ''));
    }

    /**
     * Provides a safe accessor for retrieving an integer value (int|null) from the Request->params.
     * Validates value as int, and converts to int on success.
     * Returns null on failure or if the value is missing.
     *
     * String values are trimmed using trim().
     * Allows you to use Hash::get() compatible paths.
     *
     * @param string $paramName Dot separated name of the value to read.
     * @return int|null
     */
    public function getParamInt(string $paramName): ?int
    {
        return $this->filterInt($this->request->getParam($paramName, ''));
    }

    /**
     * Provides a safe accessor for retrieving an integer value (int|null) from the Request->cookies.
     * Validates value as int, and converts to int on success.
     * Returns null on failure or if the value is missing.
     *
     * String values are trimmed using trim().
     * Allows you to use Hash::get() compatible paths.
     *
     * @param string $paramName Dot separated name of the value to read.
     * @return int|null
     */
    public function getCookieInt(string $paramName): ?int
    {
        return $this->filterInt($this->request->getCookie($paramName, ''));
    }

    /**
     * Validates value as int, and converts to int on success, null on failure.
     * String values are trimmed using trim().
     *
     * @param mixed $rawValue
     * @return int|null
     */
    protected function filterInt(mixed $rawValue): ?int
    {
        return filter_var($rawValue, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
    }

    /**
     * Provides a safe accessor for retrieving a string value (string|null) from the Request->query.
     * Validates the value as a string, and converts it to a string on success.
     * Returns null on failure or if the value is missing.
     *
     * Allows you to use Hash::get() compatible paths.
     *
     * @param string $paramName Dot separated name of the value to read.
     * @return string|null
     */
    public function getQueryString(string $paramName): ?string
    {
        $rawValue = $this->request->getQuery($paramName);

        return is_string($rawValue) ? $rawValue : null;
    }

    /**
     * Provides a safe accessor for retrieving a string value (string|null) from the Request->data.
     * Validates the value as a string, and converts it to a string on success.
     * Returns null on failure or if the value is missing.
     *
     * Allows you to use Hash::get() compatible paths.
     *
     * @param string $paramName Dot separated name of the value to read.
     * @return string|null
     */
    public function getDataString(string $paramName): ?string
    {
        $rawValue = $this->request->getData($paramName);

        return is_string($rawValue) ? $rawValue : null;
    }

    /**
     * Provides a safe accessor for retrieving a string value (string|null) from the Request->params.
     * Validates the value as a string, and converts it to a string on success.
     * Returns null on failure or if the value is missing.
     *
     * Allows you to use Hash::get() compatible paths.
     *
     * @param string $paramName Dot separated name of the value to read.
     * @return string|null
     */
    public function getParamString(string $paramName): ?string
    {
        $rawValue = $this->request->getParam($paramName);

        return is_string($rawValue) ? $rawValue : null;
    }

    /**
     * Provides a safe accessor for retrieving a string value (string|null) from the Request->cookies.
     * Validates the value as a string, and converts it to a string on success.
     * Returns null on failure or if the value is missing.
     *
     * Allows you to use Hash::get() compatible paths.
     *
     * @param string $paramName Dot separated name of the value to read.
     * @return string|null
     */
    public function getCookieString(string $paramName): ?string
    {
        $rawValue = $this->request->getCookie($paramName);

        return is_string($rawValue) ? $rawValue : null;
    }

    /**
     * Provides a safe accessor for retrieving a bool value (bool|null) from the Request->query.
     *
     * 1 | '1' | 1.0 | true values returns as true
     * 0 | '0' | 0.0 | false values returns as false
     * Other values or missing values returns as null
     *
     * Allows you to use Hash::get() compatible paths.
     *
     * @param string $paramName Dot separated name of the value to read.
     * @return bool|null
     */
    public function getQueryBool(string $paramName): ?bool
    {
        return $this->filterBool($this->request->getQuery($paramName, ''));
    }

    /**
     * Provides a safe accessor for retrieving a bool value (bool|null) from the Request->data.
     *
     * 1 | '1' | 1.0 | true values returns as true
     * 0 | '0' | 0.0 | false values returns as false
     * Other values or missing values returns as null
     *
     * Allows you to use Hash::get() compatible paths.
     *
     * @param string $paramName Dot separated name of the value to read.
     * @return bool|null
     */
    public function getDataBool(string $paramName): ?bool
    {
        return $this->filterBool($this->request->getData($paramName, ''));
    }

    /**
     * Provides a safe accessor for retrieving a bool value (bool|null) from the Request->params.
     *
     * 1 | '1' | 1.0 | true values returns as true
     * 0 | '0' | 0.0 | false values returns as false
     * Other values or missing values returns as null
     *
     * Allows you to use Hash::get() compatible paths.
     *
     * @param string $paramName Dot separated name of the value to read.
     * @return bool|null
     */
    public function getParamBool(string $paramName): ?bool
    {
        return $this->filterBool($this->request->getParam($paramName, ''));
    }

    /**
     * Provides a safe accessor for retrieving a bool value (bool|null) from the Request->cookies.
     *
     * 1 | '1' | 1.0 | true values returns as true
     * 0 | '0' | 0.0 | false values returns as false
     * Other values or missing values returns as null
     *
     * Allows you to use Hash::get() compatible paths.
     *
     * @param string $paramName Dot separated name of the value to read.
     * @return bool|null
     */
    public function getCookieBool(string $paramName): ?bool
    {
        return $this->filterBool($this->request->getCookie($paramName, ''));
    }

    /**
     * 1 | '1' | 1.0 | true values returns as true
     * 0 | '0' | 0.0 | false values returns as false
     * Other values or missing values returns as null
     *
     * @param mixed $rawValue
     * @return bool|null
     */
    protected function filterBool(mixed $rawValue): ?bool
    {
        if ($rawValue === '1' || $rawValue === 1 || $rawValue === 1.0 || $rawValue === true) {
            return true;
        } elseif ($rawValue === '0' || $rawValue === 0 || $rawValue === 0.0 || $rawValue === false) {
            return false;
        } else {
            return null;
        }
    }
}
