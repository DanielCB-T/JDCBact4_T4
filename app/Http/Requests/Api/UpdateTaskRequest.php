<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Usamos "sometimes" para soportar tanto PUT (todos los campos)
     * como PATCH (actualización parcial). Si el campo viene en el
     * request, se valida igualmente como requerido (no puede venir vacío).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'autor' => ['sometimes', 'required', 'string', 'max:255'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'category' => ['sometimes', 'required', 'string', 'max:100'],
            'priority' => ['sometimes', 'required', 'string', 'in:baja,media,alta'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'autor.required' => 'El autor no puede estar vacío.',
            'title.required' => 'El título no puede estar vacío.',
            'description.required' => 'La descripción no puede estar vacía.',
            'category.required' => 'La categoría no puede estar vacía.',
            'priority.required' => 'La prioridad no puede estar vacía.',
            'priority.in' => 'La prioridad debe ser: baja, media o alta.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Error en la validación de los datos.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
