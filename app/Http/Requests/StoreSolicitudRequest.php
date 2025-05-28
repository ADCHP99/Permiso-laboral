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
            'fecha_inicio' => 'required | date | after_or_equal:today',
            'fecha_fin' => 'required | date | after_or_equal:fecha_inicio',
            'hora_inicio' => ['nullable', 'date_format:H:i'],
            'hora_fin'    => ['nullable', 'date_format:H:i'],
            'motivo' => 'required | string| max:255',
            'descripcion' => 'nullable | string| max:255',
            'archivo_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }
    public function withValidator ($validator)
    {
        $validator->sometimes(['hora_inicio','hora_fin'],'required',function($input){
           return $input->tipo_permiso == 'con_sueldo';
        });
        $validator->sometimes(['fecha_inicio','fecha_fin'],'required',function($input){
            return $input->tipo_permiso == 'sin_sueldo';
        });
        $validator->after(function ($validator) {
            $data = $this->all();

            // Solo si ambos existen y tipo_permiso es con_sueldo
            if (
                isset($data['hora_inicio'], $data['hora_fin']) &&
                $data['tipo_permiso'] === 'con_sueldo'
            ) {
                // Convertir a segundos/minutos para comparar
                $inicio = strtotime($data['hora_inicio']);
                $fin = strtotime($data['hora_fin']);
                if ($fin <= $inicio) {
                    $validator->errors()->add('hora_fin', 'La hora de fin debe ser posterior a la hora de inicio.');
                }
            }
        });
    }

    public function messages (){
        return[
            'tipo_permiso.required' => 'Debe seleccionar un tipo de permiso.',
            'tipo_permiso.in' => 'El tipo de permiso no es valido.',

            'fecha_inicio.required' => 'La fecha de inicio es obligatoria',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha valida.',
            'fecha_inicio.after_or_equal' => 'La fecha de inicio no puede ser anterior a hoy' ,

            'fecha_fin.required'=> 'La fecha de fin es obligatorio',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',

            'hora_inicio.required' => 'La hora de inicio es obligatoria para el permiso con sueldo',
            'hora_inicio.date_format' => 'La hora de inicio debe tener el formato HH:MM',

            'hora_fin.required'    => 'La hora  fin es obligatoria para el permiso con sueldo',
            'hora_fin.date_format' => 'La hora fin debe tener el formato HH:MM',
            'hora_fin.after'=>'La hora fin  debe ser posterior a la hora de inicio',

            'descripcion.string' => 'La descripcion debe ser un texto',
            'descripcion.max'=> 'La descripción no puede tener más de 255 caracteres.',

            'motivo.required' => 'El motivo es obligatorio',
            'motivo.string' => 'El motivo debe ser un texto.',
            'motivo.max' => 'El motivo no puede tener más de 255 caracteres.',

            'archivo_pdf.file' => 'El archivo debe ser un documento.',
            'archivo_pdf.mimes' => 'El archivo debe ser de tipo PDF.',
            'archivo_pdf.max' => 'El archivo no puede pesar más de 5 MB.',
        ];
    }
}
