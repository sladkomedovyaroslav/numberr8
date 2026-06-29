<?php
class Validator {
    private const RULES = [
        'full_name' => [
            'required' => true,
            'pattern' => '/^[a-zA-Zа-яА-ЯёЁ\s\-]{1,150}$/u',
            'message' => 'ФИО должно содержать только буквы, пробелы и дефисы (не более 150 символов)'
        ],
        'phone' => [
            'required' => true,
            'pattern' => '/^[\d\s\+\(\)-]{5,20}$/',
            'message' => 'Телефон должен содержать только цифры, пробелы, +, (, ), - (5-20 символов)'
        ],
        'email' => [
            'required' => true,
            'filter' => FILTER_VALIDATE_EMAIL,
            'message' => 'Введите корректный email'
        ],
        'birth_date' => [
            'required' => true,
            'custom' => 'validateBirthDate',
            'message' => 'Дата рождения не может быть в будущем'
        ],
        'gender' => [
            'required' => true,
            'values' => ['male', 'female'],
            'message' => 'Выберите пол'
        ],
        'dishes' => [
            'required' => true,
            'custom' => 'validateDishes',
            'message' => 'Выберите хотя бы одно блюдо'
        ],
        'agreed' => [
            'required' => true,
            'values' => ['1', 1, true],
            'message' => 'Вы должны согласиться с условиями бронирования'
        ]
    ];
    
    // Список блюд ресторана
    private const ALLOWED_DISHES = [
        'Плов узбекский',
        'Лагман',
        'Манты',
        'Шашлык из баранины',
        'Люля-кебаб',
        'Шурпа',
        'Долма',
        'Самса',
        'Чебуреки',
        'Пахлава',
        'Чак-чак',
        'Чай зелёный'
    ];
    
    public static function validate(array $data): array {
        $errors = [];
        
        foreach (self::RULES as $field => $rules) {
            $value = $data[$field] ?? null;
            
            if (!empty($rules['required']) && empty($value) && $value !== '0') {
                $errors[$field] = $rules['message'];
                continue;
            }
            
            if (empty($value) && $value !== '0') continue;
            
            if (isset($rules['pattern']) && !preg_match($rules['pattern'], $value)) {
                $errors[$field] = $rules['message'];
                continue;
            }
            
            if (isset($rules['filter']) && !filter_var($value, $rules['filter'])) {
                $errors[$field] = $rules['message'];
                continue;
            }
            
            if (isset($rules['values']) && !in_array($value, $rules['values'], true)) {
                $errors[$field] = $rules['message'];
                continue;
            }
            
            if (isset($rules['custom']) && method_exists(self::class, $rules['custom'])) {
                if (!self::{$rules['custom']}($value)) {
                    $errors[$field] = $rules['message'];
                }
            }
        }
        
        return $errors;
    }
    
    private static function validateBirthDate(string $value): bool {
        $timestamp = strtotime($value);
        return $timestamp !== false && $timestamp <= time();
    }
    
    private static function validateDishes(mixed $value): bool {
        if (!is_array($value) || empty($value)) return false;
        foreach ($value as $dish) {
            if (!in_array($dish, self::ALLOWED_DISHES, true)) return false;
        }
        return true;
    }
    
    public static function getAllowedDishes(): array {
        return self::ALLOWED_DISHES;
    }
}