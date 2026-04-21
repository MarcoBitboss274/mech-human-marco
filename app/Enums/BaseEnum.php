<?php

namespace App\Enums;

trait BaseEnum
{
    /**
     * Get the array of enum values.
     * 
     * @return array<string, string>
     */
    public static function toArray(): array
    {
        return array_map(fn(self $enum) => $enum->value, self::cases());
    }

    /**
     * Get the array of enum values with labels.
     * 
     * @return array<string, array<string, string>>
     */
    public static function toArrayWithLabels(): array
    {
        return array_map(fn(self $enum) => ['value' => $enum->value, 'label' => $enum->label()], self::cases());
    }

    /**
     * Get the array of enum values with labels and categories.
     * 
     * @return array<string, array<string, string>>
     */
    public static function toArrayWithLabelsAndCategories(): array
    {
        return array_map(fn(self $enum) => ['value' => $enum->value, 'label' => $enum->label(), 'category' => $enum->category()], self::cases());
    }
}