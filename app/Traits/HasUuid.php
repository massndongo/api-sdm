<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Boot the trait and set the UUID when creating the model.
     */
    protected static function bootHasUuid()
    {
        static::creating(function ($model) {
            // Vérifie si le modèle n'a pas encore d'ID
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Indique que l'ID est de type string au lieu d'integer.
     */
    public function getKeyType()
    {
        return 'string';
    }

    /**
     * Indique que l'ID n'est pas auto-incrémenté.
     */
    public function getIncrementing()
    {
        return false;
    }
}
