<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Business;
use App\Http\Resources\BusinessResource;
use App\Http\Requests\HaversineSearchRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\Inflector\Language;

class HaversineSearchController extends BaseController
{
    private $inflectors;
    private $categoryTranslations;
    private $detectedLanguage;

    public function __construct()
    {
        $this->inflectors = [
            'es' => InflectorFactory::createForLanguage(Language::SPANISH)->build(),
            'pt' => InflectorFactory::createForLanguage(Language::PORTUGUESE)->build(),
            'en' => InflectorFactory::createForLanguage(Language::ENGLISH)->build(),
        ];

        $this->categoryTranslations = config('categories.translations', []);
        $this->detectedLanguage = 'es'; // Default language
    }

    private function detectLanguage(string $text): string
    {
        $languageMarkers = [
        'pt' => [
            'quero', 'perto', 'mim', 'pequeno', 'almoco', 'jantar', 'restaurante', 'comida', 
            'lanche', 'café', 'suco', 'cerveja', 'vinho', 'pizza', 'hamburguer', 'sorvete', 
            'padaria', 'mercado', 'shopping', 'fome', 'sede', 'delivery', 'entrega', 'pedido',
            'mostra', 'mostre', 'muestrame' // Agregado para portugués
        ],
        'en' => [
            'want', 'near', 'food', 'breakfast', 'lunch', 'dinner', 'restaurant', 'meal', 
            'snack', 'coffee', 'juice', 'beer', 'wine', 'pizza', 'burger', 'ice cream', 
            'bakery', 'market', 'mall', 'hungry', 'thirsty', 'delivery', 'order', 'eat',
            'show', 'show me', 'display' // Agregado para inglés
        ],
        'es' => [
            'quiero', 'cerca', 'comida', 'desayuno', 'almuerzo', 'cena', 'dame', 'restaurante', 
            'comida', 'merienda', 'café', 'jugo', 'cerveza', 'vino', 'pizza', 'hamburguesa', 
            'helado', 'panadería', 'mercado', 'centro comercial', 'hambre', 'sed', 'entrega', 
            'pedido', 'comer', 'muestrame', 'muestra', 'enseña' // Agregado para español
        ]
    ];

        $textLower = Str::lower($text);
        $langScores = array_fill_keys(array_keys($languageMarkers), 0);

        foreach ($languageMarkers as $lang => $markers) {
            foreach ($markers as $marker) {
                if (str_contains($textLower, $marker)) {
                    $langScores[$lang]++;
                }
            }
        }

        $detectedLang = array_search(max($langScores), $langScores);

        Log::info('Language detection', [
            'text' => $text,
            'scores' => $langScores,
            'detected' => $detectedLang
        ]);

        return $detectedLang;
    }

    private function getTranslationVariants(string $word): array
    {
        $variants = [$word];
        $wordLower = Str::lower($word);

        foreach ($this->categoryTranslations as $translations) {
            foreach ($translations as $langTerms) {
                foreach ($langTerms as $term) {
                    if (str_contains($term, $wordLower) || str_contains($wordLower, $term)) {
                        $variants = array_merge($variants, $langTerms);
                        break 2;
                    }
                }
            }
        }

        $allVariants = [];
        foreach ($variants as $variant) {
            $allVariants[] = $variant;
            foreach ($this->inflectors as $inflector) {
                try {
                    $allVariants[] = $inflector->pluralize($variant);
                    $allVariants[] = $inflector->singularize($variant);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return array_values(array_unique($allVariants));
    }

    private function extractKeywords(string $voiceText): array
    {
        $this->detectedLanguage = $this->detectLanguage($voiceText);
        $stopWords = config('search.stop_words', []);
        $words = explode(' ', $voiceText);
        $keywords = [];

        foreach ($words as $word) {
            $word = Str::lower(trim($word));

            if (in_array($word, $stopWords) || strlen($word) <= 2) {
                continue;
            }

            $variants = $this->getTranslationVariants($word);
            $keywords = array_merge($keywords, $variants);
        }

        $keywords = array_values(array_unique($keywords));

        Log::info('Keywords and translations extracted', [
            'original_text' => $voiceText,
            'detected_language' => $this->detectedLanguage,
            'keywords' => $keywords
        ]);

        return $keywords;
    }

    public function search(HaversineSearchRequest $request)
    {
        try {
            $validated = $request->validated();
            $radius = min($validated['radius'] ?? 5, 50);

            $haversine = "(
                6371 * acos(
                    cos(radians(?))
                    * cos(radians(business_latitude))
                    * cos(radians(business_longitude) - radians(?))
                    + sin(radians(?))
                    * sin(radians(business_latitude))
                )
            ) AS distance";

            $query = Business::query()
                ->select('businesses.*')
                ->selectRaw($haversine, [
                    $validated['latitude'],
                    $validated['longitude'],
                    $validated['latitude']
                ])
                ->having('distance', '<', $radius)
                ->with('category');

            if (!empty($validated['voice_text'])) {
                $keywords = $this->extractKeywords($validated['voice_text']);

                $query->where(function ($q) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $q->orWhere(function ($subQuery) use ($keyword) {
                            $subQuery->whereHas('category', function ($categoryQuery) use ($keyword) {
                                $categoryQuery->where('category_name', 'LIKE', "%{$keyword}%")
                                    ->orWhere('category_description', 'LIKE', "%{$keyword}%");
                            })->orWhere('business_name', 'LIKE', "%{$keyword}%")
                                ->orWhere('business_about_us', 'LIKE', "%{$keyword}%");
                        });
                    }
                });
            }

            $query->orderBy('distance');
            $businesses = $query->paginate(15);

            return response()->json([
                'business' => BusinessResource::collection($businesses),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in haversine search', [
                'error' => $e->getMessage(),
                'params' => $validated ?? []
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searching businesses: ' . $e->getMessage()
            ], 500);
        }
    }
}