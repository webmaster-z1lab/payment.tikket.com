<?php
/**
 * Created by PhpStorm.
 * User: Alisson
 * Date: 02/08/2017
 * Time: 14:17
 */

namespace App\Validator;

use Illuminate\Validation\Validator as BaseValidator;
use Respect\Validation\Validator as Respect;

class Validator extends BaseValidator
{
    /**
     * Valida o formato do celular junto com o ddd
     *
     * @param string $attribute
     * @param string $value
     *
     * @return boolean
     */
    protected function validateBoolCustom($attribute, $value)
    {
        $array = ['true', 'false', 0, 1, TRUE, FALSE];

        return in_array($value, $array);
    }

    /**
     * Valida o formato do celular junto com o ddd
     *
     * @param string $attribute
     * @param string $value
     *
     * @return boolean
     */
    protected function validateCellPhone($attribute, $value)
    {
        return preg_match('/^\d{10,11}$/', $value) > 0;
    }

    /**
     * Valida se o CPF é válido
     *
     * @param string $attribute
     * @param string $value
     *
     * @return boolean
     */
    protected function validateCpf($attribute, $value)
    {
        return Respect::cpf()->validate($value);
    }

    /**
     * Valida se o CNPJ é válido
     *
     * @param string $attribute
     * @param string $value
     *
     * @return boolean
     */
    protected function validateCnpj($attribute, $value)
    {
        return Respect::cnpj()->validate($value);
    }

    /**
     * Valida se o CPF é válido
     *
     * @param string $attribute
     * @param string $value
     *
     * @return boolean
     */
    protected function validateDocument($attribute, $value)
    {
        $doc = strlen($value);

        if ($doc === 11) return Respect::cpf()->validate($value);

        if ($doc === 14) return Respect::cnpj()->validate($value);

        return FALSE;
    }
}
