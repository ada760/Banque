<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompteRequest extends FormRequest
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
     'type' => 'required|string|in:epargne,cheque',
     // 'num_compte' => 'required|string|unique:comptes,num_compte',
     'devise' => 'required|string',
     'status' => 'required|string|in:bloque,actif,ferme,suspendu',
     'email' => 'nullable|email',
     'telephone' => 'nullable|string|max:9',
     'adresse' => 'nullable|string',
     'cni' => 'nullable|string|min:13|max:14',
     'titulaire'=>'nullable|string'
 ];
     }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => "Le champ 'type' est requis.",
            'type.string'   => "Le champ 'type' doit être une chaîne de caractères.",
            'type.in'       => "Le type doit être l'une des valeurs suivantes : 'epargne' ou 'cheque'.",
            'num_compte.required' => "Le numéro de compte est requis.",
            'num_compte.string'   => "Le numéro de compte doit être une chaîne de caractères.",
            'num_compte.unique'   => "Ce numéro de compte existe déjà.",
            'devise.required' => "La devise est requise.",
            'devise.string'   => "La devise doit être une chaîne de caractères.",
            'status.required' => "Le statut est requis.",
            'status.string'   => "Le statut doit être une chaîne de caractères.",
            'status.in'       => "Le statut doit être l'une des valeurs suivantes : 'bloque', 'actif', 'ferme' ou 'suspendu'.",
        ];
    }
}
