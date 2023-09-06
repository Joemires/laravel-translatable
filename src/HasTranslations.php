<?php

namespace Joemires\Translatable;

use App\Models\Concerns\Translation;
use Illuminate\Database\Eloquent\Model;

trait HasTranslations
{
    protected ?string $translationLocale = null;

    public static function usingLocale(string $locale): self
    {
        return (new self())->setLocale($locale);
    }

    public function setLocale(string $locale): self
    {
        $this->translationLocale = $locale;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->translationLocale ?: app()->currentLocale();
    }

    /**
     * This model's translations
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function translations()
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    public function isDefaultLocale() {
        return config('translation.default_locale') == $this->getLocale();
    }

    public function getAttributeValue($attribute): mixed
    {
        if (! in_array($attribute, $this->translatable) || $this->isDefaultLocale()) {
            return parent::getAttributeValue($attribute);
        }

        return $this->translations
            ->where('locale', $this->getLocale())
            ->where('attribute', $attribute)
            ->value('value') ?: parent::getAttributeValue($attribute);
    }

    public function setAttribute($attribute, $value)
    {
        if (! in_array($attribute, $this->translatable) || $this->isDefaultLocale()) {
            return parent::setAttribute($attribute, $value);
        }

        $translation = $this->translations
            ->where('locale', $this->getLocale())
            ->where('attribute', $attribute)
            ->first();

        if(! $translation) {
            $translation = new Translation;
            $translation->translatable_type = $this->getMorphClass();
            $translation->translatable_id = $this->getKey();
            $translation->attribute = $attribute;
            $translation->locale = $this->getLocale();

            $translations = $this->translations->add($translation);
            $this->setRelation('translations', $translations);
        }

        return $translation->setAttribute('value', $value);
    }

    public function initializeHasTranslations () {
        if($this->eagerLoadTranslations || true) $this->with[] = 'translations';

        if($this->hideTranslations || true) $this->hidden[] = 'translations';

        // We dynamically append translatable attributes to array output
        if ($this->appendLocalizedAttributes) {
            foreach($this->translatable as $translatableAttribute) {
                $this->appends[] = $translatableAttribute;
            }
        }
    }

    public function translate() {
        foreach ($this->translations as $translation) {
            if($translation->isDirty()) {
                $translation->save();
            }
        }
    }

    /**
     * The "booted" method of the model.
     */
    public static function bootHasTranslations () {
        static::created(function (Model $model) {
            $model->translate();
        });

        static::updated(function (Model $model) {
            $model->translate();
        });

        static::saved(function (Model $model) {
            $model->translate();
        });
    }
}
