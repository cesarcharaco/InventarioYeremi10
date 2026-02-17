<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SolicitantesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'nombres' => 'required',
            'rut' => 'required|numeric|unique:solicitantes',
            'email' => 'required|email|unique:solicitantes',
            'telefono' => 'required|numeric'

        ];
    }

    public function mesagges()
    {
        return [
            'nombres.required' => 'Los Nombres son obligatorios',
            'rut.required' => 'El RUT es obligatorio',
            'rut.numeric' => 'El RUT solo debe contener números',
            'rut.unique' => 'Ya existe el RUT registrado',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser un correo válido',
            'email.unique' => 'Ya existe el email registrado',
            'telefono.required' => 'El Télefono es obligatorio',
            'telefono.numeric' => 'El Teléfono solo debe contener números'

        ];
    }
}
