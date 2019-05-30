<?php

namespace Modules\Transaction\Http\Requests;

use Z1lab\JsonApi\Http\Requests\ApiFormRequest;

class TransactionRequest extends ApiFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return TRUE;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'hash'     => 'bail|required|string',
            'ip'       => 'bail|required|ipv4',
            'amount'   => 'bail|required|integer|min:1',
            'order_id' => 'bail|required|string',

            'customer'                     => 'bail|required|array',
            'customer.user_id'             => 'bail|required|uuid',
            'customer.name'                => 'bail|required|string',
            'customer.email'               => 'bail|required|email',
            'customer.document'            => 'bail|required|cpf',
            'customer.phone'               => 'bail|required|array',
            'customer.phone.area_code'     => 'bail|required|digits:2',
            'customer.phone.phone'         => 'bail|required|digits_between:8,9',
            'customer.address.street'      => 'bail|required_if:type,boleto|string',
            'customer.address.number'      => 'bail|required_if:type,boleto|integer|between:1,999999999',
            'customer.address.complement'  => 'bail|nullable|string',
            'customer.address.district'    => 'bail|required_if:type,boleto|string',
            'customer.address.postal_code' => 'bail|required_if:type,boleto|digits:8',
            'customer.address.city'        => 'bail|required_if:type,boleto|string',
            'customer.address.state'       => 'bail|required_if:type,boleto|string|size:2',


            'items'               => 'bail|required|array|min:1',
            'items.*.item_id'     => 'bail|required|string',
            'items.*.description' => 'bail|required|string',
            'items.*.quantity'    => 'bail|required|integer|min:1',
            'items.*.amount'      => 'bail|required|integer|min:1',

            'type' => 'bail|required|string|in:boleto,credit_card',

            'card'                            => 'bail|required_if:type,credit_card|array',
            'card.brand'                      => 'bail|required_if:type,credit_card|string',
            'card.number'                     => 'bail|required_if:type,credit_card|string|size:4',
            'card.token'                      => 'bail|required_if:type,credit_card|string',
            'card.installments'               => 'bail|required_if:type,credit_card|integer|between:1,18',
            'card.parcel'                     => 'bail|required_if:type,credit_card|integer|min:1',
            'card.holder.name'                => 'bail|required_if:type,credit_card|string',
            'card.holder.document'            => 'bail|required_if:type,credit_card|digits:11',
            'card.holder.birth_date'          => 'bail|required_if:type,credit_card|date_format:Y-m-d|before:today',
            'card.holder.address'             => 'bail|required_if:type,credit_card|array',
            'card.holder.address.street'      => 'bail|required_if:type,credit_card|string',
            'card.holder.address.number'      => 'bail|required_if:type,credit_card|integer|between:1,999999999',
            'card.holder.address.complement'  => 'bail|nullable|string',
            'card.holder.address.district'    => 'bail|required_if:type,credit_card|string',
            'card.holder.address.postal_code' => 'bail|required_if:type,credit_card|digits:8',
            'card.holder.address.city'        => 'bail|required_if:type,credit_card|string',
            'card.holder.address.state'       => 'bail|required_if:type,credit_card|string|size:2',
            'card.holder.phone'               => 'bail|required_if:type,credit_card|array',
            'card.holder.phone.area_code'     => 'bail|required_if:type,credit_card|digits:2',
            'card.holder.phone.phone'         => 'bail|required_if:type,credit_card|digits_between:8,9',
        ];
    }
}
