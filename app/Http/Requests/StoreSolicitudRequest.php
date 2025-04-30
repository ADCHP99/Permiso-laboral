<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSolicitudRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tipo_permiso' => ['required', Rule::in(['con_sueldo', 'sin_sueldo'])],
            'fecha_inicio' => 'required | date',
            'fecha_fin' => 'required | date | after_or_equal:fecha_inicio',
            'hora_inicio' => ['nullable', 'date_format:H:i'],
            'hora_fin'    => ['nullable', 'date_format:H:i'],
            'motivo' => 'required | string| max:255',
            'descripcion' => 'nullable | string| max:255',
            'archivo_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }
}
