<?php

declare(strict_types=1);

namespace TheFairLib\Request;

use Hyperf\Validation\Request\FormRequest;

class BaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'page' => 'i',
            'item_per_page' => 'i',
            'item_per_row' => 'i',
//            'last_item_id' => 'string|max:64',

            'app_id' => 'str|max:32',
            'app_name' => 'str|max:32',
//            'source' => 'str|max:32',
            '__from' => 'str|max:32',

//            'sort' => 'str|max:32',
//            'sort_field' => 'str|max:32',
//            'sort_order' => 'str|max:32',
        ];
    }
}
