<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PmeSyncProduitsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'produits' => ['required', 'array', 'min:1'],
            'produits.*.reference' => ['required', 'string', 'max:100'],
            'produits.*.designation' => ['required', 'string', 'max:255'],
            'produits.*.prix' => ['nullable', 'numeric', 'min:0'],
            'produits.*.pv_1' => ['nullable', 'numeric', 'min:0'],
            'produits.*.pv_2' => ['nullable', 'numeric', 'min:0'],
            'produits.*.pv_3' => ['nullable', 'numeric', 'min:0'],
            'produits.*.stock' => ['required', 'numeric'],
            'produits.*.promo' => ['nullable', 'boolean'],
            'produits.*.date_debut_promo' => ['nullable', 'date'],
            'produits.*.date_fin_promo' => ['nullable', 'date'],
            'produits.*.quantite_promo' => ['nullable', 'integer', 'min:1'],
            'produits.*.prix_promo' => ['nullable', 'numeric', 'min:0'],
            'produits.*.categorie' => ['required', 'string', 'max:100'],
            'produits.*.sous_categorie' => ['nullable', 'string', 'max:255'],
            'produits.*.marque' => ['nullable', 'string', 'max:255'],
            'produits.*.abonne_only' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $items = $this->input('produits', []);
            if (! is_array($items)) {
                return;
            }

            foreach ($items as $i => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $hasPrix = array_key_exists('prix', $item) && $item['prix'] !== null && $item['prix'] !== '';
                $hasPv1 = array_key_exists('pv_1', $item) && $item['pv_1'] !== null && $item['pv_1'] !== '';

                if (! $hasPrix && ! $hasPv1) {
                    $v->errors()->add("produits.{$i}.pv_1", 'pv_1 ou prix est requis.');
                }
            }
        });
    }
}
