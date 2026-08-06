<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'question_ar',
        'answer',
        'answer_ar',
        'category',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Return a locale-aware representation of this FAQ entry.
     *
     * For Arabic callers, prefer the Arabic translation when available;
     * otherwise fall back gracefully to English so no entry appears blank.
     * BUG-084: FAQ section remains in English regardless of selected app language.
     */
    public function toLocalizedArray(): array
    {
        $locale = App::getLocale();

        $question = ($locale === 'ar' && !empty($this->question_ar))
            ? $this->question_ar
            : $this->question;

        $answer = ($locale === 'ar' && !empty($this->answer_ar))
            ? $this->answer_ar
            : $this->answer;

        return [
            'id'         => $this->id,
            'question'   => $question,
            'answer'     => $answer,
            'category'   => $this->category,
            'sort_order' => $this->sort_order,
        ];
    }
}
