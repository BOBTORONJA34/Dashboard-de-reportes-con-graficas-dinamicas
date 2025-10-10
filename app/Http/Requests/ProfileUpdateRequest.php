<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Autoriza esta petición.
     * Como ya pasaste por el middleware 'auth', aquí devolvemos true.
     * Si quisieras reglas por rol/permiso, podrías validarlas aquí.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * (Opcional) Normaliza datos antes de validar.
     * Forzamos el email a minúsculas para ser consistentes,
     * aunque ya usemos la regla 'lowercase' en rules().
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email') && is_string($this->email)) {
            $this->merge([
                'email' => mb_strtolower($this->email, 'UTF-8'),
            ]);
        }
    }

    /**
     * Reglas de validación para actualizar el perfil.
     *
     * - name: requerido, texto, hasta 255 chars.
     * - email: requerido, texto, en minúsculas, formato email,
     *          único en la tabla users EXCEPTO el del usuario autenticado.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase', // Laravel validará que venga en minúsculas
                'email',
                'max:255',
                // Ignora el email del usuario actual para no disparar 'unique'
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }

    /**
     * (Opcional) Mensajes personalizados. Útil si quieres textos exactos.
     */
    public function messages(): array
    {
        return [
            'name.required'   => 'El nombre es obligatorio.',
            'name.string'     => 'El nombre debe ser texto.',
            'name.max'        => 'El nombre no puede superar 255 caracteres.',
            'email.required'  => 'El correo es obligatorio.',
            'email.lowercase' => 'El correo debe estar en minúsculas.',
            'email.email'     => 'Ingresa un correo válido.',
            'email.max'       => 'El correo no puede superar 255 caracteres.',
            'email.unique'    => 'Este correo ya está registrado.',
        ];
    }
}
